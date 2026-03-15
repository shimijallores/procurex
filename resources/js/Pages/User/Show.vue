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
                    { label: "Users", href: route("users.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    user: Object,
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
                <Link :href="route('users.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ user.name }}
                    </h1>
                    <p class="text-muted-foreground">
                        User details and information
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('users.edit', user.id)">
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

        <!-- User Info Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:user" class="h-5 w-5" />
                        User Information
                    </CardTitle>
                    <CardDescription>
                        Basic information about this user
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            User ID
                        </p>
                        <p class="font-medium">{{ user.id }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Name
                        </p>
                        <p class="font-medium">{{ user.name }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Email
                        </p>
                        <p class="font-medium">{{ user.email }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Created At
                        </p>
                        <p class="font-medium">
                            {{ formatDate(user.created_at) }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Last Updated
                        </p>
                        <p class="font-medium">
                            {{ formatDate(user.updated_at) }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <!-- Assignments Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:settings" class="h-5 w-5" />
                        Assignments
                    </CardTitle>
                    <CardDescription>
                        Role and office assignments
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="flex items-center gap-4 rounded-lg border p-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                        >
                            <Icon
                                icon="lucide:shield"
                                class="h-6 w-6 text-primary"
                            />
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Roles
                            </p>
                            <div
                                v-if="(user.roles?.length ?? 0) > 0"
                                class="mt-1 flex flex-wrap items-center gap-2"
                            >
                                <span
                                    v-for="role in user.roles"
                                    :key="role.id"
                                    class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-medium"
                                >
                                    {{ role.name }}
                                </span>
                            </div>
                            <p v-else class="text-lg font-bold">
                                No roles assigned
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 rounded-lg border p-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10"
                        >
                            <Icon
                                icon="lucide:building-2"
                                class="h-6 w-6 text-primary"
                            />
                        </div>
                        <div>
                            <p
                                class="text-sm font-medium text-muted-foreground"
                            >
                                Office
                            </p>
                            <p class="text-lg font-bold">
                                {{ user.office?.name || "No office assigned" }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete User"
            :description="`Are you sure you want to delete '${user.name}'? This action cannot be undone.`"
            :delete-url="route('users.destroy', user.id)"
        />
    </div>
</template>
