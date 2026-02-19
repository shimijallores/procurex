<script setup>
import { ref, computed, watch } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useCalendarCheck } from "@/composables/useCalendarCheck";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleEmanatings: Array,
    commonPurposes: Array,
    isEdit: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["submit"]);

// Autocomplete UI state
const showPurposeSuggestions = ref(false);
const purposeInput = ref(null);

const purposeBlurTimer = ref(null);

const onPurposeBlur = () => {
    purposeBlurTimer.value = setTimeout(
        () => (showPurposeSuggestions.value = false),
        200,
    );
};

// Calendar check
const { checkDate, isChecking } = useCalendarCheck();
const prDateCheck = ref(null);
const saiDateCheck = ref(null);

const checkPRDate = async (date) => {
    if (!date) return;
    prDateCheck.value = await checkDate(date);
};

const checkSAIDate = async (date) => {
    if (!date) return;
    saiDateCheck.value = await checkDate(date);
};

// Derived: selected emanating object
const selectedEmanating = computed(() =>
    props.eligibleEmanatings?.find(
        (e) => String(e.id) === String(props.form.emanating_id),
    ),
);

// When emanating changes, auto-populate office/fund and build items list
watch(
    () => props.form.pr_date,
    (newDate) => checkPRDate(newDate),
);

watch(
    () => props.form.sai_date,
    (newDate) => checkSAIDate(newDate),
);

watch(
    () => props.form.emanating_id,
    (newId) => {
        if (!newId) return;
        const em = props.eligibleEmanatings?.find(
            (e) => String(e.id) === String(newId),
        );
        if (!em) return;

        props.form.office_id = em.project?.fund?.office?.id ?? "";
        props.form.fund_id = em.project?.fund?.id ?? "";

        // Build items from emanating items (pre-fill with canvassed prices)
        props.form.items = (em.emanating_items || []).map((item) => ({
            emanating_item_id: item.id,
            _name: item.ppmp_item?.name || "Unknown Item",
            _unit: item.unit || "",
            _original_qty: item.quantity,
            quantity: item.quantity,
            unit_cost:
                parseFloat(item.total_price || 0) / Math.max(item.quantity, 1),
            vat_applicable: false,
            vat_rate: 0.12,
            remarks: "",
        }));
    },
);

// Item line total (with or without VAT)
const getLineTotal = (item) => {
    const base = parseFloat(item.unit_cost || 0) * parseInt(item.quantity || 0);
    if (item.vat_applicable) {
        return base * (1 + parseFloat(item.vat_rate || 0.12));
    }
    return base;
};

const grandTotal = computed(() =>
    (props.form.items || []).reduce((sum, item) => sum + getLineTotal(item), 0),
);

const formatCurrency = (val) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(val || 0);

const selectPurpose = (p) => {
    props.form.purpose = p;
    showPurposeSuggestions.value = false;
};

const filteredPurposes = computed(() => {
    if (!props.form.purpose) return props.commonPurposes;
    const q = props.form.purpose.toLowerCase();
    return (props.commonPurposes || []).filter((p) =>
        p.toLowerCase().includes(q),
    );
});

