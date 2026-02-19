<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import MasterListCategoryForm from "@/components/masterListCategories/create/MasterListCategoryForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Master List Categories",
                        href: route("master-list-categories.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('master-list-categories.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Add Category
                </h1>
                <p class="text-muted-foreground">
                    Create a new master list item category
                </p>
            </div>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Category Details</CardTitle>
                <CardDescription
                    >Define a category for grouping master list
                    items</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form
                    :action="route('master-list-categories.store')"
                    method="post"
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
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">Remarks</Label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        />
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            checked
                            class="h-4 w-4 rounded border-input"
                        />
                        <Label for="is_active">Active category</Label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="processing">
                            <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                            Save Category
                        </Button>
                        <Link :href="route('master-list-categories.index')">
                            <Button type="button" variant="outline"
                                >Cancel</Button
                            >
                        </Link>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
