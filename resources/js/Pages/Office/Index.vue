<script setup>
import { ref } from 'vue'
import { Link } from '@inertiajs/vue3'
import { Icon } from '@iconify/vue'
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
    layout: Layout,
})

const props = defineProps({
    offices: Object,
})

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
                    Offices
                </h1>
                <p class="text-muted-foreground">
                    Manage all offices and their assigned users
                </p>
            </div>
            <Link :href="route('offices.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    Add Office
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
                    <div class="text-2xl font-bold">{{ offices.total }}</div>
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
                        {{ offices.data.reduce((sum, office) => sum + office.users_count, 0) }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Assigned to offices
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
                        {{ offices.total > 0 ? Math.round(offices.data.reduce((sum, office) => sum + office.users_count, 0) / offices.total) : 0 }}
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
                <CardTitle>All Offices</CardTitle>
                <CardDescription>
                    A list of all offices in the system
                </CardDescription>
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
                                v-for="office in offices.data" 
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
                                        <Link :href="route('offices.show', office.id)">
                                            <Button variant="ghost" size="sm">
                                                <Icon icon="lucide:eye" class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Link :href="route('offices.edit', office.id)">
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
                            <tr v-if="offices.data.length === 0">
                                <td colspan="4" class="p-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Icon icon="lucide:inbox" class="h-12 w-12 text-muted-foreground/50" />
                                        <p class="text-muted-foreground">No offices found</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="offices.last_page > 1" class="mt-4 flex items-center justify-between border-t pt-4">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ offices.from }} to {{ offices.to }} of {{ offices.total }} offices
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in offices.links" :key="index">
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
            :delete-url="officeToDelete ? route('offices.destroy', officeToDelete.id) : ''"
        />
    </div>
</template>
