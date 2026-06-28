<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    metrics: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card v-for="metric in metrics" :key="metric.title">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium">
                    {{ metric.title }}
                </CardTitle>
                <div class="flex size-8 items-center justify-center rounded-lg bg-primary/10">
                    <Icon :icon="metric.icon" class="size-4 text-primary" />
                </div>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ metric.value }}</div>
                <p class="text-xs text-muted-foreground">
                    {{ metric.description }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
