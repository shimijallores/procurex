<script setup>
import { ref, watch } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { useDebounceFn } from "@vueuse/core";
import Layout from "@/Layout/Layout.vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import DeleteModal from "@/components/DeleteModal.vue";

defineOptions({
    layout: (h, page) =>
        h(Layout, { breadcrumbs: [{ label: "Accounts" }] }, () => page),
});

const props = defineProps({
    accounts: Object,
    mainCategories: Array,
    subcategories: Array,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");
const selectedMainCategory = ref(props.filters?.main_category ?? "");
const selectedSubcategory = ref(props.filters?.subcategory ?? "");

const applyFilters = useDebounceFn(() => {
    router.get(
        route("accounts.index"),
        {
            search: search.value,
            main_category: selectedMainCategory.value,
            subcategory: selectedSubcategory.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch([search, selectedMainCategory, selectedSubcategory], () =>
    applyFilters(),
);

const clearSearch = () => {
    search.value = "";
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const showDeleteModal = ref(false);
const accountToDelete = ref(null);

const openDeleteModal = (account) => {
    accountToDelete.value = account;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Accounts
                </h1>
                <p class="text-muted-foreground">
                    Manage chart of account entries
                </p>
            </div>
            <Link :href="route('accounts.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Account
                </Button>
            </Link>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Accounts</CardTitle
                    >
                    <Icon
                        icon="lucide:book-marked"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ accounts.total }}</div>
                    <p class="text-xs text-muted-foreground">
                        All chart entries
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Main Categories</CardTitle
                    >
                    <Icon
                        icon="lucide:layers"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{
                            new Set(accounts.data.map((a) => a.main_category))
                                .size
                        }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Distinct categories in view
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >With Subcategory</CardTitle
                    >
                    <Icon
                        icon="lucide:folders"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ accounts.data.filter((a) => a.subcategory).length }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Entries with subcategory
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <CardTitle>All Accounts</CardTitle>
                        <CardDescription>
                            A list of all chart of account entries
                        </CardDescription>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <select
                            v-model="selectedMainCategory"
                            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Main Categories</option>
                            <option
                                v-for="mainCategory in mainCategories"
                                :key="mainCategory"
                                :value="mainCategory"
                            >
                                {{ mainCategory }}
                            </option>
                        </select>

                        <select
                            v-model="selectedSubcategory"
                            class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="">All Subcategories</option>
                            <option
                                v-for="subcategory in subcategories"
                                :key="subcategory"
                                :value="subcategory"
                            >
                                {{ subcategory }}
                            </option>
                        </select>

                        <div class="relative w-64">
                            <Icon
                                icon="lucide:search"
                                class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search accounts..."
                                class="flex h-10 w-full rounded-md border border-input bg-background pl-9 pr-9 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            />
                            <button
                                v-if="search"
                                @click="clearSearch"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                            >
                                <Icon icon="lucide:x" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Code
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Name
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Main Category
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Subcategory
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Created
                                </th>
                                <th
                                    class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                                >
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="account in accounts.data"
                                :key="account.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle font-medium">
                                    {{ account.code }}
                                </td>
                                <td class="p-4 align-middle">
                                    {{ account.name }}
                                </td>
                                <td class="p-4 align-middle">
                                    {{ account.main_category }}
                                </td>
                                <td class="p-4 align-middle">
                                    {{ account.subcategory || "—" }}
                                </td>
                                <td
                                    class="p-4 align-middle text-muted-foreground"
                                >
                                    {{ formatDate(account.created_at) }}
                                </td>
                                <td class="p-4 align-middle text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'accounts.show',
                                                    account.id,
                                                )
                                            "
                                        >
                                            <Button variant="ghost" size="sm">
                                                <Icon
                                                    icon="lucide:eye"
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </Link>
                                        <Link
                                            :href="
                                                route(
                                                    'accounts.edit',
                                                    account.id,
                                                )
                                            "
                                        >
                                            <Button variant="ghost" size="sm">
                                                <Icon
                                                    icon="lucide:pencil"
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </Link>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openDeleteModal(account)"
                                        >
                                            <Icon
                                                icon="lucide:trash-2"
                                                class="h-4 w-4 text-destructive"
                                            />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="accounts.data.length === 0">
                                <td colspan="6" class="p-8 text-center">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Icon
                                            icon="lucide:inbox"
                                            class="h-12 w-12 text-muted-foreground/50"
                                        />
                                        <p class="text-muted-foreground">
                                            No accounts found
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t pt-4"
                >
                    <div class="text-sm text-muted-foreground">
                        Showing {{ accounts.from }} to {{ accounts.to }} of
                        {{ accounts.total }} accounts
                    </div>
                    <div class="flex items-center gap-1">
                        <template
                            v-for="(link, index) in accounts.links"
                            :key="index"
                        >
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                    link.label.includes('Previous') ||
                                    link.label.includes('Next')
                                        ? 'px-3'
                                        : 'w-9',
                                    link.active
                                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                                        : 'hover:bg-accent hover:text-accent-foreground',
                                ]"
                                preserve-scroll
                                v-html="link.label"
                            />
                            <span
                                v-else
                                :class="[
                                    'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium',
                                    link.label.includes('Previous') ||
                                    link.label.includes('Next')
                                        ? 'px-3'
                                        : 'w-9',
                                    'pointer-events-none opacity-50',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>

        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Account"
            :description="`Are you sure you want to delete '${accountToDelete?.code} - ${accountToDelete?.name}'? This action cannot be undone.`"
            :delete-url="
                accountToDelete
                    ? route('accounts.destroy', accountToDelete.id)
                    : ''
            "
        />
    </div>
</template>
