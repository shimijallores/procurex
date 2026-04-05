<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligiblePurchaseRequests: Array,
});

defineEmits(["submit"]);
const selectedOfficeId = ref("");
const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const officeOptions = computed(() => {
    const map = new Map();

    for (const pr of props.eligiblePurchaseRequests || []) {
        const officeId = pr?.office?.id;
        const officeName = pr?.office?.name || "Unknown Office";

        if (!officeId || map.has(String(officeId))) {
            continue;
        }

        map.set(String(officeId), {
            id: String(officeId),
            name: officeName,
        });
    }

    return [...map.values()].sort((a, b) => a.name.localeCompare(b.name));
});

const filteredPurchaseRequests = computed(() => {
    if (!selectedOfficeId.value) {
        return [];
    }

    return (props.eligiblePurchaseRequests || []).filter(
        (pr) => String(pr?.office?.id || "") === selectedOfficeId.value,
    );
});

const selectedPurchaseRequest = computed(() => {
    if (props.form.pr_id) {
        return props.eligiblePurchaseRequests?.find(
            (pr) => String(pr.id) === String(props.form.pr_id),
        );
    }

    if (props.form.pr_no) {
        const normalized = String(props.form.pr_no).trim().toLowerCase();
        return props.eligiblePurchaseRequests?.find(
            (pr) =>
                String(pr.pr_no || "")
                    .trim()
                    .toLowerCase() === normalized,
        );
    }

    return null;
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};

watch(
    selectedPurchaseRequest,
    (pr) => {
        if (!pr) {
            return;
        }

        props.form.pr_id = String(pr.id);
        props.form.pr_no = pr.pr_no || "";
        props.form.project_name = pr.purpose || "";

        const defaultAbc = Number(
            pr.emanating?.ppmp_category?.total_estimated_budget ||
                pr.total_amount ||
                0,
        );
        props.form.abc_amount = String(defaultAbc || 0);

        props.form.items = (pr.items || []).map((item) => ({
            pr_item_id: item.id,
            item_name:
                item.item_name || item.emanating_item?.ppmp_item?.name || "",
            unit: item.unit || item.emanating_item?.unit || "",
            quantity: Number(item.quantity || 0),
        }));
    },
    { immediate: true },
);

const onPrDropdownChange = (value) => {
    props.form.pr_id = value;
    const selected = filteredPurchaseRequests.value?.find(
        (pr) => String(pr.id) === String(value),
    );
    props.form.pr_no = selected?.pr_no || "";
};

watch(selectedOfficeId, (officeId) => {
    if (!officeId) {
        props.form.pr_id = "";
        props.form.pr_no = "";
        props.form.project_name = "";
        props.form.abc_amount = "";
        props.form.items = [];

        return;
    }

    if (!props.form.pr_id) {
        return;
    }

    const stillValid = filteredPurchaseRequests.value.some(
        (pr) => String(pr.id) === String(props.form.pr_id),
    );

    if (!stillValid) {
        props.form.pr_id = "";
        props.form.pr_no = "";
        props.form.project_name = "";
        props.form.abc_amount = "";
        props.form.items = [];
    }
});

watch(
    () => props.form.rfq_date,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "rfq_date",
            statusKey: "rfq_date",
            clearDate: () => {
                props.form.rfq_date = "";
            },
        });
    },
);

