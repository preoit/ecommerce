<?php

use App\Support\PermissionRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table): void {
            $table->id(); $table->string('name')->unique(); $table->string('slug')->unique();
            $table->string('description')->nullable(); $table->boolean('is_system')->default(false); $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id(); $table->string('name')->unique(); $table->string('module')->index();
            $table->string('action', 30); $table->string('label'); $table->timestamps();
        });
        Schema::create('permission_role', function (Blueprint $table): void {
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->primary(['permission_id', 'role_id']);
        });
        Schema::create('role_user', function (Blueprint $table): void {
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['role_id', 'user_id']);
        });
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('is_admin')->index();
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
        });

        $now = now();
        $roleId = DB::table('roles')->insertGetId(['name'=>'Super Admin','slug'=>'super-admin','description'=>'Full access to every administration feature.','is_system'=>true,'created_at'=>$now,'updated_at'=>$now]);
        foreach (PermissionRegistry::permissions() as $permission) {
            $id = DB::table('permissions')->insertGetId(['name'=>$permission['name'],'module'=>$permission['module'],'action'=>$permission['action'],'label'=>$permission['label'],'created_at'=>$now,'updated_at'=>$now]);
            DB::table('permission_role')->insert(['permission_id'=>$id,'role_id'=>$roleId]);
        }
        DB::table('users')->where('is_admin', true)->pluck('id')->each(fn ($userId) => DB::table('role_user')->insertOrIgnore(['role_id'=>$roleId,'user_id'=>$userId]));
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['is_active','last_login_at']));
        Schema::dropIfExists('role_user'); Schema::dropIfExists('permission_role'); Schema::dropIfExists('permissions'); Schema::dropIfExists('roles');
    }
};
