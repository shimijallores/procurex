<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import EarmarkShowHeader from "@/components/earmarks/show/EarmarkShowHeader.vue";
import EarmarkShowDetails from "@/components/earmarks/show/EarmarkShowDetails.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Budgeting", href: route("earmarks.index") },
                    { label: "Earmark Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    earmark: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <EarmarkShowHeader
            :earmark="earmark"
            @delete="showDeleteModal = true"
        />

        <EarmarkShowDetails :earmark="earmark" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Earmark"
            :description="`Are you sure you want to delete Earmark ${earmark.earmark_no}? This will revert the linked PR back to budget review status.`"
            :delete-url="route('earmarks.destroy', earmark.id)"
        />
    </div>
</template>
