<script setup>
import { Icon } from "@iconify/vue";
import { Link } from "@inertiajs/vue3";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    purchaseRequests: Object,
    search: String,
    offices: Array,
    fiscalYears: Object,
    selectedOffice: String,
    selectedFiscalYear: String,
    selectedStatus: String,
});

defineEmits([
    "update:search",
    "update:selected-office",
    "update:selected-fiscal-year",
    "update:selected-status",
    "delete-click",
]);

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};

const getStatusBadge = (status) => {
    const map = {
        draft: {
            text: "Draft",
            color: "bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300",
            icon: "lucide:file-edit",
        },
        approved: {
            text: "Approved",
            color: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300",
            icon: "lucide:check-circle",
        },
        returned: {
            text: "Returned",
            color: "bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300",
            icon: "lucide:undo-2",
        },
        cancelled: {
            text: "Cancelled",
            color: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300",
            icon: "lucide:x-circle",
        },
    };
    return map[status] || map.draft;
};

const statusOptions = [
    { value: "", label: "All Statuses" },
    { value: "draft", label: "Draft" },
    { value: "approved", label: "Approved" },
    { value: "returned", label: "Returned" },
    { value: "cancelled", label: "Cancelled" },
];
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Purchase Requests</CardTitle>
                    <CardDescription>
                        A list of all purchase requests filtered by office
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Office filter -->
                    <select
                        :value="selectedOffice"
                        @change="
                            $emit('update:selected-office', $event.target.value)
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Offices</option>
                        <option
                            v-for="office in offices"
                            :key="office.id"
                            :value="office.id"
                        >
                            {{ office.name }}
                        </option>
                    </select>

                    <!-- Status filter -->
                    <select
                        :value="selectedStatus"
                        @change="
                            $emit('update:selected-status', $event.target.value)
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option
                            v-for="opt in statusOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </select>

                    <!-- Fiscal Year filter -->
                    <select
                        :value="selectedFiscalYear"
                        @change="
                            $emit(
                                'update:selected-fiscal-year',
                                $event.target.value,
                            )
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Years</option>
                        <option
                            v-for="(year, id) in fiscalYears"
                            :key="id"
                            :value="id"
                        >
                            {{ year }}
                        </option>
                    </select>

                    <slot name="search" />
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
                                PR No. / Office
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Purpose
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                PR Date
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Total Amount
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!purchaseRequests?.data?.length">
                            <td
                                colspan="6"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-plus-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No purchase requests found.
                            </td>
                        </tr>
                        <tr
                            v-for="pr in purchaseRequests?.data"
                            :key="pr.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10"
                                    >
                                        <Icon
                                            icon="lucide:file-plus-2"
                                            class="h-5 w-5 text-primary"
                                        />
                                    </div>
                                    <div>
                                        <div class="font-medium">
                                            {{ pr.pr_no || "— (No PR No.)" }}
                                        </div>
                                        <div
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{
                                                pr.office?.name ||
                                                "Unknown Office"
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle max-w-xs">
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                    :title="pr.purpose"
                                >
                                    {{ pr.purpose || "—" }}
                                </p>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <Icon
                                        icon="lucide:calendar"
                                        class="h-3.5 w-3.5 text-muted-foreground"
                                    />
                                    <span>{{ formatDate(pr.pr_date) }}</span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right font-medium">
                                {{ formatCurrency(pr.total_amount) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    :class="getStatusBadge(pr.status).color"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                >
                                    <Icon
                                        :icon="getStatusBadge(pr.status).icon"
                                        class="h-3 w-3"
                                    />
                                    {{ getStatusBadge(pr.status).text }}
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'purchase-requests.show',
                                                pr.id,
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
                                        v-if="pr.status === 'draft'"
                                        :href="
                                            route(
                                                'purchase-requests.edit',
                                                pr.id,
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
                                        class="text-destructive hover:text-destructive"
                                        @click="$emit('delete-click', pr)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="purchaseRequests?.last_page > 1"
                class="mt-4 flex items-center justify-between border-t pt-4"
            >
                <p class="text-sm text-muted-foreground">
                    Page {{ purchaseRequests.current_page }} of
                    {{ purchaseRequests.last_page }} ({{
                        purchaseRequests.total
                    }}
                    total)
                </p>
                <div class="flex gap-2">
                    <Link
                        v-if="purchaseRequests.prev_page_url"
                        :href="purchaseRequests.prev_page_url"
                    >
                        <Button variant="outline" size="sm">
                            <Icon icon="lucide:chevron-left" class="h-4 w-4" />
                            Previous
                        </Button>
                    </Link>
                    <Link
                        v-if="purchaseRequests.next_page_url"
                        :href="purchaseRequests.next_page_url"
                    >
                        <Button variant="outline" size="sm">
                            Next
                            <Icon icon="lucide:chevron-right" class="h-4 w-4" />
                        </Button>
                    </Link>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
