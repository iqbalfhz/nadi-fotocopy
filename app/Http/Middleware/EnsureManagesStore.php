<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Membatasi akses Panel Admin ke role owner (§7.1).
 *
 * Pembatasan ditegakkan di server, bukan sekadar menyembunyikan menu di tampilan.
 */
class EnsureManagesStore
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if($user === null || ! $user->canManageStore(), 403);

        return $next($request);
    }
}
