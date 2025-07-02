<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>OTP Verification</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }
    .email-container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #ffffff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 0 10px rgba(0,0,0,0.05);
    }
    .email-header {
      text-align: center;
      padding-bottom: 20px;
    }
    .email-header h1 {
      color: #333;
      margin: 0;
    }
    .email-content {
      font-size: 16px;
      color: #555;
      line-height: 1.6;
    }
    .otp-box {
      text-align: center;
      background-color: #f0f0f0;
      padding: 20px;
      margin: 20px 0;
      border-radius: 8px;
      font-size: 28px;
      font-weight: bold;
      letter-spacing: 5px;
      color: #000;
    }
    .footer {
      font-size: 14px;
      color: #999;
      text-align: center;
      padding-top: 20px;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1>OTP Verification</h1>
    </div>
    <div class="email-content">
      <p>Hello,</p>
      <p>Use the following One-Time Password (OTP) to complete your verification process:</p>

      <div class="otp-box">{{ $otp }}</div>

      <p>This OTP is valid for the next 10 minutes. Please do not share it with anyone.</p>
      <p>If you didn’t request this, you can safely ignore this email.</p>

      <p>Thanks,<br><strong>Peraux Luxries</strong></p>
    </div>
    <div class="footer">
      © 2025 Peraux Luxries Private Limited. All rights reserved.
    </div>
  </div>
</body>
</html>
