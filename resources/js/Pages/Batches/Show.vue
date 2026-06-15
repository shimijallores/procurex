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
                    { label: "Batches", href: route("batches.index") },
                    { label: "Details" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    batch: Object,
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

const showDeleteModal = ref(false);
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <Link :href="route('batches.index')">
                    <Button variant="ghost" size="sm">
                        <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <div class="space-y-1">
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">
                        Batch {{ batch.batch_no }}
                    </h1>
                    <p class="text-muted-foreground">
                        Batch details and associated AOQs
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="route('batches.edit', batch.id)">
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

        <!-- Batch Info Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:layers" class="h-5 w-5" />
                    Batch Information
                </CardTitle>
                <CardDescription>
                    Details about this batch
                </CardDescription>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Batch Number
                    </p>
                    <p class="font-medium text-lg">{{ batch.batch_no }}</p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Created At
                    </p>
                    <p class="font-medium">
                        {{ formatDate(batch.created_at) }}
                    </p>
                </div>
                <div class="grid gap-1">
                    <p class="text-sm font-medium text-muted-foreground">
                        Last Updated
                    </p>
                    <p class="font-medium">
                        {{ formatDate(batch.updated_at) }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <!-- AOQs Card -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Icon icon="lucide:file-spreadsheet" class="h-5 w-5" />
                    Associated AOQs
                </CardTitle>
                <CardDescription>
                    Abstracts of Quotation under this batch
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="batch.aoqs.length > 0" class="relative w-full overflow-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr class="border-b transition-colors hover:bg-muted/50">
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    AOQ Date
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    SVP No.
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Project Name
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Office
                                </th>
                                <th class="h-12 px-4 text-left align-middle font-medium text-muted-foreground">
                                    Winner Supplier
                                </th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="aoq in batch.aoqs"
                                :key="aoq.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle">
                                    {{ aoq.aoq_date ? formatDate(aoq.aoq_date) : '—' }}
                                </td>
                                <td class="p-4 align-middle font-medium">
                                    {{ aoq.rfq?.svp_no ?? '—' }}
                                </td>
                                <td class="p-4 align-middle">
                                    <Link
                                        v-if="aoq.id"
                                        :href="route('aoqs.show', aoq.id)"
                                        class="font-medium text-primary hover:text-primary/80 hover:underline"
                                    >
                                        {{ aoq.rfq?.project_name ?? '—' }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="p-4 align-middle">
                                    {{ aoq.rfq?.purchase_request?.office?.name ?? '—' }}
                                </td>
                                <td class="p-4 align-middle">
                                    {{ aoq.winner_supplier?.name ?? '—' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="flex flex-col items-center gap-2 py-8">
                    <Icon icon="lucide:inbox" class="h-12 w-12 text-muted-foreground/50" />
                    <p class="text-muted-foreground">No AOQs assigned to this batch</p>
                </div>
            </CardContent>
        </Card>

        <!-- Delete Confirmation Modal -->
        <DeleteModal
            v-model:open="showDeleteModal"
            title="Delete Batch"
            :description="`Are you sure you want to delete batch '${batch.batch_no}'? This action cannot be undone.`"
            :delete-url="route('batches.destroy', batch.id)"
        />
    </div>
</template>
