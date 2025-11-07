@props([
    'actions' => [],
    'formAction' => '',
])

<div 
    x-data="{
        selectedItems: [],
        selectAll: false,
        actions: @js($actions),
        init() {
            this.$watch('selectedItems', value => {
                const total = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']').length;
                this.selectAll = total > 0 && value.length === total;
            });
        },
        updateSelectedItems() {
            const checkboxes = Array.from(document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']:checked'));
            this.selectedItems = checkboxes.map(cb => cb.value);
        },
        toggleSelectAll() {
            const checkboxes = document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']');
            this.selectAll = !this.selectAll;
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.selectAll;
            });
            this.updateSelectedItems();
        },
        hasSelection() {
            return this.selectedItems.length > 0;
        },
        getSelectedCount() {
            return this.selectedItems.length;
        },
        clearSelection() {
            this.selectedItems = [];
            this.selectAll = false;
            document.querySelectorAll('input[type=\'checkbox\'][name=\'selected[]\']').forEach(cb => cb.checked = false);
        },
        executeAction(actionValue) {
            const action = this.actions.find(a => a.value === actionValue);
            if (!action || !this.hasSelection()) return;
            
            if (action.confirm && !confirm(action.confirm)) return;
            
            const form = document.getElementById('bulk-action-form');
            if (form) {
                form.querySelector('input[name=\'action\']').value = actionValue;
                form.submit();
            }
        }
    }"
    class="mb-4 flex items-center justify-between bg-gray-50 px-4 py-3 rounded-lg border border-gray-200"
    x-show="hasSelection()"
    x-cloak
>
    <div class="flex items-center gap-4">
        <span class="text-sm font-medium text-gray-700">
            <span x-text="getSelectedCount()"></span> item dipilih
        </span>
        
        <div class="relative" x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                <span>Aksi Bulk</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            
            <div
                x-show="open"
                @click.away="open = false"
                x-cloak
                class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg ring-1 ring-black ring-opacity-5 z-10"
            >
                <div class="py-1">
                    @foreach($actions as $action)
                        <button
                            type="button"
                            @click="executeAction('{{ $action['value'] }}'); open = false"
                            class="block w-full text-left px-4 py-2 text-sm {{ ($action['danger'] ?? false) ? 'text-red-600 hover:bg-red-50' : 'text-gray-700 hover:bg-gray-100' }}"
                        >
                            {{ $action['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    
    <button
        type="button"
        @click="clearSelection()"
        class="text-sm text-gray-600 hover:text-gray-900"
    >
        Batal
    </button>
</div>



