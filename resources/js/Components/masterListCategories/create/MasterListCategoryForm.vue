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
    category: Object,
});

const isEdit = props.action === "edit";
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>Category Details</CardTitle>
            <CardDescription>{{
                isEdit
                    ? "Update the category information"
                    : "Define a category for grouping master list items"
            }}</CardDescription>
        </CardHeader>
        <CardContent>
            <Form
                :action="route"
                :method="isEdit ? 'put' : 'post'"
                class="space-y-5"
                #default="{ errors, processing }"
            >
                <div class="space-y-2">
                    <Label for="name"
                        >Category Name
                        <span class="text-destructive">*</span></Label
                    >
                    <input
                        id="name"
                        name="name"
                        type="text"
                        :defaultValue="category?.name ?? ''"
                        placeholder="e.g. Office Supplies, Medical Supplies"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    />
                    <p v-if="errors?.name" class="text-sm text-destructive">
                        {{ errors.name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Optional category description"
                        :defaultValue="category?.description ?? ''"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    ></textarea>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        name="remarks"
                        rows="2"
                        :defaultValue="category?.remarks ?? ''"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                    ></textarea>
                </div>

                <div class="flex items-center gap-2">
                    <input
                        id="is_active"
                        name="is_active"
                        type="checkbox"
                        value="1"
                        :defaultChecked="category?.is_active ?? true"
                        class="h-4 w-4 rounded border-input"
                    />
                    <Label for="is_active">Active category</Label>
                </div>

                <div class="flex gap-3 pt-2">
                    <Button type="submit" :disabled="processing">
                        <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                        {{ isEdit ? "Update" : "Save" }} Category
                    </Button>
                    <Link :href="returnRoute">
                        <Button type="button" variant="outline">Cancel</Button>
                    </Link>
                </div>
            </Form>
        </CardContent>
    </Card>
</template>
