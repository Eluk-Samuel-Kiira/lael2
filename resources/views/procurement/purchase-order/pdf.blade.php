<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $order->po_number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 13px; color: #181c32; }
        table { border-collapse: collapse; }
    </style>
</head>
<body>
    @include('procurement.purchase-order.pdf-content', ['order' => $order])
</body>
</html>