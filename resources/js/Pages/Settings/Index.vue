<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useColorMode } from "@vueuse/core";
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
                    { label: "Settings" },
                ],
            },
            () => page,
        ),
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const mode = useColorMode();

const themeOptions = [
    { value: "light", label: "Light", icon: "lucide:sun" },
    { value: "dark", label: "Dark", icon: "lucide:moon" },
    { value: "auto", label: "System", icon: "lucide:monitor" },
];

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
                    Settings
                </h1>
                <p class="text-muted-foreground">
                    Preferences and account information
                </p>
            </div>
        </div>

        <!-- Admin notice -->
        <div class="flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/30">
            <Icon icon="lucide:info" class="mt-0.5 size-5 shrink-0 text-blue-600 dark:text-blue-400" />
            <p class="text-sm text-blue-800 dark:text-blue-300">
                Account details (name, email, password, and role assignments) can only be changed by an administrator. Please contact the system administrator for any account-related changes.
            </p>
        </div>

        <div class="space-y-6">
            <!-- Theme Preferences -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:palette" class="h-5 w-5" />
                        Theme
                    </CardTitle>
                    <CardDescription>
                        Choose your preferred color scheme
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-3">
                        <button
                            v-for="option in themeOptions"
                            :key="option.value"
                            :class="[
                                'flex items-center gap-3 rounded-lg border px-4 py-3 text-sm font-medium transition-colors hover:bg-accent',
                                mode === option.value
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-input',
                            ]"
                            @click="mode = option.value"
                        >
                            <Icon :icon="option.icon" class="size-5" />
                            {{ option.label }}
                        </button>
                    </div>
                </CardContent>
            </Card>

            <!-- Account & Session Info -->
            <div class="grid gap-6 md:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon icon="lucide:user-cog" class="h-5 w-5" />
                            Account Details
                        </CardTitle>
                        <CardDescription>
                            Your login credentials and account metadata
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
                                Password
                            </p>
                            <p class="font-medium text-muted-foreground">
                                ••••••••
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon icon="lucide:clock" class="h-5 w-5" />
                            Session Information
                        </CardTitle>
                        <CardDescription>
                            Account registration and activity timeline
                        </CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
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
                        <div class="grid gap-1">
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
                            <p v-else class="font-medium text-muted-foreground">
                                No roles
                            </p>
                        </div>
                        <div class="grid gap-1">
                            <p class="text-sm font-medium text-muted-foreground">
                                Office
                            </p>
                            <p class="font-medium">
                                {{ user.office?.name || "No office assigned" }}
                            </p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