// PR number control suggestion helper
const prNoPlaceholder = computed(() => {
    const y = new Date().getFullYear().toString().slice(-2);
    const m = String(new Date().getMonth() + 1).padStart(2, "0");
    return `${y}${m}-A  `; // two buffer spaces
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <!-- Emanating Selection -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source Emanating Request
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-2">
                    <Label for="emanating_id"
                        >Approved & Canvassed Emanating
                        <span class="text-destructive">*</span></Label
                    >
                    <select
                        id="emanating_id"
                        :value="form.emanating_id"
                        :disabled="isEdit"
                        @change="form.emanating_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:opacity-60"
                    >
                        <option value="">
                            — Select an emanating request —
                        </option>
                        <option
                            v-for="em in eligibleEmanatings"
                            :key="em.id"
                            :value="em.id"
                        >
                            {{ em.project?.fund?.office?.name }} —
                            {{ em.project?.name }} (FY {{ em.fiscal_year }})
                            {{ em.pr_no ? `· ${em.pr_no}` : "" }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.emanating_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.emanating_id }}
                    </p>
                </div>

                <!-- Auto-populated office/fund info -->
                <div
                    v-if="selectedEmanating"
                    class="mt-4 rounded-md bg-muted/40 border border-border p-3 text-sm space-y-1"
                >
                    <div class="flex gap-2">
                        <span class="text-muted-foreground w-20 shrink-0"
                            >Office:</span
                        >
                        <span class="font-medium">{{
                            selectedEmanating.project?.fund?.office?.name
                        }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-muted-foreground w-20 shrink-0"
                            >Fund:</span
                        >
                        <span class="font-medium">{{
                            selectedEmanating.project?.fund?.name
                        }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-muted-foreground w-20 shrink-0"
                            >Project:</span
                        >
                        <span class="font-medium">{{
                            selectedEmanating.project?.name
                        }}</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="text-muted-foreground w-20 shrink-0"
                            >Category:</span
                        >
                        <span class="font-medium">{{
                            selectedEmanating.ppmp_category?.name || "—"
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- PR Details -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
                    PR Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <!-- PR No. -->
                    <div class="space-y-2">
                        <Label for="pr_no">Control No. (PR No.)</Label>
                        <input
                            id="pr_no"
                            v-model="form.pr_no"
                            type="text"
                            :placeholder="prNoPlaceholder"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p class="text-xs text-muted-foreground">
                            Format:
                            <code class="font-mono">{{
                                prNoPlaceholder.trim()
                            }}</code>
                            — leave at least 2 buffer spaces
                        </p>
                        <p
                            v-if="form.errors?.pr_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.pr_no }}
                        </p>
                    </div>

                    <!-- PR Date -->
                    <div class="space-y-2">
                        <Label for="pr_date">
                            PR Date
                            <span class="text-destructive">*</span>
                            <Icon
                                v-if="isChecking && form.pr_date"
                                icon="lucide:loader-2"
                                class="ml-1 inline h-4 w-4 animate-spin text-muted-foreground"
                            />
                        </Label>
                        <input
                            id="pr_date"
                            v-model="form.pr_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p class="text-xs text-muted-foreground">
                            Date availability is checked against calendar events
                            and holidays.
                        </p>
                        <p
                            v-if="form.errors?.pr_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.pr_date }}
                        </p>
                        <!-- Calendar Warning -->
                        <p
                            v-if="prDateCheck && !prDateCheck.is_available"
                            class="mt-1 text-xs text-red-600"
                        >
                            Non-working day —
                            <strong>{{ prDateCheck.type }}:</strong>
                            {{ prDateCheck.name
                            }}<span v-if="prDateCheck.message">
                                ({{ prDateCheck.message }})</span
                            >
                        </p>
                    </div>

                    <!-- SAI No. -->
                    <div class="space-y-2">
                        <Label for="sai_no">SAI No.</Label>
                        <input
                            id="sai_no"
                            v-model="form.sai_no"
                            type="text"
                            placeholder="e.g. SAI-2026-001"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.sai_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.sai_no }}
                        </p>
                    </div>

                    <!-- SAI Date -->
                    <div class="space-y-2">
                        <Label for="sai_date">
                            SAI Date
                            <Icon
                                v-if="isChecking && form.sai_date"
                                icon="lucide:loader-2"
                                class="ml-1 inline h-4 w-4 animate-spin text-muted-foreground"
                            />
                        </Label>
                        <input
                            id="sai_date"
                            v-model="form.sai_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.sai_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.sai_date }}
                        </p>
                        <!-- Calendar Warning -->
                        <p
                            v-if="saiDateCheck && !saiDateCheck.is_available"
                            class="mt-1 text-xs text-red-600"
                        >
                            Non-working day —
                            <strong>{{ saiDateCheck.type }}:</strong>
                            {{ saiDateCheck.name
                            }}<span v-if="saiDateCheck.message">
                                ({{ saiDateCheck.message }})</span
                            >
                        </p>
                    </div>
                </div>

                <!-- Purpose (with autocomplete) -->
                <div class="space-y-2 relative">
                    <Label for="purpose"
                        >Purpose <span class="text-destructive">*</span></Label
                    >
                    <textarea
                        id="purpose"
                        ref="purposeInput"
                        v-model="form.purpose"
                        rows="3"
                        placeholder="Describe the purpose of this purchase request…"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        @focus="showPurposeSuggestions = true"
                        @blur="onPurposeBlur"
                    />
                    <!-- Suggestions dropdown -->
                    <div
                        v-if="showPurposeSuggestions && filteredPurposes.length"
                        class="absolute z-10 mt-1 w-full rounded-md border border-border bg-background shadow-lg"
                    >
                        <button
                            v-for="p in filteredPurposes"
                            :key="p"
                            type="button"
                            class="block w-full px-3 py-2 text-left text-sm hover:bg-muted transition-colors"
                            @mousedown.prevent="selectPurpose(p)"
                        >
                            {{ p }}
                        </button>
                    </div>
                    <p
                        v-if="form.errors?.purpose"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.purpose }}
                    </p>
                </div>

                <!-- Remarks -->
                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="2"
                        placeholder="Any additional remarks…"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p
                        v-if="form.errors?.remarks"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Items Table -->
        <Card v-if="form.items?.length">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:package" class="h-4 w-4 text-primary" />
                    Items
                    <span
                        class="ml-auto text-sm font-normal text-muted-foreground"
                    >
                        {{ form.items.length }} item(s)
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th
                                    class="h-10 px-3 text-left font-medium text-muted-foreground"
                                >
                                    #
                                </th>
                                <th
                                    class="h-10 px-3 text-left font-medium text-muted-foreground"
                                >
                                    Description
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground"
                                >
                                    Unit
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-24"
                                >
                                    Qty
                                </th>
                                <th
                                    class="h-10 px-3 text-right font-medium text-muted-foreground w-32"
                                >
                                    Unit Cost
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-20"
                                >
                                    VAT 12%
                                </th>
                                <th
                                    class="h-10 px-3 text-right font-medium text-muted-foreground w-32"
                                >
                                    Line Total
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="(item, idx) in form.items"
                                :key="item.emanating_item_id"
                                class="border-b"
                            >
                                <td
                                    class="p-3 text-muted-foreground text-xs align-middle"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="p-3 align-middle">
                                    <div class="font-medium">
                                        {{ item._name }}
                                    </div>
                                </td>
                                <td
                                    class="p-3 text-center align-middle text-muted-foreground"
                                >
                                    {{ item._unit }}
                                </td>
                                <td class="p-3 align-middle">
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        :min="1"
                                        :max="item._original_qty"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-center text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                    <p
                                        class="text-xs text-muted-foreground mt-0.5 text-center"
                                    >
                                        max {{ item._original_qty }}
                                    </p>
                                </td>
                                <td class="p-3 align-middle">
                                    <input
                                        v-model.number="item.unit_cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-right text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </td>
                                <td class="p-3 align-middle text-center">
                                    <label
                                        class="flex items-center justify-center gap-1.5 cursor-pointer"
                                    >
                                        <input
                                            v-model="item.vat_applicable"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <span
                                            class="text-xs text-muted-foreground"
                                            >12%</span
                                        >
                                    </label>
                                </td>
                                <td
                                    class="p-3 align-middle text-right font-semibold"
                                >
                                    {{ formatCurrency(getLineTotal(item)) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/30">
                                <td
                                    colspan="6"
                                    class="px-3 py-3 text-right font-semibold"
                                >
                                    Grand Total
                                </td>
                                <td
                                    class="px-3 py-3 text-right font-bold text-primary text-base"
                                >
                                    {{ formatCurrency(grandTotal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <p class="mt-2 text-xs text-muted-foreground">
                    <Icon icon="lucide:info" class="inline h-3 w-3 mr-1" />
                    You may reduce quantity per item if needed. VAT (12%) is
                    applied where checked.
                </p>
            </CardContent>
        </Card>

        <div
            v-else-if="form.emanating_id"
            class="rounded-md border border-dashed border-border p-6 text-center text-sm text-muted-foreground"
        >
            <Icon
                icon="lucide:package"
                class="mx-auto mb-2 h-6 w-6 opacity-40"
            />
            No items found in the selected emanating.
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <Button
                type="button"
                variant="outline"
                @click="router.get(route('purchase-requests.index'))"
            >
                Cancel
            </Button>
            <Button
                type="submit"
                :disabled="form.processing || isChecking || !form.emanating_id"
            >
                <Icon icon="lucide:save" class="mr-1.5 h-4 w-4" />
                {{
                    isEdit
                        ? "Update Purchase Request"
                        : "Create Purchase Request"
                }}
            </Button>
        </div>
    </form>
</template>
