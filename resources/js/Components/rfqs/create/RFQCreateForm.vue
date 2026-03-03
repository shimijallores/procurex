<script setup>
import { computed, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligiblePurchaseRequests: Array,
    suppliers: Array,
});

const emit = defineEmits(["submit"]);

const selectedPurchaseRequest = computed(() =>
    props.eligiblePurchaseRequests?.find(
        (pr) => String(pr.id) === String(props.form.pr_id),
    ),
);

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};

watch(
    () => props.form.pr_id,
    (newPrId) => {
        if (!newPrId) return;
        const pr = props.eligiblePurchaseRequests?.find(
            (item) => String(item.id) === String(newPrId),
        );

        if (!pr) return;

        if (!props.form.submission_deadline && props.form.rfq_date) {
            const date = new Date(props.form.rfq_date);
            date.setDate(date.getDate() + 7);
            props.form.submission_deadline = date.toISOString().split("T")[0];
        }
    },
);

const toggleSupplier = (supplierId) => {
    if (props.form.supplier_ids.includes(supplierId)) {
        props.form.supplier_ids = props.form.supplier_ids.filter(
            (id) => id !== supplierId,
        );
        return;
    }

    props.form.supplier_ids = [...props.form.supplier_ids, supplierId];
};
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
            <CardContent class="space-y-3">
                <div class="space-y-2">
                    <Label for="pr_id">Approved Purchase Request</Label>
                    <select
                        id="pr_id"
                        :value="form.pr_id"
                        @change="form.pr_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">— Select Purchase Request —</option>
                        <option
                            v-for="pr in eligiblePurchaseRequests"
                            :key="pr.id"
                            :value="pr.id"
                        >
                            {{ pr.pr_no || "PR #" + pr.id }} —
                            {{ pr.office?.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.pr_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.pr_id }}
                    </p>
                </div>

                <div
                    v-if="selectedPurchaseRequest"
                    class="rounded-md border border-border bg-muted/40 p-3 text-sm space-y-1"
                >
                    <div>
                        <span class="text-muted-foreground">Office:</span>
                        <span class="font-medium">
                            {{
                                selectedPurchaseRequest.office?.name || "—"
                            }}</span
                        >
                    </div>
                    <div>
                        <span class="text-muted-foreground">Project:</span>
                        <span class="font-medium">
                            {{
                                selectedPurchaseRequest.emanating?.project
                                    ?.name ||
                                selectedPurchaseRequest.purpose ||
                                "N/A"
                            }}</span
                        >
                    </div>
                    <div>
                        <span class="text-muted-foreground">ABC:</span>
                        <span class="font-medium">
                            {{
                                formatCurrency(
                                    selectedPurchaseRequest.total_amount,
                                )
                            }}</span
                        >
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
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
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
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p class="text-xs text-muted-foreground">
                        Accomplishment beyond one week is tagged as late filing.
                    </p>
                    <p
                        v-if="form.errors?.submission_deadline"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.submission_deadline }}
                    </p>
                </div>

                <div class="space-y-2 sm:col-span-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Optional notes for this RFQ..."
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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
                    <Icon
                        icon="lucide:building-2"
                        class="h-4 w-4 text-primary"
                    />
                    Suppliers
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="grid gap-2 sm:grid-cols-2">
                    <button
                        v-for="supplier in suppliers"
                        :key="supplier.id"
                        type="button"
                        :class="[
                            'rounded-md border p-3 text-left transition-colors',
                            form.supplier_ids.includes(supplier.id)
                                ? 'border-primary bg-primary/10'
                                : 'border-border hover:bg-muted',
                        ]"
                        @click="toggleSupplier(supplier.id)"
                    >
                        <div class="font-medium">{{ supplier.name }}</div>
                        <div class="text-xs text-muted-foreground mt-1">
                            {{ supplier.contact_number || "No contact number" }}
                        </div>
                        <div
                            class="text-xs text-muted-foreground truncate"
                            :title="supplier.address"
                        >
                            {{ supplier.address || "No address" }}
                        </div>
                    </button>
                </div>

                <p class="text-xs text-muted-foreground">
                    Selected {{ form.supplier_ids.length }} supplier(s)
                </p>
                <p
                    v-if="form.errors?.supplier_ids"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.supplier_ids }}
                </p>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()">
                Reset
            </Button>
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
