<script setup>
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useWorkingDayInputGuard } from "@/composables/useWorkingDayInputGuard";
import { NativeSelect } from "@/components/ui/native-select";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Label } from "@/components/ui/label";

const props = defineProps({
    form: Object,
    eligibleAoqs: Array,
    suppliers: Array,
    defaultNoaDate: String,
});

defineEmits(["submit"]);
const { enforceWorkingDay, getDateNotice, getDateNoticeClass } =
    useWorkingDayInputGuard(props.form);

const selectedAoq = computed(() =>
    props.eligibleAoqs?.find(
        (aoq) => String(aoq.id) === String(props.form.aoq_id),
    ),
);

const showRepresentativeSuggestions = ref(false);
const showRecipientTitleSuggestions = ref(false);

const recipientTitleSuggestions = [
    "Proprietor",
    "Authorized Representative",
    "Owner",
];

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

const suggestedDefaultNoaDate = normalizeDate(props.defaultNoaDate) || "";

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
    () => props.form.aoq_id,
    (id) => {
        const aoq = props.eligibleAoqs?.find(
            (entry) => String(entry.id) === String(id),
        );

        if (!aoq) {
            props.form.noa_no = "";
            props.form.winner_supplier_name = "";

            if (!props.form.noa_date) {
                props.form.noa_date = suggestedDefaultNoaDate;
            }

            return;
        }

        props.form.noa_no = aoq.rfq?.svp_no || "";
        props.form.winner_supplier_name =
            aoq.winner_supplier?.name || "";

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

watch(
    () => props.form.noa_date,
    async (date) => {
        await enforceWorkingDay({
            dateValue: date,
            errorKey: "noa_date",
            statusKey: "noa_date",
            clearDate: () => {
                props.form.noa_date = "";
            },
        });
    },
);
</script>

<template>
    <form @submit.prevent="$emit('submit')" class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2 text-base">
                    <Icon icon="lucide:link" class="h-4 w-4 text-primary" />
                    Source AOQ
                </CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
                <div class="space-y-2">
                    <Label for="aoq_id">Abstract of Quotation (AOQ)</Label>
                    <NativeSelect
                        id="aoq_id"
                        :model-value="form.aoq_id"
                        @update:model-value="form.aoq_id = $event"
                        class="w-full"
                    >
                        <option value="">— Select AOQ —</option>
                        <option
                            v-for="aoq in eligibleAoqs"
                            :key="aoq.id"
                            :value="aoq.id"
                        >
                            {{ aoq.rfq?.svp_no || "No SVP" }} —
                            {{ aoq.rfq?.project_name || "Project" }}
                        </option>
                    </NativeSelect>
                    <p
                        v-if="form.errors?.aoq_id"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.aoq_id }}
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
                        <p :class="getDateNoticeClass('noa_date')">
                            {{ getDateNotice("noa_date") }}
                        </p>
                        <p
                            v-if="form.errors?.noa_date"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors.noa_date }}
                        </p>
                    </div>
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

        <Card v-if="selectedAoq">
            <CardHeader>
                <CardTitle class="text-base">Preview</CardTitle>
            </CardHeader>
            <CardContent class="grid gap-3 text-sm md:grid-cols-2">
                <div>
                    <p class="text-muted-foreground">SVP No.</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.svp_no || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">RFQ Date</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.rfq_date || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Project</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.project_name || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Winner Amount</p>
                    <p class="font-medium">
                        {{ selectedAoq.winner_amount || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Winner Supplier</p>
                    <p class="font-medium">
                        {{ selectedAoq.winner_supplier?.name || "—" }}
                    </p>
                </div>
                <div>
                    <p class="text-muted-foreground">Office</p>
                    <p class="font-medium">
                        {{ selectedAoq.rfq?.purchase_request?.office?.name || "—" }}
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
