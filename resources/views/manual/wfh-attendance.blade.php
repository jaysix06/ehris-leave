@extends('layouts.guest')

@section('title', "User's Manual – WFH Attendance")
@section('headerTitle', "User's Manual – WFH Attendance")

@section('content')
    <div class="flex flex-col gap-4 lg:flex-row">
        <aside class="lg:sticky lg:top-[4.25rem] lg:h-[calc(100vh-4.25rem-1.5rem)] lg:w-64 lg:shrink-0">
            <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-200 px-4 py-3">
                    <p class="text-sm font-semibold text-slate-900">Sections</p>
                    <p class="text-xs text-slate-500">Choose a page</p>
                </div>
                <nav class="max-h-[50vh] overflow-y-auto p-2 lg:max-h-none" aria-label="Manual sections">
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-intro">How to use</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-clock">Clock In and Clock Out</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-calendar">Task calendar</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-create">Create a task</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-manage">Manage task status</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-search">Search and sort</button>
                    <button type="button" class="js-menu block w-full rounded-md px-3 py-2 text-left text-sm font-medium text-slate-700 hover:bg-slate-100" data-target="manual-export">Export report</button>
                </nav>
            </div>
        </aside>

        <section class="min-w-0 flex-1">
            <div id="manual-intro" class="js-page scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-2xl font-semibold text-slate-900">How to use the WFH Attendance page</h2>
                <p class="mt-2 text-slate-600">
                    This page lets you record your work-from-home time and manage your tasks. Choose a topic from the menu on the left.
                </p>
                <div class="mt-4 rounded-lg bg-blue-50 px-4 py-3 text-center text-slate-900">
                    <p class="text-base font-semibold">You can click any photo in this manual to zoom in and view it in full size.</p>
                </div>
            </div>

            <div id="manual-clock" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Clock In and Clock Out</h3>
                <p class="mt-2 text-slate-600">
                    The clock card on the right shows how long you have worked this week and whether you are currently clocked in or out.
                </p>
                <button type="button" class="js-zoom mt-3 block w-full max-w-md overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                    <img src="/Users_Manual/ClockIn.png" alt="Clock card" class="h-auto w-full object-cover" style="object-position: right center;" />
                </button>
                <ul class="mt-4 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> the green <strong>Clock In</strong> button to start recording time.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/ClockIn_btn.png" alt="Clock In button" class="h-auto w-full object-cover" style="object-position: right bottom;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Clock Out</strong> when you finish.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Clockout_btn.png" alt="Clock Out button" class="h-auto w-full object-cover" style="object-position: right bottom;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> the <strong>View time</strong> link to open your time logs. When you are done, <strong>click</strong> <strong>Back to WFH Attendance</strong> to return.</span>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/ViewTime_btn.png" alt="View time link" class="h-auto w-full object-cover" style="object-position: right center;" />
                            </button>
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/Back_btn.png" alt="Back to WFH Attendance" class="h-auto w-full object-cover" style="object-position: left top;" />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <div id="manual-calendar" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Task calendar</h3>
                <p class="mt-2 text-slate-600">
                    The Task calendar on the left shows your tasks by due date. Dates with tasks are marked.
                </p>
                <ul class="mt-4 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> (or hover) over a date to see which tasks are due.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/CalendarHover.png" alt="Calendar hover on date" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> the arrows to move between months.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Calendarbtn.png" alt="Task calendar" class="h-auto w-full object-cover" style="object-position: left center;" />
                        </button>
                    </li>
                </ul>
            </div>

            <div id="manual-create" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Create a task</h3>
                <p class="mt-2 text-slate-600">
                    Enter the task title, priority, target (description), and due date. You can select a date range on the calendar (weekdays only). The task appears in the list and on the calendar.
                </p>
                <p class="mt-4 text-lg font-semibold text-slate-700">Steps (in order):</p>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Create Task</strong> in the Tasks section to open the form.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/CreateTask_btn.png" alt="Create Task button" class="h-auto w-full object-cover" style="object-position: left top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Task title</strong> — Enter a short name (e.g. <em>Prepare Q1 report</em>, <em>Review attendance records</em>).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/TaskTitle.png" alt="Task title field" class="h-auto w-full object-cover" style="object-position: center top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Priority</strong> — Choose <strong>Low</strong>, <strong>Medium</strong>, or <strong>High</strong>.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Priority.png" alt="Priority selector" class="h-auto w-full object-cover" style="object-position: center top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Target (description)</strong> — Enter what you need to accomplish (e.g. <em>Compile data and submit to HR by Friday</em>).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/TaskTarget.png" alt="Task target field" class="h-auto w-full object-cover" style="object-position: center top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Due date range</strong> — Click a start date and an end date on the calendar (e.g. March 10 to March 12).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Picking_Date.png" alt="Picking due date" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Save New Task</strong> to create the task.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/SaveTask_btn.png" alt="Save New Task button" class="h-auto w-full object-cover" style="object-position: center bottom;" />
                        </button>
                    </li>
                </ul>
            </div>

            <div id="manual-manage" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Manage task status</h3>
                <p class="mt-2 text-slate-600">
                    New tasks are created already <strong>In Progress</strong>. Each task has a <strong>View</strong> button and action buttons. Completed tasks move to the <strong>Completed Tasks</strong> tab. You can edit or delete from the view modal (delete is not available for completed tasks). From the Completed Tasks tab you can <strong>Re-enter</strong> a task to move it back to the open Tasks list.
                </p>
                <ul class="mt-4 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>View</strong> to see task details.</span>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/View_btn.png" alt="View button" class="h-auto w-full object-cover" style="object-position: left center;" />
                            </button>
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/TaskDetail.png" alt="Task detail modal" class="h-auto w-full object-cover" />
                            </button>
                        </div>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Hold Task</strong> or <strong>Complete Task</strong> when In Progress.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Hold_Complete_btn.png" alt="Hold and Complete Task buttons" class="h-auto w-full object-cover" style="object-position: left center;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Resume Task</strong> when the task is On Hold.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/ResumeTask_btn.png" alt="Resume Task button" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                </ul>

                <p class="mt-6 text-lg font-semibold text-slate-700">Completing a task (accomplishment report):</p>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span>When a task is <strong>In Progress</strong>, <strong>click</strong> <strong>Complete Task</strong>.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/CompleteTask_btn2.png" alt="Complete Task button" class="h-auto w-full object-cover" style="object-position: left center;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span>A modal opens. You must write an <strong>Accomplishment Report</strong> — describe what you actually did or delivered for this task (e.g. <em>Submitted the Q1 draft to HR; revised section 3 per feedback</em>).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/AccomplishReport.png" alt="Accomplishment report modal" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                    <li>This report is required and is included in the exported PDF under “Actual Accomplishment/Output”.</li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Complete Task</strong> in the modal to save and mark the task complete, or <strong>Cancel</strong> to return without completing.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/AccomplishReport%20Complete_Cancel.png" alt="Complete and Cancel buttons" class="h-auto w-full object-cover" style="object-position: center bottom;" />
                        </button>
                    </li>
                </ul>

                <p class="mt-6 text-lg font-semibold text-slate-700">Completed Tasks tab:</p>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> the <strong>Completed Tasks</strong> tab to view tasks you have finished. Completed tasks appear in this list.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/CompleteTaskMenu_btn%20-%20Copy.png" alt="Completed Tasks tab" class="h-auto w-full object-cover" style="object-position: left top;" />
                        </button>
                    </li>
                </ul>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span>For completed tasks: <strong>click</strong> <strong>Re-enter</strong> to move the task back to the Tasks list (status becomes Not Started).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/ReEnter_btn.png" alt="Re-enter button" class="h-auto w-full object-cover" style="object-position: left center;" />
                        </button>
                    </li>
                </ul>

                <p class="mt-6 text-lg font-semibold text-slate-700">How to edit a task:</p>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li><strong>Click</strong> <strong>View</strong> on the task you want to edit (edit is not available for completed tasks).</li>
                    <li class="space-y-2">
                        <span>In the task details modal, <strong>click</strong> the <strong>Edit</strong> (pencil) button.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Edit_btn.png" alt="Edit button" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span>Change the title, priority, target (description), or due date range as needed.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/EditingTask.png" alt="Editing task" class="h-auto w-full object-cover" style="object-position: center top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Save Changes</strong> to apply, or <strong>Cancel</strong> to close without saving.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/SaveEdit_btn.png" alt="Save Changes button" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                </ul>

                <p class="mt-6 text-lg font-semibold text-slate-700">How to delete a task:</p>
                <ul class="mt-3 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span>In the <strong>Tasks</strong> list (open tasks only), <strong>click</strong> <strong>Delete Task</strong> (trash icon) on the task you want to remove. Delete is not available for completed tasks.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/DeleteTask_btn.png" alt="Delete Task button" class="h-auto w-full object-cover" style="object-position: left center;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span>In the confirmation dialog, <strong>click</strong> <strong>Delete</strong> to confirm (this cannot be undone), or <strong>Cancel</strong> to keep the task.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/DeleteConfirmation_btn.png" alt="Delete confirmation dialog" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                </ul>
            </div>

            <div id="manual-search" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Search and sort</h3>
                <p class="mt-2 text-slate-600">
                    Filter by title or target; order the list by status or priority.
                </p>
                <ul class="mt-4 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> in the <strong>Search Task</strong> field and type to filter.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Searching.png" alt="Search Task field" class="h-auto w-full object-cover" style="object-position: left top;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Sort by status</strong> or <strong>Sort by priority</strong> to reorder the list.</span>
                        <div class="flex flex-wrap gap-3">
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/Sorting1.png" alt="Sort by status or priority" class="h-auto w-full object-cover" style="object-position: left top;" />
                            </button>
                            <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                                <img src="/Users_Manual/Sorting2.png" alt="Sorted task list" class="h-auto w-full object-cover" style="object-position: left top;" />
                            </button>
                        </div>
                    </li>
                </ul>
            </div>

            <div id="manual-export" class="js-page hidden scroll-mt-24 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-xl font-semibold text-slate-900">Export report</h3>
                <p class="mt-2 text-slate-600">
                    Export all tasks (open and completed) in a date range as a PDF. The report uses the official header and footer when available. The header and footer appear on every page; if you have many tasks, the table continues on the next pages with the same header and footer on each page.
                </p>
                <ul class="mt-4 list-inside list-disc space-y-4 text-slate-700">
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Export</strong> to open the export dialog.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/Export_btn.png" alt="Export button" class="h-auto w-full object-cover" style="object-position: right bottom;" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span>Choose a date range: <strong>click</strong> a start date and an end date on the calendar (no range is pre-selected when you open the dialog).</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/ExportDatePicker_btn.png" alt="Export date picker" class="h-auto w-full object-cover" />
                        </button>
                    </li>
                    <li class="space-y-2">
                        <span><strong>Click</strong> <strong>Export</strong> to download the PDF.</span>
                        <button type="button" class="js-zoom block w-full max-w-sm overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                            <img src="/Users_Manual/ExportNow_btn.png" alt="Export button in dialog" class="h-auto w-full object-cover" style="object-position: center bottom;" />
                        </button>
                    </li>
                </ul>
            </div>
        </section>
    </div>

    <div id="zoom-overlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4">
        <button type="button" class="absolute right-4 top-4 rounded-full bg-white/10 p-2 text-white hover:bg-white/20" aria-label="Close">
            ✕
        </button>
        <img id="zoom-image" alt="Zoomed image" class="max-h-[90vh] max-w-full rounded-lg border border-white/10 bg-black object-contain shadow-2xl" />
    </div>
