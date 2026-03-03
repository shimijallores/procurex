<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import BACResolutionShowHeader from "@/components/bac-resolutions/show/BACResolutionShowHeader.vue";
import BACResolutionShowEditor from "@/components/bac-resolutions/show/BACResolutionShowEditor.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "BAC Resolutions",
                        href: route("bac-resolutions.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    resolution: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <BACResolutionShowHeader
            :resolution="resolution"
            @delete="showDeleteModal = true"
        />

        <BACResolutionShowEditor :resolution="resolution" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete BAC Resolution"
            :description="`Are you sure you want to delete ${resolution.resolution_no}? This action cannot be undone.`"
            :delete-url="route('bac-resolutions.destroy', resolution.id)"
        />
    </div>
</template>
