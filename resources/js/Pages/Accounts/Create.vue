<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import AccountForm from "@/components/accounts/create/AccountForm.vue";

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
                    { label: "Create" },
                ],
            },
            () => page,
        ),
});
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center gap-4">
            <Link :href="route('accounts.index')">
                <Button variant="ghost" size="sm">
                    <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                    Back
                </Button>
            </Link>
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Create Account
                </h1>
                <p class="text-muted-foreground">
                    Add a new chart of account entry
                </p>
            </div>
        </div>

        <AccountForm
            action="create"
            :route="route('accounts.store')"
            :return-route="route('accounts.index')"
        />
    </div>
</template>
