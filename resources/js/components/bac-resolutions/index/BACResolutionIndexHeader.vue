<script setup>
import { ref } from "vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
import DownloadPdfModal from "@/components/DownloadPdfModal.vue";

const showDownloadModal = ref(false);
</script>

<template>
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="BAC Resolutions" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage BAC resolution documents linked to AOQs.
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
            <Link :href="route('bac-resolutions.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New BAC Resolution
                </Button>
            </Link>
        </div>

        <DownloadPdfModal
            v-model:open="showDownloadModal"
            title="Download BAC Resolution PDFs"
            description="Download BAC Resolution documents as individual PDFs in a ZIP file."
            :route-name="route('bac-resolutions.download-pdfs')"
        />
    </div>
</template>
