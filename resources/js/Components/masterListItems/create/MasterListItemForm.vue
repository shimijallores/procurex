<script setup>
import { Form, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

const props = defineProps({
    action: String,
    route: String,
    returnRoute: String,
    item: Object,
    categories: Array,
    suppliers: Array,
    prefill: Object,
    errors: Object,
    processing: Boolean,
});

const isEdit = props.action === "edit";
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Item Details</CardTitle>
            <CardDescription>{{
                isEdit
                    ? "Update item details and pricing"
                    : "Enter the item information and pricing"
            }}</CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="route"
                :method="isEdit ? 'put' : 'post'"
                #default="{ errors: formErrors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="master_list_category_id"
                            >Category
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="master_list_category_id"
                            name="master_list_category_id"
                            :value="item?.master_list_category_id ?? ''"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">Select category</option>
                            <option
                                v-for="cat in categories"
                                :key="cat.id"
                                :value="cat.id"
                                :selected="
                                    item?.master_list_category_id === cat.id
                                "
                            >
                                {{ cat.name }}
                            </option>
                        </select>
                        <p
                            v-if="formErrors?.master_list_category_id"
                            class="text-sm text-destructive"
                        >
                            {{ formErrors.master_list_category_id }}
                        </p>
                    </div>
                    <div class="space-y-2">
                        <Label for="supplier_id"
                            >Supplier
                            <span class="text-destructive">*</span></Label
                        >
                        <select
                            id="supplier_id"
                            name="supplier_id"
                            :value="item?.supplier_id ?? ''"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="">Select supplier</option>
                            <option
                                v-for="sup in suppliers"
                                :key="sup.id"
                                :value="sup.id"
                                :selected="item?.supplier_id === sup.id"
                            >
                                {{ sup.name }}
                            </option>
                        </select>
                        <p
                            v-if="formErrors?.supplier_id"
                            class="text-sm text-destructive"
                        >
                            {{ formErrors.supplier_id }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="item_name"
                        >Item Name
                        <span class="text-destructive">*</span></Label
                    >
                    <input
                        id="item_name"
                        name="item_name"
                        type="text"
                        :defaultValue="
                            item?.item_name ?? prefill?.item_name ?? ''
                        "
                        placeholder="e.g. Bond Paper A4 80gsm"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p
                        v-if="formErrors?.item_name"
                        class="text-sm text-destructive"
                    >
                        {{ formErrors.item_name }}
                    </p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="unit">Unit</Label>
                        <input
                            id="unit"
                            name="unit"
                            type="text"
                            :defaultValue="item?.unit ?? prefill?.unit ?? ''"
                            placeholder="e.g. ream, piece, box"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="default_unit_price"
                            >Default Unit Price (₱)</Label
                        >
                        <input
                            id="default_unit_price"
                            name="default_unit_price"
                            type="number"
                            step="0.01"
                            min="0"
                            :defaultValue="item?.default_unit_price ?? ''"
                            placeholder="0.00"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="search_key"
                        >Search Key
                        <span class="text-muted-foreground text-xs"
                            >(optional shorthand for searching)</span
                        ></Label
                    >
                    <input
                        id="search_key"
                        name="search_key"
                        type="text"
                        :defaultValue="
                            item?.search_key ?? prefill?.search_key ?? ''
                        "
                        placeholder="e.g. bond paper"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="2"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >{{ item?.remarks ?? "" }}</textarea
                    >
                </div>

                <!-- Phase Out Section (Edit only) -->
                <div v-if="isEdit" class="rounded-lg border p-4 space-y-3">
                    <div class="flex items-center gap-2">
                        <input
                            id="is_phased_out"
                            name="is_phased_out"
                            type="checkbox"
                            value="1"
                            :defaultChecked="item?.is_phased_out"
                            class="h-4 w-4 rounded border-input"
                        />
                        <Label
                            for="is_phased_out"
                            class="font-medium text-destructive"
                            >Mark as Phased Out</Label
                        >
                    </div>
                    <div v-if="item?.is_phased_out" class="space-y-2">
                        <Label for="phased_out_reason">Phase-Out Reason</Label>
                        <textarea
                            id="phased_out_reason"
                            name="phased_out_reason"
                            rows="2"
                            placeholder="Reason for phasing out this item"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >{{ item?.phased_out_reason ?? "" }}</textarea
                        >
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <Button type="submit" :disabled="processing">
                        <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                        {{ isEdit ? "Update" : "Save" }} Item
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
