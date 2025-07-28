<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Project Update</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <style>
        /* Basic resets for email */
        body,
        table,
        td,
        a {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            -ms-interpolation-mode: bicubic;
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            max-width: 100%;
            display: block;
        }

        body {
            margin: 0;
            padding: 0;
            width: 100% !important;
            background-color: #f7f9fc;
            font-family: Arial, sans-serif;
            color: #4a5568;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .container {
                width: 90% !important;
                padding: 0 !important;
                border-radius: 0 !important;
            }

            .content {
                padding: 15px !important;
                font-size: 14px !important;
                line-height: 1.5 !important;
            }

            .footer {
                padding: 10px 15px !important;
                font-size: 12px !important;
            }

            .activation-button {
                width: 50% !important;
                text-align: center !important;
                display: block !important;
                font-size: 14px !important;
                padding: 14px 10px !important;
            }

            a[href^="http"] {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
                text-align: center !important;
                font-size: 14px !important;
            }

            h1,
            h2,
            h3,
            p {
                font-size: 90% !important;
            }

            img {
                max-width: 100% !important;
                height: auto !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background-color:#f7f9fc;">
    <center>
        <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f7f9fc" role="presentation">
            <tr>
                <td align="center">
                    <table class="container" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="max-width:600px; margin:30px auto; background:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #ddd;">
                        <tr>
                            <td align="center" style="padding: 20px 0; border-bottom:2px solid #e2e8f0;">
                                <img src="{{ $projectData['logoPath'] ?? '-' }}" alt="Company Logo" style="max-height:50px; display:inline-block;" />
                            </td>
                        </tr>
                        <tr>
                            <td class="content" style="padding: 0 30px 30px 30px; font-size:14px; line-height:1.6; color:#4a5568; font-family:Arial, sans-serif;">
                                <p> {!! $projectData['projectStatusText'] ?? '
                                <p>-</p>' !!}</p>


                                <table border="0" cellspacing="0" cellpadding="0" align="center" width="100%" style="margin-top: 20px;">
                                    <tr>
                                        <td align="center" style="padding: 0 15px;">
                                            @if($projectData['project']['customer_id'] == null)
                                            <a href="{{ $projectData['signupUrl'] }}" class="activation-button" style="display: inline-block; width: 30%; max-width: 100%; padding: 8px 0; background: linear-gradient(90deg, #F5A623 -53.36%, #9B5A25 100%);
        color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 5px; font-size: 14px; font-family: Arial, sans-serif; text-align: center;">Register </a>
                                            @else
                                            <a href="{{ $projectData['projectViewUrl'] }}" class="activation-button" style="display: inline-block; width: 30%; max-width: 100%; padding: 8px 0; background: linear-gradient(90deg, #F5A623 -53.36%, #9B5A25 100%);
        color: #ffffff; text-decoration: none; font-weight: bold; border-radius: 5px; font-size: 14px; font-family: Arial, sans-serif; text-align: center;"> View Project </a>
                                            @endif
                                        </td>
                                    </tr>
                                </table>
                                @if(in_array($type, ['Invite', 'Reinvite']))
                                <p>The project titled <strong>{{ $projectData['project']['projectname'] ?? '-' }}</strong> has been created by contractor <strong>{{ $projectData['project']['contractor_name'] ?? '-' }}</strong>, who represents <strong>{{ $projectData['project']['contractor_businessname'] ?? '-' }}</strong> (Company Reg. No. {{ $projectData['project']['contractor_company_registernum'] ?? '-' }}).</p>
                                @endif
                                <p>This project is located at <strong>{{ $projectData['project']['projectlocation'] ?? '-' }}</strong> and is scheduled to begin on <strong>{{ $projectData ['projectStartDate'] ?? '-' }}</strong>, with an expected completion date of <strong>{{ $projectData ['projectEndDate'] ?? '-' }}</strong>.</p>
                                @php
                                $tasks = $projectData['tasks'] ?? [];
                                @endphp
                                @if(count($tasks))
                                @foreach($tasks as $index => $task)
                                <p>
                                    Task {{ $index + 1 }}: <strong>{{ $task->taskname }}</strong>
                                    (Status: <strong>{{ $task->taskStatusLabel ?? 'N/A' }}</strong>)
                                    — Budgeted at <strong>£{{ number_format($task->taskamount, 2) }}</strong>
                                </p>
                                @endforeach
                                @endif
                                <p>The total budgeted amount for this project is <strong>£{{ $projectData['project']['projectamount'] ?? '-' }}</strong>.</p>
                                <p>You can view more details and take necessary actions from your dashboard.</p>
                                <p>You can reach him via email at <a href="mailto:{{ $projectData['dynamicEmail'] ?? '-' }}">{{ $projectData['dynamicEmail'] ?? '-' }}</a>.</p>
                            </td>
                        </tr>
                        <tr>
                            <td class="footer" style="font-size:10px; color:#a0aec0; text-align:center; padding:15px 30px 30px 30px; border-top:1px solid #e2e8f0; font-style:italic;">
                                © {{ now()->year }} Construction Payment Scheme. All rights reserved.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </center>
</body>

</html>