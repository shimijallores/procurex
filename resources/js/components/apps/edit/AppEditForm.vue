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

defineProps({
    form: Object,
    app: Object,
    offices: Array,
});

defineEmits(["submit"]);
</script>

<template>
    <Card class="max-w-2xl">
        <CardHeader>
            <CardTitle>APP Details</CardTitle>
            <CardDescription>
                Update the information for this procurement plan
            </CardDescription>
        </CardHeader>
        <CardContent>
            <form @submit.prevent="$emit('submit')" class="space-y-6">
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
    </Card>
</template>
