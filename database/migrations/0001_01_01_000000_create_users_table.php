<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. 建立租戶表 (必須在 users 之前，因為 users 需要關聯 tenant_id)
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('租戶名稱 (例如：某某診所、某某健身房)');
            $table->string('slug')->unique()->comment('租戶唯一識別碼 (用於 URL，例如：beyond-gym)');
            $table->json('settings')->nullable()->comment('租戶自訂設定 (包含品牌顏色、Logo URL、營業時間等)');
            $table->timestamps();
        });

        // 2. 使用者表 (整合多租戶)
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            // 關聯租戶
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->nullOnDelete()
                ->comment('使用者所屬的租戶 ID (NULL 代表中央管理員)');

            $table->string('name')->comment('使用者姓名');
            $table->string('email')->unique()->comment('登入電子郵件');
            $table->timestamp('email_verified_at')->nullable()->comment('電子郵件驗證時間');
            $table->string('password')->comment('加密密碼');
            $table->rememberToken()->comment('記住我權杖');
            $table->timestamps();

            // 索引優化
            $table->index('tenant_id');
        });

        // 3. 密碼重設權杖表
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary()->comment('關聯的使用者信箱');
            $table->string('token')->comment('重設權杖內容');
            $table->timestamp('created_at')->nullable()->comment('建立時間');
        });

        // 4. 連線 Session 表
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary()->comment('Session 唯一 ID');
            $table->foreignId('user_id')->nullable()->index()->comment('關聯的使用者 ID');
            $table->string('ip_address', 45)->nullable()->comment('使用者的 IP 位址');
            $table->text('user_agent')->nullable()->comment('瀏覽器代理程式資訊');
            $table->longText('payload')->comment('Session 資料內容');
            $table->integer('last_activity')->index()->comment('最後活動時間戳');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
        Schema::dropIfExists('tenants');
    }
};