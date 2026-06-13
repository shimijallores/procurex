<script setup>
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    matrixRow: Object,
    form: Object,
});

defineEmits(["submit"]);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Editable Fields</CardTitle>
        </CardHeader>

        <CardContent>
            <form class="space-y-4" @submit.prevent="$emit('submit')">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="svp">SVP No.</Label>
                        <Input id="svp" :model-value="props.matrixRow.svp_no || '-'" readonly class="bg-muted text-muted-foreground" />
                    </div>

                    <div class="space-y-2">
                        <Label for="batch">Batch</Label>
                        <Input id="batch" :model-value="props.matrixRow.batch || '-'" readonly class="bg-muted text-muted-foreground" />
                    </div>

                    <div class="space-y-2">
                        <Label for="office">Office</Label>
                        <Input id="office" v-model="props.form.office_text" />
                    </div>

                    <div class="space-y-2">
                        <Label for="po_no">PO No.</Label>
                        <Input id="po_no" v-model="props.form.po_no_text" />
                    </div>

                    <div class="space-y-2">
                        <Label for="mode_of_procurement"
                            >Mode of Procurement</Label
                        >
                        <Input
                            id="mode_of_procurement"
                            v-model="props.form.mode_of_procurement_text"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="pr_no">PR No.</Label>
                        <Input id="pr_no" v-model="props.form.pr_no_text" readonly class="bg-muted text-muted-foreground" />
                    </div>

                    <div class="space-y-2">
                        <Label for="abc">ABC</Label>
                        <Input
                            id="abc"
                            type="number"
                            step="0.01"
                            v-model="props.form.abc_amount"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="amount">Amount</Label>
                        <Input
                            id="amount"
                            type="number"
                            step="0.01"
                            v-model="props.form.amount_value"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="supplier">Supplier</Label>
                        <Input
                            id="supplier"
                            v-model="props.form.supplier_text"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="particulars">Particulars</Label>
                        <Input
                            id="particulars"
                            v-model="props.form.particulars_text"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="rfq">RFQ</Label>
                        <Input id="rfq" v-model="props.form.rfq_value" />
                    </div>

                    <div class="space-y-2">
                        <Label for="abstract">Abstract</Label>
                        <Input
                            id="abstract"
                            v-model="props.form.abstract_value"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="resolution">Resolution</Label>
                        <Input
                            id="resolution"
                            v-model="props.form.resolution_value"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="noa_po">NOA & PO</Label>
                        <Input id="noa_po" v-model="props.form.noa_po_value" />
                    </div>

                    <div class="space-y-2">
                        <Label for="transmittal_form">Transmittal Form</Label>
                        <Input
                            id="transmittal_form"
                            v-model="props.form.transmittal_form_value"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="bac_members_gov">BAC Members/Gov</Label>
                        <Input id="bac_members_gov" v-model="props.form.admin_value" />
                    </div>

                    <div class="space-y-2">
                        <Label for="frontdesk">Frontdesk</Label>
                        <Input
                            id="frontdesk"
                            v-model="props.form.frontdesk_value"
                        />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <Label for="remarks">Remarks</Label>
                        <Input
                            id="remarks"
                            v-model="props.form.remarks_value"
                        />
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="props.form.processing">
                        Save Changes
                    </Button>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
