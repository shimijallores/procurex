<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    selectedEmanating: Object,
    form: Object,
});

const emit = defineEmits(["submit"]);

const handleSubmit = () => {
    emit("submit");
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Canvas Settings</CardTitle>
        </CardHeader>
        <CardContent class="space-y-4">
            <!-- Selected emanating preview -->
            <div
                v-if="selectedEmanating"
                class="rounded-lg bg-muted p-3 text-sm space-y-1"
            >
                <p class="font-medium">
                    {{ selectedEmanating.project?.name }}
                </p>
                <p class="text-muted-foreground text-xs">
                    PR No: {{ selectedEmanating.pr_no ?? "—" }}
                </p>
                <p class="text-muted-foreground text-xs">
                    FY {{ selectedEmanating.fiscal_year }}
                </p>
            </div>
            <div
                v-else
                class="rounded-lg bg-muted p-3 text-sm text-muted-foreground"
            >
                No emanating selected
            </div>

            <p
                v-if="form?.errors?.emanating_id"
                class="text-sm text-destructive"
            >
                {{ form.errors.emanating_id }}
            </p>

            <Button
                class="w-full"
                :disabled="!form?.emanating_id || form?.processing"
                @click="handleSubmit"
            >
                <Icon icon="lucide:play" class="mr-2 h-4 w-4" />
                Start Canvassing
            </Button>
        </CardContent>
    </Card>
</template>
