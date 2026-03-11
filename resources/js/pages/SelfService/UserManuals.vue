<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import type { BreadcrumbItem } from '@/types';

const pageTitle = "User's Manuals";

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Self-Service', href: selfServiceRoutes.wfhTimeInOut().url },
    { title: 'WFH Attendance', href: selfServiceRoutes.wfhTimeInOut().url },
    { title: pageTitle },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="pageTitle" />
        <div class="space-y-6">
            <div class="flex items-center justify-between border-b border-border pb-4">
                <h1 class="flex items-center gap-2 text-xl font-semibold text-foreground">
                    <BookOpen class="size-6 text-primary" />
                    User's Manual – WFH Attendance
                </h1>
                <Link
                    :href="selfServiceRoutes.wfhTimeInOut().url"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
                >
                    ← Back to WFH Attendance
                </Link>
            </div>

            <div class="space-y-6 text-sm text-foreground">
                <section>
                    <h3 class="mb-2 font-semibold text-foreground">How to use the WFH Attendance page</h3>
                    <p class="text-muted-foreground">
                        This page lets you record your work-from-home time and manage your tasks. Follow the steps below.
                    </p>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">1.) Clock In and Clock Out</h4>
                    <p class="text-muted-foreground">
                        The clock card on the right shows how long you have worked this week and whether you are currently clocked in or out.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> the green <strong>Clock In</strong> button to start recording time.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Clock Out</strong> when you finish.</li>
                        <li><strong class="text-foreground">Click</strong> the <strong>View time</strong> link to open your time logs. When you are done, <strong class="text-foreground">click</strong> <strong>Back to WFH Attendance</strong> to return.</li>
                    </ul>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">2.) Create a task</h4>
                    <p class="text-muted-foreground">
                        Enter the task title, target (description), priority, and due date. You can select a date range on the calendar (weekdays only). The task appears in the list and on the calendar.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>Create Task</strong> in the Tasks section.</li>
                        <li><strong class="text-foreground">Click</strong> a start date and end date on the calendar to set the due date range.</li>
                    </ul>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">3.) Task calendar</h4>
                    <p class="text-muted-foreground">
                        The Task calendar on the left shows your tasks by due date. Dates with tasks are marked.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> (or hover) over a date to see which tasks are due.</li>
                        <li><strong class="text-foreground">Click</strong> the arrows to move between months.</li>
                    </ul>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">4.) Manage task status</h4>
                    <p class="text-muted-foreground">
                        Each task has a <strong>View</strong> button and action buttons. Completed tasks move to the <strong>Completed Tasks</strong> tab. You can edit or delete from the view modal (delete is not available for completed tasks).
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>View</strong> to see task details.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Start Task</strong> when the task is Not Started.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Hold Task</strong> or <strong>Complete Task</strong> when In Progress.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Resume Task</strong> when the task is On Hold.</li>
                    </ul>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">5.) Search and sort</h4>
                    <p class="text-muted-foreground">
                        Filter by title or target; order the list by status or priority.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> in the <strong>Search Task</strong> field and type to filter.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Sort by status</strong> or <strong>Sort by priority</strong> to reorder the list.</li>
                    </ul>
                </section>

                <section class="space-y-2">
                    <h4 class="font-medium text-foreground">6.) Export report</h4>
                    <p class="text-muted-foreground">
                        The report uses the official header and footer. You can optionally export without images if needed.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>Export</strong> to download your task list as a PDF (open or completed, depending on the active tab).</li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
