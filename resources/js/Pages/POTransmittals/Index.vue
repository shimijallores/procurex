<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import POTransmittalIndexHeader from "@/components/po-transmittals/index/POTransmittalIndexHeader.vue";
import POTransmittalIndexStats from "@/components/po-transmittals/index/POTransmittalIndexStats.vue";
import POTransmittalIndexTable from "@/components/po-transmittals/index/POTransmittalIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "PO Transmittal" }] }, () => page),
});

const props = defineProps({
    poTransmittals: Object,
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
        route("po-transmittals.index"),
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
const poTransmittalToDelete = ref(null);

const openDeleteModal = (poTransmittal) => {
    poTransmittalToDelete.value = poTransmittal;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <POTransmittalIndexHeader />

        <POTransmittalIndexStats :stats="stats" />

        <POTransmittalIndexTable
            :po-transmittals="poTransmittals"
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
                        :model-value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search transmittals..."
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
        </POTransmittalIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PO Transmittal"
            :description="'Are you sure you want to delete this PO transmittal set (COA + OPG)? This action cannot be undone.'"
            :delete-url="
                poTransmittalToDelete
                    ? route('po-transmittals.destroy', poTransmittalToDelete.id)
                    : ''
            "
        />
    </div>
</template>
