<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import PPMPIndexHeader from "@/components/ppmps/index/PPMPIndexHeader.vue";
import PPMPIndexStats from "@/components/ppmps/index/PPMPIndexStats.vue";
import PPMPIndexTable from "@/components/ppmps/index/PPMPIndexTable.vue";
import PPMPIndexPagination from "@/components/ppmps/index/PPMPIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Project Procurement Management Plan" }] },
            () => page,
        ),
});

const props = defineProps({
    ppmps: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("ppmps.index"),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch(search, () => {
    debouncedSearch();
});

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
const ppmpToDelete = ref(null);

const openDeleteModal = (ppmp) => {
    ppmpToDelete.value = ppmp;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <PPMPIndexHeader />

        <!-- Stats Cards -->
        <PPMPIndexStats
            :total-count="ppmps?.total || 0"
            :current-page-count="ppmps?.data?.length || 0"
            :current-page="ppmps?.current_page || 1"
            :last-page="ppmps?.last_page || 1"
            :addendum-count="
                ppmps?.data?.filter((ppmp) => ppmp.is_addendum).length || 0
            "
        />

        <!-- PPMPs Table -->
        <PPMPIndexTable
            :ppmps="ppmps"
            :search="search"
            @delete-click="openDeleteModal"
        >
            <template #search>
                <div class="relative w-64">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search PPMPs..."
                        class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <button
                        v-if="search"
                        @click="clearSearch"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                        <Icon icon="lucide:x" class="h-4 w-4" />
                    </button>
                </div>
            </template>
        </PPMPIndexTable>

        <!-- Pagination -->
        <PPMPIndexPagination :ppmps="ppmps" />

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete PPMP"
            :description="`Are you sure you want to delete this PPMP for ${ppmpToDelete?.office?.name} - ${ppmpToDelete?.project?.name} (FY ${ppmpToDelete?.fiscal_year})? This will also delete all associated categories and items.`"
            :delete-url="
                ppmpToDelete ? route('ppmps.destroy', ppmpToDelete.id) : ''
            "
        />
    </div>
</template>
