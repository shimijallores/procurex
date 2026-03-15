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
    accounts: Array,
    fiscalYears: Object,
    search: String,
    selectedOffice: String,
    selectedAccount: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:search",
    "update:selected-office",
    "update:selected-account",
    "update:selected-fiscal-year",
]);

const formatDate = (date) => {
    if (!date) {
        return "-";
    }

    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "2-digit",
        day: "2-digit",
    });
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>Ongoing Purchase Request Matrix</CardTitle>
                    <CardDescription>
                        14-column matrix for PR item-level tracking.
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
                        :value="selectedAccount"
                        @change="
                            $emit(
                                'update:selected-account',
                                $event.target.value,
                            )
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    >
                        <option value="">All Accounts</option>
                        <option
                            v-for="account in accounts"
                            :key="account.id"
                            :value="account.id"
                        >
                            {{ account.name }}
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
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                CONTROL NO. / EMANATING NO.
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                OFFICES / HOSPITALS
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                ITEM DESCRIPTION (PROJECT NAME)
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                PR NO.
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                PR DATE
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                AMOUNT BELOW 1M
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                AMOUNT ABOVE 1M
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                NEW AMOUNT
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                ACCOUNT / CHARGED TO
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                PERSON IN CHARGE (PR SECTION)
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                PERSON IN CHARGE (BUDGETING)
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                DATE RELEASE
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-center align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                NEW DATE RELEASE
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-left align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                REMARKS
                            </th>
                            <th
                                class="h-11 px-2 py-2 text-right align-middle font-medium text-muted-foreground whitespace-normal break-words"
                            >
                                ACTIONS
                            </th>
                        </tr>
                    </thead>

                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!matrixRows?.data?.length">
                            <td
                                colspan="15"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:table"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No ongoing PR matrix rows found.
                            </td>
                        </tr>

                        <tr
                            v-for="row in matrixRows?.data"
                            :key="row.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-2 align-middle font-medium">
                                {{ row.control_no || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.office_name || "-" }}
                            </td>
                            <td class="p-2 align-middle max-w-[180px]">
                                <p
                                    class="truncate"
                                    :title="row.item_description || '-'"
                                >
                                    {{ row.item_description || "-" }}
                                </p>
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.pr_no || "-" }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ formatDate(row.pr_date) }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                {{
                                    row.amount_below_1m
                                        ? formatCurrency(row.amount_below_1m)
                                        : "-"
                                }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                {{
                                    row.amount_above_1m
                                        ? formatCurrency(row.amount_above_1m)
                                        : "-"
                                }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                {{
                                    row.new_amount
                                        ? formatCurrency(row.new_amount)
                                        : "-"
                                }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.account_name || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.pr_admin_name || "-" }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.budgeting_admin_name || "-" }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ formatDate(row.date_release) }}
                            </td>
                            <td class="p-2 align-middle text-center">
                                {{ formatDate(row.new_date_release) }}
                            </td>
                            <td class="p-2 align-middle">
                                {{ row.remarks || "-" }}
                            </td>
                            <td class="p-2 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="
                                            route(
                                                'purchase-request-matrix.show',
                                                row.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="sm"
                                            ><Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                        /></Button>
                                    </Link>
                                    <Link
                                        :href="
                                            route(
                                                'purchase-request-matrix.edit',
                                                row.id,
                                            )
                                        "
                                    >
                                        <Button variant="ghost" size="sm"
                                            ><Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                        /></Button>
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
