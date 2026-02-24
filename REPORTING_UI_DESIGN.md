# Teacher Reports UI Design Reference

## Overview
This document provides a visual reference for the Teacher Reports interface design.

## Page Layout Structure

```
┌─────────────────────────────────────────────────────────────────────────┐
│  HEADER SECTION                                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  Teacher Reports                                    [Export PDF]  │  │
│  │  Generate comprehensive reports for teachers...     [Export Excel]│  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│  FILTER SECTION (Card)                                                   │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  🔍 Report Filters                                    [Clear All]  │  │
│  │  Select criteria to filter the report results                     │  │
│  │                                                                   │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ School/Office│  │  Job Title    │  │   Subject    │          │  │
│  │  │ [Dropdown ▼] │  │ [Dropdown ▼] │  │ [Dropdown ▼] │          │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  │                                                                   │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ Grade Level  │  │ Emp. Status   │  │ Salary Grade │          │  │
│  │  │ [Dropdown ▼] │  │ [Dropdown ▼] │  │ [Dropdown ▼] │          │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  │                                                                   │  │
│  │  ┌──────────────────────────────────────────────┐  ┌──────────┐  │  │
│  │  │ 🔍 Search Employee                          │  │ Generate │  │  │
│  │  │ [Search by name, employee ID...]            │  │  Report  │  │  │
│  │  └──────────────────────────────────────────────┘  └──────────┘  │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│  REPORT RESULTS SECTION (Card)                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  Report Results                                    [CSV] [Excel]   │  │
│  │  Showing 3 records                                 [Print]        │  │
│  │                                                                   │  │
│  │  ┌────────────┐  ┌────────────┐  ┌────────────┐  ┌────────────┐ │  │
│  │  │ Total      │  │ Permanent  │  │ Avg Leave  │  │ Outstanding│ │  │
│  │  │ Teachers   │  │            │  │ Balance    │  │ Performers │ │  │
│  │  │    3       │  │    3       │  │   8.5      │  │    1       │ │  │
│  │  └────────────┘  └────────────┘  └────────────┘  └────────────┘ │  │
│  │                                                                   │  │
│  │  ┌─────────────────────────────────────────────────────────────┐  │  │
│  │  │  Data Table                                                 │  │  │
│  │  │  ┌──────┬──────────┬──────────┬────────┬────────┬────────┐ │  │  │
│  │  │  │ ID   │ Name     │ Job Title│ Subject│ Grade  │ School │ │  │  │
│  │  │  ├──────┼──────────┼──────────┼────────┼────────┼────────┤ │  │  │
│  │  │  │20001 │ Juan...  │ Teacher I│ Math   │ Grade 7│ OCCS   │ │  │  │
│  │  │  │20002 │ Maria... │ TeacherII│ English│ Grade 8│ OCNHS  │ │  │  │
│  │  │  │20003 │ Carlo... │ Teacher I│ Math   │ Grade 7│ OCCS   │ │  │  │
│  │  │  └──────┴──────────┴──────────┴────────┴────────┴────────┘ │  │  │
│  │  └─────────────────────────────────────────────────────────────┘  │  │
│  │                                                                   │  │
│  │  Showing 1 to 3 of 3 results              [Previous] [Next]     │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────────────┐
│  QUICK REPORT TEMPLATES (Card)                                          │
│  ┌───────────────────────────────────────────────────────────────────┐  │
│  │  Quick Report Templates                                            │  │
│  │  Pre-configured report templates for common queries                │  │
│  │                                                                   │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ School +     │  │ Subject      │  │ Grade +      │          │  │
│  │  │ Job Title    │  │ Taught        │  │ Subject      │          │  │
│  │  │ All Teacher I│  │ All Math     │  │ All Grade 7  │          │  │
│  │  │ in a school  │  │ teachers     │  │ Math teachers│          │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  │                                                                   │  │
│  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │  │
│  │  │ Status +     │  │ Low Leave     │  │ Performance   │          │  │
│  │  │ Salary Grade │  │ Balance       │  │ Rating        │          │  │
│  │  │ All permanent│  │ Teachers with│  │ High-performing│         │  │
│  │  │ SG 11 teachers│ │ < 5 days leave│ │ teachers      │          │  │
│  │  └──────────────┘  └──────────────┘  └──────────────┘          │  │
│  └───────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
```

