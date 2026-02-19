<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import SupplierForm from "@/components/suppliers/edit/SupplierForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Suppliers", href: route("suppliers.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    supplier: Object,
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
                    Edit Supplier
                </h1>
                <p class="text-muted-foreground">Update supplier information</p>
            </div>
        </div>

        <SupplierForm
            action="edit"
            :route="route('suppliers.update', supplier.id)"
            :return-route="route('suppliers.index')"
            :supplier="supplier"
        />
    </div>
</template>
