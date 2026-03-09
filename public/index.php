<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Docflow</title>
  <link rel="stylesheet" href="/style.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar">
      <h1 class="sidebar-title">Ready Jobs</h1>
      <ul id="job-list" class="job-list"></ul>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="topbar-left">
          <span class="title">PDF Files</span>
          <span id="processing-indicator" class="processing hidden">
            <span class="spinner" aria-hidden="true"></span>
            <span id="processing-text"></span>
          </span>
          <label for="client-select">Client</label>
          <select id="client-select">
            <option value="" hidden>Choose client</option>
          </select>
        </div>
        <div class="topbar-right">
          <label for="view-mode">View</label>
          <select id="view-mode">
            <option value="pdf" selected>PDF</option>
            <option value="ocr">OCR-data</option>
          </select>
          <button id="clients-button" type="button">Huvudmän</button>
        </div>
      </div>

      <div class="viewer-wrap">
        <iframe id="pdf-viewer" class="pdf-viewer" title="PDF Viewer"></iframe>
        <pre id="ocr-view" class="ocr-view hidden"></pre>
      </div>
    </main>
  </div>

  <div id="clients-modal" class="modal-overlay hidden">
    <div class="modal-card">
      <h2>Huvudmän</h2>
      <p>Edit data/clients.json content directly.</p>
      <textarea
        id="clients-textarea"
        spellcheck="false"
        autocorrect="off"
        autocapitalize="off"
      ></textarea>
      <div class="modal-actions">
        <button id="clients-cancel" type="button">Cancel</button>
        <button id="clients-save" type="button">OK</button>
      </div>
    </div>
  </div>

  <script src="/app.js"></script>
</body>
</html>
