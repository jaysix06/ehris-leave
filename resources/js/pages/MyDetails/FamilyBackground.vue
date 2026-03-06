<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';

function val(v: unknown): string {
    if (v == null || v === '') return '';
    return String(v).trim();
}

type SpouseFields = {
    lastname: string;
    firstname: string;
    middlename: string;
    extension: string;
    occupation: string;
    employer_name: string;
    business_add: string;
    tel_num: string;
};

type ChildRow = { fullname: string; dob: string };

type ParentFields = {
    lastname: string;
    firstname: string;
    middlename: string;
    extension: string;
};

const emptySpouse = (): SpouseFields => ({
    lastname: '',
    firstname: '',
    middlename: '',
    extension: '',
    occupation: '',
    employer_name: '',
    business_add: '',
    tel_num: '',
});

const emptyParent = (): ParentFields => ({
    lastname: '',
    firstname: '',
    middlename: '',
    extension: '',
});

const props = defineProps<{
    family?: Record<string, unknown>[];
    familyUpdateUrl?: string;
}>();

const isEditing = ref(false);
const editModalOpen = ref(false);
const spouse = ref<SpouseFields>(emptySpouse());
const children = ref<ChildRow[]>([{ fullname: '', dob: '' }]);
const father = ref<ParentFields>(emptyParent());
const mother = ref<ParentFields>(emptyParent());
const processing = ref(false);
const errors = ref<Record<string, string>>({});

function parseFamily(fam: Record<string, unknown>[] | undefined): void {
    if (!fam || !fam.length) return;
    const childRows = fam.filter((item) => ['child', 'children'].includes((val(item.relationship) || '').toLowerCase()));
    if (childRows.length > 0) {
        children.value = childRows.map((item) => ({
            fullname: familyName(item),
            dob: val(item.dob),
        }));
    }
    for (const item of fam) {
        const rel = (val(item.relationship) || '').toLowerCase();
        if (rel === 'spouse') {
            spouse.value = {
                lastname: val(item.lastname),
                firstname: val(item.firstname),
                middlename: val(item.middlename),
                extension: val(item.extension),
                occupation: val(item.occupation),
                employer_name: val(item.employer_name),
                business_add: val(item.business_add),
                tel_num: val(item.tel_num),
            };
        } else if (rel === 'father') {
            father.value = {
                lastname: val(item.lastname),
                firstname: val(item.firstname),
                middlename: val(item.middlename),
                extension: val(item.extension),
            };
        } else if (rel === 'mother') {
            mother.value = {
                lastname: val(item.lastname),
                firstname: val(item.firstname),
                middlename: val(item.middlename),
                extension: '',
            };
        }
    }
    if (children.value.length === 0) {
        children.value = [{ fullname: '', dob: '' }];
    }
}

function familyName(item: Record<string, unknown>): string {
    const parts = [item.firstname, item.middlename, item.lastname, item.extension].filter(Boolean);
    return parts.map(String).join(' ').trim();
}

function relationOf(item: Record<string, unknown>): string {
    return val(item.relationship).toLowerCase();
}

function buildFamilyPayload(): Record<string, unknown>[] {
    const out: Record<string, unknown>[] = [];
    const s = spouse.value;
    if (s.lastname || s.firstname || s.middlename || s.occupation || s.employer_name || s.business_add || s.tel_num) {
        out.push({
            relationship: 'Spouse',
            lastname: s.lastname || null,
            firstname: s.firstname || null,
            middlename: s.middlename || null,
            extension: s.extension || null,
            occupation: s.occupation || null,
            employer_name: s.employer_name || null,
            business_add: s.business_add || null,
            tel_num: s.tel_num || null,
            dob: null,
        });
    }
    for (const c of children.value) {
        if (c.fullname || c.dob) {
            out.push({
                relationship: 'Child',
                lastname: null,
                firstname: c.fullname || null,
                middlename: null,
                extension: null,
                occupation: null,
                employer_name: null,
                business_add: null,
                tel_num: null,
                dob: c.dob || null,
            });
        }
    }
    const f = father.value;
    if (f.lastname || f.firstname || f.middlename) {
        out.push({
            relationship: 'Father',
            lastname: f.lastname || null,
            firstname: f.firstname || null,
            middlename: f.middlename || null,
            extension: f.extension || null,
            occupation: null,
            employer_name: null,
            business_add: null,
            tel_num: null,
            dob: null,
        });
    }
    const m = mother.value;
    if (m.lastname || m.firstname || m.middlename) {
        out.push({
            relationship: 'Mother',
            lastname: m.lastname || null,
            firstname: m.firstname || null,
            middlename: m.middlename || null,
            extension: null,
            occupation: null,
            employer_name: null,
            business_add: null,
            tel_num: null,
            dob: null,
        });
    }
    return out;
}

