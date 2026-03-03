<script setup>
import { computed, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleRfqs: Array,
    suppliers: Array,
});

const emit = defineEmits(["submit"]);

const selectedRfq = computed(() =>
    props.eligibleRfqs?.find(
        (rfq) => String(rfq.id) === String(props.form.rfq_id),
    ),
);

const selectedItems = computed(() => selectedRfq.value?.items || []);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

const createQuotation = () => ({
    supplier_id: "",
    submitted_at: "",
    remarks: "",
    unit_prices: {},
});

const normalizeQuotationUnitPrices = (quotation, itemIds) => {
    const unitPrices = {};

    for (const itemId of itemIds) {
        unitPrices[itemId] = quotation.unit_prices?.[itemId] ?? "";
    }

    return {
        ...quotation,
        unit_prices: unitPrices,
    };
};

watch(
    () => props.form.rfq_id,
    (newRfqId, oldRfqId) => {
        if (!newRfqId || newRfqId === oldRfqId) {
            return;
        }

        if (!props.form.quotations?.length) {
            props.form.quotations = [createQuotation()];
        }

        const itemIds = selectedItems.value.map((item) => item.id);

        props.form.quotations = props.form.quotations.map((quotation) => {
            return normalizeQuotationUnitPrices(quotation, itemIds);
        });
    },
    { immediate: true },
);

const addQuotation = () => {
    const itemIds = selectedItems.value.map((item) => item.id);
    const quotation = createQuotation();

    for (const itemId of itemIds) {
        quotation.unit_prices[itemId] = "";
    }

    props.form.quotations = [...(props.form.quotations || []), quotation];
};

const removeQuotation = (index) => {
    props.form.quotations = props.form.quotations.filter((_, i) => i !== index);
};

const supplierName = (supplierId) => {
    const supplier = props.suppliers?.find(
        (item) => String(item.id) === String(supplierId),
    );

    return supplier?.name || "Supplier";
};

const quotationTotal = (quotation) => {
    let total = 0;

    for (const item of selectedItems.value) {
        const unitPrice = Number(quotation.unit_prices?.[item.id] || 0);
        const quantity = Number(item.quantity || 0);
        total += unitPrice * quantity;
    }

    return total;
};

const supplierCountWithPrices = computed(() => {
    return (props.form.quotations || []).filter((quotation) => {
        return selectedItems.value.some((item) => {
            const rawPrice = quotation.unit_prices?.[item.id];
            return (
                rawPrice !== null && rawPrice !== undefined && rawPrice !== ""
            );
        });
    }).length;
});

