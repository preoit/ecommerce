<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search'));
        $query = User::query()->where('is_admin', true)->with('roles:id,name,slug');
        $users = $query->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
            ->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->latest('id')->paginate(15)->withQueryString()->through(fn (User $user) => $this->row($user));

        return Inertia::render('app/modules/access/users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
            'stats' => ['total'=>User::where('is_admin',true)->count(),'active'=>User::where('is_admin',true)->where('is_active',true)->count(),'roles'=>Role::count()],
        ]);
    }

    public function create(): Response { return $this->form(); }
    public function edit(User $adminUser): Response { $this->admin($adminUser); return $this->form($adminUser); }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $user = DB::transaction(function () use ($data): User {
            $user = User::create(['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'password'=>$data['password'],'is_admin'=>true,'is_active'=>$data['is_active'],'email_verified_at'=>now()]);
            $user->roles()->sync($data['roles']); return $user;
        });
        return to_route('admin-users.show', $user)->with('success', 'Admin user created successfully.');
    }

    public function update(Request $request, User $adminUser): RedirectResponse
    {
        $this->admin($adminUser); $data = $this->validated($request, $adminUser);
        if ($adminUser->isSuperAdmin() && (! $data['is_active'] || ! Role::whereIn('id',$data['roles'])->where('slug','super-admin')->exists())) {
            throw ValidationException::withMessages(['roles'=>'A Super Admin must remain active and keep the Super Admin role.']);
        }
        DB::transaction(function () use ($adminUser, $data): void {
            $values=['name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone']??null,'is_active'=>$data['is_active']];
            if (filled($data['password']??null)) $values['password']=$data['password'];
            $adminUser->update($values); $adminUser->roles()->sync($data['roles']);
        });
        return to_route('admin-users.show', $adminUser)->with('success', 'Admin user updated successfully.');
    }

    public function show(User $adminUser): Response
    {
        $this->admin($adminUser); $adminUser->load('roles.permissions');
        return Inertia::render('app/modules/access/users/Show', ['adminUser'=>$this->details($adminUser)]);
    }

    public function destroy(Request $request, User $adminUser): RedirectResponse
    {
        $this->admin($adminUser);
        if ($request->user()->is($adminUser)) throw ValidationException::withMessages(['user'=>'You cannot delete your own account.']);
        if ($adminUser->isSuperAdmin()) throw ValidationException::withMessages(['user'=>'A Super Admin account cannot be deleted.']);
        $adminUser->delete();
        return to_route('admin-users.index')->with('success', 'Admin user deleted successfully.');
    }

    private function form(?User $user=null): Response
    {
        $user?->load('roles:id');
        return Inertia::render('app/modules/access/users/Form', [
            'adminUser'=>$user ? ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone,'isActive'=>(bool)$user->is_active,'roles'=>$user->roles->pluck('id')->all()] : null,
            'roles'=>Role::query()->withCount('users')->orderBy('name')->get()->map(fn(Role $role)=>['id'=>$role->id,'name'=>$role->name,'description'=>$role->description,'users'=>$role->users_count,'system'=>$role->is_system]),
        ]);
    }

    private function validated(Request $request, ?User $user=null): array
    {
        return $request->validate([
            'name'=>['required','string','max:255'],
            'email'=>['required','email','max:255',Rule::unique('users','email')->ignore($user?->id)],
            'phone'=>['nullable','string','regex:/^\+?[0-9]{10,15}$/',Rule::unique('users','phone')->ignore($user?->id)],
            'password'=>[$user?'nullable':'required','confirmed',Password::min(12)],
            'is_active'=>['required','boolean'],
            'roles'=>['required','array','min:1'],'roles.*'=>['integer','exists:roles,id'],
        ]);
    }

    private function row(User $user): array { return ['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'phone'=>$user->phone,'roles'=>$user->roles->pluck('name')->all(),'active'=>(bool)$user->is_active,'superAdmin'=>$user->isSuperAdmin(),'lastLogin'=>$this->date($user->last_login_at),'createdAt'=>$this->date($user->created_at)]; }
    private function details(User $user): array { return [...$this->row($user),'permissions'=>$user->isSuperAdmin()?['All permissions']:$user->roles->pluck('permissions')->flatten()->pluck('name')->unique()->sort()->values()->all()]; }
    private function admin(User $user): void { abort_unless($user->is_admin, 404); }
    private function date($value): ?string { return $value?->timezone('Asia/Dhaka')->format('d M Y, h:i A'); }
}
