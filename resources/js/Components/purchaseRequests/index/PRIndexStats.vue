<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    stats: Object,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-4">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total PRs</CardTitle>
                <Icon
                    icon="lucide:file-text"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats?.total || 0 }}</div>
                <p class="text-xs text-muted-foreground">Across all offices</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Draft</CardTitle>
                <Icon
                    icon="lucide:file-edit"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats?.draft || 0 }}</div>
                <p class="text-xs text-muted-foreground">Pending submission</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Approved</CardTitle>
                <Icon
                    icon="lucide:check-circle-2"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div
                    class="text-2xl font-bold text-green-600 dark:text-green-400"
                >
                    {{ stats?.approved || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">Ready for printing</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Returned</CardTitle>
                <Icon
                    icon="lucide:undo-2"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">
                    {{ stats?.returned || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">Sent back to office</p>
            </CardContent>
        </Card>
    </div>
</template>
