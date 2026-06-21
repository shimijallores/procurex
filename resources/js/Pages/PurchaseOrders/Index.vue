<script setup>
import { onMounted, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import { route } from "ziggy-js";
import Layout from "@/Layout/Layout.vue";
import PoSmartInput from "@/components/PoSmartInput.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PurchaseOrderIndexHeader from "@/components/purchase-orders/index/PurchaseOrderIndexHeader.vue";
import PurchaseOrderIndexStats from "@/components/purchase-orders/index/PurchaseOrderIndexStats.vue";
import PurchaseOrderIndexTable from "@/components/purchase-orders/index/PurchaseOrderIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Purchase Order" }] }, () => page),
});

const props = defineProps({
    purchaseOrders: Object,
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
        route("purchase-orders.index"),
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
const purchaseOrderToDelete = ref(null);

onMounted(() => {
    const page = usePage();
    const batchId = page.props.flash?.print_batch_id;
    if (batchId) {
        const url = route("purchase-orders.print-batch", batchId);
        window.open(url, "_blank");
    }
});

const openDeleteModal = (purchaseOrder) => {
    purchaseOrderToDelete.value = purchaseOrder;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <PurchaseOrderIndexHeader />

        <PurchaseOrderIndexStats :stats="stats" />

        <PurchaseOrderIndexTable
            :purchase-orders="purchaseOrders"
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
                <div class="relative w-72">
                    <PoSmartInput
                        :model-value="search"
                        @update:model-value="search = $event"
                        @select="search = $event"
                    />
                </div>
            </template>
        </PurchaseOrderIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Purchase Order"
            :description="`Are you sure you want to delete PO ${purchaseOrderToDelete?.po_no || ''}? This action cannot be undone.`"
            :delete-url="
                purchaseOrderToDelete
                    ? route('purchase-orders.destroy', purchaseOrderToDelete.id)
                    : ''
            "
        />
    </div>
</template>
