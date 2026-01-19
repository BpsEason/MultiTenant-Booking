<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ShieldSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // 1. 定義系統權限清單
        $permissions = [
            'view_tenant',
            'view_any_tenant',
            'create_tenant',
            'update_tenant',
            'delete_tenant', // 僅限上帝
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'delete_user',
            'view_service',
            'view_any_service',
            'create_service',
            'update_service',
            'delete_service',
            'view_appointment',
            'view_any_appointment',
            'create_appointment',
            'update_appointment',
            'delete_appointment',
            'view_revenue_stats',
            'view_shield',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // 2. 建立角色

        // A. 超級管理員
        $superAdminRole = Role::firstOrCreate(['name' => Utils::getSuperAdminName(), 'guard_name' => 'web']);
        $superAdminRole->syncPermissions(Permission::all());

        // B. 租戶管理員 (店長)
        $tenantAdminRole = Role::firstOrCreate(['name' => 'tenant_admin', 'guard_name' => 'web']);
        $tenantAdminPermissions = [
            'view_role',
            'view_any_role',
            'create_role',
            'update_role',
            'delete_role',
            'view_user',
            'view_any_user',
            'create_user',
            'update_user',
            'delete_user',
            'view_service',
            'view_any_service',
            'create_service',
            'update_service',
            'delete_service',
            'view_appointment',
            'view_any_appointment',
            'create_appointment',
            'update_appointment',
            'delete_appointment',
            'view_revenue_stats',
        ];
        $tenantAdminRole->syncPermissions($tenantAdminPermissions);

        // C. 補上這兩個缺失的角色 (解決你的報錯)
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);    // 確保 staff 存在
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']); // 這是解決「看不到客戶」的關鍵！

        // 3. 建立「中央管理中心」租戶
        $rootTenant = Tenant::firstOrCreate(['slug' => 'central-admin'], ['name' => 'SaaS 系統管理中心']);

        // 4. 建立上帝帳號
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => '中央系統管理員',
                'password' => Hash::make('password'),
                'tenant_id' => $rootTenant->id,
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($superAdminRole);
    }
}