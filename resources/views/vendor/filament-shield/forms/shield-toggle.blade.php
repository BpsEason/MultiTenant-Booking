@php
    $offColor = $getOffColor() ?? 'gray';
    $onColor = $getOnColor() ?? 'primary';
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field" :inline-label-vertical-alignment="\Filament\Support\Enums\VerticalAlignment::Center">
    @capture($content)
        <div class="flex items-center gap-3" x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            checkboxes: [],
            checkboxLists: [],
        
            toggleAll() {
                this.state = !this.state;
                this.checkboxLists.forEach(list => {
                    const data = Alpine.$data(list.parentNode);
                    if (data) {
                        data.areAllCheckboxesChecked = false;
                        data.checkIfAllCheckboxesAreChecked();
                        data.updateVisibleCheckboxListOptions();
                        data.toggleAllCheckboxes();
                    }
                });
        
                this.checkboxes.forEach(checkbox => checkbox.checked = this.state);
                this.updateStateBasedOnCheckboxes();
            },
        
            updateStateBasedOnCheckboxes() {
                if (this.checkboxes.length === 0) return;
                this.state = this.checkboxes.every(checkbox => checkbox.checked);
            },
        
            init() {
                this.checkboxLists = Array.from(document.querySelectorAll('.fi-fo-checkbox-list'));
                this.checkboxes = Array.from(document.querySelectorAll('.fi-fo-checkbox-list-option-label input[type=\'checkbox\']'));
        
                this.checkboxes.forEach((checkbox) => {
                    checkbox.addEventListener('change', () => this.updateStateBasedOnCheckboxes());
                });
        
                $nextTick(() => this.updateStateBasedOnCheckboxes());
                $watch('state', (val, old) => { if (val === old) this.toggleAll(); });
            }
        }">
            {{-- Toggle Button 本体 --}}
            <button type="button" role="switch" wire:loading.attr="disabled" x-on:click="toggleAll()"
                x-bind:aria-checked="state.toString()"
                x-bind:class="state
                    ?
                    '{{ match ($onColor) {'gray' => 'bg-gray-600',default => 'bg-custom-600'} }} ring-2 ring-custom-600/20' :
                    'bg-gray-200 dark:bg-gray-700 ring-2 ring-transparent'"
                x-bind:style="state ? '{{ \Filament\Support\get_color_css_variables($onColor, shades: [600]) }}' : ''"
                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-all duration-300 ease-in-out focus:outline-none disabled:opacity-50">
                <span
                    class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-300 ease-in-out"
                    x-bind:class="state ? 'translate-x-5 rtl:-translate-x-5' : 'translate-x-0'">
                    {{-- On Icon --}}
                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        x-bind:class="state ? 'opacity-100 ease-in duration-200' : 'opacity-0 ease-out duration-100'">
                        @if ($hasOnIcon())
                            <x-filament::icon :icon="$getOnIcon()" class="h-3 w-3 text-custom-600" />
                        @else
                            <svg class="h-3 w-3 text-custom-600" fill="currentColor" viewBox="0 0 12 12">
                                <path
                                    d="M3.707 5.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4a1 1 0 00-1.414-1.414L5 7.586 3.707 5.293z" />
                            </svg>
                        @endif
                    </span>
                    {{-- Off Icon --}}
                    <span class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                        x-bind:class="state ? 'opacity-0 ease-out duration-100' : 'opacity-100 ease-in duration-200'">
                        @if ($hasOffIcon())
                            <x-filament::icon :icon="$getOffIcon()" class="h-3 w-3 text-gray-400" />
                        @else
                            <svg class="h-3 w-3 text-gray-400" fill="none" viewBox="0 0 12 12">
                                <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        @endif
                    </span>
                </span>
            </button>

            {{-- 額外增加標籤提示 --}}
            <span class="text-sm font-medium text-gray-500 dark:text-gray-400 select-none cursor-pointer"
                x-on:click="toggleAll()">
                <span x-show="!state">全選</span>
                <span x-show="state">取消全選</span>
            </span>
        </div>
    @endcapture

    @if ($isInline())
        <x-slot name="labelPrefix">{{ $content() }}</x-slot>
    @else
        {{ $content() }}
    @endif
</x-dynamic-component>
