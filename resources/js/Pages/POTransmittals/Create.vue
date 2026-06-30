<script setup>
import Layout from "@/Layout/Layout.vue";
import POTransmittalCreateHeader from "@/components/po-transmittals/create/POTransmittalCreateHeader.vue";
import POTransmittalCreateForm from "@/components/po-transmittals/create/POTransmittalCreateForm.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "PO Transmittal",
                        href: route("po-transmittals.index"),
                    },
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({});
</script>

<template>
    <div class="space-y-6">
        <POTransmittalCreateHeader />

        <POTransmittalCreateForm />
    </div>
</template>
