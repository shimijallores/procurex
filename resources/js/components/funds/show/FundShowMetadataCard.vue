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
    fund: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Icon icon="lucide:info" class="h-5 w-5" />
                Metadata
            </CardTitle>
            <CardDescription>
                Timestamps and system information
            </CardDescription>
        </CardHeader>
        <CardContent class="space-y-4">
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">Fund ID</p>
                <p class="font-medium">{{ fund.id }}</p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Created At
                </p>
                <p class="font-medium">
                    {{ formatDate(fund.created_at) }}
                </p>
            </div>
            <div class="grid gap-1">
                <p class="text-sm font-medium text-muted-foreground">
                    Last Updated
                </p>
                <p class="font-medium">
                    {{ formatDate(fund.updated_at) }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>
