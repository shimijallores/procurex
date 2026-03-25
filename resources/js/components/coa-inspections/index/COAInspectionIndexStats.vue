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
                <CardTitle class="text-sm font-medium">Total Sets</CardTitle>
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
                <CardTitle class="text-sm font-medium">SVP Filled</CardTitle>
                <Icon
                    icon="lucide:circle-check"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.svp_filled || 0 }}
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"
                    >Bidding Filled</CardTitle
                >
                <Icon
                    icon="lucide:badge-check"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ stats?.bidding_filled || 0 }}
                </div>
            </CardContent>
        </Card>
    </div>
</template>
