<script setup>
import { ref, watch, computed } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useCalendarCheck } from "@/composables/useCalendarCheck";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import { Input } from "@/components/ui/input";

const props = defineProps({
    purchaseRequest: Object, // pre-selected PR
    eligiblePRs: Array,
    funds: Array,
});

const form = useForm({
    purchase_request_id: props.purchaseRequest?.id ?? "",
    fund_id: props.purchaseRequest?.fund_id ?? "",
    earmark_no: "",
    earmark_date: new Date().toISOString().split("T")[0],
    certified_amount: props.purchaseRequest?.total_amount ?? "",
    expense_class: "",
    resolution_no: "",
    ordinance_no: "",
    ordinance_date: "",
    remarks: "",
});

// Calendar check
const { checkDate, isChecking } = useCalendarCheck();
const earmarkDateCheck = ref(null);
const ordinanceDateCheck = ref(null);

const checkEarmarkDate = async (date) => {
    if (!date) return;
    earmarkDateCheck.value = await checkDate(date);
};

const checkOrdinanceDate = async (date) => {
    if (!date) return;
    ordinanceDateCheck.value = await checkDate(date);
};

// Sync certified_amount when PR changed
const selectedPR = computed(() =>
    props.eligiblePRs?.find((pr) => pr.id === Number(form.purchase_request_id)),
);

watch(
    () => form.purchase_request_id,
    (newId) => {
        const pr = props.eligiblePRs?.find((p) => p.id === Number(newId));
        if (pr) {
            form.certified_amount = pr.total_amount;
            form.fund_id = pr.fund_id;
            form.expense_class = pr.category_name || "";
        }
    },
);

watch(
    () => form.earmark_date,
    (newDate) => checkEarmarkDate(newDate),
);

watch(
    () => form.ordinance_date,
    (newDate) => checkOrdinanceDate(newDate),
);

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);