const calculationMessage = computed(() => {
    const count = supplierCountWithPrices.value;

    if (count >= 2) {
        return "lowest calculated";
    }

    if (count === 1) {
        return "single calculated";
    }

    return "";
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source RFQ
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="rfq_id">RFQ</Label>
                    <select
                        id="rfq_id"
                        :value="form.rfq_id"
                        @change="form.rfq_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select RFQ —</option>
                        <option
                            v-for="rfq in eligibleRfqs"
                            :key="rfq.id"
                            :value="rfq.id"
                        >
                            {{ rfq.svp_no }} — {{ rfq.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.rfq_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.rfq_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="aoq_date">AOQ Date</Label>
                    <input
                        id="aoq_date"
                        v-model="form.aoq_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.aoq_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.aoq_date }}
                    </p>
                </div>

                <div
                    v-if="selectedRfq"
                    class="sm:col-span-2 rounded-md border border-border bg-muted/40 p-3 text-sm space-y-1"
                >
                    <div>
                        <span class="text-muted-foreground">Project:</span>
                        <span class="font-medium">{{
                            selectedRfq.project_name
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Office:</span>
                        <span class="font-medium">{{
                            selectedRfq.purchase_request?.office?.name || "—"
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">ABC:</span>
                        <span class="font-medium">{{
                            formatCurrency(selectedRfq.abc_amount)
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:building-2"
                        class="h-4 w-4 text-primary"
                    />
                    Supplier Matrix Setup
                </CardTitle>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    :disabled="!selectedRfq"
                    @click="addQuotation"
                >
                    <Icon icon="lucide:plus" class="mr-1 h-3.5 w-3.5" />
                    Add Supplier Column
                </Button>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="!selectedRfq" class="text-sm text-muted-foreground">
                    Select an RFQ first to build the quotation matrix.
                </p>

                <div
                    v-if="selectedRfq && form.quotations?.length"
                    class="space-y-3"
                >
                    <div
                        v-for="(quotation, quotationIndex) in form.quotations"
                        :key="quotationIndex"
                        class="grid gap-3 rounded-md border border-border p-3 lg:grid-cols-12"
                    >
                        <div class="space-y-2 lg:col-span-3">
                            <Label>Supplier</Label>
                            <select
                                v-model="quotation.supplier_id"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                            >
                                <option value="">— Select Supplier —</option>
                                <option
                                    v-for="supplier in suppliers"
                                    :key="supplier.id"
                                    :value="supplier.id"
                                >
                                    {{ supplier.name }}
                                </option>
                            </select>
                        </div>

                        <div class="space-y-2 lg:col-span-3">
                            <Label>Submitted Date</Label>
                            <input
                                v-model="quotation.submitted_at"
                                type="date"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                            />
                        </div>

                        <div class="space-y-2 lg:col-span-5">
                            <Label>Remarks</Label>
                            <input
                                v-model="quotation.remarks"
                                type="text"
                                class="flex h-9 w-full rounded-md border border-input bg-background px-2 py-1 text-sm"
                            />
                        </div>

                        <div class="flex items-end justify-end lg:col-span-1">
                            <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                @click="removeQuotation(quotationIndex)"
                            >
                                <Icon
                                    icon="lucide:trash-2"
                                    class="h-3.5 w-3.5"
                                />
                            </Button>
                        </div>
                    </div>
                </div>

                <p
                    v-if="form.errors?.quotations"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.quotations }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:grid-2x2" class="h-4 w-4 text-primary" />
                    Quotation Matrix
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <p v-if="!selectedRfq" class="text-sm text-muted-foreground">
                    Matrix preview is available once an RFQ is selected.
                </p>

                <div
                    v-if="selectedRfq && form.quotations?.length"
                    class="relative w-full overflow-auto rounded-md border"
                >
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-3 py-2 text-left">Item</th>
                                <th class="px-3 py-2 text-center">Qty</th>
                                <th class="px-3 py-2 text-center">Unit</th>
                                <th
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`header-${quotationIndex}`"
                                    class="px-3 py-2 text-right min-w-[180px]"
                                >
                                    {{ supplierName(quotation.supplier_id) }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="item in selectedItems"
                                :key="item.id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">
                                    {{
                                        item.item_name ||
                                        item.purchase_request_item?.item_name ||
                                        "—"
                                    }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.quantity || 0 }}
                                </td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.unit || "—" }}
                                </td>
                                <td
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`cell-${quotationIndex}-${item.id}`"
                                    class="px-3 py-2"
                                >
                                    <input
                                        v-model="quotation.unit_prices[item.id]"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="flex h-8 w-full rounded-md border border-input bg-background px-2 py-1 text-right"
                                        placeholder="Unit price"
                                    />
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-muted/20 font-semibold">
                                <td colspan="3" class="px-3 py-2 text-right">
                                    Supplier Totals
                                </td>
                                <td
                                    v-for="(
                                        quotation, quotationIndex
                                    ) in form.quotations"
                                    :key="`total-${quotationIndex}`"
                                    class="px-3 py-2 text-right"
                                >
                                    {{
                                        formatCurrency(
                                            quotationTotal(quotation),
                                        )
                                    }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div
                    v-if="calculationMessage"
                    class="rounded-md border border-border bg-muted/40 p-3 text-sm"
                >
                    Calculation Message:
                    <span class="font-semibold">{{ calculationMessage }}</span>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()"
                >Reset</Button
            >
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create AOQ
            </Button>
        </div>
    </form>
</template>
