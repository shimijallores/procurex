<script setup>
import { computed } from "vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    pipeline: {
        type: Array,
        default: () => [],
    },
    title: { type: String, default: "Workflow Pipeline" },
    description: { type: String, default: "Current document counts in your accessible workflow stages." },
});

const maxPipeline = computed(() =>
    Math.max(...props.pipeline.map((s) => s.value), 1),
);

const pipelineColors = [
    "bg-blue-300",
    "bg-cyan-300",
    "bg-teal-300",
    "bg-emerald-300",
    "bg-amber-300",
    "bg-violet-300",
    "bg-rose-300",
];

const totalPipeline = computed(() =>
    props.pipeline.reduce((sum, s) => sum + s.value, 0),
);

const pipelinePercentage = (value) =>
    totalPipeline.value > 0
        ? ((value / totalPipeline.value) * 100).toFixed(1)
        : 0;
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="pipeline.length">
                <div class="flex gap-3" style="height: 180px">
                    <div
                        v-for="(stage, i) in pipeline"
                        :key="stage.label"
                        class="flex flex-1 flex-col items-center justify-end gap-1.5"
                    >
                        <span v-if="stage.value > 0" class="text-xs font-medium">
                            {{ stage.value }}
                        </span>
                        <div
                            class="w-full rounded-t-md bg-muted"
                            :style="{
                                height: (stage.value / maxPipeline) * 100 + '%',
                                minHeight: stage.value > 0 ? '4px' : '0',
                            }"
                        >
                            <div
                                :class="pipelineColors[i % pipelineColors.length]"
                                class="h-full w-full rounded-t-md transition-all duration-500"
                            />
                        </div>
                        <span class="text-center text-xs text-muted-foreground">
                            {{ stage.label }}
                        </span>
                    </div>
                </div>
                <div class="mt-4 flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                    <span>{{ totalPipeline }} total documents</span>
                    <span class="flex flex-wrap gap-3">
                        <span v-for="(stage, i) in pipeline" :key="stage.label" class="flex items-center gap-1">
                            <span class="inline-block size-2 rounded-full" :class="pipelineColors[i % pipelineColors.length]" />
                            {{ pipelinePercentage(stage.value) }}%
                        </span>
                    </span>
                </div>
            </div>
            <div v-else class="text-sm text-muted-foreground">
                No pipeline data available.
            </div>
        </CardContent>
    </Card>
</template>
