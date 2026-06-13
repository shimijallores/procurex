<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AOQIndexHeader from "@/components/aoqs/index/AOQIndexHeader.vue";
import AOQIndexStats from "@/components/aoqs/index/AOQIndexStats.vue";
import AOQIndexTable from "@/components/aoqs/index/AOQIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Abstract of Quotation" }] },
            () => page,
        ),
});

const props = defineProps({
    aoqs: Object,
    stats: Object,
    offices: Array,
    batches: Array,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");
const selectedBatch = ref(props.filters?.batch_id ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("aoqs.index"),
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
const aoqToDelete = ref(null);

const openDeleteModal = (aoq) => {
    aoqToDelete.value = aoq;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <AOQIndexHeader />

        <AOQIndexStats :stats="stats" />

        <AOQIndexTable
            :aoqs="aoqs"
            :offices="offices"
            :batches="batches"
            :fiscal-years="fiscalYears"
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
                        placeholder="Search AOQs…"
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
        </AOQIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete AOQ"
            :description="`Are you sure you want to delete AOQ for ${aoqToDelete?.rfq?.svp_no || 'this RFQ'}? This action cannot be undone.`"
            :delete-url="
                aoqToDelete ? route('aoqs.destroy', aoqToDelete.id) : ''
            "
        />
    </div>
</template>
