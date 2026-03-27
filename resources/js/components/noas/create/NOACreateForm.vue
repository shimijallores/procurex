<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleResolutions: Array,
    suppliers: Array,
    defaultResolutionDate: String,
    defaultNoaDate: String,
});

defineEmits(["submit"]);

const selectedResolution = computed(() =>
    props.eligibleResolutions?.find(
        (resolution) =>
            String(resolution.id) === String(props.form.bac_resolution_id),
    ),
);

const resolutionAoqOptions = computed(() => {
    const resolution = selectedResolution.value;
    if (!resolution) {
        return [];
    }

    const remainingAoqs = Array.isArray(resolution.remaining_aoqs)
        ? resolution.remaining_aoqs
        : [];
    if (remainingAoqs.length > 0) {
        return remainingAoqs;
    }

    const aoqs = Array.isArray(resolution.aoqs) ? resolution.aoqs : [];
    if (aoqs.length > 0) {
        return aoqs;
    }

    return resolution.aoq ? [resolution.aoq] : [];
});

const selectedProjectAoq = computed(() =>
    resolutionAoqOptions.value.find(
        (aoq) => String(aoq.id) === String(props.form.selected_aoq_id),
    ),
);

const showRepresentativeSuggestions = ref(false);
const showRecipientTitleSuggestions = ref(false);

const recipientTitleSuggestions = [
    "Proprietor",
    "Authorized Representative",
    "Owner",
];

const calculationLabelOptions = ["Lowest Calculated", "Single Calculated"];

const normalizeCalculationLabel = (value) => {
    const normalized = String(value || "")
        .trim()
        .toLowerCase();

    if (normalized.includes("lowest")) {
        return "Lowest Calculated";
    }

    if (normalized.includes("single")) {
        return "Single Calculated";
    }

    return "";
};

const normalizeName = (value) =>
    String(value || "")
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]/g, "");

const selectedSupplier = computed(() => {
    const supplierName = String(props.form.winner_supplier_name || "").trim();
    if (!supplierName) {
        return null;
    }

    const normalizedSupplierName = normalizeName(supplierName);

    const exactMatch = props.suppliers?.find(
        (supplier) => normalizeName(supplier.name) === normalizedSupplierName,
    );

    if (exactMatch) {
        return exactMatch;
    }

    return (
        props.suppliers?.find(
            (supplier) =>
                normalizeName(supplier.name).includes(normalizedSupplierName) ||
                normalizedSupplierName.includes(normalizeName(supplier.name)),
        ) || null
    );
});

const representativeSuggestions = computed(() => {
    const supplier = selectedSupplier.value;
    if (!supplier) {
        return [];
    }

    return [
        supplier.proprietor,
        supplier.authorized_representative,
        supplier.owner,
    ]
        .map((value) => String(value || "").trim())
        .filter(
            (value, index, values) =>
                value !== "" && values.indexOf(value) === index,
        );
});

const todayDate = new Date().toISOString().slice(0, 10);
const normalizeDate = (dateValue) => {
    if (!dateValue) {
        return "";
    }

    const dateString = String(dateValue).trim();
    const isoPrefixMatch = dateString.match(/^(\d{4}-\d{2}-\d{2})/);
    if (isoPrefixMatch) {
        return isoPrefixMatch[1];
    }

    const parsedDate = new Date(dateString);
    if (Number.isNaN(parsedDate.getTime())) {
        return "";
    }

    return parsedDate.toISOString().slice(0, 10);
};

const suggestedDefaultResolutionDate =
    normalizeDate(props.defaultResolutionDate) || todayDate;
const suggestedDefaultNoaDate =
    normalizeDate(props.defaultNoaDate) || todayDate;

const suggestNoaDate = (resolutionDate) => {
    if (!resolutionDate) {
        return suggestedDefaultNoaDate;
    }

    return resolutionDate > suggestedDefaultNoaDate
        ? resolutionDate
        : suggestedDefaultNoaDate;
};

const selectRecipientName = (name) => {
    props.form.recipient_name = String(name || "");
    showRepresentativeSuggestions.value = false;

    const supplier = selectedSupplier.value;
    if (!supplier) {
        return;
    }

    if (
        String(supplier.proprietor || "")
            .trim()
            .toLowerCase() ===
        String(props.form.recipient_name || "")
            .trim()
            .toLowerCase()
    ) {
        props.form.recipient_title = "Proprietor";
        return;
    }

    if (
        String(supplier.authorized_representative || "")
            .trim()
            .toLowerCase() ===
        String(props.form.recipient_name || "")
            .trim()
            .toLowerCase()
    ) {
        props.form.recipient_title = "Authorized Representative";
        return;
    }

    if (
        String(supplier.owner || "")
            .trim()
            .toLowerCase() ===
        String(props.form.recipient_name || "")
            .trim()
            .toLowerCase()
    ) {
        props.form.recipient_title = "Owner";
    }
};

const onRecipientFocus = () => {
    showRepresentativeSuggestions.value =
        representativeSuggestions.value.length > 0;
};

