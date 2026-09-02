<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $periodLabel }} Report</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding: 20px 0;
            border-bottom: 2px solid #e9ecef;
        }
        .header h1 {
            margin: 0;
            color: #2d3748;
            font-size: 24px;
        }
        .header .subtitle {
            color: #718096;
            font-size: 14px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            padding: 20px 0;
        }
        .stat-card {
            background: #f7fafc;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-card .label {
            font-size: 12px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .value {
            font-size: 22px;
            font-weight: bold;
            margin-top: 5px;
        }
        .stat-card .value.positive { color: #48bb78; }
        .stat-card .value.negative { color: #fc8181; }
        .stat-card .value.neutral { color: #2d3748; }
        .section {
            padding: 15px 0;
            border-top: 1px solid #e9ecef;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 10px;
        }
        .location-item, .user-item, .method-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f7fafc;
        }
        .location-item .name, .user-item .name, .method-item .name {
            color: #2d3748;
        }
        .location-item .amount, .user-item .amount, .method-item .amount {
            font-weight: 600;
        }
        .amount.positive { color: #48bb78; }
        .amount.negative { color: #fc8181; }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 2px solid #e9ecef;
            color: #a0aec0;
            font-size: 12px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success { background: #c6f6d5; color: #22543d; }
        .badge-danger { background: #fed7d7; color: #9b2c2c; }
        .badge-warning { background: #fefcbf; color: #744210; }
        .emoji { font-size: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📊 {{ $periodLabel }} Report</h1>
            <p class="subtitle">{{ $user->name }} · {{ date('M d, Y', strtotime($reportData['start_date'])) }} - {{ date('M d, Y', strtotime($reportData['end_date'])) }}</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">💰 Total Deposits</div>
                <div class="value positive">{{ $reportData['currency_symbol'] }}{{ number_format($reportData['total_deposits'], 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">💸 Total Withdrawals</div>
                <div class="value negative">{{ $reportData['currency_symbol'] }}{{ number_format($reportData['total_withdrawals'], 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">{{ $reportData['net_profit'] >= 0 ? '📈' : '📉' }} Net {{ $reportData['net_profit'] >= 0 ? 'Profit' : 'Loss' }}</div>
                <div class="value {{ $reportData['net_profit'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $reportData['currency_symbol'] }}{{ number_format(abs($reportData['net_profit']), 2) }}
                </div>
            </div>
            <div class="stat-card">
                <div class="label">📊 Profit Margin</div>
                <div class="value neutral">{{ number_format($reportData['profit_percentage'], 1) }}%</div>
            </div>
        </div>

        <!-- Additional Stats -->
        <div class="section">
            <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                <span>🔄 Total Transactions</span>
                <span><strong>{{ number_format($reportData['total_transactions']) }}</strong></span>
            </div>
            <div style="display: flex; justify-content: space-between; padding: 5px 0;">
                <span>📅 Period</span>
                <span><strong>{{ $periodLabel }}</strong></span>
            </div>
        </div>

        <!-- Location Breakdown -->
        @if(!empty($reportData['location_breakdown']))
        <div class="section">
            <div class="section-title">📍 Location Breakdown</div>
            @foreach($reportData['location_breakdown'] as $location)
            <div class="location-item">
                <span class="name">{{ $location['location_name'] }}</span>
                <span class="amount {{ $location['net'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $reportData['currency_symbol'] }}{{ number_format($location['net'], 2) }}
                    <span style="font-size: 12px; color: #a0aec0;">
                        ({{ number_format($location['transaction_count']) }} txns)
                    </span>
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Top Users -->
        @if(!empty($reportData['top_users']))
        <div class="section">
            <div class="section-title">🏆 Top Performers</div>
            @foreach($reportData['top_users'] as $index => $userData)
            <div class="user-item">
                <span class="name">
                    @if($index === 0) 🥇 @elseif($index === 1) 🥈 @elseif($index === 2) 🥉 @else 👤 @endif
                    {{ $userData['user_name'] }}
                </span>
                <span class="amount {{ $userData['net'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $reportData['currency_symbol'] }}{{ number_format($userData['net'], 2) }}
                    <span style="font-size: 12px; color: #a0aec0;">
                        ({{ number_format($userData['transaction_count']) }} txns)
                    </span>
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Payment Method Breakdown -->
        @if(!empty($reportData['method_breakdown']))
        <div class="section">
            <div class="section-title">💳 Payment Methods</div>
            @foreach($reportData['method_breakdown'] as $method)
            <div class="method-item">
                <span class="name">{{ $method['method_name'] }}</span>
                <span class="amount {{ $method['net'] >= 0 ? 'positive' : 'negative' }}">
                    {{ $reportData['currency_symbol'] }}{{ number_format($method['net'], 2) }}
                    <span style="font-size: 12px; color: #a0aec0;">
                        ({{ number_format($method['transaction_count']) }} txns)
                    </span>
                </span>
            </div>
            @endforeach
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>📅 Generated: {{ now()->setTimezone('Africa/Nairobi')->format('M d, Y H:i') }} EAT</p>
            <p style="margin-top: 5px;">This is an automated report. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>