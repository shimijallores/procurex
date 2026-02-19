<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import BACResolutionIndexHeader from "@/components/bac-resolutions/index/BACResolutionIndexHeader.vue";
import BACResolutionIndexStats from "@/components/bac-resolutions/index/BACResolutionIndexStats.vue";
import BACResolutionIndexTable from "@/components/bac-resolutions/index/BACResolutionIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "BAC Resolutions" }] }, () => page),
});

const props = defineProps({
    resolutions: Object,
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
        route("bac-resolutions.index"),
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
const resolutionToDelete = ref(null);

const openDeleteModal = (resolution) => {
    resolutionToDelete.value = resolution;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <BACResolutionIndexHeader />

        <BACResolutionIndexStats :stats="stats" />

        <BACResolutionIndexTable
            :resolutions="resolutions"
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
                        placeholder="Search BAC Resolutions…"
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
        </BACResolutionIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete BAC Resolution"
            :description="`Are you sure you want to delete ${resolutionToDelete?.resolution_no || 'this BAC Resolution'}? This action cannot be undone.`"
            :delete-url="
                resolutionToDelete
                    ? route('bac-resolutions.destroy', resolutionToDelete.id)
                    : ''
            "
        />
    </div>
</template>
