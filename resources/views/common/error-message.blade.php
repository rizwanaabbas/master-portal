<div class="row">
    <div class="col-md-12">
        @if (session()->has('success'))
            <div class="alert bg-success-400 text-white">
                <i class="fal fa-check mr-2"></i> {{ session('success') }}
                <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
        @endif
        @if (session()->has('warning'))
            <div class="alert bg-warning-400 text-white">
                <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
                @foreach ((array) session('warning') as $warning)
                    <i class="fal fa-exclamation-triangle mr-2"></i> {{ $warning }} <br>
                @endforeach
            </div>
        @endif
        @if (session()->has('info'))
            <div class="alert bg-info-400 text-white">
                <i class="fal fa-check mr-2"></i> {{ session('info') }}
                <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="alert alert-danger">
                <i class="fal fa-exclamation-triangle"></i> {{ session('error') }}
                <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                        aria-hidden="true">×</span></button>
            </div>
        @endif

        @if (count($errors->all()) > 1)
            <div class="alert alert-danger">
                <p><i class="fal fa-exclamation-triangle"></i> There are multiple errors, please fix and submit again.
                </p>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                    <ul>
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                                aria-hidden="true">×</span></button>
            </div>
        @else
            @foreach ($errors->all() as $error)
                <div class="alert alert-danger">
                    <i class="fal fa-exclamation-triangle"></i> {{ $error }}
                    <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span
                            aria-hidden="true">×</span></button>
                </div>
            @endforeach
        @endif
    </div>
</div>
