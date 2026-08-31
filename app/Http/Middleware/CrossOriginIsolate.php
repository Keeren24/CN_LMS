<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Cross-origin isolates the IDE editor page so SharedArrayBuffer + Atomics
 * are available. That's what lets the Pyodide worker implement blocking
 * input() the same way Pyodide's own console demo does.
 *
 * COEP is set to "credentialless" (not "require-corp") so third-party
 * assets — the Pyodide CDN scripts/wasm — don't need to opt in with their
 * own Cross-Origin-Resource-Policy header.
 */
class CrossOriginIsolate
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');

        return $response;
    }
}
