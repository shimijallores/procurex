<script setup>
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

defineProps({
    matrixRow: Object,
    form: Object,
    accounts: Array,
    prAdminUsers: Array,
    budgetingAdminUsers: Array,
});

defineEmits(["submit"]);
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>Update Matrix Row</CardTitle>
        </CardHeader>
        <CardContent>
            <form class="space-y-4" @submit.prevent="$emit('submit')">
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium"
                            >Amount Below 1M</label
                        >
                        <input
                            v-model="form.matrix_amount_below_1m"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_amount_below_1m"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_amount_below_1m }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium"
                            >Amount Above 1M</label
                        >
                        <input
                            v-model="form.matrix_amount_above_1m"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_amount_above_1m"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_amount_above_1m }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">New Amount</label>
                        <input
                            v-model="form.matrix_new_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_new_amount"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_new_amount }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium"
                            >Account / Charged To</label
                        >
                        <select
                            v-model="form.matrix_account_id"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Select account</option>
                            <option
                                v-for="account in accounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.matrix_account_id"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_account_id }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium"
                            >Person In Charge (PR Section)</label
                        >
                        <select
                            v-model="form.matrix_pr_admin_user_id"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Select PR Admin</option>
                            <option
                                v-for="user in prAdminUsers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.matrix_pr_admin_user_id"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_pr_admin_user_id }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium"
                            >Person In Charge (Budgeting)</label
                        >
                        <select
                            v-model="form.matrix_budgeting_admin_user_id"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Select Document Admin</option>
                            <option
                                v-for="user in budgetingAdminUsers"
                                :key="user.id"
                                :value="user.id"
                            >
                                {{ user.name }}
                            </option>
                        </select>
                        <p
                            v-if="form.errors.matrix_budgeting_admin_user_id"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_budgeting_admin_user_id }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label class="text-sm font-medium">Date Release</label>
                        <input
                            v-model="form.matrix_date_release"
                            type="date"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_date_release"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_date_release }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium"
                            >New Date Release</label
                        >
                        <input
                            v-model="form.matrix_new_date_release"
                            type="date"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_new_date_release"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_new_date_release }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Remarks</label>
                        <input
                            v-model="form.matrix_remarks"
                            type="text"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                        />
                        <p
                            v-if="form.errors.matrix_remarks"
                            class="mt-1 text-sm text-destructive"
                        >
                            {{ form.errors.matrix_remarks }}
                        </p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing"
                        >Save Changes</Button
                    >
                </div>
            </form>
        </CardContent>
    </Card>
</template>
