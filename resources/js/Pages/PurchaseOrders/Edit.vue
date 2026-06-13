<script setup>
import { computed, ref, watch } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Purchase Order", href: route("purchase-orders.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    purchaseOrder: Object,
    abcAmount: Number,
    defaults: Object,
});

const po = props.purchaseOrder;

const numberToWords = (num) => {
    const ones = ["", "one", "two", "three", "four", "five", "six", "seven", "eight", "nine", "ten", "eleven", "twelve", "thirteen", "fourteen", "fifteen", "sixteen", "seventeen", "eighteen", "nineteen"];
    const tens = ["", "", "twenty", "thirty", "forty", "fifty", "sixty", "seventy", "eighty", "ninety"];

    const convert = (n) => {
        if (n < 20) return ones[n];
        if (n < 100) return `${tens[Math.floor(n / 10)]}${n % 10 ? ` ${ones[n % 10]}` : ""}`;
        if (n < 1000) return `${ones[Math.floor(n / 100)]} hundred${n % 100 ? ` ${convert(n % 100)}` : ""}`;
        if (n < 1000000) return `${convert(Math.floor(n / 1000))} thousand${n % 1000 ? ` ${convert(n % 1000)}` : ""}`;
        return `${convert(Math.floor(n / 1000000))} million${n % 1000000 ? ` ${convert(n % 1000000)}` : ""}`;
    };

    const amount = Number(num || 0);
    if (amount <= 0) return "Zero Pesos Only";
    const whole = Math.floor(amount);
    const cents = Math.round((amount - whole) * 100);
    const wholeWords = convert(whole).split(" ").filter(Boolean).map((p) => p.charAt(0).toUpperCase() + p.slice(1)).join(" ");
    if (cents > 0) return `${wholeWords} Pesos and ${String(cents).padStart(2, "0")}/100 Only`;
    return `${wholeWords} Pesos Only`;
};

