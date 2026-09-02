<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #181c32;
            background: #fff;
        }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    @include('orders.order.receipt-content', ['order' => $order])
</body>
</html>