// Fix for missing print_country function
function print_country() {
    // This function is called but not defined, adding a placeholder
    console.log("print_country function called");
    return true;
}

jQuery(window).on("load", function () {
  //   alert("js file run");
  //jQuery("#elementor-popup-modal-2146").css("display", "none");
  /* jQuery(".dong-half-dozen").on("touchstart click", function (e) { */
  jQuery(document).on("touchstart click", ".dong-half-dozen", function (e) {
    e.preventDefault();
    console.log("open modal");

    jQuery("#elementor-popup-modal-2146").attr(
      "style",
      "display: block !important"
    );
  });
  /* jQuery(".jki-window-close-solid").on("touchstart click", function (e) { */
  jQuery(document).on(
    "touchstart click",
    ".jki-window-close-solid",
    function (e) {
      e.preventDefault();
      // alert("colse modal");

      jQuery("#elementor-popup-modal-2146").attr(
        "style",
        "display: none !important"
      );
    }
  );
});

const plus = document.querySelector(".dt-plus"),
  minus = document.querySelector(".dt-minus"),
  num = document.querySelector(".dt-num");

window.addEventListener("load", () => {
  if (localStorage["num"]) {
    num.innerText = localStorage.getItem("num");
  } else {
    let a = "01";
    num.innerText = a;
  }
});

plus.addEventListener("click", () => {
  a = num.innerText;
  a++;
  a = (a < 10) ? "0" + a : a;
  localStorage.setItem("num", a);
  num.innerText = localStorage.getItem("num");
});

minus.addEventListener("click", () => {
  a = num.innerText;
  if (a > 1) {
    a--;
    a = (a < 10) ? "0" + a : a;
    localStorage.setItem("num", a);
    num.innerText = localStorage.getItem("num");
  }
});

// Check for proof_id and scan_type=proof in URL, then save to localStorage when user selects "yes"
(function() {
  'use strict';
  
  // Function to initialize the proof of delivery handler
  function initProofOfDeliveryHandler() {
    // Check if jQuery is available
    if (typeof jQuery === 'undefined') {
      console.error('jQuery is not loaded');
      return;
    }
    
    // Wait for DOM to be ready
    jQuery(document).ready(function() {
      // Get URL parameters
      const urlParams = new URLSearchParams(window.location.search);
      const scanType = urlParams.get('scan_type');
      const proofId = urlParams.get('proof_id');

      // Check if both proof_id and scan_type=proof exist in URL
      if (proofId && scanType === 'proof') {
        console.log('Proof of delivery scan detected:', {
          proof_id: proofId,
          scan_type: scanType
        });

        // Listen for changes on the "Is this proof of delivery?" radio button
        jQuery(document).on('change', 'input[name="delivery_proof"]', function() {
          const deliveryProofValue = jQuery('input[name="delivery_proof"]:checked').val();
          
          // Only save to localStorage if user selects "yes"
          if (deliveryProofValue === 'yes') {
            // Prepare scan data object
            const scanData = {
              proof_id: proofId,
              scan_type: scanType,
              delivery_proof: 'yes',
              timestamp: new Date().toISOString()
            };

            // Save to localStorage with key "scan data"
            localStorage.setItem('scan data', JSON.stringify(scanData));
            
            // Log for debugging
            console.log('Scan data saved to localStorage:', scanData);
          } else {
            // Remove data if user selects "no" or changes selection
            localStorage.removeItem('scan data');
            console.log('Scan data removed from localStorage (user selected "no")');
          }
        });
      }
    });
  }
  
  // Try to initialize immediately if jQuery is already loaded
  if (typeof jQuery !== 'undefined') {
    initProofOfDeliveryHandler();
  } else {
    // Wait for jQuery to load
    window.addEventListener('load', function() {
      if (typeof jQuery !== 'undefined') {
        initProofOfDeliveryHandler();
      }
    });
  }
})();
