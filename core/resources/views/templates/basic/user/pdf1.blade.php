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
        .receipt-title1 { font-size: 18px; font-weight: bold;}
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
        <div class="company-name">Provisional Certificate of Allocation</div>
        <div class="receipt-title1">Of Fractional Farmland</div>
        <div class="receipt-title1">(For full payment subscribers, pending Registered Deed)</div>
    </div>
    <div class="customer-info">
        <h3>This is to certify that:</h3>
        <p><strong>Subscriber Name:</strong> {{ $user->fullname }}</p>
        <p><strong>Username:</strong> {{ $user->username }}</p>
        <p><strong>Unit(s) Subscribed:</strong> {{ $rinvest->units }}</p> 
    </div>
    <div class="payment-modes">
        <div class="checkbox">has made full payment for the above subscription under the 10,000 Hectare<br/>
         WiiFarm Land Development Master Plan in Akwa Ibom State, and is hereby allocated the corresponding fractional farmland interest.
         <br/>
         The subscriber is entitled to:
         <br/>  
         <ol>
            <li>Provisional recognition as co-owner of farmland units under the WiiFarm Cooperative global Registered Deed of Sublease</li>
            <li>Rights to proportional returns from proceeds of agricultural output (through farm management contracts).</li>
            <li>
                Issuance of a Substantive Certificate of Allocation drawn from WiiFarm Cooperative Society’s global Registered Deed of Sublease upon completion of processing with the State Department of Lands & Survey.
            </li>
            <li>
                This certificate is provisional and shall be replaced by a Substantive Certificate of Allocation drawn from WiiFarm Cooperative Society's Registered Deed of Sublease once duly perfected.
            </li>
        </ol>
        </div>
    </div>
    <br/>  
    <p class="header">
        Signed: ________________________ Seal & Date: ________________________ 
        <br/>
        (AuthorizedSignatory – WiiFarmCooperativeSociety)
    </p>
</body>
</html>
