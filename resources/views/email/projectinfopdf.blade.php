<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Project Info</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 20px;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .logo {
            height: 50px;
        }

        h2,
        h3 {
            color: #2c3e50;
            margin-top: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th,
        td {
            padding: 8px 10px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f0f4f8;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 11px;
            color: #888;
            margin-top: 40px;
        }
    </style>
</head>

<body>

    <!-- Header with Logo -->
    <div class="header">
        <img src="{{ $projectData['cpsLogo'] }}" width="52" height="59" alt="Company Logo">
    </div>

    <!-- Project Information -->
    <h2>Project Information</h2>
    <table>
        <tr>
            <th>Project Name</th>
            <td>{{$projectData['project']['projectname'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Contractor Name</th>
            <td>{{$projectData['project']['contractor_name'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Contractor Email</th>
            <td>{{$projectData['project']['contractor_email'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Contractor Business Name</th>
            <td>{{$projectData['project']['contractor_businessname'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Contractor Company Register Number</th>
            <td>{{$projectData['project']['contractor_company_registernum'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Project Location</th>
            <td>{{$projectData['project']['projectlocation'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Project Start Date</th>
            <td>{{$projectData['project']['startdate'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Project Completion Date</th>
            <td>{{$projectData['project']['completiondate'] ?? '-'}}</td>
        </tr>
        <tr>
            <th>Project Conditions</th>
            <td>{{$projectData['project']['conditions'] ?? '-'}}</td>
        </tr>
    </table>

    <h3>Project Tasks</h3>
    @php
    $tasks = $projectData['tasks'] ?? [];
    $totalAmount = $projectData['project']['projectamount'] ?? 0;
    @endphp

    <table border="1" cellpadding="8" cellspacing="0" width="100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Task Name</th>
                <th>Task Amount {{$projectData['currencyType']}}</th>
            </tr>
        </thead>
        <tbody>
            @if(count($tasks))
            @foreach($tasks as $index => $task)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $task['taskname'] ?? '-' }}</td>
                <td>{{$projectData['currencySymbol']}}{{ number_format($task['taskamount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
            @else
            <tr>
                <td colspan="3" align="center">No tasks available.</td>
            </tr>
            @endif

            @if($totalAmount)
            <tr>
                <td colspan="2" align="right"><strong>Total Project Amount:</strong></td>
                <td><strong>{{$projectData['currencySymbol']}}{{ number_format($totalAmount, 2) }}</strong></td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Footer -->
    <div class="footer">
        © {{ now()->year }} Construction Payment Scheme. All rights reserved.
    </div>

</body>

</html>