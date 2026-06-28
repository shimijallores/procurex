<script setup>
import { computed } from "vue";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    trends: {
        type: Array,
        default: () => [],
    },
    title: { type: String, default: "Monthly Activity" },
    description: { type: String, default: "Records created per month over the last 6 months." },
    color: { type: String, default: "stroke-primary" },
    fillColor: { type: String, default: "fill-primary" },
});

const maxTrend = computed(() =>
    Math.max(...props.trends.map((m) => m.count), 1),
);

const linePoints = computed(() => {
    const count = props.trends.length;
    if (!count) return "";

    return props.trends
        .map((m, i) => {
            const x = 8 + (i / (count - 1 || 1)) * 90;
            const y = 48 - (m.count / maxTrend.value) * 43;
            return `${x},${y}`;
        })
        .join(" ");
});

const lineDots = computed(() => {
    const count = props.trends.length;
    if (!count) return [];

    return props.trends.map((m, i) => ({
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
    <Card>
        <CardHeader>
            <CardTitle>{{ title }}</CardTitle>
            <CardDescription>{{ description }}</CardDescription>
        </CardHeader>
        <CardContent>
            <div v-if="trends.length" class="relative" style="height: 200px">
                <svg viewBox="0 0 100 65" class="h-full w-full">
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
                    <text v-for="g in yGridLines" :key="'l' + g.value" x="0" :y="g.y + 1" class="fill-muted-foreground" font-size="3">
                        {{ g.value }}
                    </text>
                    <polyline :points="linePoints" fill="none" :class="color" stroke-width="0.8" stroke-linejoin="round" stroke-linecap="round" />
                    <circle v-for="dot in lineDots" :key="dot.label" :cx="dot.x" :cy="dot.y" r="1.2" :class="fillColor" />
                    <text
                        v-for="(month, i) in trends"
                        :key="'x' + month.label"
                        :x="8 + (i / (trends.length - 1 || 1)) * 90"
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
</template>
