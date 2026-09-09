<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route($request->user()->is_admin ? 'admin.profile.edit' : 'account.profile.edit', absolute: false));
        }

        if (blank($request->user()->email)) {
            return back()->withErrors([
                'email_verification' => 'Add an email address before requesting verification.',
            ]);
        }
        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
