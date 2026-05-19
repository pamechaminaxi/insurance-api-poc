<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Quote Notification</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { width: 100%; background-color: #f4f6f9; padding: 40px 0; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header-created  { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 40px 40px 30px; text-align: center; }
        .header-approved { background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #047857 100%); padding: 40px 40px 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
        .header p  { color: rgba(255,255,255,0.7); margin: 8px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #1a1a2e; font-weight: 600; margin-bottom: 12px; }
        .message { font-size: 14px; color: #5a6a7e; line-height: 1.7; margin-bottom: 28px; }
        .info-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 24px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .detail-item:last-child { margin-bottom: 0; }
        .detail-item .key { color: #8898aa; font-weight: 500; }
        .detail-item .val { color: #1a1a2e; font-weight: 700; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; background:#cfe2ff; color:#084298; }
        .badge-approved { background:#d1e7dd; color:#0a3622; }
        .highlight-box { border-radius: 8px; padding: 14px 16px; margin-bottom: 24px; font-size: 14px; line-height: 1.6; }
        .highlight-created  { background: #e0f2fe; color: #0369a1; border-left: 4px solid #0284c7; }
        .highlight-approved { background: #d1e7dd; color: #0a3622; border-left: 4px solid #16a34a; }
        .divider { border: none; border-top: 1px solid #e9ecef; margin: 24px 0; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer p { font-size: 12px; color: #adb5bd; margin: 0; line-height: 1.6; }
        .footer strong { color: #1a1a2e; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        @if($eventType === 'approved')
        <div class="header header-approved">
            <h1>🎉 Quote Approved!</h1>
            <p>Insurance Management System</p>
        </div>
        @else
        <div class="header header-created">
            <h1>📋 Quote Created</h1>
            <p>Insurance Management System</p>
        </div>
        @endif

        <!-- Body -->
        <div class="body">
            <p class="greeting">Hello, {{ $quote->customer->name ?? $quote->customer_name }},</p>

            @if($eventType === 'approved')
            <p class="message">
                Great news! Your insurance quote <strong>{{ $quote->quote_number }}</strong> has been
                <strong>approved</strong>. You can now proceed to file a claim against this policy.
            </p>
            <div class="highlight-box highlight-approved">
                ✅ Your policy is now <strong>active</strong>. Keep your Quote Number safe —
                you'll need it when filing any claims.
            </div>
            @else
            <p class="message">
                Your insurance quote <strong>{{ $quote->quote_number }}</strong> has been successfully created
                and is currently under review by our team. We'll notify you once it has been approved.
            </p>
            <div class="highlight-box highlight-created">
                ℹ️ Your quote is in <strong>Draft</strong> status. Our agents will review and
                submit it for approval shortly.
            </div>
            @endif

            <!-- Quote Details -->
            <div class="info-card">
                <div class="detail-item">
                    <span class="key">Quote Number</span>
                    <span class="val">{{ $quote->quote_number }}</span>
                </div>
                <div class="detail-item">
                    <span class="key">Insurance Type</span>
                    <span class="val">{{ ucfirst($quote->insurance_type) }}</span>
                </div>
                <div class="detail-item">
                    <span class="key">Premium Amount</span>
                    <span class="val">₹{{ number_format($quote->premium_amount, 2) }}</span>
                </div>
                <div class="detail-item">
                    <span class="key">Coverage Amount</span>
                    <span class="val">₹{{ number_format($quote->coverage_amount, 2) }}</span>
                </div>
                <div class="detail-item">
                    <span class="key">Status</span>
                    <span class="badge {{ $eventType === 'approved' ? 'badge-approved' : '' }}">
                        {{ ucfirst($quote->status) }}
                    </span>
                </div>
                <div class="detail-item">
                    <span class="key">Date</span>
                    <span class="val">{{ now()->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is an automated email from <strong>Insurance Management System</strong>.<br/>
            Please do not reply to this email.</p>
        </div>
    </div>
</div>
</body>
</html>
