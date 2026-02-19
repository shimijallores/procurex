<script setup>
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";

const props = defineProps({
    resolution: Object,
    saving: Boolean,
});

defineEmits(["save", "finalize", "delete"]);

const canEdit = computed(() => !props.resolution?.finalized_at);

const openPdf = () => {
    window.open(route("bac-resolutions.pdf", props.resolution.id), "_blank");
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{ resolution.resolution_no }}
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                BAC Resolution document editor and print preview.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button variant="outline" @click="openPdf">
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Print PDF
            </Button>

            <Button
                v-if="canEdit"
                variant="outline"
                :disabled="saving"
                @click="$emit('save')"
            >
                <Icon icon="lucide:save" class="mr-2 h-4 w-4" />
                Save
            </Button>

            <Button
                v-if="canEdit"
                :disabled="saving"
                @click="$emit('finalize')"
            >
                <Icon icon="lucide:check-check" class="mr-2 h-4 w-4" />
                Finalize
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
