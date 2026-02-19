<script setup>
import { computed, ref, watch } from "vue";
import axios from "axios";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleNoas: Array,
    defaults: Object,
});

defineEmits(["submit"]);

const amountWordsTouched = ref(false);
const poNoTouched = ref(false);

const selectedNoa = computed(() =>
    props.eligibleNoas?.find(
        (noa) => String(noa.id) === String(props.form.noa_id),
    ),
);

const winnerSupplierQuote = computed(() => {
    const winnerSupplierId =
        selectedNoa.value?.bac_resolution?.aoq?.winner_supplier_id;
    return selectedNoa.value?.bac_resolution?.aoq?.rfq?.suppliers?.find(
        (supplier) => String(supplier.supplier_id) === String(winnerSupplierId),
    );
});

const numberToWords = (num) => {
    const ones = [
        "",
        "one",
        "two",
        "three",
        "four",
        "five",
        "six",
        "seven",
        "eight",
        "nine",
        "ten",
        "eleven",
        "twelve",
        "thirteen",
        "fourteen",
        "fifteen",
        "sixteen",
        "seventeen",
        "eighteen",
        "nineteen",
    ];
    const tens = [
        "",
        "",
        "twenty",
        "thirty",
        "forty",
        "fifty",
        "sixty",
        "seventy",
        "eighty",
        "ninety",
    ];

    const convert = (n) => {
        if (n < 20) return ones[n];
        if (n < 100) {
            return `${tens[Math.floor(n / 10)]}${n % 10 ? ` ${ones[n % 10]}` : ""}`;
        }
        if (n < 1000) {
            return `${ones[Math.floor(n / 100)]} hundred${n % 100 ? ` ${convert(n % 100)}` : ""}`;
        }
        if (n < 1000000) {
            return `${convert(Math.floor(n / 1000))} thousand${n % 1000 ? ` ${convert(n % 1000)}` : ""}`;
        }
        return `${convert(Math.floor(n / 1000000))} million${n % 1000000 ? ` ${convert(n % 1000000)}` : ""}`;
    };

    const amount = Number(num || 0);
    if (amount <= 0) return "Zero Pesos Only";

    const whole = Math.floor(amount);
    const cents = Math.round((amount - whole) * 100);

    const wholeWords = convert(whole)
        .split(" ")
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(" ");

    if (cents > 0) {
        return `${wholeWords} Pesos and ${String(cents).padStart(2, "0")}/100 Only`;
    }

    return `${wholeWords} Pesos Only`;
};

const refreshPoNo = async () => {
    if (!props.form.po_date) return;

    try {
        const { data } = await axios.post(
            route("purchase-orders.suggest-po-no"),
            {
                po_date: props.form.po_date,
            },
        );

        if (!poNoTouched.value) {
            props.form.po_no = data.po_no;
        }
    } catch {
        // noop
    }
};

watch(
    () => props.form.noa_id,
    async () => {
        const noa = selectedNoa.value;
        if (!noa) return;

        if (!props.form.mode_of_procurement) {
            props.form.mode_of_procurement =
                props.defaults?.mode_of_procurement || "Small Value";
        }

        if (!props.form.payment_term) {
            props.form.payment_term =
                props.defaults?.payment_term ||
                "upon 100% completion /delivery";
        }

        const officeName =
            noa.bac_resolution?.aoq?.rfq?.purchase_request?.office?.name;
        if (!props.form.place_of_delivery && officeName) {
            props.form.place_of_delivery = officeName;
        }

        const rfqItems = noa.bac_resolution?.aoq?.rfq?.items || [];
        const supplierItems = winnerSupplierQuote.value?.supplier_items || [];

        props.form.items = rfqItems.map((rfqItem) => {
            const matchedSupplierItem = supplierItems.find(
                (entry) => String(entry.rfq_item_id) === String(rfqItem.id),
            );

            const quantity = Number(
                rfqItem.purchase_request_item?.quantity || 0,
            );
            const unitCost = Number(matchedSupplierItem?.unit_price || 0);

            return {
                rfq_item_id: rfqItem.id,
                quantity_snapshot: quantity,
                unit_cost_snapshot: unitCost,
                amount_snapshot: quantity * unitCost,
                _name:
                    rfqItem.purchase_request_item?.item_name || "Unknown Item",
                _unit: rfqItem.purchase_request_item?.unit || "",
            };
        });

        props.form.total_amount = props.form.items.reduce(
            (sum, item) => sum + Number(item.amount_snapshot || 0),
            0,
        );

        if (!amountWordsTouched.value) {
            props.form.total_amount_words = numberToWords(
                props.form.total_amount,
            );
        }

        await refreshPoNo();
    },
);

watch(
    () => props.form.po_date,
    async () => {
        await refreshPoNo();
    },
);

