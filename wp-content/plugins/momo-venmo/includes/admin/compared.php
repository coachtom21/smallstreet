<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>

<section class="bg-grey">
    <div class="container">
        <h1>Pay with Venmo vs. Venmo Link</h1>
        <p><strong><em>Checkout with Venmo</em></strong> is a WordPress plugin that helps your customers pay you using Pay with Venmo.</p>
        <p class="d-none"><a class="btn btn-primary" role="button" href="https://paypal.theafricanboss.com" target="_blank">Go to paypal.theafricanboss.com for updates</a></p>
        <p><strong><em>Checkout with Venmo</em> plugin comes with 2 payment methods</strong>. Enable one of them based on the features you need.</p>
        <p>Here is how the checkout page looks like for both</p>

        <div class="">
            <img class="shadow" src="<?php echo (WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo_pay_checkout.jpg'); ?>" width="50%" height="auto" alt="Venmo on the checkout page" />
            <p>Pay with Venmo example above</p>
            <img class="shadow" src="<?php echo (WCVENMO_PLUGIN_DIR_URL . 'assets/images/venmo_checkout.jpg'); ?>" width="50%" height="auto" alt="Venmo Link on the checkout page" />
            <p>Venmo Link example above</p>
        </div>

        <h2>Here is a much more detailed list of differences:</h2>
        <div class="table-responsive bg-light shadow compare p-0">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th class="table-primary text-center table-title">Woocommerce Payment Gateways</th>
                        <th class="text-center subscription-plan">
                            <p>Payment Method #1</p>
                            <h2>Pay with Venmo</h2>
                        </th>
                        <th class="text-center subscription-plan">
                            <p>Payment Method #2</p>
                            <h2>Venmo Link</h2>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="feature-row">
                        <td>Official PayPal Integration<br /></td>
                        <td class="text-center feature-option">✔</td>
                        <td class="text-center feature-option">x</td>
                    </tr>
                    <tr class="feature-row">
                        <td>Transaction &amp; App Fees **<br /></td>
                        <td class="text-center feature-option">✔</td>
                        <td class="text-center feature-option">x - with personal Venmo<br />✔ - with a Venmo Business Account<br /></td>
                    </tr>
                    <tr class="feature-row">
                        <td>Requires a Venmo account<br /></td>
                        <td class="text-center feature-option">x</td>
                        <td class="text-center feature-option">✔</td>
                    </tr>
                    <tr class="feature-row">
                        <td>Requires a PayPal account<br /></td>
                        <td class="text-center feature-option">✔</td>
                        <td class="text-center feature-option">x</td>
                    </tr>
                    <tr class="feature-row">
                        <td>emailreceipts.io integration<br /></td>
                        <td class="text-center feature-option">N/A</td>
                        <td class="text-center feature-option">✔</td>
                    </tr>
                    <tr class="feature-row">
                        <td>Automatic order status updates<br /></td>
                        <td class="text-center feature-option">✔</td>
                        <td class="text-center feature-option">x - without emailreceipts.io<br />✔ - with emailreceipts.io (NEW)<br /></td>
                    </tr>
                    <tr class="feature-row">
                        <td>Reduce inventory stock<br /></td>
                        <td class="text-center feature-option">✔</td>
                        <td class="text-center feature-option">x - without emailreceipts.io<br />✔ - with emailreceipts.io (NEW)<br /></td>
                    </tr>
                    <tr class="feature-row">
                        <td>Transfer/Withdrawal platform<br /></td>
                        <td class="text-center feature-option">PayPal via website/app/hardware</td>
                        <td class="text-center feature-option">Venmo via the app</td>
                    </tr>
                    <tr class="feature-row">
                        <td>Instant transfer/withdrawal<br /></td>
                        <td class="text-center feature-option">instant transfer = paid<br />delayed transfer = free</td>
                        <td class="text-center feature-option">instant transfer = paid<br />delayed transfer = free</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p class="mt-4">** <a href="https://www.paypal.com/us/webapps/mpp/merchant-fees#commtrans-domestic" target="_blank">PayPal</a> charges 3.49% for Pay with Venmo transactions.</p>
        <p class="mt-4">** <a href="https://venmo.com/business/start/" target="_blank">Venmo for Business</a> lets you accept unlimited payments of any size using your email, @venmo_username account. You won’t have any account limits, but there will be a 3.49% per transaction fee when you accept payments with a Venmo Business account.</p>
    </div>

    <div class="container">
        <header class="tableHeader">
            <h4><a href="https://www.paypal.com/us/webapps/mpp/merchant-fees#commtrans-domestic" target="_blank">Standard rate for receiving domestic transactions</a></h4>
        </header>
        <div class="ppvx_table-wrapper table-responsive">
            <table class="table table-bordered ppvx_table responsive-table squish col"><thead><tr><th colspan="1" rowspan="1">Payment Type</th><th colspan="1" rowspan="1">Rate</th></tr></thead><tbody><tr><th scope="row">Alternative Payment Method (APM)</th><td><a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#apm-rates" pa-marked="1">APM Transaction Rates Apply</a></td></tr><tr><th scope="row">Invoicing</th><td>3.49% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">PayPal Checkout</th><td>3.49% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">PayPal Guest Checkout</th><td>3.49% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">QR code Transactions – 10.01 USD and above</th><td>1.90% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#qr_code_fixed_fee" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">QR code Transactions – 10.00 USD and below</th><td>2.40% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#qr_code_fixed_fee_transactions" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">QR code Transactions through third party integrator</th><td>2.29% + 0.09 USD</td></tr><tr><th scope="row">Pay with Venmo</th><td>3.49% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">Send/Receive Money for Goods and Services</th><td>2.99%</td></tr><tr><th scope="row">Standard Credit and Debit Card Payments</th><td>2.99% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr><tr><th scope="row">All Other Commercial Transactions</th><td>3.49% + <a target="_blank" href="https://www.paypal.com/us/webapps/mpp/merchant-fees#fixed-fees-commercialtrans" pa-marked="1">fixed fee</a></td></tr></tbody></table>
        </div>
    </div>

</section>