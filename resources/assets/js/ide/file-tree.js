'use strict';

/**
 * Flat, sorted file list with folder-depth indentation. Deliberately not a
 * full collapsible tree — project sizes here are small (quota: 200 files)
 * and a flat list keeps the click/rename/delete wiring simple.
 */
export class FileTree {
    constructor({ container, onOpen, onRename, onDelete }) {
        this.container = container;
        this.onOpen = onOpen;
        this.onRename = onRename;
        this.onDelete = onDelete;
        this.files = [];
        this.activePath = null;
    }

    setFiles(files) {
        this.files = [...files].sort((a, b) => a.path.localeCompare(b.path));
        this.render();
    }

    setActive(path) {
        this.activePath = path;
        this.render();
    }

    render() {
        this.container.innerHTML = '';

        for (const file of this.files) {
            const depth = file.path.split('/').length - 1;
            const label = file.path.split('/').pop();

            const row = document.createElement('div');
            row.className = 'ide-file-row d-flex align-items-center justify-content-between px-2 py-1 rounded' +
                (file.path === this.activePath ? ' active' : '');
            row.style.paddingLeft = `${8 + depth * 14}px`;
            row.style.cursor = 'pointer';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'ide-file-name text-truncate';
            nameSpan.innerHTML = `<i class="ti ti-file-code ti-xs me-1 opacity-75"></i>${this._escape(label)}`;
            nameSpan.title = file.path;
            row.appendChild(nameSpan);

            const actions = document.createElement('span');
            actions.className = 'ide-file-actions';

            const renameBtn = document.createElement('button');
            renameBtn.type = 'button';
            renameBtn.className = 'btn btn-icon btn-sm p-0 me-1 text-muted';
            renameBtn.innerHTML = '<i class="ti ti-pencil ti-xs"></i>';
            renameBtn.title = 'Rename';
            renameBtn.addEventListener('click', (e) => { e.stopPropagation(); this.onRename(file.path); });
            actions.appendChild(renameBtn);

            const delBtn = document.createElement('button');
            delBtn.type = 'button';
            delBtn.className = 'btn btn-icon btn-sm p-0 text-muted';
            delBtn.innerHTML = '<i class="ti ti-trash ti-xs"></i>';
            delBtn.title = 'Delete';
            delBtn.addEventListener('click', (e) => { e.stopPropagation(); this.onDelete(file.path); });
            actions.appendChild(delBtn);

            row.appendChild(actions);
            row.addEventListener('click', () => this.onOpen(file.path));

            this.container.appendChild(row);
        }
    }

    _escape(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}