watch(
    () => props.form.items,
    (items) => {
        props.form.total_amount = (items || []).reduce(
            (sum, item) => sum + Number(item.amount_snapshot || 0),
            0,
        );

        if (!amountWordsTouched.value) {
            props.form.total_amount_words = numberToWords(
                props.form.total_amount,
            );
        }
    },
    { deep: true },
);

const updateLineAmount = (item) => {
    item.amount_snapshot =
        Number(item.quantity_snapshot || 0) *
        Number(item.unit_cost_snapshot || 0);
};

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
                    Source Notice of Award
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="noa_id">Notice of Award</Label>
                    <select
                        id="noa_id"
                        :value="form.noa_id"
                        @change="form.noa_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select NOA —</option>
                        <option
                            v-for="noa in eligibleNoas"
                            :key="noa.id"
                            :value="noa.id"
                        >
                            {{ noa.noa_no }} —
                            {{ noa.bac_resolution?.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.noa_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.noa_id }}
                    </p>
                </div>

                <div
                    v-if="selectedNoa"
                    class="grid gap-3 text-sm md:grid-cols-2"
                >
                    <div>
                        <p class="text-muted-foreground">Project</p>
                        <p class="font-medium">
                            {{
                                selectedNoa.bac_resolution?.project_name || "—"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Winner Supplier</p>
                        <p class="font-medium">
                            {{
                                selectedNoa.bac_resolution?.aoq?.winner_supplier
                                    ?.name || "—"
                            }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Purchase Order Details</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="po_no">PO No.</Label>
                    <input
                        id="po_no"
                        v-model="form.po_no"
                        placeholder="mmyy-####"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        @input="poNoTouched = true"
                    />
                    <p
                        v-if="form.errors?.po_no"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.po_no }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="po_date">PO Date</Label>
                    <input
                        id="po_date"
                        v-model="form.po_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.po_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.po_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="mode_of_procurement">Mode of Procurement</Label>
                    <input
                        id="mode_of_procurement"
                        v-model="form.mode_of_procurement"
                        list="mode-options"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <datalist id="mode-options">
                        <option value="Small Value" />
                        <option value="Public Bidding" />
                        <option value="Shopping" />
                        <option value="Direct Contracting" />
                    </datalist>
                    <p
                        v-if="form.errors?.mode_of_procurement"
                        class="text-xs text-destructive"
                    >
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
                    <p
                        v-if="form.errors?.place_of_delivery"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.place_of_delivery }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="delivery_term_days">Delivery Term (days)</Label>
                    <select
                        id="delivery_term_days"
                        v-model="form.delivery_term_days"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option :value="15">15</option>
                        <option :value="30">30</option>
                    </select>
                    <p
                        v-if="form.errors?.delivery_term_days"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.delivery_term_days }}
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
                    <p
                        v-if="form.errors?.payment_term"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.payment_term }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">PO Line Items</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium">
                                    Item
                                </th>
                                <th class="px-3 py-2 text-center font-medium">
                                    Qty
                                </th>
                                <th class="px-3 py-2 text-left font-medium">
                                    Unit
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Unit Cost
                                </th>
                                <th class="px-3 py-2 text-right font-medium">
                                    Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!form.items?.length" class="border-b">
                                <td
                                    colspan="5"
                                    class="px-3 py-4 text-center text-muted-foreground"
                                >
                                    Select an NOA to load awarded item
                                    snapshots.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, index) in form.items"
                                :key="item.rfq_item_id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">{{ item._name }}</td>
                                <td class="px-3 py-2 text-center">
                                    <input
                                        v-model.number="item.quantity_snapshot"
                                        type="number"
                                        min="1"
                                        class="h-9 w-24 rounded-md border border-input bg-background px-2 text-center"
                                        @input="updateLineAmount(item)"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    {{ item._unit || "—" }}
                                </td>
                                <td class="px-3 py-2 text-right">
                                    <input
                                        v-model.number="item.unit_cost_snapshot"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-9 w-32 rounded-md border border-input bg-background px-2 text-right"
                                        @input="updateLineAmount(item)"
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
                        <Label for="total_amount">Total Amount</Label>
                        <input
                            id="total_amount"
                            v-model.number="form.total_amount"
                            type="number"
                            min="0"
                            step="0.01"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.total_amount"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.total_amount }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="total_amount_words">Amount in Words</Label>
                        <textarea
                            id="total_amount_words"
                            v-model="form.total_amount_words"
                            rows="2"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @input="amountWordsTouched = true"
                        />
                        <p
                            v-if="form.errors?.total_amount_words"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.total_amount_words }}
                        </p>
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
                    <p
                        v-if="form.errors?.remarks"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
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
                Create PO
            </Button>
        </div>
    </form>
</template>
