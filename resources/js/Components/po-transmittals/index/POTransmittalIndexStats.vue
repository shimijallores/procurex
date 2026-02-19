<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    stats: Object,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Total Transmittals</CardTitle
                >
                <Icon
                    icon="lucide:files"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total || 0 }}</div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">COA Type</CardTitle>
                <Icon
                    icon="lucide:file-check"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.coa_count || 0 }}
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">OPG Type</CardTitle>
                <Icon
                    icon="lucide:file-text"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.opg_count || 0 }}
                </div>
            </CardContent>
        </Card>
    </div>
</template>
