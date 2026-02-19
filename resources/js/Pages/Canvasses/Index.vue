<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import CanvasIndexHeader from "@/components/canvasses/index/CanvasIndexHeader.vue";
import CanvasIndexStats from "@/components/canvasses/index/CanvasIndexStats.vue";
import CanvasIndexTable from "@/components/canvasses/index/CanvasIndexTable.vue";
import CanvasIndexPagination from "@/components/canvasses/index/CanvasIndexPagination.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Canvassing" }] }, () => page),
});

const props = defineProps({
    canvasses: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const status = ref(props.filters?.status ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("canvasses.index"),
        { search: search.value, status: status.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch([search, status], () => applyFilters());

const clearSearch = () => {
    search.value = "";
};

const showDeleteModal = ref(false);
const canvasToDelete = ref(null);
const openDeleteModal = (canvas) => {
    canvasToDelete.value = canvas;
    showDeleteModal.value = true;
};

const statusVariant = (s) =>
    ({
        pending: "secondary",
        completed: "default",
        returned: "destructive",
    })[s] ?? "outline";

const formatCurrency = (value) => {
    if (!value) return "—";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <CanvasIndexHeader />

        <!-- Stats -->
        <CanvasIndexStats
            :total-count="stats?.total || 0"
            :pending-count="stats?.pending || 0"
            :completed-count="stats?.completed || 0"
            :returned-count="stats?.returned || 0"
        />

        <!-- Table -->
        <CanvasIndexTable
            :canvasses="canvasses"
            :on-delete-click="openDeleteModal"
        >
            <template #search>
                <div class="flex items-center gap-2">
                    <select
                        v-model="status"
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="completed">Completed</option>
                        <option value="returned">Returned</option>
                    </select>
                    <div class="relative w-56">
                        <Icon
                            icon="lucide:search"
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search canvasses..."
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
                </div>
            </template>
        </CanvasIndexTable>

        <!-- Delete Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Canvas"
            description="Are you sure you want to delete this canvas? All selections will be removed."
            :delete-url="
                canvasToDelete
                    ? route('canvasses.destroy', canvasToDelete.id)
                    : ''
            "
        />
    </div>
</template>
