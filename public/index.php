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
      <h1 class="sidebar-title">
        <span>Ready Jobs</span>
        <span id="processing-indicator" class="processing hidden">
          <span class="spinner" aria-hidden="true"></span>
          <span id="processing-text"></span>
        </span>
      </h1>
      <ul id="job-list" class="job-list"></ul>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="topbar-left">
          <span class="title">PDF Files</span>
          <div class="field-group">
            <label class="floating-label" for="client-select">Client</label>
            <select id="client-select">
              <option value="" hidden>Choose client</option>
            </select>
          </div>
        </div>
        <div class="topbar-right">
          <div class="field-group">
            <label class="floating-label" for="view-mode">View</label>
            <select id="view-mode">
              <option value="pdf" selected>PDF</option>
              <option value="ocr">OCR-data</option>
            </select>
          </div>
          <button id="settings-button" type="button" aria-label="Inställningar" title="Inställningar">
            <span class="hamburger" aria-hidden="true">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </button>
        </div>
      </div>

      <div class="viewer-wrap">
        <iframe id="pdf-viewer" class="pdf-viewer" title="PDF Viewer"></iframe>
        <pre id="ocr-view" class="ocr-view hidden"></pre>
      </div>
    </main>
  </div>

  <div id="settings-modal" class="modal-overlay hidden">
    <div class="modal-card">
      <h2>Inställningar</h2>
      <details class="settings-section">
        <summary>Huvudmän</summary>
        <p>Edit data/clients.json content directly.</p>
        <textarea
          id="clients-textarea"
          spellcheck="false"
          autocorrect="off"
          autocapitalize="off"
        ></textarea>
      </details>
      <div class="settings-danger">
        <button id="settings-reset-jobs" type="button">Reset all jobs</button>
      </div>
      <div class="modal-actions">
        <button id="settings-cancel" type="button">Cancel</button>
        <button id="settings-save" type="button">OK</button>
      </div>
    </div>
  </div>

  <script src="/app.js"></script>
</body>
</html>
