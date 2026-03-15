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
      <div class="sidebar-list-wrap">
        <ul id="job-list" class="job-list"></ul>
      </div>
      <section id="selected-job-panel" class="selected-job-panel">
        <div class="selected-job-panel-header">Markerat jobb</div>
        <div id="selected-job-name" class="selected-job-name">Inget jobb markerat</div>
        <div id="selected-job-meta" class="selected-job-meta">Markera ett jobb i listan för att visa åtgärder.</div>
        <div class="selected-job-actions">
          <button id="selected-job-reprocess" type="button" disabled>Kör om efter OCR</button>
          <button id="selected-job-rerun-ocr" type="button" disabled>Kör om från grunden</button>
        </div>
      </section>
    </aside>

    <main class="main">
      <div class="topbar">
        <div class="topbar-left">
          <div class="field-group">
            <label class="floating-label" for="client-select">Huvudman</label>
            <select id="client-select">
              <option value="" hidden>Välj huvudman</option>
            </select>
          </div>
          <div class="field-group">
            <label class="floating-label" for="sender-select">Avsändare</label>
            <select id="sender-select">
              <option value="" hidden>Välj avsändare</option>
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
        <div id="ocr-search-bar" class="ocr-search-bar hidden">
          <input id="ocr-search-input" type="text" placeholder="Sök i OCR-data">
          <label class="ocr-search-toggle" for="ocr-search-regex">
            <input id="ocr-search-regex" type="checkbox">
            <span>Regex</span>
          </label>
          <button id="ocr-search-prev" type="button" aria-label="Föregående träff">↑</button>
          <button id="ocr-search-next" type="button" aria-label="Nästa träff">↓</button>
          <span id="ocr-search-status" class="ocr-search-status"></span>
        </div>
        <div id="pdf-stack" class="pdf-stack">
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
        </div>
        <pre id="ocr-highlight-view" class="ocr-highlight-view hidden" aria-hidden="true"></pre>
        <textarea
          id="ocr-view"
          class="ocr-view hidden"
          readonly
          wrap="off"
          spellcheck="false"
          autocorrect="off"
          autocapitalize="off"
        ></textarea>
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
        <button class="settings-tab" data-settings-tab="senders" type="button">Avsändare</button>
        <button class="settings-tab" data-settings-tab="matching" type="button">OCR-matchning</button>
        <button class="settings-tab" data-settings-tab="ocr-processing" type="button">Bild-OCR</button>
        <button class="settings-tab" data-settings-tab="categories" type="button">Arkivstruktur</button>
        <button class="settings-tab" data-settings-tab="jobs" type="button">Jobb</button>
        <button class="settings-tab" data-settings-tab="paths" type="button">Sökvägar</button>
      </aside>
      <section class="settings-content">
        <div id="settings-panel-clients" class="settings-panel active"></div>
        <div id="settings-panel-senders" class="settings-panel hidden"></div>
        <div id="settings-panel-matching" class="settings-panel hidden"></div>
        <div id="settings-panel-ocr-processing" class="settings-panel hidden"></div>
        <div id="settings-panel-categories" class="settings-panel hidden"></div>
        <div id="settings-panel-jobs" class="settings-panel hidden"></div>
        <div id="settings-panel-paths" class="settings-panel hidden"></div>

        <div class="settings-close-row">
          <button id="settings-close" type="button">Stäng</button>
        </div>
      </section>
    </div>
  </div>

  <template id="settings-template-clients">
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
  </template>

  <template id="settings-template-senders">
    <h3>Avsändare</h3>
    <p>Redigera avsändare som lagras i databasen. Fälten här styr listan i väljaren för avsändare och vilka dokumentidentifierare som kan matchas mot avsändaren.</p>
    <p>Om du tar bort en avsändare här tas även dess kopplade betalnummer bort ur databasen.</p>
    <div class="settings-group senders-toolbar">
      <div class="senders-toolbar-group">
        <label class="settings-label" for="senders-sort-order">Sortering</label>
        <select id="senders-sort-order" class="settings-select">
          <option value="name">Namn</option>
          <option value="orgNumber">Org.nr</option>
          <option value="domain">Domän</option>
          <option value="paymentCount">Antal betalnummer</option>
        </select>
      </div>
      <div class="senders-toolbar-actions">
        <button id="senders-expand-all" type="button">Expandera alla</button>
        <button id="senders-collapse-all" type="button">Kontrahera alla</button>
      </div>
    </div>
    <div id="senders-list" class="categories-list"></div>
    <div class="categories-actions">
      <button id="senders-add-row" type="button">Lägg till avsändare</button>
    </div>
    <div class="panel-actions">
      <button id="senders-cancel" type="button">Avbryt</button>
      <button id="senders-apply" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-matching">
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
  </template>

  <template id="settings-template-ocr-processing">
    <h3>Bild-OCR</h3>
    <p>Kör <code>ocrmypdf</code> som ett steg i bearbetningen innan OCR-text läses ut för analys.</p>
    <div class="ocr-status-card">
      <div class="ocr-status-row">
        <span class="ocr-status-label">JBIG2-optimering</span>
        <div class="ocr-status-actions">
          <span id="jbig2-status-badge-wrap" class="ocr-status-badge-wrap">
            <span id="jbig2-status-badge" class="ocr-status-badge">Kontrollerar...</span>
          </span>
          <button id="jbig2-refresh-button" class="ocr-status-refresh hidden" type="button" aria-label="Kontrollera igen" title="Kontrollera igen">↻</button>
        </div>
      </div>
      <p>JBIG2 används för att komprimera svartvita skannade bilder effektivare. Om det finns installerat kan OCRmyPDF ofta ge betydligt mindre PDF-filer.</p>
      <p>Installationskommando:</p>
      <pre id="jbig2-install-command" class="settings-command"></pre>
    </div>
    <p>Kommandot som används är i praktiken:</p>
    <pre id="ocr-processing-command" class="settings-command"></pre>
    <label class="settings-checkbox">
      <input id="ocr-skip-existing-text" type="checkbox" checked>
      <span>Hoppa över dokument som redan har OCR-text</span>
    </label>
    <p class="settings-help">Ikryssad använder <code>--mode skip</code>. Avmarkerad använder <code>--mode redo</code>. I <code>redo</code>-läge utelämnas <code>--deskew</code> eftersom OCRmyPDF 17 inte tillåter den kombinationen.</p>
    <div class="settings-group">
      <label class="settings-label" for="ocr-optimize-level">Komprimeringsnivå</label>
      <select id="ocr-optimize-level" class="settings-select">
        <option value="0">Ingen (-O0)</option>
        <option value="1" selected>Lossless (-O1)</option>
        <option value="2">Balans (-O2)</option>
        <option value="3">Max (-O3)</option>
      </select>
    </div>
    <div class="settings-group">
      <label class="settings-label" for="ocr-text-extraction-method">Textuttag för OCR-data</label>
      <select id="ocr-text-extraction-method" class="settings-select">
        <option value="layout">pdftotext -layout</option>
        <option value="bbox">bbox-grid (ord + koordinater)</option>
      </select>
    </div>
    <p class="settings-help"><code>pdftotext -layout</code> är den äldre direkta textutläsningen. <code>bbox-grid</code> läser ord och koordinater via <code>pdftotext -bbox-layout</code> och bygger sedan en rutnätstext från samma data.</p>
    <div class="settings-group">
      <p>Substitutions nedan ändrar OCR-texten innan den byggs in i PDF-filens textlager via Docflows OCRmyPDF-plugin.</p>
      <p>Exempel: mappa <code>0K:</code> till <code>OK:</code> eller korrigera återkommande felstavade ord redan i PDF-texten.</p>
      <div id="ocr-pdf-substitutions-list" class="matching-list"></div>
      <div class="categories-actions">
        <button id="ocr-pdf-substitutions-add-row" type="button">Lägg till substitution</button>
      </div>
    </div>
    <div class="panel-actions">
      <button id="ocr-processing-cancel" type="button">Avbryt</button>
      <button id="ocr-processing-apply" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-categories">
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
  </template>

  <template id="settings-template-jobs">
    <h3>Jobb</h3>
    <p>Välj hur jobbstatus ska uppdateras i UI:t.</p>
    <label class="settings-label" for="jobs-state-transport">Uppdateringsmetod för jobbstatus</label>
    <select id="jobs-state-transport" class="settings-select">
      <option value="polling">Polling</option>
      <option value="sse">Automatisk push (SSE)</option>
    </select>
    <p class="settings-help">Vid byte sparas inställningen direkt och sidan laddas om.</p>
    <p>Ogiltigförklara alla jobb och flytta tillbaka <code>source.pdf</code> till inbox.</p>
    <div class="settings-danger">
      <button id="settings-reset-jobs" type="button">Återställ alla jobb</button>
    </div>
  </template>

  <template id="settings-template-paths">
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
  </template>

  <script src="/app.js"></script>
</body>
</html>
