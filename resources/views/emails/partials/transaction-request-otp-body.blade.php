{{-- OTP body copy shared by request-otp and transaction-request-otp --}}
<tr>
    <td align="center">
        <h1>Authorise your transaction</h1>
    </td>
</tr>
<tr>
    <td align="left" style="text-align: left; line-height: 1.6;">
        <p>Use this one-time code to confirm your {{ strtolower($data['message_needle'] ?? 'transaction') }}. Do not share this code with anyone, including {{ $ownCompany ? $ownCompany->name : 'us' }} staff.</p>
    </td>
</tr>
<tr>
    <td align="center">
        <p style="margin: 0 0 8px; font-size: 13px; color: #555;">Verification code</p>
        <h1 class="otp gray-highlighting">{{ $data['otp'] }}</h1>
    </td>
</tr>
<tr>
    <td align="left" style="text-align: left; line-height: 1.5; font-size: 12px; color: #555;">
        <p>This code expires shortly. If you did not initiate this request, ignore this email and contact us immediately.</p>
    </td>
</tr>
