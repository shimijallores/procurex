<script setup>
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
                    {
                        label: "Project Codes",
                        href: route("project-codes.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    projectCode: Object,
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
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('project-codes.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ projectCode.code }}
                    </h1>
                    <p class="text-muted-foreground">Project code details</p>
                </div>
            </div>
            <Link :href="route('project-codes.edit', projectCode.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
        </div>

        <Card class="max-w-3xl">
            <CardHeader>
                <CardTitle>Project Code Information</CardTitle>
                <CardDescription>
                    Basic information about this project code
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Code
                    </p>
                    <p class="font-medium">{{ projectCode.code }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Name
                    </p>
                    <p class="font-medium">{{ projectCode.name }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Office
                    </p>
                    <p class="font-medium">
                        {{ projectCode.office?.code }} -
                        {{ projectCode.office?.name }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Created At
                    </p>
                    <p class="font-medium">
                        {{ formatDate(projectCode.created_at) }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Last Updated
                    </p>
                    <p class="font-medium">
                        {{ formatDate(projectCode.updated_at) }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
