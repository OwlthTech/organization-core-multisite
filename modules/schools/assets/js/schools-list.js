/**
 * Schools Module - Schools List Page Handler
 *
 * Handles edit/delete operations for schools list
 * Uses WordPress localized data via schoolsListData object
 *
 * @package Organization_Core
 * @subpackage Modules/Schools
 * @since 1.0.0
 */

(function ($) {
  "use strict";

  // Wait for DOM ready
  $(document).ready(function () {
    initSchoolsList();
  });

  function initSchoolsList() {
    // Check if localized data exists
    if (typeof schoolsListData === "undefined") {
      console.error(
        "schoolsListData is not defined. Ensure wp_localize_script is called."
      );
      return;
    }

    // Make functions globally available for onclick handlers
    window.editSchool = editSchool;
    window.deleteSchool = deleteSchool;
  }

  /**
   * Edit school - Navigate to edit page
   */
  function editSchool(schoolId) {
    if (!schoolId) {
      alert("❌ School ID is missing");
      return;
    }

    const editUrl =
      schoolsListData.home_url +
      "/my-account/schools/add?edit=" +
      schoolId +
      "&ref=" +
      encodeURIComponent(schoolsListData.current_url);

    window.location.href = editUrl;
  }

  /**
   * Delete school - AJAX request
   */
  function deleteSchool(schoolId) {
    if (!schoolId) {
      alert("❌ School ID is missing");
      return;
    }

    if (
      !confirm(
        "Are you sure you want to delete this school?\n\nThis action cannot be undone."
      )
    ) {
      return;
    }

    // Show loading state
    const $deleteBtn = $("[onclick*=\"deleteSchool('" + schoolId + "')\"]");
    const originalHtml = $deleteBtn.html();
    $deleteBtn
      .prop("disabled", true)
      .html('<i class="fas fa-spinner fa-spin"></i>');

    // Send AJAX request
    $.ajax({
      url: schoolsListData.ajax_url,
      type: "POST",
      dataType: "json",
      data: {
        action: "mus_delete_school",
        nonce: schoolsListData.nonce,
        school_id: schoolId, // ✅ Must be numeric
      },
      success: function (response) {
        if (response.success) {
          alert("✅ " + response.data.message);

          // ✅ FIX: Use data-school-id (not data-school-key)
          const $row = $('tr[data-school-id="' + schoolId + '"]');
          if ($row.length > 0) {
            $row.fadeOut(300, function () {
              $(this).remove();

              // Check if table is now empty
              if ($("tbody tr").length === 0) {
                setTimeout(() => location.reload(), 500);
              }
            });
          } else {
            console.warn("⚠️ Row not found for ID:", schoolId);
            location.reload(); // Fallback: reload page
          }
        } else {
          alert("❌ " + response.data.message);
          $deleteBtn.prop("disabled", false).html(originalHtml);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", {
          status: status,
          error: error,
          response: xhr.responseText,
        });
        alert("❌ Failed to delete school.\nError: " + error);
        $deleteBtn.prop("disabled", false).html(originalHtml);
      },
    });
  }
})(jQuery);
