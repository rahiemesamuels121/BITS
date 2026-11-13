<?php

namespace App\Services\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionService
{
    public function logout(Request $request): void
    {
        // The guard also expires the remember-me cookie and rotates its token.
        Auth::guard('web')->logout();
        // invalidate() flushes the data and destroys the old server-side session ID.
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
