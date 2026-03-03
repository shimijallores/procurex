<script setup>
import { ref } from "vue";
import { Icon } from "@iconify/vue";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    open: Boolean,
    ppmpId: Number,
    processing: Boolean,
    errors: Object,
});

const emit = defineEmits(["update:open", "submit"]);

const xlsxFile = ref(null);
const xlsxFileName = ref("");

const handleFileChange = (event) => {
    const file = event.target.files[0];
    xlsxFile.value = file;
    xlsxFileName.value = file ? file.name : "";
};

const submitImport = () => {
    if (xlsxFile.value) {
        emit("submit", xlsxFile.value);
    }
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 bg-background/80 backdrop-blur-sm"
        @click="emit('update:open', false)"
    >
        <div
            class="fixed left-1/2 top-1/2 z-50 w-full max-w-lg -translate-x-1/2 -translate-y-1/2"
            @click.stop
        >
            <Card>
                <CardHeader>
                    <CardTitle>Import XLSX Data</CardTitle>
                    <CardDescription>
                        Upload an XLSX file to import categories and items
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form @submit.prevent="submitImport" class="space-y-4">
                        <div class="space-y-2">
                            <Label for="import_xlsx">XLSX File</Label>
                            <input
                                id="import_xlsx"
                                type="file"
                                accept=".csv,.txt,.xlsx,.xls"
                                @change="handleFileChange"
                                :class="[
                                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                    'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-ring focus-visible:ring-offset-2',
                                    errors?.xlsx_file
                                        ? 'border-destructive'
                                        : '',
                                ]"
                            />
                            <p
                                v-if="xlsxFileName"
                                class="text-sm text-muted-foreground flex items-center gap-1"
                            >
                                <Icon
                                    icon="lucide:file-check"
                                    class="h-3 w-3"
                                />
                                Selected: {{ xlsxFileName }}
                            </p>
                            <p
                                v-if="errors?.xlsx_file"
                                class="text-sm text-destructive"
                            >
                                {{ errors.xlsx_file }}
                            </p>
                        </div>

                        <div
                            class="rounded-md bg-yellow-50 dark:bg-yellow-950/30 p-3"
                        >
                            <div class="flex gap-2">
                                <Icon
                                    icon="lucide:alert-triangle"
                                    class="h-4 w-4 text-yellow-600 dark:text-yellow-400 mt-0.5"
                                />
                                <p
                                    class="text-sm text-yellow-800 dark:text-yellow-300"
                                >
                                    This will delete all existing categories and
                                    items, then import the new data.
                                </p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                @click="emit('update:open', false)"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="processing || !xlsxFile"
                            >
                                <Icon
                                    v-if="processing"
                                    icon="lucide:loader-2"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                <Icon
                                    v-else
                                    icon="lucide:upload"
                                    class="mr-2 h-4 w-4"
                                />
                                Import
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
