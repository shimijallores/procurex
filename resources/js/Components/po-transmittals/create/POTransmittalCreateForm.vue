<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    purchaseOrders: Array,
});

defineEmits(["submit"]);

const headerTouched = ref(false);
const signatoryTouched = ref(false);
const titleTouched = ref(false);
const opgHeaderTouched = ref(false);
const opgSignatoryTouched = ref(false);
const opgTitleTouched = ref(false);

const selectedPurchaseOrder = computed(() =>
    props.purchaseOrders?.find(
        (po) => String(po.id) === String(props.form.purchase_order_id),
    ),
);

const typeDefaults = {
    coa: {
        header_text:
            "MARIA VANESSA C. BRIONES - VEGAS\nOIC – SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City\n\nMa’am,",
        signatory_name: "NOEL R. ROCAFORT",
        signatory_title: "PGDH – GSO",
    },
    opg: {
        header_text:
            "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City\n\nMa’am,",
        signatory_name: "NOEL R. ROCAFORT",
        signatory_title: "PGDH – GSO",
    },
};

watch(
    () => props.form.purchase_order_id,
    () => {
        if (!headerTouched.value) {
            props.form.coa.header_text = typeDefaults.coa.header_text;
        }
        if (!signatoryTouched.value) {
            props.form.coa.signatory_name = typeDefaults.coa.signatory_name;
        }
        if (!titleTouched.value) {
            props.form.coa.signatory_title = typeDefaults.coa.signatory_title;
        }

        if (!opgHeaderTouched.value) {
            props.form.opg.header_text = typeDefaults.opg.header_text;
        }
        if (!opgSignatoryTouched.value) {
            props.form.opg.signatory_name = typeDefaults.opg.signatory_name;
        }
        if (!opgTitleTouched.value) {
            props.form.opg.signatory_title = typeDefaults.opg.signatory_title;
        }
    },
    { immediate: true },
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
                        <p class="text-muted-foreground">Project</p>
                        <p class="font-medium">
                            {{
                                selectedPurchaseOrder.noa?.bac_resolution
                                    ?.project_name || "—"
                            }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-6 lg:grid-cols-2">
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
                        <Label for="coa_transmittal_date"
                            >Transmittal Date</Label
                        >
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
                            @input="headerTouched = true"
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
                            @input="signatoryTouched = true"
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
                            @input="titleTouched = true"
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
                        <Label for="opg_transmittal_date"
                            >Transmittal Date</Label
                        >
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
                            @input="opgHeaderTouched = true"
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
                            @input="opgSignatoryTouched = true"
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
                            @input="opgTitleTouched = true"
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
        </div>

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
                Create COA + OPG Transmittals
            </Button>
        </div>
    </form>
</template>
