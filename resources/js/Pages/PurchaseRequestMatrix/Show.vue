<script setup>
import Layout from "@/Layout/Layout.vue";
import PRMatrixShowHeader from "@/components/purchase-request-matrix/show/PRMatrixShowHeader.vue";
import PRMatrixShowDetails from "@/components/purchase-request-matrix/show/PRMatrixShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "PR Matrix",
                        href: route("purchase-request-matrix.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

defineProps({
    matrixRow: Object,
});
</script>

<template>
    <div class="space-y-6">
        <PRMatrixShowHeader :matrix-row="matrixRow" />
        <PRMatrixShowDetails :matrix-row="matrixRow" />
    </div>
</template>
