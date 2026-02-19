<script setup>
import { ref, computed } from "vue";
import { Link, router } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import CanvasHeader from "@/components/canvasses/show/CanvasHeader.vue";
import CanvasTotalTracker from "@/components/canvasses/show/CanvasTotalTracker.vue";
import CanvasItemsTable from "@/components/canvasses/show/CanvasItemsTable.vue";
import CanvasMasterListSidebar from "@/components/canvasses/show/CanvasMasterListSidebar.vue";
import ReturnReasonBanner from "@/components/canvasses/show/ReturnReasonBanner.vue";
import ReturnCanvasDialog from "@/components/canvasses/show/ReturnCanvasDialog.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Canvassing", href: route("canvasses.index") },
                    { label: "Canvas #" + (page.props.canvas?.id || "") },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    canvas: Object,
    masterListCategories: Array,
    returnReasons: Array,
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatCurrency = (value) => {
    if (value === null || value === undefined) return "—";
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value);
};

const statusVariant = (s) =>
    ({
        pending: "secondary",
        completed: "default",
        returned: "destructive",
    })[s] ?? "outline";

// ─── Computed canvas state ────────────────────────────────────────────────────
const isPending = computed(() => props.canvas.status === "pending");

const allRowsPriced = computed(() =>
    props.canvas.canvas_items?.every((ci) => ci.computed_price !== null),
);

const canvasTotal = computed(
    () =>
        props.canvas.canvas_items?.reduce(
            (sum, ci) => sum + parseFloat(ci.computed_price ?? 0),
            0,
        ) ?? 0,
);

// ─── Row editor state (for editing one emanating item at a time) ──────────────
const activeRowId = ref(null);
const itemSearch = ref("");
const categoryFilter = ref("");

// Local selections for the currently editing row
// Shape: { [masterListItemId]: { master_list_item_id, quantity, unit_price } }
const localSelections = ref({});
const localComputedPrice = ref(null);

const openRowEditor = (canvasItem) => {
    activeRowId.value = canvasItem.id;

    // Pre-populate from existing selections
    const initial = {};
    canvasItem.selections?.forEach((sel) => {
        initial[sel.master_list_item_id] = {
            master_list_item_id: sel.master_list_item_id,
            quantity: parseFloat(sel.quantity),
            unit_price: parseFloat(sel.unit_price),
        };
    });
    localSelections.value = initial;
    localComputedPrice.value = parseFloat(canvasItem.computed_price) || null;
    itemSearch.value = "";
    categoryFilter.value = "";
};

const closeRowEditor = () => {
    activeRowId.value = null;
    localSelections.value = {};
    localComputedPrice.value = null;
};

// Filtered master list categories
const filteredCategories = computed(() => {
    if (!props.masterListCategories) return [];
    return props.masterListCategories
        .map((cat) => ({
            ...cat,
            masterListItems: cat.master_list_items.filter((item) => {
                const matchesSearch =
                    !itemSearch.value ||
                    item.item_name
                        .toLowerCase()
                        .includes(itemSearch.value.toLowerCase()) ||
                    (item.search_key ?? "")
                        .toLowerCase()
                        .includes(itemSearch.value.toLowerCase());
                return matchesSearch;
            }),
        }))
        .filter(
            (cat) =>
                (!categoryFilter.value || cat.id == categoryFilter.value) &&
                cat.masterListItems.length > 0,
        );
});

const isItemSelected = (itemId) => !!localSelections.value[itemId];

const toggleItem = (item) => {
    if (localSelections.value[item.id]) {
        const updated = { ...localSelections.value };
        delete updated[item.id];
        localSelections.value = updated;
    } else {
        localSelections.value = {
            ...localSelections.value,
            [item.id]: {
                master_list_item_id: item.id,
                quantity: 1,
                unit_price: parseFloat(item.default_unit_price ?? 0),
            },
        };
    }
};

const updateSelection = (payload) => {
    const { itemId, field, value } = payload;
    if (!localSelections.value[itemId]) return;
    localSelections.value = {
        ...localSelections.value,
        [itemId]: {
            ...localSelections.value[itemId],
            [field]: parseFloat(value) || 0,
        },
    };
};

