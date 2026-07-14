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
            <PageTitle title="Purchase Order" />
            <p class="text-sm text-muted-foreground mt-1">
                Manage generated Purchase Order documents.
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
            <Link :href="route('purchase-orders.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Purchase Order
                </Button>
            </Link>
        </div>

        <DownloadModal
            v-model:open="showDownloadModal"
            title="Download Purchase Order"
            description="Download Purchase Order documents as individual PDFs in a ZIP file."
            :route-name="route('purchase-orders.download-files')"
        />
    </div>
</template>
