<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Claim Status Update</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { width: 100%; background-color: #f4f6f9; padding: 40px 0; }
        .container { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); padding: 40px 40px 30px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; font-weight: 700; letter-spacing: 0.5px; }
        .header p { color: #a8b4c8; margin: 8px 0 0; font-size: 13px; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #1a1a2e; font-weight: 600; margin-bottom: 12px; }
        .message { font-size: 14px; color: #5a6a7e; line-height: 1.7; margin-bottom: 28px; }
        .status-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 20px 24px; margin-bottom: 28px; }
        .status-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .status-row:last-child { margin-bottom: 0; }
        .status-label { font-size: 12px; color: #8898aa; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px; }
        .status-value { font-size: 14px; color: #1a1a2e; font-weight: 600; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-pending     { background: #fff3cd; color: #856404; }
        .badge-review      { background: #cfe2ff; color: #084298; }
        .badge-approved    { background: #d1e7dd; color: #0a3622; }
        .badge-rejected    { background: #f8d7da; color: #842029; }
        .badge-settled     { background: #e2d9f3; color: #432874; }
        .divider { border: none; border-top: 1px solid #e9ecef; margin: 24px 0; }
        .details-title { font-size: 13px; font-weight: 700; color: #1a1a2e; text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 14px; }
        .detail-item { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px; }
        .detail-item .key { color: #8898aa; }
        .detail-item .val { color: #1a1a2e; font-weight: 600; }
        .footer { background: #f8fafc; padding: 24px 40px; text-align: center; border-top: 1px solid #e9ecef; }
        .footer p { font-size: 12px; color: #adb5bd; margin: 0; line-height: 1.6; }
        .footer strong { color: #1a1a2e; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🔔 Claim Status Update</h1>
            <p>Insurance Management System</p>
        </div>

        <!-- Body -->
        <div class="body">
            <p class="greeting">Hello, {{ $claim->user->name ?? 'Valued Customer' }},</p>
            <p class="message">
                We wanted to let you know that the status of your insurance claim
                <strong>{{ $claim->claim_number }}</strong> has been updated.
            </p>

            <!-- Status Change Card -->
            <div class="status-card">
                <div class="status-row">
                    <span class="status-label">Previous Status</span>
                    <span class="badge
                        @if($previousStatus === 'Pending') badge-pending
                        @elseif($previousStatus === 'Under Review') badge-review
                        @elseif($previousStatus === 'Approved') badge-approved
                        @elseif($previousStatus === 'Rejected') badge-rejected
                        @else badge-settled @endif">
                        {{ $previousStatus }}
                    </span>
                </div>
                <div style="text-align:center;color:#adb5bd;font-size:20px;margin:6px 0;">↓</div>
                <div class="status-row">
                    <span class="status-label">New Status</span>
                    <span class="badge
                        @if($newStatus === 'Pending') badge-pending
                        @elseif($newStatus === 'Under Review') badge-review
                        @elseif($newStatus === 'Approved') badge-approved
                        @elseif($newStatus === 'Rejected') badge-rejected
                        @else badge-settled @endif">
                        {{ $newStatus }}
                    </span>
                </div>
            </div>

            <!-- Claim Details -->
            <p class="details-title">Claim Details</p>
            <div class="detail-item">
                <span class="key">Claim Number</span>
                <span class="val">{{ $claim->claim_number }}</span>
            </div>
            <div class="detail-item">
                <span class="key">Quote Reference</span>
                <span class="val">{{ $claim->quote->quote_number ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="key">Claim Amount</span>
                <span class="val">₹{{ number_format($claim->claim_amount, 2) }}</span>
            </div>
            <div class="detail-item">
                <span class="key">Updated On</span>
                <span class="val">{{ now()->format('d M Y, h:i A') }}</span>
            </div>

            <hr class="divider"/>

            @if($newStatus === 'Approved')
            <p class="message" style="background:#d1e7dd;border-radius:8px;padding:14px 16px;color:#0a3622;">
                🎉 <strong>Congratulations!</strong> Your claim has been approved. Our team will process the payment and update you shortly.
            </p>
            @elseif($newStatus === 'Rejected')
            <p class="message" style="background:#f8d7da;border-radius:8px;padding:14px 16px;color:#842029;">
                We regret to inform you that your claim has been rejected. Please contact our support team for further assistance.
            </p>
            @elseif($newStatus === 'Settled')
            <p class="message" style="background:#e2d9f3;border-radius:8px;padding:14px 16px;color:#432874;">
                ✅ Your claim has been <strong>settled</strong>. Thank you for choosing our insurance services.
            </p>
            @else
            <p class="message">
                Our team is actively reviewing your claim. We will notify you as soon as there is a further update. Thank you for your patience.
            </p>
            @endif
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
