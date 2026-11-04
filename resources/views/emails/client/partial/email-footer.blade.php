<p>Thanks & best regards.</p>
<p>{{ $ownCompany ? $ownCompany->poc_name : '' }}</p>
<p>
    <strong>{{ $ownCompany ? $ownCompany->name : '' }}</strong>
    <br><strong>{{ $ownCompany ? $ownCompany->country : '' }}</strong>
    <br>Email: <a
        href="mailto:{{ $ownCompany ? $ownCompany->poc_email : '' }}">{{ $ownCompany ? $ownCompany->poc_email : '' }}</a>
</p>

@include('emails.partials.email-footer-disclaimer')
