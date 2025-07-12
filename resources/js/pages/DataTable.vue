<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '../components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Data Table',
        href: '/dataTable',
    },
];

import type {
    ColumnDef,
    ColumnFiltersState,
    ExpandedState,
    SortingState,
    VisibilityState,
} from '@tanstack/vue-table'
import {
    FlexRender,
    getCoreRowModel,
    getExpandedRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import { ArrowUpDown, ChevronDown } from 'lucide-vue-next'
import type { DateRange } from 'reka-ui'
import {
    CalendarDate,
    DateFormatter,
    getLocalTimeZone,
} from '@internationalized/date'

import { CalendarIcon } from 'lucide-vue-next'

import { h, ref, shallowRef, computed, type Ref, onMounted, watch } from 'vue'
import { valueUpdater, cn } from '@/lib/utils'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { RangeCalendar } from '@/components/ui/range-calendar'
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu'
import { Input } from '@/components/ui/input'
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter
} from '@/components/ui/dialog'
import { getGlobalFilteredRowModel } from '@tanstack/vue-table'
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'

// Search After Reload //
import axios from 'axios';
import { debounce } from 'lodash'
import { usePage, router } from '@inertiajs/vue3';

const props = defineProps({
    search: {
        type: String,
        default: '',
    },
    page: {
        type: Number,
        default: 1,
    }
});

const data = shallowRef<User[]>([]);
const currentPage = ref(1);
const totalPages = ref(1);
// Filters state
const globalFilter = ref('')
const selectedStatuses = ref<string[]>([])
const value = ref<DateRange | null>(null) // date range picker

// Access current URL query params
const page = usePage()

function parseDateRangeFromQuery(query) {
    if (!query.start_date || !query.end_date) return null
    // Convert YYYY-MM-DD strings to CalendarDate objects (adjust as per your date lib)
    return {
        start: new CalendarDate(...query.start_date.split('-').map(Number)),
        end: new CalendarDate(...query.end_date.split('-').map(Number)),
    }
}

// Initialize filter states from URL query params on mount
onMounted(() => {
    currentPage.value = props.page || 1;
    globalFilter.value = props.search || '';

    const query = new URLSearchParams(window.location.search);

    selectedStatuses.value = query.get('status') ? query.get('status').split(',') : [];

    const startDate = query.get('start_date');
    const endDate = query.get('end_date');
    if (startDate && endDate) {
        value.value = {
            start: new CalendarDate(...startDate.split('-').map(Number)),
            end: new CalendarDate(...endDate.split('-').map(Number)),
        }
    } else {
        value.value = null;
    }

    loadUserData(currentPage.value);
});

// Navigate and update URL query with filters
function navigate(page = 1) {
    const params: Record<string, any> = {
        page,
        search: globalFilter.value || undefined,
    }
    if (selectedStatuses.value.length) {
        params.status = selectedStatuses.value.join(',')
    }
    if (value.value) {
        params.start_date = formatDate(value.value.start)
        params.end_date = formatDate(value.value.end)
    }

    router.get(route('dataTable'), params, {
        preserveState: true,
        replace: true,
    })
}

// Watch filters and navigate with debounce
watch([globalFilter, selectedStatuses, value], () => {
    // reset to page 1 on filter change
    navigate(1)
}, { deep: true })

// Pagination watch
watch(currentPage, (newPage) => {
    navigate(newPage)
})

onMounted(() => {
    const query = new URLSearchParams(window.location.search)
    const page = parseInt(query.get('page') || '1')
    currentPage.value = page
    loadUserData(page)
})

const df = new DateFormatter('en-US', {
    dateStyle: 'medium',
})

const initialDateRange = {
    start: new CalendarDate(2025, 1, 1),
    end: new CalendarDate(2025, 12, 31),
}

function isDefaultDateRange(): boolean {
    return value.value.start.toString() === initialDateRange.start.toString() &&
        value.value.end.toString() === initialDateRange.end.toString()
}

export interface User {
    id: number | string
    name: string
    email: string
    country: string
    registration_date: string
    status: string
}

const debounceNavigate = debounce(() => {
    // Reset to page 1 when search changes
    navigate(1, globalFilter.value);
}, 400);

watch(globalFilter, () => {
    debouncedSearch()
})


