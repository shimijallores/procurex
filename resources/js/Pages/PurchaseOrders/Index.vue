<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
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
                <div class="relative w-64">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :model-value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search PO..."
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
