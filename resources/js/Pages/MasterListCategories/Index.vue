<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import CategoryIndexHeader from "@/components/masterListCategories/index/CategoryIndexHeader.vue";
import CategoryIndexStats from "@/components/masterListCategories/index/CategoryIndexStats.vue";
import CategoryIndexTable from "@/components/masterListCategories/index/CategoryIndexTable.vue";
import CategoryIndexPagination from "@/components/masterListCategories/index/CategoryIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            { breadcrumbs: [{ label: "Master List Categories" }] },
            () => page,
        ),
});

const props = defineProps({
    categories: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("master-list-categories.index"),
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
const categoryToDelete = ref(null);

const openDeleteModal = (category) => {
    categoryToDelete.value = category;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <CategoryIndexHeader />

        <!-- Stats -->
        <CategoryIndexStats
            :total-count="categories?.total || 0"
            :active-count="
                categories?.data?.filter((c) => c.is_active).length || 0
            "
            :current-page="categories?.current_page || 1"
            :last-page="categories?.last_page || 1"
        />

        <!-- Table -->
        <CategoryIndexTable
            :categories="categories"
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
                        placeholder="Search categories..."
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
        </CategoryIndexTable>

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Category"
            :description="`Are you sure you want to delete '${categoryToDelete?.name}'? This will also remove all items in this category.`"
            :delete-url="
                categoryToDelete
                    ? route(
                          'master-list-categories.destroy',
                          categoryToDelete.id,
                      )
                    : ''
            "
        />
    </div>
</template>
