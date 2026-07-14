<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    aoq: Object,
});

defineEmits(["delete"]);

const openExport = () => {
    window.open(route("aoqs.export", props.aoq.id), "_blank");
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('aoqs.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="min-w-0 break-words">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Abstract of Quotation
                        <span class="text-primary">{{ aoq.rfq?.svp_no }}</span>
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ aoq.rfq?.project_name }}
                    </p>
                </div>
            </div>

            <div class="flex shrink-0 items-center justify-end gap-2">
                <Button variant="outline" @click="openExport">
                    <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                    Download
                </Button>
                <Button variant="destructive" @click="$emit('delete')">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>
    </div>
</template>
