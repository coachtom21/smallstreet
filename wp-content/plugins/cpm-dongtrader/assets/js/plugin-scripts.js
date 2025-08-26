(function ($) {
  // $(".dong-close").on("click", function (ec) {
  //   var dia = this.parentNode.parentNode;
  //   dia.style.opacity = 0;
  //   dia.style.zIndex = -1;
  //   $(document).find("#modal-form").trigger("reset");
  //   $(".dong-notify-msg").empty();
  // });

  // $(".dong-modal-toggle").on("click", function (dt) {
  //   dt.preventDefault();
  //   $("#openModal1").css("display", "");
  //   var targetId = this.getAttribute("data-target");
  //   var target = document.getElementById(targetId);
  //   target.style.opacity = 1;
  //   target.style.zIndex = 9999;

  //   var ifEdit = this.getAttribute("data-edit");
  //   if (ifEdit) {
  //     $(".qrtiger-size").val(this.getAttribute("data-size"));
  //     //$('.qrtiger-text').val(this.)
  //     var clickEvt = "edit";
  //   }
  // });

  // $(".delete-qr").on("click", function (d) {
  //   d.preventDefault();
  //   var qrId = this.getAttribute("data-qrid"),
  //     clickEvt = "del";

  //   //dong_ajax_request()
  // });
  //qr tiger generator form on submit
  // $(document).on("submit", ".qrtiger-form", function (ev) {
  //   ev.preventDefault();
  //   var formData = $(this).serialize();
  //   console.log(formData);
  //   $(".dong-notify-msg").empty().fadeIn("fast");
  //   $(".form-loader").css("display", "block");
  //   var qRsize = $(".qrtiger-size").val(),
  //     qRurl = $(".qrtiger-url").val(),
  //     qRcolor = $(".qrtiger-color").val();
    
  //   console.log(qRcolor, qRsize, qRurl);

  //   var datas = {
  //     action: "dongtrader_generate_qr2",
  //     type: "JSON",
  //     qrsize: qRsize,
  //     qrcolor: qRcolor,
  //     qrurl: qRurl,
  //   };

  //   dong_ajax_request(datas);
  // });

  // function dong_ajax_request(data) {
  //   // console.log(data);

  //   $.post(dongScript.ajaxUrl, data, function (rdata) {
  //     var resp = JSON.parse(rdata);
  //     var iconClass = resp.dataStatus ? `fa fa-check` : `fa fa-times-circle`;
  //     var msgClass = resp.dataStatus ? `success-msg` : `error-msg`;
  //     var msgText = resp.dataStatus
  //       ? `QR code generated successfully`
  //       : `All fields are required`;
  //     var responseHtml = `<div class="${msgClass}"><i class="${iconClass}"></i>${msgText}</div>`;
  //     /*  if ajax respone sucess */
  //     if (resp.dataStatus && resp.apistatus) {
  //       $(".dong-notify-msg").append(responseHtml).fadeOut(2000, "swing");
  //       $("#openModal1").fadeOut(2500, "swing");
  //     } else if (resp.dataStatus && !resp.apistatus) {
  //       var notifyHtml = `<div class="error-msg"><i class="fa fa-times-circle"></i>Api Error! Please Try Again</div>`;
  //       $(".dong-notify-msg").append(notifyHtml).fadeOut(2000, "swing");
  //     } else {
  //       $(".dong-notify-msg").append(responseHtml).fadeOut(2000, "swing");
  //     }
  //     $(".form-loader").css("display", "none");
  //   });
  // }
})(jQuery);
