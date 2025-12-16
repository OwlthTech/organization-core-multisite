/**
 * Reset Password Form Handler - Updated for New Structure
 * Matches the new PHP AJAX handler
 */

function initializeResetPasswordForm() {
  const $form = $("#mus-reset-form");
  if (!$form.length) return;

  $form.on("submit", function (e) {
    e.preventDefault();

    const $submitBtn = $form.find('button[type="submit"]');
    const $messageContainer = $form.find(".mus-form-message");
    const originalBtnText = $submitBtn.text();

    // Clear previous messages
    $messageContainer.html("");

    // ✅ KEY FIX: Get nonce from form field (not from window object)
    const nonce = $form.find('input[name="nonce"]').val();

    console.log("📋 Nonce from form:", nonce);

    if (!nonce) {
      $messageContainer.html(
        '<div class="alert alert-danger">❌ Security error: Nonce missing</div>'
      );
      return;
    }

    // Get form data using FormData
    const formData = new FormData(this);
    formData.append("action", "mus_process_new_password");
    formData.set("nonce", nonce); // ✅ Ensure nonce is set

    console.log("📤 Sending AJAX with data:", {
      action: "mus_process_new_password",
      nonce: nonce.substring(0, 10) + "...",
    });

    // Show loading state
    $submitBtn
      .prop("disabled", true)
      .html(
        '<span class="spinner-border spinner-border-sm me-2"></span> Resetting Password...'
      );

    // ✅ Submit AJAX request with FormData
    $.ajax({
      url: mus_params.ajax_url,
      type: "POST",
      data: formData,
      processData: false, // ✅ Don't process FormData
      contentType: false, // ✅ Let browser set content-type
      dataType: "json",
      success: function (response) {
        console.log("✅ Reset Password Response:", response);

        if (response.success) {
          $messageContainer.html(
            '<div class="alert alert-success">✅ ' +
              response.data.message +
              "</div>"
          );
          $submitBtn.html('<span class="me-2">✓</span>Success! Redirecting...');

          // Redirect after success
          setTimeout(function () {
            const redirectUrl =
              response.data.redirect_url || mus_params.home_url + "/login";
            window.location.href = redirectUrl;
          }, 2000);
        } else {
          $messageContainer.html(
            '<div class="alert alert-danger">❌ ' +
              (response.data?.message || "Error occurred") +
              "</div>"
          );
          $submitBtn.prop("disabled", false).html(originalBtnText);
          console.error("❌ Password reset failed:", response.data?.message);
        }
      },
      error: function (xhr, status, error) {
        console.error(
          "❌ AJAX Error:",
          error,
          "Status:",
          xhr.status,
          "Response:",
          xhr.responseText
        );

        let errorMsg = "An error occurred. Please try again.";

        if (xhr.status === 400) {
          errorMsg = "Bad Request: Invalid nonce or missing data";
        } else if (xhr.status === 403) {
          errorMsg = "Permission Denied: Security verification failed";
        } else if (xhr.status === 500) {
          errorMsg = "Server error. Please check error logs.";
        }

        $messageContainer.html(
          '<div class="alert alert-danger">❌ ' + errorMsg + "</div>"
        );
        $submitBtn.prop("disabled", false).html(originalBtnText);
      },
    });
  });
}

// ✅ Initialize on document ready
$(document).ready(function () {
  initializeResetPasswordForm();
});
