<script setup>
import { Icon } from "@iconify/vue";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
});

defineEmits(["submit"]);

const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:info" class="h-4 w-4 text-primary" />
                    RFQ Details
                </CardTitle>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <div class="space-y-2">
                    <Label for="svp_no">SVP Number</Label>
                    <input
                        id="svp_no"
                        v-model="form.svp_no"
                        type="text"
                        placeholder="YYYY-XXXX"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <p
                        v-if="form.errors?.svp_no"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.svp_no }}
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
                    RFQ Items
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
                                <th class="px-3 py-2 text-right">Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!form.items?.length">
                                <td
                                    colspan="5"
                                    class="px-3 py-6 text-center text-muted-foreground"
                                >
                                    No items found.
                                </td>
                            </tr>
                            <tr
                                v-for="(item, index) in form.items"
                                :key="item.id || item.pr_item_id"
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
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Update RFQ
            </Button>
        </div>
    </form>
</template>
