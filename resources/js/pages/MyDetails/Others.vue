<script setup lang="ts">
import { FileText, Pencil } from 'lucide-vue-next';

function val(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

defineProps<{
    serviceRecord?: Record<string, unknown>[];
    leaveHistory?: Record<string, unknown>[];
    documents?: Record<string, unknown>[];
    awards?: Record<string, unknown>[];
    performance?: Record<string, unknown>[];
    researches?: Record<string, unknown>[];
    expertise?: Record<string, unknown>[];
}>();
</script>

<template>
    <!-- SERVICE RECORD -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Service Record</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit service record">
                <Pencil class="size-4" />
            </button>
        </div>
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
    </section>

    <!-- LEAVE HISTORY -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Leave History</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit leave history">
                <Pencil class="size-4" />
            </button>
        </div>
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
    </section>

    <!-- DOCUMENTS -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Documents</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit documents">
                <Pencil class="size-4" />
            </button>
        </div>
        <ul class="ehris-stacked-list" v-if="documents && documents.length">
            <li v-for="(item, i) in documents" :key="i" class="ehris-doc-row">
                <FileText class="size-4" />
                <span>{{ val(item.title) }}</span>
                <span v-if="item.document" class="ehris-muted"> – {{ val(item.document) }}</span>
            </li>
        </ul>
        <p v-else class="ehris-muted">No documents on file.</p>
    </section>

    <!-- AWARDS -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Awards</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit awards">
                <Pencil class="size-4" />
            </button>
        </div>
        <div class="ehris-table-wrap" v-if="awards && awards.length">
            <table class="ehris-table">
                <thead>
                    <tr>
                        <th>Award title</th>
                        <th>Category</th>
                        <th>School year</th>
                        <th>Award</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(item, i) in awards" :key="i">
                        <td>{{ val(item.award_title) }}</td>
                        <td>{{ val(item.category) }}</td>
                        <td>{{ val(item.school_year) }}</td>
                        <td>{{ val(item.award) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-else class="ehris-muted">No awards on file.</p>
    </section>

    <!-- PERFORMANCE -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Performance</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit performance">
                <Pencil class="size-4" />
            </button>
        </div>
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
    </section>

    <!-- RESEARCHES -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Researches</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit researches">
                <Pencil class="size-4" />
            </button>
        </div>
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
    </section>

    <!-- EXPERTISE -->
    <section class="ehris-card">
        <div class="ehris-card-header">
            <h3>Expertise</h3>
            <button type="button" class="ehris-edit-btn" aria-label="Edit expertise">
                <Pencil class="size-4" />
            </button>
        </div>
        <ul class="ehris-stacked-list" v-if="expertise && expertise.length">
            <li v-for="(item, i) in expertise" :key="i">
                {{ val(item.expertise) }}
            </li>
        </ul>
        <p v-else class="ehris-muted">No expertise on file.</p>
    </section>
</template>
