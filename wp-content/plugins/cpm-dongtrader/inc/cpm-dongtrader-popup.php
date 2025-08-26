<?php
function cpp_popup_markup()
{
    if ((is_home() || is_front_page()) && !is_user_logged_in()) {
        echo '
        <div id="cpp-popup" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                <p>Is this proof of delivery?</p>
                <button id="cpp-yes">Yes</button>
                <button id="cpp-no">No</button>
            </div>
        </div>

        <div id="cpp-popup-2" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                <form id="cpp-form">
                    <p>Are you a buyer/seller/personal ?</p>
                    <input type="radio" id="buyer" name="role" value="buyer" checked="checked" required>
                    <label for="buyer">Buyer (7%)</label><br>
                    <input type="radio" id="seller" name="role" value="seller" required>
                    <label for="seller">Seller (3%)</label><br>
                    <input type="radio" id="personal" name="role" value="personal" required>
                    <label for="personal">Personal (10%)</label><br>
                    <button type="submit">Next</button>
                </form>
            </div>
        </div>

        <div id="cpp-popup-3" class="cpp-popup">
            <div class="cpp-popup-content">
                <span class="cpp-close">&times;</span>
                ' . do_shortcode('[cpm_twilio_otp shadow="no" ]') . '
            </div>
        </div>
        ';
        ?>

        <script>

            jQuery(document).ready(function ($) {

                // Get the current URL
                const url = new URL(window.location.href);

                // Get the 'role' parameter
                const role = url.searchParams.get('role');
                // alert(role)

                if (role !== null) {
                    closePopup('#cpp-popup');
                    closePopup('#cpp-popup-2');
                    showPopup('#cpp-popup-3');
                }

                function setCookie(name, value, days) {
                    var expires = "";
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + (value || "") + expires + "; path=/";
                }

                function showPopup(popupId) {
                    $(popupId).fadeIn();
                }

                function closePopup(popupId) {
                    $(popupId).fadeOut();
                }

                $(document).ready(function () {
                    showPopup('#cpp-popup');

                    $('#cpp-yes').click(function () {
                        closePopup('#cpp-popup');
                        showPopup('#cpp-popup-2');
                    });

                    $('#cpp-no').click(function () {
                        closePopup('#cpp-popup');
                    });

                    $('.cpp-close').click(function () {
                        closePopup('#cpp-popup');
                        closePopup('#cpp-popup-2');
                        closePopup('#cpp-popup-3');
                    });

                    $('#cpp-form').submit(function (e) {
                        e.preventDefault();
                        var selectedRole = $('input[name="role"]:checked').val();
                        setCookie('user_role', selectedRole, 30);
                        closePopup('#cpp-popup-2');
                        showPopup('#cpp-popup-3');
                        // Here you can add AJAX call to submit form data if needed
                    });

                });
            });
        </script>

        <style>
            .cpp-popup {
                display: none;
                position: fixed;
                z-index: 1000;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgb(0, 0, 0);
                background-color: rgba(0, 0, 0, 0.4);
            }

            .cpp-popup-content {
                background-color: #fefefe;
                margin: 15% auto;
                padding: 20px;
                border: 1px solid #888;
                width: 80%;
                max-width: 500px;
                position: relative;
            }

            .cpp-close {
                color: #aaa;
                float: right;
                font-size: 28px;
                font-weight: bold;
            }

            .cpp-close:hover,
            .cpp-close:focus {
                color: black;
                text-decoration: none;
                cursor: pointer;
            }

            .cpp-popup-content #cpp-yes,
            .cpp-popup-content #cpp-no {
                padding: 10px 40px;
            }
        </style>
        <?php
    }
}
add_action('wp_footer', 'cpp_popup_markup');
