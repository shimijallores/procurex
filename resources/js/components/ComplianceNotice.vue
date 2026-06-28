<script setup>
import { ref } from "vue";
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
import { Checkbox } from "@/components/ui/checkbox";

defineProps({
    open: Boolean,
});

const emit = defineEmits(["update:open"]);

const agreed = ref(false);
const accepting = ref(false);

function acknowledge() {
    if (!agreed.value) return;

    accepting.value = true;
    router.post(
        route("compliance.acknowledge"),
        {},
        {
            onSuccess: () => {
                emit("update:open", false);
                accepting.value = false;
            },
            onError: () => {
                accepting.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open">
        <DialogContent
            class="max-w-2xl max-h-[90vh]"
            :show-close-button="false"
        >
            <DialogHeader>
                <DialogTitle class="text-lg">
                    Compliance Notice
                </DialogTitle>
                <DialogDescription>
                    Please review the following mandatory notices before
                    accessing the system.
                </DialogDescription>
            </DialogHeader>

            <div class="max-h-[55vh] overflow-y-auto scroll-smooth space-y-6 pr-1">
                <section class="space-y-2">
                    <h3 class="text-sm font-semibold">
                        Data Privacy Notice
                    </h3>
                    <div class="space-y-2 text-sm text-muted-foreground">
                        <p>
                            This system collects and processes personal
                            information in compliance with
                            <strong>
                                Republic Act No. 10173, otherwise known as
                                the Data Privacy Act of 2012
                            </strong>,
                            and its Implementing Rules and Regulations.
                        </p>
                        <p>
                            <strong>Legal Mandate:</strong>
                            The collection of personal data is authorized
                            under the procurement laws and regulations of the
                            Republic of the Philippines, including but not
                            limited to Republic Act No. 9184 (Government
                            Procurement Reform Act) and its revised
                            Implementing Rules and Regulations.
                        </p>
                        <p>
                            <strong>Purpose:</strong>
                            The information collected is used exclusively for
                            the procurement management, tracking, and
                            reporting purposes of the Provincial Government.
                        </p>
                        <p>
                            <strong>Storage and Retention:</strong>
                            Personal data is stored in secure servers and
                            retained for the period prescribed by the
                            National Archives of the Philippines. Data beyond
                            the retention period shall be disposed of in
                            accordance with NAP guidelines.
                        </p>
                        <p>
                            <strong>Data Protection Officer (DPO):</strong>
                            Any inquiries, concerns, or requests regarding
                            the processing of personal data may be directed
                            to the Provincial Government's Data Protection
                            Officer at the Provincial Capitol Building,
                            Batangas City, or through the official
                            communication channels of the Provincial
                            Government.
                        </p>
                    </div>
                </section>

                <hr class="border-border" />

                <section class="space-y-2">
                    <h3 class="text-sm font-semibold">
                        Authorized Use &amp; Monitoring
                    </h3>
                    <div class="space-y-2 text-sm text-muted-foreground">
                        <p>
                            This system is an
                            <strong>
                                official repository and procurement
                                management application of the Republic of the
                                Philippines
                            </strong>,
                            operated and maintained by the Provincial
                            Government of Batangas.
                        </p>
                        <p>
                            Access to this system is restricted to
                            authorized personnel only. Any unauthorized
                            access, use, modification, or disclosure of the
                            information contained herein is
                            <strong>
                                strictly prohibited and constitutes a
                                violation of Republic Act No. 10175 (Cybercrime Prevention Act of 2012)
                            </strong>.
                        </p>
                        <p>
                            <strong>
                                All user activities within this system,
                                including but not limited to data entry,
                                retrieval, modification, and file downloads,
                                are logged, monitored, and subject to audit
                                at any time.
                            </strong>
                        </p>
                        <p>
                            By proceeding, you expressly acknowledge and
                            consent to the monitoring, recording, and
                            auditing of your activities within this system.
                            Any evidence of unauthorized or illegal activity
                            may be provided to law enforcement authorities.
                        </p>
                    </div>
                </section>

                <hr class="border-border" />

                <section class="space-y-2">
                    <h3 class="text-sm font-semibold">
                        System of Records and Logs
                    </h3>
                    <div class="space-y-2 text-sm text-muted-foreground">
                        <p>
                            All records, documents, and logs generated
                            within this system are managed in accordance
                            with the guidelines and standards set by the
                            <strong>
                                National Archives of the Philippines (NAP)
                            </strong>.
                        </p>
                        <p>
                            <strong>Data Archival:</strong>
                            Records are archived periodically following the
                            prescribed retention schedules under NAP
                            regulations. Archived records shall remain
                            accessible for reference and audit purposes
                            throughout their retention period.
                        </p>
                        <p>
                            <strong>Data Disposal:</strong>
                            Upon expiration of the applicable retention
                            period, data shall be disposed of securely in a
                            manner that prevents unauthorized access or
                            reconstruction, strictly adhering to NAP
                            disposition guidelines.
                        </p>
                        <p>
                            <strong>Audit Trail:</strong>
                            Complete audit trails, including user identity,
                            timestamp, IP address, and actions performed,
                            are maintained for all transactions in compliance
                            with the Commission on Audit (COA) and NAP
                            documentation requirements.
                        </p>
                    </div>
                </section>
            </div>

            <div class="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
                <Checkbox
                    :id="'agree-compliance'"
                    v-model="agreed"
                    class="mt-0.5"
                />
                <label
                    for="agree-compliance"
                    class="text-xs leading-relaxed text-muted-foreground cursor-pointer"
                >
                    I have read and understood the above notices and agree to
                    comply with all stated policies, data privacy provisions,
                    and authorized use terms.
                </label>
            </div>

            <DialogFooter class="border-t border-border pt-4">
                <Button
                    size="lg"
                    class="w-full"
                    :disabled="!agreed || accepting"
                    @click="acknowledge"
                >
                    <Icon
                        icon="lucide:check-circle"
                        class="mr-2 size-4"
                    />
                    {{ accepting ? "Processing..." : "I Acknowledge & Proceed" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
