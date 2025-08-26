/* js for copy paste qr generated url */
function dong_traders_url_copy(element) {
  var $temp = jQuery("<input>");
  jQuery("body").append($temp);
  $temp.val(jQuery(element).text()).select();
  document.execCommand("copy");
  $temp.remove();
}


(function ($) {
  $(document).ready(function () {


    jQuery("#checkbox-control-0").prop("checked", true);
    //initialize tab on backend
    $(document).on("click", ".update_card", function (ev) {
      $(".qr-tiger-vcard-code-generator").css("display", "block");
    }); // END OF DOCUMENT READY

    print_country("country");
    jQuery("#country").val("USA");
    print_state("state", jQuery("#country")[0].selectedIndex);

    jQuery(".select-product").change(function () {
      var get_p_id = jQuery(this).val();
      $("div.export_variation_product_hide").hide();
      $("#varition-" + get_p_id).show();
    });
  });



})(jQuery);
