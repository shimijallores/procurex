<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    earmark: Object,
});

defineEmits(["delete"]);

const openPdf = () => {
    window.open(route("earmarks.pdf", props.earmark.id), "_blank");
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('earmarks.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-bold tracking-tight md:text-3xl"
                        >
                            Earmark
                            <span class="text-primary"
                                >#{{ earmark.earmark_no }}</span
                            >
                        </h1>
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-300"
                        >
                            <Icon icon="lucide:check-circle" class="h-3 w-3" />
                            Issued
                        </span>
                    </div>
                    <p class="mt-1 text-muted-foreground">
                        {{ earmark.purchase_request?.office?.name || "—" }}
                        &mdash;
                        {{ earmark.fund?.name || "—" }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <!-- Edit -->
                <Link :href="route('earmarks.edit', earmark.id)">
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>

                <!-- Print PDF -->
                <Button @click="openPdf">
                    <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                    Print Certification
                </Button>

                <!-- Delete -->
                <Button variant="destructive" @click="$emit('delete')">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>
    </div>
</template>
