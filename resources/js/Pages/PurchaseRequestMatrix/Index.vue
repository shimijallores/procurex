<script setup>
import { computed, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import PRMatrixIndexHeader from "@/components/purchase-request-matrix/index/PRMatrixIndexHeader.vue";
import PRMatrixIndexTable from "@/components/purchase-request-matrix/index/PRMatrixIndexTable.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Purchase Requests",
                        href: route("purchase-requests.index"),
                    },
                    { label: "PR Matrix" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    matrixRows: Object,
    offices: Array,
    accounts: Array,
    fiscalYears: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(props.filters?.office_id ?? "");
const selectedAccount = ref(props.filters?.account_id ?? "");
const selectedFiscalYear = ref(
    props.filters?.fiscal_year ?? String(new Date().getFullYear()),
);

const exportUrl = computed(() =>
    route("purchase-request-matrix.export-xlsx", {
        search: search.value || undefined,
        office_id: selectedOffice.value || undefined,
        account_id: selectedAccount.value || undefined,
        fiscal_year: selectedFiscalYear.value || undefined,
    }),
);

const applyFilters = useDebounceFn(() => {
    router.get(
        route("purchase-request-matrix.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            account_id: selectedAccount.value,
            fiscal_year: selectedFiscalYear.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}, 300);

watch([search, selectedOffice, selectedAccount, selectedFiscalYear], () =>
    applyFilters(),
);
</script>

<template>
    <div class="space-y-6">
        <PRMatrixIndexHeader :export-url="exportUrl" />

        <PRMatrixIndexTable
            :matrix-rows="matrixRows"
            :offices="offices"
            :accounts="accounts"
            :fiscal-years="fiscalYears"
            :search="search"
            :selected-office="selectedOffice"
            :selected-account="selectedAccount"
            :selected-fiscal-year="selectedFiscalYear"
            @update:search="search = $event"
            @update:selected-office="selectedOffice = $event"
            @update:selected-account="selectedAccount = $event"
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
                        placeholder="Search PR Matrix..."
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
        </PRMatrixIndexTable>
    </div>
</template>
