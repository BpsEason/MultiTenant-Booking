<div class="min-h-screen pb-20 bg-gray-50 dark:bg-gray-900">
    <style>
        :root {
            --primary-color: {{ $tenant->settings['primary_color'] ?? '#4F46E5' }};
            --primary-dark: color-mix(in srgb, var(--primary-color) 80%, black);
            --primary-glow: color-mix(in srgb, var(--primary-color) 25%, transparent);
        }

        .text-tenant {
            color: var(--primary-color);
        }

        .bg-tenant {
            background-color: var(--primary-color);
        }
    </style>

    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-md border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
        <div class="max-w-4xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="{{ route('tenant.frontend', $tenant->slug) }}"
                class="text-gray-500 hover:text-tenant flex items-center gap-2 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                返回
            </a>
            <span class="font-bold text-lg text-gray-900 dark:text-white tracking-wide">預約服務</span>
            <div class="w-10"></div>
        </div>
    </header>

    <!-- Main -->
    <main class="max-w-4xl mx-auto px-6 mt-10 space-y-10">

        <!-- Service Card -->
        <div
            class="bg-white dark:bg-gray-800 rounded-3xl p-6 shadow-lg border border-gray-100 dark:border-gray-700 flex items-center gap-6 hover:shadow-xl transition">
            <div class="w-20 h-20 rounded-2xl bg-tenant/10 flex items-center justify-center text-tenant shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $service->name }}</h2>
                <p class="text-gray-500 dark:text-gray-400 mt-1">
                    ${{ number_format($service->price) }} • 服務約 {{ $service->duration_minutes }} 分鐘
                </p>
            </div>
        </div>

        <!-- Calendar Section -->
        <div
            class="bg-gradient-to-br from-white to-gray-50 dark:from-gray-800 dark:to-gray-900
                    rounded-3xl shadow-2xl border border-gray-200 dark:border-gray-700
                    overflow-hidden p-6 transition duration-300 hover:shadow-[0_10px_25px_rgba(0,0,0,0.15)]">

            <!-- Title -->
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-700 dark:text-gray-200 flex items-center gap-2">
                    <svg class="w-6 h-6 text-tenant" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    預約日曆
                </h2>
                <span class="px-3 py-1 text-sm rounded-full bg-tenant/10 text-tenant font-medium">
                    {{ $service->name }}
                </span>
            </div>

            <!-- Livewire Calendar -->
            <div class="rounded-2xl bg-white dark:bg-gray-800 p-4 shadow-inner">
                @livewire('booking-calendar', ['service' => $service])
            </div>
        </div>

    </main>
</div>
