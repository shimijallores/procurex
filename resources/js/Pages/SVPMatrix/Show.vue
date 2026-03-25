<script setup>
import Layout from "@/Layout/Layout.vue";
import SVPMatrixShowHeader from "@/components/svp-matrix/show/SVPMatrixShowHeader.vue";
import SVPMatrixShowDetails from "@/components/svp-matrix/show/SVPMatrixShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "SVP Matrix",
                        href: route("svp-matrix.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    matrixRow: Object,
});
</script>

<template>
    <div class="space-y-6">
        <SVPMatrixShowHeader :matrix-row="matrixRow" />
        <SVPMatrixShowDetails :matrix-row="matrixRow" />
    </div>
</template>
