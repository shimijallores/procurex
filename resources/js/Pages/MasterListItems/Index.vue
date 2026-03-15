<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import ItemIndexHeader from "@/components/masterListItems/index/ItemIndexHeader.vue";
import ItemIndexStats from "@/components/masterListItems/index/ItemIndexStats.vue";
import ItemIndexTable from "@/components/masterListItems/index/ItemIndexTable.vue";
import ItemIndexPagination from "@/components/masterListItems/index/ItemIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Master List" }] }, () => page),
});

const props = defineProps({
    items: Object,
    categories: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedCategory = ref(props.filters?.category_id ?? "");
const phasedOut = ref(props.filters?.phased_out ?? "");

const printDocxUrl = computed(() =>
    route("master-list-items.print.docx", {
        search: search.value || undefined,
        category_id: selectedCategory.value || undefined,
        phased_out: phasedOut.value || undefined,
    }),
);

const printPdfUrl = computed(() =>
    route("master-list-items.print.pdf", {
        search: search.value || undefined,
        category_id: selectedCategory.value || undefined,
        phased_out: phasedOut.value || undefined,
    }),
);

const applyFilters = useDebounceFn(() => {
    router.get(
        route("master-list-items.index"),
        {
            search: search.value,
            category_id: selectedCategory.value,
            phased_out: phasedOut.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedCategory, phasedOut], () => applyFilters());

const clearSearch = () => {
    search.value = "";
};

const showDeleteModal = ref(false);
const itemToDelete = ref(null);
const openDeleteModal = (item) => {
    itemToDelete.value = item;
    showDeleteModal.value = true;
};

const togglePhaseOut = (item) => {
    router.post(
        route("master-list-items.toggle-phase-out", item.id),
        {},
        {
            preserveScroll: true,
        },
    );
};

const formatCurrency = (value) => {
    if (!value) return "—";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <ItemIndexHeader
            :print-docx-url="printDocxUrl"
            :print-pdf-url="printPdfUrl"
        />

        <!-- Stats -->
        <ItemIndexStats
            :total-count="items?.total || 0"
            :active-count="
                items?.data?.filter((i) => !i.is_phased_out).length || 0
            "
            :phased-out-count="
                items?.data?.filter((i) => i.is_phased_out).length || 0
            "
            :current-page="items?.current_page || 1"
            :last-page="items?.last_page || 1"
        />

        <!-- Table -->
        <ItemIndexTable
            :items="items"
            :categories="categories"
            :selected-category="selectedCategory"
            :phased-out="phasedOut"
            :on-delete-click="openDeleteModal"
            :on-toggle-phase-out="togglePhaseOut"
            @update:selectedCategory="selectedCategory = $event"
            @update:phasedOut="phasedOut = $event"
        >
            <template #search>
                <div class="relative w-56">
                    <Icon
                        icon="lucide:search"
                        class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search items..."
                        class="flex h-9 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
        </ItemIndexTable>

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Item"
            :description="`Are you sure you want to delete '${itemToDelete?.item_name}'? This action cannot be undone.`"
            :delete-url="
                itemToDelete
                    ? route('master-list-items.destroy', itemToDelete.id)
                    : ''
            "
        />
    </div>
</template>
