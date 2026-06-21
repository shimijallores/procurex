<script setup>
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { NativeSelect } from "@/components/ui/native-select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    batchNoas: Array,
    defaults: Object,
    formatCurrency: Function,
});

const emit = defineEmits([
    "submit",
    "toggleNoa",
    "selectAll",
    "deselectAll",
    "reset",
]);

const noaFormMap = computed(() => {
    const map = {};
    if (props.batchNoas && props.form?.noas) {
        props.batchNoas.forEach((noa) => {
            map[noa.id] = props.form.noas.find((n) => n.noa_id === noa.id);
        });
    }
    return map;
});

const selectedCount = computed(
    () => props.form?.noas?.filter((n) => n.selected).length ?? 0,
);

const totalSelectedAmount = computed(() => {
    if (!props.batchNoas || !props.form?.noas) return 0;
    const selectedIds = props.form.noas
        .filter((n) => n.selected)
        .map((n) => n.noa_id);
    return props.batchNoas
        .filter((n) => selectedIds.includes(n.id))
        .reduce((sum, n) => sum + (n.winner_amount || 0), 0);
});

const getNoaForm = (noaId) => noaFormMap.value[noaId] ?? null;

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
        if (n < 100)
            return `${tens[Math.floor(n / 10)]}${n % 10 ? ` ${ones[n % 10]}` : ""}`;
        if (n < 1000)
            return `${ones[Math.floor(n / 100)]} hundred${n % 100 ? ` ${convert(n % 100)}` : ""}`;
        if (n < 1000000)
            return `${convert(Math.floor(n / 1000))} thousand${n % 1000 ? ` ${convert(n % 1000)}` : ""}`;
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
    if (cents > 0)
        return `${wholeWords} Pesos and ${String(cents).padStart(2, "0")}/100`;
    return `${wholeWords} Pesos Only`;
};

const computeAmount = (items) =>
    (items || []).reduce((sum, item) => sum + Number(item.amount || 0), 0);
</script>

