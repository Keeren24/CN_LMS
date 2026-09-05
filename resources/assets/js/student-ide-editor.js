'use strict';

// Below this width the toolbar and output panel don't fit — matches the
// `.ide-mobile-block` CSS gate in editor.blade.php. Kept as a dynamic
// import (not a static top-level one) specifically so a blocked visitor
// never pays for downloading Monaco/xterm/Pyodide-adjacent code at all —
// a static import would already be fetched and evaluated by the time any
// runtime check here could run.
const IDE_MIN_WIDTH = 992;

document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('ideRoot');
    if (!root) return;

    // The width gate only exists to skip paying for Monaco/xterm/Pyodide on
    // a phone-sized viewport — it must stay reactive to resize, not just
    // checked once here. Otherwise a page that first rendered under a
    // narrow viewport (e.g. DevTools' device toolbar) would never boot even
    // after the viewport widens back out, leaving the loading overlay
    // stuck forever since nothing else re-triggers initialization.
    let booted = false;
    function tryBoot() {
        if (booted || window.innerWidth < IDE_MIN_WIDTH) return;
        booted = true;
        window.removeEventListener('resize', tryBoot);
        bootIde(root);
    }

    tryBoot();
    window.addEventListener('resize', tryBoot);
});

async function bootIde(root) {
    const [{ createEditor, languageForPath, monaco }, { FileTree }, { IdeApi }, { WebRunner }] = await Promise.all([
        import('./ide/monaco-setup.js'),
        import('./ide/file-tree.js'),
        import('./ide/api.js'),
        import('./ide/web-runner.js'),
    ]);

    const config = JSON.parse(root.dataset.config);
    const projectUrl = config.projectUrl;

    const editorContainer = document.getElementById('ideEditorContainer');
    const tabsEl = document.getElementById('ideTabs');
    const fileTreeEl = document.getElementById('ideFileTree');
    const saveStatusEl = document.getElementById('ideSaveStatus');
    const runBtn = document.getElementById('ideRunBtn');
    const stopBtn = document.getElementById('ideStopBtn');
    const downloadBtn = document.getElementById('ideDownloadBtn');
    const historyModalEl = document.getElementById('ideHistoryModal');
    const snapshotListEl = document.getElementById('ideSnapshotList');
    const newFileModalEl = document.getElementById('ideNewFileModal');
    const newFileForm = document.getElementById('ideNewFileForm');
    const newFilePathInput = document.getElementById('ideNewFilePath');
    const newFileError = document.getElementById('ideNewFileError');
    const newFileSpinner = document.getElementById('ideNewFileSpinner');
    const snapshotModalEl = document.getElementById('ideSnapshotModal');
    const snapshotForm = document.getElementById('ideSnapshotForm');
    const snapshotLabelInput = document.getElementById('ideSnapshotLabel');
    const snapshotError = document.getElementById('ideSnapshotError');
    const snapshotSpinner = document.getElementById('ideSnapshotSpinner');
    const loadingOverlay = document.getElementById('ideLoadingOverlay');
    const loadingText = document.getElementById('ideLoadingText');
    const loadingSpinner = document.getElementById('ideLoadingSpinner');

    const editor = createEditor(editorContainer);

    // ── State ──────────────────────────────────────────────────────────
    const openTabs = [];       // [{path, model}]
    let activePath = null;
    const dirtySet = new Set();
    let saveTimer = null;
    const LOCAL_KEY = `ide-draft-${config.projectId}`;

    // ── Local crash-net (localStorage) ────────────────────────────────
    function loadLocalDrafts() {
        try {
            return JSON.parse(localStorage.getItem(LOCAL_KEY) || '{}');
        } catch (_) {
            return {};
        }
    }

    function saveLocalDraft(path, content) {
        try {
            const drafts = loadLocalDrafts();
            drafts[path] = { content, savedAt: Date.now() };
            localStorage.setItem(LOCAL_KEY, JSON.stringify(drafts));
        } catch (_) { /* storage full/unavailable — ignore, server autosave still applies */ }
    }

    function clearLocalDraft(path) {
        try {
            const drafts = loadLocalDrafts();
            delete drafts[path];
            localStorage.setItem(LOCAL_KEY, JSON.stringify(drafts));
        } catch (_) { /* ignore */ }
    }

    // ── File tree ──────────────────────────────────────────────────────
    const tree = new FileTree({
        container: fileTreeEl,
        onOpen: (path) => openFile(path),
        onRename: async (path) => {
            const { value: newPath } = await Swal.fire({
                title: 'Rename file',
                input: 'text',
                inputValue: path,
                inputValidator: (value) => (!value || !value.trim() ? 'Please enter a file path.' : undefined),
                showCancelButton: true,
                confirmButtonText: 'Rename',
            });
            if (!newPath || newPath === path) return;
            try {
                await IdeApi.renameFile(projectUrl, path, newPath);
                await refreshTree();
                renameOpenTab(path, newPath);
            } catch (err) {
                Swal.fire({ title: 'Could not rename', text: err.message, icon: 'error' });
            }
        },
        onDelete: async (path) => {
            if (path === config.entryFile) {
                Swal.fire({ title: 'Cannot delete', text: 'The entry file cannot be deleted.', icon: 'error' });
                return;
            }

            const result = await Swal.fire({
                title: `Delete "${path}"?`,
                text: 'This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc3545',
            });
            if (!result.isConfirmed) return;

            try {
                await IdeApi.deleteFile(projectUrl, path);
                closeTab(path);
                await refreshTree();
            } catch (err) {
                Swal.fire({ title: 'Could not delete', text: err.message, icon: 'error' });
            }
        },
    });

    async function refreshTree() {
        const data = await IdeApi.files(projectUrl);
        tree.setFiles(data.files);
        return data.files;
    }

    // ── Tabs / editor models ──────────────────────────────────────────
    function renderTabs() {
        tabsEl.innerHTML = '';
        for (const tab of openTabs) {
            const el = document.createElement('div');
            el.className = 'ide-tab d-inline-flex align-items-center px-3 py-2 border-end' +
                (tab.path === activePath ? ' active' : '');

            const label = document.createElement('span');
            label.className = 'text-truncate';
            label.style.maxWidth = '140px';
            label.textContent = tab.path + (dirtySet.has(tab.path) ? ' •' : '');
            label.title = tab.path;
            el.appendChild(label);

            const close = document.createElement('i');
            close.className = 'ti ti-x ti-xs ms-2 opacity-50';
            close.addEventListener('click', (e) => { e.stopPropagation(); closeTab(tab.path); });
            el.appendChild(close);

            el.addEventListener('click', () => activateTab(tab.path));
            tabsEl.appendChild(el);
        }
    }

    function activateTab(path) {
        const tab = openTabs.find((t) => t.path === path);
        if (!tab) return;
        activePath = path;
        editor.setModel(tab.model);
        tree.setActive(path);
        renderTabs();
    }

    function closeTab(path) {
        const idx = openTabs.findIndex((t) => t.path === path);
        if (idx === -1) return;
        openTabs[idx].model.dispose();
        openTabs.splice(idx, 1);

        if (activePath === path) {
            activePath = openTabs.length ? openTabs[openTabs.length - 1].path : null;
            if (activePath) {
                editor.setModel(openTabs.find((t) => t.path === activePath).model);
                tree.setActive(activePath);
            } else {
                editor.setModel(monaco.editor.createModel('', 'plaintext'));
            }
        }
        renderTabs();
    }

    function renameOpenTab(oldPath, newPath) {
        const tab = openTabs.find((t) => t.path === oldPath);
        if (!tab) return;
        tab.path = newPath;
        if (activePath === oldPath) activePath = newPath;
        renderTabs();
    }

    async function openFile(path) {
        let tab = openTabs.find((t) => t.path === path);
        if (!tab) {
            const drafts = loadLocalDrafts();
            const local = drafts[path];

            const server = await IdeApi.fileContent(projectUrl, path);
            let content = server.content ?? '';

            if (local && new Date(local.savedAt) > new Date(server.updated_at)) {
                const result = await Swal.fire({
                    title: 'Restore unsaved changes?',
                    text: `You have unsaved local changes to ${path} newer than the server copy.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Restore',
                });
                if (result.isConfirmed) {
                    content = local.content;
                    dirtySet.add(path);
                }
            }

            const model = monaco.editor.createModel(content, languageForPath(path));
            model.onDidChangeContent(() => onEdit(path));
            tab = { path, model, serverUpdatedAt: server.updated_at };
            openTabs.push(tab);
        }
        activateTab(path);
    }

    function onEdit(path) {
        dirtySet.add(path);
        renderTabs();
        setSaveStatus('editing');

        const tab = openTabs.find((t) => t.path === path);
        if (tab) saveLocalDraft(path, tab.model.getValue());

        clearTimeout(saveTimer);
        saveTimer = setTimeout(() => saveFile(path), 800);
    }

    async function saveFile(path) {
        const tab = openTabs.find((t) => t.path === path);
        if (!tab) return;

        setSaveStatus('saving');
        try {
            await IdeApi.saveFile(projectUrl, path, tab.model.getValue());
            dirtySet.delete(path);
            clearLocalDraft(path);
            renderTabs();
            setSaveStatus('saved');
        } catch (err) {
            setSaveStatus('error', err.message);
        }
    }

    function setSaveStatus(state, message) {
        const map = {
            editing: ['Editing…', 'text-muted'],
            saving: ['Saving…', 'text-muted'],
            saved: ['All changes saved', 'text-success'],
            error: [message || 'Save failed', 'text-danger'],
        };
        const [text, cls] = map[state] || ['', ''];
        saveStatusEl.textContent = text;
        saveStatusEl.className = `small ${cls}`;
    }

    // Flush on tab close / navigation away.
    window.addEventListener('beforeunload', () => {
        for (const path of dirtySet) {
            const tab = openTabs.find((t) => t.path === path);
            if (!tab) continue;
            const token = document.querySelector('meta[name="csrf-token"]').content;
            const blob = new Blob([JSON.stringify({ path, content: tab.model.getValue() })], { type: 'application/json' });
            navigator.sendBeacon(`${projectUrl}/file?_method=PUT&_token=${encodeURIComponent(token)}`, blob);
        }
    });

    // ── New file ───────────────────────────────────────────────────────
    function setModalError(el, message) {
        if (!el) return;
        el.textContent = message || '';
        el.classList.toggle('d-none', !message);
    }

    function setModalBusy(button, spinner, busy) {
        if (button) button.disabled = busy;
        if (spinner) spinner.classList.toggle('d-none', !busy);
    }

    newFileModalEl?.addEventListener('show.bs.modal', () => {
        newFileForm.reset();
        setModalError(newFileError, null);
    });
    newFileModalEl?.addEventListener('shown.bs.modal', () => newFilePathInput.focus());

    newFileForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const path = newFilePathInput.value.trim();
        if (!path) return;

        setModalError(newFileError, null);
        setModalBusy(newFileForm.querySelector('button[type="submit"]'), newFileSpinner, true);
        try {
            await IdeApi.createFile(projectUrl, path);
            await refreshTree();
            openFile(path);
            bootstrap.Modal.getInstance(newFileModalEl)?.hide();
        } catch (err) {
            setModalError(newFileError, err.message);
        } finally {
            setModalBusy(newFileForm.querySelector('button[type="submit"]'), newFileSpinner, false);
        }
    });

    // ── Snapshot ───────────────────────────────────────────────────────
    snapshotModalEl?.addEventListener('show.bs.modal', () => {
        snapshotForm.reset();
        snapshotLabelInput.value = new Date().toLocaleString();
        setModalError(snapshotError, null);
    });
    snapshotModalEl?.addEventListener('shown.bs.modal', () => {
        snapshotLabelInput.focus();
        snapshotLabelInput.select();
    });

    snapshotForm?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const label = snapshotLabelInput.value.trim();
        if (!label) return;

        setModalError(snapshotError, null);
        setModalBusy(snapshotForm.querySelector('button[type="submit"]'), snapshotSpinner, true);
        try {
            await IdeApi.snapshot(projectUrl, label);
            bootstrap.Modal.getInstance(snapshotModalEl)?.hide();
        } catch (err) {
            setModalError(snapshotError, err.message);
        } finally {
            setModalBusy(snapshotForm.querySelector('button[type="submit"]'), snapshotSpinner, false);
        }
    });

    // ── Restore points ─────────────────────────────────────────────────
    async function renderSnapshotList() {
        snapshotListEl.innerHTML = '<div class="text-muted small px-2 py-3 text-center">Loading…</div>';

        let snapshots;
        try {
            snapshots = await IdeApi.snapshots(projectUrl);
        } catch (err) {
            snapshotListEl.innerHTML = `<div class="text-danger small px-2 py-3 text-center">${escapeHtml(err.message)}</div>`;
            return;
        }

        if (!snapshots.length) {
            snapshotListEl.innerHTML = '<div class="text-muted small px-2 py-3 text-center">No restore points yet. Use "Snapshot" to create one.</div>';
            return;
        }

        snapshotListEl.innerHTML = '';
        for (const snap of snapshots) {
            const item = document.createElement('div');
            item.className = 'list-group-item d-flex align-items-center justify-content-between';

            const label = document.createElement('div');
            label.innerHTML = `<div>${escapeHtml(snap.label)}</div><div class="small text-muted">${new Date(snap.created_at).toLocaleString()}</div>`;
            item.appendChild(label);

            const restoreWrap = document.createElement('div');
            restoreWrap.className = 'd-flex flex-column align-items-end gap-1';

            const restoreBtn = document.createElement('button');
            restoreBtn.type = 'button';
            restoreBtn.className = 'btn btn-sm btn-outline-primary';
            restoreBtn.textContent = 'Restore';
            restoreWrap.appendChild(restoreBtn);

            const restoreError = document.createElement('div');
            restoreError.className = 'text-danger small d-none';
            restoreWrap.appendChild(restoreError);

            restoreBtn.addEventListener('click', async () => {
                const result = await Swal.fire({
                    title: `Restore "${snap.label}"?`,
                    text: 'This replaces all current files. This cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Restore',
                    confirmButtonColor: '#dc3545',
                });
                if (!result.isConfirmed) return;
                restoreBtn.disabled = true;
                restoreError.classList.add('d-none');
                try {
                    await IdeApi.restore(projectUrl, snap.id);
                    location.reload();
                } catch (err) {
                    restoreBtn.disabled = false;
                    restoreError.textContent = err.message;
                    restoreError.classList.remove('d-none');
                }
            });
            item.appendChild(restoreWrap);

            snapshotListEl.appendChild(item);
        }
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    historyModalEl?.addEventListener('show.bs.modal', renderSnapshotList);

    // ── Download (client-side zip) ───────────────────────────────────
    downloadBtn?.addEventListener('click', async () => {
        const { default: JSZip } = await import('jszip');
        const zip = new JSZip();
        const files = await refreshTree();
        for (const f of files) {
            const content = openTabs.find((t) => t.path === f.path)?.model.getValue()
                ?? (await IdeApi.fileContent(projectUrl, f.path)).content ?? '';
            zip.file(f.path, content);
        }
        const blob = await zip.generateAsync({ type: 'blob' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `${config.projectSlug}.zip`;
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });

    // ── Boot: file tree + editor first — this must never be blocked by ──
    // ── the (possibly slow) web/Python runner setup below.             ──
    try {
        await refreshTree();
        await openFile(config.entryFile);
        setSaveStatus('saved');
        loadingOverlay?.classList.add('ide-loading-hidden');
    } catch (err) {
        loadingSpinner?.classList.add('d-none');
        if (loadingText) {
            loadingText.innerHTML = '';
            const msg = document.createElement('div');
            msg.className = 'ide-loading-error';
            msg.textContent = `Couldn't load this project: ${err.message}`;
            const retry = document.createElement('button');
            retry.type = 'button';
            retry.className = 'btn btn-sm btn-primary mt-3';
            retry.textContent = 'Retry';
            retry.addEventListener('click', () => location.reload());
            loadingText.replaceWith(msg);
            msg.after(retry);
        }
        return;
    }

    // ── Run / Stop ─────────────────────────────────────────────────────
    // Fire-and-forget: runner setup happens independently so a slow or
    // failed Pyodide/xterm load can never hold up the editor itself.
    if (config.kind === 'web') {
        const iframe = document.getElementById('ideWebFrame');
        const consoleEl = document.getElementById('ideWebConsole');
        const runner = new WebRunner({ iframe, consoleEl });

        async function runWeb() {
            const files = await refreshTree();
            const withContent = await Promise.all(files.map(async (f) => {
                const openTab = openTabs.find((t) => t.path === f.path);
                return { path: f.path, content: openTab ? openTab.model.getValue() : (await IdeApi.fileContent(projectUrl, f.path)).content };
            }));
            runner.run(withContent, config.entryFile);
        }

        runBtn.addEventListener('click', runWeb);
        runWeb(); // preview shouldn't be blank on open — run once as soon as the editor is ready

        stopBtn?.classList.add('d-none');
    } else {
        setupPythonRunner();
    }

    async function setupPythonRunner() {
        let runner;
        try {
            const { PythonRunner } = await import('./ide/python-runner.js');
            runner = new PythonRunner({
                container: document.getElementById('idePyTerminal'),
                figuresContainer: document.getElementById('idePyFigures'),
                cdn: config.pyodideCdn,
                packages: config.packages,
                onStatusChange: (status) => {
                    runBtn.disabled = status === 'loading' || status === 'running';
                    stopBtn.disabled = status !== 'running';
                },
            });
        } catch (err) {
            runBtn.disabled = true;
            stopBtn.disabled = true;
            document.getElementById('idePyTerminal').innerHTML =
                `<div class="text-danger small p-2">Failed to load the Python runtime: ${err.message}</div>`;
            return;
        }

        runner.boot();

        runBtn.addEventListener('click', async () => {
            const tab = openTabs.find((t) => t.path === config.entryFile);
            const code = tab ? tab.model.getValue() : (await IdeApi.fileContent(projectUrl, config.entryFile)).content;
            runner.run(code || '');
        });

        stopBtn.addEventListener('click', () => runner.stop());
    }
}
