<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
}

function cancelEdit(): void {
    isEditing.value = false;
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

const canEdit = computed(() => Boolean(props.familyUpdateUrl));
const hasFamily = computed(() => props.family && props.family.length > 0);

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
        <div class="ehris-card-header">
            <h3>II. Family Background</h3>
            <button
                v-if="canEdit && !isEditing"
                type="button"
                class="ehris-edit-btn"
                aria-label="Edit family"
                @click="openEdit"
            >
                <Pencil class="size-4" />
            </button>
        </div>

        <!-- View mode: read-only like Official Info (dt/dd grid) -->
        <template v-if="!isEditing">
            <template v-if="hasFamily">
                <div class="ehris-official-info-grid ehris-family-view-grid">
                    <dl class="ehris-official-info-col">
                        <template v-if="family && family.some((f) => String(f.relationship || '').toLowerCase() === 'spouse')">
                            <div class="ehris-details-row"><dt>Spouse's surname</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.lastname) }}</dd></div>
                            <div class="ehris-details-row"><dt>First name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.firstname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Middle name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.middlename) }}</dd></div>
                            <div class="ehris-details-row"><dt>Name extension</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.extension) }}</dd></div>
                            <div class="ehris-details-row"><dt>Occupation</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.occupation) }}</dd></div>
                        </template>
                        <template v-if="family && family.filter((f) => String(f.relationship || '').toLowerCase() === 'child').length">
                            <div v-for="(item, i) in (family || []).filter((f) => String(f.relationship || '').toLowerCase() === 'child')" :key="i" class="ehris-details-row">
                                <dt>Child {{ i + 1 }}</dt>
                                <dd>{{ familyName(item) }} – {{ displayVal(item.dob) }}</dd>
                            </div>
                        </template>
                    </dl>
                    <dl class="ehris-official-info-col">
                        <template v-if="family && family.some((f) => String(f.relationship || '').toLowerCase() === 'spouse')">
                            <div class="ehris-details-row"><dt>Employer / Business name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.employer_name) }}</dd></div>
                            <div class="ehris-details-row"><dt>Business address</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.business_add) }}</dd></div>
                            <div class="ehris-details-row"><dt>Telephone no.</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'spouse')?.tel_num) }}</dd></div>
                        </template>
                        <template v-if="family && family.find((f) => String(f.relationship || '').toLowerCase() === 'father')">
                            <div class="ehris-details-row"><dt>Father's surname</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'father')?.lastname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Father's first name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'father')?.firstname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Father's middle name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'father')?.middlename) }}</dd></div>
                            <div class="ehris-details-row"><dt>Father's name extension</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'father')?.extension) }}</dd></div>
                        </template>
                    </dl>
                    <dl class="ehris-official-info-col">
                        <template v-if="family && family.find((f) => String(f.relationship || '').toLowerCase() === 'mother')">
                            <div class="ehris-details-row"><dt>Mother's surname</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'mother')?.lastname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Mother's first name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'mother')?.firstname) }}</dd></div>
                            <div class="ehris-details-row"><dt>Mother's middle name</dt><dd>{{ displayVal((family || []).find((f) => String(f.relationship || '').toLowerCase() === 'mother')?.middlename) }}</dd></div>
                        </template>
                    </dl>
                </div>
            </template>
            <p v-else class="ehris-muted">No family information on file.</p>
        </template>

        <!-- Edit mode: text field form -->
        <template v-else>
            <form @submit.prevent="submit" class="ehris-family-form">
                <div v-if="Object.keys(errors).length" class="ehris-family-errors">
                    <p v-for="(msg, key) in errors" :key="key">{{ msg }}</p>
                </div>

                <!-- 22. Spouse -->
                <div class="ehris-family-section">
                    <h4 class="ehris-family-section-title">22. Spouse's information</h4>
                    <div class="ehris-family-fields">
                        <div class="ehris-field-row ehris-field-row-spouse-surname">
                            <Label for="spouse-lastname" class="ehris-field-label">Spouse's surname</Label>
                            <Input id="spouse-lastname" v-model="spouse.lastname" type="text" class="ehris-field-input" placeholder="Surname" />
                        </div>
                        <div class="ehris-field-row ehris-field-row-spouse-names">
                            <div class="ehris-field-group">
                                <Label for="spouse-firstname" class="ehris-field-label">First name</Label>
                                <Input id="spouse-firstname" v-model="spouse.firstname" type="text" class="ehris-field-input" placeholder="First name" />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="spouse-middlename" class="ehris-field-label">Middle name</Label>
                                <Input id="spouse-middlename" v-model="spouse.middlename" type="text" class="ehris-field-input" placeholder="Middle name" />
                            </div>
                            <div class="ehris-field-group ehris-field-extension">
                                <Label for="spouse-extension" class="ehris-field-label">Name extension (Jr., Sr.)</Label>
                                <Input id="spouse-extension" v-model="spouse.extension" type="text" class="ehris-field-input" placeholder="Jr., Sr." />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="spouse-occupation" class="ehris-field-label">Occupation</Label>
                                <Input id="spouse-occupation" v-model="spouse.occupation" type="text" class="ehris-field-input" placeholder="Occupation" />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="spouse-employer" class="ehris-field-label">Employer / Business name</Label>
                                <Input id="spouse-employer" v-model="spouse.employer_name" type="text" class="ehris-field-input" placeholder="Employer or business name" />
                            </div>
                        </div>
                        <div class="ehris-field-row ehris-field-row-spouse-contact">
                            <div class="ehris-field-group">
                                <Label for="spouse-business-add" class="ehris-field-label">Business address</Label>
                                <Input id="spouse-business-add" v-model="spouse.business_add" type="text" class="ehris-field-input" placeholder="Business address" />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="spouse-tel" class="ehris-field-label">Telephone no.</Label>
                                <Input id="spouse-tel" v-model="spouse.tel_num" type="text" class="ehris-field-input" placeholder="Telephone number" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 23. Children -->
                <div class="ehris-family-section">
                    <h4 class="ehris-family-section-title">23. Name of children (Write full name and list all)</h4>
                    <div class="ehris-family-fields">
                        <div v-for="(child, index) in children" :key="index" class="ehris-field-row ehris-field-row-children">
                            <div class="ehris-field-group ehris-field-child-name">
                                <Label :for="`child-name-${index}`" class="ehris-field-label">Name of children</Label>
                                <Input :id="`child-name-${index}`" v-model="child.fullname" type="text" class="ehris-field-input" placeholder="Full name" />
                            </div>
                            <div class="ehris-field-group ehris-field-child-dob">
                                <Label :for="`child-dob-${index}`" class="ehris-field-label">Date of birth (dd/mm/yyyy)</Label>
                                <Input :id="`child-dob-${index}`" v-model="child.dob" type="text" class="ehris-field-input" placeholder="dd/mm/yyyy" />
                            </div>
                            <div class="ehris-field-actions">
                                <Button type="button" variant="ghost" size="icon" aria-label="Remove" :disabled="children.length <= 1" @click="removeChild(index)">
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </div>
                        <div class="ehris-add-child-wrap">
                            <Button type="button" variant="outline" size="sm" :disabled="processing" @click="addChild">
                                <Plus class="size-4 mr-1" />
                                Add child
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- 24. Father -->
                <div class="ehris-family-section">
                    <h4 class="ehris-family-section-title">24. Father's surname</h4>
                    <div class="ehris-family-fields">
                        <div class="ehris-field-row">
                            <Label for="father-lastname" class="ehris-field-label">Father's surname</Label>
                            <Input id="father-lastname" v-model="father.lastname" type="text" class="ehris-field-input" placeholder="Surname" />
                        </div>
                        <div class="ehris-field-row ehris-field-row-inline">
                            <div class="ehris-field-group">
                                <Label for="father-firstname" class="ehris-field-label">First name</Label>
                                <Input id="father-firstname" v-model="father.firstname" type="text" class="ehris-field-input" placeholder="First name" />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="father-middlename" class="ehris-field-label">Middle name</Label>
                                <Input id="father-middlename" v-model="father.middlename" type="text" class="ehris-field-input" placeholder="Middle name" />
                            </div>
                            <div class="ehris-field-group ehris-field-extension">
                                <Label for="father-extension" class="ehris-field-label">Name extension (Jr., Sr.)</Label>
                                <Input id="father-extension" v-model="father.extension" type="text" class="ehris-field-input" placeholder="Jr., Sr." />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 25. Mother -->
                <div class="ehris-family-section">
                    <h4 class="ehris-family-section-title">25. Mother's maiden name</h4>
                    <div class="ehris-family-fields">
                        <div class="ehris-field-row">
                            <Label for="mother-lastname" class="ehris-field-label">Surname</Label>
                            <Input id="mother-lastname" v-model="mother.lastname" type="text" class="ehris-field-input" placeholder="Surname" />
                        </div>
                        <div class="ehris-field-row ehris-field-row-inline">
                            <div class="ehris-field-group">
                                <Label for="mother-firstname" class="ehris-field-label">First name</Label>
                                <Input id="mother-firstname" v-model="mother.firstname" type="text" class="ehris-field-input" placeholder="First name" />
                            </div>
                            <div class="ehris-field-group">
                                <Label for="mother-middlename" class="ehris-field-label">Middle name</Label>
                                <Input id="mother-middlename" v-model="mother.middlename" type="text" class="ehris-field-input" placeholder="Middle name" />
                            </div>
                        </div>
                    </div>
                </div>

                <p class="ehris-family-note">(Continue on separate sheet if necessary)</p>

                <div class="ehris-family-form-actions">
                    <Button type="submit" :disabled="processing">
                        <Spinner v-if="processing" class="size-4 mr-1" />
                        Save
                    </Button>
                    <Button type="button" variant="ghost" :disabled="processing" @click="cancelEdit">
                        Cancel
                    </Button>
                </div>
            </form>
        </template>
    </section>
