<script setup>
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";

defineProps({
    fund: Object,
});
</script>

<template>
    <Card v-if="fund?.project">
        <CardHeader>
            <div class="flex items-start justify-between gap-3">
                <div class="space-y-1">
                    <CardTitle class="flex items-center gap-2">
                        <Icon icon="lucide:folder-kanban" class="h-5 w-5" />
                        Related Project Documents
                    </CardTitle>
                    <CardDescription>
                        Documents for the project linked to this emanating
                        request
                    </CardDescription>
                </div>

                <Link
                    :href="route('funds.show', fund.id)"
                    class="inline-flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm hover:bg-muted"
                >
                    <Icon icon="lucide:external-link" class="h-4 w-4" />
                    Open Fund
                </Link>
            </div>
        </CardHeader>
        <CardContent>
            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-lg border p-4 space-y-3">
                    <p class="font-medium">Work Program</p>
                    <a
                        v-if="fund.project.work_program"
                        :href="`/storage/${fund.project.work_program.file_url}`"
                        target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Icon icon="lucide:download" class="h-4 w-4" />
                        Download
                    </a>
                    <p v-else class="text-sm text-muted-foreground">
                        No document uploaded
                    </p>
                </div>

                <div class="rounded-lg border p-4 space-y-3">
                    <p class="font-medium">Project Brief</p>
                    <a
                        v-if="fund.project.project_brief"
                        :href="`/storage/${fund.project.project_brief.file_url}`"
                        target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Icon icon="lucide:download" class="h-4 w-4" />
                        Download
                    </a>
                    <p v-else class="text-sm text-muted-foreground">
                        No document uploaded
                    </p>
                </div>

                <div class="rounded-lg border p-4 space-y-3">
                    <p class="font-medium">Project Proposal</p>
                    <a
                        v-if="fund.project.project_proposal"
                        :href="`/storage/${fund.project.project_proposal.file_url}`"
                        target="_blank"
                        class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                    >
                        <Icon icon="lucide:download" class="h-4 w-4" />
                        Download
                    </a>
                    <p v-else class="text-sm text-muted-foreground">
                        No document uploaded
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
