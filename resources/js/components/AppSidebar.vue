<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, ChartColumnBig, ChartLine, UsersRound, UserRoundCog, FileClock, BookUser, Wrench, NotepadText, FileText } from 'lucide-vue-next';
import { computed } from 'vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, myDetails } from '@/routes';
import cotRpmsSummaryRoutes from '@/routes/cot-rpms-summary';
import employeeManagementRoutes from '@/routes/employee-management';
import reportsRoutes from '@/routes/reports';
import requestStatusRoutes from '@/routes/request-status';
import satSummaryRoutes from '@/routes/sat-summary';
import selfServiceRoutes from '@/routes/self-service';
import surveyRoutes from '@/routes/survey';
import utilitiesRoutes from '@/routes/utilities';
import leaveTypesRoutes from '@/routes/utilities/leave-types';
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';

const page = usePage();
type SidebarNavItem = NavItem & {
    key: string;
    children?: SidebarNavItem[];
};

type RoleAccessRule = {
    allow: string[];
    hidden?: string[];
};

const NAV_ACCESS_BY_ROLE: Record<string, RoleAccessRule> = {
    hidden: {
        allow: [],
        hidden: ['cot-rpms-summary', 'sat-summary', 'self-service.service-record', 'self-service.leave-application','self-service.deped-email-requests', 'request-status', 'survey', 'employee-management.employee-profile', 'employee-management.psipop-update', 'employee-management.deped-email-requests', 'employee-management.leave-requests'],
    },
    admin: {
        allow: ['*'],
    },
    hr: {
        allow: ['dashboard', 'employee-management', 'self-service', 'request-status', 'my-details', 'reports', 'survey'],
    },
    reporting_manager: {
        allow: ['dashboard', 'employee-management', 'self-service', 'request-status', 'my-details', 'survey'],
    },
    sds: {
        allow: ['dashboard', 'employee-management' ,'self-service', 'request-status', 'my-details', 'reports', 'survey', 'utilities.employee-list', 'utilities.job-title-monthly-salary', 'utilities.reporting-manager', 'utilities.survey-management', 'utilities.pop-up-management', 'utilities.announcement-management'],
    },
    employee: {
        allow: ['dashboard', 'self-service', 'request-status', 'my-details', 'survey'],
    },
};

/**
 * Normalize nav keys so config values like `Request Status`, `request_status`,
 * or `request-status` all resolve to the same comparable form.
 */
const normalizeNavKey = (value: unknown): string => {
    if (typeof value !== 'string') {
        return '';
    }

    return value.trim() === '*'
        ? '*'
        : value
              .trim()
              .replace(/^nav[.:]/i, '')
              .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
              .toLowerCase()
              .replace(/[_\s/]+/g, '-')
              .replace(/[^a-z0-9.-]+/g, '')
              .replace(/-+/g, '-')
              .replace(/^-|-$/g, '');
};
const normalizeRole = (value: string): string => value.trim().toLowerCase().replace(/\s+/g, ' ');

/**
 * Exact role-name mapping from `tbl_user.role` / `tbl_role.role_name`.
 * Add new exact role names here when new roles are introduced.
 */
const ROLE_KEY_BY_EXACT_ROLE: Record<string, string> = {
    'system admin': 'admin',
    'reporting manager': 'reporting_manager',
    'sds manager': 'sds',
    employee: 'employee',
    teacher: 'employee',
    'hr manager': 'hr',
    'hr staff': 'hr',
    'hrld manager': 'hr',
    'hrld staff': 'hr',
    'hrdd manager': 'hr',
    'hrdd staff': 'hr',
    'ao manager': 'employee',
    'ict coordinator': 'employee',
};

const resolveRoleKey = (rawRole: unknown): string => {
    const role = normalizeRole(typeof rawRole === 'string' ? rawRole : '');
    return ROLE_KEY_BY_EXACT_ROLE[role] ?? 'employee';
};

const coerceStringArray = (value: unknown): string[] => {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .map((item) => (typeof item === 'string' ? item.trim() : ''))
        .filter((item) => item.length > 0);
};

