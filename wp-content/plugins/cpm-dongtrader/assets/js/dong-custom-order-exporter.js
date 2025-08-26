(function ($) {
  "use strict";

  /**
   * This enables you to define handlers, for when the DOM is ready:
   *
   */

  $(document).ready(function ($) {
    $(".export-loader").css("display", "none");
    /* $("body").on("click", "#export-csv-order", function (event) { */

    $("#export-csv-order").on("submit", function (event) {
      event.preventDefault();
      $(".export-loader").css("display", "");
      var start_date = this.start_month.value;
      var end_date = this.end_month.value;
      //var user_id = $("#affilate_id option:selected").val();
      if ($("#affilate_id option:selected").val() == "") {
        var user_id = $("#affilate_id option:selected").val();
      } else {
        var user_id = this.affilate_id.value;
      }
      console.log(user_id);

      var d = new Date();

      var month = d.getMonth() + 1;
      var day = d.getDate();

      var outputdate =
        d.getFullYear() +
        "/" +
        (("" + month).length < 2 ? "0" : "") +
        month +
        "/" +
        (("" + day).length < 2 ? "0" : "") +
        day;

      $.ajax({
        type: "POST",
        url: exporterajax.ajaxurl,
        data: {
          action: "dong_custom_order_exporter_csv_files",
          start_date: start_date,
          end_date: end_date,
          user_id: user_id,
        },
        success: function (response) {
          $(".export-loader").css("display", "none");
          if ($.trim(response) == "") {
            alert("There is no Order Data To export");
          } else {
            /*
             * Make CSV downloadable
             */
            var downloadLink = document.createElement("a");
            var fileData = ["\ufeff" + response];

            var blobObject = new Blob(fileData, {
              type: "text/csv;charset=utf-8;",
            });

            var url = URL.createObjectURL(blobObject);
            var csv_file_name = "dongtraders-custom-orders-";
            downloadLink.href = url;
            downloadLink.download = csv_file_name + outputdate + ".csv";
            // console.log(downloadLink.download);

            /*
             * Actually download CSV
             */
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
          }

          /*return false;*/
        },
      });
    });
  });
})(jQuery);
