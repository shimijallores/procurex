<script setup>
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
import PPMPCreateCSVUpload from "./PPMPCreateCSVUpload.vue";

const props = defineProps({
    form: Object,
    offices: Array,
    projects: Array,
    csvFileName: String,
});

const emit = defineEmits(["submit", "file-change"]);

const handleSubmit = () => {
    emit("submit");
};

const handleFileChange = (event) => {
    emit("file-change", event);
};
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>PPMP Details</CardTitle>
            <CardDescription>
                Enter the information for the new procurement plan
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
                    <Label for="project_id">Project</Label>
                    <select
                        id="project_id"
                        v-model="form.project_id"
                        :class="[
                            'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.project_id ? 'border-destructive' : '',
                        ]"
                    >
                        <option value="">Select a project</option>
                        <option
                            v-for="project in projects"
                            :key="project.id"
                            :value="project.id"
                        >
                            {{ project.name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors.project_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.project_id }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label for="account_code">Account Code</Label>
                        <input
                            id="account_code"
                            v-model="form.account_code"
                            type="text"
                            placeholder="e.g., 5-02-02"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.account_code
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.account_code"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.account_code }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="project_code">Project Code</Label>
                        <input
                            id="project_code"
                            v-model="form.project_code"
                            type="text"
                            placeholder="e.g., PROJ-2026"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.project_code
                                    ? 'border-destructive'
                                    : '',
                            ]"
                        />
                        <p
                            v-if="form.errors.project_code"
                            class="text-sm text-destructive"
                        >
                            {{ form.errors.project_code }}
                        </p>
                    </div>
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

                <div class="flex items-center space-x-2">
                    <input
                        id="is_addendum"
                        v-model="form.is_addendum"
                        type="checkbox"
                        class="h-4 w-4 rounded border border-input"
                    />
                    <Label
                        for="is_addendum"
                        class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
                    >
                        This is an addendum
                    </Label>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks (Optional)</Label>
                    <textarea
                        id="remarks"
                        v-model="form.remarks"
                        rows="3"
                        placeholder="Any additional notes or remarks..."
                        :class="[
                            'flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                            'ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                            'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            form.errors.remarks ? 'border-destructive' : '',
                        ]"
                    />
                    <p
                        v-if="form.errors.remarks"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.remarks }}
                    </p>
                </div>

                <!-- CSV Upload Section -->
                <PPMPCreateCSVUpload
                    :csv-file-name="csvFileName"
                    :errors="form.errors"
                    @file-change="handleFileChange"
                />

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">
                        <Icon
                            v-if="form.processing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        <Icon v-else icon="lucide:plus" class="mr-2 h-4 w-4" />
                        Create PPMP
                    </Button>
                    <Link :href="route('ppmps.index')">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                    </Link>
                </div>
            </form>
        </CardContent>
    </Card>
</template>
