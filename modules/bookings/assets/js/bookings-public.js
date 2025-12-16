// /**
//  * Bookings Public JavaScript
//  *
//  * Complete working version with all functions
//  * Handles bookings wizard, form submission, and dynamic loading
//  *
//  * @package Organization_Core
//  * @subpackage Bookings/Public
//  */

// (function ($) {
//   "use strict";

//   // ==================== CONFIGURATION ====================
//   const params =
//     typeof bookingsParams !== "undefined"
//       ? bookingsParams
//       : {
//           ajaxurl: "/wp-admin/admin-ajax.php",
//           nonce: "",
//           user_logged_in: false,
//           user_id: 0,
//           home_url: window.location.origin,
//           is_multisite: false,
//           debug: false,
//         };

//   const config = {
//     packageId: window.packageId || 0,
//     totalSteps: window.totalSteps || 5,
//     schoolRequired: window.schoolRequired || false,
//     packageTitle: window.packageTitle || "",
//     packageSlug: window.packageSlug || "",
//   };

//   // ==================== INITIALIZATION ====================
//   $(document).ready(function () {
//     console.log("🔵 Bookings Public: Initializing...");
//     console.log("AJAX URL:", params.ajaxurl);
//     console.log("Nonce:", params.nonce);

//     // Initialize wizard
//     initializeSmartWizard();

//     // Customize wizard buttons
//     customizeWizardButtons();

//     // Register event handlers
//     registerAllEventHandlers();

//     // Initialize conditional fields
//     initializeConditionalFields();

//     // Restore form data if available
//     restoreAllFormData();

//     console.log("✅ Bookings Public: Initialization complete");
//   });

//   // ==================== SMARTWIZARD INITIALIZATION ====================
//   function initializeSmartWizard() {
//     console.log("🔧 Initializing SmartWizard...");

//     if ($("#smartwizard").length === 0) {
//       console.warn("⚠️ SmartWizard element not found on page");
//       return;
//     }

//     $("#smartwizard").smartWizard({
//       selected: 0,
//       autoAdjustHeight: true,
//       theme: "dots",
//       transition: {
//         animation: "fade",
//         speed: 400,
//       },
//       toolbar: {
//         showNextButton: true,
//         showPreviousButton: true,
//         position: "bottom",
//       },
//     });

//     console.log("✓ SmartWizard initialized");
//   }

//   // ==================== WIZARD CUSTOMIZATION ====================
//   function customizeWizardButtons() {
//     console.log("🎨 Customizing wizard buttons...");

//     // Find and customize buttons
//     const $nextBtn = $(".sw-btn-next");
//     const $prevBtn = $(".sw-btn-prev");
//     const $submitBtn = $("button[type='submit']");

//     // Add Bootstrap classes
//     $nextBtn.addClass("btn btn-primary").text("Next →");
//     $prevBtn.addClass("btn btn-secondary").text("← Previous");

//     // On last step, change Next to Submit
//     $(document).on("click", ".sw-btn-next", function () {
//       setTimeout(function () {
//         const activeStep = $(".sw-navbar .nav-item.active").index();
//         if (activeStep === config.totalSteps - 1) {
//           $nextBtn.text("Submit Booking").addClass("btn-success");
//         } else {
//           $nextBtn.text("Next →").removeClass("btn-success");
//         }
//       }, 100);
//     });

//     console.log("✓ Wizard buttons customized");
//   }

//   // ==================== EVENT HANDLERS ====================
//   function registerAllEventHandlers() {
//     console.log("📌 Registering event handlers...");

//     // Booking form submission
//     $(document).on("submit", "form", function (e) {
//       // Only handle booking forms
//       if (
//         $(this).closest("#smartwizard").length > 0 ||
//         $(this).data("form-type") === "booking"
//       ) {
//         handleBookingSubmit.call(this, e);
//       }
//     });

//     // Location changes
//     $(document).on("change", 'input[name="locationid"]', handleLocationChange);

//     // Date changes
//     $(document).on("change", 'input[name="date"]', handleDateChange);

//     // Park selection
//     $(document).on("change", ".park-checkbox", handleParkChange);

