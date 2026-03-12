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
                    <p class="text-muted-foreground font-medium">What to input:</p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Task title</strong> — Short name for the task (e.g. <em>Prepare Q1 report</em>, <em>Review attendance records</em>).</li>
                        <li><strong class="text-foreground">Target (description)</strong> — What you need to accomplish (e.g. <em>Compile data and submit to HR by Friday</em>).</li>
                        <li><strong class="text-foreground">Priority</strong> — Choose <strong>Low</strong>, <strong>Medium</strong>, or <strong>High</strong>.</li>
                        <li><strong class="text-foreground">Due date range</strong> — Select a start and end date on the calendar (e.g. from March 10 to March 12).</li>
                    </ul>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground mt-2">
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
                        Each task has a <strong>View</strong> button and action buttons. Completed tasks move to the <strong>Completed Tasks</strong> tab. You can edit or delete from the view modal (delete is not available for completed tasks). From the Completed Tasks tab you can <strong>Re-enter</strong> a task to move it back to the open Tasks list.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>View</strong> to see task details.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Start Task</strong> when the task is Not Started.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Hold Task</strong> or <strong>Complete Task</strong> when In Progress.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Resume Task</strong> when the task is On Hold.</li>
                    </ul>
                    <p class="text-muted-foreground font-medium mt-2">Completing a task (accomplishment report):</p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li>When a task is <strong>In Progress</strong>, <strong class="text-foreground">click</strong> <strong>Complete Task</strong>.</li>
                        <li>A modal opens. You must write an <strong>Accomplishment Report</strong> — describe what you actually did or delivered for this task (e.g. <em>Submitted the Q1 draft to HR; revised section 3 per feedback</em>).</li>
                        <li>This report is required and is included in the exported PDF under “Actual Accomplishment/Output”.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Complete Task</strong> in the modal to save and mark the task complete, or <strong>Cancel</strong> to return without completing.</li>
                    </ul>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground mt-2">
                        <li>For completed tasks: <strong class="text-foreground">click</strong> <strong>Re-enter</strong> to move the task back to the Tasks list (status becomes Not Started).</li>
                    </ul>
                    <p class="text-muted-foreground font-medium mt-2">How to edit a task:</p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>View</strong> on the task you want to edit (edit is not available for completed tasks).</li>
                        <li>In the task details modal, <strong class="text-foreground">click</strong> the <strong>Edit</strong> (pencil) button.</li>
                        <li>Change the title, target (description), priority, or due date range as needed.</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Save Changes</strong> to apply, or <strong>Cancel</strong> to close without saving.</li>
                    </ul>
                    <p class="text-muted-foreground font-medium mt-2">How to delete a task:</p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li>In the <strong>Tasks</strong> list (open tasks only), <strong class="text-foreground">click</strong> <strong>Delete Task</strong> (trash icon) on the task you want to remove. Delete is not available for completed tasks.</li>
                        <li>In the confirmation dialog, <strong class="text-foreground">click</strong> <strong>Delete</strong> to confirm (this cannot be undone), or <strong>Cancel</strong> to keep the task.</li>
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
                        Export all tasks (open and completed) in a date range as a PDF. The report uses the official header and footer when available. The header and footer appear on every page; if you have many tasks, the table continues on the next pages with the same header and footer on each page.
                    </p>
                    <ul class="list-inside list-disc space-y-1 text-muted-foreground">
                        <li><strong class="text-foreground">Click</strong> <strong>Export</strong> to open the export dialog.</li>
                        <li>Choose a date range: <strong class="text-foreground">click</strong> a start date and an end date on the calendar (no range is pre-selected when you open the dialog).</li>
                        <li><strong class="text-foreground">Click</strong> <strong>Export</strong> to download the PDF.</li>
                    </ul>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