function openEdit(): void {
    parseFamily(props.family);
    if (children.value.length === 0) {
        children.value = [{ fullname: '', dob: '' }];
    }
    errors.value = {};
    isEditing.value = true;
    editModalOpen.value = true;
}

function cancelEdit(): void {
    isEditing.value = false;
    editModalOpen.value = false;
}

function addChild(): void {
    children.value = [...children.value, { fullname: '', dob: '' }];
}

function removeChild(index: number): void {
    if (children.value.length <= 1) return;
    children.value = children.value.filter((_, i) => i !== index);
}

function submit(): void {
    if (!props.familyUpdateUrl) return;
    processing.value = true;
    errors.value = {};
    const family = buildFamilyPayload();
    router.post(
        props.familyUpdateUrl,
        { family } as Parameters<typeof router.post>[1],
        {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            isEditing.value = false;
            editModalOpen.value = false;
        },
        onError: (errs) => {
            errors.value = (errs as Record<string, string>) || {};
        },
    });
}

watch(() => props.family, (next) => {
    if (!isEditing.value && next && next.length > 0) {
        parseFamily(next);
    }
}, { immediate: true });

const familyRows = computed(() => props.family ?? []);
const spouseEntry = computed<Record<string, unknown> | null>(() => {
    return familyRows.value.find((item) => relationOf(item) === 'spouse') ?? null;
});
const childrenEntries = computed<Record<string, unknown>[]>(() => {
    return familyRows.value.filter((item) => {
        const rel = relationOf(item);
        return rel === 'child' || rel === 'children';
    });
});
const fatherEntry = computed<Record<string, unknown> | null>(() => {
    return familyRows.value.find((item) => relationOf(item) === 'father') ?? null;
});
const motherEntry = computed<Record<string, unknown> | null>(() => {
    return familyRows.value.find((item) => relationOf(item) === 'mother') ?? null;
});

const canEdit = computed(() => Boolean(props.familyUpdateUrl));
const hasFamily = computed(() => Boolean(
    spouseEntry.value
    || childrenEntries.value.length > 0
    || fatherEntry.value
    || motherEntry.value
));

function displayVal(v: unknown): string {
    if (v == null || v === '') return '—';
    return String(v);
}

