<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <title>Download Snocart</title>
  <script>
    var ua = navigator.userAgent || navigator.vendor || window.opera;

    if (/android/i.test(ua)) {
      window.location.href = "https://play.google.com/store/apps/details?id=com.snofood&hl=en_IN";
    } else if (/iPad|iPhone|iPod/.test(ua) && !window.MSStream) {
      window.location.href = "https://apps.apple.com/in/app/snocart-everything-in-mins/id6477191585";
    } else {
      window.location.href = "https://new.snocart.com"; // fallback
    }
  </script>
</head>
<body>
  <p>Redirecting you to the app store…</p>
</body>
</html>

