<script setup>
import { ref, computed } from "vue";
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    app: Object,
    offices: Array,
    existingApps: Array,
});

const emit = defineEmits(["submit"]);

const showWarning = ref(false);

const hasExistingApp = computed(() => {
    if (!props.form.office_id || !props.form.fiscal_year) return false;
    return (props.existingApps || []).some(
        (app) =>
            String(app.office_id) === String(props.form.office_id) &&
            String(app.fiscal_year) === String(props.form.fiscal_year),
    );
});

const handleSubmit = () => {
    if (hasExistingApp.value) {
        showWarning.value = true;
    } else {
        emit("submit");
    }
};

const confirmSubmit = () => {
    showWarning.value = false;
    emit("submit");
};
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle>APP Details</CardTitle>
            <CardDescription>
                Update the information for this procurement plan
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="handleSubmit" class="space-y-6">
                <div class="space-y-2">
                    <Label for="office_id">Office</Label>
                    <select
                        id="office_id"
                        v-model="form.office_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.office_id ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select an office</option>
                        <option
                            v-for="office in offices"
                            :key="office.id"
                            :value="office.id"
                        >
                            {{ office.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.office_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.office_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="fiscal_year">Fiscal Year</Label>
                    <input
                        id="fiscal_year"
                        v-model="form.fiscal_year"
                        type="number"
                        min="2000"
                        max="2100"
                        placeholder="2026"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.fiscal_year ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="form.errors.fiscal_year"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.fiscal_year }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:save" class="mr-2 h-4 w-4" />
                        Update APP
                    </Button>
                    <Link :href="route('apps.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </CardContent>

        <!-- Warning Modal -->
        <div
            v-if="showWarning"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/45 px-4 backdrop-blur-[1px]"
            @click.self="showWarning = false"
        >
            <div
                class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-xl"
            >
                <div class="flex items-center gap-3 mb-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30"
                    >
                        <Icon
                            icon="lucide:alert-triangle"
                            class="h-5 w-5 text-amber-600 dark:text-amber-400"
                        />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">
                            Existing APP Found
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            The selected office already has an APP for fiscal
                            year {{ form.fiscal_year }}.
                        </p>
                    </div>
                </div>
                <div class="border-t my-4" />
                <p class="text-sm text-muted-foreground mb-6 text-justify">
                    Saving these changes will
                    <strong>permanently replace</strong> the existing
                    procurement plan. All categories and items will be deleted
                    and replaced. This action cannot be undone.
                </p>
                <div class="flex justify-end gap-3">
                    <Button variant="outline" @click="showWarning = false">
                        Cancel
                    </Button>
                    <Button variant="default" @click="confirmSubmit">
                        Continue & Replace
                    </Button>
                </div>
            </div>
        </div>
    </Card>
</template>
