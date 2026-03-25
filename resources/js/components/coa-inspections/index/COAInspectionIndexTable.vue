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
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

defineProps({
    coaInspections: Object,
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

const firstLine = (text) => {
    if (!text) return "-";

    const lines = text
        .split(/\r\n|\r|\n/)
        .map((line) => line.trim())
        .filter(Boolean);

    return lines[0] || "-";
};

const formatDate = (date) => {
    if (!date) return "-";

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
                    <CardTitle>All COA Inspections</CardTitle>
                    <CardDescription
                        >One row per PO containing both SVP and Bidding
                        letters</CardDescription
                    >
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="selectedOffice"
                        @change="
                            $emit('update:selected-office', $event.target.value)
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                                PO / Project
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                SVP Recipient
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Bidding Recipient
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Office
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Created
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!coaInspections?.data?.length">
                            <td
                                colspan="6"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-x-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No COA inspections found.
                            </td>
                        </tr>
                        <tr
                            v-for="entry in coaInspections.data"
                            :key="entry.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ entry.purchase_order?.po_no || "-" }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground truncate max-w-[320px]"
                                >
                                    {{
                                        entry.purchase_order?.noa
                                            ?.bac_resolution?.project_name ||
                                        "-"
                                    }}
                                </div>
                                <div class="mt-1">
                                    <Badge variant="outline" class="uppercase"
                                        >SVP + Bidding</Badge
                                    >
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                {{ firstLine(entry.svp_header_text) }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ firstLine(entry.bidding_header_text) }}
                            </td>
                            <td class="p-4 align-middle">
                                {{
                                    entry.purchase_order?.noa?.bac_resolution
                                        ?.aoq?.rfq?.purchase_request?.office
                                        ?.name || "-"
                                }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDate(entry.created_at) }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <a
                                        :href="
                                            route(
                                                'coa-inspections.pdf',
                                                entry.id,
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
                                                'coa-inspections.show',
                                                entry.id,
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
                                        @click="$emit('delete-click', entry)"
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
                    Showing {{ coaInspections.from }} to
                    {{ coaInspections.to }} of
                    {{ coaInspections.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in coaInspections.links"
                        :key="index"
                    >
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium transition-colors',
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
                                'inline-flex h-9 items-center justify-center rounded-md text-sm font-medium pointer-events-none opacity-50',
                                link.label.includes('Previous') ||
                                link.label.includes('Next')
                                    ? 'px-3'
                                    : 'w-9',
                            ]"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
