var next_click = document.querySelectorAll(".next_button");
var main_form = document.querySelectorAll(".main");
var step_lists = document.querySelectorAll(".wz-progress-bar li");
var nums = document.querySelectorAll(".step-number"); // Use querySelectorAll to select all elements with the class

var stepName = document.getElementById("step-name");
let formnumber = 0;
next_click.forEach(function (next_click_form) {
    next_click_form.addEventListener('click', function () {
        if (!validateform()) {
            return false;
        }
        formnumber++;
        updateform();
        progress_forward();
        contentchange();
    });
});

var back_click = document.querySelectorAll(".back_button");
back_click.forEach(function (back_click_form) {
    back_click_form.addEventListener('click', function () {
        if(formnumber==3){
            formnumber--;
            updateform();
            progress_backward();
        }
        formnumber--;
        updateform();
        progress_backward();
    });
});
var next_step_click = document.querySelectorAll(".next_step_button");
var reload_click = document.querySelectorAll(".reload_button");

var errorCase = document.getElementById('error-case');
var showCase = document.getElementById('show-case');

reload_click.forEach(function (reload_click_click_form) {
    reload_click_click_form.addEventListener('click', function () {
        errorCase.style.display = 'none';
        showCase.style.display = 'block';
        livnessCheck();
    });});

next_step_click.forEach(function (next_step_click_form) {
    next_step_click_form.addEventListener('click', function () {
        // Reset previous error messages
        $('.error-input').text('');

        // Check for required fields only inside the active main div
        var isValid = true;
        $('.main.active input[required], .main.active select[required], .main.active input[type="radio"][required]').each(function () {
            if ((!$(this).val() && !$(this).is(':checked')) || ($(this).is(':radio') && !$(':radio[name="' + $(this).attr('name') + '"]:checked').length)) {
                isValid = false;
                // Set error message for the corresponding field
                var errorMessage =  'This field is required.';
                $(this).closest('.form-group').find('.error-input').text(errorMessage);
            }
            
        });
        // Validate director name and email
        $('.main.active .director-name').each(function () {
            if (!$(this).val().trim()) {
                isValid = false;
                var errorMessage = 'This field is required.';
                $(this).closest('.form-group').find('.error-input').text(errorMessage);
            }
        });

        $('.main.active .director-email').each(function () {
            if (!$(this).val().trim()) {
                isValid = false;
                var errorMessage = 'This field is required.';
                $(this).closest('.form-group').find('.error-input').text(errorMessage);
            } else if (!validateEmail($(this).val().trim())) {
                isValid = false;
                var errorMessage = 'Please provide a valid email.';
                $(this).closest('.form-group').find('.error-input').text(errorMessage);
            }
        });

        if (formnumber == 3 && $('.main.active input[name="currency[]"]:checked').length == 0) {
            isValid = false;
            // At least one checkbox is checked if not, the user can't proceed to the next step
            $('.main.active .error-input').text('Please select at least one currency.');
        }
        // If all required fields are filled, proceed to the next step
        if (isValid) {

            formnumber++;
            updateform();
            progress_forward();

            if(formnumber==2){

            //loading the liveness iframe
            const photoUrlElement = document.getElementById('photo-url');

            //check if the user already made the liveness check
            if (photoUrlElement && photoUrlElement.value.trim() === '') {

                livnessCheck();

            }else{
                formnumber++;
                updateform();
                progress_forward();
            }
            }

        }
    });
});

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(String(email).toLowerCase());
}
var username = document.querySelector("#user_name");
var shownname = document.querySelector(".shown_name");


var heart = document.querySelector(".fa-heart");
heart.addEventListener('click', function () {
    heart.classList.toggle('heart');
});

var share = document.querySelector(".fa-share-alt");
share.addEventListener('click', function () {
    share.classList.toggle('share');
});

function updateform() {
    main_form.forEach(function (mainform_number) {
        mainform_number.classList.remove('active');
    });
    main_form[formnumber].classList.add('active');
}

function progress_forward() {
    nums.forEach(function (num) {
        num.innerHTML = formnumber + 1;
    });
   const activeListItem= document.querySelector('.wz-progress-bar li.active');

    step_lists[formnumber].classList.add('active');

    stepName.innerHTML =    step_lists[formnumber].innerText;


}

function progress_backward() {
    var form_num = formnumber + 1;
    step_lists[form_num].classList.remove('active');
    nums.forEach(function (num) {
        num.innerHTML = form_num;
    });
    const activeListItem= document.querySelector('.wz-progress-bar li.active');
    stepName.innerHTML =step_lists[formnumber].innerText;
}

var step_num_contents = document.querySelectorAll(".step-number-content");

function contentchange() {
    step_num_contents.forEach(function (content) {
        content.classList.remove('active');
        content.classList.add('d-none');
    });
    step_num_contents[formnumber].classList.add('active');
}

function validateform() {
    validate = true;
    var validate_inputs = document.querySelectorAll(".main.active input");
    validate_inputs.forEach(function (vaildate_input) {
        vaildate_input.classList.remove('warning');
        if (vaildate_input.hasAttribute('require')) {
            if (vaildate_input.value.length == 0) {
                validate = false;
                vaildate_input.classList.add('warning');
            }
        }
    });
    return validate;
}

 function livnessCheck(){

    // Create a webview container
    const webviewContainer = document.getElementById('webview-container');

    // Check if there is an existing iframe, and remove it if it exists
    const existingIframe = webviewContainer.querySelector('iframe');

    if (existingIframe) {
    webviewContainer.removeChild(existingIframe);
    }
    // Create a new iframe element
    const iframe = document.createElement('iframe');
    var base_url = webviewContainer.getAttribute('data-base-url');
    iframe.src = liveness_base_url + "?platform=web&baseUrl="+base_url;
    iframe.style = "width: 100%; height: 90vh; border: none;";
    iframe.setAttribute('allow', 'camera');
    // Append the iframe to the webview container
    webviewContainer.appendChild(iframe);

    // Set up event listeners and handle WebView events
    iframe.addEventListener('load', function() {
        // Handle page load finished
        window.addEventListener('message', function(e) {
            if (e.origin === liveness_base_url) {
                try {
                    if (e.data.liveness_status) {
                        document.getElementById('photo-url').value = e.data.selfie_image;
                        formnumber=3;
                        updateform();
                        progress_forward();
                    } else {
                       // iframe.src = iframe.src;
                       errorCase.style.display = 'block';
                       showCase.style.display = 'none';
                       iframe.removeEventListener('load', handleIFrameLoad);

                    }



                } catch (error) {
                    console.error("Error :", error);
                }
            }
        }, false);


});
}

