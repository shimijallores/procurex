<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import SVPMatrixIndexHeader from "@/components/svp-matrix/index/SVPMatrixIndexHeader.vue";
import SVPMatrixIndexTable from "@/components/svp-matrix/index/SVPMatrixIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [{ label: "SVP Matrix" }],
            },
            () => page,
        ),
});

const props = defineProps({
    matrixRows: Object,
    offices: Array,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedFiscalYear = ref(
    props.filters?.fiscal_year ?? String(new Date().getFullYear()),
);

const exportUrl = computed(() =>
    route("svp-matrix.export-xlsx", {
        search: search.value || undefined,
        office_id: selectedOffice.value || undefined,
        fiscal_year: selectedFiscalYear.value || undefined,
    }),
);

const applyFilters = useDebounceFn(() => {
    router.get(
        route("svp-matrix.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedFiscalYear], () => applyFilters());
</script>

<template>
    <div class="space-y-6">
        <SVPMatrixIndexHeader :export-url="exportUrl" />

        <SVPMatrixIndexTable
            :matrix-rows="matrixRows"
            :offices="offices"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-fiscal-year="selectedFiscalYear"
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
                        :value="search"
                        @input="search = $event.target.value"
                        type="text"
                        placeholder="Search SVP Matrix..."
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
        </SVPMatrixIndexTable>
    </div>
</template>
