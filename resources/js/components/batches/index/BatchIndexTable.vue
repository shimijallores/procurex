<script setup>
import { ref } from "vue";
import axios from "axios";
import { Link } from "@inertiajs/vue3";
import { Icon } from "@iconify/vue";
import { route } from "ziggy-js";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    batches: Object,
});

defineEmits(["delete-click"]);

const editing = ref(null);
const editField = ref(null);
const editValue = ref("");

const dateFields = [
    { key: "rfq_date", label: "RFQ" },
    { key: "aoq_date", label: "AOQ" },
    { key: "bac_date", label: "BAC" },
    { key: "noa_date", label: "NOA" },
    { key: "po_date", label: "PO" },
];

const startEdit = (batch, field) => {
    editing.value = batch.id;
    editField.value = field;
    editValue.value = batch[field] ?? "";
};

const cancelEdit = () => {
    editing.value = null;
    editField.value = null;
    editValue.value = "";
};

const saveDate = async (batch) => {
    const field = editField.value;
    if (!field) return;

    try {
        await axios.put(route("batches.update-dates", batch.id), {
            [field]: editValue.value || null,
        });
        batch[field] = editValue.value || null;
    } catch {
        //
    } finally {
        cancelEdit();
    }
};

const formatDate = (date) => {
    if (!date) return "—";
    return new Date(date).toLocaleDateString("en-US", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
};

const formatDateInput = (date) => {
    if (!date) return "";
    const d = new Date(date);
    const mm = String(d.getMonth() + 1).padStart(2, "0");
    const dd = String(d.getDate()).padStart(2, "0");
    const yyyy = d.getFullYear();
    return `${yyyy}-${mm}-${dd}`;
};
</script>

<template>
    <Card>
        <CardHeader>
            <div
                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <CardTitle>All Batches</CardTitle>
                    <CardDescription>
                        A list of all batches in the system
                    </CardDescription>
                </div>
                <div class="flex items-center gap-2">
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
                                class="h-12 px-3 text-left align-middle font-medium text-muted-foreground"
                            >
                                Batch No.
                            </th>
                            <th
                                v-for="df in dateFields"
                                :key="df.key"
                                class="h-12 px-3 text-center align-middle font-medium text-muted-foreground"
                            >
                                {{ df.label }}
                            </th>
                            <th
                                class="h-12 px-3 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="[&_tr:last-child]:border-0">
                        <tr
                            v-for="batch in batches.data"
                            :key="batch.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-3 align-middle font-medium">
                                <Link
                                    :href="route('batches.show', batch.id)"
                                    class="hover:underline"
                                >
                                    {{ batch.batch_no }}
                                </Link>
                            </td>
                            <td
                                v-for="df in dateFields"
                                :key="df.key"
                                class="p-3 align-middle text-center"
                            >
                                <div
                                    v-if="
                                        editing === batch.id &&
                                        editField === df.key
                                    "
                                    class="inline-flex items-center gap-1"
                                >
                                    <input
                                        type="date"
                                        :value="formatDateInput(editValue)"
                                        @input="editValue = $event.target.value"
                                        @keydown.enter="saveDate(batch)"
                                        @keydown.escape="cancelEdit"
                                        class="h-8 w-36 rounded border border-input bg-background px-2 text-xs"
                                        ref="dateInput"
                                        autofocus
                                    />
                                    <button
                                        @click="saveDate(batch)"
                                        class="text-green-600 hover:text-green-700"
                                    >
                                        <Icon
                                            icon="lucide:check"
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>
                                    <button
                                        @click="cancelEdit"
                                        class="text-muted-foreground hover:text-foreground"
                                    >
                                        <Icon
                                            icon="lucide:x"
                                            class="h-3.5 w-3.5"
                                        />
                                    </button>
                                </div>
                                <button
                                    v-else
                                    class="cursor-pointer hover:bg-accent rounded px-2 py-1 -mx-2 transition-colors"
                                    :class="{
                                        'text-muted-foreground': !batch[df.key],
                                    }"
                                    @click="startEdit(batch, df.key)"
                                >
                                    {{ formatDate(batch[df.key]) }}
                                </button>
                            </td>
                            <td class="p-3 align-middle text-right">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="route('batches.show', batch.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:eye"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Link
                                        :href="route('batches.edit', batch.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Icon
                                                icon="lucide:pencil"
                                                class="h-4 w-4"
                                            />
                                        </Button>
                                    </Link>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="$emit('delete-click', batch)"
                                    >
                                        <Icon
                                            icon="lucide:trash-2"
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="batches.data.length === 0">
                            <td
                                :colspan="dateFields.length + 2"
                                class="p-8 text-center"
                            >
                                <div class="flex flex-col items-center gap-2">
                                    <Icon
                                        icon="lucide:inbox"
                                        class="h-12 w-12 text-muted-foreground/50"
                                    />
                                    <p class="text-muted-foreground">
                                        No batches found
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between border-t pt-4">
                <div class="text-sm text-muted-foreground">
                    Showing {{ batches.from }} to {{ batches.to }} of
                    {{ batches.total }} batches
                </div>
                <div class="flex items-center gap-1">
                    <template
                        v-for="(link, index) in batches.links"
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
                            preserve-scroll
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
