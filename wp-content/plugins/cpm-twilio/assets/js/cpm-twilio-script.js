let $ = jQuery;

$(document).ready(function () {

    let userID = 0;
    let nonce = 0;
    const $inputs = $('.otp-input');

    function validatePhoneNumber(phone) {
        return /^\d{10}$/.test(phone);
    }

    function validateOTP(otp) {
        return /^\d{6}$/.test(otp);
    }

    function validateIndividualOTPinput(otp) {
        return /^\d{1}$/.test(otp);
    }

    function checkIfAllOTPFieldsValid() {
        let allFilled = true;
        $inputs.each(function () {
            if ($(this).val().length === 0) {
                allFilled = false;
                return false;
            }
        });
        $('#validate_otp').prop('disabled', !allFilled);
    }

    //display form error
    function displayFormMsg(msg, type = 'fail') {
        $('.form-msg').html('');
        let icon = '';
        let pClass = '';
        if (type == 'fail') {
            pClass = 'form-err';
            icon = '<i class="fa-regular fa-circle-xmark"></i>';
        } else if (type == 'success') {
            pClass = 'form-success';
            icon = '<i class="fa-regular fa-circle-check"></i>';
        }
        $('.form-msg').html('<p class=' + pClass + '>' + icon + msg + '</p>');
    }

    //clear form error
    function clearFormMsg() {
        $('.form-msg').html('');
    }

    // Disable buttons by default
    $('#send_otp').prop('disabled', true);
    $('#validate_otp').prop('disabled', true);

    // Hide the OTP group by default
    $('.cpm_otp_group').hide();

    // validate phone number
    $(document).on('input', '#otp_phone_num', function () {
        const phoneNumber = $(this).val();
        $(this).val($(this).val().replace(/\D/g, ''));
        clearFormMsg();
        if (validatePhoneNumber(phoneNumber)) {
            $('#send_otp').prop('disabled', false);
        } else {
            $('#send_otp').prop('disabled', true);
        }
    });

    // send OTP
    $(document).on('click', '#send_otp', function () {
        clearFormMsg();

        let phoneNumber = $('#otp_phone_num').val();
        nonce = $('#phone_num_verification_nonce').val();
        $this = $(this);

        $('#otp_phone_num').prop('disabled', true); //disable input
        $this.prop('disabled', true); //disable button
        $this.html("Verifying phone number...");

        //ajax to check if the phone number belongs to an actual user
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_verify_user_phone_number",
                phone_number: phoneNumber,
                nonce: nonce,
            },
            success: function (response) {
                //console.log("111:::", response.data[1]);
                $this.prop('disabled', true);

                if (response.data[0] != 'valid_phone') {
                    if (response.data[0] == 'invalid_phone') {
                        displayFormMsg('The phone number you entered does not belong to any user');
                    } else if (response.data[0] == 'nonce_failed') {
                        displayFormMsg('Securtiy check failed');
                    }

                    $('#otp_phone_num').prop('disabled', false);
                    $this.prop('disabled', false);
                    $this.html("Send OTP");
                    return;
                }

                nonce = response.data[2];
                userID = response.data[1];
                $this.html("Sending OTP...");

                //ajax to send the otp
                jQuery.ajax({
                    url: ct_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "ct_send_twilio_otp",
                        phone_number: phoneNumber,
                        nonce: nonce,
                    },
                    success: function (response) {
                        //console.log("222:::", response);
                        $this.prop('disabled', true);
                        if (response.data[0] != 'otp_sent') {
                            if (response.data[0] == 'otp_failed') {
                                displayFormMsg(response.data[1]);
                            } else if (response.data[0] == 'nonce_failed') {
                                displayFormMsg('Securtiy check failed');
                            }

                            $this.prop('disabled', false);
                            $('#otp_phone_num').prop('disabled', false);
                            $this.html("Send OTP");
                            return;
                        }

                        nonce = response.data[2];
                        $('.cpm_phone_group').hide();
                        $('.cpm_otp_group').show();
                    },
                });

            },
        });
    });




    // validate otp button
    $(document).on('click', '#validate_otp', function () {
        clearFormMsg();
        let phoneNumber = $('#otp_phone_num').val();

        let otp = '';
        $inputs.each(function () { otp += $(this).val(); });

        $this = $(this);

        $("#twilio_otp").prop('disabled', true);
        $this.prop('disabled', true);
        $this.html("Validating...");


        if (!validateOTP(otp)) {
            displayFormMsg('OTP format not correct');
            $this.html("Validate");
            return;
        }

        ///ajax to validate otp
        jQuery.ajax({
            url: ct_ajax.ajax_url,
            type: "POST",
            data: {
                action: "ct_validate_twilio_otp",
                phone_number: phoneNumber,
                otp: otp,
                nonce: nonce
            },
            success: function (response) {
                //console.log("333:::", response);

                //check if otp invalid
                if (response.data[0] == "invalid_otp") {
                    if (response.data[0] == 'invalid_otp') {
                        displayFormMsg('OTP is invalid. Please try again');
                    } else if (response.data[0] == 'nonce_failed') {
                        displayFormMsg('Securtiy check failed');
                    }

                    $this.html("Validate");
                    $this.prop('disabled', false);
                    return;
                }

                nonce = response.data[1];
                displayFormMsg('OTP validated !! Logging you in...', 'success');
                $this.html("Logging you in...");

                //ajax to signin the user
                jQuery.ajax({
                    url: ct_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "ct_user_signin",
                        userId: userID,
                        nonce: nonce,
                    },
                    success: function (response) {
                        //console.log('444:::', response);
                        if (response.data[0] == "logged_in") {
                            window.location.href = '/my-account/detente-orders/';
                        } else if (response.data[0] == 'nonce_failed') {
                            displayFormMsg('Securtiy check failed');
                        }
                    },
                });
            },
        });
    });

    //refresh the page to restsrt the otp verification process again.
    $(document).on('click', '#otp_retry, #phone_retry', function () {
        window.location.reload();
    });


    //=====OTP input fields start=====

    // Validate individual OTP input field
    $inputs.on('input', function (e) {
        if (e.key === 'Backspace') {
            return;
        }
        clearFormMsg();
        $(this).css('border-color', '');//change border color to default

        const $this = $(this);
        if (validateIndividualOTPinput($this.val())) {
            const nextIndex = $inputs.index(this) + 1;
            if (nextIndex < $inputs.length) {
                $inputs.eq(nextIndex).focus();
            }
        } else {
            $(this).val(''); //clear the input
            displayFormMsg('OTP can only be numbers'); //show appropriate display message
            $(this).css('border-color', 'red');//change border color to red
        }

        checkIfAllOTPFieldsValid();
    });

    $inputs.on('keydown', function (event) {
        const $this = $(this);
        if (event.key === 'Backspace' && $this.val().length === 0) {
            $(this).css('border-color', '');//change border color to default
            const prevIndex = $inputs.index(this) - 1;
            if (prevIndex >= 0) {
                $inputs.eq(prevIndex).focus();
            }
        }
    });

    //=====OTP input fields end=====
});
