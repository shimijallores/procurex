<script setup>
import { Icon } from "@iconify/vue";
import { Label } from "@/components/ui/label";

const props = defineProps({
    xlsxFileName: String,
    errors: Object,
});

const emit = defineEmits(["file-change"]);

const handleFileChange = (event) => {
    emit("file-change", event);
};
</script>

<template>
    <div class="space-y-4 rounded-lg border p-4 bg-muted/50">
        <div class="flex items-center gap-2">
            <Icon icon="lucide:file-spreadsheet" class="h-5 w-5 text-primary" />
            <h3 class="font-semibold">Import from XLSX</h3>
        </div>

        <p class="text-sm text-muted-foreground">
            Upload an XLSX file to create the PPMP and automatically populate
            categories and items.
        </p>

        <div class="space-y-2">
            <Label for="xlsx_file"
                >XLSX File <span class="text-destructive">*</span></Label
            >
            <div
                class="rounded-lg border-2 border-dashed border-primary/30 bg-background p-4"
            >
                <input
                    id="xlsx_file"
                    type="file"
                    accept=".xlsx"
                    @change="handleFileChange"
                    :class="[
                        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                        'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                        'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                        'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                        errors?.xlsx_file ? 'border-destructive' : '',
                    ]"
                />
                <p class="mt-2 text-xs text-muted-foreground">
                    Supported format: <strong>.xlsx</strong>
                </p>
            </div>
            <p
                v-if="xlsxFileName"
                class="text-sm text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:file-check" class="h-3 w-3" />
                Selected: {{ xlsxFileName }}
            </p>
            <p v-if="errors?.xlsx_file" class="text-sm text-destructive">
                {{ errors.xlsx_file }}
            </p>
        </div>

        <div class="rounded-md bg-blue-50 dark:bg-blue-950/30 p-3">
            <div class="flex gap-2">
                <Icon
                    icon="lucide:info"
                    class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5"
                />
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">XLSX Format Tips:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Make sure to use the provided PPMP template</li>
                        <li>
                            Categories are mapped using account code and name
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
