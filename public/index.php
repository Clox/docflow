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
          <div class="field-group">
            <label class="floating-label" for="category-select">Category</label>
            <select id="category-select">
              <option value="" hidden>Choose category</option>
            </select>
          </div>
        </div>
        <div class="topbar-right">
          <div class="field-group">
            <label class="floating-label" for="view-mode">View</label>
            <select id="view-mode">
              <option value="pdf" selected>PDF</option>
              <option value="ocr">OCR-data</option>
              <option value="matches">Matches</option>
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
        <div id="matches-view" class="matches-view hidden"></div>
      </div>
    </main>
  </div>

  <div id="settings-modal" class="modal-overlay hidden">
    <div class="settings-dialog">
      <aside class="settings-nav">
        <h2>Inställningar</h2>
        <button class="settings-tab active" data-settings-tab="clients" type="button">Huvudmän</button>
        <button class="settings-tab" data-settings-tab="matching" type="button">OCR-matchning</button>
        <button class="settings-tab" data-settings-tab="categories" type="button">Arkivstruktur</button>
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

        <div id="settings-panel-matching" class="settings-panel hidden">
          <h3>OCR-matchning</h3>
          <p>Definiera teckenersättningar för OCR-text före matchning.</p>
          <p>Exempel: mappa <code>é</code> till <code>ö</code> så att <code>Férfallodatum</code> kan matcha <code>Förfallodatum</code>.</p>
          <div id="matching-list" class="matching-list"></div>
          <div class="categories-actions">
            <button id="matching-add-row" type="button">Lägg till ersättning</button>
          </div>
          <div class="panel-actions">
            <button id="matching-cancel" type="button">Cancel</button>
            <button id="matching-apply" type="button">Apply</button>
          </div>
        </div>

        <div id="settings-panel-categories" class="settings-panel hidden">
          <h3>Arkivstruktur</h3>
          <p>Bygg upp mappar med kategorier och regler (Mapp → Kategori → Regel).</p>
          <div id="categories-list" class="categories-list"></div>
          <div class="categories-actions">
            <button id="categories-add-category" type="button">Lägg till mapp</button>
          </div>
          <div class="panel-actions">
            <button id="categories-cancel" type="button">Cancel</button>
            <button id="categories-apply" type="button">Apply</button>
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
          <p>Set the base output path (your Huvudmän/Clients folder).</p>
          <p>There should be one subfolder per client inside, and each subfolder name should match that client's <code>folderName</code>.</p>
          <label class="settings-label" for="output-base-path">Base output path</label>
          <input
            id="output-base-path"
            type="text"
            placeholder="/absolute/path/to/Huvudmän"
            spellcheck="false"
            autocorrect="off"
            autocapitalize="off"
          >
          <div class="panel-actions">
            <button id="paths-cancel" type="button">Cancel</button>
            <button id="paths-apply" type="button">Apply</button>
          </div>
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
