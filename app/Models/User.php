<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;
use Filament\Facades\Filament;

class User extends Authenticatable implements FilamentUser, HasTenants
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id', // 確保這個欄位可以被批次寫入
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            // 禁止刪除擁有超級管理員角色的使用者
            if ($user->hasRole('super_admin')) {
                throw new \Exception("無法刪除中央系統管理員。");
            }

            // 禁止刪除自己
            if ($user->id === auth()->id()) {
                throw new \Exception("您不能刪除自己的帳號。");
            }
        });
    }

    /**
     * 關聯：所屬租戶
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // --- Filament 多租戶核心邏輯 ---

    /**
     * 取得使用者可存取的租戶清單
     */
    public function getTenants(Panel $panel): Collection
    {
        // 1. 如果是超級管理員，回傳所有租戶
        if ($this->hasRole('super_admin', 'web')) {
            return Tenant::all();
        }

        // 2. 一般用戶：回傳自己所屬的租戶
        // 建議：使用 wrap 確保回傳 Collection
        return collect([$this->tenant])->filter();
    }

    /**
     * 檢查使用者是否可進入特定租戶面板
     */
    public function canAccessTenant(Model $tenant): bool
    {
        // 超級管理員無視限制
        if ($this->hasRole('super_admin', 'web')) {
            return true;
        }

        // 店長或員工必須租戶 ID 匹配
        return (int) $this->tenant_id === (int) $tenant->id;
    }

    /**
     * 檢查使用者是否可進入 Filament 面板
     * 優化：只要有分配任何後台角色，就允許進入
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // 如果你的角色名稱都有固定前綴或在 Shield 範圍內，可以用 count 判斷
        // 這樣未來增加「教練」或「新角色」就不需要再回來改這行代碼
        return $this->roles()->count() > 0;
    }

    /**
     * 頭像與名稱優化
     */
    public function getFilamentName(): string
    {
        return "{$this->name}";
    }

    /**
     * 實戰技巧：判斷是否為超級管理員
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * 實戰技巧：判斷是否為當前租戶的管理員
     */
    public function isTenantAdmin(): bool
    {
        return $this->hasRole('tenant_admin');
    }
}