@endsection

@section('scripts')
    <script>
        (function () {
            const overlay = document.getElementById('zoom-overlay');
            const zoomImg = document.getElementById('zoom-image');
            if (!overlay || !zoomImg) return;

            function close() {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                zoomImg.removeAttribute('src');
            }

            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) close();
            });

            overlay.querySelector('button')?.addEventListener('click', close);

            document.querySelectorAll('button.js-zoom').forEach((btn) => {
                btn.addEventListener('click', () => {
                    const img = btn.querySelector('img');
                    if (!img) return;
                    zoomImg.setAttribute('src', img.getAttribute('src'));
                    zoomImg.setAttribute('alt', img.getAttribute('alt') || 'Zoomed image');
                    overlay.classList.remove('hidden');
                    overlay.classList.add('flex');
                });
            });

            const pages = Array.from(document.querySelectorAll('.js-page'));
            const menuButtons = Array.from(document.querySelectorAll('button.js-menu'));

            function setActivePage(id) {
                pages.forEach((p) => {
                    if (p.id === id) {
                        p.classList.remove('hidden');
                    } else {
                        p.classList.add('hidden');
                    }
                });

                menuButtons.forEach((b) => {
                    const isActive = b.getAttribute('data-target') === id;
                    b.classList.toggle('bg-slate-100', isActive);
                    b.classList.toggle('text-slate-900', isActive);
                    b.classList.toggle('font-semibold', isActive);
                });
            }

            menuButtons.forEach((b) => {
                b.addEventListener('click', () => {
                    const id = b.getAttribute('data-target');
                    if (!id) return;
                    setActivePage(id);
                });
            });

            setActivePage('manual-intro');
        })();
    </script>
@endsection