watch(value, () => {
    if (value.value === null) {
        loadUserData(1) // or set data to [] if needed
        return
    }

    if (!isDefaultDateRange()) {
        loadUserData(1)
    } else {
        data.value = []
    }
}, { deep: true })


async function loadUserData(page = 1) {
    try {
        currentPage.value = page
        localStorage.setItem('userCurrentPage', String(page))

        const response = await axios.get(`http://127.0.0.1:8000/user-details`, {
            params: {
                page: page,
                search: globalFilter.value,
                status: selectedStatuses.value.join(','),
                ...(value.value && {
                    start_date: formatDate(value.value.start),
                    end_date: formatDate(value.value.end),
                })
            },
        });

        data.value = response.data.data;
        currentPage.value = response.data.current_page;
        totalPages.value = response.data.last_page;

        // Auto-select previously selected user if still visible
        const selectedId = localStorage.getItem('selectedUserId')
        if (selectedId) {
            const row = table.getRowModel().rows.find(row => String(row.original.id) === selectedId)
            if (row) row.toggleSelected(true)
        }
    } catch (error) {
        console.error('Failed to load user data:', error);
    }
}

function formatDate(calendarDate) {
    return calendarDate.toDate(getLocalTimeZone()).toISOString().split('T')[0];
}

const columns: ColumnDef<User>[] = [
    {
        id: 'select',
        header: ({ table }) => h(Checkbox, {
            'modelValue': table.getIsAllPageRowsSelected() || (table.getIsSomePageRowsSelected() && 'indeterminate'),
            'onUpdate:modelValue': value => table.toggleAllPageRowsSelected(!!value),
            'ariaLabel': 'Select all',
        }),
        cell: ({ row }) => h(Checkbox, {
            'modelValue': row.getIsSelected(),
            'onUpdate:modelValue': value => row.toggleSelected(!!value),
            'ariaLabel': 'Select row',
        }),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'name',
        header: () => 'Name',
        cell: ({ row }) => h('div', {}, row.getValue('name')),
    },
    {
        accessorKey: 'email',
        header: () => 'Email',
        cell: ({ row }) => h('div', {}, row.getValue('email')),
    },
    {
        accessorKey: 'country',
        header: () => 'Country',
        cell: ({ row }) => h('div', {}, row.getValue('country')),
    },
    {
        accessorKey: 'registration_date',
        header: () => 'Registration Date',
        cell: ({ row }) => h('div', {}, row.getValue('registration_date')),
    },
    {
        accessorKey: 'status',
        header: () => 'Status',
        cell: ({ row }) => h('div', {}, row.getValue('status')),
    },
    {
        id: 'actions',
        header: () => h('div', { class: 'text-center' }, 'Actions'),
        enableHiding: false,
        cell: ({ row }) => {
            const user = row.original
            return h('div', { class: 'flex gap-2 justify-center' }, [
                h(Button, {
                    variant: 'outline',
                    size: 'sm',
                    title: 'Edit',
                    onClick: () => editRow(user),
                }, () => h('i', { class: 'fa-regular fa-pen-to-square' })),
                h(Button, {
                    variant: 'destructive',
                    size: 'sm',
                    title: 'Delete',
                    onClick: () => deleteRow(user.id),
                }, () => h('i', { class: 'fa-regular fa-trash-can' })),
            ])
        },
    },
];

// Update & Edit Function //
import { useToast } from 'primevue/usetoast';
const toast = useToast();

const editingUser = ref<User | null>(null);
const isEditModalOpen = ref(false)
const editForm = ref<User>({
    id: '',
    name: '',
    email: '',
    country: '',
    registration_date: '',
    status: '',
})

function editRow(user: User) {
    editingUser.value = { ...user };
    editForm.value = {
        name: user.name,
        email: user.email,
        country: user.country,
        status: user.status
    };
    isEditModalOpen.value = true;
}

async function updateUser() {
    if (!editingUser.value) return;

    try {
        const response = await axios.post(`http://127.0.0.1:8000/user-details/${editingUser.value.id}`, editForm.value);

        const index = data.value.findIndex(u => u.id === editingUser.value?.id);
        if (index !== -1) {
            data.value[index] = response.data;
        }

        await loadUserData();
        isEditModalOpen.value = false;
        editingUser.value = null;

        toast.add({
            severity: 'contrast',
            summary: 'Success',
            detail: 'User updated successfully.',
            life: 3000
        });

    } catch (error) {
        console.error('Update failed:', error);

        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to update user.',
            life: 3000
        });
    }
}
// Update & Edit Function //

