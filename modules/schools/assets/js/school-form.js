/**
 * Schools Module - Add/Edit School Form Handler
 *
 * External JavaScript file for form validation, submission, and state management
 * Uses WordPress localized data from PHP via schoolFormData object
 *
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Wait for DOM to be ready
  $(document).ready(function () {
    initSchoolForm();
  });

  function initSchoolForm() {
    // ================================================
    // GET DATA FROM WORDPRESS (via wp_localize_script)
    // ================================================

    // Check if localized data exists
    if (typeof schoolFormData === "undefined") {
      console.error(
        "schoolFormData is not defined. Ensure wp_localize_script is called."
      );
      return;
    }

    // Extract data from PHP
    const formConfig = {
      isEdit: schoolFormData.is_edit === "1",
      editKey: schoolFormData.school_id || "",
      backUrl: schoolFormData.back_url || "/my-account",
      ajaxUrl: schoolFormData.ajax_url,
      nonce: schoolFormData.nonce,
      savedState: schoolFormData.saved_state || "",
      savedCountry: schoolFormData.saved_country || "",
      homeUrl: schoolFormData.home_url,
    };

    console.log("Form initialized with config:", formConfig);

    // ================================================
    // DOM ELEMENT REFERENCES
    // ================================================

    const form = $("#addSchoolForm");
    const saveBtn = $("#saveSchoolBtn");
    const goBackBtn = $("#goBackBtn");
    const countrySelect = $("#school_country");
    const stateSelect = $("#school_state");

    // ================================================
    // VALIDATION PATTERNS
    // ================================================

    const ValidationPatterns = {
      phone: /^\(\d{3}\)\s\d{3}-\d{4}$/,
      email: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
      url: /^https?:\/\/.+/,
      zip: {
        US: /^\d{5}(-\d{4})?$/,
        CA: /^[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d$/,
      },
    };

    // ================================================
    // STATE/COUNTRY DATA
    // ================================================

    const stateData = {
      US: [
        { code: "AL", name: "Alabama" },
        { code: "AK", name: "Alaska" },
        { code: "AZ", name: "Arizona" },
        { code: "AR", name: "Arkansas" },
        { code: "CA", name: "California" },
        { code: "CO", name: "Colorado" },
        { code: "CT", name: "Connecticut" },
        { code: "DE", name: "Delaware" },
        { code: "FL", name: "Florida" },
        { code: "GA", name: "Georgia" },
        { code: "HI", name: "Hawaii" },
        { code: "ID", name: "Idaho" },
        { code: "IL", name: "Illinois" },
        { code: "IN", name: "Indiana" },
        { code: "IA", name: "Iowa" },
        { code: "KS", name: "Kansas" },
        { code: "KY", name: "Kentucky" },
        { code: "LA", name: "Louisiana" },
        { code: "ME", name: "Maine" },
        { code: "MD", name: "Maryland" },
        { code: "MA", name: "Massachusetts" },
        { code: "MI", name: "Michigan" },
        { code: "MN", name: "Minnesota" },
        { code: "MS", name: "Mississippi" },
        { code: "MO", name: "Missouri" },
        { code: "MT", name: "Montana" },
        { code: "NE", name: "Nebraska" },
        { code: "NV", name: "Nevada" },
        { code: "NH", name: "New Hampshire" },
        { code: "NJ", name: "New Jersey" },
        { code: "NM", name: "New Mexico" },
        { code: "NY", name: "New York" },
        { code: "NC", name: "North Carolina" },
        { code: "ND", name: "North Dakota" },
        { code: "OH", name: "Ohio" },
        { code: "OK", name: "Oklahoma" },
        { code: "OR", name: "Oregon" },
        { code: "PA", name: "Pennsylvania" },
        { code: "RI", name: "Rhode Island" },
        { code: "SC", name: "South Carolina" },
        { code: "SD", name: "South Dakota" },
        { code: "TN", name: "Tennessee" },
        { code: "TX", name: "Texas" },
        { code: "UT", name: "Utah" },
        { code: "VT", name: "Vermont" },
        { code: "VA", name: "Virginia" },
        { code: "WA", name: "Washington" },
        { code: "WV", name: "West Virginia" },
        { code: "WI", name: "Wisconsin" },
        { code: "WY", name: "Wyoming" },
      ],
      CA: [
        { code: "AB", name: "Alberta" },
        { code: "BC", name: "British Columbia" },
        { code: "MB", name: "Manitoba" },
        { code: "NB", name: "New Brunswick" },
        { code: "NL", name: "Newfoundland and Labrador" },
        { code: "NS", name: "Nova Scotia" },
        { code: "NT", name: "Northwest Territories" },
        { code: "NU", name: "Nunavut" },
        { code: "ON", name: "Ontario" },
        { code: "PE", name: "Prince Edward Island" },
        { code: "QC", name: "Quebec" },
        { code: "SK", name: "Saskatchewan" },
        { code: "YT", name: "Yukon" },
      ],
    };

    // ================================================
    // PHONE NUMBER AUTO-FORMATTING
    // ================================================

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

    // ================================================
    // POPULATE STATE DROPDOWN
    // ================================================

    function populateStates(country, selectValue = "") {
      stateSelect.html('<option value="">Select State</option>');

      if (stateData[country]) {
        stateData[country].forEach((state) => {
          stateSelect.append(
            $("<option>", {
              value: state.code,
              text: state.name,
            })
          );
        });

        if (selectValue) {
          stateSelect.val(selectValue);
          console.log(`✅ State selected: ${selectValue}`);
        }
      }
    }

    // ================================================
    // REAL-TIME VALIDATION HANDLERS
    // ================================================

    let phoneLastValue = {}; // Track last value for each phone field

    $(".phone-input").on("keydown", function (e) {
      // Store the current value before any changes
      phoneLastValue[this.id] = this.value;
    });

    $(".phone-input").on("input", function (e) {
      const input = this;
      const previousValue = phoneLastValue[this.id] || "";
      const currentValue = input.value;
      const cursorPos = input.selectionStart;

      // Get only digits
      const digitsOnly = currentValue.replace(/\D/g, "");

      // Check if user is DELETING
      if (currentValue.length < previousValue.length) {
        // User deleted - just update with digits
        input.value = digitsOnly;

        // Place cursor at correct position
        input.setSelectionRange(
          Math.max(0, cursorPos - 1),
          Math.max(0, cursorPos - 1)
        );
      } else if (currentValue.length > previousValue.length) {
        // User ADDING - format it
        let formatted = "";

        if (digitsOnly.length === 0) {
          formatted = "";
        } else if (digitsOnly.length <= 3) {
          formatted = digitsOnly;
        } else if (digitsOnly.length <= 6) {
          formatted = "(" + digitsOnly.slice(0, 3) + ") " + digitsOnly.slice(3);
        } else if (digitsOnly.length <= 10) {
          formatted =
            "(" +
            digitsOnly.slice(0, 3) +
            ") " +
            digitsOnly.slice(3, 6) +
            "-" +
            digitsOnly.slice(6, 10);
        }

        input.value = formatted;

        // Smart cursor positioning for additions
        if (digitsOnly.length === 3) {
          // After 3 digits, move after ")"
          input.setSelectionRange(5, 5); // Position after ") "
        } else if (digitsOnly.length === 6) {
          // After 6 digits, move after "-"
          input.setSelectionRange(9, 9); // Position after "-"
        } else {
          // Default: end of input
          input.setSelectionRange(formatted.length, formatted.length);
        }
      }

      // Update the stored value
      phoneLastValue[this.id] = input.value;

      // Real-time validation
      const $this = $(this);
      if (input.value.length > 0) {
        if (ValidationPatterns.phone.test(input.value)) {
          $this.removeClass("is-invalid").addClass("is-valid");
        } else {
          $this.removeClass("is-valid");
          if (input.value.length === 14) {
            $this.addClass("is-invalid");
          }
        }
      } else {
        $this.removeClass("is-valid is-invalid");
      }
    });

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
    // Email validation
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

    // URL validation
    $(".url-input").on("input", function () {
      const $this = $(this);
      if (this.value.length > 0) {
        if (ValidationPatterns.url.test(this.value)) {
          $this.removeClass("is-invalid").addClass("is-valid");
        } else {
          $this.removeClass("is-valid");
        }
      } else {
        $this.removeClass("is-valid is-invalid");
      }
    });

    $(".url-input").on("blur", function () {
      const $this = $(this);
      if (this.value && !this.value.match(/^https?:\/\//)) {
        this.value = "https://" + this.value;
        if (ValidationPatterns.url.test(this.value)) {
          $this.addClass("is-valid").removeClass("is-invalid");
        }
      }
    });

    // ================================================
    // FORM VALIDATION
    // ================================================

    function validateForm() {
      let isValid = true;
      const errors = [];

      // Check required fields
      form.find("[required]").each(function () {
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
            `Invalid phone number format for ${this.id.replace("_", " ")}`
          );
        }
      });

      // Validate email
      const emailInput = $("#director_email");
      if (
        emailInput.val() &&
        !ValidationPatterns.email.test(emailInput.val())
      ) {
        isValid = false;
        emailInput.addClass("is-invalid");
        errors.push("Invalid email address format");
      }

      // Validate URL if provided
      const urlInput = $("#school_website");
      if (urlInput.val() && !ValidationPatterns.url.test(urlInput.val())) {
        isValid = false;
        urlInput.addClass("is-invalid");
        errors.push("Invalid website URL format");
      }

      if (!isValid) {
        showAlert(
          "danger",
          "Please fix the following errors:<br>" + errors.join("<br>")
        );
        // Scroll to first error
        const firstError = form.find(".is-invalid").first();
        if (firstError.length) {
          $("html, body").animate(
            {
              scrollTop: firstError.offset().top - 100,
            },
            500
          );
          firstError.focus();
        }
      }

      return isValid;
    }

    // ================================================
    // EVENT HANDLERS
    // ================================================

    // Back button
    goBackBtn.on("click", function () {
      window.location.href = formConfig.backUrl;
    });

    // Country change - update states
    countrySelect.on("change", function () {
      populateStates(this.value);
    });

    // Form submission
    form.on("submit", function (e) {
      e.preventDefault();

      if (!validateForm()) {
        form.addClass("was-validated");
        return;
      }

      form.addClass("was-validated");
      saveBtn.prop("disabled", true);
      const originalText = saveBtn.html();
      saveBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...');

      const action = formConfig.isEdit
        ? "mus_update_school"
        : "mus_save_school";
      const formData = new FormData(this);
      const params = new URLSearchParams();

      params.append("action", action);
      params.append("nonce", formConfig.nonce);
      params.append("back_url", formConfig.backUrl);

      for (let [key, value] of formData.entries()) {
        if (key !== "action") {
          params.append(key, value);
        }
      }

      $.ajax({
        url: formConfig.ajaxUrl,
        type: "POST",
        data: params.toString(),
        dataType: "json",
        success: function (data) {
          if (data.success) {
            showAlert(
              "success",
              formConfig.isEdit
                ? "School updated successfully!"
                : "School created successfully!"
            );
            setTimeout(function () {
              window.location.href =
                data.data?.redirect ||
                formConfig.backUrl ||
                formConfig.homeUrl + "/my-account/schools";
            }, 1500);
          } else {
            const message =
              data.data?.message ||
              data.data?.errors?.join(", ") ||
              "Error saving school";
            showAlert("danger", message);
            saveBtn.prop("disabled", false).html(originalText);
          }
        },
        error: function (xhr, status, error) {
          console.error("AJAX error:", error);
          showAlert("danger", "Network error: " + error);
          saveBtn.prop("disabled", false).html(originalText);
        },
      });
    });

    // ================================================
    // HELPER FUNCTIONS
    // ================================================

    function showAlert(type, message) {
      const messageDiv = $("#school-form-message");
      messageDiv.attr(
        "class",
        `alert alert-${type} alert-dismissible fade show`
      );
      messageDiv.html(
        `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`
      );
      messageDiv.show();

      setTimeout(function () {
        messageDiv.hide();
      }, 5000);
    }

    // ================================================
    // INITIALIZE FORM
    // ================================================

    function initialize() {
      console.log("Initializing form...");

      if (formConfig.isEdit && formConfig.savedCountry) {
        console.log(
          `Editing mode - Country: ${formConfig.savedCountry}, State: ${formConfig.savedState}`
        );
        countrySelect.val(formConfig.savedCountry);
        populateStates(formConfig.savedCountry, formConfig.savedState);
      } else if (!formConfig.isEdit && countrySelect.val()) {
        console.log(`Creating mode with country: ${countrySelect.val()}`);
        populateStates(countrySelect.val());
      } else {
        console.log("No initial country - waiting for user selection");
      }
    }

    // Run initialization
    initialize();
  }
})(jQuery);
