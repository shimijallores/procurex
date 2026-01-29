<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
import { useDebounceFn } from '@vueuse/core'
import Layout from '@/Layout/Layout.vue'
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import DeleteModal from '@/components/DeleteModal.vue'

defineOptions({
    layout: (h, page) => h(Layout, { breadcrumbs: [{ label: 'Funds' }] }, () => page),
})

const props = defineProps({
    funds: Object,
    filters: Object,
})

const search = ref(funds.filters?.search ?? '')

const debouncedSearch = useDebounceFn(() => {
    router.get(route('funds.index'), { search: search.value }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}, 300)

watch(search, () => {
    debouncedSearch()
})

const clearSearch = () => {
    search.value = ''
}

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

const showDeleteModal = ref(false)
const officeToDelete = ref(null)

const openDeleteModal = (office) => {
    officeToDelete.value = office
    showDeleteModal.value = true
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Funds
                </h1>
                <p class="text-muted-foreground">
                    Manage all funds and their assigned users
                </p>
            </div>
            <Link :href="route('funds.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    Add Funds
                </Button>
            </Link>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Offices</CardTitle>
                    <Icon icon="lucide:building-2" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ funds.total }}</div>
                    <p class="text-xs text-muted-foreground">
                        Across all departments
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                    <Icon icon="lucide:users" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ funds.data.reduce((sum, office) => sum + office.users_count, 0) }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Assigned to funds
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">Average Users</CardTitle>
                    <Icon icon="lucide:user-check" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ funds.total > 0 ? Math.round(funds.data.reduce((sum, office) => sum + office.users_count, 0) / funds.total) : 0 }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Per office
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Offices Table -->
        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>All Offices</CardTitle>
                        <CardDescription>
                            A list of all funds in the system
                        </CardDescription>
                    </div>
                    <div class="relative w-64">
                        <Icon icon="lucide:search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search funds..."
                            class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <button
                            v-if="search"
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        >
                            <Icon icon="lucide:x" class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Office Name
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Users
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Created
                                </th>
                                <th class="h-12 px-4 text-right align-middle font-medium text-muted-foreground">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="office in funds.data"
                                :key="office.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle font-medium">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                                            <Icon icon="lucide:building-2" class="h-5 w-5 text-primary" />
                                        </div>
                                        <div>
                                            <div class="font-medium">{{ office.name }}</div>
                                            <div class="text-xs text-muted-foreground">ID: {{ office.id }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-2">
                                        <Icon icon="lucide:users" class="h-4 w-4 text-muted-foreground" />
                                        <span class="font-medium">{{ office.users_count }}</span>
                                        <span class="text-muted-foreground">{{ office.users_count === 1 ? 'user' : 'users' }}</span>
                                    </div>
                                </td>
                                <td class="p-4 align-middle text-muted-foreground">
                                    {{ formatDate(office.created_at) }}
                                </td>
                                <td class="p-4 align-middle text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('funds.show', office.id)">
                                            <Button variant="ghost" size="sm">
                                                <Icon icon="lucide:eye" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('funds.edit', office.id)">
                                            <Button variant="ghost" size="sm">
                                                <Icon icon="lucide:pencil" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" @click="openDeleteModal(office)">
                                            <Icon icon="lucide:trash-2" class="h-4 w-4 text-destructive" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="funds.data.length === 0">
                                <td colspan="4" class="p-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Icon icon="lucide:inbox" class="h-12 w-12 text-muted-foreground/50" />
                                        <p class="text-muted-foreground">No funds found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="funds.last_page > 1" class="mt-4 flex items-center justify-between border-t pt-4">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ funds.from }} to {{ funds.to }} of {{ funds.total }} funds
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in funds.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                    link.label.includes('Previous') || link.label.includes('Next') ? 'h-9 px-3' : 'h-9 w-9',
                                    link.active
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'hover:bg-accent hover:text-accent-foreground'
                                ]"
                                preserve-scroll
                                v-html="link.label"
                            />
                            <span
                                v-else
                                :class="[
                                    'inline-flex items-center justify-center rounded-md text-sm font-medium',
                                    link.label.includes('Previous') || link.label.includes('Next') ? 'h-9 px-3' : 'h-9 w-9',
                                    'pointer-events-none opacity-50'
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Office"
            :description="`Are you sure you want to delete '${officeToDelete?.name}'? This action cannot be undone.`"
            :delete-url="officeToDelete ? route('funds.destroy', officeToDelete.id) : ''"
        />
    </div>
</template>
