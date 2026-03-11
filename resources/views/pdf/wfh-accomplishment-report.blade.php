<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Work From Home Individual Accomplishment Report</title>
    <style>
        @page { margin: 0.6in; }
        body {
            font-family: DejaVu Sans, Helvetica, sans-serif;
            font-size: 9pt;
            margin: 0;
            padding: 0;
            color: #000;
        }
        .header-image {
            width: 100%;
            max-width: 11in;
            height: auto;
            max-height: 2in;
            margin: 0 auto 0.25in;
            display: block;
        }
        .report-title {
            font-size: 11pt;
            font-weight: bold;
            text-align: center;
            margin: 0 0 0.1in;
        }
        .report-date {
            font-size: 10pt;
            text-align: center;
            margin: 0 0 0.15in;
        }
        .report-title-line {
            border: none;
            border-top: 1px solid #000;
            margin: 0 0 0.2in;
        }
        .employee-name {
            font-weight: bold;
            margin-bottom: 0.15in;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9pt;
        }
        th, td {
            border: 1px solid #000;
            padding: 3px;
        }
        th {
            font-weight: bold;
            background-color: #f2f2f2;
        }
        th.col-task { text-align: left; }
        th.col-accomplishment { text-align: left; }
        th.col-date { text-align: center; }
        th.col-priority { text-align: center; }
        th.col-station { text-align: left; }
        td.col-task { text-align: left; }
        td.col-accomplishment { text-align: left; }
        td.col-date { text-align: center; }
        td.col-priority { text-align: center; }
        td.col-station { text-align: left; }
        .footer-image {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            max-height: 1in;
            height: auto;
            display: block;
        }
        .footer-text {
            position: fixed;
            bottom: 0.35in;
            left: 0;
            right: 0;
            font-size: 8pt;
            font-style: italic;
            color: #666;
            text-align: center;
        }
        .content-wrap {
            padding-bottom: 1.2in;
        }
    </style>
</head>
<body>
    @if(!empty($headerImageDataUri))
        <img src="{{ $headerImageDataUri }}" alt="Header" class="header-image">
    @endif
    <h1 class="report-title">WORK FROM HOME INDIVIDUAL ACCOMPLISHMENT REPORT</h1>
    <p class="report-date">{{ $subtitle }}</p>
    <hr class="report-title-line">

    <div class="content-wrap">
        <p class="employee-name">Name: {{ $employeeName }}</p>
        <table>
            <thead>
                <tr>
                    <th class="col-task">Targeted Task/ Assignments/ Output</th>
                    <th class="col-accomplishment">Actual Accomplishment/Output</th>
                    <th class="col-date">Date</th>
                    <th class="col-priority">Priority</th>
                    <th class="col-station">Station</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasks as $task)
                    <tr>
                        <td class="col-task">{{ $task['title'] }}</td>
                        <td class="col-accomplishment">{{ $task['accomplishment'] }}</td>
                        <td class="col-date">{{ $task['date_range'] }}</td>
                        <td class="col-priority">{{ $task['priority'] }}</td>
                        <td class="col-station">{{ $station }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center;">No tasks in this report.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(!empty($footerImageDataUri))
        <img src="{{ $footerImageDataUri }}" alt="Footer" class="footer-image">
    @endif
    <p class="footer-text">© {{ date('Y') }} DepEd</p>
</body>
</html>
