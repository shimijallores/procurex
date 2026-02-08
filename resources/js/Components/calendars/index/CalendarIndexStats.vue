<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    stats: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total Entries</CardTitle>
                <Icon
                    icon="lucide:calendar"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.total }}</div>
                <p class="text-xs text-muted-foreground">
                    All calendar entries
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Holidays</CardTitle>
                <Icon
                    icon="lucide:party-popper"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.holidays }}</div>
                <p class="text-xs text-muted-foreground">This year</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Suspended Days</CardTitle
                >
                <Icon
                    icon="lucide:cloud-rain"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.suspended }}</div>
                <p class="text-xs text-muted-foreground">This year</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Upcoming</CardTitle>
                <Icon
                    icon="lucide:calendar-clock"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.upcoming }}</div>
                <p class="text-xs text-muted-foreground">Next 30 days</p>
            </CardContent>
        </Card>
    </div>
</template>
