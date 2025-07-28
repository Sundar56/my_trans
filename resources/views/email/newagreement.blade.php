<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Construction Agreement</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 40px;
            color: #333;
            line-height: 1.6;
        }

        h1,
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section h3 {
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 10px;
            color: #444;
        }

        .signature-container {
            display: flex;
            justify-content: flex-end;
            margin-top: 40px;
        }

        .signature {
            /* width: 300px; */
            text-align: left;
        }

        .signature-line {
            line-height: 2;
            margin-bottom: 20px;
        }

        .footer {
            font-size: 0.85em;
            text-align: center;
            margin-top: 20px;
            color: #888;
        }
    </style>
</head>

<body>
    <div style="text-align: center; margin-bottom: 30px;">
        <img src="{{ $projectData['cpsLogo'] }}" width="187" height="63" alt="Company Logo">
    </div>

    <h1>{{ $projectData['project']['projectname'] ?? '-' }} Project's Agreement</h1>

    <div class="section">
        <p>This Agreement is made on <strong>{{$projectData['formattedCreatedAt']}}</strong> between:</p>
        <p>{{$projectData['project']['contractor_name'] ?? '-'}} <strong>[Legal Name of Party Being Paid]</strong>, with a registered address at <strong>{{$projectData['project']['contractor_address'] ?? '-'}}</strong>,</p>
        <p>{{ $projectData['customerName']?? '-' }} <strong>[Name of Party for Whose Benefit the Work is Being Done]</strong>, with a registered address at <strong>{{ $projectData['customerAddress'] ?? '-' }}</strong>.</p>
    </div>

    <div class="section">
        <h3>1. Scope of Work</h3>
        <p>The Contractor agrees to perform the following work at the address <strong>{{ $projectData['project']['projectlocation'] ?? '-' }}</strong>:</p>
        <p>Description of Work: <strong>{{$projectData['taskNames']}}</strong>.</p>
        <p>
            <strong>Task Breakdown and Amounts:</strong>
        </p>
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
                    <td style="text-align: right;">{{$projectData['currencySymbol']}}{{ number_format($task['taskamount'] ?? 0, 2) }}</td>
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
                    <td style="text-align: right;"><strong>{{$projectData['currencySymbol']}}{{ number_format($totalAmount, 2) }}</strong></td>
                </tr>
                @endif
            </tbody>
        </table>

        <p>Full details of the scope of works to be completed are set out in the CPS dashboard. <strong>CPS{{ $projectData['project']['id'] ?? '-' }}</strong></p>
        <p>This reference number corresponds to specific defined tasks on the CPS dashboard that can no longer be changed or amended.</p>
    </div>

    <div class="section">
        <h3>2. Payment Terms</h3>
        <p>The Client agrees to compensate the Contractor for the above-described work as per the agreed pricing schedule. <strong>Total Project Amount: {{$projectData['currencySymbol']}}{{ number_format($projectData['project']['projectamount'], 2) }}</strong></p>
        <p>Payment shall be made in accordance with the milestones set forth in the attached schedule.</p>
    </div>

    <div class="section">
        <h3>3. Timelines and Completion</h3>
        <p>The Contractor shall commence work on <strong>{{$projectData['projectStartDate']}}</strong> and complete work by <strong>{{$projectData['projectEndDate']}}</strong>, unless delays occur due to unforeseen circumstances outside the control of the Contractor.</p>
    </div>

    <div class="section">
        <h3>4. Liability and Warranties</h3>
        <p>The Contractor warrants that all work will be performed in a professional and diligent manner, in compliance with industry standards.</p>
        <p>Any defects or issues arising from substandard workmanship shall be rectified within a reasonable time frame.</p>
    </div>


    <div class="section">
        <h3>5. Acknowledgement</h3>
        <p>By submitting payment for these works, both parties acknowledge and agree to the terms outlined in this agreement. Payment constitutes formal acceptance of all contractual obligations and conditions, without requiring a separate signature or additional confirmation.</p>
        <p>This agreement is binding upon the successful transfer of funds from one party to the other.</p>
    </div>
    <table style="width: 100%; margin-top: 30px; font-family: Arial, sans-serif; font-size: 14px;">
        <tr>
            <!-- Left Side: Logo Card -->
            <td style="width: 50%; vertical-align: top;">
                <table style="
        width: 220px;
        height: 205px;
        border: 1px solid #0000002D;
        border-radius: 20px;
        -webkit-border-radius: 20px;
        -moz-border-radius: 20px;
        font-family: Arial, sans-serif;
        font-size: 13px;
        margin-top: 18.64px;
    " cellspacing="0" cellpadding="0">
                    <tr>
                        <td style="text-align: center; padding-top: 20px;">
                            <img src="{{ $projectData['transpactLogo'] }}"
                                alt="Transpact Logo"
                                style="width: 150px; height: 60px; object-fit: contain;">
                        </td>
                    </tr>
                    <tr>
                        <td style="
                text-align: center;
                padding: 15px 10px 20px 10px;
                color: #333;
            ">
                            Working in partnership with <strong>Transpact</strong><br>
                            to keep your money secure.
                        </td>
                    </tr>
                </table>
            </td>
            <!-- Right Side: Signatures -->
            <td style="width: 50%; vertical-align: top; text-align: right;">
                <!-- Contractor Section -->
                <strong>Contractor</strong><br><br>
                @if (!empty($projectData['contractorSign']))
                <img src="{{ $projectData['contractorSign'] }}" width="120" height="60" style="border: 1px solid #ccc;" alt="Contractor Signature">
                @else
                <span>No signature found</span>
                @endif
                <br><br>
                Name: <strong>{{ $projectData['project']['contractor_name'] ?? '-' }}</strong><br><br>

                <!-- Spacer -->
                <div style="margin-top: 40px;"></div>

                <!-- Customer Section -->
                <strong>Customer</strong><br><br>
                @if (!empty($projectData['customerSign']))
                <img src="{{ $projectData['customerSign'] }}" width="120" height="60" style="border: 1px solid #ccc;" alt="Customer Signature">
                @else
                <span>No signature found</span>
                @endif
                <br><br>
                Name: <strong>{{ $projectData['project']['customer_name'] ?? '-' }}</strong><br><br>
            </td>
        </tr>
    </table>

    <div class="footer">
        &copy; {{ date('Y') }} Construction Payment Scheme - All Rights Reserved
    </div>

</body>

</html>