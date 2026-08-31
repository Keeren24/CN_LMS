<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Quotas
    |--------------------------------------------------------------------------
    |
    | Enforced server-side on every write. Keeps the cn_lms schema bounded
    | since everything is stored as plain rows (MEDIUMTEXT), not blobs.
    |
    */

    'max_projects_per_student' => 20,
    'max_files_per_project' => 200,
    'max_file_size_bytes' => 1024 * 1024,       // 1 MB per file
    'max_project_size_bytes' => 5 * 1024 * 1024, // 5 MB per project (sum of files)
    'max_snapshots_per_project' => 10,

    /*
    |--------------------------------------------------------------------------
    | Pyodide
    |--------------------------------------------------------------------------
    |
    | Pinned CDN version. A matching copy should also be mirrored into
    | public/vendor/pyodide/ as an offline fallback (see resources/assets/js
    | /ide-python.worker.js for the load order).
    |
    */

    'pyodide_version' => 'v0.26.4',
    'pyodide_cdn' => 'https://cdn.jsdelivr.net/pyodide/v0.26.4/full/',

    /*
    |--------------------------------------------------------------------------
    | Curated Python package sets
    |--------------------------------------------------------------------------
    |
    | Students never pip-install anything themselves. A project picks one of
    | these named sets; the worker preloads it via micropip on first Run.
    | Keep lists short — every package here downloads (once, cached) to the
    | student's browser the first time they run a project using it.
    |
    */

    'package_sets' => [
        'basic' => [
            'label' => 'Basic (Python standard library only)',
            'packages' => [],
        ],
        'data' => [
            'label' => 'Data (numpy, pandas, matplotlib)',
            'packages' => ['numpy', 'pandas', 'matplotlib'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Starter templates
    |--------------------------------------------------------------------------
    |
    | Seeded directly into a new project's files on creation. Keyed by kind.
    |
    */

    'templates' => [
        'web' => [
            'entry_file' => 'index.html',
            'files' => [
                'index.html' => <<<'HTML'
                <!DOCTYPE html>
                <html lang="en">
                <head>
                    <meta charset="UTF-8">
                    <title>My Project</title>
                    <link rel="stylesheet" href="style.css">
                </head>
                <body>
                    <h1>Hello, world!</h1>
                    <p>Edit index.html, style.css and app.js, then hit Run.</p>
                    <script src="app.js"></script>
                </body>
                </html>
                HTML,
                'style.css' => <<<'CSS'
                body {
                    font-family: sans-serif;
                    margin: 2rem;
                }
                CSS,
                'app.js' => <<<'JS'
                console.log('Hello from app.js!');
                JS,
            ],
        ],
        'python' => [
            'entry_file' => 'main.py',
            'files' => [
                'main.py' => <<<'PY'
                name = input("What's your name? ")
                print(f"Hello, {name}!")
                PY,
            ],
        ],
    ],

];
