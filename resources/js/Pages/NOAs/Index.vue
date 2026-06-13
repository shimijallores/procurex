<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import NOAIndexHeader from "@/components/noas/index/NOAIndexHeader.vue";
import NOAIndexStats from "@/components/noas/index/NOAIndexStats.vue";
import NOAIndexTable from "@/components/noas/index/NOAIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Notice of Award" }] }, () => page),
});

const props = defineProps({
    noas: Object,
    stats: Object,
    offices: Array,
    fiscalYears: Object,
    batches: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");
const selectedBatch = ref(props.filters?.batch_id ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("noas.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
            batch_id: selectedBatch.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear, selectedBatch], () => applyFilters());

const showDeleteModal = ref(false);
const noaToDelete = ref(null);

const openDeleteModal = (noa) => {
    noaToDelete.value = noa;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <NOAIndexHeader />

        <NOAIndexStats :stats="stats" />

        <NOAIndexTable
            :noas="noas"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :batches="batches"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            :selected-batch="selectedBatch"
            @delete-click="openDeleteModal"
            @update:selected-office="selectedOffice = $event"
            @update:selected-fiscal-year="selectedFiscalYear = $event"
            @update:selected-batch="selectedBatch = $event"
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
                        placeholder="Search NOAs..."
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
        </NOAIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Notice of Award"
            :description="`Are you sure you want to delete NOA ${noaToDelete?.noa_no || ''}? This action cannot be undone.`"
            :delete-url="
                noaToDelete ? route('noas.destroy', noaToDelete.id) : ''
            "
        />
    </div>
</template>
