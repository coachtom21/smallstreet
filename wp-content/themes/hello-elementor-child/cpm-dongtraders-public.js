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
