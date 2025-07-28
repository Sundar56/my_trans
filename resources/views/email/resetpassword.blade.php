<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
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
        .password-box {
            background-color: #f6f9fc;
            border-left: 5px solid #0a0b09;
            padding: 18px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: bold;
            font-size: 18px;
            text-align: center;
            letter-spacing: 1px;
        }
        .login-instructions {
            margin-top: 20px;
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
            <h1>{{ $subject }}</h1>
        </div>
        @if($subject == 'Reset Password')
        <div class="email-body">
            <p class="user-name">Hi {{ $username }}</p>
            <p>We've received a request to reset your password. Here's your temporary password:</p>
            <div class="password-box">
            {{ $password }}
            </div>
            <p class="login-instructions">Please use this password to log into your account. For your security, we recommend changing your password after logging in.</p>
        </div>
        <div class="email-footer">
            <p>© {{ date('Y') }}. All rights reserved.</p>
        </div>
        @else
         <div class="email-body">
            <p class="user-name">Hi {{ $username }}</p>
            <p>Your Account has been created succesfully,Here's your temporary password:</p>
            <div class="password-box">
            {{ $password }}
            </div>
            <p class="login-instructions">Please use this password to log into your account. For your security, we recommend changing your password after logging in.</p>
        </div>
        <div class="email-footer">
            <p>© {{ date('Y') }}. All rights reserved.</p>
        </div>
        @endif
    </div>
</body>
</html>
