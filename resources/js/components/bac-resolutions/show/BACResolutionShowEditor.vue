<script setup>
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

defineProps({
    resolution: Object,
});

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", {
        style: "currency",
        currency: "PHP",
    }).format(value || 0);
</script>

<template>
    <div class="space-y-4">
        <Card>
            <CardHeader>
                <CardTitle class="text-base flex items-center gap-2">
                    <Icon
                        icon="lucide:file-text"
                        class="h-4 w-4 text-primary"
                    />
                    Resolution Content
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Resolution Date
                        </p>
                        <p class="font-medium">
                            {{ resolution.resolution_date || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Meeting Date
                        </p>
                        <p class="font-medium">
                            {{ resolution.meeting_date || "—" }}
                        </p>
                    </div>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Project Name</p>
                    <p class="font-medium">
                        {{ resolution.project_name || "—" }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <p class="text-muted-foreground text-sm">
                            Winner Supplier
                        </p>
                        <p class="font-medium">
                            {{ resolution.winner_supplier_name || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Winner Amount
                        </p>
                        <p class="font-medium">
                            {{ formatCurrency(resolution.winner_amount || 0) }}
                        </p>
                    </div>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        RFQ ABC (Linked)
                    </p>
                    <p class="font-medium">
                        {{
                            formatCurrency(resolution.aoq?.rfq?.abc_amount || 0)
                        }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">
                        Calculation Label
                    </p>
                    <p class="font-medium">
                        {{ resolution.calculation_label || "—" }}
                    </p>
                </div>

                <div>
                    <p class="text-muted-foreground text-sm">Justification</p>
                    <p class="whitespace-pre-wrap">
                        {{ resolution.justification || "—" }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-muted-foreground text-sm">
                            Signatory: Chairperson
                        </p>
                        <p class="font-medium">
                            {{ resolution.signatory_chairperson || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Signatory: Member 1
                        </p>
                        <p class="font-medium">
                            {{ resolution.signatory_member_one || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Signatory: Member 2
                        </p>
                        <p class="font-medium">
                            {{ resolution.signatory_member_two || "—" }}
                        </p>
                    </div>

                    <div>
                        <p class="text-muted-foreground text-sm">
                            Signatory: Member 3
                        </p>
                        <p class="font-medium">
                            {{ resolution.signatory_member_three || "—" }}
                        </p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
