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
    errors: Object,
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

        <MasterListCategoryForm
            action="edit"
            :route="route('master-list-categories.update', category.id)"
            :return-route="route('master-list-categories.index')"
            :category="category"
        />
    </div>
</template>
