<script setup>
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";

const props = defineProps({
    rfq: Object,
});

defineEmits(["delete"]);

const openPdf = () => {
    const firstSupplier = props.rfq?.suppliers?.[0];
    const href = firstSupplier
        ? route("rfqs.pdf", {
              rfq: props.rfq.id,
              supplier_id: firstSupplier.supplier_id,
          })
        : route("rfqs.pdf", props.rfq.id);
    window.open(href, "_blank");
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('rfqs.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Request for Quotation
                        <span class="text-primary">{{ rfq.svp_no }}</span>
                    </h1>
                    <p class="text-muted-foreground mt-1">
                        {{ rfq.purchase_request?.office?.name || "—" }} —
                        {{ rfq.project_name }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-end gap-2">
                <Button variant="outline" @click="openPdf">
                    <Icon icon="lucide:printer" class="mr-2 h-4 w-4" />
                    Print RFQ
                </Button>

                <Button variant="destructive" @click="$emit('delete')">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>
    </div>
</template>
