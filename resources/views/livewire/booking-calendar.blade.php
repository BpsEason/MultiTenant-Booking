<div class="relative">
    @if ($step === 1)
        <div class="p-8">
            <div class="mb-8">
                <label class="block text-lg font-bold text-gray-900 dark:text-white mb-4">選擇日期</label>
                <input type="date" wire:model.live="selectedDate" min="{{ date('Y-m-d') }}"
                    class="w-full p-4 rounded-2xl border-gray-100 bg-gray-50 dark:bg-gray-700 dark:border-gray-600 focus:ring-tenant focus:border-tenant"
                    onclick="this.showPicker()">
            </div>

            <div class="grid grid-cols-3 gap-3">
                @foreach ($availableSlots as $slot)
                    <button wire:click="selectTime('{{ $slot }}')"
                        class="py-4 rounded-2xl border-2 transition-all font-bold 
                        {{ $selectedTime === $slot ? 'border-tenant bg-tenant text-white shadow-lg shadow-tenant/30' : 'border-gray-50 bg-gray-50 text-gray-500 hover:border-tenant/50' }}">
                        {{ $slot }}
                    </button>
                @endforeach
            </div>

            @if ($selectedTime)
                <button wire:click="goToStepTwo()"
                    class="w-full mt-8 bg-tenant text-white py-4 rounded-2xl font-bold text-lg shadow-xl hover:opacity-90 transition-all">
                    下一步：填寫聯絡資料
                </button>
            @endif
        </div>
    @else
        <div class="p-8 animate-in fade-in slide-in-from-right-4 duration-300">
            <div class="space-y-6">
                <!-- 姓名 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">姓名</label>
                    <input type="text" wire:model="name"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 
                       bg-gray-50 dark:bg-gray-700 px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-tenant focus:border-tenant
                       shadow-sm transition">
                    @error('name')
                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                    <input type="email" wire:model="email"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 
                       bg-gray-50 dark:bg-gray-700 px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-tenant focus:border-tenant
                       shadow-sm transition">
                </div>

                <!-- 電話 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">電話</label>
                    <input type="text" wire:model="phone"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 
                       bg-gray-50 dark:bg-gray-700 px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-tenant focus:border-tenant
                       shadow-sm transition">
                </div>

                <!-- 備註 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">備註 (選填)</label>
                    <textarea wire:model="note"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 
                       bg-gray-50 dark:bg-gray-700 px-4 py-3
                       focus:outline-none focus:ring-2 focus:ring-tenant focus:border-tenant
                       shadow-sm transition"
                        rows="3"></textarea>
                </div>
            </div>

            <!-- 按鈕 -->
            <div class="flex gap-3 mt-10">
                <button wire:click="$set('step', 1)"
                    class="flex-1 py-4 rounded-2xl font-bold bg-gray-100 text-gray-600 hover:bg-gray-200 transition">
                    返回
                </button>
                <button wire:click="submitBooking"
                    class="flex-[2] py-4 rounded-2xl font-bold bg-tenant text-white shadow-lg hover:opacity-90 transition">
                    確認預約
                </button>
            </div>
        </div>
    @endif
</div>
