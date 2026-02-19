<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import RFQIndexHeader from "@/components/rfqs/index/RFQIndexHeader.vue";
import RFQIndexStats from "@/components/rfqs/index/RFQIndexStats.vue";
import RFQIndexTable from "@/components/rfqs/index/RFQIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Request for Quotation" }] },
            () => page,
        ),
});

const props = defineProps({
    rfqs: Object,
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
        route("rfqs.index"),
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
const rfqToDelete = ref(null);

const openDeleteModal = (rfq) => {
    rfqToDelete.value = rfq;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <RFQIndexHeader />

        <RFQIndexStats :stats="stats" />

        <RFQIndexTable
            :rfqs="rfqs"
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
                <div class="relative w-64">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        :model-value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search RFQs…"
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
        </RFQIndexTable>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete RFQ"
            :description="`Are you sure you want to delete ${rfqToDelete?.svp_no || 'this RFQ'}? This action cannot be undone.`"
            :delete-url="
                rfqToDelete ? route('rfqs.destroy', rfqToDelete.id) : ''
            "
        />
    </div>
</template>
