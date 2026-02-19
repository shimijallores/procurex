<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import MasterListCategoryForm from "@/components/masterListCategories/edit/MasterListCategoryForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    category: Object,
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
                    Edit Category
                </h1>
                <p class="text-muted-foreground">Update category information</p>
            </div>
        </div>

        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Category Details</CardTitle>
                <CardDescription
                    >Update the category information</CardDescription
                >
            </CardHeader>
            <CardContent>
                <Form
                    :action="
                        route('master-list-categories.update', category.id)
                    "
                    method="put"
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
                            :value="category.name"
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
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >{{ category.description }}</textarea
                        >
                    </div>

                    <div class="space-y-2">
                        <Label for="remarks">Remarks</Label>
                        <textarea
                            id="remarks"
                            name="remarks"
                            rows="2"
                            class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >{{ category.remarks }}</textarea
                        >
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_active"
                            name="is_active"
                            type="checkbox"
                            value="1"
                            :checked="category.is_active"
                            class="h-4 w-4 rounded border-input"
                        />
                        <Label for="is_active">Active category</Label>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <Button type="submit" :disabled="processing">
                            <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                            Update Category
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
