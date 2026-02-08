<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AppIndexHeader from "@/components/apps/index/AppIndexHeader.vue";
import AppIndexStats from "@/components/apps/index/AppIndexStats.vue";
import AppIndexTable from "@/components/apps/index/AppIndexTable.vue";
import AppIndexPagination from "@/components/apps/index/AppIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Annual Procurement Plan" }] },
            () => page,
        ),
});

const props = defineProps({
    apps: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("apps.index"),
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

const showDeleteModal = ref(false);
const appToDelete = ref(null);

const openDeleteModal = (app) => {
    appToDelete.value = app;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <AppIndexHeader />

        <!-- Stats Cards -->
        <AppIndexStats
            :total-count="apps?.total || 0"
            :current-year-count="
                apps?.data?.filter(
                    (a) => a.fiscal_year === new Date().getFullYear(),
                ).length || 0
            "
            :office-count="
                new Set(apps?.data?.map((a) => a.office_id)).size || 0
            "
        />

        <!-- APPs Table -->
        <AppIndexTable
            :apps="apps"
            :search="search"
            @update:search="search = $event"
            @delete="openDeleteModal"
        />

        <!-- Pagination -->
        <AppIndexPagination :apps="apps" />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Procurement Plan"
            :description="`Are you sure you want to delete this APP for ${appToDelete?.office?.name}? This will also delete all categories and items.`"
            :delete-url="
                appToDelete ? route('apps.destroy', appToDelete.id) : ''
            "
        />
    </div>
</template>
