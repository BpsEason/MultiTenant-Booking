<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * 超級管理員自動通行證
     * 這會攔截所有檢查，如果是 super_admin 直接回傳 true
     */
    public function before(User $user, string $ability): ?bool
    {
        // 增加針對 super_admin 的全局通行判定
        if ($user->hasRole('super_admin')) {
            return true;
        }

        return null; // 繼續執行下方的細節判斷
    }

    /**
     * 是否可以看到員工選單 (List)
     */
    public function viewAny(User $user): bool
    {
        // 關鍵修正：加入資料庫實際存在的 panel_user 角色
        return $user->hasAnyRole(['super_admin', 'tenant_admin', 'panel_user']);
    }

    /**
     * 是否可以查看特定員工詳情
     */
    public function view(User $user, User $model): bool
    {
        // 店長或面板使用者只能看自己租戶下的員工
        return $user->hasAnyRole(['tenant_admin', 'panel_user']) && (int)$user->tenant_id === (int)$model->tenant_id;
    }

    /**
     * 是否可以新增員工
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'tenant_admin', 'panel_user']);
    }

    /**
     * 是否可以編輯員工
     */
    public function update(User $user, User $model): bool
    {
        // 店長/面板使用者只能編輯自己店的人
        return $user->hasAnyRole(['tenant_admin', 'panel_user']) && (int)$user->tenant_id === (int)$model->tenant_id;
    }

    /**
     * 是否可以刪除員工
     */
    public function delete(User $user, User $model): bool
    {
        // 不能刪除自己，且必須是同租戶
        return $user->hasAnyRole(['tenant_admin', 'panel_user'])
            && (int)$user->tenant_id === (int)$model->tenant_id
            && $user->id !== $model->id;
    }
}