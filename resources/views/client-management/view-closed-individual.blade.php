@extends('layouts.cp')
@section('title', 'Closed Client - Client Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
        <div class="d-flex">
            @php $user = \Illuminate\Support\Facades\Auth::user(); @endphp
            @can('approve client')
                @if ($user->id != $client->created_by || !$user->manager_id > 0)
                    @if ($client->status == 'Closed')
                        <button onclick="restoreClient(); return false;" class="btn btn-danger ml-2 mx-md-2">
                            Restore Client
                        </button>
                    @endif
                @endif
            @endcan
            <a href="{{ url('/client-management/closed-clients') }}" class="btn btn-primary mr-3"><i
                    class="fal fa-arrow-alt-left"></i> Go Back </a>
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Closed Client
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        {{-- Client --}}
                        <tr>
                            <th colspan="2">
                                <h3>{{ ucfirst($client->type) }} Details</h3>
                            </th>
                        </tr>
                        <tr>
                            <th width="200">Name</th>
                            <td>
                                {{ $client->name }}
                            </td>
                        </tr>
                        <tr>
                            <th>Date of Birth</th>
                            <td>{{ $client->individual->dob ? \Carbon\Carbon::parse($client->individual->dob)->format('d/m/Y') : '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Nationality</th>
                            <td>{{ $client->individual->nationality->name }}</td>
                        </tr>
                        <tr>
                            <th>Mailing Address</th>
                            <td>{{ $client->individual->mailing_address }}</td>
                        </tr>

                        <tr>
                            <th>Gender</th>
                            <td>{{ $client->individual->gender }}</td>
                        </tr>
                        <tr>
                            <th>Mobile Number</th>
                            <td>{{ $client->individual->mobile_number }}</td>
                        </tr>
                        <tr>
                            <th>Email Address</th>
                            <td>{{ $client->individual->email_address }}</td>
                        </tr>
                        <tr>
                            <th>Passport Number</th>
                            <td>{{ $client->individual->passport_number }}</td>
                        </tr>
                        <tr>
                            <th>Passport Expiry Date</th>
                            <td>{{ $client->individual->passport_expiry_date ? \Carbon\Carbon::parse($client->individual->passport_expiry_date)->format('d/m/Y') : '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge {{ $statusBadge[$client->status] }}">{{ $client->status }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Closed By</th>
                            <td>{{ $client->closer->name }}</td>
                        </tr>
                        <tr>
                            <th>Closed At</th>
                            <td>{{ $client->closed_at }}</td>
                        </tr>
                        <tr>
                            <th>Closing Reason</th>
                            <td>{{ $client->close_reason }}</td>
                        </tr>

                        {{-- Employer --}}
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <h3>Employer Details</h3>
                            </th>
                        </tr>
                        <tr>
                            <th>Occupation / Business</th>
                            <td>{{ $client->individual->employer ? $client->individual->employer->occupation_business : '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Applicant's Employer Business Name</th>
                            <td>{{ $client->individual->employer ? $client->individual->employer->employer_business_name : '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Employer Address</th>
                            <td>{{ $client->individual->employer ? $client->individual->employer->employer_address : '' }}
                            </td>
                        </tr>
                        <tr>
                            <th>Job Title</th>
                            <td>{{ $client->individual->employer ? $client->individual->employer->job_title : '' }}</td>
                        </tr>
                        <tr>
                            <th>Applicant's Estimated Gross Annual Income (USD)</th>
                            <td>{{ $client->individual->employer ? $client->individual->employer->applicant_annual_income_usd : '' }}
                            </td>
                        </tr>

                        {{-- Accounts --}}
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <h3>Client Accounts' Details</h3>
                            </th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                @if ($client->individual->accounts->count() > 0)
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="30">S.No</th>
                                                <th>Account title</th>
                                                <th>Account Number</th>
                                                <th>Currency</th>
                                                <th>Is Active?</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($client->individual->accounts as $account)
                                                @if (!in_array('Crypto', $enabledModules) && $account->currency->is_crypto)
                                                    @php continue; @endphp
                                                @endif
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $account->title }}</td>
                                                    <td>{{ $account->account_number }}</td>
                                                    <td>
                                                        @if ($account->currency)
                                                            @if ($account->currency->logo)
                                                                <img src="{{ $account->currency->logo }}"
                                                                    alt="{{ $account->currency->name }}"
                                                                    height="17px" />&nbsp;
                                                            @endif
                                                            {{ $account->currency->name . ' (' . $account->currency->short . ')' }}
                                                        @else
                                                            {{ $account->id }}
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($account->is_active)
                                                            <span class="badge badge-success">Yes</span>
                                                        @else
                                                            <span class="badge badge-danger">No</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @else
                                    No Accounts found.
                                @endif
                            </td>
                        </tr>

                        {{-- Sub Accounts --}}
                        @if ($client->children->count() > 0)
                            <tr>
                                <td colspan="2">&nbsp;</td>
                            </tr>
                            <tr>
                                <th colspan="2">
                                    <h3>Sub Accounts</h3>
                                </th>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="30">S.No</th>
                                                <th>Client Name</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Created At</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($client->children as $child)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <a
                                                            href="{{ url('/client-management/clients/individual/edit/' . $child->id) }}">
                                                            {{ $child->name }}
                                                        </a>
                                                    </td>
                                                    <td>{{ ucfirst($child->type) }}</td>
                                                    <td>
                                                        <span class="badge {{ $statusBadge[$child->status] }}">
                                                            {{ $child->status }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $child->created_at ? \Carbon\Carbon::parse($child->created_at)->format('d/m/Y') : '' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif

                        {{-- Files --}}
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                        <tr>
                            <th colspan="2">
                                <h3>Related Files</h3>
                            </th>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <table class="table table-striped table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="30">S.No</th>
                                            <th>Required Documents</th>
                                            <th>Upload</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $counter = 0 @endphp

                                        @foreach ($fileTypes as $file)
                                            @php $existingFiles = $client->individual->files->where("file_id", $file->id) @endphp

                                            @if ($existingFiles->count() > 0)
                                                @php $counter++ @endphp
                                                <tr>
                                                    <td>{{ $counter }}</td>
                                                    <td>{{ $file->title }}</td>

                                                    <td>
                                                        @foreach ($existingFiles as $efile)
                                                            <div class="row p-2">
                                                                <div class="col-md-8"><a href="{{ $efile->url }}"
                                                                        target="_blank"><i class="fal fa-file-alt"></i>
                                                                        View</a>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    <div class="modal fade" id="restoreClient" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Approve Client Profile</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/client-management/clients/restore') }}" method="post">
                        @csrf
                        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Restore</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
@stop
@section('scripts')
    <script>
        function restoreClient() {
            $('#restoreClient').modal('show');
        }
    </script>

@stop
