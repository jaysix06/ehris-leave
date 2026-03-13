<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, FileText, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

type AwardRow = {
    award_title: string;
    category: string;
    school_year: string;
    award: string;
};

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

function valInput(v: unknown): string {
    if (v == null || String(v).trim() === '') {
        return '';
    }

    return String(v);
}

const props = defineProps<{
    serviceRecord?: Record<string, unknown>[];
    leaveHistory?: Record<string, unknown>[];
    documents?: Record<string, unknown>[];
    awards?: Record<string, unknown>[];
    awardsUpdateUrl?: string;
    performance?: Record<string, unknown>[];
    researches?: Record<string, unknown>[];
    expertise?: Record<string, unknown>[];
    affiliation?: Record<string, unknown>[];
}>();

const accordionOpen = ref<Record<string, boolean>>({
    serviceRecord: false,
    leaveHistory: false,
    documents: false,
    awards: false,
    performance: false,
    researches: false,
    expertise: false,
    affiliation: false,
});

const canEditAwards = computed(() => Boolean(props.awardsUpdateUrl));
const awardsProcessing = ref(false);
const awardsErrors = ref<Record<string, string>>({});
const awardsForm = ref<AwardRow[]>([]);

function emptyAwardRow(): AwardRow {
    return {
        award_title: '',
        category: '',
        school_year: '',
        award: '',
    };
}

function isAwardRowFilled(row: AwardRow): boolean {
    return (
        row.award_title.trim() !== ''
        || row.category.trim() !== ''
        || row.school_year.trim() !== ''
        || row.award.trim() !== ''
    );
}

function syncAwardsForm(): void {
    const rows = (props.awards ?? []).map((item) => ({
        award_title: valInput(item.award_title),
        category: valInput(item.category),
        school_year: valInput(item.school_year),
        award: valInput(item.award),
    }));

    if (rows.length === 0 && canEditAwards.value) {
        awardsForm.value = [emptyAwardRow()];
        return;
    }

    awardsForm.value = rows;
}

watch(
    () => [props.awards, canEditAwards.value] as const,
    () => {
        syncAwardsForm();
    },
    { immediate: true },
);

function addAwardRow(): void {
    awardsForm.value = [...awardsForm.value, emptyAwardRow()];
}

function removeAwardRow(index: number): void {
    if (awardsForm.value.length <= 1) {
        awardsForm.value = [emptyAwardRow()];
        return;
    }

    awardsForm.value = awardsForm.value.filter((_, idx) => idx !== index);
}

function submitAwards(): void {
    if (!props.awardsUpdateUrl) {
        return;
    }

    awardsProcessing.value = true;
    awardsErrors.value = {};

    const payload = awardsForm.value
        .filter((row) => isAwardRowFilled(row))
        .map((row) => ({ ...row }));

    router.post(
        props.awardsUpdateUrl,
        { awards: payload } as { awards: AwardRow[] },
        {
            preserveScroll: true,
            onFinish: () => {
                awardsProcessing.value = false;
            },
            onError: (errs) => {
                awardsErrors.value = (errs as Record<string, string>) || {};
            },
        },
    );
}

function toggleAccordion(key: string) {
    accordionOpen.value[key] = !accordionOpen.value[key];
}
</script>

