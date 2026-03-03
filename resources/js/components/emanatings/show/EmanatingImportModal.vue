<script setup>
import { ref } from "vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Icon } from "@iconify/vue";

const props = defineProps({
    show: Boolean,
    processing: Boolean,
});

const emit = defineEmits(["update:show", "submit"]);

const fileInput = ref(null);
const fileName = ref("");
const selectedFile = ref(null);

const handleFileChange = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        fileName.value = file.name;
        selectedFile.value = file;
    }
};

const submit = () => {
    if (selectedFile.value) {
        emit("submit", selectedFile.value);
    }
};

const close = () => {
    emit("update:show", false);
    fileName.value = "";
    selectedFile.value = null;
    if (fileInput.value) {
        fileInput.value.value = "";
    }
};
</script>

<template>
    <Dialog :open="show" @update:open="close">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Import Emanating XLSX</DialogTitle>
                <DialogDescription>
                    Upload a new XLSX file to import emanating items. This will
                    replace existing items.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-4">
                <div>
                    <Label for="xlsx-file">XLSX File</Label>
                    <Input
                        id="xlsx-file"
                        ref="fileInput"
                        type="file"
                        accept=".xlsx"
                        @change="handleFileChange"
                        class="mt-2"
                    />
                    <p
                        v-if="fileName"
                        class="text-sm text-muted-foreground mt-2"
                    >
                        Selected: {{ fileName }}
                    </p>
                </div>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="close" :disabled="processing">
                    Cancel
                </Button>
                <Button @click="submit" :disabled="!fileName || processing">
                    <Icon
                        v-if="processing"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Icon v-else icon="lucide:upload" class="mr-2 h-4 w-4" />
                    {{ processing ? "Importing..." : "Import" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
