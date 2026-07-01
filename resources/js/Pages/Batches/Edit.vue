<script setup>
import { Link, Form } from "@inertiajs/vue3";
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
                    { label: "Batches", href: route("batches.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    batch: Object,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('batches.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Batch
                </h1>
                <p class="text-muted-foreground">
                    Update the batch number
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Batch Details</CardTitle>
                <CardDescription>
                    Modify the batch number below
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    :action="route('batches.update', batch.id)"
                    method="put"
                    class="space-y-6"
                    #default="{ errors, processing }"
                >
                    <div class="space-y-2">
                        <Label for="batch_no">Batch Number</Label>
                        <input
                            id="batch_no"
                            name="batch_no"
                            type="text"
                            :defaultValue="batch.batch_no"
                            placeholder="Enter batch number"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.batch_no ? 'border-destructive' : '',
                            ]"
                        />
                        <p
                            v-if="errors.batch_no"
                            class="text-sm text-destructive"
                        >
                            {{ errors.batch_no }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-2">
                            <Label for="earmark_date_from">
                                Earmark Date From
                            </Label>
                            <input
                                id="earmark_date_from"
                                name="earmark_date_from"
                                type="date"
                                :defaultValue="batch.earmark_date_from?.slice(0, 10)"
                                :class="[
                                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-ring focus-visible:ring-offset-2',
                                    errors.earmark_date_from ? 'border-destructive' : '',
                                ]"
                            />
                            <p
                                v-if="errors.earmark_date_from"
                                class="text-sm text-destructive"
                            >
                                {{ errors.earmark_date_from }}
                            </p>
                        </div>
                        <div class="space-y-2">
                            <Label for="earmark_date_to">
                                Earmark Date To
                            </Label>
                            <input
                                id="earmark_date_to"
                                name="earmark_date_to"
                                type="date"
                                :defaultValue="batch.earmark_date_to?.slice(0, 10)"
                                :class="[
                                    'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                    'ring-offset-background focus-visible:outline-none focus-visible:ring-2',
                                    'focus-visible:ring-ring focus-visible:ring-offset-2',
                                    errors.earmark_date_to ? 'border-destructive' : '',
                                ]"
                            />
                            <p
                                v-if="errors.earmark_date_to"
                                class="text-sm text-destructive"
                            >
                                {{ errors.earmark_date_to }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <input
                            id="is_locked"
                            name="is_locked"
                            type="checkbox"
                            value="1"
                            :checked="batch.is_locked"
                            class="h-4 w-4 rounded border-input text-primary focus:ring-primary"
                        />
                        <Label for="is_locked" class="cursor-pointer">
                            Lock this batch
                        </Label>
                    </div>
                    <p
                        v-if="errors.is_locked"
                        class="text-sm text-destructive"
                    >
                        {{ errors.is_locked }}
                    </p>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="processing">
                            <Icon
                                v-if="processing"
                                icon="lucide:loader-2"
                                class="mr-2 h-4 w-4 animate-spin"
                            />
                            <Icon
                                v-else
                                icon="lucide:save"
                                class="mr-2 h-4 w-4"
                            />
                            Save Changes
                        </Button>
                        <Link :href="route('batches.index')">
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </Link>
                    </div>
                </Form>
            </CardContent>
        </Card>
    </div>
</template>
