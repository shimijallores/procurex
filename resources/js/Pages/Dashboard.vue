<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
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
    layout: Layout,
});

defineProps({
    roleName: String,
    scopeLabel: String,
    metrics: {
        type: Array,
        default: () => [],
    },
    pipeline: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
    quickLinks: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Welcome back, {{ user?.name?.split(" ")?.[0] ?? "User" }}!
            </h1>
            <p class="text-muted-foreground">
                Crucial {{ scopeLabel?.toLowerCase() }} procurement updates for
                {{ roleName || "your role" }}.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="metric in metrics" :key="metric.title">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium">
                        {{ metric.title }}
                    </CardTitle>
                    <Icon
                        :icon="metric.icon"
                        class="size-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metric.value }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ metric.description }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Workflow Pipeline</CardTitle>
                <CardDescription>
                    Current document counts in your accessible workflow stages.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div
                    class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7"
                >
                    <div
                        v-for="stage in pipeline"
                        :key="stage.label"
                        class="rounded-md border bg-muted/30 p-3"
                    >
                        <p
                            class="text-xs uppercase tracking-wide text-muted-foreground"
                        >
                            {{ stage.label }}
                        </p>
                        <p class="mt-1 text-xl font-semibold">
                            {{ stage.value }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-7">
            <Card class="lg:col-span-4">
                <CardHeader>
                    <CardTitle>Recent Records</CardTitle>
                    <CardDescription>
                        Latest documents relevant to your role.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recentActivities.length" class="space-y-3">
                        <Link
                            v-for="(entry, index) in recentActivities"
                            :key="`${entry.title}-${index}`"
                            :href="entry.link"
                            class="flex items-start justify-between gap-3 rounded-md border p-3 transition-colors hover:bg-muted/40"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{ entry.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ entry.subtitle }}
                                </p>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ entry.meta }}
                                </p>
                            </div>
                            <span
                                class="text-xs text-muted-foreground whitespace-nowrap"
                                >{{ entry.date }}</span
                            >
                        </Link>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No recent records found.
                    </div>
                </CardContent>
            </Card>

            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Quick Access</CardTitle>
                    <CardDescription>
                        Shortcut links for your most common tasks.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.label"
                            :href="link.href"
                        >
                            <Button
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Icon :icon="link.icon" class="mr-2 size-4" />
                                {{ link.label }}
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
