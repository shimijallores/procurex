<script setup>
import Layout from "@/Layout/Layout.vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import EarmarkCreateForm from "@/components/earmarks/create/EarmarkCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Budgeting", href: route("earmarks.index") },
                    { label: "Issue Earmark" },
                ],
            },
            () => page,
        ),
});

defineProps({
    purchaseRequest: Object,
    eligiblePRs: Array,
    funds: Array,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex items-center gap-4">
            <Link :href="route('earmarks.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Issue Earmark
                </h1>
                <p class="text-muted-foreground">
                    Review the Purchase Request and fill in the earmark /
                    certification details
                </p>
            </div>
        </div>

        <EarmarkCreateForm
            :purchase-request="purchaseRequest"
            :eligible-p-rs="eligiblePRs"
            :funds="funds"
        />
    </div>
</template>
