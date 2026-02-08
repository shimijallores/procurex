<script setup>
import { ref } from "vue";
import { Icon } from "@iconify/vue";
import { Label } from "@/components/ui/label";

const props = defineProps({
    csvFileName: String,
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
            <h3 class="font-semibold">Import from CSV</h3>
        </div>

        <p class="text-sm text-muted-foreground">
            Upload a CSV file to automatically populate categories and items.
            You can also create an empty PPMP and import the CSV later.
        </p>

        <div class="space-y-2">
            <Label for="csv_file">CSV File (Optional)</Label>
            <div class="flex items-center gap-2">
                <input
                    id="csv_file"
                    type="file"
                    accept=".csv,.txt,.xlsx,.xls"
                    @change="handleFileChange"
                    :class="[
                        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                        'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                        'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                        'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                        errors?.csv_file ? 'border-destructive' : '',
                    ]"
                />
            </div>
            <p
                v-if="csvFileName"
                class="text-sm text-muted-foreground flex items-center gap-1"
            >
                <Icon icon="lucide:file-check" class="h-3 w-3" />
                Selected: {{ csvFileName }}
            </p>
            <p v-if="errors?.csv_file" class="text-sm text-destructive">
                {{ errors.csv_file }}
            </p>
        </div>

        <div class="rounded-md bg-blue-50 dark:bg-blue-950/30 p-3">
            <div class="flex gap-2">
                <Icon
                    icon="lucide:info"
                    class="h-4 w-4 text-blue-600 dark:text-blue-400 mt-0.5"
                />
                <div class="text-sm text-blue-800 dark:text-blue-300">
                    <p class="font-medium mb-1">CSV Format Tips:</p>
                    <ul class="list-disc list-inside space-y-1 text-xs">
                        <li>Make sure to use the provided PPMP template</li>
                        <li>
                            Budget validation against APP categories will be
                            performed
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</template>
