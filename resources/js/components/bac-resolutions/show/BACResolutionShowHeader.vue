<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import ConfirmModal from "@/components/ConfirmModal.vue";

const props = defineProps({
    resolution: Object,
});

defineEmits(["delete"]);

const showFinalizeModal = ref(false);
const showRegenerateModal = ref(false);
const finalizing = ref(false);
const regenerating = ref(false);

const openExport = () => {
    window.open(route("bac-resolutions.export", props.resolution.id), "_blank");
};

const confirmFinalize = () => {
    finalizing.value = true;
    router.post(route("bac-resolutions.finalize", props.resolution.id), {}, {
        onFinish: () => {
            finalizing.value = false;
            showFinalizeModal.value = false;
        },
    });
};

const confirmRegenerate = () => {
    regenerating.value = true;
    router.post(route("bac-resolutions.regenerate", props.resolution.id), {}, {
        onFinish: () => {
            regenerating.value = false;
            showRegenerateModal.value = false;
        },
    });
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
                <Button @click="showFinalizeModal = true">
                    <Icon icon="lucide:check-circle" class="mr-2 h-4 w-4" />
                    Finalize
                </Button>
            </template>
            <Button v-if="resolution.finalized_at" variant="outline" @click="showRegenerateModal = true">
                <Icon icon="lucide:refresh-cw" class="mr-2 h-4 w-4" />
                Regenerate
            </Button>
            <Button variant="outline" @click="openExport">
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Download
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

    <ConfirmModal
        v-model:open="showFinalizeModal"
        title="Finalize BAC Resolution"
        description="This will lock the batch and prevent new AOQs from being added. Continue?"
        confirm-text="Finalize"
        :loading="finalizing"
        @confirm="confirmFinalize"
    />

    <ConfirmModal
        v-model:open="showRegenerateModal"
        title="Regenerate BAC Resolution"
        description="This will re-sync all AOQs from the batch and update the PDF. Continue?"
        confirm-text="Regenerate"
        :loading="regenerating"
        @confirm="confirmRegenerate"
    />
</template>
