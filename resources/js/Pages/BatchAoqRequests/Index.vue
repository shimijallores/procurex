<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
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
                        label: "Batch AOQ Requests",
                        href: route("batch-aoq-requests.index"),
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

const approving = ref(null);
const rejecting = ref(null);
const rejectReason = ref("");
const showRejectModal = ref(false);
const rejectTarget = ref(null);

const approve = async (request) => {
    if (!confirm("Approve this request? The AOQ will be created and assigned to the batch.")) return;
    approving.value = request.id;
    try {
        router.post(route("batch-aoq-requests.approve", request.id), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success("Request approved — AOQ created."),
            onError: () => toast.error("Failed to approve request."),
            onFinish: () => { approving.value = null; },
        });
    } catch {
        approving.value = null;
    }
};

const openReject = (request) => {
    rejectTarget.value = request;
    rejectReason.value = "";
    showRejectModal.value = true;
};

const submitReject = () => {
    if (!rejectTarget.value) return;
    rejecting.value = rejectTarget.value.id;
    router.post(route("batch-aoq-requests.reject", rejectTarget.value.id), {
        rejection_reason: rejectReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success("Request rejected.");
            showRejectModal.value = false;
        },
        onError: () => toast.error("Failed to reject request."),
        onFinish: () => { rejecting.value = null; },
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
                    <PageTitle title="Batch AOQ Requests" />
                </div>
                <p class="text-sm text-muted-foreground mt-1">
                    Review and approve requests to add AOQs to locked batches.
                </p>
            </div>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>All Requests</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="relative w-full overflow-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Batch</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Requester</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Reason</th>
                                <th class="h-12 px-3 text-left align-middle font-medium text-muted-foreground">Date</th>
                                <th class="h-12 px-3 text-center align-middle font-medium text-muted-foreground">Status</th>
                                <th class="h-12 px-3 text-right align-middle font-medium text-muted-foreground">Actions</th>
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
                                <td class="p-3 align-middle">
                                    {{ request.requester?.name || "—" }}
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
                                <td class="p-3 align-middle text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <template v-if="request.status === 'pending'">
                                            <Button
                                                size="sm"
                                                variant="default"
                                                :disabled="approving === request.id"
                                                @click="approve(request)"
                                            >
                                                <Icon
                                                    v-if="approving === request.id"
                                                    icon="lucide:loader-2"
                                                    class="h-3.5 w-3.5 mr-1 animate-spin"
                                                />
                                                Approve
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                :disabled="rejecting === request.id"
                                                @click="openReject(request)"
                                            >
                                                Reject
                                            </Button>
                                        </template>
                                        <span
                                            v-else-if="request.status === 'rejected' && request.rejection_reason"
                                            class="text-xs text-muted-foreground"
                                            :title="request.rejection_reason"
                                        >
                                            {{ request.rejection_reason }}
                                        </span>
                                        <span
                                            v-else-if="request.status === 'approved'"
                                            class="text-xs text-muted-foreground"
                                        >
                                            by {{ request.approver?.name || "—" }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="requests.data.length === 0">
                                <td colspan="6" class="p-8 text-center text-muted-foreground">
                                    No requests found.
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

        <!-- Reject Modal -->
        <div
            v-if="showRejectModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
            @click.self="showRejectModal = false"
        >
            <div class="w-full max-w-md rounded-lg bg-background p-6 shadow-lg">
                <h3 class="text-lg font-semibold mb-2">Reject Request</h3>
                <p class="text-sm text-muted-foreground mb-4">
                    Reject request for batch {{ rejectTarget?.batch?.batch_no }} by {{ rejectTarget?.requester?.name }}.
                </p>
                <div class="space-y-2">
                    <label class="text-sm font-medium">Rejection Reason (optional)</label>
                    <textarea
                        v-model="rejectReason"
                        rows="3"
                        class="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        placeholder="Provide a reason for rejection..."
                    />
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <Button variant="outline" @click="showRejectModal = false">Cancel</Button>
                    <Button
                        variant="destructive"
                        :disabled="rejecting === rejectTarget?.id"
                        @click="submitReject"
                    >
                        <Icon
                            v-if="rejecting === rejectTarget?.id"
                            icon="lucide:loader-2"
                            class="h-4 w-4 mr-2 animate-spin"
                        />
                        Reject
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