watch(
    () => props.form.submission_deadline,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "submission_deadline",
            statusKey: "submission_deadline",
            clearDate: () => {
                props.form.submission_deadline = "";
            },
        });
    },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source Purchase Request
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="office_filter_id">Office</Label>
                        <select
                            id="office_filter_id"
                            v-model="selectedOfficeId"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">— Select office first —</option>
                            <option
                                v-for="office in officeOptions"
                                :key="office.id"
                                :value="office.id"
                            >
                                {{ office.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="pr_id">Approved Purchase Request</Label>
                        <select
                            id="pr_id"
                            :value="form.pr_id"
                            @change="onPrDropdownChange($event.target.value)"
                            :disabled="!selectedOfficeId"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        >
                            <option value="">
                                {{
                                    selectedOfficeId
                                        ? "— Select Purchase Request —"
                                        : "— Select office first —"
                                }}
                            </option>
                            <option
                                v-for="pr in filteredPurchaseRequests"
                                :key="pr.id"
                                :value="pr.id"
                            >
                                {{ pr.pr_no || "PR #" + pr.id }} —
                                {{ pr.office?.name }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <Label for="pr_no">Or Enter PR Number</Label>
                        <input
                            id="pr_no"
                            v-model="form.pr_no"
                            type="text"
                            placeholder="e.g. 0226-0001"
                            :disabled="!selectedOfficeId"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
                <p v-if="form.errors?.pr_id" class="text-xs text-destructive">
                    {{ form.errors.pr_id }}
                </p>
                <p v-if="form.errors?.pr_no" class="text-xs text-destructive">
                    {{ form.errors.pr_no }}
                </p>

                <div
                    v-if="selectedPurchaseRequest"
                    class="rounded-md border border-border bg-muted/40 p-3 text-sm space-y-1"
                >
                    <div>
                        <span class="text-muted-foreground">Office:</span>
                        <span class="font-medium">{{
                            selectedPurchaseRequest.office?.name || "—"
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">Purpose:</span>
                        <span class="font-medium">{{
                            selectedPurchaseRequest.purpose || "N/A"
                        }}</span>
                    </div>
                    <div>
                        <span class="text-muted-foreground">PR Total:</span>
                        <span class="font-medium">{{
                            formatCurrency(selectedPurchaseRequest.total_amount)
                        }}</span>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
                    RFQ Details
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="rfq_date">RFQ Date</Label>
                    <input
                        id="rfq_date"
                        v-model="form.rfq_date"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p :class="getDateNoticeClass('rfq_date')">
                        {{ getDateNotice("rfq_date") }}
                    </p>
                    <p
                        v-if="form.errors?.rfq_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.rfq_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="submission_deadline">Submission Deadline</Label>
                    <input
                        id="submission_deadline"
                        v-model="form.submission_deadline"
                        type="date"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p :class="getDateNoticeClass('submission_deadline')">
                        {{ getDateNotice("submission_deadline") }}
                    </p>
                    <p
                        v-if="form.errors?.submission_deadline"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.submission_deadline }}
                    </p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="project_name">Project Name</Label>
                    <input
                        id="project_name"
                        v-model="form.project_name"
                        type="text"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.project_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.project_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="abc_amount">ABC Amount</Label>
                    <input
                        id="abc_amount"
                        v-model="form.abc_amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.abc_amount"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.abc_amount }}
                    </p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.remarks"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:list" class="h-4 w-4 text-primary" />
                    RFQ Items Snapshot
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="px-3 py-2 text-left">Item</th>
                                <th class="px-3 py-2 text-center">Unit</th>
                                <th class="px-3 py-2 text-center">Quantity</th>
                                <th class="px-3 py-2 text-right">Unit Price</th>
                                <th class="px-3 py-2 text-right">
                                    Total Amount
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!form.items?.length">
                                <td
                                    colspan="5"
                                    class="px-3 py-6 text-center text-muted-foreground"
                                >
                                    Select a Purchase Request to load items.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, index) in form.items"
                                :key="item.pr_item_id"
                                class="border-b"
                            >
                                <td class="px-3 py-2">{{ item.item_name }}</td>
                                <td class="px-3 py-2 text-center">
                                    {{ item.unit || "—" }}
                                </td>
                                <td class="px-3 py-2">
                                    <input
                                        v-model.number="
                                            form.items[index].quantity
                                        "
                                        type="number"
                                        min="1"
                                        class="mx-auto flex h-9 w-24 rounded-md border border-input bg-background px-2 py-1 text-center"
                                    />
                                </td>
                                <td
                                    class="px-3 py-2 text-right text-muted-foreground"
                                >
                                    (to be filled by supplier)
                                </td>
                                <td
                                    class="px-3 py-2 text-right text-muted-foreground"
                                >
                                    (to be filled by supplier)
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p
                    v-if="form.errors?.items"
                    class="mt-2 text-xs text-destructive"
                >
                    {{ form.errors.items }}
                </p>
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
                Create RFQ
            </Button>
        </div>
    </form>
</template>
