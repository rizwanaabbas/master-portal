@extends('layouts.cp')
@section('title', 'Update Bank - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/banks') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go
            Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Update Bank
            </h2>
        </div>


        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general"
                            role="tab">General</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-account"
                            role="tab">Accounts</a></li>
                </ul>

                <div class="tab-content p-3">
                    <div class="tab-pane fade show active" id="tab-general" role="tabpanel" aria-labelledby="tab-general">
                        <form method="post" action="{{ url('/finance-management/banks/update') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="bank_id" value="{{ $bank->id }}">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" autofocus id="name" name="name"
                                            value="{{ $bank->name }}" class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="address">Address</label>
                                        <input type="text" id="address" name="address" value="{{ $bank->address }}"
                                            class="form-control" required>
                                        @error('address')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">

                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" @if ($bank->is_service_fee_bank) checked @endif
                                                    class="custom-control-input" id="service_fee_bank"
                                                    value="service_fee_bank" name="fee_type">
                                                <label class="custom-control-label" for="service_fee_bank">Is
                                                    {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee
                                                    Bank</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" @if ($bank->is_trn_fee_bank) checked @endif
                                                    class="custom-control-input" id="trn_fee_bank" value="trn_fee_bank"
                                                    name="fee_type">
                                                <label class="custom-control-label" for="trn_fee_bank">Is Transaction
                                                    Fee
                                                    Bank</label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mb-3">
                                                    <input type="checkbox" class="custom-control-input"
                                                        @if ($bank->is_reserve_bank) checked @endif
                                                        id="is_reserve_bank" value="1" name="is_reserve_bank">
                                                    <label class="custom-control-label" for="is_reserve_bank">Is Reserve
                                                        Bank</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <hr>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="tab-account" role="tabpanel" aria-labelledby="account-currency">
                        <div class="row">
                            <div class="col-md-10">
                                @if ($bank->accounts->count() > 0)
                                    <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                        <thead class="thead-themed">
                                            <tr>
                                                <th width="30">S.No</th>
                                                <th>Account Title</th>
                                                <th>Account Number</th>
                                                <th>Currency</th>
                                                <th>Is Active?</th>
                                                @can('delete records')
                                                    <th width="30" align="center"></th>
                                                @endcan
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($bank->accounts as $account)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $account->title }}</td>
                                                    <td>{{ $account->account_number }}</td>
                                                    <td>{{ $account->currency->name . ' (' . $account->currency->short . ')' }}
                                                    </td>
                                                    <td>
                                                        @if ($account->is_active)
                                                            <span class="badge badge-success">Yes</span>
                                                        @else
                                                            <span class="badge badge-danger">No</span>
                                                        @endif
                                                    </td>
                                                    @can('delete records')
                                                        <td nowrap="nowrap">
                                                            <a href="javascript:deleteAccount({{ $account->id }})"><i
                                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a>

                                                        </td>
                                                    @endcan
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                @endif
                            </div>
                        </div>

                        <form method="post" action="{{ url('/finance-management/banks/account/store') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="bank_id" value="{{ $bank->id }}">
                            <div class="row">
                                <div class="col-md-12">
                                    <h5>Add New Account</h5>
                                    <hr>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="title">Account Title</label>
                                        <input type="text" autofocus id="title" name="title"
                                            value="{{ old('title') }}" class="form-control" required>
                                        @error('title')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Account Number</label>
                                        <input type="text" autofocus id="account_number" name="account_number"
                                            value="{{ old('account_number') }}" class="form-control" required>
                                        @error('account_number')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account_currency">Currency</label>
                                        <select name="account_currency" id="account_currency" class="form-control"
                                            required>
                                            <option value="">choose currency</option>
                                            @foreach ($currencies as $currency)
                                                <option value="{{ $currency->id }}">
                                                    {{ $currency->name . ' (' . $currency->short . ')' }}</option>
                                            @endforeach

                                        </select>
                                        @error('manager_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>


                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <hr>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <form method="POST" name="delete-account" id="delete-account"
        action="{{ url('/finance-management/banks/account/delete') }}">
        @csrf
        <input type="hidden" name="bank_id" id="bank_id" value="{{ $bank->id }}">
        <input type="hidden" name="account_id" id="account_id" value="">
    </form>
    <script>
        function deleteAccount(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "Account will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#account_id").val(id);
                    document.getElementById('delete-account').submit()
                }
            });
        }
    </script>
@stop
