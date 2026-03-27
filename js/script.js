document.getElementById("loginForm")?.addEventListener("submit", function(e) {
  e.preventDefault();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const role = document.getElementById("role").value;

  if (!email || !password) {
    alert("Please fill in all fields.");
    return;
  }

  if (role === "host") {
    window.location.href = "owner-dashboard.php";
  } else {
    window.location.href = "restaurant.php";
  }
});
