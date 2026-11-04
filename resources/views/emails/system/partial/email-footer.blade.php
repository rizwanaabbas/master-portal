<p style="margin-top: 20px;">
    <strong>{{ $ownCompany ? $ownCompany->name : '' }}</strong>
    <br><strong>{{ $ownCompany ? $ownCompany->country : '' }}</strong>
</p>

@include("emails.partials.email-footer-disclaimer")