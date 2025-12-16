document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("mus-login-form");
  if (!form) return;

  console.log("✅ Login form initialized");

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = document.getElementById("login-submit-btn");
    const spinner = btn.querySelector(".spinner-border");
    const msgContainer = document.getElementById("login-messages");
    const username = document.getElementById("mus_username");
    const password = document.getElementById("mus_password");

    // Clear messages
    msgContainer.innerHTML = "";

    // Validate
    if (!username.value.trim() || !password.value.trim()) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">Please enter username and password</div>';
      return;
    }

    // Show loading
    btn.disabled = true;
    spinner.classList.remove("d-none");

    const formData = new FormData(form);

    console.log("📋 Sending login request...");
    console.log("Action:", formData.get("action"));
    console.log("Username:", formData.get("username"));
    console.log("Nonce:", formData.get("nonce"));

    const ajaxUrl =
      typeof window.mus_params !== "undefined"
        ? window.mus_params.ajax_url
        : "/wp-admin/admin-ajax.php";

    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((response) => {
        console.log("📡 Response status:", response.status);
        return response.json();
      })
      .then((data) => {
        console.log("📥 Response data:", data);

        if (data.success) {
          console.log("✅ LOGIN SUCCESSFUL");
          msgContainer.innerHTML =
            '<div class="alert alert-success">' + data.data.message + "</div>";

          setTimeout(() => {
            window.location.href = data.data.redirect_url;
          }, 1500);
        } else {
          console.error("❌ LOGIN FAILED:", data.data?.message);

          // ✅ NEW: Display HTML (for the link)
          msgContainer.innerHTML =
            '<div class="alert alert-danger">' +
            (data.data?.message || "Login failed") +
            "</div>";
          password.value = "";
        }
      })
      .catch((error) => {
        console.error("❌ Network error:", error);
        msgContainer.innerHTML =
          '<div class="alert alert-danger">Network error. Please try again.</div>';
      })
      .finally(() => {
        btn.disabled = false;
        spinner.classList.add("d-none");
      });
  });
});
