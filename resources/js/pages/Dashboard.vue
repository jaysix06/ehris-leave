<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Bell,
    CalendarDays,
    CheckCircle2,
    ClipboardList,
    LogOut,
    SendHorizontal,
    Settings,
    SquarePen,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

const announcements = [
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, ac mollis id turpis.',
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="leave-page">
            <div class="leave-shell">
                <aside class="left-rail">
                </aside>

                <section class="leave-content">
                    <header class="top-row">
                        <div>
                            <p class="hello-text">Hi,</p>
                            <h1 class="name-text">Hannah</h1>
                        </div>
                        <div class="header-icons">
                            <button class="icon-btn" type="button" aria-label="Notifications">
                                <Bell :size="16" />
                            </button>
                            <button class="profile-btn" type="button" aria-label="Profile">
                                H
                            </button>
                        </div>
                    </header>

                    <section class="summary-row">
                        <article class="upcoming-card">
                            <h2>Upcoming leaves</h2>
                            <p class="days-count">03 Days</p>
                            <p class="range-line">04/04/2023 - 06/04/2023</p>
                            <div class="leave-type">
                                <span>Paid Time Off (PTO)</span>
                                <CheckCircle2 :size="16" />
                            </div>
                        </article>
                        <article class="mini-stat">
                            <p class="mini-num">27/30</p>
                            <p class="mini-label">Leaves remaining</p>
                        </article>
                        <article class="mini-stat">
                            <p class="mini-num">03/30</p>
                            <p class="mini-label">Leaves used</p>
                        </article>
                    </section>

                    <section class="main-row">
                        <article class="request-card">
                            <div class="request-head">
                                <h2>Leave request</h2>
                                <p class="date-range">19/05/2023 to 24/05/2023</p>
                            </div>
                            <div class="request-grid">
                                <div class="left-form">
                                    <label>
                                        Approval Manager
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
                                        <button type="button" aria-label="Previous Month">
                                            &lt;
                                        </button>
                                        <strong>May 2023</strong>
                                        <button type="button" aria-label="Next Month">&gt;</button>
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

                        <aside class="announcement-card">
                            <h2>Announcements</h2>
                            <ul>
                                <li v-for="(item, index) in announcements" :key="index">
                                    <p>{{ item }}</p>
                                    <span>19/05/2023</span>
                                </li>
                            </ul>
                        </aside>
                    </section>
                </section>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.leave-page {
    min-height: 100%;
    padding: 2rem 1.25rem;
    background:
        radial-gradient(circle at 12% 20%, #dde2f3 0 2px, transparent 2px 120px),
        radial-gradient(circle at 82% 68%, #e8ecf9 0 2px, transparent 2px 110px),
        linear-gradient(180deg, #f6f7fb 0%, #f3f4f8 100%);
}

.leave-shell {
    margin: 0 auto;
    display: grid;
    max-width: 1180px;
    grid-template-columns: 84px 1fr;
    border-radius: 20px;
    border: 1px solid #d8deef;
    background: #f3f4f8;
    box-shadow: 0 24px 40px -30px rgba(26, 45, 106, 0.5);
}

.left-rail {
    display: flex;
    flex-direction: column;
    background: #1f3fa8;
    color: #fff;
    border-radius: 20px 0 0 20px;
}

.rail-tab {
    width: 100%;
    height: 18px;
    background: #4162ca;
    border-radius: 20px 0 10px 0;
}

.rail-nav,
.rail-footer {
    display: grid;
    gap: 0.25rem;
    padding: 0.85rem 0.45rem;
}

.rail-footer {
    margin-top: auto;
    padding-bottom: 0.9rem;
}

.nav-item {
    display: grid;
    justify-items: center;
    gap: 0.4rem;
    border-radius: 8px;
    padding: 0.55rem 0.25rem;
    color: #e9efff;
    text-align: center;
    text-decoration: none;
    font-size: 0.75rem;
    line-height: 1.15;
}

.nav-item.active {
    background: #2c4db8;
}

.icon-wrap {
    display: inline-flex;
}

.leave-content {
    padding: 1rem 1rem 1.15rem;
}

.top-row {
    display: flex;
    align-items: start;
    justify-content: space-between;
    margin-bottom: 0.85rem;
}

.hello-text {
    margin: 0;
    color: #1f3fa8;
    font-size: 1.1rem;
}

.name-text {
    margin: 0;
    color: #1a2c7f;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.header-icons {
    display: flex;
    gap: 0.6rem;
}

.icon-btn,
.profile-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    border: 1px solid #c8d1ed;
    background: #fff;
    color: #1f3fa8;
}

.icon-btn {
    width: 34px;
    height: 34px;
}

.profile-btn {
    width: 34px;
    height: 34px;
    font-weight: 700;
}

.summary-row {
    display: grid;
    grid-template-columns: 1.65fr 1fr 1fr;
    gap: 1rem;
    margin-bottom: 0.95rem;
}

.upcoming-card,
.mini-stat,
.request-card,
.announcement-card {
    border-radius: 11px;
    border: 1px solid #7990d1;
    background: #f7f8fd;
}

.upcoming-card {
    padding: 0.85rem;
}

.upcoming-card h2 {
    margin: 0 0 0.5rem;
    color: #223f95;
    font-size: 1.95rem;
}

.days-count {
    margin: 0;
    font-size: 1.2rem;
    color: #101a36;
}

.range-line {
    margin: 0.25rem 0 0.4rem;
    font-size: 0.92rem;
    color: #434a5f;
}

.leave-type {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: #2c3246;
}

.leave-type :deep(svg) {
    color: #2ea37f;
}

.mini-stat {
    display: grid;
    place-content: center;
    min-height: 128px;
    text-align: center;
    padding: 0.8rem;
}

.mini-num {
    margin: 0;
    color: #2449ad;
    font-size: 2rem;
    font-weight: 700;
    line-height: 1.1;
}

.mini-label {
    margin: 0.65rem 0 0;
    color: #273156;
    font-size: 0.9rem;
}

.main-row {
    display: grid;
    grid-template-columns: 2.35fr 1fr;
    gap: 0.95rem;
}

.request-card {
    padding: 0.8rem;
}

.request-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 0.65rem;
}

.request-head h2,
.announcement-card h2 {
    margin: 0;
    color: #223f95;
    font-size: 1.95rem;
}

.date-range {
    margin: 0;
    color: #1f3fa8;
    font-weight: 600;
}

.request-grid {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 0.8rem;
}

.left-form {
    display: grid;
    gap: 0.6rem;
}

.left-form label {
    display: grid;
    gap: 0.35rem;
    color: #505768;
    font-size: 0.82rem;
}

.left-form select,
.left-form textarea {
    border-radius: 7px;
    border: 1px solid #b7c1df;
    background: #fff;
    color: #26314f;
    font-size: 1.28rem;
    padding: 0.55rem 0.7rem;
}

.left-form textarea {
    min-height: 124px;
    resize: none;
}

.calendar-box {
    border: 1px solid #bcc6e2;
    border-radius: 7px;
    background: #fff;
    padding: 0.65rem;
}

.month-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #e6e9f2;
    padding-bottom: 0.52rem;
    margin-bottom: 0.65rem;
}

.month-head button {
    border: none;
    background: transparent;
    color: #2d375b;
    font-size: 1rem;
    cursor: pointer;
}

.month-head strong {
    color: #273a7d;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: 0.36rem;
    text-align: center;
    color: #31374a;
    font-size: 0.88rem;
}

.calendar-grid .selected {
    border-radius: 999px;
    background: #2f4eb4;
    color: #fff;
}

.calendar-grid .selected.light {
    background: #bac7ef;
    color: #1c2c63;
}

.request-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.55rem;
    margin-top: 0.75rem;
}

