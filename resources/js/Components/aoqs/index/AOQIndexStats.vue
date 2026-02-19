<script setup>
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    stats: Object,
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Total AOQs</CardDescription>
                <CardTitle class="text-2xl">{{ stats?.total || 0 }}</CardTitle>
            </CardHeader>
            <CardContent
                class="text-xs text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:file-spreadsheet" class="h-3.5 w-3.5" />
                Total created abstracts
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Single Calculated</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.single_calculated || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent
                class="text-xs text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:user" class="h-3.5 w-3.5" />
                One supplier computed
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>Lowest Calculated</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.lowest_calculated || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent
                class="text-xs text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:users" class="h-3.5 w-3.5" />
                Two or more suppliers compared
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="pb-2">
                <CardDescription>No Winner Assigned</CardDescription>
                <CardTitle class="text-2xl">{{
                    stats?.without_winner || 0
                }}</CardTitle>
            </CardHeader>
            <CardContent
                class="text-xs text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:triangle-alert" class="h-3.5 w-3.5" />
                Pending winner determination
            </CardContent>
        </Card>
    </div>
</template>
