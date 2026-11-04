<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Beneficiary Rejected</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
    </style>
</head>
<body>
<h4>Dear {{ $ben->user->name }},</h4>
<p>The following beneficiary has been rejected.</p>
<table border="1" cellpadding="5" cellspacing="0">
    <tr>
        <td>Name</td>
        <td>Account Number</td>
        <td>IBAN</td>
    </tr>
    <tr>
        <td>{{ $ben->name }}</td>
        <td>{{ $ben->account_number }}</td>
        <td>{{ $ben->iban }}</td>
    </tr>
</table>
<h3>Reason:</h3>
<p>{{ $ben->reject_reason }}</p>

@include("emails.system.partial.email-footer")
</body>
</html>
