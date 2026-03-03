<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

defineProps({
    form: Object,
    poTransmittal: Object,
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
                <CardTitle class="text-base">Transmittal Details</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="type">Type</Label>
                    <select
                        id="type"
                        v-model="form.type"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="coa">COA</option>
                        <option value="opg">OPG</option>
                    </select>
                    <p
                        v-if="form.errors?.type"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.type }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="transmittal_no">Transmittal No.</Label>
                    <input
                        id="transmittal_no"
                        v-model="form.transmittal_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.transmittal_no"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.transmittal_no }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="transmittal_date">Transmittal Date</Label>
                    <input
                        id="transmittal_date"
                        v-model="form.transmittal_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.transmittal_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.transmittal_date }}
                    </p>
                </div>

                <div class="space-y-2" v-if="form.type === 'coa'">
                    <Label for="coa_circular_no">COA Circular No.</Label>
                    <input
                        id="coa_circular_no"
                        v-model="form.coa_circular_no"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.coa_circular_no"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.coa_circular_no }}
                    </p>
                </div>

                <div class="space-y-2 md:col-span-2">
                    <Label for="header_text">Header / Addressee</Label>
                    <textarea
                        id="header_text"
                        v-model="form.header_text"
                        rows="6"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.header_text"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.header_text }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="signatory_name">Signatory Name</Label>
                    <input
                        id="signatory_name"
                        v-model="form.signatory_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.signatory_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.signatory_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="signatory_title">Signatory Title</Label>
                    <input
                        id="signatory_title"
                        v-model="form.signatory_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.signatory_title"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.signatory_title }}
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
