<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import DeleteModal from "@/components/DeleteModal.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Offices", href: route("offices.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    office: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('offices.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ office.name }}
                    </h1>
                    <p class="text-muted-foreground">
                        Office details and assigned users
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('offices.edit', office.id)">
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

        <!-- Office Info Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:building-2" class="h-5 w-5" />
                        Office Information
                    </CardTitle>
                    <CardDescription>
                        Basic information about this office
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Office Code
                        </p>
                        <p class="font-medium">{{ office.code }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Office Name
                        </p>
                        <p class="font-medium">{{ office.name }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Created At
                        </p>
                        <p class="font-medium">
                            {{ formatDate(office.created_at) }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Last Updated
                        </p>
                        <p class="font-medium">
                            {{ formatDate(office.updated_at) }}
                        </p>
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
                        Office statistics and metrics
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-4 rounded-lg border p-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                        >
                            <Icon
                                icon="lucide:users"
                                class="h-6 w-6 text-primary"
                            />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">
                                {{ office.users_count }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    office.users_count === 1 ? "User" : "Users"
                                }}
                                assigned
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-lg border p-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-green-500/10"
                        >
                            <Icon
                                icon="lucide:wallet"
                                class="h-6 w-6 text-green-500"
                            />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">
                                {{ office.funds_count }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{
                                    office.funds_count === 1 ? "Fund" : "Funds"
                                }}
                                assigned
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
                    Users that belong to this office
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Name
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Email
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="user in office.users"
                                :key="user.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10"
                                        >
                                            <Icon
                                                icon="lucide:user"
                                                class="h-4 w-4 text-primary"
                                            />
                                        </div>
                                        <span class="font-medium">{{
                                            user.name
                                        }}</span>
                                    </div>
                                </td>
                                <td
                                    class="p-4 align-middle text-muted-foreground"
                                >
                                    {{ user.email }}
                                </td>
                            </tr>
                            <tr v-if="office.users.length === 0">
                                <td colspan="2" class="p-8 text-center">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Icon
                                            icon="lucide:users"
                                            class="h-12 w-12 text-muted-foreground/50"
                                        />
                                        <p class="text-muted-foreground">
                                            No users assigned to this office
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Funds Table -->
        <Card>
            <CardHeader>
                <CardTitle>Assigned Funds</CardTitle>
                <CardDescription>
                    Funds that belong to this office
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Fund Name
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Created
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="fund in office.funds"
                                :key="fund.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500/10"
                                        >
                                            <Icon
                                                icon="lucide:wallet"
                                                class="h-4 w-4 text-green-500"
                                            />
                                        </div>
                                        <span class="font-medium">{{
                                            fund.name
                                        }}</span>
                                    </div>
                                </td>
                                <td
                                    class="p-4 align-middle text-muted-foreground"
                                >
                                    {{ formatDate(fund.created_at) }}
                                </td>
                            </tr>
                            <tr v-if="office.funds.length === 0">
                                <td colspan="2" class="p-8 text-center">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Icon
                                            icon="lucide:wallet"
                                            class="h-12 w-12 text-muted-foreground/50"
                                        />
                                        <p class="text-muted-foreground">
                                            No funds assigned to this office
                                        </p>
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
            title="Delete Office"
            :description="`Are you sure you want to delete '${office.name}'? This action cannot be undone.`"
            :delete-url="route('offices.destroy', office.id)"
        />
    </div>
</template>