## Component Breakdown

### 1. Header Section
- **Title**: "Teacher Reports" (Large, Bold)
- **Description**: Brief explanation of the page purpose
- **Actions**: Export buttons (PDF, Excel) on the right

### 2. Filter Section Card
- **Title**: "Report Filters" with filter icon
- **Description**: Instructions for using filters
- **Filter Grid**: 3-column responsive grid with:
  - School/Office dropdown
  - Job Title dropdown
  - Subject Taught dropdown
  - Grade Level dropdown
  - Employment Status dropdown
  - Salary Grade dropdown
- **Search Bar**: Full-width search input with search icon
- **Action Buttons**: 
  - "Clear All" button (top right)
  - "Generate Report" button (bottom right)

### 3. Report Results Card
- **Header**: 
  - Title: "Report Results"
  - Record count
  - Export buttons (CSV, Excel, Print)
- **Summary Statistics**: 4 stat cards showing:
  - Total Teachers
  - Permanent Employees
  - Average Leave Balance
  - Outstanding Performers
- **Data Table**: Full-width table with columns:
  - Employee ID
  - Name
  - Job Title (with badge)
  - Subject
  - Grade Level
  - School
  - Salary Grade
  - Status (with badge)
  - Leave Balance (with color-coded badge)
  - Performance Rating (with color-coded badge)
- **Pagination**: Bottom navigation with page info and Previous/Next buttons

### 4. Quick Report Templates Card
- **Title**: "Quick Report Templates"
- **Description**: Explanation of quick templates
- **Template Grid**: 3-column grid of clickable template buttons:
  - School + Job Title
  - Subject Taught
  - Grade + Subject
  - Status + Salary Grade
  - Low Leave Balance
  - Performance Rating

## Color Coding & Badges

### Employment Status Badges
- **Permanent**: Primary color (blue/green)
- **Temporary/Contractual**: Secondary color (gray)

### Leave Balance Badges
- **< 5 days**: Red/Destructive (warning)
- **≥ 5 days**: Outline (normal)

### Performance Rating Badges
- **Outstanding**: Primary color (blue/green)
- **Very Satisfactory**: Secondary color (gray)
- **Satisfactory**: Outline (gray)

## Responsive Design

### Desktop (lg: 1024px+)
- 3-column filter grid
- Full table with all columns
- Side-by-side summary stats

### Tablet (md: 768px+)
- 2-column filter grid
- Scrollable table
- 2x2 summary stats grid

### Mobile (< 768px)
- 1-column filter grid
- Stacked summary stats
- Horizontal scrollable table

## User Flow

1. **Select Filters**: User selects criteria from dropdowns
2. **Optional Search**: User can search by name or ID
3. **Generate Report**: Click "Generate Report" button
4. **View Results**: Results displayed in table with summary stats
5. **Export/Print**: User can export to PDF, Excel, CSV, or print
6. **Quick Templates**: User can click template buttons to auto-fill filters

## Example Filter Combinations

### Example 1: School + Job Title
```
School: Ozamiz City Central School
Job Title: Teacher I
Result: All Teacher I in Ozamiz City Central School
```

### Example 2: Subject + Grade Level
```
Subject: Mathematics
Grade Level: Grade 7
Result: All Grade 7 Math teachers
```

### Example 3: Employment Status + Salary Grade
```
Employment Status: Permanent
Salary Grade: SG 11
Result: All permanent teachers in Salary Grade 11
```

## Export Options

1. **PDF Export**: Formatted report with headers, filters applied, and table
2. **Excel Export**: Spreadsheet with all data, filters, and summary stats
3. **CSV Export**: Raw data in comma-separated format
4. **Print**: Print-friendly version with page breaks

## Future Enhancements

1. **Save Report Templates**: Save custom filter combinations
2. **Schedule Reports**: Automatically generate and email reports
3. **Advanced Filters**: Date ranges, performance ranges, etc.
4. **Chart Visualizations**: Graphs and charts for data analysis
5. **Comparison Reports**: Compare data across schools/departments
6. **Drill-down**: Click on summary stats to see detailed breakdown
