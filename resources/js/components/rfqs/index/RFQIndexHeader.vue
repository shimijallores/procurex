<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
import DownloadModal from "@/components/DownloadModal.vue";

const showDownloadModal = ref(false);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Request for Quotation" />
            <p class="text-muted-foreground">
                Manage SVP RFQs from approved purchase requests
            </p>
        </div>
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                @click="showDownloadModal = true"
            >
                <Icon icon="lucide:download" class="mr-2 h-4 w-4" />
                Download
            </Button>
            <Link :href="route('rfqs.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Request for Quotation
                </Button>
            </Link>
        </div>

        <DownloadModal
            v-model:open="showDownloadModal"
            title="Download RFQ"
            description="Download RFQ documents as individual PDFs in a ZIP file."
            :route-name="route('rfqs.download-files')"
        />
    </div>
</template>
