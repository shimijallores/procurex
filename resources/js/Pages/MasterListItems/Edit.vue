<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import MasterListItemForm from "@/components/masterListItems/edit/MasterListItemForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Master List",
                        href: route("master-list-items.index"),
                    },
                    { label: "Edit Item" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    item: Object,
    categories: Array,
    suppliers: Array,
});

const isPhasedOut = ref(props.item?.is_phased_out ?? false);
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('master-list-items.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Master List Item
                </h1>
                <p class="text-muted-foreground">
                    Update item details and pricing
                </p>
            </div>
        </div>

        <MasterListItemForm
            action="edit"
            :route="route('master-list-items.update', item.id)"
            :return-route="route('master-list-items.index')"
            :item="item"
            :categories="categories"
            :suppliers="suppliers"
        />
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="master_list_category_id"
                    >Category <span class="text-destructive">*</span></Label
                >
                <select
                    id="master_list_category_id"
                    name="master_list_category_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="">Select category</option>
                    <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.id"
                        :selected="cat.id === item.master_list_category_id"
                    >
                        {{ cat.name }}
                    </option>
                </select>
            </div>
            <div class="space-y-2">
                <Label for="supplier_id"
                    >Supplier <span class="text-destructive">*</span></Label
                >
                <select
                    id="supplier_id"
                    name="supplier_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >
                    <option value="">Select supplier</option>
                    <option
                        v-for="sup in suppliers"
                        :key="sup.id"
                        :value="sup.id"
                        :selected="sup.id === item.supplier_id"
                    >
                        {{ sup.name }}
                    </option>
                </select>
            </div>
        </div>

        <div class="space-y-2">
            <Label for="item_name"
                >Item Name <span class="text-destructive">*</span></Label
            >
            <input
                id="item_name"
                name="item_name"
                type="text"
                :value="item.item_name"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
            <p v-if="errors?.item_name" class="text-sm text-destructive">
                {{ errors.item_name }}
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="space-y-2">
                <Label for="unit">Unit</Label>
                <input
                    id="unit"
                    name="unit"
                    type="text"
                    :value="item.unit"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
            </div>
            <div class="space-y-2">
                <Label for="default_unit_price">Default Unit Price (₱)</Label>
                <input
                    id="default_unit_price"
                    name="default_unit_price"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="item.default_unit_price"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
            </div>
        </div>

        <div class="space-y-2">
            <Label for="search_key">Search Key</Label>
            <input
                id="search_key"
                name="search_key"
                type="text"
                :value="item.search_key"
                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
            />
        </div>

        <!-- Phase Out Section -->
        <div class="rounded-lg border p-4 space-y-3">
            <div class="flex items-center gap-2">
                <input
                    id="is_phased_out"
                    name="is_phased_out"
                    type="checkbox"
                    value="1"
                    :checked="isPhasedOut"
                    class="h-4 w-4 rounded border-input"
                    @change="isPhasedOut = $event.target.checked"
                />
                <Label for="is_phased_out" class="font-medium text-destructive"
                    >Mark as Phased Out</Label
                >
            </div>
            <div v-if="isPhasedOut" class="space-y-2">
                <Label for="phased_out_reason">Phase-Out Reason</Label>
                <textarea
                    id="phased_out_reason"
                    name="phased_out_reason"
                    rows="2"
                    placeholder="Reason for phasing out this item"
                    :value="item.phased_out_reason"
                    class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                />
            </div>
        </div>

        <div class="space-y-2">
            <Label for="remarks">Remarks</Label>
            <textarea
                id="remarks"
                name="remarks"
                rows="2"
                class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                >{{ item.remarks }}</textarea
            >
        </div>

        <div class="flex gap-3 pt-2">
            <Button type="submit" :disabled="processing">
                <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                Update Item
            </Button>
            <Link :href="route('master-list-items.index')">
                <Button type="button" variant="outline">Cancel</Button>
            </Link>
        </div>
    </div>
</template>
