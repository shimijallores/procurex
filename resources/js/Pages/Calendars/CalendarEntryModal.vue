<script setup>
import { ref, watch, computed, reactive } from "vue";
import { router } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";

const props = defineProps({
    open: { type: Boolean, default: false },
    entry: { type: Object, default: null },
});

const emit = defineEmits(["update:open"]);

const isOpen = ref(props.open);
const isProcessing = ref(false);
const errors = ref({});

watch(
    () => props.open,
    (value) => {
        isOpen.value = value;
        if (value) {
            resetForm();
        }
    },
);

watch(isOpen, (value) => {
    emit("update:open", value);
});

const isEditing = computed(() => props.entry?.id);

const formData = reactive({
    date: "",
    type: "holiday",
    name: "",
    remarks: "",
});

const resetForm = () => {
    if (props.entry?.id) {
        // Editing existing entry
        formData.date = props.entry.date;
        formData.type = props.entry.type;
        formData.name = props.entry.name || "";
        formData.remarks = props.entry.remarks || "";
    } else {
        // Creating new entry
        formData.date = props.entry?.date || "";
        formData.type = "holiday";
        formData.name = "";
        formData.remarks = "";
    }
    errors.value = {};
};

const typeOptions = [
    { value: "holiday", label: "Holiday", icon: "lucide:party-popper" },
    {
        value: "special_workday",
        label: "Special Workday",
        icon: "lucide:briefcase",
    },
    { value: "blackout", label: "Blackout", icon: "lucide:ban" },
    {
        value: "suspended",
        label: "Suspended (Typhoon/Weather)",
        icon: "lucide:cloud-rain",
    },
];

const submit = () => {
    isProcessing.value = true;
    errors.value = {};

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            isOpen.value = false;
            isProcessing.value = false;
        },
        onError: (err) => {
            errors.value = err;
            isProcessing.value = false;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
    };

    if (isEditing.value) {
        router.put(
            route("calendars.update", props.entry.id),
            formData,
            options,
        );
    } else {
        router.post(route("calendars.store"), formData, options);
    }
};
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-[500px]">
            <DialogHeader>
                <DialogTitle>
                    {{
                        isEditing ? "Edit Calendar Entry" : "Add Calendar Entry"
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        isEditing
                            ? "Update the calendar entry details."
                            : "Add a new entry to the system calendar."
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="date"
                        >Date <span class="text-destructive">*</span></Label
                    >
                    <Input
                        id="date"
                        v-model="formData.date"
                        type="date"
                        :class="{ 'border-destructive': errors.date }"
                        required
                    />
                    <p v-if="errors.date" class="text-sm text-destructive">
                        {{ errors.date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="type"
                        >Type <span class="text-destructive">*</span></Label
                    >
                    <Select v-model="formData.type" required>
                        <SelectTrigger
                            :class="{ 'border-destructive': errors.type }"
                        >
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectGroup>
                                <SelectItem
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    <div class="flex items-center gap-2">
                                        <Icon
                                            :icon="option.icon"
                                            class="h-4 w-4"
                                        />
                                        {{ option.label }}
                                    </div>
                                </SelectItem>
                            </SelectGroup>
                        </SelectContent>
                    </Select>
                    <p v-if="errors.type" class="text-sm text-destructive">
                        {{ errors.type }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="name">Name</Label>
                    <Input
                        id="name"
                        v-model="formData.name"
                        type="text"
                        placeholder="e.g., New Year's Day, Typhoon Signal #8"
                        :class="{ 'border-destructive': errors.name }"
                    />
                    <p v-if="errors.name" class="text-sm text-destructive">
                        {{ errors.name }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Optional: A descriptive name for this entry
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="remarks">Remarks</Label>
                    <textarea
                        id="remarks"
                        v-model="formData.remarks"
                        rows="3"
                        placeholder="Additional notes or remarks..."
                        :class="[
                            'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                            { 'border-destructive': errors.remarks },
                        ]"
                    ></textarea>
                    <p v-if="errors.remarks" class="text-sm text-destructive">
                        {{ errors.remarks }}
                    </p>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                        :disabled="isProcessing"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="isProcessing">
                        <Icon
                            v-if="isProcessing"
                            icon="lucide:loader-2"
                            class="mr-2 h-4 w-4 animate-spin"
                        />
                        {{ isEditing ? "Update" : "Create" }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
