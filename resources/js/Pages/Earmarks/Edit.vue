<script setup>
import Layout from "@/Layout/Layout.vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import EarmarkEditForm from "@/components/earmarks/edit/EarmarkEditForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Budgeting", href: route("earmarks.index") },
                    {
                        label: `Earmark #${page.props.earmark?.earmark_no}`,
                        href: route("earmarks.show", page.props.earmark?.id),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

defineProps({
    earmark: Object,
    funds: Array,
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('earmarks.show', earmark.id)">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Earmark
                    <span class="text-primary">#{{ earmark.earmark_no }}</span>
                </h1>
                <p class="text-muted-foreground">Update the earmark details</p>
            </div>
        </div>

        <EarmarkEditForm :earmark="earmark" :funds="funds" />
    </div>
</template>
