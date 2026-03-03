<script setup>
import { Link } from "@inertiajs/vue3";
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

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "Accounts",
                        href: route("accounts.index"),
                    },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    account: Object,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('accounts.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ account.code }}
                    </h1>
                    <p class="text-muted-foreground">Account details</p>
                </div>
            </div>
            <Link :href="route('accounts.edit', account.id)">
                <Button variant="outline">
                    <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                    Edit
                </Button>
            </Link>
        </div>

        <Card class="max-w-3xl">
            <CardHeader>
                <CardTitle>Account Information</CardTitle>
                <CardDescription>
                    Basic information about this account
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Code
                    </p>
                    <p class="font-medium">{{ account.code }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Name
                    </p>
                    <p class="font-medium">{{ account.name }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Main Category
                    </p>
                    <p class="font-medium">{{ account.main_category }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Subcategory
                    </p>
                    <p class="font-medium">{{ account.subcategory || "—" }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Created At
                    </p>
                    <p class="font-medium">
                        {{ formatDate(account.created_at) }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Last Updated
                    </p>
                    <p class="font-medium">
                        {{ formatDate(account.updated_at) }}
                    </p>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
