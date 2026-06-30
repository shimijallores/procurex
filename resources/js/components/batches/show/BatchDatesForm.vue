<script setup>
import { ref } from "vue";
import axios from "axios";
import { route } from "ziggy-js";
import { toast } from "vue-sonner";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    batch: Object,
});

const emit = defineEmits(["saved"]);

const toDateInput = (date) => {
    if (!date) return "";
    if (typeof date === "string" && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }
    const d = new Date(date);
    if (isNaN(d.getTime())) return "";
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    return `${d.getFullYear()}-${mm}-${dd}`;
};

const saving = ref(false);
const dates = ref({
    bac_date: toDateInput(props.batch.bac_date),
    noa_date: toDateInput(props.batch.noa_date),
    po_date: toDateInput(props.batch.po_date),
});

const dateFields = [
    { key: "bac_date", label: "BAC Resolution Date", description: "Default date for BAC Resolution" },
    { key: "noa_date", label: "NOA Date", description: "Default date for Notice of Award" },
    { key: "po_date", label: "PO Date", description: "Default date for Purchase Order" },
];

const saveDates = async () => {
    saving.value = true;
    try {
        const payload = {};
        for (const field of dateFields) {
            payload[field.key] = dates.value[field.key] || null;
        }
        const { data } = await axios.put(
            route("batches.update-dates", props.batch.id),
            payload,
        );
        toast.success("Dates saved successfully");
        emit("saved", data.batch);
    } catch {
        toast.error("Failed to save dates");
    } finally {
        saving.value = false;
    }
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2">
                <Icon icon="lucide:calendar" class="h-5 w-5" />
                Document Schedule Dates
            </CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <p class="text-sm text-muted-foreground">
                Set the target dates for each document under this batch. These will be used as default
                dates when creating the respective documents. You can still edit them before submission.
            </p>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div v-for="field in dateFields" :key="field.key" class="space-y-1.5">
                    <Label :for="field.key">{{ field.label }}</Label>
                    <input
                        :id="field.key"
                        v-model="dates[field.key]"
                        type="date"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                </div>
            </div>

            <div class="flex justify-end">
                <Button :disabled="saving" @click="saveDates">
                    <Icon
                        v-if="saving"
                        icon="lucide:loader-2"
                        class="mr-2 h-4 w-4 animate-spin"
                    />
                    <Icon v-else icon="lucide:save" class="mr-2 h-4 w-4" />
                    Save Dates
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
