<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Button } from "@/components/ui/button";
import AccountForm from "@/components/accounts/edit/AccountForm.vue";

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
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    account: Object,
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
                    Edit Account
                </h1>
                <p class="text-muted-foreground">
                    Update chart of account information
                </p>
            </div>
        </div>

        <AccountForm
            action="edit"
            :route="route('accounts.update', props.account.id)"
            :return-route="route('accounts.index')"
            :account="props.account"
        />
    </div>
</template>