</template>

<style scoped>
.ehris-family-form {
    width: 100%;
    max-width: 100%;
}
.ehris-family-section {
    margin-bottom: 1.5rem;
    padding: 1rem 0;
    border-bottom: 1px solid hsl(var(--border));
}
.ehris-family-section:last-of-type {
    border-bottom: none;
}
.ehris-family-section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
    margin-bottom: 0.75rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid hsl(var(--border));
}
.ehris-family-fields {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
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
.ehris-field-row {
    display: grid;
    grid-template-columns: minmax(160px, 200px) 1fr;
    gap: 0.75rem 1rem;
    align-items: center;
    width: 100%;
    max-width: 100%;
}
/* Spouse's surname field width: change max-width to make it narrower or wider */
.ehris-field-row-spouse-surname .ehris-field-input {
    max-width: 8rem;
}
.ehris-field-row-inline {
    display: grid;
    grid-template-columns: 1fr 1fr 7rem;
    gap: 0.75rem 1rem;
    align-items: end;
    width: 100%;
    max-width: 100%;
}
.ehris-field-row-spouse-names {
    display: grid;
    grid-template-columns: 1fr 1fr 6rem 1fr 1.5fr;
    gap: 0.75rem 1rem;
    align-items: end;
    width: 100%;
    max-width: 100%;
}
.ehris-field-row-spouse-names .ehris-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}
.ehris-field-row-spouse-names .ehris-field-extension {
    max-width: 6rem;
}
@media (max-width: 640px) {
    .ehris-field-row-spouse-names {
        grid-template-columns: 1fr 1fr;
    }
    .ehris-field-row-spouse-names .ehris-field-extension {
        max-width: none;
    }
    .ehris-field-row-spouse-contact {
        grid-template-columns: 1fr;
    }
}
.ehris-field-row-spouse-contact {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem 1rem;
    align-items: end;
    width: 100%;
    max-width: 100%;
}
.ehris-field-row-spouse-contact .ehris-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}
.ehris-add-child-wrap {
    margin-top: 1rem;
    padding: 0.5rem 0;
    display: flex;
    justify-content: flex-start;
    align-items: center;
}
.ehris-field-row-inline .ehris-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}
.ehris-field-row-inline .ehris-field-group.ehris-field-extension {
    max-width: 7rem;
}
.ehris-field-group {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}
.ehris-field-extension {
    max-width: 7rem;
}
.ehris-field-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
}
.ehris-field-input {
    width: 100%;
    min-width: 0;
}
.ehris-field-row-children {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 0.75rem 1rem;
    grid-template-columns: unset;
    width: 100%;
    max-width: 100%;
}
.ehris-field-child-name {
    flex: 1 1 50%;
    min-width: 200px;
}
.ehris-field-child-dob {
    flex: 0 0 140px;
    min-width: 120px;
}
.ehris-field-actions {
    flex-shrink: 0;
    padding-bottom: 0.25rem;
}
.ehris-family-note {
    font-size: 0.75rem;
    color: hsl(var(--destructive));
    margin-top: 1rem;
    margin-bottom: 1.5rem;
}
.ehris-family-form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: 1rem;
}
</style>
