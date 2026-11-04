<div class="main ">
    <div id="loading-spinner-form2"
        style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); height: 100px; display:none">
        <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
            style="background: none; shape-rendering: auto;" width="50px" height="50px" viewBox="0 0 100 100"
            preserveAspectRatio="xMidYMid">
            <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13" r="38"
                stroke-dasharray="179.0707812546182 61.690260418206066">
                <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="0.625s"
                    values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
            </circle>
        </svg>
    </div>
    <div>
        Upload Signature :
    </div>
    <br>
    <div>
        <input type="file" name="uploadedSignature" id="uploadedSignature-id" accept="image/*">
    </div>
    <br>
    <div>Or</div>
    <br>
    <div>Draw Signature</div>
    <br>
    <canvas id="signatureCanvas" style="border: 0.5px solid #000;
        display: block;"></canvas>
    <input type="hidden" name="signature" id="signatureInput">
    @php
        $clientID = $client->individual->id ?? $client->company->id;
    @endphp
    <input type="hidden" name="client_id" value="{{ $clientID }}">
    <div class="row mt-4">
        <div class="col-md-5 mt-2">
            <button type="button" onclick="clearSignature()" class="btn btn-primary">Clear
                Signature</button>
        </div>

    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <hr>
                <button type="button" class="btn btn-outline-secondary back_button"><i class="fal fa-arrow-left"></i>
                    Back</button>
                <button type="submit" onclick="saveSignature()" class="btn btn-primary">Next <i
                        class="fal fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
<script>
    // Initialize Signature Pad
    var canvas = document.getElementById('signatureCanvas');
    var signaturePad = new SignaturePad(canvas, {
        penColor: '#ffc800',
        minWidth: 1, // Set the minimum stroke width
        maxWidth: 1
    });
    // Function to clear the signature
    function clearSignature() {
        signaturePad.clear();
    }
    // Function to save the signature and check for requierd fields
    function saveSignature() {

        var signatureDataIsEmpty = signaturePad.isEmpty();

        var fileAtribute = document.getElementById('uploadedSignature-id');

        if (!signatureDataIsEmpty) {

            fileAtribute.removeAttribute('required');

            // Get the signature data as a base64-encoded PNG
            var signatureData = signaturePad.toDataURL();

            document.getElementById('signatureInput').value = signatureData;

        } else {

            fileAtribute.setAttribute('required', 'required');

        }
    }
    document.getElementById("form1").addEventListener("submit", function() {
        var loadingSpinner = document.getElementById("loading-spinner-form2");
        var submitButton = document.querySelector("button[type='submit']");

        // Show the loading spinner
        loadingSpinner.style.display = "block";
        submitButton.disabled = true;

    });
</script>
