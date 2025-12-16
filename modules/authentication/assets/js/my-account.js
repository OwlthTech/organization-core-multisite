/**
 * My Account Page - Profile Management & School Management
 *
 * Handles:
 * - Profile form submission with validation
 * - Phone number formatting (123) 456-7890
 * - Email validation
 * - School deletion with confirmation
 * - Logout functionality
 * - Tab navigation
 * - Scroll to top on all actions
 * - Notifications system
 *
 * @package Organization_Core
 * @subpackage Authentication
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // ================================================
  // INITIALIZATION
  // ================================================

  $(document).ready(function () {
    initMyAccount();
  });

  function initMyAccount() {
    console.log("  My Account page initialized");

    // ================================================
    // VALIDATION PATTERNS
    // ================================================

    const ValidationPatterns = {
      phone: /^\(\d{3}\)\s\d{3}-\d{4}$/,
      email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    };

    // ================================================
    //   SCROLL TO TOP HELPER
    // ================================================

    function scrollToTop() {
      $("html, body").animate(
        {
          scrollTop: 0,
        },
        600,
        "easeInOutQuad"
      );
    }

    // ================================================
    //   SHOW NOTIFICATION
    // ================================================

    window.showNotification = function (message, type) {
      const $container = $("#mus-fixed-notification");
      const alertClass = type === "success" ? "alert-success" : "alert-danger";

      const alert = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
          ${message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;

      $container.html(alert);

      if (type === "success") {
        setTimeout(function () {
          $container.find(".alert").fadeOut("slow", function () {
            $container.empty();
          });
        }, 4000);
      }
    };

    // ================================================
    //   PERFECT PHONE NUMBER FORMATTER
    // Format: (123) 456-7890 with smooth backspace
    // ================================================

    let phoneLastValue = {};

    function formatPhoneNumber(input) {
      const cleaned = input.replace(/\D/g, "");
      const match = cleaned.match(/^(\d{0,3})(\d{0,3})(\d{0,4})$/);

      if (!match) return input;

      let formatted = "";
      if (match[1]) {
        formatted = "(" + match[1];
        if (match[1].length === 3) formatted += ") ";
      }
      if (match[2]) {
        formatted += match[2];
        if (match[2].length === 3 && match[3]) formatted += "-";
      }
      if (match[3]) {
        formatted += match[3];
      }

      return formatted;
    }

    // Track phone input changes
    $(".phone-input").on("keydown", function (e) {
      phoneLastValue[this.id] = this.value;
    });

    // Smart phone input handler
    $(".phone-input").on("input", function () {
      const $this = $(this);
      const previousValue = phoneLastValue[this.id] || "";
      const currentValue = this.value;
      const cursorPos = this.selectionStart;
      const digitsOnly = currentValue.replace(/\D/g, "");

      // User is DELETING
      if (currentValue.length < previousValue.length) {
        this.value = digitsOnly;
        this.setSelectionRange(
          Math.max(0, cursorPos - 1),
          Math.max(0, cursorPos - 1)
        );
      }
      // User is ADDING
      else if (currentValue.length > previousValue.length) {
        this.value = formatPhoneNumber(this.value);

        // Smart cursor placement
        if (digitsOnly.length === 3) {
          this.setSelectionRange(5, 5); // After ")"
        } else if (digitsOnly.length === 6) {
          this.setSelectionRange(9, 9); // After "-"
        } else {
          this.setSelectionRange(this.value.length, this.value.length);
        }
      }

      phoneLastValue[this.id] = this.value;

      // Real-time validation
      if (this.value.length > 0) {
        if (ValidationPatterns.phone.test(this.value)) {
          $this.removeClass("is-invalid").addClass("is-valid");
        } else {
          $this.removeClass("is-valid");
          if (this.value.length === 14) {
            $this.addClass("is-invalid");
          }
        }
      } else {
        $this.removeClass("is-valid is-invalid");
      }
    });

    // Phone blur validation
    $(".phone-input").on("blur", function () {
      const $this = $(this);
      if (
        $this.attr("required") &&
        !ValidationPatterns.phone.test(this.value)
      ) {
        if (this.value.length > 0) {
          $this.addClass("is-invalid");
        }
      }
    });

    // ================================================
    //   EMAIL VALIDATION
    // ================================================

    $(".email-input").on("input", function () {
      const $this = $(this);
      if (this.value.length > 0) {
        if (ValidationPatterns.email.test(this.value)) {
          $this.removeClass("is-invalid").addClass("is-valid");
        } else {
          $this.removeClass("is-valid");
        }
      } else {
        $this.removeClass("is-valid is-invalid");
      }
    });

    $(".email-input").on("blur", function () {
      const $this = $(this);
      if ($this.attr("required") && this.value.length > 0) {
        if (!ValidationPatterns.email.test(this.value)) {
          $this.addClass("is-invalid");
        }
      }
    });

    // ================================================
    //   FORM VALIDATION
    // ================================================

    function validateProfileForm() {
      let isValid = true;
      const errors = [];
      const $form = $("#mus-profile-form");

      // Check required fields
      $form.find("[required]").each(function () {
        const $field = $(this);
        if (!$field.val().trim()) {
          isValid = false;
          $field.addClass("is-invalid");
          const label = $field.prev("label").text().replace("*", "").trim();
          errors.push(`${label} is required`);
        }
      });

      // Validate phone numbers
      $(".phone-input[required]").each(function () {
        if (this.value && !ValidationPatterns.phone.test(this.value)) {
          isValid = false;
          $(this).addClass("is-invalid");
          errors.push(
            `Invalid phone number format for ${$(this).prev("label").text()}`
          );
        }
      });

      // Validate email
      const emailInput = $("#user_email");
      if (
        emailInput.val() &&
        !ValidationPatterns.email.test(emailInput.val())
      ) {
        isValid = false;
        emailInput.addClass("is-invalid");
        errors.push("Invalid email address format");
      }

      if (!isValid) {
        showNotification(
          "❌ Please fix the following errors:<br>" + errors.join("<br>"),
          "error"
        );
        scrollToTop();

        // Focus first error
        const firstError = $form.find(".is-invalid").first();
        if (firstError.length) {
          firstError.focus();
        }
      }

      return isValid;
    }

    // ================================================
    //   PROFILE FORM HANDLER
    // ================================================

    $("#mus-profile-form").on("submit", function (e) {
      e.preventDefault();

      if (!validateProfileForm()) {
        return;
      }

      const $form = $(this);
      const $submitBtn = $("#updateProfileBtn");
      const $spinner = $submitBtn.find(".spinner-border");

      $submitBtn.prop("disabled", true);
      $spinner.removeClass("d-none");

      const formData = new FormData(this);
      formData.append("action", "mus_update_profile");
      formData.append("nonce", mus_params.nonce);

      $.ajax({
        url: mus_params.ajax_url,
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        success: function (response) {
          if (response.success) {
            showNotification("  " + response.data.message, "success");
            scrollToTop();
          } else {
            showNotification(
              "❌ " + (response.data.message || "Update failed"),
              "error"
            );
            scrollToTop();
          }
        },
        error: function (xhr, status, error) {
          showNotification("❌ Network error. Please try again.", "error");
          scrollToTop();
        },
        complete: function () {
          $submitBtn.prop("disabled", false);
          $spinner.addClass("d-none");
        },
      });
    });

    // ================================================
    //   DELETE SCHOOL HANDLER
    // ================================================

    $(document).on("click", ".delete-school-btn", function (e) {
      e.preventDefault();

      const $btn = $(this);
      const schoolId = $btn.data("school-id");

      if (!schoolId || schoolId === 0) {
        showNotification("❌ Invalid school ID", "error");
        scrollToTop();
        return;
      }

      if (
        !confirm(
          "Are you sure you want to delete this school?\n\nThis action cannot be undone."
        )
      ) {
        return;
      }

      console.log("🗑️  Deleting school:", schoolId);

      const originalHtml = $btn.html();
      $btn
        .prop("disabled", true)
        .html('<i class="fas fa-spinner fa-spin"></i>');

      $.ajax({
        url: mus_params.ajax_url,
        type: "POST",
        dataType: "json",
        data: {
          action: "mus_schools_delete",
          nonce: mus_params.nonce,
          school_id: schoolId,
        },
        success: function (response) {
          console.log("  AJAX Response:", response);

          if (response.success) {
            showNotification("  " + response.data.message, "success");
            scrollToTop();

            // Refresh page after 1 second
            setTimeout(() => {
              location.reload();
            }, 1000);
          } else {
            showNotification("❌ " + response.data.message, "error");
            scrollToTop();
            $btn.prop("disabled", false).html(originalHtml);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX Error:", { status, error });
          showNotification("❌ Failed to delete school", "error");
          scrollToTop();
          $btn.prop("disabled", false).html(originalHtml);
        },
      });
    });

    // ================================================
    //   LOGOUT HANDLER
    // ================================================

    window.handleLogout = function () {
      if (!confirm("Are you sure you want to logout?")) {
        return;
      }

      scrollToTop();

      $.ajax({
        url: mus_params.ajax_url,
        type: "POST",
        data: {
          action: "mus_process_logout",
          nonce: mus_params.nonce,
        },
        dataType: "json",
        success: function (response) {
          if (response.success) {
            showNotification(
              response.data.message || "  Logged out successfully!",
              "success"
            );
            setTimeout(() => {
              window.location.href = response.data.redirect_url;
            }, 1000);
          }
        },
        error: function () {
          window.location.href = mus_params.home_url + "/login";
        },
      });
    };

    // ================================================
    //   TAB NAVIGATION
    // ================================================

    function activateTabFromHash() {
      let hash = window.location.hash || "#profile";

      const $tabLink = $('[data-bs-target="' + hash + '"]');
      const $tabPane = $(hash);

      if ($tabLink.length && $tabPane.length) {
        $(".list-group-item").removeClass("active");
        $(".tab-pane").removeClass("show active");

        $tabLink.addClass("active");
        $tabPane.addClass("show active");

        scrollToTop();
      }
    }

    $(".list-group-item[data-bs-toggle='tab']").on("click", function (e) {
      e.preventDefault();
      const target = $(this).attr("data-bs-target");
      history.replaceState(null, null, target);
      activateTabFromHash();
    });

    $(window).on("hashchange", function () {
      activateTabFromHash();
    });

    activateTabFromHash();
  }
})(jQuery);