//     // School selection
//     $(document).on("change", 'input[name="schoolkey"]', handleSchoolChange);

//     // Other park checkbox
//     $(document).on(
//       "change",
//       'input[name="parks[]"][value="other"]',
//       function () {
//         if ($(this).is(":checked")) {
//           $("#other-park-container").show();
//         } else {
//           $("#other-park-container").hide();
//         }
//       }
//     );

//     console.log("✓ Event handlers registered");
//   }

//   // ==================== BOOKING FORM SUBMISSION ====================
//   function handleBookingSubmit(e) {
//     e.preventDefault();

//     console.log("📤 Submitting booking...");

//     // Validate required fields first
//     const packageId = config.packageId || $('input[name="packageid"]').val();
//     const locationId = $('input[name="locationid"]:checked').val();
//     const date = $('input[name="date"]:checked').val();
//     const totalStudents = parseInt($("#totalstudents").val()) || 0;

//     if (!packageId || !locationId || !date || !totalStudents) {
//       showError("❌ Please fill in all required fields.");
//       return false;
//     }

//     // Gather form data
//     const formData = {
//       action: "create_booking",
//       nonce: params.nonce,
//       package_id: packageId,
//       school_key: $('input[name="schoolkey"]:checked').val() || "",
//       location_id: locationId,
//       date: date,
//       parks: JSON.stringify(getSelectedParks()),
//       other_park_name: $("#otherparkname").val() || "",
//       total_students: totalStudents,
//       total_chaperones: parseInt($("#totalchaperones").val()) || 0,
//       transportation: $('input[name="transportation"]:checked').val() || "own",
//       include_meal_vouchers: $("#includemealvouchers").is(":checked") ? 1 : 0,
//       meals_per_day: parseInt($("#mealsperday").val()) || 0,
//       park_meal_options: JSON.stringify(getParkMealOptions()),
//       lodging_dates: $("#lodgingdates").val() || "",
//       booking_notes: $("#bookingnotes").val() || "",
//     };

//     // Show loading state
//     const $submit = $(this).find('button[type="submit"]');
//     const originalText = $submit.text();
//     $submit.prop("disabled", true).text("Submitting...");

//     console.log("📨 Sending AJAX request to:", params.ajaxurl);
//     console.log("📦 Form data:", formData);

//     // Send AJAX request
//     $.ajax({
//       url: params.ajaxurl,
//       type: "POST",
//       dataType: "json",
//       data: formData,
//       success: function (response) {
//         console.log("✅ Booking response:", response);

//         if (response.success) {
//           showSuccess("✅ Booking confirmed! Redirecting...");

//           // Store booking data
//           if (response.data && response.data.booking_id) {
//             sessionStorage.setItem("last_booking_id", response.data.booking_id);
//           }

//           // Redirect after delay
//           setTimeout(function () {
//             window.location.href = params.home_url + "/my-account/bookings";
//           }, 2000);
//         } else {
//           const errorMsg =
//             response.data && response.data.message
//               ? response.data.message
//               : "Failed to submit booking.";
//           showError("❌ " + errorMsg);
//           $submit.prop("disabled", false).text(originalText);
//         }
//       },
//       error: function (xhr, status, error) {
//         console.error("❌ AJAX Error:", error);
//         console.error("Status:", status);
//         console.error("XHR:", xhr);

//         let errorMsg = "An error occurred. Please try again.";
//         if (
//           xhr.responseJSON &&
//           xhr.responseJSON.data &&
//           xhr.responseJSON.data.message
//         ) {
//           errorMsg = xhr.responseJSON.data.message;
//         }

//         showError("❌ " + errorMsg);
//         $submit.prop("disabled", false).text(originalText);
//       },
//     });

//     return false;
//   }

//   // ==================== LOCATION HANDLER ====================
//   function handleLocationChange() {
//     const locationId = $('input[name="locationid"]:checked').val();
//     console.log("📍 Location changed to:", locationId);

//     if (!locationId) return;

//     // Load dates for this location
//     loadDatesForLocation(locationId);

//     // Load parks for this location
//     loadParksForLocation(locationId);
//   }

