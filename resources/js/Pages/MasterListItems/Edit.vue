<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { ref } from "vue";
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
    </div>
</template>