const submit = () => {
    form.post(route("earmarks.store"));
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Linked PR -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:file-text" class="h-5 w-5" />
                    Purchase Request
                </CardTitle>
                <CardDescription>
                    Select or confirm the PR to issue the earmark for
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <!-- If pre-selected PR -->
                <div v-if="purchaseRequest">
                    <div
                        class="rounded-md border border-primary/30 bg-primary/5 p-4 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <p class="font-semibold">
                                PR #{{
                                    purchaseRequest.pr_no || purchaseRequest.id
                                }}
                            </p>
                            <span
                                class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-900 dark:text-amber-300"
                            >
                                For Budget Review
                            </span>
                        </div>
                        <p class="text-sm text-muted-foreground">
                            {{ purchaseRequest.office?.name || "—" }}
                        </p>
                        <p class="text-sm">
                            Total Amount:
                            <strong>{{
                                formatCurrency(purchaseRequest.total_amount)
                            }}</strong>
                        </p>
                        <p
                            v-if="purchaseRequest.purpose"
                            class="text-sm text-muted-foreground"
                        >
                            {{ purchaseRequest.purpose }}
                        </p>
                    </div>
                    <input type="hidden" v-model="form.purchase_request_id" />
                </div>

                <!-- Select PR dropdown otherwise -->
                <div v-else class="space-y-2">
                    <Label for="purchase_request_id"
                        >Select PR
                        <span class="text-destructive">*</span></Label
                    >
                    <select
                        id="purchase_request_id"
                        v-model="form.purchase_request_id"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">— Select a Purchase Request —</option>
                        <option
                            v-for="pr in eligiblePRs"
                            :key="pr.id"
                            :value="pr.id"
                        >
                            PR #{{ pr.pr_no || pr.id }} –
                            {{ pr.office?.name }} ({{
                                formatCurrency(pr.total_amount)
                            }})
                        </option>
                    </select>
                    <p
                        v-if="form.errors.purchase_request_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.purchase_request_id }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- Earmark Info -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:stamp" class="h-5 w-5" />
                    Earmark Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <!-- Earmark No. -->
                    <div class="space-y-2">
                        <Label for="earmark_no"
                            >Earmark No.
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="earmark_no"
                            v-model="form.earmark_no"
                            placeholder="e.g. EA2026-01-0001"
                        />
                        <p
                            v-if="form.errors.earmark_no"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.earmark_no }}
                        </p>
                    </div>

                    <!-- Earmark Date -->
                    <div class="space-y-2">
                        <Label for="earmark_date"
                            >Date <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="earmark_date"
                            type="date"
                            v-model="form.earmark_date"
                        />
                        <p
                            v-if="form.errors.earmark_date"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.earmark_date }}
                        </p>
                        <!-- Calendar Warning -->
                        <div
                            v-if="isChecking && form.earmark_date"
                            class="text-xs text-muted-foreground"
                        >
                            Checking calendar...
                        </div>
                        <div
                            v-if="
                                earmarkDateCheck &&
                                !earmarkDateCheck.is_available
                            "
                            class="rounded-md border border-amber-200 bg-amber-50 p-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-300"
                        >
                            <p class="font-medium">
                                ⚠️ {{ earmarkDateCheck.name }}
                            </p>
                            <p class="text-xs opacity-90">
                                {{ earmarkDateCheck.message }}
                            </p>
                        </div>
                    </div>

                    <!-- Fund -->
                    <div class="space-y-2">
                        <Label for="fund_id"
                            >Fund <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="fund_id"
                            v-model="form.fund_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">— Select Fund —</option>
                            <option
                                v-for="fund in funds"
                                :key="fund.id"
                                :value="fund.id"
                            >
                                {{ fund.name
                                }}{{ fund.code ? ` (${fund.code})` : "" }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.fund_id"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.fund_id }}
                        </p>
                    </div>

                    <!-- Certified Amount -->
                    <div class="space-y-2">
                        <Label for="certified_amount"
                            >Certified Amount
                            <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="certified_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            v-model="form.certified_amount"
                            placeholder="0.00"
                        />
                        <p
                            v-if="form.errors.certified_amount"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.certified_amount }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Budget Reference -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:scroll" class="h-5 w-5" />
                    Budget Reference
                </CardTitle>
                <CardDescription
                    >Sangguniang Panlalawigan resolution / ordinance
                    details</CardDescription
                >
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="resolution_no">Resolution No.</Label>
                        <Input
                            id="resolution_no"
                            v-model="form.resolution_no"
                            placeholder="e.g. 1774"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="ordinance_no"
                            >Appropriation Ordinance No.</Label
                        >
                        <Input
                            id="ordinance_no"
                            v-model="form.ordinance_no"
                            placeholder="e.g. 005"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="ordinance_date">Ordinance Date</Label>
                        <Input
                            id="ordinance_date"
                            type="date"
                            v-model="form.ordinance_date"
                        />
                        <!-- Calendar Warning -->
                        <div
                            v-if="isChecking && form.ordinance_date"
                            class="text-xs text-muted-foreground"
                        >
                            Checking calendar...
                        </div>
                        <div
                            v-if="
                                ordinanceDateCheck &&
                                !ordinanceDateCheck.is_available
                            "
                            class="rounded-md border border-amber-200 bg-amber-50 p-2 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-900/20 dark:text-amber-300"
                        >
                            <p class="font-medium">
                                ⚠️ {{ ordinanceDateCheck.name }}
                            </p>
                            <p class="text-xs opacity-90">
                                {{ ordinanceDateCheck.message }}
                            </p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Remarks -->
        <Card>
            <CardHeader>
                <CardTitle>Remarks</CardTitle>
            </CardHeader>
            <CardContent>
                <textarea
                    v-model="form.remarks"
                    rows="3"
                    placeholder="Optional remarks…"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
            </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-2">
            <Link :href="route('earmarks.index')">
                <Button type="button" variant="outline">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="form.processing">
                <Icon icon="lucide:stamp" class="mr-2 h-4 w-4" />
                Issue Earmark & Approve PR
            </Button>
        </div>
    </form>
</template>
