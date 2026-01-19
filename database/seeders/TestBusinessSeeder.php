<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Service;
use App\Models\Appointment;
use Illuminate\Database\Seeder;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class TestBusinessSeeder extends Seeder
{
    public function run(): void
    {
        // 1. 建立/取得全域角色
        Role::firstOrCreate(['name' => Utils::getPanelUserRoleName(), 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'receptionist', 'guard_name' => 'web']);
        // 修正點：確保資料庫中有 customer 這個角色，否則後續 assignRole 會報錯
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        // 租戶 A：超越健身中心
        $gym = Tenant::updateOrCreate(['slug' => 'beyond-gym'], [
            'name' => '超越健身中心',
            'settings' => [
                'primary_color' => '#3b82f6',
                'welcome_msg' => '超越自我，從這裡開始！',
                'booking_interval' => 60,
                'cancellation_limit_hours' => 12,
            ]
        ]);
        $this->seedTenantFullData($gym, 'gym', '教練', ['私人健身課', '瑜珈團體課', '拳擊有氧']);

        // 租戶 B：晨曦中醫診所
        $clinic = Tenant::updateOrCreate(['slug' => 'sunrise-clinic'], [
            'name' => '晨曦中醫診所',
            'settings' => [
                'primary_color' => '#10b981',
                'welcome_msg' => '晨曦中醫，為您的健康把脈。',
                'booking_interval' => 30,
                'cancellation_limit_hours' => 48,
            ]
        ]);
        $this->seedTenantFullData($clinic, 'clinic', '醫師', ['經絡調理', '針灸治療', '一般內科']);

        // 租戶 C：完美佳人美甲
        $beauty = Tenant::updateOrCreate(['slug' => 'beauty-salon'], [
            'name' => '完美佳人美甲',
            'settings' => [
                'primary_color' => '#ec4899',
                'welcome_msg' => '完美佳人，為您打造指尖藝術。',
                'booking_interval' => 15,
                'cancellation_limit_hours' => 24,
            ]
        ]);
        $this->seedTenantFullData($beauty, 'beauty', '美甲師', ['單色美甲', '法式指甲', '手部深層保養']);

        $this->command->info('✅ 所有測試租戶、員工、服務與預約資料已生成。');
    }

    protected function seedTenantFullData(Tenant $tenant, $prefix, $staffTitle, array $serviceNames)
    {
        // A. 管理員
        $manager = User::updateOrCreate(['email' => "{$prefix}_manager@example.com"], [
            'name' => $tenant->name . '管理者',
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $manager->assignRole(Utils::getPanelUserRoleName());

        // B. 櫃檯
        $receptionist = User::updateOrCreate(['email' => "{$prefix}_reception@example.com"], [
            'name' => "{$tenant->name}櫃檯",
            'password' => Hash::make('password'),
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);
        $receptionist->assignRole('receptionist');

        // C. 專業人員 (2 位)
        $staffMembers = [];
        for ($i = 1; $i <= 2; $i++) {
            $staff = User::updateOrCreate(['email' => "{$prefix}_staff{$i}@example.com"], [
                'name' => "{$staffTitle}{$i}",
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);
            $staff->assignRole('staff');
            $staffMembers[] = $staff;
        }

        // D. 服務項目（重點修正：填入新欄位的測試資料）
        $services = [];
        foreach ($serviceNames as $index => $name) {
            $services[] = Service::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'duration_minutes' => rand(30, 120),
                'price' => rand(800, 3500),

                // 填入新欄位資料
                'description' => "這是 {$name} 的測試說明文字。服務時間約 " . rand(30, 120) . " 分鐘，適合初學者。",
                'image_path' => "images/{$prefix}_service" . ($index + 1) . ".jpg", // 假路徑，可替換成實際檔案
                'is_popular' => (bool) rand(0, 1), // 隨機 true/false
            ]);
        }

        // E. 客戶 (5 位)
        $customers = [];
        for ($i = 1; $i <= 5; $i++) {
            $customer = User::updateOrCreate(['email' => "{$prefix}_customer{$i}@example.com"], [
                'name' => "{$tenant->name}客戶{$i}",
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
                'email_verified_at' => now(),
            ]);
            // 修正點：必須指派 customer 角色，這樣 CustomerResource 的 getEloquentQuery 才能透過 whereHas 抓到他們
            $customer->assignRole('customer');
            $customers[] = $customer;
        }

        // F. 產生多樣化預約（避免時間衝突）
        foreach ($staffMembers as $staff) {
            // 產生 5 ~ 8 筆隨機時間預約（今日 + 未來）
            for ($j = 0; $j < rand(5, 8); $j++) {
                $service = $services[array_rand($services)];
                $duration = $service->duration_minutes;

                // 隨機日期：今日或未來 7 天內
                $baseDate = Carbon::today()->addDays(rand(0, 7));

                // 隨機開始小時（9~20 點） + 隨機分鐘（0,15,30,45）
                $hour = rand(9, 20);
                $minute = [0, 15, 30, 45][array_rand([0, 15, 30, 45])];
                $startTime = $baseDate->copy()->setHour($hour)->setMinute($minute)->setSecond(0);

                // 避撞：如果這個時間已被該員工佔用，往前/往後移 15 分鐘重試（最多試 5 次）
                $attempts = 0;
                while ($attempts < 5) {
                    $conflict = Appointment::where('staff_id', $staff->id)
                        ->where('start_time', $startTime)
                        ->exists();

                    if (!$conflict) {
                        break;
                    }

                    $startTime->addMinutes(15);
                    $attempts++;
                }

                // 如果還是衝突，就跳過這筆
                if ($attempts >= 5) {
                    continue;
                }

                $endTime = $startTime->copy()->addMinutes($duration);

                $statusList = ['pending', 'confirmed', 'completed', 'canceled'];
                $status = $statusList[array_rand($statusList)];

                Appointment::create([
                    'tenant_id'   => $tenant->id,
                    'customer_id' => $customers[array_rand($customers)]->id,
                    'staff_id'    => $staff->id,
                    'service_id'  => $service->id,
                    'start_time'  => $startTime,
                    'end_time'    => $endTime,
                    'status'      => $status,
                    'notes'       => $status === 'canceled' ? '客戶臨時取消' : '正常預約',
                ]);
            }
        }
    }
}