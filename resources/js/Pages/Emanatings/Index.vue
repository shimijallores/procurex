<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import EmanatingIndexHeader from "@/components/emanatings/index/EmanatingIndexHeader.vue";
import EmanatingIndexStats from "@/components/emanatings/index/EmanatingIndexStats.vue";
import EmanatingIndexTable from "@/components/emanatings/index/EmanatingIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Emanating Requests" }] },
            () => page,
        ),
});

const props = defineProps({
    emanatings: Object,
    offices: Object,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("emanatings.index"),
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

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const showDeleteModal = ref(false);
const emanatingToDelete = ref(null);

const openDeleteModal = (emanating) => {
    emanatingToDelete.value = emanating;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <EmanatingIndexHeader />

        <!-- Stats Cards -->
        <EmanatingIndexStats
            :total-count="emanatings?.total || 0"
            :current-page-count="emanatings?.data?.length || 0"
            :current-page="emanatings?.current_page || 1"
            :last-page="emanatings?.last_page || 1"
            :approved-count="
                emanatings?.data?.filter((e) => e.is_approved).length || 0
            "
        />

        <!-- Emanating Requests Table -->
        <EmanatingIndexTable
            :emanatings="emanatings"
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
                        placeholder="Search Emanatings..."
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
        </EmanatingIndexTable>

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Emanating Request"
            :description="`Are you sure you want to delete this emanating request for ${emanatingToDelete?.project?.fund?.office?.name} - ${emanatingToDelete?.project?.name} (FY ${emanatingToDelete?.fiscal_year})? This will also delete all associated items.`"
            :delete-url="
                emanatingToDelete
                    ? route('emanatings.destroy', emanatingToDelete.id)
                    : ''
            "
        />
    </div>
</template>
