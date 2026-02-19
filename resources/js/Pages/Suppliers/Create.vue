<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import SupplierForm from "@/components/suppliers/create/SupplierForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Suppliers", href: route("suppliers.index") },
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
            <Link :href="route('suppliers.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Add Supplier
                </h1>
                <p class="text-muted-foreground">
                    Register a new supplier or vendor
                </p>
            </div>
        </div>

        <SupplierForm
            action="create"
            :route="route('suppliers.store')"
            :return-route="route('suppliers.index')"
        />
    </div>
</template>
