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
    aoqs: Object,
    offices: Array,
    batches: Array,
    fiscalYears: Object,
    selectedOffice: String,
    selectedFiscalYear: String,
    selectedBatch: String,
});

defineEmits([
    "update:selected-office",
    "update:selected-fiscal-year",
    "update:selected-batch",
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
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Abstract of Quotations</CardTitle>
                    <CardDescription>
                        Consolidated quotation abstract per RFQ
                    </CardDescription>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="selectedOffice"
                        @change="
                            $emit('update:selected-office', $event.target.value)
                        "
                        class="flex h-10 w-40 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
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

                    <select
                        :value="selectedBatch"
                        @change="
                            $emit(
                                'update:selected-batch',
                                $event.target.value,
                            )
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-backspace focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Batches</option>
                        <option
                            v-for="batch in batches"
                            :key="batch.id"
                            :value="String(batch.id)"
                        >
                            {{ batch.batch_no }}
                            ({{ batch.aoqs_count }} AOQ{{ batch.aoqs_count !== 1 ? "s" : "" }})
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
                                RFQ / Project
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Office
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Batch
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                AOQ Date
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Calculation
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Winner Supplier
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!aoqs?.data?.length">
                            <td
                                colspan="7"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-x-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No AOQs found.
                            </td>
                        </tr>
                        <tr
                            v-for="aoq in aoqs.data"
                            :key="aoq.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ aoq.rfq?.svp_no || "—" }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground truncate max-w-[320px]"
                                    :title="aoq.rfq?.project_name"
                                >
                                    {{ aoq.rfq?.project_name || "—" }}
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                {{
                                    aoq.rfq?.purchase_request?.office?.name ||
                                    "—"
                                }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="aoq.batch"
                                    class="inline-flex items-center rounded-md border border-border bg-muted/40 px-2 py-0.5 text-xs font-mono"
                                >
                                    {{ aoq.batch.batch_no }}
                                </span>
                                <span
                                    v-else-if="aoq.batch_id"
                                    class="inline-flex items-center rounded-md border border-border bg-muted/40 px-2 py-0.5 text-xs font-mono"
                                >
                                    Batch #{{ aoq.batch_id }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDate(aoq.aoq_date) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="
                                        (aoq.calculated_supplier_count || 0) >=
                                        2
                                    "
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300"
                                >
                                    Lowest Calculated
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-300"
                                >
                                    Single Calculated
                                </span>
                            </td>
                            <td class="p-4 align-middle">
                                {{ aoq.winner_supplier?.name || "—" }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <a
                                        :href="route('aoqs.pdf', aoq.id)"
                                        target="_blank"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:printer"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </a>
                                    <Link :href="route('aoqs.show', aoq.id)">
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
                                        @click="$emit('delete-click', aoq)"
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
                    Showing {{ aoqs.from }} to {{ aoqs.to }} of
                    {{ aoqs.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template v-for="(link, index) in aoqs.links" :key="index">
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
