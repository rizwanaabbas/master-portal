@extends("layouts.cp")
@section('title', 'Reset Data - Data Management')

@section("content")

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Data Management</span>
        </h1>
    </div>

    <div  class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Reset Data
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include("common.error-message")
                <div class="row">
                    <div class="col-md-6 p-4">
                        <h3>Delete Transactions</h3>
                        <p class="alert alert-primary">
                            Delete all transactions data, this action will remove all the debit / credit transactions done in the system and can't be rolledback, please make sure you are OK with it.
                            <br><a href="{{ url("/reset/transactions") }}" class="btn btn-danger mt-2">Yes Delete it</a>
                        </p>
                    </div>
                    <div class="col-md-6 p-4">
                        <h3>Delete Clients</h3>
                        <p class="alert alert-primary">Delete all Clients, this action will remove all clients from the system including, accounts, files and client user, again this action can't be rolled back, make sure you want to do it.
                            <br><a href="{{ url("/reset/clients") }}" class="btn btn-danger mt-2">Yes Delete it</a>
                        </p>
                    </div>



                </div>
            </div>

        </div>
    </div>

@stop
