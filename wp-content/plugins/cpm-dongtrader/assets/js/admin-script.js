jQuery(document).ready(function ($) {

  jQuery("#refresh_order").click(function () {
    location.reload();
  });
  /*Retreives Data From session storage */
  let data = sessionStorage.getItem("lastid");
  /* Checks The Last Tab clicked from session storage and makes the same tab active from session storage */
  if (data) $("#" + data).addClass("ui-tabs-active ui-state-active");
  /*When any tab is clicked from settings sections the tab id is stored in session storage */
  $("#dongtrader-tabs>li").on("click", function () {
    sessionStorage.setItem("lastid", $(this).prop("id"));
  });

  if(!$('#dong_currency_check').is(':checked')){
    $('.currency-convert').hide();
    $('#dong_currency_check').val('off');
  }

  $('#dong_currency_check').change(function() {
    $('.currency-convert').toggle($(this).is(':checked'));
    if($('#dong_currency_check').is(':checked')){
      $('#dong_currency_check').val('on');
    }else{
      $('#dong_currency_check').val('off');
    }
  });
  /*Saves Data On Options via ajax for the from inside Integration API Tab from settings section*/
  $("#save-settings").submit(function () {
    var b = $(this).serialize();
    $(".save-settings-dash").val("Saving...");
    $.post("options.php", b)
      .error(function () {
        var html = `<div class="error-box"></div>`;
        $(html).insertAfter(".settings-submit");
        setTimeout(function () {
          $(".error-box").remove();
        }, 1000);
        $(".save-settings-dash").val("Confirm Changes");
      })
      .success(function () {
        var html = `<div class="success-box">Settings Saved Successfully</div>`;
        $(html).insertAfter(".settings-submit");
        setTimeout(function () {
          $(".success-box").remove();
        }, 1000);
        $(".save-settings-dash").val("Confirm Changes");
      });
    return false;
  });

  /* A jQuery function to initialize the tabs. */
  $(function () {
    // Check if jQuery UI tabs is available (only on settings page)
    if (typeof $.fn.tabs !== 'undefined' && $("#tabs-wrap").length > 0) {
      $("#tabs-wrap").tabs();
    }
  });

  function animate_button(show) {
    if (show) {
      $(".real-button").css("display", "none");
      $(".anim-button").css("display", "");
    } else {
      $(".real-button").css("display", "");
      $(".anim-button").css("display", "none");
    }
  }
  /* When The Form is submitted from Qr Code tabs in settings sections */
  $(document).on("submit", ".qrtiger-form", function (ev) {
    ev.preventDefault();

    animate_button(true);
    // $('.custom-load').css('display', '');
    $(".dong-notify-msg").empty().fadeIn("fast");
    $(".form-loader").css("display", "block");
    var qRsize = $(".qrtiger-size").val(),
      qRurl = $(".qrtiger-url").val(),
      qRcolor = $(".qrtiger-color").val(),
      qRnonce = $("input[name='qrtiger_nonce']").val();
    var datas = {
      action: "dongtrader_generate_qr2",
      type: "JSON",
      qrsize: qRsize,
      qrcolor: qRcolor,
      qrurl: qRurl,
      nonce: qRnonce,
    };

    dong_ajax_request(datas);
  });

  /**
   * It takes the data from the form and sends it to the server using ajax
   * @param data - The data to be sent to the server.
   */
  function dong_ajax_request(data) {
    $.post(dongScript.ajaxUrl, data, function (rdata) {
      try {
        /*  Parse json data to object */
        var resp = JSON.parse(rdata);
        /*  Set icon class either error or success*/
        var iconClass = resp.dataStatus ? `fa fa-check` : `fa fa-times-circle`;
        /*  Setting the value of the variable `msgClass` to `success-msg` if the value of
          `resp.dataStatus` is true, and `error-msg` if the value of `resp.dataStatus` is false. */
        var msgClass = resp.dataStatus ? `success-msg` : `error-msg`;
        /*  Setting the value of the variable `msgText` to `QR code generated successfully` if the value of
          `resp.dataStatus` is true, and use error message if available, otherwise default message. */
        var msgText = resp.dataStatus
          ? `QR code generated successfully.`
          : (resp.error || `All fields are required`);
        /*  Response Html Message Combined to display the response status as error or valid*/
        var responseHtml = `<div class="${msgClass}"><i class="${iconClass}"></i>${msgText}</div>`;
        /*  if api response is ok and ajax response data is valid */
        if (resp.dataStatus && resp.apistatus) {
          $(".dong-notify-msg").append(responseHtml).fadeOut(2000, "swing");
          $("#openModal1").fadeOut(2500, "swing");
          animate_button(false);
          sessionStorage.setItem("lastid", "second");
          window.location.reload();
        } else if (resp.dataStatus && !resp.apistatus) {
          /*  if api response is bad and ajax response data is valid */
          var notifyHtml = `<div class="error-msg"><i class="fa fa-times-circle"></i>Api Error! Please Try Again</div>`;
          $(".dong-notify-msg").append(notifyHtml).fadeOut(2000, "swing");
          animate_button(false);
        } else {
          /*  if everything gone wrong */
          $(".dong-notify-msg").append(responseHtml).fadeOut(2000, "swing");
          animate_button(false);
        }
        animate_button(false);
      } catch (e) {
        /*  Handle JSON parse error */
        console.error('JSON Parse Error:', e);
        console.error('Response data:', rdata);
        var errorHtml = `<div class="error-msg"><i class="fa fa-times-circle"></i>Error: Invalid server response. Please try again.</div>`;
        $(".dong-notify-msg").append(errorHtml).fadeOut(2000, "swing");
        animate_button(false);
      }
    }).fail(function(xhr, status, error) {
      /*  Handle AJAX failure */
      console.error('AJAX Error:', status, error);
      var errorHtml = `<div class="error-msg"><i class="fa fa-times-circle"></i>Error: Failed to generate QR code. Please try again.</div>`;
      $(".dong-notify-msg").append(errorHtml).fadeOut(2000, "swing");
      animate_button(false);
    });
  }

  $(document).on("click", ".url-copy", function (cp) {
    cp.preventDefault();
    var urlcp = $(this).attr("data-url");
    var $temp = $("<input>");
    $("body").append($temp);
    $temp.val(urlcp).select();
    if (document.execCommand("copy") && $temp.remove())
      alert("QR URL copied to clipboard");
  });

  $(document).on("click", ".qr-remover", function (rm) {
    rm.preventDefault();
    $(this).text("Removing...");
    var itemId = $(this).attr("data-remove");
    var metaKey = $(this).attr("data-meta");
    var removeButton = $(this);
    var loop = $(this).attr("data-index") || "";
    var checkLocation = $(this).closest('.dong-qr-components-single').length > 0;
    
    // Get container based on type
    var container;
    if (metaKey === 'variable_product_qr_data') {
      // For variable products, try to get loop from container ID if not in data-index
      if (!loop) {
        var containerId = $(this).closest('[id^="dong-qr-components"]').attr('id');
        if (containerId) {
          loop = containerId.replace('dong-qr-components', '');
        }
      }
      // Use the specific container ID
      container = loop ? $("#dong-qr-components" + loop) : $(this).closest('.dong-qr-components');
    } else {
      // For single products
      container = $(this).closest('.dong-qr-components');
    }
    
    var changeEvt = !checkLocation && loop ? $("#variable_description" + loop) : false;
    var save = !checkLocation && loop ? $(".save-variation-changes") : false;
    
    // Store data before emptying (for variable products)
    var parentProductId = '';
    var variationId = itemId;
    if (metaKey === 'variable_product_qr_data') {
      var hiddenInput = container.find('input[data-id]');
      if (hiddenInput.length) {
        // For existing QR, data-id contains parent product ID
        parentProductId = hiddenInput.attr('data-id');
      }
      // Variation ID is the itemId
      variationId = itemId;
    }

    $.post(
      dongScript.ajaxUrl, 
      {
        action: "dongtrader_delete_qr_fields", 
        itemID: itemId,
        metakey: metaKey,
      },
      function (mData) {
        // Determine initiator based on metaKey BEFORE emptying
        var initiator = '';
        var variations = '';
        var postId = itemId;
        
        if (metaKey === '_product_qr_codes') {
          initiator = '_product_qr_codes';
        } else if (metaKey === '_product-qr-direct-checkouts') {
          initiator = '_product-qr-direct-checkouts';
        } else if (metaKey === 'variable_product_qr_data') {
          initiator = '_product-qr-variabled';
          variations = variationId; // variation ID
          postId = parentProductId || itemId; // parent product ID (optional, used for validation)
        }
        
        // Empty container after getting data
        container.empty();
        
        // Automatically regenerate QR code after removal
        if (initiator) {
          // Show loading state in container
          container.html('<p>Regenerating QR code...</p>');
          
          // For variable products, trigger change event
          if (metaKey === 'variable_product_qr_data') {
            if (changeEvt && changeEvt.length) {
              changeEvt.trigger("change");
            }
          }
          
          var inPut = $('<input>').attr('data-id', postId);
          container.append(inPut);
          
          // Create a temporary button element for status updates
          var statusButton = $('<button>').text('Regenerating...');
          
          // Trigger generation
          initiate_ajax_request(
            {
              action: "dongtrader_meta_qr_generator",
              productnums: postId,
              variations: variations,
              intiator: initiator,
              loop: loop || ""
            },
            inPut,
            container,
            statusButton
          );
        } else {
          // If no initiator, just show generate button (old behavior)
          if (changeEvt && changeEvt.length) changeEvt.trigger("change");
          if (save && save.length) save.trigger("click");
          if (checkLocation) window.location.reload();
        }
      }
    );
  });

  $(document).on("click", ".qr-delete", function (rm) {
    rm.preventDefault();

    var index = $(this).attr('data-index');
    var buttonId = $(this).prop('id');
    
     var row = $(this).closest('#tr-index-'+index);
     $('#'+buttonId).text('Deleting...');
      $.post(
          dongScript.ajaxUrl,
          {
            action : "dongtrader_delete_qr_items_settingspage",
            index : index,
            nonce : dongScript.deleteQrNonce,
          },
          function(data){
            if(data && data.resp){
              row.remove();
            }

            if(data && data.reload){
              window.location.reload();
            }
          }
        
      ).fail(function(xhr, status, error) {
        console.error('AJAX Error:', status, error);
        $('#'+buttonId).text('Delete');
        alert('Error: Failed to delete QR code. Please try again.');
      });
  
  });

  $(document).on("submit",".rfund-form" , function(rf){
    rf.preventDefault();
    animate_button(true);
    var formdatas =  $(this).serialize() , resptemp = $(this).find('.rfund-notify-msg') , form = $(this);


    $.post(dongScript.ajaxUrl,{action : "dongtrader_release_funds", formdatas : formdatas,},function(response){
      var msgClass = response.success ? `success-msg` : `error-msg`,
      iconClass = response.success ? `fa fa-check` : `fa fa-times-circle`,
      notifyHtml = `<div class="${msgClass}"><i class="${iconClass}"></i> ${response.data}</div>`;
      resptemp.append(notifyHtml).fadeIn().delay(2000).fadeOut(function() {
       
        form[0].reset();
        animate_button(false);
        window.location.reload();
      });

    }).fail(function(jqXHR, textStatus, errorThrown) {
      
      console.log(textStatus + ': ' + errorThrown);
      animate_button(false);
    });
   
   
  });

  function qr_generator(button) {
    button.on("click", function (e) {
    
  
      e.preventDefault();
      button.text("Generating...");
      var postId = $(this).attr("data-id");
      var evtAction = $(this).attr("data-initiator");
      var inPut = $(this).next("input");
      var mainContainer = $(this).parent(".dong-qr-components");
      var variations = $(this).attr(".data-variable");

      initiate_ajax_request(
        {
          action: "dongtrader_meta_qr_generator",
          productnums: postId,
          variations: variations,
          intiator: evtAction,
        },
        inPut,
        mainContainer,
        $(this)
      );
    });
  }

  qr_generator($(".generate-product-qr"));
  qr_generator($(".generate-product-qr-direct-checkout"));

  function initiate_ajax_request(datas, inPut, mainContainer, button) {
    $.post(dongScript.ajaxUrl, datas, function (mData) {
      try {
        var jsonData = JSON.parse(mData);
        console.table(jsonData);
        if (jsonData.success) {
          mainContainer.empty();
          mainContainer.append(jsonData.template);
          inPut.val(mData);
          button.text("Generate Product QR");
        } else {
          // Handle error response
          alert('Error: ' + (jsonData.error || 'QR generation failed'));
          button.text("Generate Product QR");
        }
      } catch (e) {
        // Handle JSON parse error
        console.error('JSON Parse Error:', e);
        console.error('Response data:', mData);
        alert('Error parsing server response. Please check the console for details.');
        button.text("Generate Product QR");
      }
    }).fail(function(xhr, status, error) {
      // Handle AJAX failure
      console.error('AJAX Error:', status, error);
      alert('Error: Failed to generate QR code. Please try again.');
      button.text("Generate Product QR");
    });
  }


  $('.rf-del').on('click', function(es){

    es.preventDefault();
    $(this).text('...')
    let rowId=$(this).attr('data-rfid');
    $.post(dongScript.ajaxUrl,{action : "dongtrader_delete_funds", rowid : rowId,},function(response){
       if(response.success){
        window.location.reload();
       }else{
        alert('Some Error Ocured.Please try again!!')
       }
    });
    // $(this).text('Delete');

  });
// $('.cpm-multiselect').select2();

$(".cpm-multiselect").select2({
    placeholder: "Select Products",
    allowClear: false
});

  //Scenario changed a bit for meta fields

  $("#woocommerce-product-data").on(
    "woocommerce_variations_loaded",
    function (event) {
      $(".generate-variable-qr").on("click", function (e) {
        e.preventDefault();
        var loop = $(this).attr("data-index");
        var postId = $(this).attr("data-productid"),
          evtAction = $(this).attr("data-initiator"),
          inPut = $(this).next("input"),
          mainContainer = $("#dong-qr-components" + loop),
          variations = $(this).attr("data-id");
        $("#variable_description" + loop).trigger("change");
        $(this).text("Generating...");
        initiate_ajax_request(
          {
            action: "dongtrader_meta_qr_generator",
            productnums: postId,
            variations: variations,
            intiator: evtAction,
            loop: loop,
          },
          inPut,
          mainContainer,
          $(this)
        );
      });
    }
  );
});
