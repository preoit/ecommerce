<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function adminEdit(Request $request): Response
    {
        return $this->render($request, 'admin');
    }

    public function customerEdit(Request $request): Response
    {
        return $this->render($request, 'customer');
    }

    private function render(Request $request, string $portal): Response
    {
        return Inertia::render('Profile/Edit', [
            'portal' => $portal,
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'hasTransactions' => $portal === 'customer'
                && DB::table('orders')->where('user_id', $request->user()->id)->exists(),
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('phone')) {
            $request->user()->phone_verified_at = null;
        }

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route($request->user()->is_admin ? 'admin.profile.edit' : 'account.profile.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        abort_if(DB::table('orders')->where('user_id', $request->user()->id)->exists(), 422, 'Accounts with order history cannot be deleted.');

        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}