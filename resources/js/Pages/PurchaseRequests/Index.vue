<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PRIndexHeader from "@/components/purchaseRequests/index/PRIndexHeader.vue";
import PRIndexStats from "@/components/purchaseRequests/index/PRIndexStats.vue";
import PRIndexTable from "@/components/purchaseRequests/index/PRIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Purchase Requests" }] },
            () => page,
        ),
});

const props = defineProps({
    purchaseRequests: Object,
    stats: Object,
    offices: Array,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");
const selectedStatus = ref(props.filters?.status ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("purchase-requests.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
            status: selectedStatus.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear, selectedStatus], () =>
    applyFilters(),
);

const showDeleteModal = ref(false);
const prToDelete = ref(null);

const openDeleteModal = (pr) => {
    prToDelete.value = pr;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <PRIndexHeader />

        <PRIndexStats :stats="stats" />

        <PRIndexTable
            :purchase-requests="purchaseRequests"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            :selected-status="selectedStatus"
            @delete-click="openDeleteModal"
            @update:search="search = $event"
            @update:selected-office="selectedOffice = $event"
            @update:selected-fiscal-year="selectedFiscalYear = $event"
            @update:selected-status="selectedStatus = $event"
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
                        placeholder="Search PRs…"
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
        </PRIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Purchase Request"
            :description="`Are you sure you want to delete PR ${prToDelete?.pr_no || '#' + prToDelete?.id}? This action cannot be undone.`"
            :delete-url="
                prToDelete
                    ? route('purchase-requests.destroy', prToDelete.id)
                    : ''
            "
        />
    </div>
</template>
