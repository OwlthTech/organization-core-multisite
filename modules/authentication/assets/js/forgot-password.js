document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("mus-forgot-form");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const btn = document.getElementById("forgot-submit-btn");
    const spinner = btn.querySelector(".spinner-border");
    const msgContainer = document.getElementById("forgot-messages");
    const userLogin = document.getElementById("mus_forgot_login");

    msgContainer.innerHTML = "";

    if (!userLogin.value.trim()) {
      msgContainer.innerHTML =
        '<div class="alert alert-danger">Please enter username or email</div>';
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
        // Always show success message for security
        msgContainer.innerHTML =
          '<div class="alert alert-success">' +
          (data.data?.message || "If your account exists, check your email") +
          "</div>";
        userLogin.value = "";
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
