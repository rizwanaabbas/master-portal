<html>

<head>
    <title>Client Profiles Report</title>
</head>

<body>
    <table>
        <tr>
            <td width="10"><b>S.NO</b></td>
            <td width="30"><b>Name</b></td>
            <td width="15"><b>Type</b></td>
            <td width="15"><b>Member Since</b></td>
            <td width="15"><b>Date of Birth/Incorporation</b></td>
            <td width="30"><b>Nationality/Incorporation Place</b></td>
            <td width="40"><b>Address</b></td>
            <td width="15"><b>Passport/License Number</b></td>
            <td width="15"><b>Passport/License Expiry</b></td>
            <td width="30"><b>Contact Person Name</b></td>
            <td width="18"><b>Contact Number</b></td>
            <td width="30"><b>Email Address</b></td>
            <td width="30"><b>Occupation/POC Designation</b></td>
            <td width="30"><b>Job Title</b></td>
            <td width="30"><b>Employer Business Name</b></td>
            <td width="40"><b>Employer Address</b></td>
            <td width="10"><b>Status</b></td>
            <td width="180"><b>Accounts</b></td>
        </tr>
        @if ($clients)
            @foreach ($clients as $client)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $client->name }}</td>
                    <td>{{ $client->type }}</td>
                    <td>{{ \Carbon\Carbon::parse($client->created_at)->format('d M, Y') }}
                    </td>

                    @php
                        if ($client->type == 'company') {
                            $date_of_birth = $client->company ? $client->company->registration_date : null;
                            $nationality = $client->company ? $client->company->company_place : '--';
                            $address = $client->company ? $client->company->business_reg_address : '--';
                            $cred_number = $client->company ? $client->company->business_reg_number : '--';
                            $cred_expiry = $client->company ? $client->company->expiry_date : null;
                            $contact_person_name = $client->company && $client->company->contact ? $client->company->contact->name : '--';
                            $contact_number = $client->company && $client->company->contact ? $client->company->contact->mobile_number : '--';
                            $email_address = $client->company && $client->company->contact ? $client->company->contact->email_address : '--';
                            $occupation = $client->company && $client->company->contact ? $client->company->contact->designation : '--';
                            $job_title = 'N/A';
                            $employer_business_name = 'N/A';
                            $employer_address = 'N/A';
                        } else {
                            $date_of_birth = $client->individual ? $client->individual->dob : null;
                            $nationality = $client->individual && $client->individual->nationality ? $client->individual->nationality->name : '--';
                            $address = $client->individual ? $client->individual->mailing_address : '--';
                            $cred_number = $client->individual ? $client->individual->passport_number : '--';
                            $cred_expiry = $client->individual ? $client->individual->passport_expiry_date : null;
                            $contact_person_name = 'N/A';
                            $contact_number = $client->individual ? $client->individual->mobile_number : '--';
                            $email_address = $client->individual ? $client->individual->email_address : '--';
                            $occupation = $client->individual && $client->individual->employer ? $client->individual->employer->occupation_business : '--';
                            $job_title = $client->individual && $client->individual->employer ? $client->individual->employer->job_title : '--';
                            $employer_business_name = $client->individual && $client->individual->employer ? $client->individual->employer->employer_business_name : '--';
                            $employer_address = $client->individual && $client->individual->employer ? $client->individual->employer->employer_address : '--';
                        }
                    @endphp

                    <td>
                        {{ $date_of_birth ? \Carbon\Carbon::parse($date_of_birth)->format('d M, Y') : '--' }}
                    </td>
                    <td>{{ $nationality }}</td>
                    <td>{{ $address }}</td>
                    <td>{{ $cred_number }}</td>
                    <td>
                        {{ $cred_expiry ? \Carbon\Carbon::parse($cred_expiry)->format('d M, Y') : '--' }}
                    </td>
                    <td>{{ $contact_person_name }}</td>
                    <td>{{ $contact_number }}</td>
                    <td>{{ $email_address }}</td>
                    <td>{{ $occupation }}</td>
                    <td>{{ $job_title }}</td>
                    <td>{{ $employer_business_name }}</td>
                    <td>{{ $employer_address }}</td>
                    <td>{{ $client->status }}</td>

                    @php
                        $accounts = null;
                        if ($client->company) {
                            $accounts = $client->company->accounts;
                        } elseif ($client->individual) {
                            $accounts = $client->individual->accounts;
                        }
                    @endphp

                    <td>
                        @if ($accounts)
                            @foreach ($accounts as $account)
                                {{ $account->account_number . ' - ' . ($account->currency ? $account->currency->short : '') }} @if ($loop->iteration < count($accounts))
                                    |
                                @endif
                            @endforeach
                        @else
                            --
                        @endif
                    </td>
                </tr>
            @endforeach
        @else
            <tr>
                <td colspan="18" align="center">No records found.</td>
            </tr>
        @endif
    </table>
</body>

</html>