<template>
    <form @submit.prevent="emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:layers" class="h-4 w-4 text-primary" />
                    Batch Purchase Orders
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ selectedCount }} of
                        {{ batchNoas?.length ?? 0 }} NOA(s) selected
                    </p>
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="emit('selectAll')"
                        >
                            Select All
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="emit('deselectAll')"
                        >
                            Deselect All
                        </Button>
                    </div>
                </div>

                <p v-if="form.errors?.noas" class="text-xs text-destructive">
                    {{ form.errors.noas }}
                </p>
            </CardContent>
        </Card>

        <div v-for="noa in batchNoas" :key="noa.id" class="space-y-4">
            <Card
                class="border-l-4 transition-colors"
                :class="
                    getNoaForm(noa.id)?.selected
                        ? 'border-l-primary'
                        : 'border-l-muted opacity-60'
                "
            >
                <CardHeader class="flex flex-row items-center gap-3 pb-0">
                    <input
                        type="checkbox"
                        :checked="getNoaForm(noa.id)?.selected ?? false"
                        @change="emit('toggleNoa', noa.id)"
                        class="h-5 w-5 rounded border-gray-300 text-primary"
                    />
                    <div class="flex-1">
                        <CardTitle class="text-base">
                            {{ noa.noa_no }} — {{ noa.project_name }}
                        </CardTitle>
                    </div>
                    <p class="text-sm font-medium text-muted-foreground">
                        {{ props.formatCurrency(noa.winner_amount) }}
                    </p>
                </CardHeader>
                <CardContent class="space-y-4 pt-4">
                    <template v-if="getNoaForm(noa.id)">
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <div class="space-y-1">
                                    <Label>Supplier Name</Label>
                                    <p class="text-sm font-medium">
                                        {{ noa.supplier_name }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <Label>Address</Label>
                                    <p class="text-sm text-muted-foreground">
                                        {{ noa.supplier_address }}
                                    </p>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="space-y-1">
                                    <Label>PO No.</Label>
                                    <p class="text-sm font-medium">
                                        {{ noa.suggested_po_no }}
                                    </p>
                                </div>
                                <div class="space-y-1">
                                    <Label>PR No.</Label>
                                    <p class="text-sm text-muted-foreground">
                                        {{ noa.pr_no || "—" }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-start-2 space-y-2">
                                <div class="space-y-2">
                                    <Label for="po_date">PO Date</Label>
                                    <input
                                        :id="`po_date_${noa.id}`"
                                        v-model="getNoaForm(noa.id).po_date"
                                        type="date"
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    />
                                    <p
                                        v-if="
                                            form.errors?.[
                                                `noas.${form.noas.indexOf(getNoaForm(noa.id))}.po_date`
                                            ]
                                        "
                                        class="text-xs text-destructive"
                                    >
                                        {{
                                            form.errors[
                                                `noas.${form.noas.indexOf(getNoaForm(noa.id))}.po_date`
                                            ]
                                        }}
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <Label :for="`mode_${noa.id}`"
                                        >Mode of Procurement</Label
                                    >
                                    <NativeSelect
                                        :id="`mode_${noa.id}`"
                                        :model-value="
                                            getNoaForm(noa.id)
                                                .mode_of_procurement
                                        "
                                        @update:model-value="
                                            getNoaForm(
                                                noa.id,
                                            ).mode_of_procurement = $event
                                        "
                                        class="w-full"
                                    >
                                        <option value="Small Value">
                                            Small Value
                                        </option>
                                        <option value="Direct Contracting">
                                            Direct Contracting
                                        </option>
                                        <option value="Direct Acquisition">
                                            Direct Acquisition
                                        </option>
                                    </NativeSelect>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label :for="`delivery_${noa.id}`"
                                    >Date of Delivery</Label
                                >
                                <template v-if="noa.purpose_date_label">
                                    <input
                                        :id="`delivery_${noa.id}`"
                                        :value="getNoaForm(noa.id).delivery_term_days"
                                        readonly
                                        class="flex h-10 w-full cursor-not-allowed rounded-md border border-input bg-muted px-3 py-2 text-sm opacity-70"
                                    />
                                    <p class="text-xs text-muted-foreground">
                                        Based on purpose: {{ noa.purpose_date_label }}
                                    </p>
                                </template>
                                <template v-else>
                                    <select
                                        :id="`delivery_${noa.id}`"
                                        v-model.number="
                                            getNoaForm(noa.id).delivery_term_days
                                        "
                                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                    >
                                        <option :value="15">15 days</option>
                                        <option :value="30">30 days</option>
                                        <option :value="45">45 days</option>
                                        <option :value="60">60 days</option>
                                        <option :value="90">90 days</option>
                                    </select>
                                    <p class="text-xs text-muted-foreground">
                                        Editable in case of event date.
                                    </p>
                                </template>
                            </div>
                            <div class="space-y-2">
                                <Label :for="`payment_${noa.id}`"
                                    >Payment Term</Label
                                >
                                <input
                                    :id="`payment_${noa.id}`"
                                    v-model="getNoaForm(noa.id).payment_term"
                                    list="payment-options"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <Label :for="`pod_${noa.id}`"
                                    >Place of Delivery</Label
                                >
                                <input
                                    :id="`pod_${noa.id}`"
                                    v-model="
                                        getNoaForm(noa.id).place_of_delivery
                                    "
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                />
                            </div>
                        </div>

                        <div class="overflow-auto rounded-md border">
                            <table class="w-full text-sm">
                                <thead class="bg-muted/40">
                                    <tr>
                                        <th
                                            class="w-16 px-3 py-2 text-center font-medium"
                                        >
                                            Qty
                                        </th>
                                        <th
                                            class="w-20 px-3 py-2 text-left font-medium"
                                        >
                                            Unit
                                        </th>
                                        <th
                                            class="px-3 py-2 text-left font-medium"
                                        >
                                            Item Description
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-right font-medium"
                                        >
                                            Unit Cost
                                        </th>
                                        <th
                                            class="w-28 px-3 py-2 text-right font-medium"
                                        >
                                            Amount
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="(item, i) in noa.items"
                                        :key="i"
                                        class="border-t"
                                    >
                                        <td class="px-3 py-2 text-center">
                                            {{ item.quantity }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ item.unit || "—" }}
                                        </td>
                                        <td class="px-3 py-2">
                                            {{ item.item_name }}
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            {{
                                                props.formatCurrency(
                                                    item.unit_cost,
                                                )
                                            }}
                                        </td>
                                        <td
                                            class="px-3 py-2 text-right font-medium"
                                        >
                                            {{
                                                props.formatCurrency(
                                                    item.amount,
                                                )
                                            }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="grid gap-2 text-sm md:grid-cols-2">
                            <div class="space-y-1">
                                <Label>Total Amount</Label>
                                <p class="text-lg font-bold">
                                    {{
                                        props.formatCurrency(
                                            computeAmount(noa.items),
                                        )
                                    }}
                                </p>
                            </div>
                            <div class="space-y-1">
                                <Label>Total Amount in Words</Label>
                                <p class="text-sm italic text-muted-foreground">
                                    {{
                                        numberToWords(computeAmount(noa.items))
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <Label :for="`remarks_${noa.id}`">Remarks</Label>
                            <textarea
                                :id="`remarks_${noa.id}`"
                                v-model="getNoaForm(noa.id).remarks"
                                rows="2"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                        </div>
                    </template>
                </CardContent>
            </Card>
        </div>

        <datalist id="payment-options">
            <option value="upon 100% completion /delivery" />
            <option value="Progress billing" />
            <option value="Net 30 days" />
        </datalist>

        <div class="flex items-center justify-between">
            <p class="text-sm text-muted-foreground">
                Creating POs for
                <strong>{{ selectedCount }}</strong> NOA(s) — total
                <strong>{{ props.formatCurrency(totalSelectedAmount) }}</strong>
            </p>
            <div class="flex gap-2">
                <Button type="button" variant="outline" @click="emit('reset')">
                    Reset
                </Button>
                <Button
                    type="submit"
                    :disabled="form.processing || !selectedCount"
                >
                    <Icon
                        v-if="form.processing"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    Save &amp; Print
                </Button>
            </div>
        </div>
    </form>
</template>
