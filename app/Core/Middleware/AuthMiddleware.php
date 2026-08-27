<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Config;
use App\Core\Request;
use App\Core\Response;

final class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, ?string $param = null): Response
    {
        if (!Auth::check()) {
            if ($request->isAjax()) {
                return Response::json(['message' => 'Unauthenticated.'], 401);
            }

            \App\Core\Session::put('_intended_url', $request->fullUrl());

            return Response::redirect(Config::get('auth.login_path', '/login'));
        }

        return $next($request);
    }
}
