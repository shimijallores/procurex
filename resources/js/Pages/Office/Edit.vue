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
                    { label: "Offices", href: route("offices.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    office: Object,
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link :href="route('offices.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Edit Office
                </h1>
                <p class="text-muted-foreground">Update office information</p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>Office Details</CardTitle>
                <CardDescription>
                    Modify the office information below
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    :action="route('offices.update', office.id)"
                    method="put"
                    class="space-y-6"
                    #default="{ errors, processing }"
                >
                    <div class="space-y-2">
                        <Label for="name">Office Name</Label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            :defaultValue="office.name"
                            placeholder="Enter office name"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.name ? 'border-destructive' : '',
                            ]"
                        />
                        <p v-if="errors.name" class="text-sm text-destructive">
                            {{ errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="code">Office Code</Label>
                        <input
                            id="code"
                            name="code"
                            type="text"
                            :defaultValue="office.code"
                            placeholder="Enter office code"
                            :class="[
                                'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm',
                                'ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium',
                                'placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2',
                                'focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                errors.code ? 'border-destructive' : '',
                            ]"
                        />
                        <p v-if="errors.code" class="text-sm text-destructive">
                            {{ errors.code }}
                        </p>
                    </div>

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
                        <Link :href="route('offices.index')">
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
