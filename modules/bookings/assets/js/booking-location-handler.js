/**
 * Booking Location Handler - Generates dynamic date/parks dropdowns
 * FIXED: Uses correct field name 'date_selection' instead of 'date'
 * FIXED: Shows day names with dates (Wednesday, January 15, 2025)
 */

(function ($) {
  "use strict";

  let lastSelectedLocationId = null;

  $(document).ready(function () {
    // Listen for location selection
    $(document).on("change", 'input[name="location_id"]', function () {
      const locationId = $(this).val();
      console.log("📍 Location selected:", locationId);
      handleLocationChange(locationId);
    });
  });

  function handleLocationChange(locationId) {
    if (!locationId || !window.locationConfigs) {
      console.warn("❌ Location configs not available");
      return;
    }

    lastSelectedLocationId = locationId;

    const config = window.locationConfigs[locationId];
    if (!config) {
      console.warn("⚠️ No config for location:", locationId);
      return;
    }

    console.log("🗂️ Config for location:", config);

    // Build dates dropdown
    buildDateOptions(config.dates);

    // Build parks dropdown
    buildParksOptions(config.parks);
  }

  // ✅ FIXED: buildDateOptions with day name support
  function buildDateOptions(dates) {
    console.log("📅 Building date options:", dates);

    let datesHtml = '<div class="date-options">';

    if (!dates || dates.length === 0) {
      datesHtml +=
        '<div class="alert alert-warning">No dates available for this location.</div>';
      datesHtml += "</div>";
      $("#date-options-container").html(datesHtml);
      return;
    }

    dates.forEach((dateObj) => {
      const date = dateObj.date || dateObj;
      const note = dateObj.note || "";

      // ✅ NEW: Use window.formatDateWithDay() or getDayNameFromDate()
      let displayDate;
      if (typeof window.formatDateWithDay === "function") {
        displayDate = window.formatDateWithDay(date);
      } else {
        // Fallback if function not available
        const dayName = getDayNameFromDateFallback(date);
        const dateFormatted = new Date(date + "T00:00:00").toLocaleDateString(
          "en-US",
          { year: "numeric", month: "long", day: "numeric" }
        );
        displayDate = `${dayName}, ${dateFormatted}`;
      }

      datesHtml += `
                <div class="option-card" onclick="selectOption(this, 'date_selection', '${date}')">
                    <div class="radio-wrapper">
                        <input type="radio"
                            class="form-check-input"
                            name="date_selection"
                            value="${date}"
                            id="date-${date}"
                            required>
                    </div>
                    <div class="content-wrapper">
                        <label class="form-check-label w-100" for="date-${date}">
                            <div class="date-display">
                                <strong>${displayDate}</strong>
                                
                                ${
                                  note
                                    ? `<span class="text-muted ms-2">(${note})</span>`
                                    : ""
                                }
                            </div>
                        </label>
                    </div>
                </div>
            `;
    });

    datesHtml += "</div>";
    $("#date-options-container").html(datesHtml);
    console.log(
      "✅ Date options built with day names (e.g., Wednesday, January 15, 2025)"
    );
  }

  // ✅ Fallback function (in case window.getDayNameFromDate not available)
  function getDayNameFromDateFallback(dateString) {
    const date = new Date(dateString + "T00:00:00");
    const dayNames = [
      "Sunday",
      "Monday",
      "Tuesday",
      "Wednesday",
      "Thursday",
      "Friday",
      "Saturday",
    ];
    return dayNames[date.getDay()];
  }

  function buildParksOptions(parks) {
    console.log("🎡 Building parks options:", parks);

    let parksHtml = '<div class="parks-selection">';

    if (!parks || parks.length === 0) {
      parksHtml +=
        '<div class="alert alert-warning">No parks available for this location.</div>';
    } else {
      parks.forEach((park) => {
        parksHtml += `
                    <div class="form-check park-option mb-3">
                        <input type="checkbox" 
                            class="form-check-input park-checkbox"
                            name="parks[]"
                            value="${park.id}"
                            id="park-${park.id}">
                        <label class="form-check-label" for="park-${park.id}">
                            <strong>${park.name}</strong>
                        </label>
                    </div>
                `;
      });
    }

    parksHtml += "</div>";
    $("#parks-selection-container").html(parksHtml);
    console.log("✅ Parks options built");
  }
})(jQuery);
