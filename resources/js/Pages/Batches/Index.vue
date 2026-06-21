<script setup>
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import BatchSmartInput from "@/components/BatchSmartInput.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import BatchIndexHeader from "@/components/batches/index/BatchIndexHeader.vue";
import BatchIndexStats from "@/components/batches/index/BatchIndexStats.vue";
import BatchIndexTable from "@/components/batches/index/BatchIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Batches" }] }, () => page),
});

const props = defineProps({
    batches: Object,
    stats: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("batches.index"),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch(search, () => applyFilters());

const onBatchSelect = (batch) => {
    if (batch?.id) {
        router.visit(route("batches.show", batch.id));
    } else {
        search.value = batch?.batch_no ?? "";
        applyFilters();
    }
};

const showDeleteModal = ref(false);
const batchToDelete = ref(null);

const openDeleteModal = (batch) => {
    batchToDelete.value = batch;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <BatchIndexHeader />

        <BatchIndexStats :stats="stats" />

        <BatchIndexTable
            :batches="batches"
            @delete-click="openDeleteModal"
        >
            <template #search>
                <div class="relative w-72">
                    <BatchSmartInput
                        :model-value="search"
                        @update:model-value="search = $event"
                        @select="onBatchSelect"
                    />
                </div>
            </template>
        </BatchIndexTable>

        <DeleteModal
            v-if="batchToDelete"
            v-model:open="showDeleteModal"
            title="Delete Batch"
            :description="`Are you sure you want to delete batch '${batchToDelete.batch_no}'? This action cannot be undone.`"
            :delete-url="route('batches.destroy', batchToDelete.id)"
        />
    </div>
</template>
