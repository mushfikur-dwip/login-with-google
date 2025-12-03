jQuery(document).ready(function ($) {
  "use strict";

  // Handle login form submission
  $("#sel-email-login-form").on("submit", function (e) {
    e.preventDefault();

    var $form = $(this);
    var $submitBtn = $form.find('button[type="submit"]');
    var $message = $(".sel-message");
    var email = $("#sel-email").val().trim();

    // Validate email
    if (!email || !isValidEmail(email)) {
      showMessage("Please enter a valid email address.", "error");
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
        nonce: sel_ajax.nonce,
      },
      success: function (response) {
        if (response.success) {
          showMessage(response.data.message, "success");

          // Reload page after 1 second to show logged in state
          setTimeout(function () {
            location.reload();
          }, 1000);
        } else {
          showMessage(response.data.message, "error");
          $submitBtn.prop("disabled", false).removeClass("loading");
        }
      },
      error: function () {
        showMessage("An error occurred. Please try again.", "error");
        $submitBtn.prop("disabled", false).removeClass("loading");
      },
    });
  });

  // Email validation function
  function isValidEmail(email) {
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
  }

  // Show message function
  function showMessage(message, type) {
    var $message = $(".sel-message");
    $message.removeClass("success error").addClass(type).text(message).fadeIn();
  }

  // Handle Create Account button click (optional)
  $(".sel-btn-secondary").on("click", function (e) {
    e.preventDefault();
    // You can redirect to registration page or show a message
    alert("Account creation feature coming soon!");
  });

  // Handle Google Sign In button click (optional)
  $(".sel-btn-google").on("click", function (e) {
    e.preventDefault();
    // You can add Google OAuth integration here
    alert("Google Sign In feature coming soon!");
  });
});
