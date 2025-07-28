<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Transpact Instructions</title>
    <style>
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

        .container {
            max-width: 600px;
            margin: 30px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #ddd;
        }

        .header {
            padding: 20px 0;
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
        }

        .header img {
            max-height: 50px;
            display: inline-block;
        }

        .content {
            padding: 0 30px 30px 30px;
            font-size: 16px;
            line-height: 1.6;
            color: #4a5568;
        }

        .content p {
            margin: 15px 0;
        }

        .content ol {
            padding-left: 20px;
        }

        .footer {
            font-size: 13px;
            color: #a0aec0;
            text-align: center;
            padding: 15px 30px 30px 30px;
            border-top: 1px solid #e2e8f0;
            font-style: italic;
        }

        @media screen and (max-width: 640px) {
            .container {
                width: 90% !important;
            }

            .content {
                padding: 0 15px 20px 15px !important;
            }

            .footer {
                padding: 10px 15px 20px 15px !important;
            }
        }
    </style>
</head>

<body>
    <center>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" bgcolor="#f7f9fc">
            <tr>
                <td align="center">
                    <table role="presentation" class="container" width="600" cellpadding="0" cellspacing="0" bgcolor="#ffffff">
                        <tr>
                            <td class="header">
                                @if($userData['type'] == 'customer' || $userData['type'] == 'contractor')
                                <img src="{{ $userData['logoPath'] ?? '-' }}" alt="Company Logo" />
                                @else
                                <img src="{{ $projectData['logoPath'] ?? '-' }}" alt="Company Logo" />
                                @endif
                            </td>
                        </tr>
                        @if($userData['type'] == 'customer')
                        <tr>
                            <td class="content" style="padding: 30px;">
                                <h2 style="color: #2c3e50;">Hello {{$userData['userName'] ?? '-'}},</h2>
                                <p>Welcome to <strong>CPS</strong> – we're excited to have you on board!</p>
                                <p>Based on your registration, you're now registered as a <strong>Customer</strong> in CPS.</p>
                                <p>Here’s what you can do with your new account:</p>

                                <ul style="padding-left: 20px;">
                                    <li><strong>Project and Tasks</strong><br>
                                        - You can monitor your contractor projects and tasks status and you can approve the project before its started<br>
                                        - Smooth agreement sign between you and your contractor
                                    </li>
                                    <li><strong>Payment and Transaction</strong><br>
                                        - You can see all the payment transaction flow on each and every projects ( You can send the funds to Transpact's Escrow. And release the fund from Escrow to your contractor bank account)
                                    </li>
                                    <li><strong>Dispute</strong><br>
                                        - You can raise the dispute of the project and admin will interact with you through the chat features we provided.<br>
                                        - Admin will take care your dispute points and he will try to resolve all your points based on your satisfaction.
                                    </li>
                                </ul>
                                <p> If you have not registered on Transpact using this email, please <a href="{{$userData['transpactUrl'] ?? '-'}}" style="color: #007bff;">register here</a>.</p>
                            </td>
                        </tr>
                        @elseif($userData['type'] == 'contractor')
                        <tr>
                            <td class="content" style="padding: 30px;">
                                <h2 style="color: #2c3e50;">Hello {{$userData['userName'] ?? '-'}},</h2>
                                <p>Welcome to <strong>CPS</strong> – we're excited to have you on board!</p>
                                <p>Based on your registration, you're now registered as a <strong>Contractor</strong> in CPS.</p>
                                <p>Here’s what you can do with your new account:</p>

                                <ul style="padding-left: 20px;">
                                    <li><strong>Project and Tasks</strong><br>
                                        - You can create project and task and invite your customer to monitor the project and tasks status<br>
                                        - Getting agreement sign between you and your customers
                                    </li>
                                    <li><strong>Payment transaction</strong><br>
                                        - You can see all the payment transaction flow (Customer pays to Traspact's Escrow, and release the fund from Escrow to your account)
                                    </li>
                                </ul>
                                <p> If you have not registered on Transpact using this email, please <a href="{{$userData['transpactUrl'] ?? '-'}}" style="color: #007bff;">register here</a>.</p>
                            </td>
                        </tr>
                        @else
                        <tr>
                            <td class="content">
                                <h2 style="color: #2c3e50;">Hello {{$projectData['customerName'] ?? '-'}},</h2>

                                <p>Please do the following steps:</p>

                                <ol>
                                    <li>If you have not registered on Transpact using this email, please <a href="{{$projectData['transpactUrl'] ?? '-'}}" style="color: #007bff;">register here</a>.</li>
                                    <li>If you're already registered, please <a href="{{$projectData['transpactLoginUrl'] ?? '-'}}" style="color: #007bff;">login to Transpact</a>.<br>
                                        Note: If you haven’t participated in any projects before, your transactions list may be empty at first.
                                    </li>
                                    <li>Go to the CPS portal and check for any assigned projects. <a href="{{$projectData['projectViewUrl'] ?? '-'}}" style="color: #007bff;">Login to CPS</a>.<br>
                                        If a project is listed but no Transpact transaction has been created, click on the <strong>“Create Transpact”</strong> button to deposit the required amount.
                                    </li>
                                </ol>
                            </td>
                        </tr>
                        @endif
                        <tr>
                            <td class="footer">
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