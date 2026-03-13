<!doctype html>
<html lang="sv">
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
        <span>Klara jobb</span>
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
          <span class="title">PDF-filer</span>
          <div class="field-group">
            <label class="floating-label" for="client-select">Huvudman</label>
            <select id="client-select">
              <option value="" hidden>Välj huvudman</option>
            </select>
          </div>
          <div class="field-group">
            <label class="floating-label" for="category-select">Kategori</label>
            <select id="category-select">
              <option value="" hidden>Välj kategori</option>
            </select>
          </div>
        </div>
        <div class="topbar-right">
          <div class="field-group">
            <label class="floating-label" for="view-mode">Vy</label>
            <select id="view-mode">
              <option value="pdf" selected>PDF</option>
              <option value="ocr">OCR-data</option>
              <option value="matches">Matchningar</option>
              <option value="meta">Meta</option>
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
        <div id="pdf-stack" class="pdf-stack">
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
        </div>
        <pre id="ocr-view" class="ocr-view hidden"></pre>
        <div id="matches-view" class="matches-view hidden"></div>
        <pre id="meta-view" class="meta-view hidden"></pre>
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
        <button class="settings-tab" data-settings-tab="jobs" type="button">Jobb</button>
        <button class="settings-tab" data-settings-tab="paths" type="button">Sökvägar</button>
      </aside>
      <section class="settings-content">
        <div id="settings-panel-clients" class="settings-panel active">
          <h3>Huvudmän</h3>
          <p>Redigera innehållet i <code>data/clients.json</code> direkt.</p>
          <textarea
            id="clients-textarea"
            spellcheck="false"
            autocorrect="off"
            autocapitalize="off"
          ></textarea>
          <div class="panel-actions">
            <button id="clients-cancel" type="button">Avbryt</button>
            <button id="clients-apply" type="button">Spara</button>
          </div>
        </div>

        <div id="settings-panel-matching" class="settings-panel hidden">
          <h3>OCR-matchning</h3>
          <div class="matching-replacements-section">
            <p>Definiera teckenersättningar för OCR-text före matchning.</p>
            <p>Exempel: mappa <code>é</code> till <code>ö</code> så att <code>Férfallodatum</code> kan matcha <code>Förfallodatum</code>.</p>
            <div id="matching-list" class="matching-list"></div>
            <div class="categories-actions">
              <button id="matching-add-row" type="button">Lägg till ersättning</button>
            </div>
          </div>

          <div class="matching-threshold-section">
            <p>Ange lägsta säkerhetspoäng (confidence) för att extraherat fakturadatafält ska accepteras (0.00 - 1.00).</p>
            <div class="matching-threshold-row">
              <div class="floating-input-group matching-threshold-field">
                <label class="floating-input-label" for="matching-invoice-threshold">Tröskel för fakturadata-extraktion</label>
                <input id="matching-invoice-threshold" type="number" min="0" max="1" step="0.01">
              </div>
            </div>
          </div>

          <div class="panel-actions">
            <button id="matching-cancel" type="button">Avbryt</button>
            <button id="matching-apply" type="button">Spara</button>
          </div>
        </div>

        <div id="settings-panel-categories" class="settings-panel hidden">
          <h3>Arkivstruktur</h3>
          <p>Bygg upp mappar med kategorier och regler (Mapp → Kategori → Regel).</p>
          <div class="archive-subtabs">
            <button id="archive-tab-categories" class="archive-subtab active" type="button" data-archive-tab="categories">Kategorier</button>
            <button id="archive-tab-system" class="archive-subtab" type="button" data-archive-tab="system">Systemkategorier</button>
          </div>

          <div id="archive-view-categories" class="archive-view">
            <div id="categories-list" class="categories-list"></div>
            <div class="categories-actions">
              <button id="categories-add-category" type="button">Lägg till mapp</button>
            </div>
          </div>

          <div id="archive-view-system" class="archive-view hidden">
            <div id="system-category-editor" class="categories-list"></div>
          </div>

          <div class="panel-actions">
            <button id="categories-cancel" type="button">Avbryt</button>
            <button id="categories-apply" type="button">Spara</button>
          </div>
        </div>

        <div id="settings-panel-jobs" class="settings-panel hidden">
          <h3>Jobb</h3>
          <p>Ogiltigförklara alla jobb och flytta tillbaka <code>source.pdf</code> till inbox.</p>
          <div class="settings-danger">
            <button id="settings-reset-jobs" type="button">Återställ alla jobb</button>
          </div>
        </div>

        <div id="settings-panel-paths" class="settings-panel hidden">
          <h3>Sökvägar</h3>
          <p>Ange grundsökväg för utdata (din Huvudmän-mapp).</p>
          <p>Det ska finnas en undermapp per huvudman, och varje mappnamn ska matcha huvudmannens <code>folderName</code>.</p>
          <label class="settings-label" for="output-base-path">Bas-sökväg för utdata</label>
          <input
            id="output-base-path"
            type="text"
            placeholder="/absolute/path/to/Huvudmän"
            spellcheck="false"
            autocorrect="off"
            autocapitalize="off"
          >
          <div class="panel-actions">
            <button id="paths-cancel" type="button">Avbryt</button>
            <button id="paths-apply" type="button">Spara</button>
          </div>
        </div>

        <div class="settings-close-row">
          <button id="settings-close" type="button">Stäng</button>
        </div>
      </section>
    </div>
  </div>

  <script src="/app.js"></script>
</body>
</html>
