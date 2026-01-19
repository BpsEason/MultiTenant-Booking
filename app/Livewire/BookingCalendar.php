<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Service;
use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BookingCalendar extends Component
{
    // 狀態控制
    public $step = 1;

    // 資料欄位
    public $serviceId;
    public $tenantId;
    public $selectedDate;
    public $selectedTime;
    public $availableSlots = [];

    // 表單欄位
    public $name;
    public $email;
    public $phone;
    public $note;

    /**
     * 修改 mount 接收方式，繞過強型別自動注入的潛在錯誤
     */
    public function mount($service = null)
    {
        if (is_object($service)) {
            $this->serviceId = $service->id;
            $this->tenantId = $service->tenant_id;
        } elseif (is_numeric($service)) {
            $this->serviceId = $service;
        }

        // 預設日期
        $this->selectedDate = now()->format('Y-m-d');

        if ($this->serviceId) {
            $this->loadSlots();
        }
    }

    public function updatedSelectedDate()
    {
        $this->loadSlots();
    }

    public function loadSlots()
    {
        if (!$this->serviceId) return;

        $allSlots = ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00', '17:00'];

        $bookedSlots = Appointment::where('tenant_id', $this->tenantId)
            ->whereDate('start_time', $this->selectedDate)
            ->whereIn('status', ['pending', 'confirmed'])
            ->pluck('start_time')
            ->map(fn($t) => $t->format('H:i'))
            ->toArray();

        $this->availableSlots = array_diff($allSlots, $bookedSlots);
    }

    public function selectTime($time)
    {
        $this->selectedTime = $time;
    }

    public function goToStepTwo()
    {
        $this->validate(['selectedTime' => 'required']);
        $this->step = 2;
    }

    public function submitBooking()
    {
        $this->validate([
            'name' => 'required|min:2',
            'email' => 'required|email',
            'phone' => 'required',
        ]);

        $user = User::firstOrCreate(
            ['email' => $this->email, 'tenant_id' => $this->tenantId],
            [
                'name' => $this->name,
                'password' => Hash::make(Str::random(10)),
            ]
        );

        $startTime = Carbon::parse($this->selectedDate . ' ' . $this->selectedTime);
        $service = Service::withoutGlobalScopes()->find($this->serviceId);

        Appointment::create([
            'tenant_id' => $this->tenantId,
            'user_id' => $user->id,
            'service_id' => $this->serviceId,
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addMinutes($service->duration_minutes ?? 60),
            'status' => 'pending',
            'notes' => $this->note,
        ]);

        return redirect()->to(route('tenant.booking.success', ['slug' => $service->tenant->slug]));
    }

    public function render()
    {
        $currentService = Service::withoutGlobalScopes()->find($this->serviceId);

        return view('livewire.booking-calendar', [
            'service' => $currentService
        ])->layout('layouts.guest'); // <--- 加上這一行
    }
}