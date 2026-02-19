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
    poTransmittals: Object,
    offices: Array,
    fiscalYears: Object,
    selectedType: String,
    selectedOffice: String,
    selectedFiscalYear: String,
});

defineEmits([
    "update:selected-type",
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
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All PO Transmittals</CardTitle>
                    <CardDescription
                        >Generated transmittal records for COA and OPG
                        printing</CardDescription
                    >
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="selectedType"
                        @change="
                            $emit('update:selected-type', $event.target.value)
                        "
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">All Types</option>
                        <option value="coa">COA</option>
                        <option value="opg">OPG</option>
                    </select>

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
                                Type
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Transmittal No.
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                PO / Project
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Date
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Signatory
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr v-if="!poTransmittals?.data?.length">
                            <td
                                colspan="6"
                                class="p-8 text-center text-muted-foreground"
                            >
                                <Icon
                                    icon="lucide:file-x-2"
                                    class="mx-auto mb-2 h-8 w-8 opacity-50"
                                />
                                No PO Transmittals found.
                            </td>
                        </tr>
                        <tr
                            v-for="entry in poTransmittals.data"
                            :key="entry.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Badge variant="outline" class="uppercase">{{
                                    entry.type
                                }}</Badge>
                            </td>
                            <td class="p-4 align-middle font-medium">
                                {{ entry.transmittal_no || "—" }}
                            </td>
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ entry.purchase_order?.po_no || "—" }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground truncate max-w-[340px]"
                                >
                                    {{
                                        entry.purchase_order?.noa
                                            ?.bac_resolution?.project_name ||
                                        "—"
                                    }}
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDate(entry.transmittal_date) }}
                            </td>
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ entry.signatory_name || "—" }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ entry.signatory_title || "—" }}
                                </div>
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <a
                                        :href="
                                            route(
                                                'po-transmittals.pdf',
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
                                                'po-transmittals.show',
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
                    Showing {{ poTransmittals.from }} to
                    {{ poTransmittals.to }} of
                    {{ poTransmittals.total }} entries
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in poTransmittals.links"
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
