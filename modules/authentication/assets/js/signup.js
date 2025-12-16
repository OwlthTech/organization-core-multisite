document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("mus-signup-form");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = document.getElementById("signup-submit-btn");
    const spinner = btn.querySelector(".spinner-border");
    const msgContainer = document.getElementById("signup-messages");
    const username = document.getElementById("mus_signup_username");
    const email = document.getElementById("mus_signup_email");
    const password = document.getElementById("mus_signup_password");
    const confirm = document.getElementById("mus_signup_confirm");
    const agree = document.getElementById("mus_agree_terms");

    msgContainer.innerHTML = "";

    // Validate
    if (!username.value.trim()) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">Username is required</div>';
      return;
    }

    if (!email.value.trim()) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">Email is required</div>';
      return;
    }

    if (password.value !== confirm.value) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">Passwords do not match</div>';
      return;
    }

    if (!agree.checked) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">You must agree to the terms</div>';
      return;
    }

    btn.disabled = true;
    spinner.classList.remove("d-none");

    const formData = new FormData(form);
    const ajaxUrl = window.mus_params?.ajax_url || "/wp-admin/admin-ajax.php";

    fetch(ajaxUrl, {
      method: "POST",
      body: formData,
      credentials: "same-origin",
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.success) {
          msgContainer.innerHTML =
            '<div class="alert alert-success">' + data.data.message + "</div>";
          setTimeout(() => {
            window.location.href = data.data.redirect_url;
          }, 1500);
        } else {
          msgContainer.innerHTML =
            '<div class="alert alert-danger">' +
            (data.data?.message || "Registration failed") +
            "</div>";
        }
      })
      .catch((error) => {
        msgContainer.innerHTML =
          '<div class="alert alert-danger">Network error. Please try again.</div>';
      })
      .finally(() => {
        btn.disabled = false;
        spinner.classList.add("d-none");
      });
  });
});
