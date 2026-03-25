<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Icon } from "@iconify/vue";
import { Label } from "@/components/ui/label";

defineProps({
    acceptanceInspection: Object,
    form: Object,
});

defineEmits(["submit"]);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">PO Date</p>
                <p class="font-medium">
                    {{ acceptanceInspection.purchase_order?.po_date || "—" }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">Supplier</p>
                <p class="font-medium">
                    {{
                        acceptanceInspection.purchase_order?.noa?.bac_resolution
                            ?.aoq?.winner_supplier?.name || "—"
                    }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">Office</p>
                <p class="font-medium">
                    {{
                        acceptanceInspection.purchase_order?.noa?.bac_resolution
                            ?.aoq?.rfq?.purchase_request?.office?.name || "—"
                    }}
                </p>
            </div>
            <div class="rounded-lg border bg-card p-4 text-sm">
                <p class="text-muted-foreground">Project</p>
                <p class="font-medium truncate">
                    {{
                        acceptanceInspection.purchase_order?.noa?.bac_resolution
                            ?.project_name || "—"
                    }}
                </p>
            </div>
        </div>

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
                <CardTitle class="text-base">PO Items</CardTitle>
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
                            <tr
                                v-for="item in acceptanceInspection
                                    .purchase_order?.items || []"
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

        <div class="flex justify-end">
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Save Changes
            </Button>
        </div>
    </form>
</template>
