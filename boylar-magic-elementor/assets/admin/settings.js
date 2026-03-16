/* global jQuery, BoylarMagicElementorSettings */
(function ($) {
  "use strict";

  function setResult(text, ok) {
    const $el = $("#boylar-magic-test-connection-result");
    $el.text(text);
    $el.css("color", ok ? "#1d7f1d" : "#b32d2e");
  }

  $(function () {
    $(document).on("click", "#boylar-magic-test-connection", async function (e) {
      e.preventDefault();
      const $btn = $(this);
      $btn.prop("disabled", true).text("Testing...");
      setResult("", true);

      try {
        const res = await $.ajax({
          url: BoylarMagicElementorSettings.ajaxUrl,
          method: "POST",
          dataType: "json",
          data: {
            action: "boylar_magic_ai_test_connection",
            nonce: BoylarMagicElementorSettings.nonce,
          },
        });

        if (!res || !res.success) {
          throw new Error((res && res.data && res.data.message) || "Connection test failed.");
        }

        setResult(res.data.message || "OK", true);
      } catch (err) {
        setResult(err && err.message ? err.message : String(err), false);
      } finally {
        $btn.prop("disabled", false).text("Test connection");
      }
    });
  });
})(jQuery);

