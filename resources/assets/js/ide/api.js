'use strict';

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

async function request(url, method, body) {
    const res = await fetch(url, {
        method,
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            Accept: 'application/json',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    let data = null;
    try {
        data = await res.json();
    } catch (_) {
        // no JSON body (e.g. 204)
    }

    if (!res.ok) {
        const message = data?.message || data?.errors ? Object.values(data.errors || {}).flat().join(' ') || data.message : `Request failed (${res.status})`;
        const err = new Error(message);
        err.status = res.status;
        err.data = data;
        throw err;
    }

    return data;
}

export const IdeApi = {
    files: (projectUrl) => request(`${projectUrl}/files`, 'GET'),
    fileContent: (projectUrl, path) => request(`${projectUrl}/file?path=${encodeURIComponent(path)}`, 'GET'),
    saveFile: (projectUrl, path, content) => request(`${projectUrl}/file`, 'PUT', { path, content }),
    createFile: (projectUrl, path) => request(`${projectUrl}/file`, 'POST', { path }),
    renameFile: (projectUrl, oldPath, newPath) => request(`${projectUrl}/file`, 'PATCH', { old_path: oldPath, new_path: newPath }),
    deleteFile: (projectUrl, path) => request(`${projectUrl}/file`, 'DELETE', { path }),
    snapshots: (projectUrl) => request(`${projectUrl}/snapshots`, 'GET'),
    snapshot: (projectUrl, label) => request(`${projectUrl}/snapshots`, 'POST', { label }),
    restore: (projectUrl, snapshotId) => request(`${projectUrl}/restore/${snapshotId}`, 'POST'),
};
