<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PDF Review</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <h1 class="sidebar-title">PDF Files</h1>
      <ul id="file-list" class="file-list"></ul>
    </aside>

    <main class="main">
      <div class="topbar">PDF Review</div>
      <div id="viewer-stack" class="viewer-stack">
        <iframe class="pdf-viewer-frame"></iframe>
        <iframe class="pdf-viewer-frame"></iframe>
        <iframe class="pdf-viewer-frame"></iframe>
      </div>
    </main>
  </div>

  <script src="/app.js"></script>
</body>
</html>
