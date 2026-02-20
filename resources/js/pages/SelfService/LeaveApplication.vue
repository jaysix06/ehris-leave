<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    CalendarDays,
    CheckCircle2,
    SendHorizontal,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import selfServiceRoutes from '@/routes/self-service';
import { type BreadcrumbItem } from '@/types';

const pageTitle = 'Leave Application';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Self-Service',
        href: selfServiceRoutes.wfhTimeInOut().url,
    },
    {
        title: pageTitle,
        href: selfServiceRoutes.leaveApplication().url,
    },
];

const announcements = [
    'Please submit leave applications at least 5 working days in advance.',
    'Medical leave requests require supporting documents upon return.',
    'Team leads should endorse requests before final submission.',
    'Unused leave credits are evaluated according to yearly policy.',
    'Keep emergency contact details updated before extended leave.',
    'Use the comments field for handover notes and critical tasks.',
];
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="ehris-page leave-application-page">
            <section class="leave-top-row">
                <div>
                    <p class="leave-kicker">Self-Service</p>
                    <h2 class="leave-heading">Leave management</h2>
                </div>

                <div class="leave-user-actions">
                    <button class="icon-btn" type="button" aria-label="Notifications">
                        <Bell :size="16" />
                    </button>
                    <button class="icon-btn" type="button" aria-label="Calendar">
                        <CalendarDays :size="16" />
                    </button>
                </div>
            </section>

            <section class="leave-summary-row">
                <article class="ehris-card leave-highlight-card">
                    <h3>Upcoming leaves</h3>
                    <p class="days-count">03 Days</p>
                    <p class="range-line">04/04/2023 - 06/04/2023</p>
                    <div class="leave-type">
                        <span>Paid Time Off (PTO)</span>
                        <CheckCircle2 :size="16" />
                    </div>
                </article>

                <article class="ehris-card mini-stat">
                    <p class="mini-num">27/30</p>
                    <p class="mini-label">Leaves remaining</p>
                </article>

                <article class="ehris-card mini-stat">
                    <p class="mini-num">03/30</p>
                    <p class="mini-label">Leaves used</p>
                </article>
            </section>

            <section class="leave-main-row">
                <article class="ehris-card request-card">
                    <div class="request-head">
                        <h3>Leave request</h3>
                        <p class="date-range">19/05/2023 to 24/05/2023</p>
                    </div>

                    <div class="request-grid">
                        <div class="left-form">
                            <label>
                                Approval manager
                                <select>
                                    <option>Jack Jensen</option>
                                </select>
                            </label>
                            <label>
                                Leave type
                                <select>
                                    <option>Sick Leave</option>
                                </select>
                            </label>
                            <label>
                                Reason for leave
                                <textarea placeholder="Type your reason"></textarea>
                            </label>
                        </div>

                        <div class="calendar-box">
                            <div class="month-head">
                                <button type="button" aria-label="Previous month">&lt;</button>
                                <strong>May 2023</strong>
                                <button type="button" aria-label="Next month">&gt;</button>
                            </div>
                            <div class="calendar-grid">
                                <span>MON</span><span>TUE</span><span>WED</span><span>THU</span><span>FRI</span><span>SAT</span><span>SUN</span>
                                <span>1</span><span>2</span><span>3</span><span>4</span><span>5</span><span>6</span><span>7</span>
                                <span>8</span><span>9</span><span>10</span><span>11</span><span>12</span><span>13</span><span>14</span>
                                <span>15</span><span>16</span><span>17</span><span>18</span><span class="selected">19</span><span class="selected">20</span><span class="selected">21</span>
                                <span class="selected light">22</span><span class="selected light">23</span><span class="selected">24</span><span>25</span><span>26</span><span>27</span><span>28</span>
                                <span>29</span><span>30</span><span>1</span><span>1</span><span>1</span><span>1</span><span>1</span>
                            </div>
                        </div>
                    </div>

                    <div class="request-actions">
                        <button class="draft-btn" type="button">Save as draft</button>
                        <button class="send-btn" type="button">
                            Send request
                            <SendHorizontal :size="14" />
                        </button>
                    </div>
                </article>

                <aside class="ehris-card announcement-card">
                    <h3>Announcements</h3>
                    <ul>
                        <li v-for="(item, index) in announcements" :key="index">
                            <p>{{ item }}</p>
                            <span>19/05/2023</span>
                        </li>
                    </ul>
                </aside>
            </section>
        </div>
    </AppLayout>
</template>

<style scoped>
.leave-application-page {
    gap: 1rem;
}

.leave-top-row {
    display: flex;
    align-items: start;
    justify-content: space-between;
    gap: 1rem;
}

