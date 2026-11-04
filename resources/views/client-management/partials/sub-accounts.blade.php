<div class="tab-pane fade" id="sub-accounts" role="tabpanel" aria-labelledby="sub-accounts">
    <form method="post" action="{{ url('/client-management/clients/link-client') }}" name="link-client"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->id }}">
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <select name="new_client_id" id="new_client_id" data-select2-id="new_client_id"
                        class="form-control select2 w-100" required>
                        <option value="">Choose Client</option>
                        @foreach ($clients as $newClient)
                            <option value="{{ $newClient->id }}">{{ $newClient->name }}</option>
                        @endforeach
                    </select>
                    @error('new_client_id')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
            </div>
            <div class="col-md-2">
                <button typ="submit" class="btn btn-primary">Add Existing</button>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-end">
                    <a href="{{ url('/client-management/clients/new/company/0/' . $client->id) }}"
                        class="btn btn-primary btn-shape ml-2"><i class="fal fa-plus-circle"></i> Add
                        Company Sub-Account </a>
                    <a href="{{ url('/client-management/clients/new/individual/' . $client->id) }}"
                        class="btn btn-primary btn-shape ml-2"><i class="fal fa-plus-circle"></i> Add
                        Individual Sub-Account </a>
                </div>
            </div>
        </div>
    </form>
    <hr />
    <h4>Sub Accounts</h4>
    <div class="row">
        <div class="col-md-12">
            <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                <thead class="thead-themed">
                    <tr>
                        <th width="30">S.No</th>
                        <th>Client Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th width="30" align="center">Remove</th>
                    </tr>
                </thead>
                <tbody>
                    @if (count($clientLinks) > 0)
                        @foreach ($clientLinks as $clientLink)
                            <tr>
                                <td align="center" scope="row">
                                    <a
                                        href="{{ url('/client-management/clients/' . $clientLink->type . '/edit/' . $clientLink->id) }}">
                                        <i class="fal fa-edit"></i>
                                    </a>
                                </td>
                                <td>{{ $clientLink->name }}</td>
                                <td>{{ $clientLink->type }}</td>
                                <td>
                                    <span class="badge {{ $statusBadge[$clientLink->status] }}">
                                        {{ $clientLink->status }}
                                    </span>
                                </td>
                                <td nowrap="nowrap">
                                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['operations manager', 'super admin']))
                                        @can('approve account')
                                            <div class="d-flex justify-content-center w-100">
                                                <a href="javascript:unlinkClient('{{ $clientLink->id }}')"
                                                    title="Remove Sub-Account"><i
                                                        class="alert-danger fal fa-times-circle rounded-circle mx-auto"></i></a>
                                            </div>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="5" align="center">No records found.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<form method="POST" name="unlink-client" id="unlink_client"
    action="{{ url('/client-management/clients/unlink-client') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
    <input type="hidden" name="linked_client_id" id="linked_client_id" value="">
</form>

<script>
    function unlinkClient(id) {
        document.getElementById('linked_client_id').value = id;

        Swal.fire({
            title: "Are you sure?",
            text: "This Sub-Account will be unlinked from this Client.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Unlink it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('unlink_client').submit()
            }
        });
    }
</script>
