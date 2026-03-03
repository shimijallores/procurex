<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AppIndexHeader from "@/components/apps/index/AppIndexHeader.vue";
import AppIndexStats from "@/components/apps/index/AppIndexStats.vue";
import AppIndexTable from "@/components/apps/index/AppIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Annual Procurement Plan" }] },
            () => page,
        ),
});

const props = defineProps({
    apps: Object,
    offices: Object,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("apps.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear], () => applyFilters());

const showDeleteModal = ref(false);
const appToDelete = ref(null);

const openDeleteModal = (app) => {
    appToDelete.value = app;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <AppIndexHeader />

        <!-- Stats Cards -->
        <AppIndexStats
            :total-count="apps?.total || 0"
            :current-year-count="
                apps?.data?.filter(
                    (a) => a.fiscal_year === new Date().getFullYear(),
                ).length || 0
            "
            :office-count="
                new Set(apps?.data?.map((a) => a.office_id)).size || 0
            "
        />

        <!-- APPs Table -->
        <AppIndexTable
            :apps="apps"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            @update:search="search = $event"
            @update:selected-office="selectedOffice = $event"
            @update:selected-fiscal-year="selectedFiscalYear = $event"
            @delete="openDeleteModal"
        />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete APP"
            :description="`Are you sure you want to delete this APP for ${appToDelete?.office?.name || 'Unknown Office'} (FY ${appToDelete?.fiscal_year || '—'})? This action cannot be undone.`"
            :delete-url="
                appToDelete ? route('apps.destroy', appToDelete.id) : ''
            "
        />
    </div>
</template>
