'use strict';

import { Terminal } from '@xterm/xterm';
import { FitAddon } from '@xterm/addon-fit';
import '@xterm/xterm/css/xterm.css';

const STDIN_HEADER_BYTES = 8;
const STDIN_MAX_BYTES = 4096;

export class PythonRunner {
    constructor({ container, figuresContainer, cdn, packages, onStatusChange }) {
        this.cdn = cdn;
        this.packages = packages || [];
        this.figuresContainer = figuresContainer || null;
        this.onStatusChange = onStatusChange || (() => {});
        this.worker = null;
        this.ready = false;
        this.running = false;
        this.sab = typeof SharedArrayBuffer !== 'undefined' ? new SharedArrayBuffer(STDIN_HEADER_BYTES + STDIN_MAX_BYTES) : null;

        this.term = new Terminal({
            convertEol: true,
            fontSize: 13,
            fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Consolas, monospace',
            theme: { background: '#1e1e2f' },
            cursorBlink: true,
        });
        this.fitAddon = new FitAddon();
        this.term.loadAddon(this.fitAddon);
        this.term.open(container);
        this.fitAddon.fit();

        this.inputBuffer = '';
        this.awaitingInput = false;
        this._bindTerminalInput();

        window.addEventListener('resize', () => this.fitAddon.fit());
    }

    _bindTerminalInput() {
        this.term.onData((data) => {
            if (!this.awaitingInput) return;

            for (const ch of data) {
                if (ch === '\r') {
                    this.term.write('\r\n');
                    this._submitStdin(this.inputBuffer);
                    this.inputBuffer = '';
                    this.awaitingInput = false;
                    return;
                } else if (ch === '') { // backspace
                    if (this.inputBuffer.length > 0) {
                        this.inputBuffer = this.inputBuffer.slice(0, -1);
                        this.term.write('\b \b');
                    }
                } else if (ch >= ' ') {
                    this.inputBuffer += ch;
                    this.term.write(ch);
                }
            }
        });
    }

    _submitStdin(line) {
        if (!this.sab) return;
        const bytes = new TextEncoder().encode(line);
        const truncated = bytes.slice(0, STDIN_MAX_BYTES);

        const view = new Uint8Array(this.sab, STDIN_HEADER_BYTES, STDIN_MAX_BYTES);
        view.set(truncated);

        const control = new Int32Array(this.sab, 0, 2);
        Atomics.store(control, 1, truncated.length);
        Atomics.store(control, 0, 1);
        Atomics.notify(control, 0);
    }

    async boot() {
        this.onStatusChange('loading');
        this.term.writeln('Starting Python (this can take a few seconds the first time)...');

        clearTimeout(this._bootTimeout);
        this._bootTimeout = setTimeout(() => {
            if (this.ready) return;
            this.term.writeln('\x1b[31mTimed out waiting for Python to start. This usually means the Pyodide CDN is unreachable from this network — check your connection and try again.\x1b[0m');
            this.onStatusChange('error');
        }, 30000);

        try {
            this.worker = await this._createWorker('./python.worker.js');
        } catch (err) {
            clearTimeout(this._bootTimeout);
            this.term.writeln(`\x1b[31mFailed to load the Python worker: ${err.message}\x1b[0m`);
            this.onStatusChange('error');
            return;
        }

        this.worker.onmessage = (e) => this._handleMessage(e.data);
        this.worker.onerror = (e) => {
            clearTimeout(this._bootTimeout);
            this.term.writeln(`\x1b[31mWorker error: ${e.message}\x1b[0m`);
            this.onStatusChange('error');
        };
        this.worker.postMessage({ type: 'init', cdn: this.cdn, packages: this.packages, sab: this.sab });
    }

    /**
     * In Vite dev mode, import.meta.url resolves to the Vite dev server's own
     * origin, not the app's — and `new Worker(crossOriginUrl)` is blocked by
     * the browser regardless of CORS (unlike a plain script/module import).
     * Fetching the script as text and building a same-origin Blob URL works
     * in both dev and production, since a blob: URL always inherits the
     * origin of the page that created it.
     */
    async _createWorker(relativeUrl) {
        const scriptUrl = new URL(relativeUrl, import.meta.url).href;
        const res = await fetch(scriptUrl);
        if (!res.ok) throw new Error(`could not fetch worker script (${res.status})`);
        const code = await res.text();
        const blobUrl = URL.createObjectURL(new Blob([code], { type: 'application/javascript' }));
        try {
            return new Worker(blobUrl);
        } finally {
            URL.revokeObjectURL(blobUrl);
        }
    }

    _handleMessage(msg) {
        switch (msg.type) {
            case 'ready':
                clearTimeout(this._bootTimeout);
                this.ready = true;
                this.term.writeln('Ready.\r\n');
                this.onStatusChange('ready');
                break;
            case 'init-error':
                clearTimeout(this._bootTimeout);
                this.term.writeln(`\x1b[31mFailed to start Python: ${msg.message}\x1b[0m`);
                this.onStatusChange('error');
                break;
            case 'stdout':
                this.term.write(msg.text.replace(/\n/g, '\r\n'));
                break;
            case 'stderr':
                this.term.write(`\x1b[31m${msg.text.replace(/\n/g, '\r\n')}\x1b[0m`);
                break;
            case 'stdin-request':
                this.awaitingInput = true;
                break;
            case 'figure':
                this._renderFigure(msg.dataUrl);
                break;
            case 'done':
                this.running = false;
                this.onStatusChange('ready');
                break;
            case 'error':
                this.term.writeln(`\x1b[31m${msg.message}\x1b[0m`);
                this.running = false;
                this.onStatusChange('ready');
                break;
        }
    }

    _renderFigure(dataUrl) {
        if (!this.figuresContainer) return;
        const img = document.createElement('img');
        img.src = dataUrl;
        img.className = 'ide-figure img-fluid rounded border mt-2 mb-2';
        this.figuresContainer.appendChild(img);
        // The figures panel only takes layout space once it has content
        // (see #idePyFigures:has(.ide-figure) in editor.blade.php), which
        // shrinks the terminal — refit it once the browser reflows.
        requestAnimationFrame(() => this.fitAddon.fit());
    }

    run(code) {
        if (!this.ready || this.running) return;

        if (this.figuresContainer) this.figuresContainer.innerHTML = '';
        this.term.clear();
        requestAnimationFrame(() => this.fitAddon.fit());
        this.running = true;
        this.onStatusChange('running');
        this.worker.postMessage({ type: 'run', code });
    }

    stop() {
        if (this.worker) this.worker.terminate();
        this.running = false;
        this.ready = false;
        this.awaitingInput = false;
        this.term.writeln('\r\n\x1b[33m--- stopped ---\x1b[0m');
        this.onStatusChange('stopped');
        this.boot(); // spin up a fresh worker so the next Run is instant
    }

    destroy() {
        clearTimeout(this._bootTimeout);
        if (this.worker) this.worker.terminate();
    }
}
