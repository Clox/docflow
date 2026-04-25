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
      <div class="sidebar-list-wrap">
        <div class="sidebar-title">
          <div class="sidebar-title-main">
            <span class="sidebar-title-label">Dokument</span>
          </div>
          <div class="sidebar-title-controls">
            <select id="job-list-mode" aria-label="Jobblista">
              <option value="ready">Att granska</option>
              <option value="archived-review">Arkiverade att granska</option>
              <option value="processing">Bearbetas</option>
              <option value="archived">Arkiverade</option>
              <option value="all">Alla</option>
            </select>
            <div id="job-list-menu-wrap" class="job-list-menu-wrap">
              <button
                id="job-list-menu-button"
                class="job-list-menu-button icon-button icon-menu-button"
                type="button"
                aria-label="Fler alternativ"
                aria-haspopup="menu"
                aria-expanded="false"
                aria-controls="job-list-menu"
                title="Fler alternativ"
              >⋮</button>
              <div id="job-list-menu" class="job-list-menu hidden" role="menu" aria-label="Dokumentlistans meny">
                <button
                  id="job-list-reanalyze-all-action"
                  type="button"
                  role="menuitem"
                  title="Kör om analysen för alla dokument med aktuell logik och aktuella regler. Arkiverade dokument ändras inte automatiskt, men nya förslag kan visas om analysen nu ger ett annat resultat."
                >Kör om analys för alla dokument</button>
              </div>
            </div>
          </div>
        </div>
        <ul id="job-list" class="job-list">
          <li id="processing-indicator" class="job-list-processing-indicator processing hidden">
            <span class="spinner" aria-hidden="true"></span>
            <span id="processing-text"></span>
          </li>
        </ul>
      </div>
      <div
        id="sidebar-splitter"
        class="sidebar-splitter"
        role="separator"
        aria-label="Ändra höjd mellan dokumentlistan och detaljpanelen"
        aria-orientation="horizontal"
        title="Dra upp eller ner för att ändra höjd mellan dokumentlistan och detaljpanelen"
      ></div>
      <section id="selected-job-panel" class="selected-job-panel">
        <div class="selected-job-panel-body">
          <div class="selected-job-panel-header-row">
            <div class="selected-job-panel-header">Detaljer</div>
            <div id="selected-job-status" class="selected-job-status"></div>
          </div>
          <div id="selected-job-name" class="selected-job-name">Inget jobb markerat</div>
          <div id="selected-job-meta" class="selected-job-meta">Markera ett jobb i listan för att visa åtgärder.</div>
          <div id="selected-job-sender-unknown-section" class="selected-job-panel-section" hidden>
            <div class="selected-job-panel-section-title">Okända avsändaruppgifter</div>
            <div id="selected-job-sender-unknown-info" class="selected-job-sender-info"></div>
          </div>
          <div id="selected-job-senders-section" class="selected-job-panel-section">
            <div class="selected-job-panel-section-title">Avsändare</div>
            <div id="selected-job-sender-linked-info" class="selected-job-sender-info">
              Ingen avsändarinformation tillgänglig ännu.
            </div>
          </div>
        </div>
      </section>
      <div class="sidebar-divider" aria-hidden="true"></div>
      <section id="selected-job-actions-panel" class="selected-job-actions-panel">
        <div class="selected-job-actions-panel-body">
          <div class="selected-job-panel-header-row selected-job-actions-panel-header-row">
            <div class="selected-job-panel-header">Åtgärder</div>
            <div class="selected-job-actions-panel-header-controls">
              <div id="selected-job-actions-warning" class="selected-job-actions-warning hidden" aria-live="polite">
                <span class="selected-job-actions-warning-text">Analys inaktuell</span>
              </div>
              <div id="selected-job-actions-menu-wrap" class="selected-job-actions-menu-wrap">
                <button
                  id="selected-job-actions-menu-button"
                  class="selected-job-actions-menu-button icon-button icon-menu-button"
                  type="button"
                  aria-label="Fler alternativ"
                  aria-haspopup="menu"
                  aria-expanded="false"
                  aria-controls="selected-job-actions-menu"
                  title="Fler alternativ"
                >⋮</button>
                <div id="selected-job-actions-menu" class="selected-job-actions-menu hidden" role="menu" aria-label="Fler åtgärder för dokument">
                  <button id="selected-job-delete-action" class="selected-job-actions-menu-item-danger" type="button" role="menuitem">Ta bort dokument…</button>
                </div>
              </div>
            </div>
          </div>
          <div class="selected-job-panel-section selected-job-fields selected-job-actions-fields">
            <div class="selected-job-panel-section-title">Arkivering</div>
            <div class="field-group">
              <label class="floating-label" for="client-select">Huvudman</label>
              <div class="field-group-control-row">
                <select id="client-select">
                  <option value="" hidden>Välj huvudman</option>
                </select>
                <button id="reset-client-action" class="field-reset-button" type="button" hidden title="Återställ till automatiskt föreslaget värde" aria-label="Återställ huvudman">↺</button>
              </div>
            </div>
            <div class="field-group">
              <label class="floating-label" for="sender-select">Avsändare</label>
              <div class="field-group-control-row">
                <select id="sender-select">
                  <option value="" hidden>Välj avsändare</option>
                </select>
                <button id="reset-sender-action" class="field-reset-button" type="button" hidden title="Återställ till automatiskt föreslaget värde" aria-label="Återställ avsändare">↺</button>
              </div>
            </div>
            <div class="field-group">
              <label class="floating-label" for="folder-select">Mapp</label>
              <div class="field-group-control-row">
                <select id="folder-select">
                  <option value="" hidden>Välj mapp</option>
                </select>
                <button id="reset-folder-action" class="field-reset-button" type="button" hidden title="Återställ till automatiskt föreslaget värde" aria-label="Återställ mapp">↺</button>
              </div>
            </div>
            <div class="field-group field-group-job-labels">
              <label class="floating-label" for="job-labels-field">Etiketter</label>
              <div class="field-group-control-row">
                <button
                  id="job-labels-field"
                  class="job-labels-field"
                  type="button"
                  disabled
                  aria-haspopup="dialog"
                  aria-expanded="false"
                  aria-controls="job-labels-overlay"
                >
                  <span id="job-labels-summary" class="job-labels-summary"></span>
                </button>
                <button id="reset-labels-action" class="field-reset-button" type="button" hidden title="Återställ till automatiskt föreslaget värde" aria-label="Återställ etiketter">↺</button>
              </div>
              <div id="job-labels-focus-hint" class="job-labels-field-hint" aria-hidden="true">Tryck Enter för att visa/redigera etiketter</div>
              <div id="job-labels-overlay" class="job-labels-overlay" aria-hidden="true">
                <div class="job-labels-combobox">
                  <input
                    id="job-labels-combobox"
                    class="job-labels-combobox-input"
                    type="text"
                    placeholder="Lägg till etikett…"
                    autocomplete="off"
                    spellcheck="false"
                    role="combobox"
                    aria-autocomplete="list"
                    aria-expanded="false"
                    aria-controls="job-labels-combobox-list"
                  >
                  <div id="job-labels-combobox-list" class="job-labels-combobox-list" role="listbox"></div>
                </div>
                <div id="job-labels-selected" class="job-labels-selected" tabindex="0"></div>
              </div>
            </div>
            <div class="field-group field-group-filename">
              <label class="floating-label" for="filename-input">Filnamn</label>
              <div class="field-group-control-row">
                <input id="filename-input" type="text" spellcheck="false" autocorrect="off" autocapitalize="off">
                <button id="reset-filename-action" class="field-reset-button" type="button" hidden title="Återställ till automatiskt föreslaget värde" aria-label="Återställ filnamn">↺</button>
              </div>
            </div>
            <button id="dismiss-archived-update-action" type="button" disabled hidden>Avfärda</button>
            <button id="archive-action" type="button" disabled title="Markera ett jobb först.">Arkivera</button>
          </div>
        </div>
      </section>
    </aside>

    <main class="main">
      <div class="topbar">
        <div id="app-notices" class="app-notices hidden"></div>
        <div class="topbar-right">
          <div class="field-group">
            <label class="floating-label" for="view-mode">Vy</label>
            <select id="view-mode">
              <option value="pdf" selected>PDF</option>
              <option value="ocr">OCR</option>
              <option value="matches">Matchningar</option>
              <option value="meta">Meta</option>
              <option value="review" hidden disabled>Granskning</option>
            </select>
          </div>
          <button
            id="settings-button"
            class="icon-button icon-settings-button"
            type="button"
            aria-label="Inställningar"
            title="Inställningar"
          >
		<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
			<path d="M12 15.5a3.5 3.5 0 1 0 0-7a3.5 3.5 0 0 0 0 7Z"></path>
			<path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.04.04a2 2 0 1 1-2.83 2.83l-.04-.04A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.05a1.7 1.7 0 0 0-1-1.55a1.7 1.7 0 0 0-1.87.34l-.04.04a2 2 0 1 1-2.83-2.83l.04-.04A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.05a1.7 1.7 0 0 0 1.55-1a1.7 1.7 0 0 0-.34-1.87l-.04-.04a2 2 0 1 1 2.83-2.83l.04.04A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.05a1.7 1.7 0 0 0 1 1.55a1.7 1.7 0 0 0 1.87-.34l.04-.04a2 2 0 1 1 2.83 2.83l-.04.04A1.7 1.7 0 0 0 19.4 9a1.7 1.7 0 0 0 1.55 1H21a2 2 0 1 1 0 4h-.05a1.7 1.7 0 0 0-1.55 1Z"></path>
		</svg>
          </button>
        </div>
      </div>

      <div class="viewer-wrap">
        <div id="ocr-toolbar" class="ocr-toolbar">
          <div id="ocr-source-tabs" class="ocr-source-tabs hidden" role="tablist" aria-label="OCR-källa">
            <div class="ocr-source-group ocr-source-group--objects">
              <div class="ocr-source-group-label">Objekt</div>
              <div class="ocr-source-group-tabs">
                <button id="ocr-source-tesseract" class="ocr-source-tab" type="button" data-ocr-source="tesseract" role="tab" aria-selected="false">Tesseract</button>
                <button id="ocr-source-rapidocr" class="ocr-source-tab" type="button" data-ocr-source="rapidocr" role="tab" aria-selected="false">RapidOCR</button>
                <button id="ocr-source-merged-objects" class="ocr-source-tab" type="button" data-ocr-source="merged-objects" role="tab" aria-selected="false">Sammanfogade</button>
              </div>
            </div>
            <span class="ocr-source-group-divider" aria-hidden="true"></span>
            <div class="ocr-source-group ocr-source-group--text">
              <div class="ocr-source-group-label">Text</div>
              <div class="ocr-source-group-tabs">
                <button id="ocr-source-merged" class="ocr-source-tab active" type="button" data-ocr-source="merged" role="tab" aria-selected="true">Sammanfogad</button>
              </div>
            </div>
          </div>
          <div id="ocr-page-controls" class="ocr-page-controls hidden" aria-label="OCR-sidkontroller">
            <div class="ocr-page-nav">
              <input id="ocr-page-current" type="number" inputmode="numeric" min="1" step="1" aria-label="Nuvarande sida">
              <span class="ocr-page-separator">/</span>
              <span id="ocr-page-total">0</span>
            </div>
            <span class="ocr-page-controls-divider" aria-hidden="true"></span>
            <div class="ocr-zoom-controls">
              <button id="ocr-zoom-out" type="button" aria-label="Zooma ut">-</button>
              <input id="ocr-zoom-input" type="text" inputmode="numeric" autocomplete="off" aria-label="Zoomnivå i procent">
              <button id="ocr-zoom-in" type="button" aria-label="Zooma in">+</button>
            </div>
          </div>
          <div id="ocr-page-image-controls" class="ocr-page-image-controls hidden">
            <label id="ocr-page-image-toggle" class="ocr-page-image-toggle" for="ocr-show-page-image">
              <input id="ocr-show-page-image" type="checkbox">
              <span>Visa PDF-bakgrund</span>
            </label>
            <input
              id="ocr-page-image-opacity"
              class="ocr-page-image-opacity"
              type="range"
              min="0"
              max="100"
              step="1"
              value="50"
              aria-label="Blandning mellan PDF-bakgrund och OCR-lager">
          </div>
          <div id="ocr-search-bar" class="ocr-search-bar hidden">
            <input id="ocr-search-input" type="text" placeholder="Sök i OCR">
            <input id="ocr-search-regex" type="checkbox" hidden>
            <button id="ocr-search-prev" type="button" aria-label="Föregående träff">↑</button>
            <button id="ocr-search-next" type="button" aria-label="Nästa träff">↓</button>
            <span id="ocr-search-status" class="ocr-search-status"></span>
          </div>
        </div>
        <div id="pdf-stack" class="pdf-stack">
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
          <iframe class="pdf-frame"></iframe>
        </div>
        <div id="ocr-pages-view" class="ocr-pages-view hidden"></div>
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
        <section id="archived-review-panel" class="archived-review-panel hidden"></section>
      </div>
    </main>
  </div>

  <div id="settings-modal" class="modal-overlay hidden">
    <div id="settings-dialog" class="settings-dialog">
      <aside id="settings-dialog-nav" class="settings-nav">
        <h2>Inställningar</h2>
        <div class="settings-nav-section">
          <div class="settings-nav-group-title">Register</div>
          <button class="settings-tab active" data-settings-tab="clients" type="button">Huvudmän</button>
          <button class="settings-tab" data-settings-tab="senders" type="button">Avsändare</button>
        </div>
        <div class="settings-nav-section">
          <div class="settings-nav-group-title">Dokumenttolkning</div>
          <button class="settings-tab" data-settings-tab="ocr-processing" type="button">Textigenkänning</button>
          <button class="settings-tab" data-settings-tab="matching" type="button">Textmatchning</button>
        </div>
        <div class="settings-nav-section">
          <div class="settings-nav-group-title">Arkivering</div>
          <button class="settings-tab" data-settings-tab="archiving-review" type="button">Uppdatera arkiverade dokument <span class="settings-tab-indicator" aria-hidden="true">●</span></button>
          <button class="settings-tab" data-settings-tab="archive-structure" type="button">Arkivstruktur</button>
          <button class="settings-tab" data-settings-tab="labels" type="button">Etiketter</button>
          <button class="settings-tab" data-settings-tab="data-fields" type="button">Datafält</button>
        </div>
        <div class="settings-nav-section">
          <div class="settings-nav-group-title">Lagring</div>
          <button class="settings-tab" data-settings-tab="paths" type="button">Sökvägar</button>
        </div>
        <div class="settings-nav-section">
          <div class="settings-nav-group-title">System</div>
          <button class="settings-tab" data-settings-tab="system" type="button">System</button>
          <button class="settings-tab" data-settings-tab="extensions" type="button">Tillägg</button>
        </div>
      </aside>
      <section class="settings-content">
        <div id="settings-panel-clients" class="settings-panel active"></div>
        <div id="settings-panel-senders" class="settings-panel hidden"></div>
        <div id="settings-panel-matching" class="settings-panel hidden"></div>
        <div id="settings-panel-ocr-processing" class="settings-panel hidden"></div>
        <div id="settings-panel-archive-structure" class="settings-panel hidden"></div>
        <div id="settings-panel-labels" class="settings-panel hidden"></div>
        <div id="settings-panel-data-fields" class="settings-panel hidden"></div>
        <div id="settings-panel-archiving-review" class="settings-panel hidden"></div>
        <div id="settings-panel-paths" class="settings-panel hidden"></div>
        <div id="settings-panel-system" class="settings-panel hidden"></div>
        <div id="settings-panel-extensions" class="settings-panel hidden"></div>

        <div class="settings-footer-row">
          <div id="settings-panel-actions-host" class="settings-panel-actions-host"></div>
          <button id="settings-close" type="button">Stäng</button>
        </div>
      </section>
      <button id="settings-dialog-resize-handle" class="settings-dialog-resize-handle" type="button" aria-label="Ändra storlek på inställningsfönstret" title="Ändra storlek"></button>
    </div>
  </div>

  <template id="settings-template-clients">
    <h3>Huvudmän</h3>
    <p>Redigera huvudmän som lagras i databasen.</p>
    <div id="clients-list" class="categories-list"></div>
    <div class="categories-actions">
      <button id="clients-add-row" type="button">Lägg till huvudman</button>
    </div>
    <div class="panel-actions">
      <button id="clients-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="clients-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-archiving-review">
    <h3>Uppdatera arkiverade dokument</h3>
    <p>Här kan du se vilka redan arkiverade dokument som får ett annat resultat med aktuella regler och aktuell kod, och sedan uppdatera dem vid behov.</p>
    <div id="archiving-review-status" class="settings-inline-notice hidden"></div>
    <div id="archiving-review-actions" class="panel-actions"></div>
    <div id="archiving-review-summary" class="archiving-review-summary"></div>
    <div id="archiving-review-template-changes" class="archiving-review-template-changes"></div>
    <div id="archiving-review-jobs" class="archiving-review-jobs"></div>
  </template>

  <template id="settings-template-senders">
    <h3>Avsändare</h3>
    <p>Redigera avsändare som lagras i databasen. Fälten här styr listan i väljaren för avsändare och vilka dokumentidentifierare som kan matchas mot avsändaren.</p>
    <p>Om du tar bort en avsändare här tas även dess kopplade betalnummer bort ur databasen.</p>
    <div id="senders-view-senders" class="archive-view">
      <div class="settings-group senders-toolbar">
        <div class="senders-toolbar-group">
          <label class="settings-label" for="senders-sort-order">Sortering</label>
          <select id="senders-sort-order" class="settings-select">
            <option value="name">Namn</option>
            <option value="domain">Domän</option>
            <option value="paymentCount">Antal betalnummer</option>
            <option value="similarity">Misstänkt samma</option>
          </select>
        </div>
        <div class="senders-toolbar-actions">
          <button id="senders-expand-all" type="button">Expandera alla</button>
          <button id="senders-collapse-all" type="button">Kontrahera alla</button>
        </div>
      </div>
      <div id="senders-list" class="categories-list"></div>
      <div class="senders-selected-count-row">
        <span id="senders-selected-count" class="senders-selected-count">Antal markerade avsändare: 0</span>
        <button id="senders-clear-selection" class="senders-clear-selection" type="button">(Avmarkera alla)</button>
      </div>
      <div class="categories-actions senders-footer-actions">
        <button id="senders-add-row" type="button">Lägg till avsändare</button>
        <button id="senders-merge-selected" type="button" disabled>Slå ihop...</button>
      </div>
    </div>
    <div id="sender-merge-overlay" class="modal-overlay hidden">
      <div class="settings-dialog sender-merge-dialog">
        <section class="settings-content">
          <div class="settings-panel active sender-merge-panel">
            <h3>Slå ihop avsändare</h3>
            <p>Välj vilka värden som ska behållas och granska den sammanslagna avsändaren innan du sparar.</p>
            <div id="sender-merge-editor" class="categories-list"></div>
            <div class="panel-actions">
              <button id="sender-merge-cancel" class="button-danger" type="button">Avbryt</button>
              <button id="sender-merge-apply" class="button-success" type="button">Spara sammanslagning</button>
            </div>
          </div>
        </section>
      </div>
    </div>
    <div class="panel-actions">
      <button id="senders-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="senders-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-matching">
    <h3>Textmatchning</h3>
    <div class="matching-threshold-section">
      <h4>Positionsbaserad säkerhetsjustering</h4>
      <p>Hur säkerheten justeras utifrån brus, avslutstecken, nyckelkrockar mellan matchningar och axelbaserad positionsavvikelse i dokumentet.</p>
      <div class="matching-threshold-row">
        <div class="matching-threshold-field">
          <label class="settings-label" for="matching-noise-penalty">Straff per brus-tecken</label>
          <div class="matching-percent-field">
            <input id="matching-noise-penalty" type="number" min="0" max="100" step="0.1" inputmode="decimal">
            <span>%</span>
          </div>
        </div>
        <div class="matching-threshold-field">
          <label class="settings-label" for="matching-trailing-delimiter-penalty">Straff när värdet slutar på kolon eller semikolon</label>
          <div class="matching-percent-field">
            <input id="matching-trailing-delimiter-penalty" type="number" min="0" step="0.1" inputmode="decimal">
            <span>%</span>
          </div>
        </div>
        <div class="matching-threshold-field">
          <label class="settings-label" for="matching-other-match-key-penalty">Straff när värdet också är nyckel i annan matching</label>
          <div class="matching-percent-field">
            <input id="matching-other-match-key-penalty" type="number" min="0" step="0.1" inputmode="decimal">
            <span>%</span>
          </div>
        </div>
        <div class="matching-threshold-field">
          <label class="settings-label" for="matching-right-y-offset-penalty">Straff per y-avvikelse när värdet ligger till höger</label>
          <div class="matching-percent-field">
            <input id="matching-right-y-offset-penalty" type="number" min="0" step="0.1" inputmode="decimal">
            <span>%</span>
          </div>
        </div>
        <div class="matching-threshold-field">
          <label class="settings-label" for="matching-down-x-offset-penalty">Straff per x-avvikelse när värdet ligger under</label>
          <div class="matching-percent-field">
            <input id="matching-down-x-offset-penalty" type="number" min="0" step="0.1" inputmode="decimal">
            <span>%</span>
          </div>
        </div>
      </div>
      <p>Kandidater som ligger till vänster eller ovanför förkastas. För kandidater till höger används bara y-avvikelse, och för kandidater under används bara x-avvikelse.</p>
    </div>
    <div class="matching-replacements-section">
      <p>Definiera teckenersättningar för OCR-text före matchning.</p>
      <p>Exempel: mappa <code>é</code> till <code>ö</code> så att <code>Férfallodatum</code> kan matcha <code>Förfallodatum</code>.</p>
      <div id="matching-list" class="matching-list"></div>
      <div class="categories-actions">
        <button id="matching-add-row" type="button">Lägg till ersättning</button>
      </div>
    </div>

    <div class="panel-actions">
      <button id="matching-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="matching-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-ocr-processing">
    <h3>Textigenkänning</h3>
    <p>Kör <code>ocrmypdf</code> som ett steg i bearbetningen innan OCR-text läses ut för analys.</p>
    <div class="ocr-status-card">
      <div class="ocr-status-row">
        <span class="ocr-status-label">JBIG2-optimering</span>
        <div class="ocr-status-actions">
          <span id="jbig2-status-badge-wrap" class="ocr-status-badge-wrap">
            <span id="jbig2-status-badge" class="ocr-status-badge">Kontrollerar...</span>
          </span>
          <button id="jbig2-local-install-button" class="ocr-status-action-button" type="button" disabled title="Lokal installation stöds inte ännu">Installera lokalt</button>
          <button id="jbig2-refresh-button" class="ocr-status-refresh hidden" type="button" aria-label="Kontrollera igen" title="Kontrollera igen">↻</button>
        </div>
      </div>
      <p>JBIG2 används för att komprimera svartvita skannade bilder effektivare. Om det finns installerat kan OCRmyPDF ofta ge betydligt mindre PDF-filer.</p>
      <p>Kommando för att installera globalt:</p>
      <div class="settings-command-wrap">
        <button class="settings-command-copy" type="button" data-copy-target="jbig2-install-command">Kopiera</button>
        <pre id="jbig2-install-command" class="settings-command"></pre>
      </div>
    </div>
    <div id="python-status-card" class="ocr-status-card">
      <div class="ocr-status-row">
        <span class="ocr-status-label">Python 3</span>
        <div class="ocr-status-actions">
          <span id="python-status-badge-wrap" class="ocr-status-badge-wrap">
            <span id="python-status-badge" class="ocr-status-badge">Kontrollerar...</span>
          </span>
          <button id="python-local-install-button" class="ocr-status-action-button" type="button" disabled title="Lokal installation stöds inte ännu">Installera lokalt</button>
          <button id="python-refresh-button" class="ocr-status-refresh hidden" type="button" aria-label="Kontrollera igen" title="Kontrollera igen">↻</button>
        </div>
      </div>
      <p>Python 3 behövs för Python-baserade OCR-tillägg och för att kunna använda RapidOCR i Docflow.</p>
      <p>Kommando för att installera globalt:</p>
      <div class="settings-command-wrap">
        <button class="settings-command-copy" type="button" data-copy-target="python-install-command">Kopiera</button>
        <pre id="python-install-command" class="settings-command"></pre>
      </div>
      <div class="ocr-status-card ocr-status-card-child">
        <div class="ocr-status-row">
          <span class="ocr-status-label">RapidOCR</span>
          <div class="ocr-status-actions">
            <span id="rapidocr-status-badge-wrap" class="ocr-status-badge-wrap">
              <span id="rapidocr-status-badge" class="ocr-status-badge">Kontrollerar...</span>
            </span>
            <button id="rapidocr-install-log-button" class="ocr-status-action-button hidden" type="button">Visa logg</button>
            <button id="rapidocr-local-install-button" class="ocr-status-action-button" type="button" disabled>Installera lokalt</button>
            <button id="rapidocr-refresh-button" class="ocr-status-refresh hidden" type="button" aria-label="Kontrollera igen" title="Kontrollera igen">↻</button>
          </div>
        </div>
        <p>RapidOCR är ett Python-paket för kompletterande OCR-relaterade funktioner utanför OCRmyPDF-flödet.</p>
        <p>Kommando för att installera globalt:</p>
        <div class="settings-command-wrap">
          <button class="settings-command-copy" type="button" data-copy-target="rapidocr-install-command">Kopiera</button>
          <pre id="rapidocr-install-command" class="settings-command"></pre>
        </div>
      </div>
    </div>
    <p>OCRmyPDF-kommandot som körs är i praktiken:</p>
    <div class="settings-command-wrap">
      <button class="settings-command-copy" type="button" data-copy-target="ocr-processing-command">Kopiera</button>
      <pre id="ocr-processing-command" class="settings-command"></pre>
    </div>
    <p>Textuttag (OCR-data) görs i praktiken med:</p>
    <div class="settings-command-wrap">
      <button class="settings-command-copy" type="button" data-copy-target="ocr-text-extraction-command">Kopiera</button>
      <pre id="ocr-text-extraction-command" class="settings-command"></pre>
    </div>
    <p class="settings-help">Vid <code>bbox-grid</code> läser Docflow XML från <code>pdftotext -bbox-layout</code> och bygger sedan en rutnätstext från samma data.</p>
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
      <button id="ocr-processing-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="ocr-processing-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-archive-structure">
    <h3>Arkivstruktur</h3>
    <p>Hantera mappar och filnamnsregler. Varje mapp har en sökvägsmall, en matchningsprioritet och egna filnamnsregler som väljs inom mappen.</p>
    <div class="archive-structure-toolbar">
      <label class="archive-structure-sort" for="archive-structure-folder-sort">
        <span>Sortera mappar i vyn</span>
        <select id="archive-structure-folder-sort">
          <option value="name">Namn</option>
          <option value="priority">Prioritet</option>
          <option value="path">Sökväg</option>
        </select>
      </label>
    </div>
    <div id="archive-structure-list" class="categories-list"></div>
    <div class="categories-actions">
      <button id="archive-structure-add-folder" type="button">Lägg till mapp</button>
    </div>

    <div class="panel-actions">
      <button id="archive-structure-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="archive-structure-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-labels">
    <h3>Etiketter</h3>
    <p>Globala etiketter som matchas mot OCR-texten med samma regelmodell som arkivregler.</p>
    <div class="archive-subtabs hidden">
      <button id="labels-tab-custom" class="archive-subtab active" type="button" data-labels-tab="labels">Etiketter</button>
      <button id="labels-tab-system" class="archive-subtab" type="button" data-labels-tab="system" hidden>Systemetiketter</button>
    </div>
    <div id="labels-view-custom" class="archive-view">
      <div id="labels-list" class="categories-list"></div>
      <div class="categories-actions">
        <button id="labels-add-row" type="button">Lägg till etikett</button>
        <button id="labels-import-row" type="button">Importera etikett</button>
      </div>
    </div>
    <div id="labels-view-system" class="archive-view hidden">
      <div id="system-label-editor" class="categories-list"></div>
    </div>
    <div class="panel-actions">
      <button id="labels-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="labels-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-data-fields">
    <h3>Datafält</h3>
    <p>Definiera egna datafält som extraheras från OCR-texten med samma märkordslogik som övrig fakturautvinning.</p>
    <div class="archive-subtabs">
      <button id="extraction-fields-tab-custom" class="archive-subtab active" type="button" data-extraction-fields-tab="fields">Datafält</button>
      <button id="extraction-fields-tab-system" class="archive-subtab" type="button" data-extraction-fields-tab="system">Systemdatafält</button>
    </div>
    <div id="extraction-fields-view-custom" class="archive-view">
      <div id="extraction-fields-editor" class="categories-list"></div>
    </div>
    <div id="extraction-fields-view-system" class="archive-view hidden">
      <div id="system-extraction-fields-editor" class="categories-list"></div>
    </div>
    <div class="categories-actions">
      <button id="extraction-fields-add-row" type="button">Lägg till datafält</button>
    </div>
    <div class="panel-actions">
      <button id="extraction-fields-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="extraction-fields-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-paths">
    <h3>Sökvägar</h3>
    <p>Ange sökvägen för in-mappen som läses av för nya jobb, samt grundsökväg för utdata.</p>
    <label class="settings-label" for="input-inbox-path">In-mapp för nya jobb</label>
    <input
      id="input-inbox-path"
      type="text"
      placeholder="/absolute/path/to/inbox"
      spellcheck="false"
      autocorrect="off"
      autocapitalize="off"
    >
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
      <button id="paths-cancel" class="button-danger" type="button">Avbryt</button>
      <button id="paths-apply" class="button-success" type="button">Spara</button>
    </div>
  </template>

  <template id="settings-template-system">
    <h3>System</h3>
    <label class="settings-label" for="system-state-transport">Uppdateringsmetod</label>
    <select id="system-state-transport" class="settings-select">
      <option value="polling">Polling</option>
      <option value="sse">Automatisk push (SSE)</option>
    </select>
    <div class="settings-danger">
      <p>Återställ alla oarkiverade jobb och flytta tillbaka deras <code>source.pdf</code> till inbox. Arkiverade dokument lämnas orörda.</p>
      <button id="settings-reset-jobs" type="button">Återställ oarkiverade jobb</button>
    </div>
  </template>

  <template id="settings-template-extensions">
    <h3>Tillägg</h3>
    <section class="system-extension-card">
      <div class="system-extension-header-row">
        <div>
          <h4>Chrome-tillägg</h4>
          <p class="system-extension-lead">Detta tillägg används för att hämta information om bankgiro och plusgiro via Swedbank.</p>
          <p class="system-extension-lead">Det gör att Docflow kan identifiera avsändare bättre och automatisera delar av processen när sådana nummer hittas i dokument.</p>
        </div>
        <span id="system-chrome-extension-status" class="ocr-status-badge">Kontrollerar...</span>
      </div>

      <div class="system-extension-section-title">Instruktioner för att installera tillägget</div>

      <div class="system-extension-install-step">
        <div class="system-extension-step-title">1. Öppna Chrome Extensions-sidan.</div>
        <div class="system-extension-copy-row">
          <input id="system-chrome-extension-page" class="settings-select system-extension-path" type="text" value="chrome://extensions" readonly>
          <button id="system-chrome-extension-copy-page" type="button">Kopiera adress</button>
        </div>
      </div>

      <div class="system-extension-install-step">
        <div class="system-extension-step-title">2. Slå på "Developer mode" (uppe till höger).</div>
      </div>

      <div class="system-extension-install-step">
        <div class="system-extension-step-title">3. Klicka "Läs in opaketerat" (eller "Load unpacked").</div>
      </div>

      <div class="system-extension-install-step">
        <div class="system-extension-step-title">4. Välj denna mapp:</div>
        <div class="system-extension-copy-row">
          <input id="system-chrome-extension-directory" class="settings-select system-extension-path" type="text" value="" readonly>
          <button id="system-chrome-extension-copy-directory" type="button">Kopiera sökväg</button>
        </div>
      </div>

      <div class="system-extension-actions">
        <button id="system-chrome-extension-test" type="button">Testa tillägget</button>
      </div>
      <label class="system-extension-checkbox">
        <input id="system-chrome-extension-suppress-missing" type="checkbox">
        <span>Dölj notis när tillägget saknas eller inte svarar.</span>
      </label>
      <div id="system-chrome-extension-debug" class="system-extension-debug"></div>
    </section>
  </template>

  <script src="/app.js"></script>
</body>
</html>
