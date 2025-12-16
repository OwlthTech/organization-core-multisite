/**
 * Quotes Public JavaScript
 * Handles form submission and validation
 */
(function () {
  "use strict";

  console.log("Quote form script loaded");

  // Wait for DOM to be ready
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initQuoteForm);
  } else {
    initQuoteForm();
  }

  /**
   * ✅ Initialize quote form
   */
  function initQuoteForm() {
    console.log("Initializing quote form...");

    const form = document.getElementById("quoteform");

    if (!form) {
      console.error("Quote form not found!");
      return;
    }

    console.log("Quote form found, attaching event listener");

    form.addEventListener(
      "submit",
      function (event) {
        console.log("Form submit triggered");
        event.preventDefault();
        event.stopPropagation();

        if (form.checkValidity() === true) {
          console.log("Form is valid, submitting...");
          submitQuoteForm(form);
        } else {
          console.log("Form is invalid");
          const firstInvalid = form.querySelector(":invalid");
          if (firstInvalid) {
            console.log("First invalid field:", firstInvalid);
            firstInvalid.focus();
            firstInvalid.scrollIntoView({
              behavior: "smooth",
              block: "center",
            });
          }
        }
        form.classList.add("was-validated");
      },
      false
    );

    console.log("Quote form initialized successfully");
  }

  /**
   * ✅ Submit quote form via AJAX
   */
  function submitQuoteForm(form) {
    console.log("submitQuoteForm called");

    const submitBtn = document.getElementById("submitQuoteBtn");

    if (!submitBtn) {
      console.error("Submit button not found!");
      return;
    }

    const originalText = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML =
      '<i class="fas fa-spinner fa-spin me-2"></i>Sending Request...';

    const formData = new FormData(form);

    // Log form data for debugging
    console.log("Form data being sent:");
    for (let pair of formData.entries()) {
      console.log(pair[0] + ": " + pair[1]);
    }

    // ✅ Append action for AJAX
    formData.append("action", "handle_quote_submission");

    console.log("Sending AJAX request to:", quotesPublicConfig.ajaxUrl);

    fetch(quotesPublicConfig.ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => {
        console.log("Response status:", response.status);

        if (response.status === 403) {
          throw new Error(
            "Security check failed. Please refresh the page and try again."
          );
        }
        if (!response.ok) {
          throw new Error("HTTP error! status: " + response.status);
        }
        return response.text().then((text) => {
          console.log("Raw response:", text);
          try {
            return JSON.parse(text);
          } catch (e) {
            console.error("JSON Parse error:", e);
            console.error("Response text:", text);
            throw new Error("Invalid server response. Please try again.");
          }
        });
      })
      .then((data) => {
        console.log("Parsed response:", data);

        if (data.success) {
          console.log("Success! Quote submitted");
          showSuccessMessage(data.data.message);

          form.reset();
          form.classList.remove("was-validated");
          window.scrollTo({
            top: 0,
            behavior: "smooth",
          });
        } else {
          console.error("Server returned error:", data.data);
          showErrorMessage(
            data.data.message ||
              "Failed to submit quote request. Please try again."
          );
        }
      })
      .catch((error) => {
        console.error("Fetch error:", error);
        showErrorMessage(error.message);
      })
      .finally(() => {
        console.log("Request complete");
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      });
  }

  /**
   * ✅ Show success message modal
   */
  function showSuccessMessage(message) {
    console.log("Showing success message");

    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      const modalHTML = `
                <div class="modal fade" id="successQuoteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-success text-white border-0">
                                <h5 class="modal-title text-white">
                                    <i class="fas fa-check-circle me-2"></i>Success!
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-5">
                                <i class="fas fa-check-circle text-success mb-4" style="font-size: 5rem;"></i>
                                <h4 class="mb-4">${message}</h4>
                                <p class="text-muted mb-0 mt-3">
                                    Our team will contact you within 24 hours to discuss your trip details.
                                </p>
                            </div>
                            <div class="modal-footer border-0 justify-content-center pb-4">
                                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
                                <a href="${quotesPublicConfig.homeUrl}" class="nir-btn px-4">Back to Home</a>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      const existingModal = document.getElementById("successQuoteModal");
      if (existingModal) existingModal.remove();

      document.body.insertAdjacentHTML("beforeend", modalHTML);
      const modal = new bootstrap.Modal(
        document.getElementById("successQuoteModal")
      );
      modal.show();

      document
        .getElementById("successQuoteModal")
        .addEventListener("hidden.bs.modal", function () {
          this.remove();
        });
    } else {
      console.warn("Bootstrap Modal not available, using alert");
      alert(message);
      window.location.href = quotesPublicConfig.homeUrl;
    }
  }

  /**
   * ✅ Show error message modal
   */
  function showErrorMessage(message) {
    console.log("Showing error message:", message);

    if (typeof bootstrap !== "undefined" && bootstrap.Modal) {
      const modalHTML = `
                <div class="modal fade" id="errorQuoteModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-danger text-white border-0">
                                <h5 class="modal-title text-white">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Error
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center py-5">
                                <i class="fas fa-exclamation-circle text-danger mb-4" style="font-size: 4rem;"></i>
                                <div class="alert alert-danger mx-4">${message}</div>
                                <p class="text-muted mb-0">
                                    If the problem persists, please contact us directly.
                                </p>
                            </div>
                            <div class="modal-footer border-0 justify-content-center pb-4">
                                <button type="button" class="btn btn-primary px-4" data-bs-dismiss="modal">Try Again</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

      const existingModal = document.getElementById("errorQuoteModal");
      if (existingModal) existingModal.remove();

      document.body.insertAdjacentHTML("beforeend", modalHTML);
      const modal = new bootstrap.Modal(
        document.getElementById("errorQuoteModal")
      );
      modal.show();

      document
        .getElementById("errorQuoteModal")
        .addEventListener("hidden.bs.modal", function () {
          this.remove();
        });
    } else {
      console.warn("Bootstrap Modal not available, using alert");
      alert("Error: " + message);
    }
  }
})();
