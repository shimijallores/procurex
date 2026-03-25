<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

defineProps({
    form: Object,
    poTransmittal: Object,
    coaTransmittal: Object,
    opgTransmittal: Object,
});

defineEmits(["submit"]);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="text-base">PO Snapshot</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">PO No.</p>
                    <p class="font-medium">
                        {{ poTransmittal.purchase_order?.po_no || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">PO Date</p>
                    <p class="font-medium">
                        {{ poTransmittal.purchase_order?.po_date || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Supplier</p>
                    <p class="font-medium">
                        {{
                            poTransmittal.purchase_order?.noa?.bac_resolution
                                ?.aoq?.winner_supplier?.name || "—"
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project Name</p>
                    <p class="font-medium">
                        {{
                            poTransmittal.purchase_order?.noa?.bac_resolution
                                ?.project_name || "—"
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">COA Transmittal</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="coa_transmittal_no">Transmittal No.</Label>
                    <input
                        id="coa_transmittal_no"
                        v-model="form.coa.transmittal_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.transmittal_no']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.transmittal_no"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="coa_transmittal_date">Transmittal Date</Label>
                    <input
                        id="coa_transmittal_date"
                        v-model="form.coa.transmittal_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.transmittal_date']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.transmittal_date"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="coa_circular_no">COA Circular No.</Label>
                    <input
                        id="coa_circular_no"
                        v-model="form.coa.coa_circular_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.coa_circular_no']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.coa_circular_no"] }}
                    </p>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="coa_header_text">Header / Addressee</Label>
                    <textarea
                        id="coa_header_text"
                        v-model="form.coa.header_text"
                        rows="6"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.header_text']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.header_text"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="coa_signatory_name">Signatory Name</Label>
                    <input
                        id="coa_signatory_name"
                        v-model="form.coa.signatory_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.signatory_name']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.signatory_name"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="coa_signatory_title">Signatory Title</Label>
                    <input
                        id="coa_signatory_title"
                        v-model="form.coa.signatory_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['coa.signatory_title']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["coa.signatory_title"] }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">OPG Transmittal</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="opg_transmittal_no">Transmittal No.</Label>
                    <input
                        id="opg_transmittal_no"
                        v-model="form.opg.transmittal_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['opg.transmittal_no']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["opg.transmittal_no"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="opg_transmittal_date">Transmittal Date</Label>
                    <input
                        id="opg_transmittal_date"
                        v-model="form.opg.transmittal_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['opg.transmittal_date']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["opg.transmittal_date"] }}
                    </p>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="opg_header_text">Header / Addressee</Label>
                    <textarea
                        id="opg_header_text"
                        v-model="form.opg.header_text"
                        rows="6"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['opg.header_text']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["opg.header_text"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="opg_signatory_name">Signatory Name</Label>
                    <input
                        id="opg_signatory_name"
                        v-model="form.opg.signatory_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['opg.signatory_name']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["opg.signatory_name"] }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="opg_signatory_title">Signatory Title</Label>
                    <input
                        id="opg_signatory_title"
                        v-model="form.opg.signatory_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.['opg.signatory_title']"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors["opg.signatory_title"] }}
                    </p>
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
