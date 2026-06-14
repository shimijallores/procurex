<script setup>
import { computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";

defineOptions({
    layout: Layout,
});

const props = defineProps({
    roleName: String,
    scopeLabel: String,
    metrics: {
        type: Array,
        default: () => [],
    },
    pipeline: {
        type: Array,
        default: () => [],
    },
    recentActivities: {
        type: Array,
        default: () => [],
    },
    quickLinks: {
        type: Array,
        default: () => [],
    },
    monthlyTrends: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const maxPipeline = computed(() =>
    Math.max(...props.pipeline.map((s) => s.value), 1),
);

const maxTrend = computed(() =>
    Math.max(...props.monthlyTrends.map((m) => m.count), 1),
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

const linePoints = computed(() => {
    const count = props.monthlyTrends.length;
    if (!count) return "";

    return props.monthlyTrends
        .map((m, i) => {
            const x = 8 + (i / (count - 1 || 1)) * 90;
            const y = 48 - (m.count / maxTrend.value) * 43;
            return `${x},${y}`;
        })
        .join(" ");
});

const lineDots = computed(() => {
    const count = props.monthlyTrends.length;
    if (!count) return [];

    return props.monthlyTrends.map((m, i) => ({
        x: 8 + (i / (count - 1 || 1)) * 90,
        y: 48 - (m.count / maxTrend.value) * 43,
        count: m.count,
        label: m.label,
    }));
});

const yGridLines = computed(() => {
    const max = maxTrend.value;
    const lines = [];
    for (let pct = 0; pct <= 100; pct += 25) {
        const value = Math.round((max * pct) / 100);
        const y = 48 - (pct / 100) * 43;
        lines.push({ value, y });
    }
    return lines;
});
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-1">
            <PageTitle
                :title="
                    'Welcome back, ' +
                    (user?.name?.split(' ')?.[0] ?? 'User') +
                    '!'
                "
            />
            <p class="text-muted-foreground">
                Crucial {{ scopeLabel?.toLowerCase() }} procurement updates for
                {{ roleName || "your role" }}.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="metric in metrics" :key="metric.title">
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium">
                        {{ metric.title }}
                    </CardTitle>
                    <div
                        class="flex size-8 items-center justify-center rounded-lg bg-primary/10"
                    >
                        <Icon
                            :icon="metric.icon"
                            class="size-4 text-primary"
                        />
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ metric.value }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ metric.description }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-7">
            <Card class="lg:col-span-4">
                <CardHeader>
                    <CardTitle>Workflow Pipeline</CardTitle>
                    <CardDescription>
                        Current document counts in your accessible workflow
                        stages.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="pipeline.length">
                        <div
                            class="flex gap-3"
                            style="height: 180px"
                        >
                            <div
                                v-for="(stage, i) in pipeline"
                                :key="stage.label"
                                class="flex flex-1 flex-col items-center justify-end gap-1.5"
                            >
                                <span
                                    v-if="stage.value > 0"
                                    class="text-xs font-medium"
                                >
                                    {{ stage.value }}
                                </span>
                                <div
                                    class="w-full rounded-t-md bg-muted"
                                    :style="{
                                        height:
                                            (stage.value / maxPipeline) *
                                                100 +
                                            '%',
                                        minHeight: stage.value > 0 ? '4px' : '0',
                                    }"
                                >
                                    <div
                                        :class="
                                            pipelineColors[
                                                i % pipelineColors.length
                                            ]
                                        "
                                        class="h-full w-full rounded-t-md transition-all duration-500"
                                    />
                                </div>
                                <span
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    {{ stage.label }}
                                </span>
                            </div>
                        </div>
                        <div
                            class="mt-4 flex items-center justify-between border-t pt-3 text-xs text-muted-foreground"
                        >
                            <span>{{ totalPipeline }} total documents</span>
                            <span class="flex flex-wrap gap-3">
                                <span
                                    v-for="(stage, i) in pipeline"
                                    :key="stage.label"
                                    class="flex items-center gap-1"
                                >
                                    <span
                                        class="inline-block size-2 rounded-full"
                                        :class="
                                            pipelineColors[
                                                i % pipelineColors.length
                                            ]
                                        "
                                    />
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

            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Monthly Activity</CardTitle>
                    <CardDescription>
                        Records created per month over the last 6 months.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        v-if="monthlyTrends.length"
                        class="relative"
                        style="height: 200px"
                    >
                        <svg
                            viewBox="0 0 100 65"
                            class="h-full w-full"
                        >
                            <line
                                v-for="g in yGridLines"
                                :key="g.value"
                                :x1="8"
                                :y1="g.y"
                                x2="98"
                                :y2="g.y"
                                class="stroke-border"
                                stroke-width="0.4"
                                stroke-dasharray="1,1.5"
                            />
                            <text
                                v-for="g in yGridLines"
                                :key="'l'+g.value"
                                x="0"
                                :y="g.y + 1"
                                class="fill-muted-foreground"
                                font-size="3"
                            >
                                {{ g.value }}
                            </text>
                            <polyline
                                :points="linePoints"
                                fill="none"
                                class="stroke-primary"
                                stroke-width="0.8"
                                stroke-linejoin="round"
                                stroke-linecap="round"
                            />
                            <circle
                                v-for="dot in lineDots"
                                :key="dot.label"
                                :cx="dot.x"
                                :cy="dot.y"
                                r="1.2"
                                class="fill-primary"
                            />
                            <text
                                v-for="(month, i) in monthlyTrends"
                                :key="'x'+month.label"
                                :x="8 + (i / (monthlyTrends.length - 1 || 1)) * 90"
                                y="58"
                                text-anchor="middle"
                                class="fill-muted-foreground"
                                font-size="3"
                            >
                                {{ month.label }}
                            </text>
                        </svg>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No activity data available.
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-7">
            <Card class="lg:col-span-4">
                <CardHeader>
                    <CardTitle>Recent Records</CardTitle>
                    <CardDescription>
                        Latest documents relevant to your role.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div v-if="recentActivities.length" class="space-y-3">
                        <Link
                            v-for="(entry, index) in recentActivities"
                            :key="`${entry.title}-${index}`"
                            :href="entry.link"
                            class="flex items-start justify-between gap-3 rounded-md border p-3 transition-colors hover:bg-muted/40"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{ entry.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ entry.subtitle }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ entry.meta }}
                                </p>
                            </div>
                            <span
                                class="whitespace-nowrap text-xs text-muted-foreground"
                                >{{ entry.date }}</span
                            >
                        </Link>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No recent records found.
                    </div>
                </CardContent>
            </Card>

            <Card class="lg:col-span-3">
                <CardHeader>
                    <CardTitle>Quick Access</CardTitle>
                    <CardDescription>
                        Shortcut links for your most common tasks.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="grid gap-2">
                        <Link
                            v-for="link in quickLinks"
                            :key="link.label"
                            :href="link.href"
                        >
                            <Button
                                variant="outline"
                                class="w-full justify-start"
                            >
                                <Icon :icon="link.icon" class="mr-2 size-4" />
                                {{ link.label }}
                            </Button>
                        </Link>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
