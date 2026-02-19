<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    totalCount: Number,
    activeCount: Number,
    phasedOutCount: Number,
    currentPage: Number,
    lastPage: Number,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total Items</CardTitle>
                <Icon
                    icon="lucide:package"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ totalCount || 0 }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Active Items</CardTitle>
                <Icon
                    icon="lucide:check-circle"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ activeCount || 0 }}</div>
                <p class="text-xs text-muted-foreground">Available to canvas</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Phased Out</CardTitle>
                <Icon
                    icon="lucide:archive"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ phasedOutCount || 0 }}</div>
                <p class="text-xs text-muted-foreground">No longer available</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Current Page</CardTitle>
                <Icon
                    icon="lucide:list"
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
