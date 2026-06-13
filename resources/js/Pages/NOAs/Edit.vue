<script setup>
import { computed, ref } from "vue";
import { useForm, Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import Layout from "@/Layout/Layout.vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

defineOptions({
    layout: (h, page) =>
        h(
            Layout,
            {
                breadcrumbs: [
                    { label: "Notice of Award", href: route("noas.index") },
                    { label: "Edit" },
                ],
            },
            () => page,
        ),
});

const props = defineProps({
    noa: Object,
    suppliers: Array,
    winnerAmount: Number,
});

const aoq = props.noa.aoq || props.noa.bac_resolution?.aoq;
const rfq = aoq?.rfq;

const form = useForm({
    noa_date: props.noa.noa_date?.slice(0, 10) || "",
    recipient_name: props.noa.recipient_name || "",
    recipient_title: props.noa.recipient_title || "",
});

const showRecipientSuggestions = ref(false);
const showTitleSuggestions = ref(false);

const recipientTitleOptions = ["Proprietor", "Authorized Representative", "Owner"];

const normalizeName = (value) =>
    String(value || "").trim().toLowerCase().replace(/[^a-z0-9]/g, "");

const selectedSupplier = computed(() => {
    const supplierName = aoq?.winner_supplier?.name || "";
    if (!supplierName) return null;
    const normalized = normalizeName(supplierName);
    const match = props.suppliers?.find((s) => normalizeName(s.name) === normalized);
    return match || null;
});

const representativeSuggestions = computed(() => {
    const supplier = selectedSupplier.value;
    if (!supplier) return [];
    return [supplier.proprietor, supplier.authorized_representative, supplier.owner]
        .map((v) => String(v || "").trim())
        .filter((v, i, a) => v !== "" && a.indexOf(v) === i);
});

const selectRecipient = (name) => {
    form.recipient_name = name;
    showRecipientSuggestions.value = false;
    const supplier = selectedSupplier.value;
    if (!supplier) return;
    if (name === supplier.proprietor) form.recipient_title = "Proprietor";
    else if (name === supplier.authorized_representative) form.recipient_title = "Authorized Representative";
    else if (name === supplier.owner) form.recipient_title = "Owner";
};

const selectTitle = (title) => {
    form.recipient_title = title;
    showTitleSuggestions.value = false;
};

const formatCurrency = (value) =>
    new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(value || 0);

const submit = () => {
    form.put(route("noas.update", props.noa.id));
};
</script>

<template>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit Notice of Award</h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{ noa.noa_no }}
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card v-if="aoq">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2 text-base">
                        <Icon icon="lucide:file-text" class="h-4 w-4 text-primary" />
                        Abstract of Quotation
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                    <div>
                        <p class="text-xs text-muted-foreground">SVP No.</p>
                        <p class="font-medium">{{ rfq?.svp_no || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Project</p>
                        <p class="font-medium">{{ rfq?.project_name || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Winner Supplier</p>
                        <p class="font-medium">{{ aoq.winner_supplier?.name || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Winner Amount</p>
                        <p class="font-medium">{{ formatCurrency(winnerAmount) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Office</p>
                        <p class="font-medium">{{ rfq?.purchase_request?.office?.name || "—" }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">NOA Number</p>
                        <p class="font-mono font-medium">{{ noa.noa_no }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">NOA Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="noa_date">NOA Date</Label>
                            <input
                                id="noa_date"
                                v-model="form.noa_date"
                                type="date"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            />
                            <p v-if="form.errors.noa_date" class="text-xs text-destructive">
                                {{ form.errors.noa_date }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="noa_no">NOA Number</Label>
                            <input
                                id="noa_no"
                                :value="noa.noa_no"
                                readonly
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground"
                            />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="recipient_name">Recipient Name</Label>
                        <div class="relative">
                            <input
                                id="recipient_name"
                                v-model="form.recipient_name"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                @focus="showRecipientSuggestions = true"
                                @blur="setTimeout(() => { showRecipientSuggestions = false }, 150)"
                            />
                            <div
                                v-if="showRecipientSuggestions && representativeSuggestions.length"
                                class="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                            >
                                <button
                                    v-for="person in representativeSuggestions"
                                    :key="person"
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                    @mousedown.prevent="selectRecipient(person)"
                                >
                                    {{ person }}
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.recipient_name" class="text-xs text-destructive">
                            {{ form.errors.recipient_name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="recipient_title">Recipient Title</Label>
                        <div class="relative">
                            <input
                                id="recipient_title"
                                v-model="form.recipient_title"
                                class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                @focus="showTitleSuggestions = true"
                                @blur="setTimeout(() => { showTitleSuggestions = false }, 150)"
                            />
                            <div
                                v-if="showTitleSuggestions"
                                class="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                            >
                                <button
                                    v-for="title in recipientTitleOptions"
                                    :key="title"
                                    type="button"
                                    class="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                    @mousedown.prevent="selectTitle(title)"
                                >
                                    {{ title }}
                                </button>
                            </div>
                        </div>
                        <p v-if="form.errors.recipient_title" class="text-xs text-destructive">
                            {{ form.errors.recipient_title }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <div class="flex justify-end gap-2">
                <Link :href="route('noas.show', noa.id)">
                    <Button type="button" variant="outline">Cancel</Button>
                </Link>
                <Button type="submit" :disabled="form.processing">
                    <Icon v-if="form.processing" icon="lucide:loader-2" class="mr-2 h-4 w-4 animate-spin" />
                    Update NOA
                </Button>
            </div>
        </form>
    </div>
</template>
