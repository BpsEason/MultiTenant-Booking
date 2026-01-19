<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 開始執行完整系統初始化...');

        // 1. 先跑 ShieldSeeder：這步會建立 super_admin, tenant_admin, receptionist, provider
        $this->call(ShieldSeeder::class);

        // 2. 再跑業務資料：這裡面會用到上面建立的角色
        $this->call(TestBusinessSeeder::class);

        $this->command->info('✅ 系統初始化成功。');
    }
}