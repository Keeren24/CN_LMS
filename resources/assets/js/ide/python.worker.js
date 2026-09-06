'use strict';

/*
 * Classic (non-module) Web Worker — Pyodide's own loader uses importScripts,
 * which is only available in classic workers, so this file intentionally
 * has no `import` statements.
 *
 * Message protocol (main thread -> worker):
 *   {type:'init', cdn, packages, sab, interruptBuffer}
 *                                          load Pyodide + curated packages.
 *                                          interruptBuffer (Int32Array) lets
 *                                          the main thread raise a real
 *                                          KeyboardInterrupt via
 *                                          pyodide.setInterruptBuffer instead
 *                                          of terminating this worker.
 *   {type:'run', code}                    execute student code
 *
 * Message protocol (worker -> main thread):
 *   {type:'ready'}
 *   {type:'init-error', message}
 *   {type:'stdout', text} / {type:'stderr', text}
 *   {type:'stdin-request'}                blocking read requested (Atomics)
 *   {type:'figure', dataUrl}              a matplotlib figure was rendered
 *   {type:'done'} / {type:'error', message}
 */

let pyodide = null;
let sab = null;

// sab layout: Int32Array[0] = flag (0 = worker waiting, 1 = line ready)
//             Int32Array[1] = byte length of the encoded line
//             bytes 8.. = UTF-8 encoded line (max 4096 bytes)
const STDIN_HEADER_BYTES = 8;
const STDIN_MAX_BYTES = 4096;

function syncStdin() {
    if (!sab) return null; // not cross-origin isolated — no SharedArrayBuffer was provided

    const control = new Int32Array(sab, 0, 2);
    Atomics.store(control, 0, 0);
    postMessage({ type: 'stdin-request' });

    Atomics.wait(control, 0, 0);

    const len = Atomics.load(control, 1);
    // TextDecoder.decode() refuses a view backed by a SharedArrayBuffer
    // ("must not be shared") — slice() copies it into a plain ArrayBuffer first.
    const bytes = new Uint8Array(sab, STDIN_HEADER_BYTES, len).slice();
    return new TextDecoder().decode(bytes) + '\n';
}

async function init(cdn, packages, sharedBuffer, interruptBuffer) {
    sab = sharedBuffer || null;

    try {
        importScripts(cdn + 'pyodide.js');
        pyodide = await self.loadPyodide({ indexURL: cdn });

        pyodide.setStdout({ batched: (text) => postMessage({ type: 'stdout', text: text + '\n' }) });
        pyodide.setStderr({ batched: (text) => postMessage({ type: 'stderr', text: text + '\n' }) });

        if (sab) {
            pyodide.setStdin({ stdin: syncStdin });
        }

        if (interruptBuffer) {
            pyodide.setInterruptBuffer(interruptBuffer);
        }

        if (packages && packages.length) {
            await pyodide.loadPackage('micropip');
            const micropip = pyodide.pyimport('micropip');
            await micropip.install(packages);
        }

        // input()'s prompt is written via stdout.write()+flush(), but Pyodide's
        // JS-side stdout writer only emits its buffer on fsync() (not flush()) —
        // so without this, the prompt sits invisible until the *next* newline
        // flushes it, together with whatever came after. Patch input() to force
        // an fsync after the prompt so the student sees it before typing.
        await pyodide.runPythonAsync(`
import builtins as __builtins, os as __os, sys as __sys
__orig_input = __builtins.input
def __patched_input(prompt=''):
    if prompt:
        __sys.stdout.write(str(prompt))
        __sys.stdout.flush()
        __os.fsync(__sys.stdout.fileno())
    return __orig_input()
__builtins.input = __patched_input
`);

        // Route matplotlib to a non-interactive backend up front (before any
        // student code runs) and silence plt.show() — there's no live display
        // in a worker, so Agg would otherwise warn on every plt.show() call.
        // Figures are captured as PNGs after each run regardless of show().
        await pyodide.runPythonAsync(`
try:
    import matplotlib
    matplotlib.use('AGG')
    import matplotlib.pyplot as __plt
    __plt.show = lambda *a, **kw: None
except ImportError:
    pass
`);

        await pyodide.runPythonAsync(`
def __capture_figures():
    import base64, io
    try:
        import matplotlib.pyplot as plt
    except ImportError:
        return
    for num in plt.get_fignums():
        fig = plt.figure(num)
        buf = io.BytesIO()
        fig.savefig(buf, format='png', bbox_inches='tight')
        buf.seek(0)
        yield base64.b64encode(buf.read()).decode('ascii')
    plt.close('all')
`);

        postMessage({ type: 'ready' });
    } catch (err) {
        postMessage({ type: 'init-error', message: String(err && err.message ? err.message : err) });
    }
}

async function run(code) {
    if (!pyodide) {
        postMessage({ type: 'error', message: 'Python runtime is not ready yet.' });
        return;
    }

    try {
        await pyodide.runPythonAsync(code);
    } catch (err) {
        postMessage({ type: 'stderr', text: String(err) + '\n' });
    }

    try {
        const hasMpl = pyodide.runPython("import sys; 'matplotlib' in sys.modules");
        if (hasMpl) {
            const figures = pyodide.runPython('list(__capture_figures())');
            const list = figures.toJs();
            for (const b64 of list) {
                postMessage({ type: 'figure', dataUrl: 'data:image/png;base64,' + b64 });
            }
            figures.destroy();
        }
    } catch (_) {
        // Figure capture is best-effort; ignore failures here.
    }

    postMessage({ type: 'done' });
}

self.onmessage = function (e) {
    const msg = e.data;
    if (msg.type === 'init') {
        init(msg.cdn, msg.packages, msg.sab, msg.interruptBuffer);
    } else if (msg.type === 'run') {
        run(msg.code);
    }
};
