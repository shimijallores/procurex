<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import SupplierIndexHeader from "@/components/suppliers/index/SupplierIndexHeader.vue";
import SupplierIndexStats from "@/components/suppliers/index/SupplierIndexStats.vue";
import SupplierIndexTable from "@/components/suppliers/index/SupplierIndexTable.vue";
import SupplierIndexPagination from "@/components/suppliers/index/SupplierIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Suppliers" }] }, () => page),
});

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("suppliers.index"),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch(search, () => debouncedSearch());

const clearSearch = () => {
    search.value = "";
};

const showDeleteModal = ref(false);
const supplierToDelete = ref(null);

const openDeleteModal = (supplier) => {
    supplierToDelete.value = supplier;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <SupplierIndexHeader />

        <!-- Stats -->
        <SupplierIndexStats
            :total-count="suppliers?.total || 0"
            :active-count="
                suppliers?.data?.filter((s) => s.is_active).length || 0
            "
            :current-page="suppliers?.current_page || 1"
            :last-page="suppliers?.last_page || 1"
        />

        <!-- Table -->
        <SupplierIndexTable
            :suppliers="suppliers"
            :on-delete-click="openDeleteModal"
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
                        placeholder="Search suppliers..."
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
        </SupplierIndexTable>

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Supplier"
            :description="`Are you sure you want to delete '${supplierToDelete?.name}'? This action cannot be undone.`"
            :delete-url="
                supplierToDelete
                    ? route('suppliers.destroy', supplierToDelete.id)
                    : ''
            "
        />
    </div>
</template>