const currentUser = computed<Record<string, unknown> | undefined>(() => page.props.auth?.user as Record<string, unknown> | undefined);
// Step 1: resolve current user role into one of our sidebar role keys.
const currentRoleKey = computed<string>(() => resolveRoleKey(currentUser.value?.role));
const currentRoleAccess = computed<RoleAccessRule>(() => NAV_ACCESS_BY_ROLE[currentRoleKey.value] ?? NAV_ACCESS_BY_ROLE.employee);
// Step 2: collect optional per-user nav permission tokens from the auth payload.
const currentUserPermissions = computed<string[]>(() => {
    const user = currentUser.value;

    return [
        ...coerceStringArray(user?.permissions),
        ...coerceStringArray(user?.navPermissions),
        ...coerceStringArray(user?.nav_permissions),
    ];
});

// Step 3: split tokens into allow-list and hide-list (`hidden:*` / `!*`).
const parsedPermissionAccess = computed<{ allow: Set<string>; hidden: Set<string> }>(() => {
    const allow = new Set<string>();
    const hidden = new Set<string>();

    for (const tokenRaw of currentUserPermissions.value) {
        const token = tokenRaw.trim().toLowerCase();

        if (token.startsWith('hidden:')) {
            hidden.add(normalizeNavKey(token.slice(7)));
            continue;
        }

        if (token.startsWith('!')) {
            hidden.add(normalizeNavKey(token.slice(1)));
            continue;
        }

        if (token === '*') {
            allow.add('*');
            continue;
        }

        allow.add(normalizeNavKey(token));
    }

    return { allow, hidden };
});

const canViewNavKey = (key: string): boolean => {
    const normalizedKey = normalizeNavKey(key);
    const roleAllowed = currentRoleAccess.value.allow.map(normalizeNavKey);
    const hasRoleWildcard = roleAllowed.includes('*');
    const hasPermissionWildcard = parsedPermissionAccess.value.allow.has('*');

    return hasRoleWildcard || hasPermissionWildcard || roleAllowed.includes(normalizedKey) || parsedPermissionAccess.value.allow.has(normalizedKey);
};

const buildVisibleNavItems = (items: SidebarNavItem[], hiddenKeys: Set<string>, parentAllowed: boolean = false): NavItem[] => {
    const visibleItems: NavItem[] = [];

    for (const item of items) {
        if (hiddenKeys.has(normalizeNavKey(item.key))) {
            continue;
        }

        const isItemAllowed = parentAllowed || canViewNavKey(item.key);
        const visibleChildren = item.children ? buildVisibleNavItems(item.children, hiddenKeys, isItemAllowed) : [];
        const hasVisibleChildren = visibleChildren.length > 0;
        const shouldRender = isItemAllowed || hasVisibleChildren;

        if (!shouldRender) {
            continue;
        }

        if (!item.href && item.children && !hasVisibleChildren) {
            continue;
        }

        const base = { ...item } as Partial<SidebarNavItem>;
        delete base.key;
        delete base.children;
        visibleItems.push({
            ...(base as NavItem),
            ...(hasVisibleChildren ? { children: visibleChildren } : {}),
        });
    }

    return visibleItems;
};

const surveyCategoriesWithSurveys = computed(() => {
    const cats = (page.props.surveyCategoriesWithSurveys as string[] | undefined) ?? [];
    return Array.isArray(cats) ? cats : [];
});

