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
    rfqs: Object,
    offices: Array,
    fiscalYears: Object,
    selectedOffice: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:selected-office",
    "update:selected-fiscal-year",
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

const getSupplierProgress = (rfq) => {
    const total = rfq?.suppliers?.length || 0;
    const submitted = (rfq?.suppliers || []).filter(
        (s) => !!s.submitted_at,
    ).length;
    return `${submitted}/${total}`;
};

const hasLateFiling = (rfq) => {
    return (rfq?.suppliers || []).some((s) => s.is_late);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Request for Quotations</CardTitle>
                    <CardDescription>
                        SVP RFQs generated from approved purchase requests
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
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
                                SVP / Project
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Office / PR
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                RFQ Date
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Submission Deadline
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Suppliers
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                ABC
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!rfqs?.data?.length">
                            <td
                                colspan="7"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-x-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No RFQs found.
                            </td>
                        </tr>

                        <tr
                            v-for="rfq in rfqs.data"
                            :key="rfq.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="font-medium">{{ rfq.svp_no }}</div>
                                <div
                                    class="text-xs text-muted-foreground truncate max-w-[320px]"
                                    :title="rfq.project_name"
                                >
                                    {{ rfq.project_name }}
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{
                                        rfq.purchase_request?.office?.name ||
                                        "—"
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    PR:
                                    {{
                                        rfq.purchase_request?.pr_no ||
                                        "#" + rfq.pr_id
                                    }}
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDate(rfq.rfq_date) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <div class="space-y-1">
                                    <div>
                                        {{
                                            formatDate(rfq.submission_deadline)
                                        }}
                                    </div>
                                    <span
                                        v-if="hasLateFiling(rfq)"
                                        class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-medium text-red-800 dark:bg-red-900 dark:text-red-300"
                                    >
                                        Late Filing
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ getSupplierProgress(rfq) }}
                            </td>
                            <td class="p-4 align-middle text-right font-medium">
                                {{ formatCurrency(rfq.abc_amount) }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link :href="route('rfqs.show', rfq.id)">
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="$emit('delete-click', rfq)"
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

            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ rfqs.from }} to {{ rfqs.to }} of
                    {{ rfqs.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in rfqs.links" :key="index">
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
                            preserve-state
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
</template>