.leave-kicker {
    margin: 0;
    color: hsl(var(--primary));
    font-size: 0.8rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    text-transform: uppercase;
}

.leave-heading {
    margin: 0.2rem 0 0;
    color: hsl(var(--foreground));
    font-size: 1.8rem;
    font-weight: 800;
    line-height: 1.1;
}

.leave-user-actions {
    display: flex;
    gap: 0.6rem;
}

.icon-btn {
    display: inline-flex;
    width: 38px;
    height: 38px;
    align-items: center;
    justify-content: center;
    border-radius: 0.7rem;
    border: 1px solid hsl(var(--border));
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.leave-summary-row {
    display: grid;
    grid-template-columns: 1.7fr 1fr 1fr;
    gap: 1rem;
}

.leave-highlight-card h3,
.request-head h3,
.announcement-card h3 {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 1.4rem;
    font-weight: 800;
}

.days-count {
    margin: 0.6rem 0 0;
    font-size: 1.25rem;
    color: hsl(var(--foreground));
    font-weight: 700;
}

.range-line {
    margin: 0.25rem 0 0.4rem;
    font-size: 0.95rem;
    color: hsl(var(--muted-foreground));
}

.leave-type {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.92rem;
    color: hsl(var(--foreground));
}

.leave-type :deep(svg) {
    color: #2ea37f;
}

.mini-stat {
    display: grid;
    place-content: center;
    min-height: 120px;
    text-align: center;
}

.mini-num {
    margin: 0;
    color: hsl(var(--primary));
    font-size: 2rem;
    font-weight: 800;
}

.mini-label {
    margin: 0.45rem 0 0;
    color: hsl(var(--muted-foreground));
    font-size: 0.9rem;
}

.leave-main-row {
    display: grid;
    grid-template-columns: 2.35fr 1fr;
    gap: 1rem;
}

.request-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.8rem;
}

.date-range {
    margin: 0;
    color: hsl(var(--primary));
    font-weight: 600;
    font-size: 0.9rem;
}

.request-grid {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 0.8rem;
}

.left-form {
    display: grid;
    gap: 0.7rem;
}

.left-form label {
    display: grid;
    gap: 0.4rem;
    color: hsl(var(--muted-foreground));
    font-size: 0.84rem;
}

.left-form select,
.left-form textarea {
    border-radius: 0.7rem;
    border: 1px solid hsl(var(--input));
    background: hsl(var(--card));
    color: hsl(var(--foreground));
    font-size: 0.94rem;
    padding: 0.65rem 0.75rem;
}

.left-form textarea {
    min-height: 122px;
    resize: none;
}

.calendar-box {
    border: 1px solid hsl(var(--border));
    border-radius: 0.8rem;
    background: hsl(var(--card));
    padding: 0.7rem;
}

.month-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid hsl(var(--border));
    padding-bottom: 0.52rem;
    margin-bottom: 0.65rem;
}

.month-head button {
    border: none;
    background: transparent;
    color: hsl(var(--foreground));
    font-size: 1rem;
    cursor: pointer;
}

.month-head strong {
    color: hsl(var(--foreground));
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.36rem;
    text-align: center;
    color: hsl(var(--foreground));
    font-size: 0.84rem;
}

.calendar-grid .selected {
    border-radius: 999px;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.calendar-grid .selected.light {
    background: color-mix(in srgb, hsl(var(--primary)) 25%, white);
    color: hsl(var(--foreground));
}

.request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    margin-top: 0.9rem;
}

.draft-btn,
.send-btn {
    border-radius: 0.6rem;
    border: 1px solid hsl(var(--primary));
    padding: 0.48rem 0.85rem;
    font-weight: 600;
    font-size: 0.84rem;
    cursor: pointer;
}

.draft-btn {
    background: hsl(var(--card));
    color: hsl(var(--primary));
}

.send-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: hsl(var(--primary));
    color: hsl(var(--primary-foreground));
}

.announcement-card ul {
    margin: 0.6rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.72rem;
}

.announcement-card li p {
    margin: 0;
    color: hsl(var(--foreground));
    font-size: 0.8rem;
    line-height: 1.4;
}

.announcement-card li span {
    display: block;
    margin-top: 0.2rem;
    text-align: right;
    color: hsl(var(--muted-foreground));
    font-size: 0.68rem;
}

@media (max-width: 1160px) {
    .leave-summary-row,
    .leave-main-row,
    .request-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .leave-top-row {
        align-items: center;
    }

    .leave-heading {
        font-size: 1.45rem;
    }
}
</style>
