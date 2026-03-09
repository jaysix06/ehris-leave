<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { LayoutGrid, ChartColumnBig, ChartLine, UsersRound, UserRoundCog, FileClock, BookUser, Wrench, NotepadText, FileText } from 'lucide-vue-next';
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
import { type NavItem } from '@/types';
import AppLogo from './AppLogo.vue';
import { dashboard, myDetails } from '@/routes';
import cotRpmsSummaryRoutes from '@/routes/cot-rpms-summary';
import employeeManagementRoutes from '@/routes/employee-management';
import requestStatusRoutes from '@/routes/request-status';
import satSummaryRoutes from '@/routes/sat-summary';
import selfServiceRoutes from '@/routes/self-service';
import surveyRoutes from '@/routes/survey';
import reportsRoutes from '@/routes/reports';
import utilitiesRoutes from '@/routes/utilities';
import leaveTypesRoutes from '@/routes/utilities/leave-types';

const page = usePage();
const surveyCategoriesWithSurveys = computed(() => {
    const cats = (page.props.surveyCategoriesWithSurveys as string[] | undefined) ?? [];
    return Array.isArray(cats) ? cats : [];
});

const mainNavItems = computed<NavItem[]>(() => {
    const surveyChildren: NavItem[] = surveyCategoriesWithSurveys.value.map((category) => ({
        title: category,
        href: surveyRoutes.gad({ query: { category } }),
    }));
    if (surveyCategoriesWithSurveys.value.length > 0) {
        surveyChildren.push({ title: 'All', href: surveyRoutes.gad() });
    }
    const surveyItem: NavItem = {
        title: 'Survey',
        icon: NotepadText,
        children: surveyChildren,
    };

    const items: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'COT-RPMS Summary',
        icon: ChartColumnBig,
        children: [
            {
                title: 'Total',
                href: cotRpmsSummaryRoutes.total(),
            },
            {
                title: 'Quarterly (Selectable Schools)',
                href: cotRpmsSummaryRoutes.quarterlySelectableSchools(),
            },
            {
                title: 'By Grade',
                href: cotRpmsSummaryRoutes.byGrade(),
            },
            {
                title: 'Subject Area',
                href: cotRpmsSummaryRoutes.subjectArea(),
            },
            {
                title: 'By Skills - Teacher',
                href: cotRpmsSummaryRoutes.bySkillsTeacher(),
            },
            {
                title: 'By Skills - Master Teacher',
                href: cotRpmsSummaryRoutes.bySkillsMasterTeacher(),
            },
        ],
    },
    {
        title: 'SAT-Summary',
        icon: ChartLine,
        children: [
            {
                title: 'Demographic Summary',
                href: satSummaryRoutes.demographicSummary(),
            },
            {
                title: 'Core Behavioral Competencies',
                href: satSummaryRoutes.coreBehavioralCompetencies(),
            },
            {
                title: 'SAT Teacher I-III',
                href: satSummaryRoutes.satTeacherIIii(),
            },
            {
                title: 'SAT Master Teacher I-IV',
                href: satSummaryRoutes.satMasterTeacherIIv(),
            },
        ],
    },
    {
        title: 'Employee Management',
        icon: UsersRound,
        children: [
            {
                title: 'Employee Profile',
                href: employeeManagementRoutes.employeeProfile(),
            },
            {
                title: 'PSIPOP Update',
                href: employeeManagementRoutes.psipopUpdate(),
            },
            {
                title: 'ID Card Printing',
                href: employeeManagementRoutes.idCardPrinting(),
            },
            {
                title: 'DepEd Email Requests',
                href: employeeManagementRoutes.depedEmailRequests(),
            },
            {
                title: 'Leave Requests',
                href: employeeManagementRoutes.leaveRequests(),
            },
        ],
    },
    {
        title: 'Self-Service',
        icon: UserRoundCog,
        children: [
            {
                title: 'Timezone',
                href: selfServiceRoutes.timezone(),
            },
            {
                title: 'WFH TimeIn/Out',
                href: selfServiceRoutes.wfhTimeInOut(),
            },
            {
                title: 'ID Card',
                href: selfServiceRoutes.idCard(),
            },
            {
                title: 'Service Record',
                href: selfServiceRoutes.serviceRecord(),
            },
            {
                title: 'Leave Application',
                href: selfServiceRoutes.leaveApplication(),
            },
            {
                title: 'DepEd Email Requests',
                href: selfServiceRoutes.depedEmailRequests(),
            },
        ],
    },
    {
        title: 'Request Status',
        icon: FileClock,
        children: [
            {
                title: 'My Requests',
                href: requestStatusRoutes.myRequests(),
            },
            {
                title: 'My Leave',
                href: requestStatusRoutes.myLeave(),
            },
        ],
    },
    {
        title: 'My Details',
        href: myDetails(),
        icon: BookUser,
    },
    {
        title: 'Utilities',
        icon: Wrench,
        children: [
            {
                title: 'Employee List',
                href: utilitiesRoutes.employeeList(),
            },
            {
                title: 'User List',
                href: utilitiesRoutes.userList(),
            },
            {
                title: 'Leave Types List',
                href: leaveTypesRoutes.index(),
            },
            {
                title: 'Business & Department List',
                href: utilitiesRoutes.businessDepartmentList(),
            },
            {
                title: 'Job Title & Monthly Salary',
                href: utilitiesRoutes.jobTitleMonthlySalary(),
            },
            {
                title: 'Reporting Manager',
                href: utilitiesRoutes.reportingManager(),
            },
            {
                title: 'Activity Log',
                href: utilitiesRoutes.activityLog(),
            },
            {
                title: 'Survey Management',
                href: utilitiesRoutes.surveyManagement(),
            },
            {
                title: 'Pop Up Management',
                href: utilitiesRoutes.popUpManagement(),
            },
        ],
    },
    ...(surveyCategoriesWithSurveys.value.length > 0 ? [surveyItem] : []),
    {
        title: 'Reports',
        icon: FileText,
        children: [
            {
                title: 'Employee Listing',
                href: reportsRoutes.employeeListing(),
            },
        ],
    },
];
    return items;
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
