<script setup>
import { ref } from "vue";
import axios from "axios";
import { Icon } from "@iconify/vue";
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogCancel,
} from "@/components/ui/alert-dialog";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: "Download Documents" },
    description: { type: String, default: "" },
    routeName: { type: String, required: true },
});

const emit = defineEmits(["update:open"]);

const dateFrom = ref("");
const dateTo = ref("");
const downloading = ref(false);

const handleDownload = async () => {
    downloading.value = true;

    try {
        const res = await axios.post(
            props.routeName,
            {
                date_from: dateFrom.value || null,
                date_to: dateTo.value || null,
            },
            { responseType: "blob" },
        );

        const disposition = res.headers?.["content-disposition"];
        let filename = `${props.title.replace(/\s+/g, "-")}.zip`;
        if (disposition) {
            const match = disposition.match(/filename="?(.+?)"?$/);
            if (match) filename = match[1];
        }

        const url = window.URL.createObjectURL(new Blob([res.data]));
        const link = document.createElement("a");
        link.href = url;
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);

        emit("update:open", false);
        dateFrom.value = "";
        dateTo.value = "";
    } catch (err) {
        //
    } finally {
        downloading.value = false;
    }
};
</script>

<template>
    <AlertDialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <AlertDialogContent
            @pointer-down-outside="emit('update:open', false)"
            @escape-key-down="emit('update:open', false)"
        >
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription>
                    {{ description || "Download all documents. Optionally filter by date range." }}
                </AlertDialogDescription>
            </AlertDialogHeader>

            <div class="space-y-4 py-2">
                <p class="text-sm text-muted-foreground">
                    Leave dates empty to download all. Specify a range to filter.
                </p>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="download_date_from">Date From</Label>
                        <input
                            id="download_date_from"
                            v-model="dateFrom"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="download_date_to">Date To</Label>
                        <input
                            id="download_date_to"
                            v-model="dateTo"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>
                </div>
            </div>

            <AlertDialogFooter>
                <AlertDialogCancel :disabled="downloading">
                    Cancel
                </AlertDialogCancel>
                <Button
                    :disabled="downloading"
                    @click="handleDownload"
                >
                    <Icon
                        v-if="downloading"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Icon
                        v-else
                        icon="lucide:download"
                        class="mr-2 h-4 w-4"
                    />
                    {{ downloading ? "Downloading..." : "Download" }}
                </Button>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template>
