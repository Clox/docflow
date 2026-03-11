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
    <div class="settings-dialog">
      <aside class="settings-nav">
        <h2>Inställningar</h2>
        <button class="settings-tab active" data-settings-tab="clients" type="button">Huvudmän</button>
        <button class="settings-tab" data-settings-tab="jobs" type="button">Jobs</button>
        <button class="settings-tab" data-settings-tab="paths" type="button">Paths</button>
      </aside>
      <section class="settings-content">
        <div id="settings-panel-clients" class="settings-panel active">
          <h3>Huvudmän</h3>
          <p>Edit data/clients.json content directly.</p>
          <textarea
            id="clients-textarea"
            spellcheck="false"
            autocorrect="off"
            autocapitalize="off"
          ></textarea>
          <div class="panel-actions">
            <button id="clients-cancel" type="button">Cancel</button>
            <button id="clients-apply" type="button">Apply</button>
          </div>
        </div>

        <div id="settings-panel-jobs" class="settings-panel hidden">
          <h3>Jobs</h3>
          <p>Invalidate all jobs and restore source files back to inbox.</p>
          <div class="settings-danger">
            <button id="settings-reset-jobs" type="button">Reset all jobs</button>
          </div>
        </div>

        <div id="settings-panel-paths" class="settings-panel hidden">
          <h3>Paths</h3>
          <p>Path settings will be added here later.</p>
        </div>

        <div class="settings-close-row">
          <button id="settings-close" type="button">Close</button>
        </div>
      </section>
    </div>
  </div>

  <script src="/app.js"></script>
</body>
</html>
