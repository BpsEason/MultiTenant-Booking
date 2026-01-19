<?php

namespace App\Filament\Widgets;

use App\Models\Appointment;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use App\Filament\Resources\AppointmentResource;

class AppointmentCalendarWidget extends FullCalendarWidget
{
    // 讓日曆根據當前登入店長所在的租戶自動過濾資料
    public function fetchEvents(array $fetchInfo): array
    {
        return Appointment::query()
            ->where('tenant_id', auth()->user()->tenant_id) // 關鍵：租戶隔離
            ->where('start_time', '>=', $fetchInfo['start'])
            ->where('end_time', '<=', $fetchInfo['end'])
            ->get()
            ->map(fn(Appointment $appointment) => [
                'id' => $appointment->id,
                'title' => $appointment->service->name . ' - ' . $appointment->customer_name,
                'start' => $appointment->start_time,
                'end' => $appointment->end_time,
                'url' => AppointmentResource::getUrl('edit', ['record' => $appointment]),
            ])
            ->toArray();
    }
}