<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    /**
     * 批量賦值屬性
     * 嚴格對應 Migration 中的欄位設計
     */
    protected $fillable = [
        'tenant_id',
        'customer_id',
        'staff_id',
        'service_id',
        'start_time',
        'end_time',
        'status',
    ];

    /**
     * 資料格式轉型
     */
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * 關聯：所屬租戶
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * 關聯：預約的客戶 (關聯至 Users 表)
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * 關聯：提供服務的員工/醫師/教練 (關聯至 Users 表)
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    /**
     * 關聯：預約的服務項目
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * 業務邏輯：檢查該預約是否已過期
     */
    public function isPast(): bool
    {
        return $this->start_time->isPast();
    }

    /**
     * 業務邏輯：格式化顯示時間區間 (用於日曆或列表)
     */
    public function getTimeRangeAttribute(): string
    {
        return "{$this->start_time->format('H:i')} - {$this->end_time->format('H:i')}";
    }
}