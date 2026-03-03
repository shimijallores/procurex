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
        h(Layout, { breadcrumbs: [{ label: "Project Codes" }] }, () => page),
});

const props = defineProps({
    projectCodes: Object,
    filters: Object,
});

const search = ref(props.filters?.search ?? "");

const debouncedSearch = useDebounceFn(() => {
    router.get(
        route("project-codes.index"),
        { search: search.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}, 300);

watch(search, () => {
    debouncedSearch();
});

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
const projectCodeToDelete = ref(null);

const openDeleteModal = (projectCode) => {
    projectCodeToDelete.value = projectCode;
    showDeleteModal.value = true;
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                    Project Codes
                </h1>
                <p class="text-muted-foreground">
                    Manage all office project code references
                </p>
            </div>
            <Link :href="route('project-codes.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Project Code
                </Button>
            </Link>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Total Project Codes</CardTitle
                    >
                    <Icon
                        icon="lucide:tags"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ projectCodes.total }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Across all offices
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Shown This Page</CardTitle
                    >
                    <Icon
                        icon="lucide:list"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{ projectCodes.data.length }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Current page records
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader
                    class="flex flex-row items-center justify-between space-y-0 pb-2"
                >
                    <CardTitle class="text-sm font-medium"
                        >Referenced Offices</CardTitle
                    >
                    <Icon
                        icon="lucide:building-2"
                        class="h-4 w-4 text-muted-foreground"
                    />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">
                        {{
                            new Set(projectCodes.data.map((p) => p.office_id))
                                .size
                        }}
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Distinct offices in view
                    </p>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div>
                        <CardTitle>All Project Codes</CardTitle>
                        <CardDescription>
                            A list of all project codes in the system
                        </CardDescription>
                    </div>
                    <div class="relative w-72">
                        <Icon
                            icon="lucide:search"
                            class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search code, name, office..."
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
                                    Office
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
                                v-for="projectCode in projectCodes.data"
                                :key="projectCode.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle font-medium">
                                    {{ projectCode.code }}
                                </td>
                                <td class="p-4 align-middle">
                                    {{ projectCode.name }}
                                </td>
                                <td class="p-4 align-middle">
                                    <div class="text-sm font-medium">
                                        {{ projectCode.office?.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ projectCode.office?.code }}
                                    </div>
                                </td>
                                <td
                                    class="p-4 align-middle text-muted-foreground"
                                >
                                    {{ formatDate(projectCode.created_at) }}
                                </td>
                                <td class="p-4 align-middle text-right">
                                    <div
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <Link
                                            :href="
                                                route(
                                                    'project-codes.show',
                                                    projectCode.id,
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
                                                    'project-codes.edit',
                                                    projectCode.id,
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
                                            @click="
                                                openDeleteModal(projectCode)
                                            "
                                        >
                                            <Icon
                                                icon="lucide:trash-2"
                                                class="h-4 w-4 text-destructive"
                                            />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="projectCodes.data.length === 0">
                                <td colspan="5" class="p-8 text-center">
                                    <div
                                        class="flex flex-col items-center gap-2"
                                    >
                                        <Icon
                                            icon="lucide:tags"
                                            class="h-12 w-12 text-muted-foreground/50"
                                        />
                                        <p class="text-muted-foreground">
                                            No project codes found
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
                        Showing {{ projectCodes.from }} to
                        {{ projectCodes.to }} of
                        {{ projectCodes.total }} project codes
                    </div>
                    <div class="flex items-center gap-1">
                        <template
                            v-for="(link, index) in projectCodes.links"
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
            title="Delete Project Code"
            :description="`Are you sure you want to delete '${projectCodeToDelete?.code} - ${projectCodeToDelete?.name}'? This action cannot be undone.`"
            :delete-url="
                projectCodeToDelete
                    ? route('project-codes.destroy', projectCodeToDelete.id)
                    : ''
            "
        />
    </div>
</template>
