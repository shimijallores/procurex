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
    matrixRows: Object,
    offices: Array,
    fiscalYears: Object,
    search: String,
    selectedOffice: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:search",
    "update:selected-office",
    "update:selected-fiscal-year",
]);

const formatCurrency = (value) => {
    if (value === null || value === undefined || value === "") {
        return "-";
    }

    return new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Ongoing SVP Matrix</CardTitle>
                    <CardDescription>
                        Overview of procurement stages from RFQ to transmittal.
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
            <div class="relative w-full overflow-x-auto">
                <table
                    class="w-full table-fixed caption-bottom text-xs md:text-sm"
                >
                    <thead class="border-b">
                        <tr
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground"
                            >
                                #
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                OFFICE
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                PO NO.
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                MODE OF PROCUREMENT
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                PR NO.
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground"
                            >
                                ABC
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                SUPPLIER
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                PARTICULARS
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground"
                            >
                                AMOUNT
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground"
                            >
                                RFQ (PABS)
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                ABSTRACT
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground"
                            >
                                RESOLUTION
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                NOA & PO
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground"
                            >
                                TRANSMITTAL FORM
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                ADMIN
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                FRONTDESK
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground"
                            >
                                REMARKS
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground"
                            >
                                ACTIONS
                            </th>
                        </tr>
                    </thead>

                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!matrixRows?.data?.length">
                            <td
                                colspan="18"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:table"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No SVP matrix rows found.
                            </td>
                        </tr>

                        <tr
                            v-for="(row, idx) in matrixRows.data"
                            :key="row.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td
                                class="p-2 align-middle text-center font-medium"
                            >
                                {{ (matrixRows.from || 1) + idx }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.office || "-" }}
                            </td>
                            <td class="p-2 align-middle font-medium">
                                {{ row.po_no || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.mode_of_procurement || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.pr_no || "-" }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                {{ formatCurrency(row.abc) }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.supplier || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.particulars || "-" }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                {{ formatCurrency(row.amount) }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ row.rfq || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.abstract || "-" }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ row.resolution || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.noa_po || "-" }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ row.transmittal_form || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.admin || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.frontdesk || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.remarks || "-" }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('svp-matrix.show', row.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link
                                        :href="route('svp-matrix.edit', row.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ matrixRows.from }} to {{ matrixRows.to }} of
                    {{ matrixRows.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in matrixRows.links"
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
