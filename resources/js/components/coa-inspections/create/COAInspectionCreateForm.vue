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

const svpTouched = ref(false);
const svpSalutationTouched = ref(false);
const biddingTouched = ref(false);
const biddingSalutationTouched = ref(false);

const defaults = {
    svpHeader:
        "MARIA VANESSA C. BRIONES - VEGAS\nOIC - SUPERVISING AUDITOR\nCOMMISSION ON AUDIT\nCapitol Site, Batangas City",
    svpSalutation: "Ma'am:",
    biddingHeader:
        "HON. VILMA SANTOS - RECTO\nGovernor\nProvince of Batangas\nCapitol Site, Batangas City",
    biddingSalutation: "Dear Governor Recto:",
};

const selectedPurchaseOrder = computed(() =>
    props.purchaseOrders?.find(
        (po) => String(po.id) === String(props.form.purchase_order_id),
    ),
);

watch(
    () => props.form.purchase_order_id,
    () => {
        if (!svpTouched.value) {
            props.form.svp.header_text = defaults.svpHeader;
        }
        if (!svpSalutationTouched.value) {
            props.form.svp.salutation = defaults.svpSalutation;
        }
        if (!biddingTouched.value) {
            props.form.bidding.header_text = defaults.biddingHeader;
        }
        if (!biddingSalutationTouched.value) {
            props.form.bidding.salutation = defaults.biddingSalutation;
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
                        <option value="">- Select Purchase Order -</option>
                        <option
                            v-for="po in purchaseOrders"
                            :key="po.id"
                            :value="po.id"
                        >
                            {{ po.po_no }} -
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
                                    ?.winner_supplier?.name || "-"
                            }}
                        </p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Office</p>
                        <p class="font-medium">
                            {{
                                selectedPurchaseOrder.noa?.bac_resolution?.aoq
                                    ?.rfq?.purchase_request?.office?.name || "-"
                            }}
                        </p>
                    </div>
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
                            @input="svpTouched = true"
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
                            @input="svpSalutationTouched = true"
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
                            @input="biddingTouched = true"
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
                            @input="biddingSalutationTouched = true"
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
                Create COA Inspection
            </Button>
        </div>
    </form>
</template>