// Calculate subtotal (sum of selected items)
const itemsSubtotal = computed(() =>
    Object.values(localSelections.value).reduce(
        (sum, s) => sum + (s.quantity ?? 0) * (s.unit_price ?? 0),
        0,
    ),
);

const savingRow = ref(false);

const saveRowPrice = () => {
    const computedPrice = parseFloat(localComputedPrice.value);
    if (activeRowId.value === null || !computedPrice || computedPrice === 0)
        return;
    savingRow.value = true;

    const selections = Object.values(localSelections.value);

    router.post(
        route("canvasses.items.selections", {
            canvas: props.canvas.id,
            canvas_item: activeRowId.value,
        }),
        {
            selections,
            computed_price: computedPrice,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                closeRowEditor();
                savingRow.value = false;
            },
            onError: () => {
                savingRow.value = false;
            },
        },
    );
};

// ─── Complete canvas ──────────────────────────────────────────────────────────
const completing = ref(false);

const completeCanvas = () => {
    completing.value = true;
    router.post(
        route("canvasses.complete", props.canvas.id),
        {},
        {
            onFinish: () => (completing.value = false),
        },
    );
};

// ─── Return canvas ────────────────────────────────────────────────────────────
const returnDialogOpen = ref(false);
const returnReason = ref("");
const returningCanvas = ref(false);

const submitReturn = () => {
    if (!returnReason.value) return;
    returningCanvas.value = true;
    router.post(
        route("canvasses.return", props.canvas.id),
        { return_reason: returnReason.value },
        {
            onFinish: () => {
                returningCanvas.value = false;
                returnDialogOpen.value = false;
            },
        },
    );
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <CanvasHeader
            :canvas="canvas"
            :status-variant="statusVariant"
            :is-pending="isPending"
            :all-rows-priced="allRowsPriced"
            :completing="completing"
            :format-currency="formatCurrency"
            :on-complete-canvas="completeCanvas"
            :on-return-click="() => (returnDialogOpen = true)"
        />

        <!-- Return reason banner -->
        <ReturnReasonBanner
            v-if="canvas.status === 'returned'"
            :canvas="canvas"
        />

        <!-- Total tracker (pending only) -->
        <CanvasTotalTracker
            v-if="isPending"
            :canvas-total="canvasTotal"
            :format-currency="formatCurrency"
        />

        <!-- Main Layout: Items + Master List Sidebar -->
        <div class="grid gap-6 lg:grid-cols-2 w-full">
            <!-- Emanating Items (left) -->
            <CanvasItemsTable
                :canvas="canvas"
                :is-pending="isPending"
                :active-row-id="activeRowId"
                :format-currency="formatCurrency"
                :on-open-row-editor="openRowEditor"
            />

            <!-- Master List Sidebar (right: 1 col) -->
            <CanvasMasterListSidebar
                :active-row-id="activeRowId"
                :canvas="canvas"
                :master-list-categories="masterListCategories"
                :item-search="itemSearch"
                :category-filter="categoryFilter"
                :local-selections="localSelections"
                :local-computed-price="localComputedPrice"
                :filtered-categories="filteredCategories"
                :is-item-selected="isItemSelected"
                :items-subtotal="itemsSubtotal"
                :saving-row="savingRow"
                :format-currency="formatCurrency"
                @update:item-search="itemSearch = $event"
                @update:category-filter="categoryFilter = $event"
                @toggle-item="toggleItem"
                @update-selection="updateSelection"
                @update:local-computed-price="localComputedPrice = $event"
                @save-row-price="saveRowPrice"
                @close-row-editor="closeRowEditor"
            />
        </div>

        <!-- Return Dialog -->
        <ReturnCanvasDialog
            :open="returnDialogOpen"
            :return-reasons="returnReasons"
            :return-reason="returnReason"
            :returning-canvas="returningCanvas"
            @update:open="returnDialogOpen = $event"
            @update:return-reason="returnReason = $event"
            @submit-return="submitReturn"
        />
    </div>
</template>
