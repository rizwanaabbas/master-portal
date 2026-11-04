{{-- @if ($isIndividual) --}}
<div class="col-md-6">
    {{-- @endif --}}
    <select id="country_code_id" class="form-control text-truncate select2" name="country_code_id" required>
        <option value="" selected>Country</option>
        @foreach ($nationalities as $nationality)
            @if ($isIndividual && $nationality->id == $client->individual->country_code_id)
                <option value="{{ $nationality->id }}" selected>
                    +({{ $nationality->phonecode }}) - {{ $nationality->name }}
                </option>
            @elseif (!$isIndividual && $client->company->contact && $nationality->id == $client->company->contact->country_code_id)
                <option value="{{ $nationality->id }}" selected>
                    +({{ $nationality->phonecode }}) - {{ $nationality->name }}
                </option>
            @else
                <option value="{{ $nationality->id }}">
                    +({{ $nationality->phonecode }}) - {{ $nationality->name }}
                </option>
            @endif
        @endforeach
    </select>
    {{-- @if ($isIndividual) --}}
</div>
{{-- @endif --}}

{{-- @if ($isIndividual) --}}
<div class="col-md-6">
    {{-- @endif --}}

    <input type="number" id="mobile_number" name="mobile_number"
        value="{{ $isIndividual ? $client->individual->mobile_number : ($client->company->contact ? $client->company->contact->mobile_number : old('mobile_number')) }}"
        placeholder="Mobile Number" class="form-control" required>
    @error('mobile_number')
        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
    @enderror

    {{-- @if ($isIndividual) --}}
</div>
{{-- @endif --}}
