<!doctype html>
<html>
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>New Beneficiary</title>
    <style>
        body{
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
        }
    </style>
</head>
<body>
<h4>Dear {{ $manager->name }},</h4>
<p>New Beneficiary account has been added by {{ $ben->user->name }}, please review and approve accordingly.</p>
<br>
<a href="{{ url("/client-management/clients/beneficiaries/view/".$ben->id) }}" style="margin-top:5px; margin-bottom:5px; padding: 5px; background-color: #ffc800; text-decoration: none; color: black; border: 1px solid orange"><strong>View Beneficiary</strong></a>
<br>
@include("emails.system.partial.email-footer")
</body>
</html>
