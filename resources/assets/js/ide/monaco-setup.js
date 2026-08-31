'use strict';

import * as monaco from 'monaco-editor';
import editorWorkerUrl from 'monaco-editor/esm/vs/editor/editor.worker?worker&url';
import jsonWorkerUrl from 'monaco-editor/esm/vs/language/json/json.worker?worker&url';
import cssWorkerUrl from 'monaco-editor/esm/vs/language/css/css.worker?worker&url';
import htmlWorkerUrl from 'monaco-editor/esm/vs/language/html/html.worker?worker&url';
import tsWorkerUrl from 'monaco-editor/esm/vs/language/typescript/ts.worker?worker&url';

/**
 * In Vite dev mode, a worker URL resolves to the Vite dev server's own
 * origin, not the app's — and `new Worker(crossOriginUrl)` is rejected by
 * the browser regardless of CORS (unlike a plain script/module import).
 * Fetching the compiled worker as text and building a same-origin Blob URL
 * works in both dev and production. Monaco accepts a Promise here (see
 * defaultWorkerFactory.js's isPromiseLike check), so this can be async.
 * Same fix as the Pyodide worker in ide/python-runner.js.
 */
async function createModuleWorker(url) {
    const res = await fetch(url);
    let code = await res.text();
    // Vite dev mode serves every ES module (including Monaco's own internal
    // dependencies, e.g. `from "/node_modules/monaco-editor/esm/vs/..."`,
    // plus its own `import "/node_modules/vite/dist/client/env.mjs"`
    // injected for import.meta.env support) using root-relative absolute
    // paths. Those resolve fine against Vite's own dev-server origin, but
    // once this code is moved into a same-origin Blob (see below), they'd
    // resolve against the *app's* origin instead and 404, crashing the
    // worker's module graph before it can run. Rewriting them to
    // fully-qualified URLs against Vite's origin fixes this — a
    // cross-origin `import` (unlike `new Worker(...)`) is allowed as long
    // as the resource has CORS headers, which Vite's dev server provides.
    // No-op in production, where these paths don't occur.
    const origin = new URL(url).origin;
    code = code.replace(/((?:from|import)\s*["'])\//g, `$1${origin}/`);
    const blobUrl = URL.createObjectURL(new Blob([code], { type: 'application/javascript' }));
    try {
        return new Worker(blobUrl, { type: 'module' });
    } finally {
        URL.revokeObjectURL(blobUrl);
    }
}

self.MonacoEnvironment = {
    getWorker(_moduleId, label) {
        if (label === 'json') return createModuleWorker(jsonWorkerUrl);
        if (label === 'css' || label === 'scss' || label === 'less') return createModuleWorker(cssWorkerUrl);
        if (label === 'html' || label === 'handlebars' || label === 'razor') return createModuleWorker(htmlWorkerUrl);
        if (label === 'typescript' || label === 'javascript') return createModuleWorker(tsWorkerUrl);
        return createModuleWorker(editorWorkerUrl);
    },
};

const EXT_LANGUAGE = {
    html: 'html',
    htm: 'html',
    css: 'css',
    scss: 'scss',
    js: 'javascript',
    mjs: 'javascript',
    json: 'json',
    py: 'python',
    md: 'markdown',
    txt: 'plaintext',
};

export function languageForPath(path) {
    const ext = path.split('.').pop().toLowerCase();
    return EXT_LANGUAGE[ext] || 'plaintext';
}

export function createEditor(container, theme = 'vs-dark') {
    return monaco.editor.create(container, {
        automaticLayout: true,
        fontSize: 14,
        minimap: { enabled: false },
        theme,
        scrollBeyondLastLine: false,
        tabSize: 4,
    });
}

export { monaco };