//   function loadDatesForLocation(locationId) {
//     const locationConfigs = window.locationConfigs || {};
//     const config = locationConfigs[locationId] || {};
//     const dates = config.dates || [];

//     let html = "";
//     if (dates.length === 0) {
//       html =
//         '<div class="alert alert-warning">No dates available for this location.</div>';
//     } else {
//       dates.forEach(function (dateObj) {
//         const displayDate = formatDate(dateObj.date);
//         html += `
//                     <div class="option-card">
//                         <div class="radio-wrapper">
//                             <input type="radio" class="form-check-input" name="date" value="${escapeHtml(
//                               dateObj.date
//                             )}" id="date-${escapeHtml(dateObj.date)}" required>
//                         </div>
//                         <label class="form-check-label" for="date-${escapeHtml(
//                           dateObj.date
//                         )}">
//                             <strong>${escapeHtml(displayDate)}</strong>
//                             ${
//                               dateObj.note
//                                 ? `<small class="text-muted d-block">${escapeHtml(
//                                     dateObj.note
//                                   )}</small>`
//                                 : ""
//                             }
//                         </label>
//                     </div>
//                 `;
//       });
//     }

//     const $container = $("#date-options-container");
//     if ($container.length > 0) {
//       $container.html(html);
//       console.log("✓ Dates loaded for location", locationId);
//     }
//   }

//   function loadParksForLocation(locationId) {
//     const locationConfigs = window.locationConfigs || {};
//     const config = locationConfigs[locationId] || {};
//     const parks = config.parks || [];

//     let html = "";
//     if (parks.length === 0) {
//       html =
//         '<div class="alert alert-warning">No parks available for this location.</div>';
//     } else {
//       parks.forEach(function (park) {
//         html += `
//                     <div class="form-check park-option mb-3">
//                         <input type="checkbox" class="form-check-input park-checkbox" name="parks[]" value="${
//                           park.id
//                         }" id="park-${park.id}">
//                         <label class="form-check-label" for="park-${park.id}">
//                             <strong>${escapeHtml(park.name)}</strong>
//                         </label>
//                     </div>
//                 `;
//       });
//     }

//     const $container = $("#parks-selection-container");
//     if ($container.length > 0) {
//       $container.html(html);
//       console.log("✓ Parks loaded for location", locationId);
//     }
//   }

//   // ==================== DATE HANDLER ====================
//   function handleDateChange() {
//     console.log("📅 Date changed");
//     // Additional logic if needed
//   }

//   // ==================== PARK HANDLER ====================
//   function handleParkChange() {
//     console.log("🎪 Park selection changed");
//     updateParkMealOptions();
//   }

//   function updateParkMealOptions() {
//     const selectedParks = getSelectedParks();
//     const allParksData = window.allParksData || [];

//     if (selectedParks.length === 0) {
//       $("#parkmealoptionscontainer").html("");
//       return;
//     }

//     let html = "<h4>Meal Options per Park</h4>";

//     selectedParks.forEach(function (parkId) {
//       const park = allParksData.find((p) => p.id == parkId);
//       if (park && park.options) {
//         html += `
//                     <div class="mb-4">
//                         <label class="form-label"><strong>${escapeHtml(
//                           park.name
//                         )} - Meal Option</strong></label>
//                         <select class="form-control park-meal-select" name="park_meal_options[${parkId}]" data-park-id="${parkId}">
//                             <option value="">-- Select Option --</option>
//                 `;

//         if (typeof park.options === "object") {
//           Object.entries(park.options).forEach(function ([label, value]) {
//             html += `<option value="${escapeHtml(value)}">${escapeHtml(
//               label
//             )}</option>`;
//           });
//         }

//         html += `
//                         </select>
//                     </div>
//                 `;
//       }
//     });

//     const $container = $("#parkmealoptionscontainer");
//     if ($container.length > 0) {
//       $container.html(html);
//     }
//   }

//   // ==================== SCHOOL HANDLER ====================
//   function handleSchoolChange() {
//     console.log("🏫 School changed");
//     const selectedSchool = $('input[name="schoolkey"]:checked').val();
//     console.log("Selected school key:", selectedSchool);
//   }

//   // ==================== HELPER FUNCTIONS ====================

