<script setup>
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PageTitle from "@/components/PageTitle.vue";
import DashboardMetrics from "@/components/dashboard/DashboardMetrics.vue";
import DashboardPipeline from "@/components/dashboard/DashboardPipeline.vue";
import DashboardTrends from "@/components/dashboard/DashboardTrends.vue";
import DashboardRecent from "@/components/dashboard/DashboardRecent.vue";
import DashboardQuickLinks from "@/components/dashboard/DashboardQuickLinks.vue";

defineOptions({
    layout: Layout,
});

const props = defineProps({
    roleName: String,
    scopeLabel: String,
    metrics: { type: Array, default: () => [] },
    pipeline: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
    quickLinks: { type: Array, default: () => [] },
    monthlyTrends: { type: [Array, Object], default: () => [] },
    secondaryTrends: { type: [Array, Object], default: () => [] },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const trendData = computed(() => {
    const t = props.monthlyTrends;
    return Array.isArray(t) ? t : (t?.data ?? []);
});

const secondaryTrendData = computed(() => {
    const t = props.secondaryTrends;
    return Array.isArray(t) ? t : (t?.data ?? []);
});
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-1">
            <PageTitle
                :title="'Welcome back, ' + (user?.name?.split(' ')?.[0] ?? 'User') + '!'"
            />
            <p class="text-muted-foreground">
                Crucial {{ scopeLabel?.toLowerCase() }} procurement updates for {{ roleName || 'your role' }}.
            </p>
        </div>

        <DashboardMetrics :metrics="metrics" />

        <div class="grid gap-6 lg:grid-cols-7">
            <DashboardPipeline
                class="lg:col-span-4"
                :pipeline="pipeline"
            />

            <DashboardTrends
                class="lg:col-span-3"
                :trends="trendData"
                :title="'Monthly ' + (monthlyTrends.label || 'Activity')"
                :description="'Records created per month over the last 6 months.'"
            />
        </div>

        <div v-if="secondaryTrendData.length" class="grid gap-6 lg:grid-cols-7">
            <DashboardTrends
                class="lg:col-span-7"
                :trends="secondaryTrendData"
                :title="'Monthly ' + (secondaryTrends.label || 'Activity')"
                color="stroke-emerald-500"
                fill-color="fill-emerald-500"
            />
        </div>

        <div class="grid gap-6 lg:grid-cols-7">
            <DashboardRecent
                class="lg:col-span-4"
                :items="recentActivities"
            />

            <DashboardQuickLinks
                class="lg:col-span-3"
                :links="quickLinks"
            />
        </div>
    </div>
</template>
