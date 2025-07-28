<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Activation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .email-header {
            background-color:#0a0b09;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 26px;
        }
        .email-body {
            padding: 30px 25px;
            color: #333;
        }
        .email-body p {
            font-size: 16px;
            line-height: 1.6;
        }
        .user-name {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .activation-box {
            background-color: #f6f9fc;
            border-left: 5px solid #0a0b09;
            padding: 20px;
            margin: 20px 0;
            border-radius: 6px;
            text-align: center;
        }
        .activation-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #0a0b09;
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            font-size: 16px;
            margin-top: 10px;
        }
        .email-footer {
            background-color: #f5f5f5;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #777;
        }
        @media screen and (max-width: 600px) {
            .email-wrapper {
                margin: 20px;
            }
            .email-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>Activate Your Account</h1>
        </div>
        @if($username == null)
        <div class="email-body">
            <p class="user-name">Hi</p>
            <p>Please activate your account with signup by clicking the button below:</p>
            <div>
            <a href="https://cps.sitecare.org/signup" class="activation-button"><span style="color:#fff;">Register Here</span></a>
           
            </div>
            <p>If you did not sign up for this account, you can safely ignore this email.</p>
        </div>
        <div class="email-footer">
            <p>Need help? Contact our support team.</p>
            <p>© {{ date('Y') }}. All rights reserved.</p>
        </div>
        @else
        <div class="email-body">
            <p class="user-name">Hi {{ $username }}</p>
            <p>Thank you for registering with us! Please activate your account by clicking the button below:</p>
            <div>
             <a href="https://cps.sitecare.org/login?code={{ $code }}" class="activation-button" style="display:inline-block; padding:12px 24px; background:#9B5A25; background:linear-gradient(90deg, #F5A623 -53.36%, #9B5A25 100%); color:#fff; text-decoration:none; font-weight:bold; border-radius:5px; font-size:16px; margin-top:10px;">
               Activate Account
            </a>
            </div>
            <p>If you did not sign up for this account, you can safely ignore this email.</p>
        </div>
        <div class="email-footer">
            <p>Need help? Contact our support team.</p>
            <p>© {{ date('Y') }}. All rights reserved.</p>
        </div>
        @endif
    </div>
</body>
</html>
