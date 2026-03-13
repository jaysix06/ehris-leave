<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Pencil } from 'lucide-vue-next';
import { ref } from 'vue';
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
    if (v == null || v === '') return 'N/A';
    const s = String(v).trim();
    return s === '' ? 'N/A' : s;
}

function valInput(v: unknown): string {
    if (v == null) return '';
    return String(v);
}

function normalizeDateToIso(v: unknown): string {
    const raw = valInput(v).trim();
    if (raw === '') {
        return '';
    }

    if (/^\d{4}-\d{2}-\d{2}$/.test(raw)) {
        return raw;
    }

    const ymdSlash = raw.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    if (ymdSlash) {
        return `${ymdSlash[1]}-${ymdSlash[2]}-${ymdSlash[3]}`;
    }

    const dmySlash = raw.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (dmySlash) {
        return `${dmySlash[3]}-${dmySlash[2]}-${dmySlash[1]}`;
    }

    const dmyDash = raw.match(/^(\d{2})-(\d{2})-(\d{4})$/);
    if (dmyDash) {
        return `${dmyDash[3]}-${dmyDash[2]}-${dmyDash[1]}`;
    }

    return '';
}

const props = defineProps<{
    profile?: Record<string, unknown> | null;
    officialInfo?: Record<string, unknown> | null;
    officialUpdateUrl?: string;
    canEditOfficialRole?: boolean;
    canEditOfficialInfo?: boolean;
    officialOptions?: {
        salaryGrades?: string[];
        steps?: string[];
        positions?: string[];
        departments?: string[];
        divisionOffices?: string[];
        roles?: string[];
        employmentStatuses?: string[];
    };
}>();

function optionList(key: keyof NonNullable<typeof props.officialOptions>): string[] {
    const options = props.officialOptions;
    const list = options && Array.isArray(options[key]) ? options[key] : [];
    return list.filter((item) => item != null && String(item).trim() !== '').map((item) => String(item));
}

function optionsWithCurrent(
    key: keyof NonNullable<typeof props.officialOptions>,
    current: string,
): string[] {
    const base = optionList(key);
    const trimmed = current.trim();
    if (trimmed === '' || base.includes(trimmed)) {
        return base;
    }
    return [trimmed, ...base];
}

const selectClass =
    'border-input h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm';

const editModalOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const form = ref({
    employee_id: '',
    prefix_name: '',
    firstname: '',
    middlename: '',
    lastname: '',
    extension: '',
    email: '',
    item_no: '',
    plantilla: '',
    job_title: '',
    employ_status: '',
    salary_grade: '',
    step: '',
    date_of_joining: '',
    date_of_promotion: '',
    year_experience: '',
    role: '',
    division_code: '',
    business_id: '',
    office: '',
    reporting_manager: '',
});

function openEdit(): void {
    const o = props.officialInfo ?? {};
    form.value = {
        employee_id: valInput(o.employee_id),
        prefix_name: valInput(o.prefix_name),
        firstname: valInput(o.firstname),
        middlename: valInput(o.middlename),
        lastname: valInput(o.lastname),
        extension: valInput(o.extension),
        email: valInput(props.profile?.email ?? o.email),
        item_no: valInput(o.item_no),
        plantilla: valInput(o.plantilla),
        job_title: valInput(o.job_title),
        employ_status: valInput(o.employ_status),
        salary_grade: valInput(o.salary_grade),
        step: valInput(o.step),
        date_of_joining: normalizeDateToIso(o.date_of_joining),
        date_of_promotion: normalizeDateToIso(o.date_of_promotion),
        year_experience: valInput(o.year_experience),
        role: valInput(o.role),
        division_code: valInput(o.division_office_name ?? o.division_code),
        business_id: valInput(o.business_id),
        office: valInput(o.office),
        reporting_manager: valInput(o.reporting_manager),
    };
    errors.value = {};
    editModalOpen.value = true;
}

