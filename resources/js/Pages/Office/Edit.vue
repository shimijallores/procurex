<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import OfficeForm from "@/components/offices/edit/OfficeForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Offices", href: route("offices.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    office: Object,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('offices.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Office
                </h1>
                <p class="text-muted-foreground">Update office information</p>
            </div>
        </div>

        <OfficeForm
            action="edit"
            :route="route('offices.update', office.id)"
            :return-route="route('offices.index')"
            :office="office"
        />
    </div>
</template>
