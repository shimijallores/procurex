<script setup>
import { Icon } from "@iconify/vue";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

const props = defineProps({
    rfq: Object,
});

defineEmits(["submit-supplier"]);

const formatDateTime = (value) => {
    if (!value) return "—";
    return new Date(value).toLocaleString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const printForSupplier = (supplierId) => {
    window.open(
        route("rfqs.pdf", { rfq: props.rfq.id, supplier_id: supplierId }),
        "_blank",
    );
};
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center gap-2 text-base">
                <Icon icon="lucide:building-2" class="h-4 w-4 text-primary" />
                Supplier Quotations
            </CardTitle>
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
                                Supplier
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Submitted At
                            </th>
                            <th
                                class="h-12 px-4 text-center align-middle font-medium text-muted-foreground"
                            >
                                Filing Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Remarks
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="supplierEntry in rfq.suppliers"
                            :key="supplierEntry.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="font-medium">
                                    {{ supplierEntry.supplier?.name }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        supplierEntry.supplier
                                            ?.contact_number ||
                                        "No contact number"
                                    }}
                                </div>
                            </td>
                            <td class="p-4 align-middle text-center">
                                {{ formatDateTime(supplierEntry.submitted_at) }}
                            </td>
                            <td class="p-4 align-middle text-center">
                                <span
                                    v-if="
                                        supplierEntry.submitted_at &&
                                        supplierEntry.is_late
                                    "
                                    class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900 dark:text-red-300"
                                >
                                    Late Filing
                                </span>
                                <span
                                    v-else-if="supplierEntry.submitted_at"
                                    class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-300"
                                >
                                    On Time
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 dark:bg-gray-800 dark:text-gray-300"
                                >
                                    Pending
                                </span>
                            </td>
                            <td
                                class="p-4 align-middle text-sm text-muted-foreground max-w-[280px] truncate"
                                :title="supplierEntry.remarks"
                            >
                                {{ supplierEntry.remarks || "—" }}
                            </td>
                            <td class="p-4 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            printForSupplier(
                                                supplierEntry.supplier_id,
                                            )
                                        "
                                    >
                                        <Icon
                                            icon="lucide:printer"
                                            class="mr-1 h-3.5 w-3.5"
                                        />
                                        Print
                                    </Button>
                                    <Button
                                        size="sm"
                                        @click="
                                            $emit(
                                                'submit-supplier',
                                                supplierEntry,
                                            )
                                        "
                                    >
                                        <Icon
                                            icon="lucide:save"
                                            class="mr-1 h-3.5 w-3.5"
                                        />
                                        Add Quotation
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </CardContent>
    </Card>
</template>
