<script setup>
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

const props = defineProps({
    resolution: Object,
});

defineEmits(["delete"]);

const openPdf = () => {
    window.open(route("bac-resolutions.pdf", props.resolution.id), "_blank");
};

const finalize = () => {
    if (!confirm("Finalize this BAC Resolution? This will lock the batch and prevent new AOQs from being added.")) return;
    router.post(route("bac-resolutions.finalize", props.resolution.id));
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div class="flex items-center gap-4">
            <Link :href="route('bac-resolutions.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ resolution.resolution_no }}
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    <template v-if="resolution.finalized_at">
                        BAC Resolution details and print preview.
                    </template>
                    <template v-else>
                        <span class="text-amber-600 font-medium">Draft</span> — not yet finalized.
                    </template>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <template v-if="!resolution.finalized_at">
                <Link :href="route('bac-resolutions.edit', resolution.id)">
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Button @click="finalize">
                    <Icon icon="lucide:check-circle" class="mr-2 h-4 w-4" />
                    Finalize
                </Button>
            </template>
            <Button variant="outline" @click="openPdf">
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Print PDF
            </Button>
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
