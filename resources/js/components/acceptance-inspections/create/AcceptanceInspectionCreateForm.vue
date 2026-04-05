<script setup>
import { computed, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    purchaseOrders: Array,
});

defineEmits(["submit"]);
const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const selectedPurchaseOrder = computed(() =>
    props.purchaseOrders?.find(
        (po) => String(po.id) === String(props.form.purchase_order_id),
    ),
);

const poItems = computed(() => selectedPurchaseOrder.value?.items || []);

watch(
    () => props.form.acceptance_date_received,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "acceptance_date_received",
            statusKey: "acceptance_date_received",
            clearDate: () => {
                props.form.acceptance_date_received = "";
            },
        });
    },
);

watch(
    () => props.form.inspection_date_inspected,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "inspection_date_inspected",
            statusKey: "inspection_date_inspected",
            clearDate: () => {
                props.form.inspection_date_inspected = "";
            },
        });
    },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Source Purchase Order</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="purchase_order_id">Purchase Order</Label>
                    <select
                        id="purchase_order_id"
                        :value="form.purchase_order_id"
                        @change="form.purchase_order_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Purchase Order —</option>
                        <option
                            v-for="po in purchaseOrders"
                            :key="po.id"
                            :value="po.id"
                        >
                            {{ po.po_no }} —
                            {{ po.noa?.bac_resolution?.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.purchase_order_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.purchase_order_id }}
                    </p>
                </div>

                <div
                    v-if="selectedPurchaseOrder"
                    class="grid gap-3 text-sm md:grid-cols-2"
                >
                    <div>
                        <p class="text-muted-foreground">Supplier</p>
                        <p class="font-medium">
                            {{
                                selectedPurchaseOrder.noa?.bac_resolution?.aoq
                                    ?.winner_supplier?.name || "—"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Office</p>
                        <p class="font-medium">
                            {{
                                selectedPurchaseOrder.noa?.bac_resolution?.aoq
                                    ?.rfq?.purchase_request?.office?.name || "—"
                            }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Report Metadata</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="air_no">Air No.</Label>
                    <input
                        id="air_no"
                        v-model="form.air_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="invoice_no">Invoice No.</Label>
                    <input
                        id="invoice_no"
                        v-model="form.invoice_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="acceptance_date_received">Date Received</Label>
                    <input
                        id="acceptance_date_received"
                        v-model="form.acceptance_date_received"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p :class="getDateNoticeClass('acceptance_date_received')">
                        {{ getDateNotice("acceptance_date_received") }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="inspection_date_inspected"
                        >Date Inspected</Label
                    >
                    <input
                        id="inspection_date_inspected"
                        v-model="form.inspection_date_inspected"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p :class="getDateNoticeClass('inspection_date_inspected')">
                        {{ getDateNotice("inspection_date_inspected") }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="acceptance_status">Acceptance Status</Label>
                    <select
                        id="acceptance_status"
                        v-model="form.acceptance_status"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Not set —</option>
                        <option value="complete">Complete</option>
                        <option value="partial">Partial</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <Label for="inspection_status_ok">Inspection Result</Label>
                    <select
                        id="inspection_status_ok"
                        v-model="form.inspection_status_ok"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Not set —</option>
                        <option :value="true">
                            Inspected, verified and found OK
                        </option>
                        <option :value="false">Not OK</option>
                    </select>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="inspection_findings_text">Findings Text</Label>
                    <textarea
                        id="inspection_findings_text"
                        v-model="form.inspection_findings_text"
                        rows="3"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="property_officer_name"
                        >Property Officer Name</Label
                    >
                    <input
                        id="property_officer_name"
                        v-model="form.property_officer_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="property_officer_title"
                        >Property Officer Title</Label
                    >
                    <input
                        id="property_officer_title"
                        v-model="form.property_officer_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="inspection_officer_name"
                        >Inspection Officer Name</Label
                    >
                    <input
                        id="inspection_officer_name"
                        v-model="form.inspection_officer_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="inspection_officer_title"
                        >Inspection Officer Title</Label
                    >
                    <input
                        id="inspection_officer_title"
                        v-model="form.inspection_officer_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">PO Items Snapshot</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-auto rounded-md border">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-center">Qty</th>
                                <th class="px-4 py-2 text-left">Unit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!poItems.length">
                                <td
                                    colspan="3"
                                    class="px-4 py-4 text-center text-muted-foreground"
                                >
                                    Select a PO to preview items.
                                </td>
                            </tr>
                            <tr
                                v-for="item in poItems"
                                :key="item.id"
                                class="border-b"
                            >
                                <td class="px-4 py-2">
                                    {{
                                        item.rfq_item?.purchase_request_item
                                            ?.item_name || "—"
                                    }}
                                </td>
                                <td class="px-4 py-2 text-center">
                                    {{ item.quantity_snapshot }}
                                </td>
                                <td class="px-4 py-2">
                                    {{
                                        item.rfq_item?.purchase_request_item
                                            ?.unit || "—"
                                    }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
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
                Create Report
            </Button>
        </div>
    </form>
</template>
