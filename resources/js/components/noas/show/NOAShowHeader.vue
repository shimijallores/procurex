<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

const props = defineProps({
    noa: Object,
});

defineEmits(["delete"]);

const openPdf = () => {
    window.open(route("noas.pdf", props.noa.id), "_blank");
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ noa.noa_no }}
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Notice of Award details and print preview.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button variant="outline" @click="openPdf">
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Print PDF
            </Button>
            <Link :href="route('noas.edit', noa.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
            <Button
                variant="ghost"
                class="text-destructive hover:text-destructive"
                @click="$emit('delete')"
            >
                <Icon icon="lucide:trash-2" class="h-4 w-4" />
            </Button>
        </div>
    </div>
</template>
