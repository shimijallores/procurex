<script setup>
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Icon } from "@iconify/vue";

defineProps({
    coaInspection: Object,
    form: Object,
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
                        {{ coaInspection.purchase_order?.po_no || "-" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">PO Date</p>
                    <p class="font-medium">
                        {{ coaInspection.purchase_order?.po_date || "-" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Supplier</p>
                    <p class="font-medium">
                        {{
                            coaInspection.purchase_order?.noa?.bac_resolution
                                ?.aoq?.winner_supplier?.name || "-"
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project Name</p>
                    <p class="font-medium">
                        {{
                            coaInspection.purchase_order?.noa?.bac_resolution
                                ?.project_name || "-"
                        }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">SVP Letter</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="svp_header_text">Header / Addressee</Label>
                        <textarea
                            id="svp_header_text"
                            v-model="form.svp.header_text"
                            rows="6"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.['svp.header_text']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["svp.header_text"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="svp_salutation">Salutation</Label>
                        <input
                            id="svp_salutation"
                            v-model="form.svp.salutation"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Bidding Letter</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="space-y-2">
                        <Label for="bidding_header_text"
                            >Header / Addressee</Label
                        >
                        <textarea
                            id="bidding_header_text"
                            v-model="form.bidding.header_text"
                            rows="6"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p
                            v-if="form.errors?.['bidding.header_text']"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors["bidding.header_text"] }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="bidding_salutation">Salutation</Label>
                        <input
                            id="bidding_salutation"
                            v-model="form.bidding.salutation"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="text-base">Common Signatory</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <Label for="signatory_name">Signatory Name</Label>
                    <input
                        id="signatory_name"
                        v-model="form.signatory_name"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="space-y-2">
                    <Label for="signatory_title">Signatory Title</Label>
                    <input
                        id="signatory_title"
                        v-model="form.signatory_title"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
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