const mainNavItems = computed<NavItem[]>(() => {
    const surveyChildren: SidebarNavItem[] = surveyCategoriesWithSurveys.value.map((category) => ({
        key: 'survey',
        title: category,
        href: surveyRoutes.gad({ query: { category } }),
    }));
    if (surveyCategoriesWithSurveys.value.length > 0) {
        surveyChildren.push({ key: 'survey', title: 'All', href: surveyRoutes.gad() });
    }
    const surveyItem: SidebarNavItem = {
        key: 'survey',
        title: 'Survey',
        icon: NotepadText,
        children: surveyChildren,
    };

    const items: SidebarNavItem[] = [
    {
        key: 'dashboard',
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        key: 'cot-rpms-summary',
        title: 'COT-RPMS Summary',
        icon: ChartColumnBig,
        children: [
            {
                key: 'cot-rpms-summary.total',
                title: 'Total',
                href: cotRpmsSummaryRoutes.total(),
            },
            {
                key: 'cot-rpms-summary.quarterly-selectable-schools',
                title: 'Quarterly (Selectable Schools)',
                href: cotRpmsSummaryRoutes.quarterlySelectableSchools(),
            },
            {
                key: 'cot-rpms-summary.by-grade',
                title: 'By Grade',
                href: cotRpmsSummaryRoutes.byGrade(),
            },
            {
                key: 'cot-rpms-summary.subject-area',
                title: 'Subject Area',
                href: cotRpmsSummaryRoutes.subjectArea(),
            },
            {
                key: 'cot-rpms-summary.by-skills-teacher',
                title: 'By Skills - Teacher',
                href: cotRpmsSummaryRoutes.bySkillsTeacher(),
            },
            {
                key: 'cot-rpms-summary.by-skills-master-teacher',
                title: 'By Skills - Master Teacher',
                href: cotRpmsSummaryRoutes.bySkillsMasterTeacher(),
            },
        ],
    },
    {
        key: 'sat-summary',
        title: 'SAT-Summary',
        icon: ChartLine,
        children: [
            {
                key: 'sat-summary.demographic-summary',
                title: 'Demographic Summary',
                href: satSummaryRoutes.demographicSummary(),
            },
            {
                key: 'sat-summary.core-behavioral-competencies',
                title: 'Core Behavioral Competencies',
                href: satSummaryRoutes.coreBehavioralCompetencies(),
            },
            {
                key: 'sat-summary.teacher-i-iii',
                title: 'SAT Teacher I-III',
                href: satSummaryRoutes.satTeacherIIii(),
            },
            {
                key: 'sat-summary.master-teacher-i-iv',
                title: 'SAT Master Teacher I-IV',
                href: satSummaryRoutes.satMasterTeacherIIv(),
            },
        ],
    },
    {
        key: 'employee-management',
        title: 'Employee Management',
        icon: UsersRound,
        children: [
            {
                key: 'employee-management.employee-profile',
                title: 'Employee Profile',
                href: employeeManagementRoutes.employeeProfile(),
            },
            {
                key: 'employee-management.psipop-update',
                title: 'PSIPOP Update',
                href: employeeManagementRoutes.psipopUpdate(),
            },
            {
                key: 'employee-management.id-card-printing',
                title: 'ID Card Printing',
                href: employeeManagementRoutes.idCardPrinting(),
            },
            {
                key: 'employee-management.deped-email-requests',
                title: 'DepEd Email Requests',
                href: employeeManagementRoutes.depedEmailRequests(),
            },
            {
                key: 'employee-management.leave-requests',
                title: 'Leave Requests',
                href: employeeManagementRoutes.leaveRequests(),
            },
        ],
    },
    {
        key: 'self-service',
        title: 'Self-Service',
        icon: UserRoundCog,
        children: [
            {
                key: 'self-service.wfh-attendance',
                title: 'WFH Attendance',
                href: selfServiceRoutes.wfhTimeInOut(),
            },
            {
                key: 'self-service.id-card',
                title: 'ID Card',
                href: selfServiceRoutes.idCard(),
            },
            {
                key: 'self-service.service-record',
                title: 'Service Record',
                href: selfServiceRoutes.serviceRecord(),
            },
            {
                key: 'self-service.leave-application',
                title: 'Leave Application',
                href: selfServiceRoutes.leaveApplication(),
            },
            {
                key: 'self-service.deped-email-requests',
                title: 'DepEd Email Requests',
                href: selfServiceRoutes.depedEmailRequests(),
            },
        ],
    },
    {
        key: 'request-status',
        title: 'Request Status',
        icon: FileClock,
        children: [
            {
                key: 'request-status.my-requests',
                title: 'My Requests',
                href: requestStatusRoutes.myRequests(),
            },
            {
                key: 'request-status.my-leave',
                title: 'My Leave',
                href: requestStatusRoutes.myLeave(),
            },
        ],
    },
    {
        key: 'my-details',
        title: 'My Details',
        icon: BookUser,
        children: [
            { key: 'my-details.official-info', title: 'Official Info', href: myDetails({ query: { section: 'official-info' } }) },
            { key: 'my-details.personal-info', title: 'Personal Info', href: myDetails({ query: { section: 'personal-info' } }) },
            { key: 'my-details.family-background', title: 'Family Background', href: myDetails({ query: { section: 'family-background' } }) },
            { key: 'my-details.education-background', title: 'Education Background', href: myDetails({ query: { section: 'education-background' } }) },
            { key: 'my-details.eligibility', title: 'Eligibility', href: myDetails({ query: { section: 'eligibility' } }) },
            { key: 'my-details.work-experience', title: 'Work Experience', href: myDetails({ query: { section: 'work-experience' } }) },
            { key: 'my-details.voluntary-work', title: 'Voluntary Work', href: myDetails({ query: { section: 'voluntary-work' } }) },
            { key: 'my-details.training', title: 'Training', href: myDetails({ query: { section: 'training' } }) },
            { key: 'my-details.others', title: 'Others', href: myDetails({ query: { section: 'others' } }) },
        ],
    },
    {
        key: 'utilities',
        title: 'Utilities',
        icon: Wrench,
        children: [
            {
                key: 'utilities.employee-list',
                title: 'Employee List',
                href: utilitiesRoutes.employeeList(),
            },
            {
                key: 'utilities.user-list',
                title: 'User List',
                href: utilitiesRoutes.userList(),
            },
            {
                key: 'utilities.leave-types-list',
                title: 'Leave Types List',
                href: leaveTypesRoutes.index(),
            },
            {
                key: 'utilities.business-department-list',
                title: 'Business & Department List',
                href: utilitiesRoutes.businessDepartmentList(),
            },
            {
                key: 'utilities.job-title-monthly-salary',
                title: 'Job Title & Monthly Salary',
                href: utilitiesRoutes.jobTitleMonthlySalary(),
            },
            {
                key: 'utilities.reporting-manager',
                title: 'Reporting Manager',
                href: utilitiesRoutes.reportingManager(),
            },
            {
                key: 'utilities.activity-log',
                title: 'Activity Log',
                href: utilitiesRoutes.activityLog(),
            },
            {
                key: 'utilities.survey-management',
                title: 'Survey Management',
                href: utilitiesRoutes.surveyManagement(),
            },
            {
                key: 'utilities.pop-up-management',
                title: 'Pop Up Management',
                href: utilitiesRoutes.popUpManagement(),
            },
            {
                key: 'utilities.announcement-management',
                title: 'Announcement Management',
                href: utilitiesRoutes.announcementManagement(),
            },
        ],
    },
    ...(surveyCategoriesWithSurveys.value.length > 0 ? [surveyItem] : []),
    {
        key: 'reports',
        title: 'Reports',
        icon: FileText,
        children: [
            {
                key: 'reports.employee-listing',
                title: 'Employee Listing',
                href: reportsRoutes.employeeListing(),
            },
        ],
    },
];

    const hiddenKeys = new Set<string>([
        ...(NAV_ACCESS_BY_ROLE.hidden.hidden ?? []).map(normalizeNavKey),
        ...(currentRoleAccess.value.hidden ?? []).map(normalizeNavKey),
        ...Array.from(parsedPermissionAccess.value.hidden).map(normalizeNavKey),
    ]);

    return buildVisibleNavItems(items, hiddenKeys);
});

const footerNavItems: NavItem[] = [
    // {
    //     title: 'Github Repo',
    //     href: 'https://github.com/laravel/vue-starter-kit',
    //     icon: Folder,
    // },
    // {
    //     title: 'Documentation',
    //     href: 'https://laravel.com/docs/starter-kits#vue',
    //     icon: BookOpen,
    // },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
