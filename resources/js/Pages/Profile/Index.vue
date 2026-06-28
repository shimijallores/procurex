<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
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

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Dashboard", href: route("dashboard.index") },
                    { label: "Profile" },
                ],
            },
            () => page,
        ),
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('dashboard.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    My Profile
                </h1>
                <p class="text-muted-foreground">
                    Your account details and information
                </p>
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:user" class="h-5 w-5" />
                        Account Information
                    </CardTitle>
                    <CardDescription>
                        Your basic account details
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
                            Email Verified
                        </p>
                        <p class="font-medium">
                            {{ user.email_verified_at ? formatDate(user.email_verified_at) : "Not verified" }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Member Since
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
                        <Icon icon="lucide:shield" class="h-5 w-5" />
                        Assignments
                    </CardTitle>
                    <CardDescription>
                        Your roles and office assignment
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
                            <p class="text-sm font-medium text-muted-foreground">
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
                            <p class="text-sm font-medium text-muted-foreground">
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
    </div>
</template>
