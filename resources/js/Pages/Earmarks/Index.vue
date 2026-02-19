<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import EarmarkIndexHeader from "@/components/earmarks/index/EarmarkIndexHeader.vue";
import EarmarkIndexStats from "@/components/earmarks/index/EarmarkIndexStats.vue";
import EarmarkIndexTable from "@/components/earmarks/index/EarmarkIndexTable.vue";
import EarmarkPendingTable from "@/components/earmarks/index/EarmarkPendingTable.vue";
import EarmarkBudgetReturnModal from "@/components/earmarks/index/EarmarkBudgetReturnModal.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Budgeting" }] }, () => page),
});

const props = defineProps({
    earmarks: Object,
    pendingReviews: Object,
    stats: Object,
    offices: Array,
    fiscalYears: Object,
    returnReasons: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");
const prSearch = ref(props.filters?.pr_search ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("earmarks.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
            pr_search: prSearch.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear, prSearch], () =>
    applyFilters(),
);

// Return modal
const showReturnModal = ref(false);
const prToReturn = ref(null);
const openReturnModal = (pr) => {
    prToReturn.value = pr;
    showReturnModal.value = true;
};

// Delete modal
const showDeleteModal = ref(false);
const earmarkToDelete = ref(null);
const openDeleteModal = (earmark) => {
    earmarkToDelete.value = earmark;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <EarmarkIndexHeader />

        <EarmarkIndexStats :stats="stats" />

        <!-- PRs Pending Budget Review -->
        <EarmarkPendingTable
            :pending-reviews="pendingReviews"
            :pr-search="prSearch"
            @return-click="openReturnModal"
            @update:pr-search="prSearch = $event"
        >
            <template #search>
                <div class="relative w-56">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :model-value="prSearch"
                        @input="prSearch = $event.target.value"
                        type="text"
                        placeholder="Search PRs…"
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                        v-if="prSearch"
                        @click="prSearch = ''"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </EarmarkPendingTable>

        <!-- Issued Earmarks -->
        <EarmarkIndexTable
            :earmarks="earmarks"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
            @delete-click="openDeleteModal"
            @update:search="search = $event"
            @update:selected-office="selectedOffice = $event"
            @update:selected-fiscal-year="selectedFiscalYear = $event"
        >
            <template #search>
                <div class="relative w-56">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :model-value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search earmarks…"
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
        </EarmarkIndexTable>

        <!-- Return Modal -->
        <EarmarkBudgetReturnModal
            v-model:open="showReturnModal"
            :purchase-request="prToReturn"
            :return-reasons="returnReasons"
        />

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Earmark"
            :description="
                earmarkToDelete
                    ? `Are you sure you want to delete Earmark ${earmarkToDelete.earmark_no}? This will also revert the PR back to budget review status.`
                    : ''
            "
            :delete-url="
                earmarkToDelete
                    ? route('earmarks.destroy', earmarkToDelete.id)
                    : '#'
            "
        />
    </div>
</template>
