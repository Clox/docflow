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
      <div class="topbar">
        <div class="topbar-left">
          <div>PDF Review</div>
          <select id="client-select">
            <option value="">No clients</option>
          </select>
        </div>
        <div class="topbar-controls">
          <label for="view-mode">View:</label>
          <select id="view-mode">
            <option value="pdf" selected>PDF</option>
            <option value="ocr">OCR-data</option>
          </select>
          <button id="clients-button" type="button">Huvudmän</button>
        </div>
      </div>
      <div id="viewer-stack" class="viewer-stack">
        <iframe class="pdf-viewer-frame"></iframe>
        <iframe class="pdf-viewer-frame"></iframe>
        <iframe class="pdf-viewer-frame"></iframe>
      </div>
      <div id="ocr-view" class="ocr-view hidden">
        <pre id="ocr-text" class="ocr-text"></pre>
      </div>
    </main>
  </div>

  <div id="clients-modal" class="modal-overlay hidden">
    <div class="modal-card">
      <h2>Huvudmän</h2>
      <details class="sql-help">
        <summary>Hämta klienter med SQL (kopierbar)</summary>
        <textarea id="clients-query" class="query-textarea" readonly>SELECT JSON_PRETTY(
	JSON_ARRAYAGG(
		JSON_OBJECT(
			'firstName', first_name,
			'lastName', last_name,
			'personalIdentityNumber', personal_identity_number
		)
	)
) AS clients_json
FROM clients;</textarea>
        <div class="sql-actions">
          <button id="copy-query-button" type="button">Copy query</button>
        </div>
      </details>
      <textarea id="clients-textarea" spellcheck="false" autocorrect="off" autocapitalize="off"></textarea>
      <div class="modal-actions">
        <button id="clients-cancel" type="button">Cancel</button>
        <button id="clients-save" type="button">OK</button>
      </div>
    </div>
  </div>

  <script src="/app.js"></script>
</body>
</html>
