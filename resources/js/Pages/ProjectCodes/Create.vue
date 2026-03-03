<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import ProjectCodeForm from "@/components/project-codes/create/ProjectCodeForm.vue";

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
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
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
                    Create Project Code
                </h1>
                <p class="text-muted-foreground">
                    Add a new project code to the system
                </p>
            </div>
        </div>

        <ProjectCodeForm
            action="create"
            :route="route('project-codes.store')"
            :return-route="route('project-codes.index')"
            :offices="props.offices"
        />
    </div>
</template>
