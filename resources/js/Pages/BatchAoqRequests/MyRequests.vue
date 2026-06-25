<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import Layout from "@/Layout/Layout.vue";
import PageTitle from "@/components/PageTitle.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    {
                        label: "My AOQ Requests",
                        href: route("batch-aoq-requests.my-requests"),
                    },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    requests: Object,
});

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const statusBadge = (status) => {
    if (status === "pending") return "bg-amber-100 text-amber-800";
    if (status === "approved") return "bg-green-100 text-green-800";
    return "bg-red-100 text-red-800";
};
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3">
                    <Link :href="route('aoqs.index')">
                        <Button variant="ghost" size="sm">
                            <Icon icon="lucide:arrow-left" class="mr-2 h-4 w-4" />
                            Back
                        </Button>
                    </Link>
                    <PageTitle title="My Batch AOQ Requests" />
                </div>
                <p class="text-sm text-muted-foreground mt-1">
                    Track your requests to add AOQs to locked batches.
                </p>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>My Requests</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Batch</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Reason</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Submitted</th>
                                <th class="h-12 px-3 text-center align-middle font-medium text-muted-foreground">Status</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Response</th>
                            </tr>
                        </thead>
                        <tbody class="[&_tr:last-child]:border-0">
                            <tr
                                v-for="request in requests.data"
                                :key="request.id"
                                class="border-b transition-colors hover:bg-muted/50"
                            >
                                <td class="p-3 align-middle font-medium">
                                    {{ request.batch?.batch_no || "—" }}
                                </td>
                                <td class="p-3 align-middle max-w-xs truncate">
                                    {{ request.reason || "—" }}
                                </td>
                                <td class="p-3 align-middle text-muted-foreground">
                                    {{ formatDate(request.created_at) }}
                                </td>
                                <td class="p-3 align-middle text-center">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :class="statusBadge(request.status)"
                                    >
                                        {{ request.status }}
                                    </span>
                                </td>
                                <td class="p-3 align-middle text-muted-foreground">
                                    <template v-if="request.status === 'approved'">
                                        Approved by {{ request.approver?.name || "—" }}
                                    </template>
                                    <template v-else-if="request.status === 'rejected'">
                                        {{ request.rejection_reason || "Rejected" }}
                                    </template>
                                    <template v-else>—</template>
                                </td>
                            </tr>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="5" class="p-8 text-center text-muted-foreground">
                                    You have no batch AOQ requests.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 flex items-center justify-between border-t pt-4">
                    <div class="text-sm text-muted-foreground">
                        Showing {{ requests.from }} to {{ requests.to }} of
                        {{ requests.total }} entries
                    </div>
                    <div class="flex items-center gap-1">
                        <template v-for="(link, index) in requests.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="[
                                    'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors',
                                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                                    link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : 'w-9',
                                    link.active ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'hover:bg-accent hover:text-accent-foreground',
                                ]"
                                preserve-state
                                v-html="link.label"
                            />
                            <span
                                v-else
                                :class="[
                                    'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium',
                                    link.label.includes('Previous') || link.label.includes('Next') ? 'px-3' : 'w-9',
                                    'pointer-events-none opacity-50',
                                ]"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
