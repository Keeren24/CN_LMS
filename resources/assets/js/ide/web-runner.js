'use strict';

/**
 * Assembles a project's HTML/CSS/JS files into one document and renders it
 * in a sandboxed iframe. The iframe is given an opaque origin (no
 * allow-same-origin) so student script can never read the parent page's
 * cookies, localStorage, or DOM — that boundary is what makes running
 * arbitrary student JS safe without a server-side sandbox.
 */
export class WebRunner {
    constructor({ iframe, consoleEl }) {
        this.iframe = iframe;
        this.consoleEl = consoleEl;

        window.addEventListener('message', (e) => {
            if (e.source !== this.iframe.contentWindow) return;
            this._handleConsoleMessage(e.data);
        });
    }

    run(files, entryFile) {
        const byPath = new Map(files.map((f) => [f.path, f.content ?? '']));
        const entry = byPath.get(entryFile) ?? '';

        const consoleShim = `
<script>
(function () {
    const send = (level, args) => {
        try {
            parent.postMessage({ __ide: true, level, text: args.map(a => {
                try { return typeof a === 'string' ? a : JSON.stringify(a); }
                catch (e) { return String(a); }
            }).join(' ') }, '*');
        } catch (e) {}
    };
    ['log', 'info', 'warn', 'error'].forEach((level) => {
        const orig = console[level];
        console[level] = function (...args) { send(level, args); orig.apply(console, args); };
    });
    window.onerror = function (message, source, lineno, colno) {
        send('error', [message + ' (line ' + lineno + ')']);
    };
})();
<\/script>`;

        let html = entry;

        // Inline local <link rel="stylesheet" href="..."> and <script src="..."> files.
        html = html.replace(/<link\s+[^>]*rel=["']stylesheet["'][^>]*href=["']([^"':]+)["'][^>]*>/gi, (match, href) => {
            const css = byPath.get(this._normalize(href));
            return css !== undefined ? `<style>\n${css}\n</style>` : match;
        });

        html = html.replace(/<script\s+[^>]*src=["']([^"':]+)["'][^>]*><\/script>/gi, (match, src) => {
            const js = byPath.get(this._normalize(src));
            return js !== undefined ? `<script>\n${js}\n<\/script>` : match;
        });

        if (/<head[^>]*>/i.test(html)) {
            html = html.replace(/<head([^>]*)>/i, `<head$1>${consoleShim}`);
        } else {
            html = consoleShim + html;
        }

        if (this.consoleEl) this.consoleEl.innerHTML = '';

        // Recreate the iframe each run so leftover globals/timers/listeners
        // from the previous run don't leak into the new one.
        const fresh = document.createElement('iframe');
        fresh.id = this.iframe.id;
        fresh.className = this.iframe.className;
        fresh.setAttribute('sandbox', 'allow-scripts allow-modals allow-forms');
        this.iframe.replaceWith(fresh);
        this.iframe = fresh;
        this.iframe.srcdoc = html;
    }

    _normalize(path) {
        return path.replace(/^\.?\//, '');
    }

    _handleConsoleMessage(data) {
        if (!data || !data.__ide || !this.consoleEl) return;

        const line = document.createElement('div');
        line.className = `ide-console-line ide-console-${data.level}`;
        line.textContent = data.text;
        this.consoleEl.appendChild(line);
        this.consoleEl.scrollTop = this.consoleEl.scrollHeight;
    }
}
