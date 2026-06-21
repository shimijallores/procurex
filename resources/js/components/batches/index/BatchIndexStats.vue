<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    stats: Object,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total Batches</CardTitle>
                <Icon
                    icon="lucide:layers"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total || 0 }}</div>
                <p class="text-xs text-muted-foreground">All batch numbers</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">With Projects</CardTitle>
                <Icon
                    icon="lucide:file-spreadsheet"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.with_aoqs || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Batches with Projects assigned
                </p>
            </CardContent>
        </Card>
    </div>
</template>
