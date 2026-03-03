<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import ProjectCodeForm from "@/components/project-codes/edit/ProjectCodeForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Project Codes",
                        href: route("project-codes.index"),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    projectCode: Object,
    offices: Array,
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('project-codes.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Project Code
                </h1>
                <p class="text-muted-foreground">
                    Update project code information
                </p>
            </div>
        </div>

        <ProjectCodeForm
            action="edit"
            :route="route('project-codes.update', props.projectCode.id)"
            :return-route="route('project-codes.index')"
            :project-code="props.projectCode"
            :offices="props.offices"
        />
    </div>
</template>
