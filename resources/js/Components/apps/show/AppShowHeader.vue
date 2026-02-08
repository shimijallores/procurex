<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

defineProps({
    app: Object,
});

defineEmits(["import", "delete"]);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <Link :href="route('apps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    {{ app.office?.name || "Unknown Office" }} -
                    {{ app.fiscal_year }}
                </h1>
                <p class="text-muted-foreground">
                    Annual Procurement Plan details
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a
                v-if="app.uploaded_file"
                :href="route('apps.download', app.id)"
                target="_blank"
            >
                <Button variant="outline">
                    <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                    Download File
                </Button>
            </a>
            <Button variant="outline" @click="$emit('import')">
                <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                Import CSV
            </Button>
            <Link :href="route('apps.edit', app.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
            <Button variant="destructive" @click="$emit('delete')">
                <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                Delete
            </Button>
        </div>
    </div>
</template>
