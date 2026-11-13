<?php

namespace App\Http\Middleware;

use App\Services\Auth\SessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function __construct(private SessionService $sessions) {}

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->is_active) {
            $this->sessions->logout($request);

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your account is inactive. Contact your administrator.'], 401);
            }

            return redirect()->route('login')->withErrors(['email' => 'Your account is inactive. Contact your administrator.']);
        }

        return $next($request);
    }
}
