<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CheckUserRole
{
    /**
     * Belirtilen rollerden herhangi birine sahip değilse JSON hata mesajı döndürür.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  mixed  ...$roles
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();
        if (!$user || !$user->hasAnyRole($roles)) {
            return response()->json([
                'success' => false,
                'message' => __('messages.unauthorized'),
            ], 403);
        }
        return $next($request);
    }
}
