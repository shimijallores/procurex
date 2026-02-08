<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import FundIndexHeader from "@/components/funds/index/FundIndexHeader.vue";
import FundIndexStats from "@/components/funds/index/FundIndexStats.vue";
import FundIndexTable from "@/components/funds/index/FundIndexTable.vue";
import FundIndexPagination from "@/components/funds/index/FundIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Funds" }] }, () => page),
});

const props = defineProps({
    funds: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("funds.index"),
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
            :search="search"
            @update:search="(value) => (search = value)"
            @clear="clearSearch"
            @delete="openDeleteModal"
        />
        <FundIndexPagination :funds="funds" />

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