.draft-btn,
.send-btn {
    border-radius: 6px;
    border: 1px solid #3556be;
    padding: 0.44rem 0.85rem;
    font-weight: 600;
    cursor: pointer;
}

.draft-btn {
    background: #f7f8fd;
    color: #1f3fa8;
}

.send-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #2f4eb4;
    color: #fff;
}

.announcement-card {
    padding: 0.75rem;
}

.announcement-card ul {
    margin: 0.55rem 0 0;
    padding-left: 1rem;
    display: grid;
    gap: 0.72rem;
}

.announcement-card li p {
    margin: 0;
    color: #2e3245;
    font-size: 0.79rem;
    line-height: 1.4;
}

.announcement-card li span {
    display: block;
    margin-top: 0.2rem;
    text-align: right;
    color: #8f95a8;
    font-size: 0.68rem;
}

@media (max-width: 1160px) {
    .summary-row {
        grid-template-columns: 1fr;
    }

    .main-row {
        grid-template-columns: 1fr;
    }

    .request-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 760px) {
    .leave-shell {
        grid-template-columns: 1fr;
    }

    .left-rail {
        border-radius: 20px 20px 0 0;
        display: block;
        padding: 0.45rem;
    }

    .rail-tab {
        display: none;
    }

    .rail-nav,
    .rail-footer {
        display: flex;
        flex-wrap: wrap;
        margin: 0;
        padding: 0;
        gap: 0.35rem;
    }

    .rail-footer {
        margin-top: 0.35rem;
    }

    .nav-item {
        flex: 1 1 112px;
        grid-template-columns: auto 1fr;
        align-items: center;
        justify-items: start;
        text-align: left;
        padding-inline: 0.6rem;
    }

    .leave-content {
        padding-top: 0.8rem;
    }
}
</style>
