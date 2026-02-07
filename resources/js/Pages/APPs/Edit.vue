<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Annual Procurement Plan",
                        href: route("apps.index"),
                    },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    app: Object,
    offices: Array,
});

const form = useForm({
    office_id: props.app.office_id,
    fiscal_year: props.app.fiscal_year,
});

const submit = () => {
    form.put(route("apps.update", props.app.id));
};
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('apps.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Annual Procurement Plan
                </h1>
                <p class="text-muted-foreground">
                    Update the procurement plan details
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>APP Details</CardTitle>
                <CardDescription>
                    Update the information for this procurement plan
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="space-y-2">
                        <Label for="office_id">Office</Label>
                        <select
                            id="office_id"
                            v-model="form.office_id"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                form.errors.office_id
                                    ? 'border-destructive'
                                    : '',
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
                                form.errors.fiscal_year
                                    ? 'border-destructive'
                                    : '',
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
                            <Icon
                                v-else
                                icon="lucide:save"
                                class="mr-2 h-4 w-4"
                            />
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
    </div>
</template>
