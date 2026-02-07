<script setup>
import { ref } from "vue";
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
import DeleteModal from "@/components/DeleteModal.vue";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Funds", href: route("funds.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    fund: Object,
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

const getFundTypeBadgeClass = (type) => {
    return type === "project"
        ? "bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300"
        : "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300";
};

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('funds.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        {{ fund.name }}
                    </h1>
                    <p class="text-muted-foreground">
                        Fund details and information
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('funds.edit', fund.id)">
                    <Button variant="outline">
                        <Icon icon="lucide:pencil" class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Button variant="destructive" @click="showDeleteModal = true">
                    <Icon icon="lucide:trash-2" class="mr-2 h-4 w-4" />
                    Delete
                </Button>
            </div>
        </div>

        <!-- Fund Info Cards -->
        <div class="grid gap-6 md:grid-cols-2">
            <!-- Details Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:wallet" class="h-5 w-5" />
                        Fund Information
                    </CardTitle>
                    <CardDescription>
                        Basic information about this fund
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Fund Code
                        </p>
                        <p class="font-medium">{{ fund.code }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Fund Name
                        </p>
                        <p class="font-medium">{{ fund.name }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Office
                        </p>
                        <p class="font-medium">{{ fund.office.name }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Type
                        </p>
                        <span
                            :class="[
                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold w-fit',
                                getFundTypeBadgeClass(fund.type),
                            ]"
                        >
                            {{
                                fund.type.charAt(0).toUpperCase() +
                                fund.type.slice(1)
                            }}
                        </span>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Fiscal Year
                        </p>
                        <p class="font-medium">{{ fund.fiscal_year }}</p>
                    </div>
                    <div v-if="fund.remarks" class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Remarks
                        </p>
                        <p class="font-medium">{{ fund.remarks }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Metadata Card -->
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:info" class="h-5 w-5" />
                        Metadata
                    </CardTitle>
                    <CardDescription>
                        Timestamps and system information
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Fund ID
                        </p>
                        <p class="font-medium">{{ fund.id }}</p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Created At
                        </p>
                        <p class="font-medium">
                            {{ formatDate(fund.created_at) }}
                        </p>
                    </div>
                    <div class="grid gap-1">
                        <p class="text-sm font-medium text-muted-foreground">
                            Last Updated
                        </p>
                        <p class="font-medium">
                            {{ formatDate(fund.updated_at) }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Project Documents Card (only for project type) -->
        <Card v-if="fund.type === 'project' && fund.project">
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:folder-kanban" class="h-5 w-5" />
                    Project Documents
                </CardTitle>
                <CardDescription>
                    Uploaded project-related documents
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-3">
                    <!-- Work Program -->
                    <div class="rounded-lg border p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10"
                            >
                                <Icon
                                    icon="lucide:file-text"
                                    class="h-5 w-5 text-blue-500"
                                />
                            </div>
                            <div>
                                <p class="font-medium">Work Program</p>
                                <p class="text-xs text-muted-foreground">
                                    PDF Document
                                </p>
                            </div>
                        </div>
                        <div v-if="fund.project.work_program">
                            <a
                                :href="`/storage/${fund.project.work_program.file_url}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                            >
                                <Icon icon="lucide:download" class="h-4 w-4" />
                                Download Document
                            </a>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            No document uploaded
                        </div>
                    </div>

                    <!-- Project Brief -->
                    <div class="rounded-lg border p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10"
                            >
                                <Icon
                                    icon="lucide:file-text"
                                    class="h-5 w-5 text-green-500"
                                />
                            </div>
                            <div>
                                <p class="font-medium">Project Brief</p>
                                <p class="text-xs text-muted-foreground">
                                    PDF Document
                                </p>
                            </div>
                        </div>
                        <div v-if="fund.project.project_brief">
                            <a
                                :href="`/storage/${fund.project.project_brief.file_url}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                            >
                                <Icon icon="lucide:download" class="h-4 w-4" />
                                Download Document
                            </a>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            No document uploaded
                        </div>
                    </div>

                    <!-- Project Proposal -->
                    <div class="rounded-lg border p-4 space-y-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/10"
                            >
                                <Icon
                                    icon="lucide:file-text"
                                    class="h-5 w-5 text-purple-500"
                                />
                            </div>
                            <div>
                                <p class="font-medium">Project Proposal</p>
                                <p class="text-xs text-muted-foreground">
                                    PDF Document
                                </p>
                            </div>
                        </div>
                        <div v-if="fund.project.project_proposal">
                            <a
                                :href="`/storage/${fund.project.project_proposal.file_url}`"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                            >
                                <Icon icon="lucide:download" class="h-4 w-4" />
                                Download Document
                            </a>
                        </div>
                        <div v-else class="text-sm text-muted-foreground">
                            No document uploaded
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Fund"
            :description="`Are you sure you want to delete '${fund.name}'? This action cannot be undone.`"
            :delete-url="route('funds.destroy', fund.id)"
        />
    </div>
</template>
