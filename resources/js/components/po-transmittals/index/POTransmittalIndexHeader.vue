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
    <div class="flex items-start justify-between">
        <div>
            <PageTitle title="PO Transmittal" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage COA and OPG transmittal records sourced from Purchase
                Orders.
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
            <Link :href="route('po-transmittals.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Transmittal
                </Button>
            </Link>
        </div>

        <DownloadModal
            v-model:open="showDownloadModal"
            title="Download PO Transmittal"
            description="Download PO Transmittal documents as individual PDFs in a ZIP file."
            :route-name="route('po-transmittals.download-files')"
        />
    </div>
</template>
