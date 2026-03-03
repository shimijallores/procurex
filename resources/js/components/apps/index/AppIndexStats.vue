<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardHeader, CardTitle, CardContent } from "@/components/ui/card";

defineProps({
    totalCount: Number,
    currentYearCount: Number,
    officeCount: Number,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total APPs</CardTitle>
                <Icon
                    icon="lucide:file-text"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ totalCount }}</div>
                <p class="text-xs text-muted-foreground">Across all offices</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Current Fiscal Year</CardTitle
                >
                <Icon
                    icon="lucide:calendar"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ currentYearCount }}</div>
                <p class="text-xs text-muted-foreground">
                    Plans for {{ new Date().getFullYear() }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Offices</CardTitle>
                <Icon
                    icon="lucide:building-2"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ officeCount }}</div>
                <p class="text-xs text-muted-foreground">
                    Unique offices with APPs
                </p>
            </CardContent>
        </Card>
    </div>
</template>
