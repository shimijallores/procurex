<script setup>
import { ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import EmanatingTable from "@/components/canvasses/create/EmanatingTable.vue";
import CanvasSettingsPanel from "@/components/canvasses/create/CanvasSettingsPanel.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Canvassing", href: route("canvasses.index") },
                    { label: "New Canvas" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    emanatings: Array,
});

const form = useForm({
    emanating_id: "",
});

const selectedEmanating = ref(null);

const selectEmanating = (em) => {
    selectedEmanating.value = em;
    form.emanating_id = em.id;
};

const submit = () => {
    form.post(route("canvasses.store"));
};

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('canvasses.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    New Canvas
                </h1>
                <p class="text-muted-foreground">
                    Select an approved emanating request to begin canvassing
                </p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <!-- Emanating table -->
            <div class="lg:col-span-2">
                <EmanatingTable
                    :emanatings="emanatings"
                    :selected-emanating="selectedEmanating"
                    @select-emanating="selectEmanating"
                />
            </div>

            <!-- Config panel -->
            <div>
                <CanvasSettingsPanel
                    :selected-emanating="selectedEmanating"
                    :form="form"
                    @submit="submit"
                />
            </div>
        </div>
    </div>
</template>
