<script setup>
import { ref, computed } from "vue";
import { usePage } from "@inertiajs/vue3";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import PageTitle from "@/components/PageTitle.vue";
import DownloadModal from "@/components/DownloadModal.vue";

const user = computed(() => usePage().props.auth?.user);
const isSuperAdmin = computed(() =>
    (user.value?.roles ?? []).some((r) => r.name === "SuperAdmin"),
);

const showDownloadModal = ref(false);
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Abstract of Quotation" />
            <p class="text-muted-foreground">
                Consolidate submitted supplier quotations and determine winner
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
            <Link :href="route('batch-aoq-requests.my-requests')">
                <Button variant="outline">
                    <Icon icon="lucide:file-clock" class="mr-2 h-4 w-4" />
                    My Requests
                </Button>
            </Link>
            <Link v-if="isSuperAdmin" :href="route('batch-aoq-requests.index')">
                <Button variant="outline">
                    <Icon icon="lucide:mail-question" class="mr-2 h-4 w-4" />
                    Batch AOQ Requests
                </Button>
            </Link>
            <Link :href="route('aoqs.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Abstract of Quotation
                </Button>
            </Link>
        </div>

        <DownloadModal
            v-model:open="showDownloadModal"
            title="Download AOQ"
            description="Download AOQ documents as individual PDFs in a ZIP file."
            :route-name="route('aoqs.download-files')"
        />
    </div>
</template>
