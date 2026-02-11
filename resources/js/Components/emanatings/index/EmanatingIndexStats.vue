<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    totalCount: Number,
    currentPageCount: Number,
    currentPage: Number,
    lastPage: Number,
    approvedCount: Number,
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-3">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Total Emanatings
                </CardTitle>
                <Icon
                    icon="lucide:file-text"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ totalCount || 0 }}
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">
                    Current Page
                </CardTitle>
                <Icon
                    icon="lucide:file-stack"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ currentPageCount || 0 }}
                </div>
                <p class="text-xs text-muted-foreground">
                    Page {{ currentPage || 1 }} of
                    {{ lastPage || 1 }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium"> Approved </CardTitle>
                <Icon
                    icon="lucide:check-circle-2"
                    class="h-4 w-4 text-muted-foreground"
                />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">
                    {{ approvedCount || 0 }}
                </div>
            </CardContent>
        </Card>
    </div>
</template>
