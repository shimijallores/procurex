<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import AOQShowHeader from "@/components/aoqs/show/AOQShowHeader.vue";
import AOQShowDetails from "@/components/aoqs/show/AOQShowDetails.vue";
import AOQShowComparison from "@/components/aoqs/show/AOQShowComparison.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Abstract of Quotation",
                        href: route("aoqs.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    aoq: Object,
    calculation: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <AOQShowHeader :aoq="aoq" @delete="showDeleteModal = true" />

        <AOQShowDetails :aoq="aoq" :calculation="calculation" />

        <AOQShowComparison :aoq="aoq" :calculation="calculation" />

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete AOQ"
            :description="`Are you sure you want to delete this AOQ for ${aoq.rfq?.svp_no}? This action cannot be undone.`"
            :delete-url="route('aoqs.destroy', aoq.id)"
        />
    </div>
</template>