//   function getSelectedParks() {
//     const parks = [];
//     $('input.park-checkbox:checked, input[name="parks[]"]:checked').each(
//       function () {
//         const val = $(this).val();
//         if (val && val !== "other") {
//           parks.push(val);
//         }
//       }
//     );
//     return parks;
//   }

//   function getParkMealOptions() {
//     const options = {};
//     $("select.park-meal-select").each(function () {
//       const parkId = $(this).data("park-id");
//       const value = $(this).val();
//       if (value) {
//         options[parkId] = value;
//       }
//     });
//     return options;
//   }

//   function formatDate(dateString) {
//     try {
//       const date = new Date(dateString + "T00:00:00");
//       return date.toLocaleDateString("en-US", {
//         year: "numeric",
//         month: "long",
//         day: "numeric",
//       });
//     } catch (e) {
//       return dateString;
//     }
//   }

//   function escapeHtml(text) {
//     const map = {
//       "&": "&amp;",
//       "<": "&lt;",
//       ">": "&gt;",
//       '"': "&quot;",
//       "'": "&#039;",
//     };
//     return String(text).replace(/[&<>"']/g, (m) => map[m]);
//   }

//   function showSuccess(message) {
//     showAlert(message, "success");
//   }

//   function showError(message) {
//     showAlert(message, "danger");
//   }

//   function showAlert(message, type) {
//     const alertClass = type === "success" ? "alert-success" : "alert-danger";
//     const html = `
//             <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
//                 ${message}
//                 <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
//             </div>
//         `;

//     // Find form and insert alert
//     const $form =
//       $("#smartwizard").length > 0
//         ? $("#smartwizard").closest("form")
//         : $("form").first();

//     if ($form.length > 0) {
//       $form.prepend(html);
//     } else {
//       // Insert at top of page
//       $("body").prepend(html);
//     }

//     // Auto dismiss after 5 seconds
//     setTimeout(function () {
//       $(".alert").fadeOut("slow", function () {
//         $(this).remove();
//       });
//     }, 5000);
//   }

//   function initializeConditionalFields() {
//     console.log("⚙️ Initializing conditional fields...");

//     // Show/hide meal voucher fields based on checkbox
//     $(document).on("change", "#includemealvouchers", function () {
//       const $container = $("#mealsperdaycontainer");
//       if ($container.length > 0) {
//         if ($(this).is(":checked")) {
//           $container.slideDown();
//         } else {
//           $container.slideUp();
//         }
//       }
//     });

//     // Show/hide lodging dates for overnight packages
//     const $lodgingContainer = $("#lodgingdatescontainer");
//     if ($lodgingContainer.length > 0) {
//       const packageTitle = (config.packageTitle || "").toLowerCase();
//       if (
//         packageTitle.includes("overnight") ||
//         packageTitle.includes("lodge")
//       ) {
//         $lodgingContainer.show();
//       } else {
//         $lodgingContainer.hide();
//       }
//     }

//     console.log("✓ Conditional fields initialized");
//   }

//   function restoreAllFormData() {
//     const savedData = sessionStorage.getItem("booking_form_data");
//     if (savedData) {
//       try {
//         const data = JSON.parse(savedData);
//         Object.entries(data).forEach(([key, value]) => {
//           const $field = $(`[name="${key}"]`);
//           if ($field.length > 0) {
//             if ($field.is(":radio, :checkbox")) {
//               $field.filter(`[value="${value}"]`).prop("checked", true);
//             } else if ($field.is("select")) {
//               $field.val(value);
//             } else {
//               $field.val(value);
//             }
//           }
//         });
//         console.log("✓ Form data restored");
//       } catch (e) {
//         console.error("Error restoring form data:", e);
//       }
//     }
//   }

//   // Save form data before page unload
//   $(window).on("beforeunload", function () {
//     const formData = {};
//     $("form")
//       .serializeArray()
//       .forEach(function (field) {
//         formData[field.name] = field.value;
//       });
//     if (Object.keys(formData).length > 0) {
//       sessionStorage.setItem("booking_form_data", JSON.stringify(formData));
//     }
//   });
// })(jQuery);
