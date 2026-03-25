<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AcceptanceInspectionIndexHeader from "@/components/acceptance-inspections/index/AcceptanceInspectionIndexHeader.vue";
import AcceptanceInspectionIndexStats from "@/components/acceptance-inspections/index/AcceptanceInspectionIndexStats.vue";
import AcceptanceInspectionIndexTable from "@/components/acceptance-inspections/index/AcceptanceInspectionIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Acceptance & Inspection" }] },
            () => page,
        ),
});

const props = defineProps({
    acceptanceInspections: Object,
    stats: Object,
    offices: Array,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("acceptance-inspections.index"),
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
const reportToDelete = ref(null);

const openDeleteModal = (report) => {
    reportToDelete.value = report;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <AcceptanceInspectionIndexHeader />

        <AcceptanceInspectionIndexStats :stats="stats" />

        <AcceptanceInspectionIndexTable
            :acceptance-inspections="acceptanceInspections"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            @delete-click="openDeleteModal"
            @update:selected-office="selectedOffice = $event"
            @update:selected-fiscal-year="selectedFiscalYear = $event"
        >
            <template #search>
                <div class="relative w-64">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search reports..."
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                        v-if="search"
                        @click="search = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </AcceptanceInspectionIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Acceptance & Inspection"
            :description="`Are you sure you want to delete report for PO ${reportToDelete?.purchase_order?.po_no || ''}? This action cannot be undone.`"
            :delete-url="
                reportToDelete
                    ? route('acceptance-inspections.destroy', reportToDelete.id)
                    : ''
            "
        />
    </div>
</template>
