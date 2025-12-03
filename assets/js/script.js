jQuery(document).ready(function ($) {
  "use strict";

  // Handle login form submission
  $("#sel-email-login-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var $message = $(".sel-message");
    var email = $("#sel-login-email").val().trim();
    var password = $("#sel-login-password").val();

    // Validate email
    if (!email || !isValidEmail(email)) {
      showMessage("Please enter a valid email address.", "error");
      return;
    }

    // Check if password field is visible and required
    if ($("#sel-password-group").is(":visible") && !password) {
      showMessage("Please enter your password.", "error");
      return;
    }

    // Disable button and show loading state
    $submitBtn.prop("disabled", true).addClass("loading");
    $message.hide().removeClass("success error");

    // Send AJAX request
    $.ajax({
      url: sel_ajax.ajax_url,
      type: "POST",
      data: {
        action: "sel_login",
        email: email,
        password: password,
        nonce: sel_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          showMessage(response.data.message, "success");

          // Redirect to my-account page
          setTimeout(function () {
            window.location.href = response.data.redirect;
          }, 1000);
        } else {
          // If password is needed, show password field
          if (response.data.needs_password) {
            $("#sel-password-group").slideDown();
            showMessage("Please enter your password to continue.", "error");
          } else if (response.data.needs_signup) {
            showMessage(response.data.message, "error");
            // Auto switch to signup form after 2 seconds
            setTimeout(function () {
              $("#sel-show-signup").click();
            }, 2000);
          } else {
            showMessage(response.data.message, "error");
          }
          $submitBtn.prop("disabled", false).removeClass("loading");
        }
      },
      error: function () {
        showMessage("An error occurred. Please try again.", "error");
        $submitBtn.prop("disabled", false).removeClass("loading");
      },
    });
  });

  // Handle signup form submission
  $("#sel-signup-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var $message = $("#sel-signup-form-container .sel-message");
    var name = $("#sel-signup-name").val().trim();
    var email = $("#sel-signup-email").val().trim();
    var password = $("#sel-signup-password").val();

    // Validate inputs
    if (!name) {
      showMessage("Please enter your name.", "error", $message);
      return;
    }

    if (!email || !isValidEmail(email)) {
      showMessage("Please enter a valid email address.", "error", $message);
      return;
    }

    if (!password || password.length < 6) {
      showMessage(
        "Password must be at least 6 characters long.",
        "error",
        $message
      );
      return;
    }

    // Disable button and show loading state
    $submitBtn.prop("disabled", true).addClass("loading");
    $message.hide().removeClass("success error");

    // Send AJAX request
    $.ajax({
      url: sel_ajax.ajax_url,
      type: "POST",
      data: {
        action: "sel_signup",
        name: name,
        email: email,
        password: password,
        nonce: sel_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          showMessage(response.data.message, "success", $message);

          // Redirect to my-account page
          setTimeout(function () {
            window.location.href = response.data.redirect;
          }, 1000);
        } else {
          showMessage(response.data.message, "error", $message);
          $submitBtn.prop("disabled", false).removeClass("loading");
        }
      },
      error: function () {
        showMessage("An error occurred. Please try again.", "error", $message);
        $submitBtn.prop("disabled", false).removeClass("loading");
      },
    });
  });

  // Toggle between login and signup forms
  $("#sel-show-signup").on("click", function (e) {
    e.preventDefault();
    $("#sel-login-form-container").fadeOut(300, function () {
      $("#sel-signup-form-container").fadeIn(300);
    });
    // Clear messages
    $(".sel-message").hide().removeClass("success error").text("");
  });

  $("#sel-show-login").on("click", function (e) {
    e.preventDefault();
    $("#sel-signup-form-container").fadeOut(300, function () {
      $("#sel-login-form-container").fadeIn(300);
    });
    // Clear messages and hide password field
    $(".sel-message").hide().removeClass("success error").text("");
    $("#sel-password-group").hide();
    $("#sel-login-password").val("");
  });

  // Email validation function
  function isValidEmail(email) {
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }

  // Show message function
  function showMessage(message, type, $element) {
    var $msg = $element || $(".sel-message");
    $msg.removeClass("success error").addClass(type).text(message).fadeIn();
  }

  // Handle Google Sign In button click
  $("#sel-google-login").on("click", function (e) {
    e.preventDefault();

    if (sel_ajax.google_enabled === "1" && sel_ajax.google_client_id) {
      // Build Google OAuth URL
      var client_id = sel_ajax.google_client_id;
      var redirect_uri = sel_ajax.ajax_url + "?action=sel_google_callback";
      var scope = "email profile";
      var response_type = "code";

      var google_auth_url =
        "https://accounts.google.com/o/oauth2/v2/auth?" +
        "client_id=" +
        encodeURIComponent(client_id) +
        "&redirect_uri=" +
        encodeURIComponent(redirect_uri) +
        "&scope=" +
        encodeURIComponent(scope) +
        "&response_type=" +
        response_type +
        "&access_type=offline";

      // Redirect to Google OAuth
      window.location.href = google_auth_url;
    } else {
      alert("Google Sign In is not configured. Please contact administrator.");
    }
  });
});
