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
    resolutions: Object,
    offices: Array,
    batches: Array,
    fiscalYears: Object,
    selectedOffice: String,
    selectedBatch: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:selected-office",
    "update:selected-batch",
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
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All BAC Resolutions</CardTitle>
                    <CardDescription>
                        Resolution records generated from one or more AOQ
                        documents
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
                        @change="$emit('update:selected-batch', $event.target.value)"
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Batches</option>
                        <option
                            v-for="batch in batches || []"
                            :key="batch.id"
                            :value="String(batch.id)"
                        >
                            {{ batch.batch_no }}
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
                                Resolution No.
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Project / AOQ
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Resolution Date
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Batch
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Winner
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
                        <tr v-if="!resolutions?.data?.length">
                            <td
                                colspan="7"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-x-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No BAC Resolutions found.
                            </td>
                        </tr>
                        <tr
                            v-for="resolution in resolutions.data"
                            :key="resolution.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">
                                {{ resolution.resolution_no }}
                            </td>
                            <td class="p-4 align-middle">
                                <div
                                    class="font-medium truncate max-w-[320px]"
                                    :title="resolution.project_name"
                                >
                                    {{ resolution.project_name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        resolution.aoqs?.length
                                            ? `${resolution.aoqs.length} AOQ(s)`
                                            : "0 AOQ"
                                    }}
                                    <span
                                        v-if="resolution.aoqs?.[0]?.rfq?.svp_no"
                                    >
                                        •
                                        {{ resolution.aoqs[0].rfq.svp_no }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDate(resolution.resolution_date) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="resolution.aoqs?.[0]?.batch"
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono"
                                >
                                    {{ resolution.aoqs[0].batch.batch_no }}
                                </span>
                                <span
                                    v-else-if="resolution.aoq?.batch"
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono"
                                >
                                    {{ resolution.aoq.batch.batch_no }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="p-4 align-middle">
                                {{ resolution.winner_supplier_name || "—" }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="resolution.finalized_at"
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300"
                                >
                                    Finalized
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900 dark:text-amber-300"
                                >
                                    Draft
                                </span>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <a
                                        :href="
                                            route(
                                                'bac-resolutions.pdf',
                                                resolution.id,
                                            )
                                        "
                                        target="_blank"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:printer"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </a>
                                    <Link
                                        :href="
                                            route(
                                                'bac-resolutions.show',
                                                resolution.id,
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

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:text-destructive"
                                        @click="
                                            $emit('delete-click', resolution)
                                        "
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
                    Showing {{ resolutions.from }} to {{ resolutions.to }} of
                    {{ resolutions.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in resolutions.links"
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