const onRecipientBlur = () => {
    setTimeout(() => {
        showRepresentativeSuggestions.value = false;
    }, 120);
};

const selectRecipientTitle = (title) => {
    props.form.recipient_title = String(title || "");
    showRecipientTitleSuggestions.value = false;
};

const onRecipientTitleFocus = () => {
    showRecipientTitleSuggestions.value = true;
};

const onRecipientTitleBlur = () => {
    setTimeout(() => {
        showRecipientTitleSuggestions.value = false;
    }, 120);
};

watch(
    () => props.form.bac_resolution_id,
    (id) => {
        const resolution = props.eligibleResolutions?.find(
            (entry) => String(entry.id) === String(id),
        );

        if (!resolution) {
            props.form.selected_aoq_id = "";
            props.form.noa_no = "";
            props.form.winner_supplier_name = "";
            props.form.resolution_no = "";
            props.form.resolution_date = "";
            props.form.calculation_label = "";

            if (!props.form.noa_date) {
                props.form.noa_date = suggestedDefaultNoaDate;
            }

            return;
        }

        const suggestedResolutionDate =
            normalizeDate(resolution.resolution_date) ||
            suggestedDefaultResolutionDate;

        const projectOptions =
            Array.isArray(resolution.aoqs) && resolution.aoqs.length > 0
                ? resolution.aoqs
                : resolution.aoq
                  ? [resolution.aoq]
                  : [];

        const hasSelectedProject = projectOptions.some(
            (aoq) => String(aoq.id) === String(props.form.selected_aoq_id),
        );

        if (!hasSelectedProject) {
            props.form.selected_aoq_id = projectOptions[0]?.id
                ? String(projectOptions[0].id)
                : "";
        }

        props.form.noa_date = suggestNoaDate(suggestedResolutionDate);
        props.form.resolution_no = resolution.resolution_no || "";
        props.form.resolution_date = suggestedResolutionDate;
        props.form.calculation_label = normalizeCalculationLabel(
            resolution.calculation_label,
        );
        props.form.winner_supplier_name = resolution.winner_supplier_name || "";

        if (!props.form.recipient_name) {
            const supplier =
                props.suppliers?.find(
                    (entry) =>
                        String(entry.name || "")
                            .trim()
                            .toLowerCase() ===
                        String(props.form.winner_supplier_name || "")
                            .trim()
                            .toLowerCase(),
                ) || null;

            props.form.recipient_name =
                supplier?.proprietor ||
                supplier?.authorized_representative ||
                supplier?.owner ||
                "";

            if (supplier?.proprietor) {
                props.form.recipient_title = "Proprietor";
            } else if (supplier?.authorized_representative) {
                props.form.recipient_title = "Authorized Representative";
            } else if (supplier?.owner) {
                props.form.recipient_title = "Owner";
            }
        }
    },
    { immediate: true },
);

watch(
    selectedProjectAoq,
    (aoq) => {
        if (!aoq) {
            return;
        }

        props.form.noa_no = aoq.rfq?.svp_no || "";
        props.form.winner_supplier_name =
            aoq.winner_supplier?.name || props.form.winner_supplier_name || "";
    },
    { immediate: true },
);

watch(
    () => props.form.winner_supplier_name,
    () => {
        if (props.form.recipient_name) {
            return;
        }

        props.form.recipient_name = representativeSuggestions.value[0] || "";

        const supplier = selectedSupplier.value;
        if (!supplier) {
            return;
        }

        if (representativeSuggestions.value[0] === supplier.proprietor) {
            props.form.recipient_title = "Proprietor";
        } else if (
            representativeSuggestions.value[0] ===
            supplier.authorized_representative
        ) {
            props.form.recipient_title = "Authorized Representative";
        } else if (representativeSuggestions.value[0] === supplier.owner) {
            props.form.recipient_title = "Owner";
        }
    },
);

