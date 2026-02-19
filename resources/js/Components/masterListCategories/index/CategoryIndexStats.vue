<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    totalCount: Number,
    activeCount: Number,
    currentPage: Number,
    lastPage: Number,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Total Categories</CardTitle
                >
                <Icon icon="lucide:tag" class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ totalCount || 0 }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Active Categories</CardTitle
                >
                <Icon
                    icon="lucide:check-circle"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ activeCount || 0 }}</div>
                <p class="text-xs text-muted-foreground">Currently in use</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Current Page</CardTitle>
                <Icon
                    icon="lucide:file-stack"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ currentPage || 1 }}</div>
                <p class="text-xs text-muted-foreground">
                    Page {{ currentPage || 1 }} of {{ lastPage || 1 }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
