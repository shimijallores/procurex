<script setup>
import { ref } from "vue";
import { Link, router } from "@inertiajs/vue3";
import { Button } from "@/components/ui/button";
import { Icon } from "@iconify/vue";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Label } from "@/components/ui/label";
import PageTitle from "@/components/PageTitle.vue";
import { useForm } from "@inertiajs/vue3";

defineProps({
    prAdmins: Array,
});

const importOpen = ref(false);

const form = useForm({
    admin_id: "",
    file: null,
});

function handleFileChange(e) {
    form.file = e.target.files[0] || null;
}

function submitImport() {
    form.post(route("purchase-requests.import"), {
        preserveScroll: true,
        onSuccess: () => {
            importOpen.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="space-y-1">
            <PageTitle title="Purchase Requests" />
            <p class="text-muted-foreground">
                Manage all purchase requests from canvassed emanating requests
            </p>
        </div>
        <div class="flex items-center space-x-2">
            <Dialog v-model:open="importOpen">
                <DialogTrigger as-child>
                    <Button variant="outline">
                        <Icon icon="lucide:upload" class="mr-2 h-4 w-4" />
                        Import Excel
                    </Button>
                </DialogTrigger>
                <DialogContent class="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Import Purchase Requests</DialogTitle>
                        <DialogDescription>
                            Upload a PR Excel file (.xls) to bulk import purchase
                            requests. Select the PR admin who manages this file.
                        </DialogDescription>
                    </DialogHeader>
                    <form @submit.prevent="submitImport">
                        <div class="grid gap-4 py-4">
                            <div class="grid gap-2">
                                <Label for="admin">PR Admin</Label>
                                <Select
                                    v-model="form.admin_id"
                                    name="admin_id"
                                >
                                    <SelectTrigger id="admin">
                                        <SelectValue
                                            placeholder="Select an admin"
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="admin in prAdmins"
                                            :key="admin.id"
                                            :value="String(admin.id)"
                                        >
                                            {{ admin.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="grid gap-2">
                                <Label for="file">Excel File (.xls)</Label>
                                <input
                                    id="file"
                                    type="file"
                                    accept=".xls,.xlsx"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                    @change="handleFileChange"
                                />
                            </div>
                        </div>
                        <DialogFooter>
                            <Button
                                type="button"
                                variant="outline"
                                @click="importOpen = false"
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                :disabled="!form.admin_id || !form.file || form.processing"
                            >
                                <Icon
                                    v-if="form.processing"
                                    icon="lucide:loader-2"
                                    class="mr-2 h-4 w-4 animate-spin"
                                />
                                Import
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Link :href="route('purchase-requests.create')">
                <Button>
                    <Icon icon="lucide:plus" class="mr-2 h-4 w-4" />
                    New Purchase Request
                </Button>
            </Link>
        </div>
    </div>
</template>
