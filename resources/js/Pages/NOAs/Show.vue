<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import NOAShowHeader from "@/components/noas/show/NOAShowHeader.vue";
import NOAShowDetails from "@/components/noas/show/NOAShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Notice of Award", href: route("noas.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

defineProps({
    noa: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <NOAShowHeader :noa="noa" @delete="showDeleteModal = true" />

        <NOAShowDetails :noa="noa" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Notice of Award"
            :description="`Are you sure you want to delete NOA ${noa.noa_no}? This action cannot be undone.`"
            :delete-url="route('noas.destroy', noa.id)"
        />
    </div>
</template>