const form = useForm({
    po_date: po.po_date?.slice(0, 10) || "",
    mode_of_procurement: po.mode_of_procurement || "Small Value",
    place_of_delivery: po.place_of_delivery || "",
    delivery_term_days: po.delivery_term_days || 15,
    payment_term: po.payment_term || "",
    total_amount: Number(po.total_amount || 0),
    total_amount_words: po.total_amount_words || "",
    remarks: po.remarks || "",
    items: (po.items || []).map((item) => ({
        rfq_item_id: item.rfq_item_id,
        quantity_snapshot: item.quantity_snapshot,
        unit_cost_snapshot: item.unit_cost_snapshot,
        amount_snapshot: item.amount_snapshot,
        _name: item.rfq_item?.purchase_request_item?.item_name || "Unknown Item",
        _unit: item.rfq_item?.purchase_request_item?.unit || "",
    })),
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(value || 0);

const updateItemAmount = (index) => {
    const item = form.items[index];
    if (!item) return;
    item.amount_snapshot = Number(item.quantity_snapshot || 0) * Number(item.unit_cost_snapshot || 0);
    form.total_amount = form.items.reduce((sum, i) => sum + Number(i.amount_snapshot || 0), 0);
    form.total_amount_words = numberToWords(form.total_amount);
};

watch(
    () => form.items.map((i) => [i.quantity_snapshot, i.unit_cost_snapshot]),
    () => {
        form.items.forEach((_, i) => updateItemAmount(i));
    },
    { deep: true },
);

const submit = () => {
    form.put(route("purchase-orders.update", po.id));
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit Purchase Order</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ po.po_no }} — ABC: {{ formatCurrency(abcAmount) }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">PO Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="po_no">PO Number</Label>
                            <input
                                id="po_no"
                                :value="po.po_no"
                                readonly
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="po_date">PO Date</Label>
                            <input
                                id="po_date"
                                v-model="form.po_date"
                                type="date"
                                :placeholder="defaults?.suggested_po_date || ''"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p v-if="form.errors.po_date" class="text-xs text-destructive">
                                {{ form.errors.po_date }}
                            </p>
                            <p v-if="defaults?.suggested_po_date && !form.po_date" class="text-xs text-muted-foreground">
                                Suggested: {{ defaults.suggested_po_date }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="mode_of_procurement">Mode of Procurement</Label>
                            <select
                                id="mode_of_procurement"
                                v-model="form.mode_of_procurement"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="Small Value">Small Value</option>
                                <option value="Direct Contracting">Direct Contracting</option>
                                <option value="Direct Acquisition">Direct Acquisition</option>
                            </select>
                            <p v-if="form.errors.mode_of_procurement" class="text-xs text-destructive">
                                {{ form.errors.mode_of_procurement }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="place_of_delivery">Place of Delivery</Label>
                            <input
                                id="place_of_delivery"
                                v-model="form.place_of_delivery"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p v-if="form.errors.place_of_delivery" class="text-xs text-destructive">
                                {{ form.errors.place_of_delivery }}
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="delivery_term_days">Delivery Term (Days)</Label>
                            <input
                                id="delivery_term_days"
                                v-model.number="form.delivery_term_days"
                                type="number"
                                min="1"
                                max="60"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p v-if="form.errors.delivery_term_days" class="text-xs text-destructive">
                                {{ form.errors.delivery_term_days }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Suggested: {{ defaults?.suggested_delivery_days || 15 }} days
                                (ABC {{ abcAmount < 200000 ? 'below' : 'at or above' }} &#8369;200,000)
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="payment_term">Payment Term</Label>
                            <input
                                id="payment_term"
                                v-model="form.payment_term"
                                list="payment-options"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <datalist id="payment-options">
                                <option value="upon 100% completion /delivery" />
                                <option value="Progress billing" />
                                <option value="Net 30 days" />
                            </datalist>
                            <p v-if="form.errors.payment_term" class="text-xs text-destructive">
                                {{ form.errors.payment_term }}
                            </p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Purchase Order Items</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="overflow-auto">
                        <table class="w-full text-sm">
                            <thead class="border-b bg-muted/40">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium">Item</th>
                                    <th class="px-3 py-2 text-center font-medium" style="width:80px;">Qty</th>
                                    <th class="px-3 py-2 text-left font-medium" style="width:60px;">Unit</th>
                                    <th class="px-3 py-2 text-right font-medium" style="width:130px;">Unit Cost</th>
                                    <th class="px-3 py-2 text-right font-medium" style="width:130px;">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!form.items?.length" class="border-b">
                                    <td colspan="5" class="px-3 py-4 text-center text-muted-foreground">
                                        No items found.
                                    </td>
                                </tr>
                                <tr v-for="(item, index) in form.items" :key="item.rfq_item_id" class="border-b">
                                    <td class="px-3 py-2">{{ item._name }}</td>
                                    <td class="px-3 py-2 text-center">
                                        <input
                                            v-model.number="item.quantity_snapshot"
                                            type="number"
                                            min="1"
                                            class="h-8 w-20 rounded border border-input bg-background px-2 py-1 text-center text-sm"
                                            @input="updateItemAmount(index)"
                                        />
                                    </td>
                                    <td class="px-3 py-2">{{ item._unit || "—" }}</td>
                                    <td class="px-3 py-2 text-right">
                                        <input
                                            v-model.number="item.unit_cost_snapshot"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            class="h-8 w-28 rounded border border-input bg-background px-2 py-1 text-right text-sm"
                                            @input="updateItemAmount(index)"
                                        />
                                    </td>
                                    <td class="px-3 py-2 text-right font-medium">
                                        {{ formatCurrency(item.amount_snapshot) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-if="form.errors?.items" class="text-xs text-destructive">
                        {{ form.errors.items }}
                    </p>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="total_amount">Total Amount (PHP)</Label>
                            <input
                                id="total_amount"
                                :value="formatCurrency(form.total_amount)"
                                readonly
                                class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm font-medium"
                            />
                        </div>
                        <div class="space-y-2">
                            <Label for="total_amount_words">Total Amount in Words</Label>
                            <textarea
                                id="total_amount_words"
                                :value="form.total_amount_words"
                                rows="2"
                                readonly
                                class="w-full rounded-md border border-input bg-muted px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">Remarks</Label>
                        <textarea
                            id="remarks"
                            v-model="form.remarks"
                            rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p v-if="form.errors.remarks" class="text-xs text-destructive">
                            {{ form.errors.remarks }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('purchase-orders.show', po.id)">
                    <Button type="button" variant="outline">Cancel</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    <Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                    Update Purchase Order
                </Button>
            </div>
        </form>
    </div>
</template>
