'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const kindRadios = document.querySelectorAll('input[name="kind"]');
    const packageSetWrap = document.getElementById('packageSetWrap');

    function syncPackageVisibility() {
        const kind = document.querySelector('input[name="kind"]:checked')?.value;
        if (packageSetWrap) packageSetWrap.style.display = kind === 'python' ? '' : 'none';
    }

    kindRadios.forEach((r) => r.addEventListener('change', syncPackageVisibility));
    syncPackageVisibility();

    document.querySelectorAll('.btn-delete-project').forEach((btn) => {
        btn.addEventListener('click', function () {
            const { name, url } = btn.dataset;

            Swal.fire({
                title: `Delete "${name}"?`,
                text: 'This permanently deletes the project and all its files. This cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#dc3545',
            }).then((result) => {
                if (!result.isConfirmed) return;

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        });
    });
});
