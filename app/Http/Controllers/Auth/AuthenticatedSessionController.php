<?php
namespace App\Http\Controllers\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
class AuthenticatedSessionController
{
    public function createCustomer(): Response { return Inertia::render('Auth/Login',['canResetPassword'=>true,'status'=>session('status'),'portal'=>'customer','loginRoute'=>'customer.login.store','registerRoute'=>'customer.register']); }
    public function createAdmin(): Response { return Inertia::render('Auth/Login',['canResetPassword'=>true,'status'=>session('status'),'portal'=>'admin','loginRoute'=>'admin.login.store','registerRoute'=>null]); }
    public function storeCustomer(LoginRequest $request): RedirectResponse { return $this->authenticateForPortal($request,false); }
    public function storeAdmin(LoginRequest $request): RedirectResponse { return $this->authenticateForPortal($request,true); }
    private function authenticateForPortal(LoginRequest $request,bool $admin): RedirectResponse
    {
        $request->authenticate();
        if((bool)$request->user()->is_admin!==$admin){ Auth::guard('web')->logout();$request->session()->invalidate();$request->session()->regenerateToken();throw ValidationException::withMessages(['email'=>$admin?'This account does not have admin access.':'Please use the admin login page for this account.']); }
        $request->session()->regenerate();
        return redirect()->intended($admin?route('dashboard',absolute:false):route('account.dashboard',absolute:false));
    }
    public function destroy(Request $request): RedirectResponse { Auth::guard('web')->logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect('/'); }
}