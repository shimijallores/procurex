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
    layout: (h, page) => h(Layout, { breadcrumbs: [
        { label: 'Roles', href: route('roles.index') },
        { label: 'Details' }
    ] }, () => page),
})

const props = defineProps({
    role: Object,
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const showDeleteModal = ref(false)
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('roles.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ role.name }}
                    </h1>
                    <p class="text-muted-foreground">
                        Role details and assigned users
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('roles.edit', role.id)">
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Button variant="destructive" @click="showDeleteModal = true">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Role Info Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:shield" class="h-5 w-5" />
                        Role Information
                    </CardTitle>
                    <CardDescription>
                        Basic information about this role
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">Role ID</p>
                        <p class="font-medium">{{ role.id }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">Role Name</p>
                        <p class="font-medium">{{ role.name }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">Created At</p>
                        <p class="font-medium">{{ formatDate(role.created_at) }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">Last Updated</p>
                        <p class="font-medium">{{ formatDate(role.updated_at) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Stats Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:bar-chart-3" class="h-5 w-5" />
                        Statistics
                    </CardTitle>
                    <CardDescription>
                        Role statistics and metrics
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4 rounded-lg border p-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                            <Icon icon="lucide:users" class="h-6 w-6 text-primary" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ role.users_count }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ role.users_count === 1 ? 'User' : 'Users' }} assigned
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Users Table -->
        <Card>
            <CardHeader>
                <CardTitle>Assigned Users</CardTitle>
                <CardDescription>
                    Users that have this role
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Name
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Email
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr 
                                v-for="user in role.users" 
                                :key="user.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10">
                                            <Icon icon="lucide:user" class="h-4 w-4 text-primary" />
                                        </div>
                                        <span class="font-medium">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="p-4 align-middle text-muted-foreground">
                                    {{ user.email }}
                                </td>
                            </tr>
                            <tr v-if="role.users.length === 0">
                                <td colspan="2" class="p-8 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <Icon icon="lucide:users" class="h-12 w-12 text-muted-foreground/50" />
                                        <p class="text-muted-foreground">No users assigned to this role</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Role"
            :description="`Are you sure you want to delete '${role.name}'? This action cannot be undone.`"
            :delete-url="route('roles.destroy', role.id)"
        />
    </div>
</template>
