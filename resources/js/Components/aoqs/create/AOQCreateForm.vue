<script setup>
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleRfqs: Array,
});

const emit = defineEmits(["submit"]);

const selectedRfq = computed(() =>
    props.eligibleRfqs?.find(
        (rfq) => String(rfq.id) === String(props.form.rfq_id),
    ),
);

const computedSuppliers = computed(() => {
    if (!selectedRfq.value) return [];

    const supplierTotals = [];
    for (const supplierEntry of selectedRfq.value.suppliers || []) {
        if (!supplierEntry.submitted_at) continue;

        let total = 0;
        let hasPrice = false;

        for (const supplierItem of supplierEntry.supplier_items || []) {
            if (
                supplierItem.unit_price === null ||
                supplierItem.unit_price === undefined ||
                supplierItem.unit_price === ""
            )
                continue;
            const quantity = Number(
                supplierItem.rfq_item?.purchase_request_item?.quantity || 0,
            );
            total += quantity * Number(supplierItem.unit_price || 0);
            hasPrice = true;
        }

        if (!hasPrice) continue;

        supplierTotals.push({
            supplier_id: supplierEntry.supplier_id,
            supplier_name: supplierEntry.supplier?.name,
            total_amount: Number(total.toFixed(2)),
        });
    }

    supplierTotals.sort((a, b) => a.total_amount - b.total_amount);
    return supplierTotals;
});

const calculationMode = computed(() => {
    if (computedSuppliers.value.length <= 1) return "single_calculated";
    return "lowest_calculated";
});

const winner = computed(() => computedSuppliers.value[0] || null);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
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
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label for="rfq_id"
                        >RFQ with Submitted Supplier Quotations</Label
                    >
                    <select
                        id="rfq_id"
                        :value="form.rfq_id"
                        @change="form.rfq_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p class="text-xs text-muted-foreground">
                        Date is auto-filled and can be edited.
                    </p>
                    <p
                        v-if="form.errors?.aoq_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.aoq_date }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedRfq">
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:calculator"
                        class="h-4 w-4 text-primary"
                    />
                    Calculation Preview
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3 text-sm">
                <div>
                    <span class="text-muted-foreground">Mode:</span>
                    <span class="font-medium ml-2">
                        {{
                            calculationMode === "single_calculated"
                                ? "Single Calculated"
                                : "Lowest Calculated"
                        }}
                    </span>
                </div>

                <div
                    v-for="supplier in computedSuppliers"
                    :key="supplier.supplier_id"
                    class="flex items-center justify-between rounded-md border px-3 py-2"
                >
                    <span>{{ supplier.supplier_name }}</span>
                    <span class="font-medium">{{
                        formatCurrency(supplier.total_amount)
                    }}</span>
                </div>

                <div
                    class="rounded-md bg-muted/40 border border-border px-3 py-2"
                    v-if="winner"
                >
                    <p class="text-muted-foreground">Winner Supplier</p>
                    <p class="font-semibold">{{ winner.supplier_name }}</p>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()">
                Reset
            </Button>
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
