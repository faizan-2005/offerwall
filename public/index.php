<?php
// Public SPA entry. Static HTML served with Tailwind CDN. JS handles SPA routing.
?><!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>OfferWall - Earn by Tasks</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body class="bg-gradient-to-b from-slate-50 to-white min-h-screen text-slate-800">
  <div id="app" class="max-w-4xl mx-auto p-4"></div>

  <script src="/assets/js/api.js"></script>
  <script src="/assets/js/auth.js"></script>
  <script src="/assets/js/app.js"></script>
</body>
</html>
