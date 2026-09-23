<?php

namespace App\Modules\Access\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PermissionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('app/modules/access/roles/Index', ['roles'=>Role::withCount(['users','permissions'])->orderByDesc('is_system')->orderBy('name')->get()->map(fn(Role $role)=>$this->row($role))]);
    }
    public function create(): Response { return $this->form(); }
    public function edit(Role $role): Response { abort_if($role->is_system, 403, 'System roles cannot be edited.'); return $this->form($role); }
    public function store(Request $request): RedirectResponse
    {
        $data=$this->validated($request); $role=DB::transaction(function()use($data){$role=Role::create(['name'=>$data['name'],'slug'=>$this->slug($data['name']),'description'=>$data['description']??null]);$role->permissions()->sync($data['permissions']);return $role;});
        return to_route('roles.show',$role)->with('success','Role created successfully.');
    }
    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->is_system,403,'System roles cannot be edited.');$data=$this->validated($request,$role);
        DB::transaction(function()use($role,$data){$role->update(['name'=>$data['name'],'slug'=>$this->slug($data['name'],$role),'description'=>$data['description']??null]);$role->permissions()->sync($data['permissions']);});
        return to_route('roles.show',$role)->with('success','Role updated successfully.');
    }
    public function show(Role $role): Response
    {
        $role->load(['permissions','users:id,name,email,is_active']);
        return Inertia::render('app/modules/access/roles/Show',['role'=>[...$this->row($role),'description'=>$role->description,'permissions'=>$role->permissions->groupBy('module')->map(fn($items)=>$items->pluck('action')->values())->all(),'users'=>$role->users->map(fn($user)=>['id'=>$user->id,'name'=>$user->name,'email'=>$user->email,'active'=>(bool)$user->is_active])]]);
    }
    public function destroy(Role $role): RedirectResponse
    {
        if($role->is_system)throw ValidationException::withMessages(['role'=>'System roles cannot be deleted.']);
        if($role->users()->exists())throw ValidationException::withMessages(['role'=>'Remove all users from this role before deleting it.']);
        $role->delete();return to_route('roles.index')->with('success','Role deleted successfully.');
    }
    private function form(?Role $role=null): Response
    {
        $role?->load('permissions:id');
        $permissions=Permission::orderBy('module')->orderBy('action')->get()->keyBy('name');
        $groups=collect(PermissionRegistry::modules())->map(function($definition,$module)use($permissions){return ['module'=>$module,'label'=>$definition['label'],'permissions'=>collect($definition['actions'])->map(fn($label,$action)=>['id'=>$permissions->get("{$module}.{$action}")?->id,'name'=>"{$module}.{$action}",'label'=>$label])->values()];})->values();
        return Inertia::render('app/modules/access/roles/Form',['role'=>$role ? ['id'=>$role->id,'name'=>$role->name,'description'=>$role->description,'permissions'=>$role->permissions->pluck('id')->all()] : null,'permissionGroups'=>$groups]);
    }
    private function validated(Request $request,?Role $role=null):array{return $request->validate(['name'=>['required','string','max:100',Rule::unique('roles','name')->ignore($role?->id)],'description'=>['nullable','string','max:500'],'permissions'=>['required','array','min:1'],'permissions.*'=>['integer','exists:permissions,id']]);}
    private function slug(string $name,?Role $ignore=null):string{$base=Str::slug($name);$slug=$base;$i=2;while(Role::where('slug',$slug)->when($ignore,fn($q)=>$q->where('id','!=',$ignore->id))->exists())$slug="{$base}-".$i++;return $slug;}
    private function row(Role $role):array{return ['id'=>$role->id,'name'=>$role->name,'slug'=>$role->slug,'description'=>$role->description,'system'=>(bool)$role->is_system,'users'=>(int)($role->users_count??$role->users()->count()),'permissions'=>(int)($role->permissions_count??$role->permissions()->count())];}
}
