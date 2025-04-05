<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        // Eğer kullanıcı yoksa veya rolü yoksa hata fırlat
        if (!$user || !$user->hasAnyRole($roles)) {
            throw new UnauthorizedException(403, 'Bu işlemi yapmaya yetkiniz yok.');
        }

        return $next($request);
    }
}