<template>
    <div class="ehris-accordion">
        <!-- SERVICE RECORD -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.serviceRecord"
                @click="toggleAccordion('serviceRecord')"
            >
                <h3>Service Record</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.serviceRecord }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.serviceRecord" class="ehris-accordion-content">
                <div class="ehris-table-wrap" v-if="serviceRecord && serviceRecord.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>From</th>
                        <th>To</th>
                        <th>Designation</th>
                        <th>Status</th>
                        <th>Place of assignment</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in serviceRecord" :key="i">
                        <td>{{ val(item.service_from) }}</td>
                        <td>{{ val(item.service_to) }}</td>
                        <td>{{ val(item.designation) }}</td>
                        <td>{{ val(item.status) }}</td>
                        <td>{{ val(item.place_of_assign) }}</td>
                    </tr>
                </tbody>
            </table>
                </div>
                <p v-else class="ehris-muted">No service record on file.</p>
            </div>
        </div>

        <!-- LEAVE HISTORY -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.leaveHistory"
                @click="toggleAccordion('leaveHistory')"
            >
                <h3>Leave History</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.leaveHistory }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.leaveHistory" class="ehris-accordion-content">
                <div class="ehris-table-wrap" v-if="leaveHistory && leaveHistory.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Credits from</th>
                        <th>Credits to</th>
                        <th>Type</th>
                        <th>No. of days</th>
                        <th>Balance</th>
                        <th>Particulars</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in leaveHistory" :key="i">
                        <td>{{ val(item.credits_from) }}</td>
                        <td>{{ val(item.credits_to) }}</td>
                        <td>{{ val(item.type) }}</td>
                        <td>{{ val(item.no_of_days) }}</td>
                        <td>{{ val(item.balance) }}</td>
                        <td>{{ val(item.particulars) }}</td>
                    </tr>
                </tbody>
            </table>
                </div>
                <p v-else class="ehris-muted">No leave history on file.</p>
            </div>
        </div>

        <!-- DOCUMENTS -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.documents"
                @click="toggleAccordion('documents')"
            >
                <h3>Documents</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.documents }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.documents" class="ehris-accordion-content">
                <ul class="ehris-stacked-list" v-if="documents && documents.length">
            <li v-for="(item, i) in documents" :key="i" class="ehris-doc-row">
                <FileText class="size-4" />
                <span>{{ val(item.title) }}</span>
                <span v-if="item.document" class="ehris-muted"> – {{ val(item.document) }}</span>
            </li>
                </ul>
                <p v-else class="ehris-muted">No documents on file.</p>
            </div>
        </div>

        <!-- AWARDS -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.awards"
                @click="toggleAccordion('awards')"
            >
                <h3>Awards</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.awards }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.awards" class="ehris-accordion-content">
                <div v-if="canEditAwards" class="mb-3 flex items-center justify-end gap-2">
                    <Button type="button" size="sm" variant="outline" @click="addAwardRow">
                        <Plus class="mr-1 size-4" />
                        Add row
                    </Button>
                    <Button type="button" size="sm" :disabled="awardsProcessing" @click="submitAwards">
                        <Spinner v-if="awardsProcessing" class="mr-2 size-4" />
                        Save
                    </Button>
                </div>

                <p v-if="awardsErrors.message" class="ehris-form-error mb-3">{{ awardsErrors.message }}</p>

                <div class="ehris-table-wrap" v-if="awardsForm.length">
                    <table class="ehris-table">
                        <thead>
                            <tr>
                                <th>Award title</th>
                                <th>Category</th>
                                <th>School year</th>
                                <th>Award</th>
                                <th v-if="canEditAwards" class="w-[68px] text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(item, i) in awardsForm" :key="`award-row-${i}`">
                                <td>
                                    <Input v-if="canEditAwards" v-model="item.award_title" />
                                    <span v-else>{{ val(item.award_title) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEditAwards" v-model="item.category" />
                                    <span v-else>{{ val(item.category) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEditAwards" v-model="item.school_year" />
                                    <span v-else>{{ val(item.school_year) }}</span>
                                </td>
                                <td>
                                    <Input v-if="canEditAwards" v-model="item.award" />
                                    <span v-else>{{ val(item.award) }}</span>
                                </td>
                                <td v-if="canEditAwards" class="text-center">
                                    <Button type="button" variant="ghost" size="icon" @click="removeAwardRow(i)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-else class="ehris-muted">No awards on file.</p>
            </div>
        </div>

        <!-- PERFORMANCE -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.performance"
                @click="toggleAccordion('performance')"
            >
                <h3>Performance</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.performance }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.performance" class="ehris-accordion-content">
                <div class="ehris-table-wrap" v-if="performance && performance.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Year</th>
                        <th>CBC</th>
                        <th>Other competencies</th>
                        <th>KRA</th>
                        <th>Adjectival rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in performance" :key="i">
                        <td>{{ val(item.year) }}</td>
                        <td>{{ val(item.cbc) }}</td>
                        <td>{{ val(item.other_competencies) }}</td>
                        <td>{{ val(item.kra) }}</td>
                        <td>{{ val(item.adjectival_rating) }}</td>
                    </tr>
                </tbody>
            </table>
                </div>
                <p v-else class="ehris-muted">No performance records on file.</p>
            </div>
        </div>

        <!-- RESEARCHES -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.researches"
                @click="toggleAccordion('researches')"
            >
                <h3>Researches</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.researches }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.researches" class="ehris-accordion-content">
                <div class="ehris-table-wrap" v-if="researches && researches.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Year conducted</th>
                        <th>Category</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in researches" :key="i">
                        <td>{{ val(item.title_of_research) }}</td>
                        <td>{{ val(item.year_conducted) }}</td>
                        <td>{{ val(item.category) }}</td>
                    </tr>
                </tbody>
            </table>
                </div>
                <p v-else class="ehris-muted">No researches on file.</p>
            </div>
        </div>

        <!-- EXPERTISE -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.expertise"
                @click="toggleAccordion('expertise')"
            >
                <h3>Expertise</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.expertise }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.expertise" class="ehris-accordion-content">
                <ul class="ehris-stacked-list" v-if="expertise && expertise.length">
                    <li v-for="(item, i) in expertise" :key="i">
                        {{ val(item.expertise) }}
                    </li>
                </ul>
                <p v-else class="ehris-muted">No expertise on file.</p>
            </div>
        </div>

        <!-- AFFILIATION -->
        <div class="ehris-accordion-item">
            <button
                type="button"
                class="ehris-accordion-header"
                :aria-expanded="accordionOpen.affiliation"
                @click="toggleAccordion('affiliation')"
            >
                <h3>Membership in Association/Organization</h3>
                <div class="ehris-accordion-actions">
                    <ChevronDown
                        class="ehris-accordion-chevron"
                        :class="{ 'ehris-accordion-chevron-open': accordionOpen.affiliation }"
                    />
                </div>
            </button>
            <div v-show="accordionOpen.affiliation" class="ehris-accordion-content">
                <ul class="ehris-stacked-list" v-if="affiliation && affiliation.length">
                    <li v-for="(item, i) in affiliation" :key="i">
                        {{ val(item.affiliation ?? item.organization ?? item.org_name) }}
                    </li>
                </ul>
                <p v-else class="ehris-muted">No memberships on file.</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.ehris-accordion {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.ehris-accordion-item {
    background: white;
    border-radius: 0.5rem;
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.ehris-accordion-header {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    transition: background-color 0.2s;
}

.ehris-accordion-header:hover {
    background-color: #f9fafb;
}

.ehris-accordion-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #111827;
}

.ehris-accordion-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.ehris-accordion-chevron {
    width: 1.25rem;
    height: 1.25rem;
    color: #6b7280;
    transition: transform 0.2s;
}

.ehris-accordion-chevron-open {
    transform: rotate(180deg);
}

.ehris-accordion-content {
    padding: 0 1.25rem 1.25rem 1.25rem;
    animation: ehris-accordion-slide 0.2s ease-out;
}

@keyframes ehris-accordion-slide {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
