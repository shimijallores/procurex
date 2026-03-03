<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import MasterListItemForm from "@/components/masterListItems/create/MasterListItemForm.vue";

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
                    { label: "Add Item" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    categories: Array,
    suppliers: Array,
    prefill: Object,
});
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
                    Add Master List Item
                </h1>
                <p class="text-muted-foreground">
                    Add a new item to the canvassing master list
                </p>
            </div>
        </div>

        <MasterListItemForm
            action="create"
            :route="route('master-list-items.store')"
            :return-route="route('master-list-items.index')"
            :categories="categories"
            :suppliers="suppliers"
            :prefill="prefill"
        />
    </div>
</template>
