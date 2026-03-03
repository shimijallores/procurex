<script setup>
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
    ppmpReference: Object,
});
</script>

<template>
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
            <div class="grid gap-4 md:grid-cols-4">
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
                            <p class="text-xs text-muted-foreground">DOCX</p>
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
                            <p class="text-xs text-muted-foreground">DOCX</p>
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
                            <p class="text-xs text-muted-foreground">DOCX</p>
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

                <!-- PPMP Reference -->
                <div class="rounded-lg border p-4 space-y-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-500/10"
                        >
                            <Icon
                                icon="lucide:table-properties"
                                class="h-5 w-5 text-amber-500"
                            />
                        </div>
                        <div>
                            <p class="font-medium">PPMP</p>
                            <p class="text-xs text-muted-foreground">
                                Same office & fiscal year
                            </p>
                        </div>
                    </div>
                    <div v-if="ppmpReference">
                        <a
                            :href="route('ppmps.show', ppmpReference.id)"
                            class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                        >
                            <Icon icon="lucide:external-link" class="h-4 w-4" />
                            View PPMP
                        </a>
                        <div class="mt-2" v-if="ppmpReference.csv_path">
                            <a
                                :href="
                                    route(
                                        'ppmps.download-csv',
                                        ppmpReference.id,
                                    )
                                "
                                class="inline-flex items-center gap-2 text-sm text-primary hover:underline"
                            >
                                <Icon icon="lucide:download" class="h-4 w-4" />
                                Download CSV
                            </a>
                        </div>
                    </div>
                    <div v-else class="text-sm text-muted-foreground">
                        No uploaded PPMP found
                    </div>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
