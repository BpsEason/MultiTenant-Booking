<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. 建立 tenants 表
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('name')->comment('租戶名稱 (例如：某某診所、某某健身房)');
                $table->string('slug')->unique()->comment('租戶唯一識別碼 (用於 URL，例如：beyond-gym)');
                $table->json('settings')->nullable()->comment('租戶自訂設定 (包含品牌顏色、Logo URL、營業時間等)');
                $table->timestamps();
            });
        }

        // 2. 修改 roles 表（支援 per-tenant roles）
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (! Schema::hasColumn('roles', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tenants')
                        ->nullOnDelete()
                        ->comment('所屬租戶 ID (NULL 代表全域角色，如超級管理員)');
                }

                $table->dropUnique(['name', 'guard_name'])->ifExists();
                $table->unique(['name', 'guard_name', 'tenant_id'], 'roles_name_guard_tenant_unique');
            });
        }

        // 3. 修改 users 表
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (! Schema::hasColumn('users', 'tenant_id')) {
                    $table->foreignId('tenant_id')
                        ->nullable()
                        ->constrained('tenants')
                        ->nullOnDelete()
                        ->index('users_tenant_id_index')
                        ->comment('使用者所屬的租戶 ID');
                }
            });
        }

        // 4. 建立 services 表
        if (! Schema::hasTable('services')) {
            Schema::create('services', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete()->comment('提供此服務的租戶');
                $table->string('name')->comment('服務名稱 (例如：私人健身課、針灸)');
                $table->text('description')->nullable()->comment('服務詳細描述');
                $table->string('image_path')->nullable()->comment('服務封面圖片路徑');
                $table->boolean('is_active')->default(true)->comment('是否啟用該服務');
                $table->integer('sort_order')->default(0)->comment('顯示排序 (由小到大)');
                $table->boolean('is_popular')->default(false)->comment('是否標記為熱門服務');
                $table->integer('duration_minutes')->comment('服務時長 (分鐘)');
                $table->decimal('price', 10, 2)->comment('服務售價');
                $table->timestamps();

                $table->index('tenant_id');
            });
        }

        // 5. 建立 appointments 表
        if (! Schema::hasTable('appointments')) {
            Schema::create('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')
                    ->constrained('tenants')
                    ->cascadeOnDelete()
                    ->comment('預約所屬租戶');
                $table->foreignId('customer_id')
                    ->constrained('users')
                    ->cascadeOnDelete()
                    ->comment('預約客戶 (User ID)');
                $table->foreignId('staff_id')
                    ->constrained('users')
                    ->cascadeOnDelete()
                    ->comment('負責處理此預約的員工/醫師/教練 (User ID)');
                $table->foreignId('service_id')
                    ->constrained('services')
                    ->cascadeOnDelete()
                    ->comment('預約的服務項目');
                $table->decimal('price', 10, 2)->nullable()->comment('成交時的價格 (紀錄用，避免服務調價影響歷史紀錄)');

                $table->dateTime('start_time')->comment('預約開始時間');
                $table->dateTime('end_time')->comment('預約結束時間');
                $table->string('status')->default('pending')->comment('預約狀態 (pending:等候中, confirmed:已確認, cancelled:已取消, completed:已完成)');

                $table->text('notes')->nullable()->comment('客戶需求備註或內部筆記');

                $table->timestamps();

                // 索引與約束
                $table->unique(['staff_id', 'start_time'], 'unique_staff_start_time');
                $table->index(['tenant_id', 'start_time', 'end_time'], 'idx_appointment_tenant_time');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            });
        }

        if (Schema::hasTable('roles') && Schema::hasColumn('roles', 'tenant_id')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropUnique('roles_name_guard_tenant_unique');
                $table->dropColumn('tenant_id');
                $table->unique(['name', 'guard_name'], 'roles_name_guard_name_unique');
            });
        }

        Schema::dropIfExists('tenants');
    }
};