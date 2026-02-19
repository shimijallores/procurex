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

        <MasterListCategoryForm
            action="create"
            :route="route('master-list-categories.store')"
            :return-route="route('master-list-categories.index')"
        />
    </div>
</template>
