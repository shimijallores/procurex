<script setup>
import { computed, ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import { VueFlow } from "@vue-flow/core";
import { Background, BackgroundVariant } from "@vue-flow/background";
import { Controls } from "@vue-flow/controls";
import { Button } from "@/components/ui/button";

import "@vue-flow/core/dist/style.css";
import "@vue-flow/core/dist/theme-default.css";
import "@vue-flow/controls/dist/style.css";

const props = defineProps({
    graph: Object,
    offices: Array,
    fiscalYears: Object,
    filters: Object,
    stats: Object,
    statusOptions: Array,
});

const search = ref(props.filters?.search ?? "");
const selectedOffice = ref(
    props.filters?.office_id ? String(props.filters.office_id) : "",
);
const selectedFiscalYear = ref(props.filters?.fiscal_year ?? "");
const selectedStatus = ref(props.filters?.status ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("procurement-map.index"),
        {
            search: search.value,
            office_id: selectedOffice.value,
            fiscal_year: selectedFiscalYear.value,
            status: selectedStatus.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 250);

watch([search, selectedOffice, selectedFiscalYear, selectedStatus], () => {
    applyFilters();
});

const statusPalette = {
    pending: {
        nodeBg: "#fef3c7",
        nodeBorder: "#f59e0b",
        text: "#92400e",
        edge: "#f59e0b",
        badge: "bg-amber-100 text-amber-800",
    },
    ongoing: {
        nodeBg: "#dbeafe",
        nodeBorder: "#3b82f6",
        text: "#1e3a8a",
        edge: "#3b82f6",
        badge: "bg-blue-100 text-blue-800",
    },
    completed: {
        nodeBg: "#dcfce7",
        nodeBorder: "#22c55e",
        text: "#14532d",
        edge: "#22c55e",
        badge: "bg-emerald-100 text-emerald-800",
    },
};

const resolvedStatus = (value) => {
    const normalized = String(value || "ongoing").toLowerCase();
    return statusPalette[normalized] ? normalized : "ongoing";
};

const nodesById = computed(() => {
    const map = new Map();

    for (const node of props.graph?.nodes || []) {
        map.set(node.id, node);
    }

    return map;
});

const flowNodes = computed(() => {
    return (props.graph?.nodes || []).map((node) => {
        const status = resolvedStatus(node?.data?.status);
        const palette = statusPalette[status];
        const category = String(node?.data?.category || "").trim();
        const normalizedCategory = category.toLowerCase();
        const isOfficeNode = normalizedCategory === "office";
        const label = String(node?.data?.label || "").trim();
        const displayLabel = isOfficeNode
            ? label
            : category
              ? `${category} • ${label}`
              : label;

        const officeStyle = isOfficeNode
            ? {
                  border: "2.5px solid #2563eb",
                  backgroundColor: "#dbeafe",
                  color: "#1e3a8a",
                  borderRadius: "9999px",
                  width: "220px",
                  height: "220px",
                  minWidth: "220px",
                  minHeight: "220px",
                  fontSize: "20px",
                  fontWeight: 700,
                  lineHeight: "1.25",
                  display: "flex",
                  alignItems: "center",
                  justifyContent: "center",
                  textAlign: "center",
                  wordBreak: "break-word",
                  boxShadow: "0 10px 28px rgba(37, 99, 235, 0.22)",
                  padding: "18px",
              }
            : {};

        return {
            ...node,
            data: {
                ...node.data,
                status,
                label: displayLabel,
            },
            style: {
                border: `1.5px solid ${palette.nodeBorder}`,
                backgroundColor: palette.nodeBg,
                color: palette.text,
                borderRadius: "10px",
                minWidth: "190px",
                fontSize: "12px",
                fontWeight: 600,
                boxShadow: "0 2px 10px rgba(15, 23, 42, 0.08)",
                padding: "8px 10px",
                ...officeStyle,
            },
        };
    });
});

const flowEdges = computed(() => {
    return (props.graph?.edges || []).map((edge) => {
        const sourceNode = nodesById.value.get(edge.source);
        const status = resolvedStatus(sourceNode?.data?.status);
        const palette = statusPalette[status];

        return {
            ...edge,
            animated: status === "ongoing",
            style: {
                stroke: palette.edge,
                strokeWidth: 1.5,
            },
        };
    });
});

const selectedNode = ref(null);

const onNodeClick = ({ node }) => {
    selectedNode.value = node;
};

const selectedNodeEntries = computed(() => {
    const meta = selectedNode.value?.data?.meta;
    if (!meta) {
        return [];
    }

    return Object.entries(meta).filter(
        ([, value]) => value !== null && value !== undefined && value !== "",
    );
});

const statusBadgeClass = computed(() => {
    const status = resolvedStatus(selectedNode.value?.data?.status);
    return statusPalette[status].badge;
});

const clearFilters = () => {
    search.value = "";
    selectedOffice.value = "";
    selectedFiscalYear.value = "";
    selectedStatus.value = "";
};
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-1">
            <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                Procurement Map
            </h1>
            <p class="text-muted-foreground">
                Workflow procurement map with in-map filters and node details.
            </p>
        </div>

        <div
            class="relative h-[calc(100vh-8rem)] min-h-190 w-full overflow-hidden rounded-xl border bg-background"
        >
            <VueFlow
                :nodes="flowNodes"
                :edges="flowEdges"
                :min-zoom="0.35"
                :max-zoom="2"
                :default-viewport="{ x: -120, y: 40, zoom: 0.95 }"
                class="h-full w-full"
                @node-click="onNodeClick"
            >
                <Background
                    :variant="BackgroundVariant.Lines"
                    :gap="24"
                    :size="1"
                    pattern-color="#d1d5db"
                />
                <Controls position="bottom-left" />
            </VueFlow>

            <div
                class="pointer-events-none absolute right-4 top-4 z-20 w-85 space-y-3"
            >
                <div
                    class="pointer-events-auto rounded-lg border bg-background/95 p-3 shadow-lg backdrop-blur-sm"
                >
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-sm font-semibold">Filters</h2>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="clearFilters"
                        >
                            <Icon
                                icon="lucide:refresh-ccw"
                                class="mr-1 h-3.5 w-3.5"
                            />
                            Reset
                        </Button>
                    </div>

                    <div class="space-y-2">
                        <div class="relative">
                            <Icon
                                icon="lucide:search"
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search PR/SVP/fund..."
                                class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                        </div>

                        <select
                            v-model="selectedOffice"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Offices</option>
                            <option
                                v-for="office in offices"
                                :key="office.id"
                                :value="String(office.id)"
                            >
                                {{ office.name }}
                            </option>
                        </select>

                        <select
                            v-model="selectedFiscalYear"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Fiscal Years</option>
                            <option
                                v-for="(year, key) in fiscalYears"
                                :key="key"
                                :value="key"
                            >
                                {{ year }}
                            </option>
                        </select>

                        <select
                            v-model="selectedStatus"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option
                                v-for="option in statusOptions"
                                :key="option.value || 'all'"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                        <span
                            class="inline-flex items-center gap-1 text-muted-foreground"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-amber-500"
                            />
                            Pending
                        </span>
                        <span
                            class="inline-flex items-center gap-1 text-muted-foreground"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-blue-500"
                            />
                            Ongoing
                        </span>
                        <span
                            class="inline-flex items-center gap-1 text-muted-foreground"
                        >
                            <span
                                class="h-2.5 w-2.5 rounded-full bg-emerald-500"
                            />
                            Completed
                        </span>
                    </div>
                </div>

                <div
                    class="pointer-events-auto max-h-[46vh] overflow-auto rounded-lg border bg-background/95 p-3 shadow-lg backdrop-blur-sm"
                >
                    <h2 class="mb-2 text-sm font-semibold">Node Details</h2>

                    <div v-if="selectedNode" class="space-y-3">
                        <div class="space-y-1">
                            <p class="text-sm font-semibold">
                                {{ selectedNode.data?.label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ selectedNode.data?.category }}
                            </p>
                        </div>

                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize"
                            :class="statusBadgeClass"
                        >
                            {{ selectedNode.data?.status }}
                        </span>

                        <div
                            class="space-y-2 rounded-md border bg-muted/30 p-3"
                        >
                            <div
                                v-for="(
                                    [key, value], index
                                ) in selectedNodeEntries"
                                :key="`${key}-${index}`"
                                class="text-xs"
                            >
                                <span class="font-medium text-foreground">
                                    {{ key.replaceAll("_", " ") }}:
                                </span>
                                <span class="text-muted-foreground">
                                    {{ value }}
                                </span>
                            </div>
                        </div>

                        <Link
                            v-if="selectedNode.data?.href"
                            :href="selectedNode.data.href"
                        >
                            <Button class="w-full" variant="outline">
                                <Icon
                                    icon="lucide:external-link"
                                    class="mr-2 h-4 w-4"
                                />
                                Open Record
                            </Button>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="rounded-md border border-dashed p-3 text-sm text-muted-foreground"
                    >
                        Click a node to inspect details.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