watch(representativeSuggestions, (suggestions) => {
    if ((suggestions || []).length === 0) {
        showRepresentativeSuggestions.value = false;
    }
});
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source BAC Resolution
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="bac_resolution_id">BAC Resolution</Label>
                    <select
                        id="bac_resolution_id"
                        :value="form.bac_resolution_id"
                        @change="form.bac_resolution_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select BAC Resolution —</option>
                        <option
                            v-for="resolution in eligibleResolutions"
                            :key="resolution.id"
                            :value="resolution.id"
                        >
                            {{ resolution.resolution_no }} —
                            {{ resolution.project_name }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.bac_resolution_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.bac_resolution_id }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="selected_aoq_id">Project in Selected BAC</Label>
                    <select
                        id="selected_aoq_id"
                        :value="form.selected_aoq_id"
                        :disabled="!selectedResolution"
                        @change="form.selected_aoq_id = $event.target.value"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Project —</option>
                        <option
                            v-for="aoq in resolutionAoqOptions"
                            :key="aoq.id"
                            :value="aoq.id"
                        >
                            {{ aoq.rfq?.svp_no || "No SVP" }} —
                            {{ aoq.rfq?.project_name || "Project" }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.selected_aoq_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.selected_aoq_id }}
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="noa_no">NOA Number</Label>
                        <input
                            id="noa_no"
                            v-model="form.noa_no"
                            readonly
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Derived from SVP number and saved automatically.
                        </p>
                        <p
                            v-if="form.errors?.noa_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.noa_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="noa_date">NOA Date</Label>
                        <input
                            id="noa_date"
                            v-model="form.noa_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Must be on or after the BAC Resolution date.
                        </p>
                        <p
                            v-if="form.errors?.noa_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.noa_date }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="resolution_no">Resolution Number</Label>
                        <input
                            id="resolution_no"
                            v-model="form.resolution_no"
                            readonly
                            class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Fetched from selected BAC Resolution.
                        </p>
                        <p
                            v-if="form.errors?.resolution_no"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.resolution_no }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <Label for="resolution_date">Resolution Date</Label>
                        <input
                            id="resolution_date"
                            v-model="form.resolution_date"
                            type="date"
                            readonly
                            class="flex h-10 w-full rounded-md border border-input bg-muted px-3 py-2 text-sm"
                        />
                        <p class="text-xs text-muted-foreground">
                            Fetched from selected BAC Resolution.
                        </p>
                        <p
                            v-if="form.errors?.resolution_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.resolution_date }}
                        </p>
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="calculation_label">Calculated Label</Label>
                    <select
                        id="calculation_label"
                        v-model="form.calculation_label"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Select Calculated Label —</option>
                        <option
                            v-for="option in calculationLabelOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </option>
                    </select>
                    <p
                        v-if="form.errors?.calculation_label"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.calculation_label }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="winner_supplier_name">Supplier Name</Label>
                    <input
                        id="winner_supplier_name"
                        v-model="form.winner_supplier_name"
                        list="supplier-options"
                        class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <datalist id="supplier-options">
                        <option
                            v-for="supplier in suppliers || []"
                            :key="supplier.id"
                            :value="supplier.name"
                        />
                    </datalist>
                    <p class="text-xs text-muted-foreground">
                        Select from existing suppliers or type a custom name.
                    </p>
                    <p
                        v-if="form.errors?.winner_supplier_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.winner_supplier_name }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="recipient_name">Recipient Name</Label>
                    <div class="relative">
                        <input
                            id="recipient_name"
                            v-model="form.recipient_name"
                            class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            @focus="onRecipientFocus"
                            @click="onRecipientFocus"
                            @blur="onRecipientBlur"
                        />
                        <div
                            v-if="
                                showRepresentativeSuggestions &&
                                representativeSuggestions.length
                            "
                            class="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                        >
                            <button
                                v-for="person in representativeSuggestions"
                                :key="person"
                                type="button"
                                class="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                @mousedown.prevent="selectRecipientName(person)"
                            >
                                {{ person }}
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Type any name or select suggested proprietor, authorized
                        representative, or owner for the selected supplier.
                    </p>
                    <p
                        v-if="form.errors?.recipient_name"
                        class="text-xs text-destructive"
                    >
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
                            @focus="onRecipientTitleFocus"
                            @click="onRecipientTitleFocus"
                            @blur="onRecipientTitleBlur"
                        />
                        <div
                            v-if="showRecipientTitleSuggestions"
                            class="absolute z-20 mt-1 max-h-44 w-full overflow-auto rounded-md border border-input bg-background shadow-sm"
                        >
                            <button
                                v-for="title in recipientTitleSuggestions"
                                :key="title"
                                type="button"
                                class="block w-full px-3 py-2 text-left text-sm hover:bg-muted"
                                @mousedown.prevent="selectRecipientTitle(title)"
                            >
                                {{ title }}
                            </button>
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground">
                        Editable title for recipient. Suggested values are based
                        on supplier roles.
                    </p>
                    <p
                        v-if="form.errors?.recipient_title"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.recipient_title }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <Card v-if="selectedResolution">
            <CardHeader>
                <CardTitle class="text-base">Preview</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">SVP No.</p>
                    <p class="font-medium">
                        {{ selectedProjectAoq?.rfq?.svp_no || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">RFQ Date</p>
                    <p class="font-medium">
                        {{ selectedProjectAoq?.rfq?.rfq_date || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Resolution Date</p>
                    <p class="font-medium">
                        {{ selectedResolution.resolution_date || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project</p>
                    <p class="font-medium">
                        {{
                            selectedProjectAoq?.rfq?.project_name ||
                            selectedResolution.project_name ||
                            "—"
                        }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Amount</p>
                    <p class="font-medium">
                        {{ selectedResolution.winner_amount || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Winner Supplier</p>
                    <p class="font-medium">
                        {{ selectedResolution.winner_supplier_name || "—" }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="form.reset()"
                >Reset</Button
            >
            <Button type="submit" :disabled="form.processing">
                <Icon
                    v-if="form.processing"
                    icon="lucide:loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                />
                Create NOA
            </Button>
        </div>
    </form>
</template>