async function deleteRow(id: number | string) {
    try {
        await axios.delete(`http://127.0.0.1:8000/user-details/${id}`);
        data.value = data.value.filter(user => user.id !== id);
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'User deleted successfully.',
            life: 3000
        });
    } catch (error) {
        console.error('Delete failed:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete user.',
            life: 3000
        });
    }

}

const sorting = ref<SortingState>([])
const columnFilters = ref<ColumnFiltersState>([])
const columnVisibility = ref<VisibilityState>({})
const rowSelection = ref({})
const expanded = ref<ExpandedState>({})


const table = useVueTable({
    data: data, // ✅ use the computed filtered data
    columns,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    onSortingChange: updaterOrValue => valueUpdater(updaterOrValue, sorting),
    onColumnFiltersChange: updaterOrValue => valueUpdater(updaterOrValue, columnFilters),
    onColumnVisibilityChange: updaterOrValue => valueUpdater(updaterOrValue, columnVisibility),
    onRowSelectionChange: updaterOrValue => valueUpdater(updaterOrValue, rowSelection),
    onExpandedChange: updaterOrValue => valueUpdater(updaterOrValue, expanded),
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get columnVisibility() { return columnVisibility.value },
        get rowSelection() { return rowSelection.value },
        get expanded() { return expanded.value },
    },
})

watch(selectedStatuses, () => {
    loadUserData(1)
})

const statuses: User['status'][] = [
    'verified',
    'unverified',
]

const isFiltering = computed(() => {
    return (
        globalFilter.value !== '' ||
        columnFilters.value.length > 0 ||
        sorting.value.length > 0 ||
        selectedStatuses.value.length > 0 ||
        value.value !== null // works as expected now
    )
})

function resetFilters() {
    globalFilter.value = ''
    columnFilters.value = []
    sorting.value = []
    rowSelection.value = {}
    expanded.value = {}
    selectedStatuses.value = []
    value.value = null // ❗ Important: use null instead of default range
    loadUserData(1)
}

watch(rowSelection, () => {
    const selectedRowIds = Object.keys(rowSelection.value)
    if (selectedRowIds.length) {
        localStorage.setItem('selectedUserId', selectedRowIds[0])
    } else {
        localStorage.removeItem('selectedUserId')
    }
})

const paginationRange = computed(() => {
    const range = []
    const total = totalPages.value
    const current = currentPage.value

    let start = current - 1
    let end = current + 1

    if (start < 1) {
        start = 1
        end = Math.min(3, total)
    }

    if (end > total) {
        end = total
        start = Math.max(1, total - 2)
    }

    for (let i = start; i <= end; i++) {
        range.push(i)
    }

    return range
})

</script>

