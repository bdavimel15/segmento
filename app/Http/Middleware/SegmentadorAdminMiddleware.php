<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SegmentadorAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('segmentador_admin', false)) {
            return redirect()->route('admin.login')
                ->with('erro', 'Acesso restrito à equipe interna.');
        }

        return $next($request);
    }
}
