<script setup>
import { ref, watch } from "vue";
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
    earmark: Object,
    funds: Array,
});

const form = useForm({
    fund_id: props.earmark.fund_id,
    earmark_no: props.earmark.earmark_no,
    earmark_date:
        props.earmark.earmark_date?.split("T")[0] ?? props.earmark.earmark_date,
    certified_amount: props.earmark.certified_amount,
    expense_class: props.earmark.expense_class ?? "",
    resolution_no: props.earmark.resolution_no ?? "",
    ordinance_no: props.earmark.ordinance_no ?? "",
    ordinance_date: props.earmark.ordinance_date?.split("T")[0] ?? "",
    remarks: props.earmark.remarks ?? "",
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

watch(
    () => form.earmark_date,
    (newDate) => checkEarmarkDate(newDate),
);

watch(
    () => form.ordinance_date,
    (newDate) => checkOrdinanceDate(newDate),
);

const submit = () => {
    form.put(route("earmarks.update", props.earmark.id));
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- Earmark Info -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:stamp" class="h-5 w-5" />
                    Earmark Details
                </CardTitle>
                <CardDescription>
                    Linked to PR #{{
                        earmark.purchase_request?.pr_no ||
                        earmark.purchase_request_id
                    }}
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="earmark_no"
                            >Earmark No.
                            <span class="text-destructive">*</span></Label
                        >
                        <Input id="earmark_no" v-model="form.earmark_no" />
                        <p
                            v-if="form.errors.earmark_no"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.earmark_no }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="earmark_date"
                            >Date <span class="text-destructive">*</span></Label
                        >
                        <Input
                            id="earmark_date"
                            type="date"
                            v-model="form.earmark_date"
                        />
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
            <CardHeader><CardTitle>Remarks</CardTitle></CardHeader>
            <CardContent>
                <textarea
                    v-model="form.remarks"
                    rows="3"
                    placeholder="Optional remarks…"
                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                />
            </CardContent>
        </Card>

        <div class="flex items-center justify-end gap-2">
            <Link :href="route('earmarks.show', earmark.id)">
                <Button type="button" variant="outline">Cancel</Button>
            </Link>
            <Button type="submit" :disabled="form.processing">
                <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                Save Changes
            </Button>
        </div>
    </form>
</template>
