<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
import DownloadPdfModal from "@/components/DownloadPdfModal.vue";

const showDownloadModal = ref(false);
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="Notice of Award" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage generated Notice of Award documents.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                @click="showDownloadModal = true"
            >
                <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                Download PDFs
            </Button>
            <Link :href="route('noas.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Notice of Award
                </Button>
            </Link>
        </div>

        <DownloadPdfModal
            v-model:open="showDownloadModal"
            title="Download NOA PDFs"
            description="Download NOA documents as individual PDFs in a ZIP file."
            :route-name="route('noas.download-pdfs')"
        />
    </div>
</template>