// Default to view mode; user clicks Edit to add or change. Do not auto-open edit when empty.
onMounted(() => {
    if (props.family && props.family.length > 0) {
        parseFamily(props.family);
    }
});
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Family information</h3>
            <button
                type="button"
                class="ehris-btn-grade-subject"
                aria-label="Edit family information"
                :disabled="!canEdit"
                @click="openEdit"
            >
                <Pencil class="size-4" />
                <span>Edit</span>
            </button>
        </div>

        <!-- View mode: PDS-style (same layout as edit form – sections 22–25, gray headers, label | value rows) -->
        <template v-if="!isEditing">
            <template v-if="hasFamily">
                <div class="ehris-pds-family-form ehris-pds-family-view">
                    <div class="ehris-pds-family-view-grid">
                    <!-- 22. Spouse -->
                    <div v-if="spouseEntry" class="ehris-pds-section ehris-pds-section--full">
                        <div class="ehris-pds-section-title">
                            <span class="ehris-pds-section-no">22.</span>
                            <span>SPOUSE DETAILS</span>
                        </div>
                        <template>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">SPOUSE'S SURNAME</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.lastname) }}</div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">FIRST NAME</div>
                                <div class="ehris-pds-value ehris-pds-value-group">
                                    {{ displayVal(spouseEntry.firstname) }}
                                    <span class="ehris-pds-label-inline">NAME EXTENSION (JR., SR.)</span>
                                    {{ displayVal(spouseEntry.extension) }}
                                </div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">MIDDLE NAME</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.middlename) }}</div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">OCCUPATION</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.occupation) }}</div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">EMPLOYER/BUSINESS NAME</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.employer_name) }}</div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">BUSINESS ADDRESS</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.business_add) }}</div>
                            </div>
                            <div class="ehris-pds-row">
                                <div class="ehris-pds-label">TELEPHONE NO.</div>
                                <div class="ehris-pds-value">{{ displayVal(spouseEntry.tel_num) }}</div>
                            </div>
                        </template>
                    </div>

                    <!-- 23. Children -->
                    <div v-if="childrenEntries.length > 0" class="ehris-pds-section ehris-pds-section--full">
                        <div class="ehris-pds-section-title ehris-pds-section-title-cols">
                            <span class="ehris-pds-section-title-main">
                                <span class="ehris-pds-section-no">23.</span>
                                <span>NAME OF CHILDREN (Write full name and list all)</span>
                            </span>
                            <span>DATE OF BIRTH (dd/mm/yyyy)</span>
                        </div>
                        <div
                            v-for="(item, i) in childrenEntries"
                            :key="i"
                            class="ehris-pds-row ehris-pds-row-children"
                        >
                            <div class="ehris-pds-value">{{ familyName(item) }}</div>
                            <div class="ehris-pds-value ehris-pds-value-dob">{{ displayVal(item.dob) }}</div>
                        </div>
                    </div>

                    <!-- 24. Father -->
                    <div v-if="fatherEntry" class="ehris-pds-section">
                        <div class="ehris-pds-section-title">
                            <span class="ehris-pds-section-no">24.</span>
                            <span>FATHER'S DETAILS</span>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">FATHER'S SURNAME</div>
                            <div class="ehris-pds-value">{{ displayVal(fatherEntry.lastname) }}</div>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">FIRST NAME</div>
                            <div class="ehris-pds-value ehris-pds-value-group">
                                {{ displayVal(fatherEntry.firstname) }}
                                <span class="ehris-pds-label-inline">NAME EXTENSION (JR., SR.)</span>
                                {{ displayVal(fatherEntry.extension) }}
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">MIDDLE NAME</div>
                            <div class="ehris-pds-value">{{ displayVal(fatherEntry.middlename) }}</div>
                        </div>
                    </div>

                    <!-- 25. Mother -->
                    <div v-if="motherEntry" class="ehris-pds-section">
                        <div class="ehris-pds-section-title">
                            <span class="ehris-pds-section-no">25.</span>
                            <span>MOTHER'S MAIDEN NAME</span>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">SURNAME</div>
                            <div class="ehris-pds-value">{{ displayVal(motherEntry.lastname) }}</div>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">FIRST NAME</div>
                            <div class="ehris-pds-value">{{ displayVal(motherEntry.firstname) }}</div>
                        </div>
                        <div class="ehris-pds-row">
                            <div class="ehris-pds-label">MIDDLE NAME</div>
                            <div class="ehris-pds-value">{{ displayVal(motherEntry.middlename) }}</div>
                        </div>
                    </div>
                    </div>
                </div>
            </template>
            <p v-else class="ehris-muted">No family information on file.</p>
        </template>

        <!-- Edit mode: form in a modal -->
        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; if (!v) cancelEdit(); }">
            <DialogContent
                :show-close-button="true"
                class="ehris-family-dialog sm:max-w-3xl max-h-[90vh] overflow-hidden flex flex-col"
            >
                <DialogHeader>
                    <DialogTitle>Edit Family Background</DialogTitle>
                </DialogHeader>
                <form @submit.prevent="submit" class="ehris-family-form flex flex-col min-h-0 flex-1">
                    <div v-if="Object.keys(errors).length" class="ehris-family-errors shrink-0">
                        <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                    </div>
                    <div class="flex-1 min-h-0 overflow-y-auto pr-2">

                <!-- PDS-style form: sections 22–25, label | input rows -->
                <div class="ehris-pds-family-form">
                    <!-- 22. Spouse -->
                    <div class="ehris-pds-section">
                        <div class="ehris-pds-section-title">22. SPOUSE'S SURNAME</div>
                        <div class="ehris-pds-row">
                            <label for="spouse-lastname" class="ehris-pds-label">SPOUSE'S SURNAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-lastname" v-model="spouse.lastname" type="text" class="ehris-pds-input" placeholder="Surname" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-firstname" class="ehris-pds-label">FIRST NAME</label>
                            <div class="ehris-pds-input-wrap ehris-pds-input-group">
                                <Input id="spouse-firstname" v-model="spouse.firstname" type="text" class="ehris-pds-input" placeholder="First name" />
                                <span class="ehris-pds-label-inline">NAME EXTENSION (JR., SR.)</span>
                                <Input id="spouse-extension" v-model="spouse.extension" type="text" class="ehris-pds-input ehris-pds-input-ext" placeholder="Jr., Sr." />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-middlename" class="ehris-pds-label">MIDDLE NAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-middlename" v-model="spouse.middlename" type="text" class="ehris-pds-input" placeholder="Middle name" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-occupation" class="ehris-pds-label">OCCUPATION</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-occupation" v-model="spouse.occupation" type="text" class="ehris-pds-input" placeholder="Occupation" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-employer" class="ehris-pds-label">EMPLOYER/BUSINESS NAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-employer" v-model="spouse.employer_name" type="text" class="ehris-pds-input" placeholder="Employer or business name" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-business-add" class="ehris-pds-label">BUSINESS ADDRESS</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-business-add" v-model="spouse.business_add" type="text" class="ehris-pds-input" placeholder="Business address" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="spouse-tel" class="ehris-pds-label">TELEPHONE NO.</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="spouse-tel" v-model="spouse.tel_num" type="text" class="ehris-pds-input" placeholder="Telephone number" />
                            </div>
                        </div>
                    </div>

                    <!-- 23. Children -->
                    <div class="ehris-pds-section">
                        <div class="ehris-pds-section-title ehris-pds-section-title-cols">
                            <span>23. NAME OF CHILDREN (Write full name and list all)</span>
                            <span>DATE OF BIRTH (dd/mm/yyyy)</span>
                        </div>
                        <template v-for="(child, index) in children" :key="index">
                            <div class="ehris-pds-row ehris-pds-row-children">
                                <div class="ehris-pds-input-wrap">
                                    <Input :id="`child-name-${index}`" v-model="child.fullname" type="text" class="ehris-pds-input" placeholder="Full name" />
                                </div>
                                <div class="ehris-pds-input-wrap ehris-pds-input-dob">
                                    <Input :id="`child-dob-${index}`" v-model="child.dob" type="text" class="ehris-pds-input" placeholder="dd/mm/yyyy" />
                                </div>
                                <div class="ehris-pds-actions">
                                    <Button type="button" variant="ghost" size="icon" aria-label="Remove" :disabled="children.length <= 1" @click="removeChild(index)">
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </template>
                        <div class="ehris-add-child-wrap">
                            <Button type="button" variant="outline" size="sm" :disabled="processing" @click="addChild">
                                <Plus class="size-4 mr-1" />
                                Add child
                            </Button>
                        </div>
                    </div>

                    <!-- 24. Father -->
                    <div class="ehris-pds-section">
                        <div class="ehris-pds-section-title">24. FATHER'S SURNAME</div>
                        <div class="ehris-pds-row">
                            <label for="father-lastname" class="ehris-pds-label">FATHER'S SURNAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="father-lastname" v-model="father.lastname" type="text" class="ehris-pds-input" placeholder="Surname" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="father-firstname" class="ehris-pds-label">FIRST NAME</label>
                            <div class="ehris-pds-input-wrap ehris-pds-input-group">
                                <Input id="father-firstname" v-model="father.firstname" type="text" class="ehris-pds-input" placeholder="First name" />
                                <span class="ehris-pds-label-inline">NAME EXTENSION (JR., SR.)</span>
                                <Input id="father-extension" v-model="father.extension" type="text" class="ehris-pds-input ehris-pds-input-ext" placeholder="Jr., Sr." />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="father-middlename" class="ehris-pds-label">MIDDLE NAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="father-middlename" v-model="father.middlename" type="text" class="ehris-pds-input" placeholder="Middle name" />
                            </div>
                        </div>
                    </div>

                    <!-- 25. Mother -->
                    <div class="ehris-pds-section">
                        <div class="ehris-pds-section-title">25. MOTHER'S MAIDEN NAME</div>
                        <div class="ehris-pds-row">
                            <label for="mother-lastname" class="ehris-pds-label">SURNAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="mother-lastname" v-model="mother.lastname" type="text" class="ehris-pds-input" placeholder="Surname" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="mother-firstname" class="ehris-pds-label">FIRST NAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="mother-firstname" v-model="mother.firstname" type="text" class="ehris-pds-input" placeholder="First name" />
                            </div>
                        </div>
                        <div class="ehris-pds-row">
                            <label for="mother-middlename" class="ehris-pds-label">MIDDLE NAME</label>
                            <div class="ehris-pds-input-wrap">
                                <Input id="mother-middlename" v-model="mother.middlename" type="text" class="ehris-pds-input" placeholder="Middle name" />
                            </div>
                        </div>
                    </div>
                </div>

                    </div>

                <DialogFooter class="mt-4 shrink-0">
                    <DialogClose as-child>
                        <Button type="button" variant="ghost" :disabled="processing">
                            Cancel
                        </Button>
                    </DialogClose>
                    <Button
                        type="submit"
                        :disabled="processing"
                    >
                        <Spinner v-if="processing" class="size-4 mr-1" />
                        Save
                    </Button>
                </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </section>
