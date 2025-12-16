/**
 * Booking System - Production Ready
 * @version 12.0.0 - WITH IMPROVED TOOLBAR & REVIEW MODAL
 * @package Organization Core Bookings
 */

(function ($) {
  "use strict";

  // ============================================
  // CONFIGURATION
  // ============================================

  const config = {
    packageId: window.packageId || 0,
    totalSteps: window.totalSteps || 6,
    schoolRequired: window.schoolRequired || false,
    packageTitle: window.packageTitle || "",
    packageSlug: window.packageSlug || "",
  };

  const serverConfig = {
    ajaxUrl: window.ajaxurl || "/wp-admin/admin-ajax.php",
    nonce: window.bookingNonce || "",
    homeUrl: window.location.origin,
  };

  // ============================================
  // DYNAMIC STEP MAPPING
  // ============================================

  let stepMap = {};

  function buildStepMap() {
    let currentStep = 1;

    if (config.schoolRequired) {
      stepMap.school = currentStep++;
    }

    stepMap.location = currentStep++;
    stepMap.date = currentStep++;
    stepMap.parks = currentStep++;
    stepMap.details = currentStep++;
    stepMap.ensemble = currentStep++;
  }

  // ============================================
  // GLOBAL STATE
  // ============================================

  let currentDraftId = null;
  let wizard = null;

  // ============================================
  // 1. INITIALIZATION
  // ============================================

  $(document).ready(function () {
    buildStepMap();
    initializeWizard();
    loadExistingDraft();
    setupEventHandlers();
    setupMealVouchersHandler();
    customizeWizardButtons();

    // Initialize location handler
    if (typeof window.bookingLocationHandler !== "undefined") {
      window.bookingLocationHandler.init();
    }
  });

  // ============================================
  // 2. WIZARD INITIALIZATION - UPDATED TOOLBAR
  // ============================================

  function initializeWizard() {
    wizard = $("#smartwizard").smartWizard({
      selected: 0,
      autoAdjustHeight: true,
      theme: "dots",
      transition: { animation: "fade", speed: "400" },
      toolbar: {
        showNextButton: true,
        showPreviousButton: true,
        position: "bottom",
        extraHtml: `
          <button class="btn bg-theme3 text-white" id="review-booking-btn" 
                  style="display:none; order: 1;">
              <i class="fas fa-check-circle me-2"></i>Review Registration
          </button>
          <div class="float-start text-muted me-3 mt-2" style="order:0;">
              Step <span id="sw-current-step">1</span> of <span id="sw-total-step">${config.totalSteps}</span>
          </div>
        `,
      },
    });
  }

  function customizeWizardButtons() {
    setTimeout(() => {
      $(".sw-btn-next").addClass("bg-theme");
      $(".sw-btn-prev").addClass("bg-theme");
    }, 100);
  }

  // ============================================
  // 3. EVENT HANDLERS
  // ============================================

  function setupEventHandlers() {
    $("#smartwizard").on("leaveStep", handleStepLeave);
    $("#smartwizard").on("showStep", handleStepShow);

    $(document).on("click", "#review-booking-btn", showReviewModal);
    $(document).on("click", "#confirm-booking-btn", handleFinalSubmit);
  }
  // ============================================
  // OTHER PARK HANDLER
  // ============================================

  /**
   * Handle "Other Park" checkbox toggle
   */
  $(document).on("change", "#park-other", function () {
    const $container = $("#other-park-container");
    const $input = $("#other_park_name");

    if (this.checked) {
      $container.slideDown(300);
      $input.prop("required", true);
    } else {
      $container.slideUp(300);
      $input.prop("required", false).val("");
    }
  });

  /**
   * Restore "Other Park" state on page load
   */
  function restoreOtherParkState() {
    const $otherCheckbox = $("#park-other");
    const $container = $("#other-park-container");
    const $input = $("#other_park_name");

    if ($otherCheckbox.is(":checked")) {
      $container.show();
      $input.prop("required", true);
    } else {
      $container.hide();
      $input.prop("required", false);
    }
  }

  // ============================================
  // RADIO BUTTON HANDLERS
  // ============================================

  $(document).on("change", ".school-radio", function () {
    const schoolId = $(this).val();
    $(".school-options .option-card").removeClass("selected");
    $(this).closest(".option-card").addClass("selected");
  });

  $(document).on("change", ".location-radio", function () {
    const locationId = $(this).val();
    $(".location-options .option-card").removeClass("selected");
    $(this).closest(".option-card").addClass("selected");

    if (typeof window.bookingLocationHandler !== "undefined") {
      window.bookingLocationHandler.handleLocationChange(locationId);
    }
  });

  $(document).on("click", ".option-card", function (e) {
    if ($(e.target).is('input[type="radio"]') || $(e.target).is("label")) {
      return;
    }
    $(this).find('input[type="radio"]').prop("checked", true).trigger("change");
  });

  function restoreSchoolSelection() {
    const sessionData = sessionStorage.getItem(`form-${stepMap.school}`);
    if (sessionData) {
      try {
        const data = JSON.parse(sessionData);
        if (data.school_id) {
          const $radio = $(`.school-radio[value="${data.school_id}"]`);
          $radio.prop("checked", true);
          $radio.closest(".option-card").addClass("selected");
        }
      } catch (e) {
        console.error("Error restoring school:", e);
      }
    }
  }

  function restoreLocationSelection() {
    const sessionData = sessionStorage.getItem(`form-${stepMap.location}`);
    if (sessionData) {
      try {
        const data = JSON.parse(sessionData);
        if (data.location_id) {
          const $radio = $(`.location-radio[value="${data.location_id}"]`);
          $radio.prop("checked", true);
          $radio.closest(".option-card").addClass("selected");

          if (typeof window.bookingLocationHandler !== "undefined") {
            window.bookingLocationHandler.handleLocationChange(
              data.location_id
            );
          }
        }
      } catch (e) {
        console.error("Error restoring location:", e);
      }
    }
  }
  // ============================================
  // IMPROVED SCROLL FUNCTION
  // ============================================

  function scrollToSection() {
    const $wizard = $(".booking-section");
    if ($wizard.length) {
      const offset = $wizard.offset().top - 100; // 100px buffer for fixed headers
      $("html, body").animate(
        {
          scrollTop: offset > 0 ? offset : 0,
        },
        300
      );
      console.log("📜 Scrolled to wizard");
    } else {
      // Fallback to top
      $("html, body").animate({ scrollTop: 0 }, 300);
    }
  }
  // ============================================
  // 4. STEP LEAVE HANDLER
  // ============================================

  function handleStepLeave(
    e,
    anchorObject,
    currentStepIndex,
    nextStepIndex,
    stepDirection
  ) {
    if (stepDirection === "forward") {
      const stepNumber = currentStepIndex + 1;

      if (!validateStep(stepNumber)) {
        e.preventDefault();
        return false;
      }

      saveStepToSession(stepNumber);
      saveDraftToDatabase();
    }

    scrollToSection();
    return true;
  }

  // ============================================
  // 5. STEP SHOW HANDLER - UPDATED WITH COUNTER
  // ============================================

  function handleStepShow(
    e,
    anchorObject,
    stepIndex,
    stepDirection,
    stepPosition
  ) {
    const stepNumber = stepIndex + 1;

    // Update step counter
    $("#sw-current-step").text(stepNumber);
    $("#sw-total-step").text(config.totalSteps);

    // Show/hide review button on last step
    if (stepNumber === config.totalSteps) {
      $("#review-booking-btn").show();
      $(".sw-btn-next").hide();
    } else {
      $("#review-booking-btn").hide();
      $(".sw-btn-next").show();
    }

    restoreStepFromSession(stepNumber);

    if (stepNumber === stepMap.school) {
      restoreSchoolSelection();
    }

    if (stepNumber === stepMap.location) {
      restoreLocationSelection();
    }

    // ✅ NEW: Restore parks step (includes "Other Park" state)
    if (stepNumber === stepMap.parks) {
      restoreOtherParkState();
    }

    if (stepNumber === stepMap.details) {
      restoreMealVoucherState();
    }

    // $("html, body").animate({ scrollTop: 0 }, 300);
  }

  // ============================================
  // 6. VALIDATION
  // ============================================

  function validateStep(stepNumber) {
    const $form = $(`#form-${stepNumber}`);

    $form.find(".validation-error").hide();
    $form.find(".is-invalid").removeClass("is-invalid");

    let isValid = true;
    const $requiredFields = $form.find("[required]");

    $requiredFields.each(function () {
      const $field = $(this);
      const value = $field.val();

      if (
        !value ||
        value === "" ||
        (Array.isArray(value) && value.length === 0)
      ) {
        isValid = false;
        $field.addClass("is-invalid");

        const $error = $field.siblings(".validation-error");
        if ($error.length) {
          $error.text("This field is required").show();
        }
      }
    });

    if (stepNumber === stepMap.ensemble) {
      const ensemblesData = sessionStorage.getItem(`form-${stepNumber}`);
      let ensembles = [];

      if (ensemblesData) {
        try {
          const parsed = JSON.parse(ensemblesData);
          ensembles = parsed.ensembles || [];
        } catch (e) {
          console.error("Error parsing ensembles:", e);
        }
      }

      if (ensembles.length === 0) {
        alert("Please add at least one ensemble before proceeding");
        isValid = false;
      }
    }

    if (isValid) {
      console.log(`✅ Step ${stepNumber} validated`);
    } else {
      alert("Please complete all required fields");
    }

    return isValid;
  }

  // ============================================
  // 7. SESSION STORAGE
  // ============================================

  function saveStepToSession(stepNumber) {
    const $form = $(`#form-${stepNumber}`);
    const formData = {};

    $form.find("input, select, textarea").each(function () {
      const $field = $(this);
      const name = $field.attr("name");
      const type = $field.attr("type");

      if (!name) return;
      if (name.includes("[]")) return;
      if (name.includes("park_meal_options")) return;

      if (type === "checkbox") {
        formData[name] = $field.is(":checked") ? 1 : 0;
      } else if (type === "radio") {
        if ($field.is(":checked")) {
          formData[name] = $field.val();
        }
      } else {
        formData[name] = $field.val();
      }
    });

    if (stepNumber === stepMap.parks) {
      const parks = [];
      $form.find('input[name="parks[]"]:checked').each(function () {
        parks.push($(this).val());
      });
      formData.parks = parks;
    }

    if (stepNumber === stepMap.details) {
      const parkMealOptions = {};
      $(".park-meal-select").each(function () {
        const $select = $(this);
        const parkId = $select.data("park-id");
        const value = $select.val();
        if (value) {
          parkMealOptions[parkId] = value;
        }
      });
      if (Object.keys(parkMealOptions).length > 0) {
        formData.park_meal_options = parkMealOptions;
      }
      console.log("🍽️ Park meal options saved:", parkMealOptions);
    }

    sessionStorage.setItem(`form-${stepNumber}`, JSON.stringify(formData));
  }

  function restoreStepFromSession(stepNumber) {
    const sessionData = sessionStorage.getItem(`form-${stepNumber}`);

    if (!sessionData) {
      console.log(`No session data for step ${stepNumber}`);
      return;
    }

    try {
      const formData = JSON.parse(sessionData);
      const $form = $(`#form-${stepNumber}`);

      Object.keys(formData).forEach(function (name) {
        const value = formData[name];
        const $field = $form.find(`[name="${name}"]`);

        if ($field.length) {
          const type = $field.attr("type");

          if (type === "checkbox") {
            $field.prop("checked", value == 1);
          } else if (type === "radio") {
            $form
              .find(`[name="${name}"][value="${value}"]`)
              .prop("checked", true);
          } else {
            $field.val(value);
          }
        }
      });

      if (
        stepNumber === stepMap.parks &&
        formData.parks &&
        Array.isArray(formData.parks)
      ) {
        formData.parks.forEach(function (park) {
          $form
            .find(`input[name="parks[]"][value="${park}"]`)
            .prop("checked", true);
        });
      }
    } catch (e) {
      console.error(`Error restoring step ${stepNumber}:`, e);
    }
  }

  // ============================================
  // 8. DRAFT SAVE TO DATABASE
  // ============================================

  function saveDraftToDatabase() {
    const allData = collectAllFormData();

    $.ajax({
      url: serverConfig.ajaxUrl,
      method: "POST",
      data: {
        action: "save_booking_draft",
        nonce: serverConfig.nonce,
        package_id: config.packageId,
        draft_id: currentDraftId,
        step_data: allData,
      },
      success: function (response) {
        if (response.success) {
          currentDraftId = response.data.draft_id;
        } else {
          console.error("❌ Draft save failed:", response.data.message);
        }
      },
      error: function (xhr, status, error) {
        console.error("❌ AJAX error:", error);
      },
    });
  }

  function loadExistingDraft() {
    $.ajax({
      url: serverConfig.ajaxUrl,
      method: "POST",
      data: {
        action: "get_booking_draft",
        nonce: serverConfig.nonce,
        package_id: config.packageId,
      },
      success: function (response) {
        if (response.success) {
          currentDraftId = response.data.draft_id;
        } else {
          console.log("ℹ️ No existing draft");
        }
      },
      error: function (xhr, status, error) {
        console.log("ℹ️ No draft found:", error);
      },
    });
  }

  // ============================================
  // 9. COLLECT ALL FORM DATA
  // ============================================

  function collectAllFormData() {
    const allData = {};

    for (let i = 1; i <= config.totalSteps; i++) {
      const sessionData = sessionStorage.getItem(`form-${i}`);
      if (sessionData) {
        try {
          const stepData = JSON.parse(sessionData);
          Object.assign(allData, stepData);
        } catch (e) {
          console.error(`Error parsing form-${i}:`, e);
        }
      }
    }

    if (allData.parks && Array.isArray(allData.parks)) {
      console.log("✅ Parks (array):", allData.parks);
    } else {
      allData.parks = [];
    }

    if (
      allData.park_meal_options &&
      typeof allData.park_meal_options === "object"
    ) {
      console.log("✅ Park meal options (object):", allData.park_meal_options);
    } else {
      allData.park_meal_options = {};
    }

    allData.package_id = config.packageId;

    return allData;
  }

  // ============================================
  // 10. MEAL VOUCHERS HANDLER
  // ============================================

  function setupMealVouchersHandler() {
    $(document).on("change", "#include_meal_vouchers", function () {
      const isChecked = $(this).is(":checked");
      const $mealSection = $("#meal_options_section");

      if (isChecked) {
        $mealSection.slideDown(300);

        setTimeout(function () {
          buildParkMealDropdowns();
        }, 350);
      } else {
        $mealSection.slideUp(300);
        $("#meals_per_day").val("");
        $("#park_meal_options_container").html("");
      }
    });
  }

  function restoreMealVoucherState() {
    const $checkbox = $("#include_meal_vouchers");

    if ($checkbox.is(":checked")) {
      $("#meal_options_section").show();
      buildParkMealDropdowns();
    }
  }

  function buildParkMealDropdowns() {
    const $container = $("#park_meal_options_container");
    const parksData = sessionStorage.getItem(`form-${stepMap.parks}`);

    if (!parksData) {
      $container.html("");
      return;
    }

    let selectedParkIds = [];
    try {
      const parsed = JSON.parse(parksData);
      selectedParkIds = parsed.parks || [];
    } catch (e) {
      console.error("Error parsing parks:", e);
      return;
    }

    if (selectedParkIds.length === 0) {
      $container.html("");
      return;
    }

    let html = '<div class="row mt-3">';
    html +=
      '<div class="col-12"><h6 class="text-theme mb-3"><i class="fas fa-utensils me-2"></i>Park-Specific Meal Options</h6></div>';

    selectedParkIds.forEach(function (parkId) {
      const park = window.allParksData.find((p) => p.id == parkId);

      if (!park) {
        console.warn(`Park ${parkId} not found`);
        return;
      }
      html += `
            <div class="col-md-6 mb-3">
                <label for="park_meal_${parkId}" class="form-label">
                    <strong>${park.name}</strong>
                </label>
                <select class="form-control park-meal-select" 
                        name="park_meal_options[${parkId}]" 
                        id="park_meal_${parkId}" 
                        data-park-id="${parkId}">
                    <option value="">Select meal option</option>`;

      if (park.options && typeof park.options === "object") {
        Object.keys(park.options).forEach(function (key) {
          const optionValue = park.options[key];
          html += `<option value="${key}">${optionValue}</option>`;
        });
      }

      html += `
                </select>
                <small class="form-text text-muted">Choose meal package</small>
            </div>`;
    });

    html += "</div>";

    $container.html(html);

    const detailsData = sessionStorage.getItem(`form-${stepMap.details}`);
    if (detailsData) {
      try {
        const parsed = JSON.parse(detailsData);
        if (parsed.park_meal_options) {
          Object.keys(parsed.park_meal_options).forEach(function (parkId) {
            $(`#park_meal_${parkId}`).val(parsed.park_meal_options[parkId]);
          });
        }
      } catch (e) {
        console.error("Error restoring meal options:", e);
      }
    }
  }

  // ============================================
  // 11. REVIEW MODAL - PREVIOUS DETAILED FORMAT
  // ============================================

  function showReviewModal() {
    const allData = collectAllFormData();

    let summaryHtml = '<div class="booking-summary-details">';

    // ✅ School
    if (config.schoolRequired && allData.school_id) {
      const schoolData = window.schoolsData?.find(
        (s) => s.id == allData.school_id
      );
      if (schoolData) {
        summaryHtml += buildSummaryRow(
          "School",
          schoolData.school_name,
          "fa-school",
          "theme"
        );
      }
    }

    // ✅ Location
    if (allData.location_id) {
      const locationData = window.locationsData?.find(
        (l) => l.id == allData.location_id
      );
      if (locationData) {
        summaryHtml += buildSummaryRow(
          "Location",
          locationData.name,
          "fa-map-marker-alt",
          "text-success"
        );
      }
    }

    // ✅ Date
    if (allData.date_selection) {
      try {
        const dateObj = new Date(allData.date_selection + "T00:00:00");
        const dateFormatted = dateObj.toLocaleDateString("en-US", {
          year: "numeric",
          month: "long",
          day: "numeric",
        });
        const dayName = dateObj.toLocaleDateString("en-US", {
          weekday: "long",
        });
        const dateDisplay = `${dateFormatted} (${dayName})`;

        summaryHtml += buildSummaryRow(
          "Date",
          dateDisplay,
          "fa-calendar",
          "theme"
        );
      } catch (e) {
        console.error("❌ Date formatting error:", e);
      }
    }

    // ✅ Parks
    const parkNames = getParkNames(allData.parks || []);
    summaryHtml += buildSummaryRow("Parks", parkNames, "fa-tree", "theme3");

    if (allData.other_park_name && allData.other_park_name.trim()) {
      summaryHtml += buildSummaryRow(
        "Other Park",
        allData.other_park_name,
        "fa-info-circle",
        "text-info"
      );
    }

    // ✅ ADDITIONAL DETAILS HEADER
    summaryHtml +=
      '<div class="mt-4 mb-3"><h6 class="text-theme"><i class="fas fa-info-circle me-2"></i>Additional Details</h6></div>';

    if (allData.total_students) {
      summaryHtml += buildSummaryRow(
        "Total Students",
        allData.total_students,
        "fa-users"
      );
    }

    if (allData.total_chaperones) {
      summaryHtml += buildSummaryRow(
        "Total Chaperones",
        allData.total_chaperones,
        "fa-user-tie"
      );
    }

    if (allData.transportation) {
      const transportText =
        allData.transportation === "own"
          ? "We will provide our own transportation"
          : "Please provide a quote for transportation";
      summaryHtml += buildSummaryRow("Transportation", transportText, "fa-bus");
    }

    // MEAL VOUCHERS SECTION
    if (allData.include_meal_vouchers || allData.meal_vouchers) {
      const mealsPerDay = allData.meals_per_day || 0;
      summaryHtml += buildSummaryRow(
        "Meal Vouchers",
        "Yes" + (mealsPerDay > 0 ? ` - ${mealsPerDay} meals/day` : ""),
        "fa-utensils"
      );

      if (
        allData.park_meal_options &&
        Object.keys(allData.park_meal_options).length > 0
      ) {
        summaryHtml +=
          '<div class="mt-2 mb-2"><h6 class="text-theme"><i class="fas fa-utensils me-2"></i>Park Meal Preferences</h6></div>';

        for (const [parkId, mealValue] of Object.entries(
          allData.park_meal_options
        )) {
          const parkData = window.allParksData?.find((p) => p.id == parkId);

          if (parkData && parkData.options) {
            let mealLabel = mealValue;
            for (const [key, val] of Object.entries(parkData.options)) {
              if (val == mealValue) {
                mealLabel = key;
                break;
              }
            }
            summaryHtml += buildSummaryRow(parkData.name, mealLabel);
          }
        }
      }
    }

    if (allData.lodging_dates && allData.lodging_dates.trim()) {
      summaryHtml += buildSummaryRow(
        "Lodging Dates",
        allData.lodging_dates,
        "fa-calendar-days"
      );
    }

    if (allData.special_notes && allData.special_notes.trim()) {
      summaryHtml += `
    <div class="summary-row border-bottom py-2">
      <div class="mb-2"><strong><i class="fas fa-sticky-note me-2"></i>Special Requests</strong></div>
      <div class="notes-preview bg-light p-2 rounded">
        <small>${allData.special_notes.replace(/\n/g, "<br>")}</small>
      </div>
    </div>
  `;
    }

    // ✅ Ensembles Summary
    const ensemblesData = sessionStorage.getItem(`form-${stepMap.ensemble}`);
    if (ensemblesData) {
      try {
        const parsed = JSON.parse(ensemblesData);
        const ensembles = parsed.ensembles || [];
        if (ensembles.length > 0) {
          summaryHtml +=
            '<div class="mt-4 mb-3"><h6 class="text-theme"><i class="fas fa-music me-2"></i>Ensembles</h6></div>';

          ensembles.forEach(function (ensemble, index) {
            summaryHtml += `
              <div class="summary-row border-bottom py-2">
                <div class="mb-2"><strong>Ensemble ${index + 1}: ${
              ensemble.ensemble_name
            }</strong></div>
                <div style="margin-left: 20px;">
                  <small class="d-block">Grade: <strong>${
                    ensemble.ensemble_grade
                  }</strong></small>
                  <small class="d-block">Type: <strong>${
                    ensemble.ensemble_type
                  }</strong></small>
                  <small class="d-block">Students: <strong>${
                    ensemble.ensemble_students
                  }</strong></small>
                  <small class="d-block">Director: <strong>${
                    ensemble.director_prefix
                  } ${ensemble.director_first_name} ${
              ensemble.director_last_name
            }</strong></small>
                  <small class="d-block">Email: <strong>${
                    ensemble.director_email
                  }</strong></small>
                </div>
              </div>
            `;
          });
        }
      } catch (e) {
        console.error("Error processing ensembles for review:", e);
      }
    }

    summaryHtml += "</div>";

    $("#booking-summary-content").html(summaryHtml);
    $("#modal-terms-agreement").prop("checked", false);
    $("#modal-terms-error").hide();
    $("#confirmModal").modal("show");
  }

  function buildSummaryRow(label, value, icon = "", iconClass = "") {
    const iconHtml = icon
      ? `<i class="fas ${icon} me-2 ${iconClass}"></i>`
      : "";
    return `
      <div class="summary-row d-flex justify-content-between align-items-center border-bottom py-2">
        <span class="summary-label">${iconHtml}<strong>${label}</strong></span>
        <span class="summary-value text-end">${value}</span>
      </div>
    `;
  }

  function getParkNames(parkIds) {
    if (!parkIds || parkIds.length === 0) {
      return "None";
    }

    const names = parkIds.map((id) => {
      const park = window.allParksData?.find((p) => p.id == id);
      return park ? park.name : id;
    });

    return names.join(", ");
  }

  // ============================================
  // 12. FINAL SUBMISSION
  // ============================================

  function handleFinalSubmit() {
    if (!$("#modal-terms-agreement").is(":checked")) {
      $("#modal-terms-error").text("You must agree to terms").show();
      return;
    }

    const allData = collectAllFormData();
    $.ajax({
      url: serverConfig.ajaxUrl,
      method: "POST",
      data: {
        action: "create_booking",
        nonce: serverConfig.nonce,
        draft_id: currentDraftId,
        ...allData,
      },
      success: function (response) {
        if (response.success) {
          $("#confirmModal").modal("hide");
          $("#successModal").modal("show");

          setTimeout(function () {
            sessionStorage.clear();
            if (response.data.redirect_url) {
              window.location.href = response.data.redirect_url;
            }
          }, 1000);
        } else {
          alert("Error: " + response.data.message);
        }
      },
      error: function (xhr, status, error) {
        alert("Failed to submit. Please try again.");
      },
    });
  }
})(jQuery);
