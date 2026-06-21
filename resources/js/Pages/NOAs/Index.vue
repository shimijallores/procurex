<script setup>
import { ref, watch, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import NOAIndexHeader from "@/components/noas/index/NOAIndexHeader.vue";
import NOAIndexStats from "@/components/noas/index/NOAIndexStats.vue";
import NOAIndexTable from "@/components/noas/index/NOAIndexTable.vue";
import NoaSmartInput from "@/components/NoaSmartInput.vue";

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

const onNoaSelect = (noa) => {
    search.value = noa;
    applyFilters();
};

const showDeleteModal = ref(false);
const noaToDelete = ref(null);

const openDeleteModal = (noa) => {
    noaToDelete.value = noa;
    showDeleteModal.value = true;
};

onMounted(() => {
    const page = usePage();
    const batchId = page.props.flash?.print_batch_id;
    if (batchId) {
        const url = route("noas.print-batch", batchId);
        window.open(url, "_blank");
    }
});
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
                <NoaSmartInput
                    :model-value="search"
                    @update:model-value="search = $event"
                    @select="onNoaSelect"
                />
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