</template>

<style scoped>
.ehris-family-form {
    width: 100%;
    max-width: 100%;
}
.ehris-family-errors {
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--destructive));
    background: hsl(var(--destructive) / 0.08);
    color: hsl(var(--destructive));
    font-size: 0.875rem;
}

/* PDS-style family form: gray section headers, label | input rows, uniform field size */
.ehris-pds-family-form {
    width: 100%;
    font-size: 0.875rem;
}
.ehris-pds-family-view-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
    align-items: start;
}
.ehris-pds-section {
    margin-bottom: 1.25rem;
}
.ehris-pds-section--full {
    grid-column: 1 / -1;
}
.ehris-pds-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-bottom: none;
}
.ehris-pds-section-no {
    width: 2.25rem;
    text-align: right;
    flex: 0 0 2.25rem;
}
.ehris-pds-section-title-cols {
    display: grid;
    grid-template-columns: 1fr 140px;
    gap: 0.5rem;
}
.ehris-pds-section-title-main {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.ehris-pds-row {
    display: grid;
    grid-template-columns: 180px 1fr;
    gap: 0;
    align-items: stretch;
    border-left: 1px solid hsl(var(--border));
    border-right: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}
.ehris-pds-row:first-of-type {
    border-top: 1px solid hsl(var(--border));
}
.ehris-pds-row-children {
    grid-template-columns: 1fr 140px auto;
    border-top: 1px solid hsl(var(--border));
}
.ehris-pds-row-children .ehris-pds-input-wrap:first-child {
    border-right: 1px solid hsl(var(--border));
}
.ehris-pds-label {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    display: flex;
    align-items: center;
    border-right: 1px solid hsl(var(--border));
}
.ehris-pds-input-wrap {
    padding: 0.25rem 0.5rem;
    display: flex;
    align-items: center;
    min-height: 2.25rem;
    min-width: 0;
}
.ehris-pds-input-wrap.ehris-pds-input-group {
    flex-wrap: wrap;
    gap: 0.5rem 0.75rem;
    align-items: center;
}
.ehris-pds-label-inline {
    font-size: 0.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
    margin-left: 0.25rem;
}
.ehris-pds-input {
    width: 100%;
    min-width: 0;
    height: 2rem;
}
.ehris-pds-family-form :deep(.ehris-pds-input) {
    height: 2rem;
    min-height: 2rem;
}
.ehris-pds-input-ext {
    max-width: 5rem;
}
.ehris-pds-input-dob {
    max-width: 140px;
}
.ehris-pds-actions {
    display: flex;
    align-items: center;
    padding: 0 0.25rem;
    border-left: 1px solid hsl(var(--border));
}
/* View mode: read-only value cells styled like text fields (bordered, rounded, background) */
.ehris-pds-family-view .ehris-pds-value {
    padding: 0.5rem 0.75rem;
    min-height: 2.25rem;
    display: flex;
    align-items: center;
    min-width: 0;
    border-right: none;
    border-radius: 0.375rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--muted) / 0.5);
    color: hsl(var(--foreground));
}
.ehris-pds-family-view .ehris-pds-value-group {
    flex-wrap: wrap;
    gap: 0 0.75rem;
    align-items: center;
}
.ehris-pds-family-view .ehris-pds-value-group .ehris-pds-label-inline {
    margin-left: 0.5rem;
}
.ehris-pds-family-view .ehris-pds-value-dob {
    max-width: 140px;
    border-left: 1px solid hsl(var(--border));
}
.ehris-pds-family-view .ehris-pds-row-children .ehris-pds-value:first-child {
    border-right: 1px solid hsl(var(--border));
}
.ehris-add-child-wrap {
    margin-top: 0.75rem;
    padding: 0.5rem 0;
}
.ehris-family-note {
    font-size: 0.75rem;
    color: hsl(var(--destructive));
    margin-top: 1rem;
    margin-bottom: 0.5rem;
}
@media (max-width: 960px) {
    .ehris-pds-family-view-grid {
        grid-template-columns: 1fr;
    }
}
@media (max-width: 640px) {
    .ehris-pds-row {
        grid-template-columns: 1fr;
    }
    .ehris-pds-row .ehris-pds-label {
        border-right: none;
        border-bottom: 1px solid hsl(var(--border));
    }
    .ehris-pds-row-children {
        grid-template-columns: 1fr auto;
    }
    .ehris-pds-section-title-cols {
        grid-template-columns: 1fr;
    }
}
</style>