<template>

    <Head title="Data Table" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-5 overflow-auto w-full">

            <div class="w-full">
                <div class="flex gap-2 items-center py-4">
                    <Input class="max-w-52" v-model="globalFilter" placeholder="Search across all columns..." />

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline">
                                <i class="fa-solid fa-circle-plus mr-1"></i>
                                Status
                            </Button>
                        </PopoverTrigger>

                        <PopoverContent class="w-48 p-2">
                            <div class="flex flex-col gap-1">
                                <label v-for="status in statuses" :key="status" class="flex items-center gap-2">
                                    <input type="checkbox" :value="status" v-model="selectedStatuses"
                                        class="w-4 h-4 border-gray-300 rounded-2xl text-transparent bg-transparent" />
                                    <span class="capitalize">{{ status }}</span>
                                </label>
                            </div>
                        </PopoverContent>
                    </Popover>

                    <Popover>
                        <PopoverTrigger as-child>
                            <Button variant="outline" :class="cn(
                                'w-[240px] justify-start text-left font-normal',
                                !value && 'text-muted-foreground',
                            )">
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                <template v-if="value">
                                    <template v-if="value.start && value.end">
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }} - {{
                                            df.format(value.end.toDate(getLocalTimeZone())) }}
                                    </template>

                                    <template v-else>
                                        {{ df.format(value.start.toDate(getLocalTimeZone())) }}
                                    </template>
                                </template>
                                <template v-else>
                                    Pick a date
                                </template>
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0">
                            <RangeCalendar v-model="value" initial-focus :number-of-months="2"
                                :min-value="new CalendarDate(2025, 1, 1)" :max-value="new CalendarDate(2025, 12, 31)"
                                @update:start-value="(startDate) => value.start = startDate" />
                        </PopoverContent>
                    </Popover>

                    <Button v-if="isFiltering" @click="resetFilters">Reset</Button>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline" class="ml-auto">
                                <i class="fa-solid fa-sliders"></i>
                                View
                                <ChevronDown class="ml-2 h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuCheckboxItem
                                v-for="column in table.getAllColumns().filter((column) => column.getCanHide())"
                                :key="column.id" class="capitalize" :model-value="column.getIsVisible()"
                                @update:model-value="(value) => {
                                    column.toggleVisibility(!!value)
                                }">
                                {{ column.id }}
                            </DropdownMenuCheckboxItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>

                <div class="rounded-md border overflow-x-auto">
                    <div class="min-w-[900px]">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                                    <TableHead v-for="header in headerGroup.headers" :key="header.id">
                                        <FlexRender v-if="!header.isPlaceholder"
                                            :render="header.column.columnDef.header" :props="header.getContext()" />
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <template v-if="table.getRowModel().rows?.length">
                                    <template v-for="row in table.getRowModel().rows" :key="row.id">
                                        <TableRow :data-state="row.getIsSelected() && 'selected'">
                                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                                <FlexRender :render="cell.column.columnDef.cell"
                                                    :props="cell.getContext()" />
                                            </TableCell>
                                        </TableRow>
                                        <TableRow v-if="row.getIsExpanded()">
                                            <TableCell :colspan="row.getAllCells().length">
                                                {{ JSON.stringify(row.original) }}
                                            </TableCell>
                                        </TableRow>
                                    </template>
                                </template>

                                <TableRow v-else>
                                    <TableCell :colspan="columns.length" class="h-24 text-center">
                                        No results.
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>

                <div class="flex items-center justify-end space-x-2 py-4">
                    <div class="flex justify-center mt-4 space-x-2">
                        <Button variant="outline" size="sm" :disabled="currentPage === 1"
                            @click="loadUserData(currentPage - 1)">
                            Previous
                        </Button>

                        <Button v-for="item in paginationRange" :key="item" variant="outline" size="sm"
                            :class="{ 'bg-blue-500 text-white': currentPage === item }" @click="loadUserData(item)">
                            {{ item }}
                        </Button>

                        <Button variant="outline" size="sm" :disabled="currentPage === totalPages"
                            @click="loadUserData(currentPage + 1)">
                            Next
                        </Button>
                    </div>
                </div>
            </div>

            <Dialog v-model:open="isEditModalOpen">
                <DialogContent class="sm:max-w-[500px]">
                    <DialogHeader>
                        <DialogTitle>Edit User</DialogTitle>
                    </DialogHeader>

                    <div class="grid gap-4 py-4">
                        <div class="grid gap-1">
                            <label class="text-sm font-medium">Name</label>
                            <Input v-model="editForm.name" placeholder="Enter name" />
                        </div>
                        <div class="grid gap-1">
                            <label class="text-sm font-medium">Email</label>
                            <Input v-model="editForm.email" placeholder="Enter email" />
                        </div>
                        <div class="grid gap-1">
                            <label class="text-sm font-medium">Country</label>
                            <Input v-model="editForm.country" placeholder="Enter country" />
                        </div>
                        <div class="grid gap-1">
                            <label class="text-sm font-medium">Status</label>
                            <select v-model="editForm.status" class="w-full border px-3 py-2 rounded-md text-sm">
                                <option value="verified">Verified</option>
                                <option value="unverified">Unverified</option>
                            </select>
                        </div>
                    </div>

                    <DialogFooter class="flex justify-end gap-2">
                        <Button variant="outline" @click="isEditModalOpen = false">Cancel</Button>
                        <Button @click="updateUser">Update</Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

        </div>
    </AppLayout>
</template>
