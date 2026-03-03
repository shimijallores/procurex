<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    totalCount: Number,
    pendingCount: Number,
    completedCount: Number,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Total Canvasses</CardTitle
                >
                <Icon
                    icon="lucide:shopping-cart"
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
                <CardTitle class="text-sm font-medium">Pending</CardTitle>
                <Icon
                    icon="lucide:clock"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ pendingCount || 0 }}</div>
                <p class="text-xs text-muted-foreground">In-progress canvas</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Completed</CardTitle>
                <Icon
                    icon="lucide:check-circle"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ completedCount || 0 }}</div>
                <p class="text-xs text-muted-foreground">Prices finalized</p>
            </CardContent>
        </Card>
    </div>
</template>
