<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import FundIndexHeader from "@/components/funds/index/FundIndexHeader.vue";
import FundIndexStats from "@/components/funds/index/FundIndexStats.vue";
import FundIndexTable from "@/components/funds/index/FundIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Funds" }] }, () => page),
});

const props = defineProps({
    funds: Object,
    offices: Object,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("funds.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear], () => applyFilters());

const clearSearch = () => {
    search.value = "";
};

const showDeleteModal = ref(false);
const fundToDelete = ref(null);

const openDeleteModal = (fund) => {
    fundToDelete.value = fund;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <FundIndexHeader />
        <FundIndexStats :funds="funds" />
        <FundIndexTable
            :funds="funds"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            @update:search="(value) => (search = value)"
            @update:selected-office="(value) => (selectedOffice = value)"
            @update:selected-fiscal-year="
                (value) => (selectedFiscalYear = value)
            "
            @clear="clearSearch"
            @delete="openDeleteModal"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Fund"
            :description="`Are you sure you want to delete '${fundToDelete?.name}'? This action cannot be undone.`"
            :delete-url="
                fundToDelete ? route('funds.destroy', fundToDelete.id) : ''
            "
        />
    </div>
</template>