function submit(): void {
    if (!props.officialUpdateUrl) return;
    processing.value = true;
    errors.value = {};
    router.post(props.officialUpdateUrl, { ...form.value }, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
        },
        onSuccess: () => {
            editModalOpen.value = false;
        },
        onError: (e) => {
            errors.value = e as Record<string, string>;
        },
    });
}
</script>

<template>
    <section class="ehris-card">
        <div class="ehris-official-info-header">
            <h3>Official information</h3>
            <button
                v-if="props.canEditOfficialInfo"
                type="button"
                class="ehris-edit-btn"
                aria-label="Edit official information"
                @click="openEdit"
            >
                <Pencil class="size-4" />
            </button>
        </div>
        <div v-if="officialInfo" class="ehris-pds-official-form">
            <div class="ehris-pds-official-section">
                <div class="ehris-pds-official-section-title">
                    <span class="ehris-pds-official-roman">I.</span>
                    <span>EMPLOYEE IDENTIFICATION</span>
                </div>
                <dl class="ehris-pds-official-content">
                    <div class="ehris-pds-official-row">
                        <dt>EMPLOYEE NO.</dt>
                        <dd>{{ val(officialInfo.employee_id) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>HR ID</dt>
                        <dd>{{ val(officialInfo.hrid) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>PREFIX NAME</dt>
                        <dd>{{ val(officialInfo.prefix_name) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>FIRST NAME</dt>
                        <dd>{{ val(officialInfo.firstname) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>MIDDLE NAME</dt>
                        <dd>{{ val(officialInfo.middlename) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>LAST NAME</dt>
                        <dd>{{ val(officialInfo.lastname) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>NAME EXTENSION (JR., SR.)</dt>
                        <dd>{{ val(officialInfo.extension) }}</dd>
                    </div>
                    <div class="ehris-pds-official-row">
                        <dt>DEPED EMAIL</dt>
                        <dd>
                            <a
                                v-if="(props.profile?.email ?? officialInfo.email) && String(props.profile?.email ?? officialInfo.email).trim() !== ''"
                                :href="`mailto:${props.profile?.email ?? officialInfo.email}`"
                                class="ehris-email-link"
                            >{{ val(props.profile?.email ?? officialInfo.email) }}</a>
                            <span v-else>N/A</span>
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="ehris-pds-official-grid">
                <div class="ehris-pds-official-section">
                    <div class="ehris-pds-official-section-title">
                        <span class="ehris-pds-official-roman">II.</span>
                        <span>POSITION AND EMPLOYMENT</span>
                    </div>
                    <dl class="ehris-pds-official-content">
                        <div class="ehris-pds-official-row">
                            <dt>ITEM NO.</dt>
                            <dd>{{ val(officialInfo.item_no) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>PLANTILLA ASSIGNMENT</dt>
                            <dd>{{ val(officialInfo.plantilla) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>POSITION</dt>
                            <dd>{{ val(officialInfo.job_title) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>EMPLOYMENT STATUS</dt>
                            <dd>{{ val(officialInfo.employ_status) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>SALARY GRADE</dt>
                            <dd>{{ val(officialInfo.salary_grade) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>STEP</dt>
                            <dd>{{ val(officialInfo.step) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DATE OF JOINING</dt>
                            <dd>{{ val(officialInfo.date_of_joining) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>LAST DATE OF PROMOTION</dt>
                            <dd>{{ val(officialInfo.date_of_promotion) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>YEARS OF EXPERIENCE</dt>
                            <dd>{{ val(officialInfo.year_experience) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="ehris-pds-official-section">
                    <div class="ehris-pds-official-section-title">
                        <span class="ehris-pds-official-roman">III.</span>
                        <span>ORGANIZATION ASSIGNMENT</span>
                    </div>
                    <dl class="ehris-pds-official-content">
                        <div class="ehris-pds-official-row">
                            <dt>ROLE</dt>
                            <dd>{{ val(officialInfo.role) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DIVISION OFFICE</dt>
                            <dd>{{ val(officialInfo.division_office_name ?? officialInfo.division_code) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>BUSINESS UNIT</dt>
                            <dd>{{ val(officialInfo.business_unit_name ?? officialInfo.business_id) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>DEPARTMENT</dt>
                            <dd>{{ val(officialInfo.department_name ?? officialInfo.office) }}</dd>
                        </div>
                        <div class="ehris-pds-official-row">
                            <dt>REPORTING MANAGER</dt>
                            <dd>{{ val(officialInfo.reporting_manager) }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
        <p v-else class="ehris-muted">No official information on file.</p>

        <Dialog :open="editModalOpen" @update:open="(v) => { editModalOpen = v; }">
            <DialogContent class="ehris-edit-dialog-content sm:max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
                <DialogHeader>
                    <DialogTitle>Edit Official Information</DialogTitle>
                </DialogHeader>

                <div class="ehris-modal-scroll">
                    <p v-if="!props.canEditOfficialInfo" class="ehris-form-error">Only HR Manager can edit official information.</p>
                    <div class="ehris-modal-section">
                        <h4>Employee Identification</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field">
                                <span>Employee No.</span>
                                <Input v-model="form.employee_id" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>DepEd Email</span>
                                <Input v-model="form.email" type="email" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Prefix Name</span>
                                <Input v-model="form.prefix_name" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>First Name</span>
                                <Input v-model="form.firstname" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Middle Name</span>
                                <Input v-model="form.middlename" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Last Name</span>
                                <Input v-model="form.lastname" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Name Extension</span>
                                <Input v-model="form.extension" />
                            </label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Position and Employment</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field">
                                <span>Item No.</span>
                                <Input v-model="form.item_no" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Plantilla Assignment</span>
                                <Input v-model="form.plantilla" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Position</span>
                                <select v-model="form.job_title" :class="selectClass">
                                    <option value="" disabled>Select position</option>
                                    <option v-for="item in optionsWithCurrent('positions', form.job_title)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Employment Status</span>
                                <select v-model="form.employ_status" :class="selectClass">
                                    <option value="" disabled>Select status</option>
                                    <option v-for="item in optionsWithCurrent('employmentStatuses', form.employ_status)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Salary Grade</span>
                                <select v-model="form.salary_grade" :class="selectClass">
                                    <option value="" disabled>Select grade</option>
                                    <option v-for="item in optionsWithCurrent('salaryGrades', form.salary_grade)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Step</span>
                                <select v-model="form.step" :class="selectClass">
                                    <option value="" disabled>Select step</option>
                                    <option v-for="item in optionsWithCurrent('steps', form.step)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Date of Joining</span>
                                <Input v-model="form.date_of_joining" type="date" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Date of Promotion</span>
                                <Input v-model="form.date_of_promotion" type="date" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Years of Experience</span>
                                <Input v-model="form.year_experience" />
                            </label>
                        </div>
                    </div>

                    <div class="ehris-modal-section">
                        <h4>Organization Assignment</h4>
                        <div class="ehris-modal-grid">
                            <label class="ehris-modal-field">
                                <span>Role</span>
                                <select v-model="form.role" :class="selectClass" :disabled="!props.canEditOfficialRole">
                                    <option value="" disabled>Select role</option>
                                    <option v-for="item in optionsWithCurrent('roles', form.role)" :key="item" :value="item">{{ item }}</option>
                                </select>
                                <small v-if="!props.canEditOfficialRole" class="ehris-modal-hint">Only HR can edit this field.</small>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Division Office</span>
                                <select v-model="form.division_code" :class="selectClass">
                                    <option value="" disabled>Select division office</option>
                                    <option v-for="item in optionsWithCurrent('divisionOffices', form.division_code)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Business Unit</span>
                                <Input v-model="form.business_id" />
                            </label>
                            <label class="ehris-modal-field">
                                <span>Department</span>
                                <select v-model="form.office" :class="selectClass">
                                    <option value="" disabled>Select department</option>
                                    <option v-for="item in optionsWithCurrent('departments', form.office)" :key="item" :value="item">{{ item }}</option>
                                </select>
                            </label>
                            <label class="ehris-modal-field">
                                <span>Reporting Manager</span>
                                <Input v-model="form.reporting_manager" />
                            </label>
                        </div>
                    </div>

                    <p v-if="errors.message" class="ehris-form-error">{{ errors.message }}</p>
                </div>

                <DialogFooter class="mt-4 shrink-0 border-t pt-4">
                    <DialogClose as-child>
                        <Button type="button" variant="outline">Cancel</Button>
                    </DialogClose>
                    <Button type="button" :disabled="processing" @click="submit">
                        <Spinner v-if="processing" class="mr-2 h-4 w-4" />
                        Save Changes
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>

<style scoped>
.ehris-pds-official-form {
    display: grid;
    gap: 1rem;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: start;
}

.ehris-pds-official-grid {
    display: contents;
}

.ehris-pds-official-section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.8125rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 0.5rem 0.75rem;
    border: 1px solid hsl(var(--border));
    border-bottom: none;
}

.ehris-pds-official-roman {
    width: 2.25rem;
    text-align: right;
    flex: 0 0 2.25rem;
}

.ehris-pds-official-content {
    margin: 0;
}

.ehris-pds-official-row {
    display: grid;
    grid-template-columns: minmax(170px, 210px) 1fr;
    border-left: 1px solid hsl(var(--border));
    border-right: 1px solid hsl(var(--border));
    border-bottom: 1px solid hsl(var(--border));
}

.ehris-pds-official-row dt {
    background: hsl(var(--muted));
    color: hsl(var(--muted-foreground));
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.5rem 0.75rem;
    border-right: 1px solid hsl(var(--border));
    text-transform: uppercase;
}

.ehris-pds-official-row dd {
    margin: 0;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    color: hsl(var(--foreground));
    display: flex;
    align-items: center;
    min-height: 2.25rem;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
    white-space: normal;
}

.ehris-pds-official-row dd a {
    min-width: 0;
    max-width: 100%;
    overflow-wrap: anywhere;
    word-break: break-word;
}

.ehris-modal-scroll {
    overflow-y: auto;
    padding-right: 0.25rem;
}

.ehris-modal-section {
    border: 1px solid hsl(var(--border));
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background: hsl(var(--background));
}

.ehris-modal-section h4 {
    font-weight: 700;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.ehris-modal-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.75rem;
}

.ehris-modal-field {
    display: grid;
    gap: 0.35rem;
    font-size: 0.875rem;
    color: hsl(var(--muted-foreground));
}

.ehris-modal-field span {
    font-weight: 600;
    color: hsl(var(--foreground));
}

.ehris-modal-hint {
    font-size: 0.75rem;
    color: hsl(var(--muted-foreground));
}

.ehris-form-error {
    color: hsl(var(--destructive));
    font-size: 0.875rem;
    margin-top: 0.5rem;
}

@media (max-width: 768px) {
    .ehris-edit-dialog-content {
        width: calc(100vw - 1rem);
        max-width: calc(100vw - 1rem);
        max-height: calc(100dvh - 1rem);
        padding: 0.875rem;
    }

    .ehris-modal-scroll {
        padding-right: 0;
    }

    .ehris-modal-section {
        padding: 0.625rem;
        margin-bottom: 0.625rem;
    }

    .ehris-modal-grid {
        grid-template-columns: 1fr;
        gap: 0.625rem;
    }
}

@media (max-width: 1280px) {
    .ehris-pds-official-form {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 640px) {
    .ehris-pds-official-form {
        grid-template-columns: 1fr;
    }

    .ehris-pds-official-row {
        grid-template-columns: minmax(122px, 44%) minmax(0, 1fr);
        align-items: stretch;
    }

    .ehris-pds-official-row dt {
        display: flex;
        align-items: center;
        border-right: 1px solid hsl(var(--border));
        border-bottom: none;
        padding: 0.5rem 0.625rem;
        line-height: 1.2;
    }

    .ehris-pds-official-row dd {
        padding: 0.5rem 0.625rem;
        min-height: 2.25rem;
        align-items: flex-start;
        line-height: 1.3;
    }
}
</style>
