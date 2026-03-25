<script setup>
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    poTransmittal: Object,
    relatedTransmittals: Array,
});

defineEmits(["delete"]);

const openPdf = () => {
    window.open(route("po-transmittals.pdf", props.poTransmittal.id), "_blank");
};
</script>

<template>
    <div
        class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">
                {{
                    poTransmittal.purchase_order?.po_no ||
                    `Transmittal #${poTransmittal.id}`
                }}
            </h1>
            <p class="text-sm text-muted-foreground mt-1">
                Combined COA and OPG transmittal details and print preview.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button variant="outline" @click="openPdf">
                <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                Print COA + OPG
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
