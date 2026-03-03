<script setup>
import { ref } from "vue";
import Layout from "@/Layout/Layout.vue";
import DeleteModal from "@/components/DeleteModal.vue";
import FundShowHeader from "@/components/funds/show/FundShowHeader.vue";
import FundShowInformationCard from "@/components/funds/show/FundShowInformationCard.vue";
import FundShowMetadataCard from "@/components/funds/show/FundShowMetadataCard.vue";
import FundShowProjectDocumentsCard from "@/components/funds/show/FundShowProjectDocumentsCard.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Funds", href: route("funds.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    fund: Object,
    ppmpReference: Object,
});

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <FundShowHeader :fund="fund" @delete="() => (showDeleteModal = true)" />

        <!-- Fund Info Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <FundShowInformationCard :fund="fund" />
            <FundShowMetadataCard :fund="fund" />
        </div>

        <!-- Project Documents Card (only for project type) -->
        <FundShowProjectDocumentsCard
            :fund="fund"
            :ppmp-reference="ppmpReference"
        />

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Fund"
            :description="`Are you sure you want to delete '${fund.name}'? This action cannot be undone.`"
            :delete-url="route('funds.destroy', fund.id)"
        />
    </div>
</template>
