<script setup>
import { computed } from "vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    statuses: {
        type: Array,
        default: () => [],
    },
    title: { type: String, default: "Status Distribution" },
    description: { type: String, default: "Breakdown of documents by current status." },
});

const statusColors = [
    "bg-blue-400",
    "bg-emerald-400",
    "bg-amber-400",
    "bg-rose-400",
    "bg-violet-400",
    "bg-cyan-400",
    "bg-gray-400",
];

const total = computed(() =>
    props.statuses.reduce((sum, s) => sum + s.value, 0),
);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="statuses.length" class="space-y-4">
                <div class="flex h-6 w-full overflow-hidden rounded-full bg-muted">
                    <div
                        v-for="(status, i) in statuses"
                        :key="status.label"
                        :style="{ width: total > 0 ? (status.value / total) * 100 + '%' : '0%' }"
                        :class="statusColors[i % statusColors.length]"
                        class="h-full transition-all duration-500 first:rounded-l-full last:rounded-r-full"
                        :title="status.label + ': ' + status.value"
                    />
                </div>
                <div class="flex flex-wrap gap-x-4 gap-y-1.5 text-xs text-muted-foreground">
                    <div v-for="(status, i) in statuses" :key="status.label" class="flex items-center gap-1.5">
                        <span class="inline-block size-2.5 rounded-sm" :class="statusColors[i % statusColors.length]" />
                        {{ status.label }}
                        <span class="font-medium text-foreground">{{ status.value }}</span>
                        <span>({{ total > 0 ? ((status.value / total) * 100).toFixed(1) : 0 }}%)</span>
                    </div>
                </div>
            </div>
            <div v-else class="text-sm text-muted-foreground">
                No status data available.
            </div>
        </CardContent>
    </Card>
</template>
