<script setup>
import Layout from "@/Layout/Layout.vue";
import ProcurementMapIndexView from "@/components/procurement-map/index/ProcurementMapIndexView.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Procurement Map" }] }, () => page),
});

defineProps({
    graph: Object,
    offices: Array,
    fiscalYears: Object,
    filters: Object,
    stats: Object,
    statusOptions: Array,
});
</script>

<template>
    <ProcurementMapIndexView
        :graph="graph"
        :offices="offices"
        :fiscal-years="fiscalYears"
        :filters="filters"
        :stats="stats"
        :status-options="statusOptions"
    />
</template>
