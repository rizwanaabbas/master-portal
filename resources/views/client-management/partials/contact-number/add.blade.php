 <div class="form-group">
     <label class="form-label" for="mobile_number">Mobile Number</label>
     <div class="row">
         <div class="col-md-6">
             <select tabindex="4" id="country_code_id" class="form-control form-control-lg text-truncate select2"
                 name="country_code_id" required>
                 <option value="" selected>Country</option>
                 @foreach ($nationalities as $nationality)
                     <option value="{{ $nationality->id }}">
                         +({{ $nationality->phonecode }}) -
                         {{ $nationality->name }} </option>
                 @endforeach
             </select>
         </div>
         <div class="col-md-6">
             <input type="number" id="mobile_number" name="mobile_number" value="{{ old('mobile_number') }}"
                 class="form-control" required>
             @error('mobile_number')
                 <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
             @enderror
         </div>
     </div>
 </div>

