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
    batches: Array,
    selectedOffice: String,
    selectedBatch: String,
});

defineEmits([
    "update:selected-office",
    "update:selected-batch",
    "delete-click",
]);

const getTransmittalByType = (entry, type) => {
    const matched = entry.purchase_order?.po_transmittals?.find(
        (item) => item.type === type,
    );

    if (matched) {
        return matched;
    }

    if (entry.type === type) {
        return entry;
    }

    return null;
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
                        >One entry per PO with paired COA and OPG
                        transmittals</CardDescription
                    >
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        :value="selectedOffice"
                        @change="
                            $emit('update:selected-office', $event.target.value)
                        "
                        class="flex h-10 w-40 rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                        :value="selectedBatch"
                        @change="$emit('update:selected-batch', $event.target.value)"
                        class="flex h-10 rounded-md border border-input bg-background px-3 py-2 text-sm"
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
                                COA / OPG No.
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Documents
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                PO / Project
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Batch
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                COA Signatory / OPG Signatory
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
                                <div class="font-medium">
                                    {{
                                        getTransmittalByType(entry, "coa")
                                            ?.transmittal_no || "—"
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        getTransmittalByType(entry, "opg")
                                            ?.transmittal_no || "—"
                                    }}
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex flex-wrap gap-1">
                                    <Badge variant="outline" class="uppercase"
                                        >COA</Badge
                                    >
                                    <Badge
                                        variant="secondary"
                                        class="uppercase"
                                        :class="{
                                            'opacity-60': !getTransmittalByType(
                                                entry,
                                                'opg',
                                            ),
                                        }"
                                    >
                                        OPG
                                    </Badge>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ entry.purchase_order?.po_no || "—" }}
                                </div>
                                <div
                                    class="text-xs text-muted-foreground truncate max-w-85"
                                >
                                    {{
                                        entry.purchase_order?.noa
                                            ?.bac_resolution?.project_name ||
                                        "—"
                                    }}
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="entry.purchase_order?.noa?.aoq?.batch"
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono"
                                >
                                    {{ entry.purchase_order.noa.aoq.batch.batch_no }}
                                </span>
                                <span
                                    v-else-if="entry.purchase_order?.noa?.bac_resolution?.aoq?.batch"
                                    class="inline-flex items-center rounded-md border px-2 py-0.5 text-xs font-mono"
                                >
                                    {{ entry.purchase_order.noa.bac_resolution.aoq.batch.batch_no }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{
                                        getTransmittalByType(entry, "coa")
                                            ?.signatory_name || "—"
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        getTransmittalByType(entry, "coa")
                                            ?.signatory_title || "—"
                                    }}
                                </div>
                                <div class="mt-2 font-medium">
                                    {{
                                        getTransmittalByType(entry, "opg")
                                            ?.signatory_name || "—"
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        getTransmittalByType(entry, "opg")
                                            ?.signatory_title || "—"
                                    }}
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
