<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payment Receipt</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 30px; }
        .logo { text-align: center; margin-bottom: 20px; }
        .logo img { max-height: 80px; width: auto; }
        .company-name { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
        .receipt-title { font-size: 18px; font-weight: bold; text-decoration: underline; }
        .receipt-info { display: flex; justify-content: space-between; margin: 20px 0; }
        .customer-info { margin: 20px 0; }
        .payment-details { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        .payment-modes { margin: 20px 0; }
        .checkbox { display: inline-block; margin-right: 20px; }
        .signature-section { margin-top: 40px; display: flex; justify-content: space-between; }
        .signature-box { width: 200px; text-align: center; }
        .signature-line { border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">
            <img src="data:image/png;base64,{{ $image }}" alt="Logo">
                            
        </div>

        <div class="company-name">FRACTIONAL FARMLAND OWNERSHIP</div>
        <div class="receipt-title">PAYMENT RECEIPT</div>
    </div>

    <div class="receipt-info">
        <div><strong>Receipt No:</strong> {{ $receipt_no }}</div>
        <div><strong>Date:</strong> {{ date('d/m/Y', strtotime($rinvest->created_at)) }}</div>
    </div>

    <div class="customer-info">
        <h3>Customer Information:</h3>
        <p><strong>Name:</strong> {{ $user->fullname }}</p>
        <p><strong>Address:</strong> {{ $user->address }}</p>
        <p><strong>Phone:</strong> {{ $user->dial_code }}{{ $user->mobile }}</p>
        <p><strong>Email:</strong> {{ $user->email }}</p>
    </div>

    <div class="payment-details">
        <h3>Payment Description:</h3>
        <table>
            <tr>
                <th>Unit Cost</th>
                <th>Number of Units</th>
                
               
                <th>Total Amount</th>
            </tr>
            <tr>
                <td>{{ $rinvest->unit_cost }} </td>
                <td>{{ $rinvest->units }}</td>
                <td> NGN{{ showAmount(($rinvest->unit_cost *  $rinvest->units), 2, true, false, false) }}</td>
            </tr>
        </table>
    </div>

    <div class="payment-modes">
        <h3>Mode of Payment:</h3>
       
        <div class="checkbox"> Online: Money box Wallet </div>
    </div>
</body>
</html>
