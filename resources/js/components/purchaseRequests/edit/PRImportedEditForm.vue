<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    form: Object,
    offices: Array,
});

const emit = defineEmits(["submit"]);

const formatCurrency = (val) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(val || 0);

const getLineTotal = (item) => {
    const base =
        parseFloat(item.unit_cost || 0) * parseInt(item.quantity || 0);
    if (item.vat_applicable) {
        return base * (1 + parseFloat(item.vat_rate || 0.12));
    }
    return base;
};

const grandTotal = computed(() =>
    (props.form.items || []).reduce(
        (sum, item) => sum + getLineTotal(item),
        0,
    ),
);

function addItem() {
    props.form.items.push({
        id: null,
        item_name: "",
        unit: "",
        quantity: 1,
        unit_cost: 0,
        vat_applicable: false,
        vat_rate: 0.12,
        remarks: "",
    });
}

function removeItem(index) {
    props.form.items.splice(index, 1);
}
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <!-- PR Details -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
                    PR Details
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="pr_no">PR No.</Label>
                        <input
                            id="pr_no"
                            v-model="form.pr_no"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.pr_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.pr_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="pr_date"
                            >PR Date
                            <span class="text-destructive">*</span></Label
                        >
                        <input
                            id="pr_date"
                            v-model="form.pr_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.pr_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.pr_date }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="sai_no">SAI No.</Label>
                        <input
                            id="sai_no"
                            v-model="form.sai_no"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.sai_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.sai_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="sai_date">SAI Date</Label>
                        <input
                            id="sai_date"
                            v-model="form.sai_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.sai_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.sai_date }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="requested_by_name"
                            >Requested By (Name)</Label
                        >
                        <input
                            id="requested_by_name"
                            v-model="form.requested_by_name"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.requested_by_name"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.requested_by_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="requested_by_designation"
                            >Requested By (Designation)</Label
                        >
                        <input
                            id="requested_by_designation"
                            v-model="form.requested_by_designation"
                            type="text"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        />
                        <p
                            v-if="form.errors?.requested_by_designation"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.requested_by_designation }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="office_id">Office</Label>
                        <select
                            id="office_id"
                            v-model="form.office_id"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">— Select office —</option>
                            <option
                                v-for="office in offices"
                                :key="office.id"
                                :value="office.id"
                            >
                                {{ office.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors?.office_id"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.office_id }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="purpose"
                        >Purpose <span class="text-destructive">*</span></Label
                    >
                    <textarea
                        id="purpose"
                        v-model="form.purpose"
                        rows="3"
                        placeholder="Describe the purpose of this purchase request…"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    />
                    <p
                        v-if="form.errors?.purpose"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.purpose }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="2"
                        placeholder="Any additional remarks…"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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

        <!-- Items -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:package" class="h-4 w-4 text-primary" />
                    Items
                    <span
                        class="ml-auto text-sm font-normal text-muted-foreground"
                    >
                        {{ form.items.length }} item(s)
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div v-if="form.items.length === 0" class="py-6 text-center">
                    <Icon
                        icon="lucide:package"
                        class="mx-auto mb-2 h-8 w-8 text-muted-foreground opacity-40"
                    />
                    <p class="text-sm text-muted-foreground">
                        No items yet. Click "Add Item" to add line items.
                    </p>
                </div>

                <div v-else class="overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th
                                    class="h-10 px-3 text-left font-medium text-muted-foreground"
                                >
                                    #
                                </th>
                                <th
                                    class="h-10 px-3 text-left font-medium text-muted-foreground min-w-[200px]"
                                >
                                    Description
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-20"
                                >
                                    Unit
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-20"
                                >
                                    Qty
                                </th>
                                <th
                                    class="h-10 px-3 text-right font-medium text-muted-foreground w-32"
                                >
                                    Unit Cost
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-20"
                                >
                                    VAT 12%
                                </th>
                                <th
                                    class="h-10 px-3 text-right font-medium text-muted-foreground w-32"
                                >
                                    Total
                                </th>
                                <th
                                    class="h-10 px-3 text-center font-medium text-muted-foreground w-16"
                                ></th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="(item, idx) in form.items"
                                :key="idx"
                                class="border-b"
                            >
                                <td
                                    class="p-2 align-middle text-xs text-muted-foreground"
                                >
                                    {{ idx + 1 }}
                                </td>
                                <td class="p-2 align-middle">
                                    <input
                                        v-model="item.item_name"
                                        type="text"
                                        placeholder="Item description"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </td>
                                <td class="p-2 align-middle">
                                    <input
                                        v-model="item.unit"
                                        type="text"
                                        placeholder="pcs"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-center text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </td>
                                <td class="p-2 align-middle">
                                    <input
                                        v-model.number="item.quantity"
                                        type="number"
                                        min="1"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-center text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </td>
                                <td class="p-2 align-middle">
                                    <input
                                        v-model.number="item.unit_cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="w-full h-8 rounded border border-input bg-background px-2 text-right text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    />
                                </td>
                                <td class="p-2 align-middle text-center">
                                    <input
                                        v-model="item.vat_applicable"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                    />
                                </td>
                                <td
                                    class="p-2 align-middle text-right font-semibold"
                                >
                                    {{ formatCurrency(getLineTotal(item)) }}
                                </td>
                                <td class="p-2 align-middle text-center">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive h-8 w-8 p-0"
                                        @click="removeItem(idx)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4"
                                        />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/30">
                                <td
                                    colspan="7"
                                    class="px-3 py-3 text-right font-semibold"
                                >
                                    Grand Total
                                </td>
                                <td
                                    class="px-3 py-3 text-right font-bold text-primary text-base"
                                >
                                    {{ formatCurrency(grandTotal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-4">
                    <Button type="button" variant="outline" @click="addItem">
                        <Icon icon="lucide:plus" class="mr-1.5 h-4 w-4" />
                        Add Item
                    </Button>
                </div>
            </CardContent>
        </Card>

        <!-- Submit -->
        <div class="flex justify-end gap-3">
            <Button
                type="button"
                variant="outline"
                @click="router.get(route('purchase-requests.index'))"
            >
                Cancel
            </Button>
            <Button type="submit" :disabled="form.processing">
                <Icon icon="lucide:save" class="mr-1.5 h-4 w-4" />
                Update Purchase Request
            </Button>
        </div>
    </form>
</template>
