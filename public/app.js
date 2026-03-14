const jobListEl = document.getElementById('job-list');
const pdfStackEl = document.getElementById('pdf-stack');
const pdfFrameEls = Array.from(document.querySelectorAll('.pdf-frame'));
const ocrViewEl = document.getElementById('ocr-view');
const ocrHighlightViewEl = document.getElementById('ocr-highlight-view');
const matchesViewEl = document.getElementById('matches-view');
const metaViewEl = document.getElementById('meta-view');
const viewModeEl = document.getElementById('view-mode');
const ocrSearchBarEl = document.getElementById('ocr-search-bar');
const ocrSearchInputEl = document.getElementById('ocr-search-input');
const ocrSearchRegexEl = document.getElementById('ocr-search-regex');
const ocrSearchPrevEl = document.getElementById('ocr-search-prev');
const ocrSearchNextEl = document.getElementById('ocr-search-next');
const ocrSearchStatusEl = document.getElementById('ocr-search-status');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const clientSelectEl = document.getElementById('client-select');
const senderSelectEl = document.getElementById('sender-select');
const categorySelectEl = document.getElementById('category-select');
const settingsButtonEl = document.getElementById('settings-button');
const settingsModalEl = document.getElementById('settings-modal');
const settingsTabEls = Array.from(document.querySelectorAll('[data-settings-tab]'));
const clientsTextareaEl = document.getElementById('clients-textarea');
const clientsCancelEl = document.getElementById('clients-cancel');
const clientsApplyEl = document.getElementById('clients-apply');
const matchingListEl = document.getElementById('matching-list');
const matchingAddRowEl = document.getElementById('matching-add-row');
const matchingCancelEl = document.getElementById('matching-cancel');
const matchingApplyEl = document.getElementById('matching-apply');
const matchingInvoiceThresholdEl = document.getElementById('matching-invoice-threshold');
const ocrSkipExistingTextEl = document.getElementById('ocr-skip-existing-text');
const ocrOptimizeLevelEl = document.getElementById('ocr-optimize-level');
const ocrTextExtractionMethodEl = document.getElementById('ocr-text-extraction-method');
const ocrPdfSubstitutionsListEl = document.getElementById('ocr-pdf-substitutions-list');
const ocrPdfSubstitutionsAddRowEl = document.getElementById('ocr-pdf-substitutions-add-row');
const ocrProcessingCommandEl = document.getElementById('ocr-processing-command');
const jbig2StatusBadgeWrapEl = document.getElementById('jbig2-status-badge-wrap');
const jbig2StatusBadgeEl = document.getElementById('jbig2-status-badge');
const jbig2InstallCommandEl = document.getElementById('jbig2-install-command');
const jbig2RefreshButtonEl = document.getElementById('jbig2-refresh-button');
const ocrProcessingCancelEl = document.getElementById('ocr-processing-cancel');
const ocrProcessingApplyEl = document.getElementById('ocr-processing-apply');
const categoriesListEl = document.getElementById('categories-list');
const systemCategoryEditorEl = document.getElementById('system-category-editor');
const categoriesAddCategoryEl = document.getElementById('categories-add-category');
const categoriesCancelEl = document.getElementById('categories-cancel');
const categoriesApplyEl = document.getElementById('categories-apply');
const archiveTabEls = Array.from(document.querySelectorAll('[data-archive-tab]'));
const archiveViewCategoriesEl = document.getElementById('archive-view-categories');
const archiveViewSystemEl = document.getElementById('archive-view-system');
const settingsResetJobsEl = document.getElementById('settings-reset-jobs');
const settingsCloseEl = document.getElementById('settings-close');
const outputBasePathEl = document.getElementById('output-base-path');
const pathsCancelEl = document.getElementById('paths-cancel');
const pathsApplyEl = document.getElementById('paths-apply');
const selectedJobPanelEl = document.getElementById('selected-job-panel');
const selectedJobNameEl = document.getElementById('selected-job-name');
const selectedJobMetaEl = document.getElementById('selected-job-meta');
const selectedJobReprocessEl = document.getElementById('selected-job-reprocess');
const selectedJobRerunOcrEl = document.getElementById('selected-job-rerun-ocr');

let state = {
  processingJobs: [],
  readyJobs: [],
  failedJobs: [],
  clients: [],
  senders: [],
  categories: []
};

const SYSTEM_CATEGORIES = {
  invoice: {
    name: 'Faktura',
    minScore: 2,
    rules: [
      { text: 'faktura', score: 4 },
      { text: 'förfallodatum', score: 3 },
      { text: 'faktura', score: 2 },
      { text: 'förfallodatum', score: 3 },
      { text: 'bankgiro', score: 5 },
      { text: 'plusgiro', score: 5 },
      { text: 'ocr', score: 5 },
      { text: 'ocr-nummer', score: 5 },
      { text: 'fakturanummer', score: 5 },
      { text: 'autogiro', score: 3 },
      { text: 'e-faktura', score: 4 },
      { text: 'betalningsmottagare', score: 2 }
    ]
  }
};

let selectedJobId = '';
let loadedOcrJobId = '';
let loadedMatchesJobId = '';
let loadedMetaJobId = '';
let pdfFrameJobIds = pdfFrameEls.map(() => '');
let pollTimer = null;
let pollInFlight = false;
let currentViewMode = 'pdf';
let ocrRequestSeq = 0;
let ocrSearchMatches = [];
let ocrSearchActiveIndex = -1;
let ocrSearchDragState = null;
let matchesRequestSeq = 0;
let metaRequestSeq = 0;
let categoriesDraft = [];
let systemCategoriesDraft = createDefaultSystemCategories();
let matchingDraft = [];
let matchingInvoiceFieldMinConfidenceDraft = 0.7;
let ocrSkipExistingTextBaseline = true;
let ocrOptimizeLevelBaseline = 1;
let ocrTextExtractionMethodBaseline = 'layout';
let ocrPdfSubstitutionsDraft = [];
let ocrPdfSubstitutionsBaselineJson = JSON.stringify([]);
let activeSettingsTabId = 'clients';
let activeArchiveTabId = 'categories';
let clientsBaselineText = '';
let matchingBaselineJson = JSON.stringify({
  replacements: [],
  invoiceFieldMinConfidence: 0.7
});
let pathsBaselineValue = '';
let categoriesBaselineJson = JSON.stringify({
  archiveFolders: [],
  systemCategories: systemCategoriesDraft
});
let clientOptionsSignature = '';
let senderOptionsSignature = '';
let categoryOptionsSignature = '';
let hasLoadedClients = false;
let hasLoadedSenders = false;
let hasLoadedCategories = false;
let hasLoadedInitialJobsState = false;
const selectedClientByJobId = new Map();
const selectedSenderByJobId = new Map();
const selectedCategoryByJobId = new Map();
const seenFailedJobKeys = new Set();
const EDIT_CLIENTS_OPTION_VALUE = '__edit_clients__';
const EDIT_CATEGORIES_OPTION_VALUE = '__edit_categories__';

clientSelectEl.disabled = true;
senderSelectEl.disabled = true;
categorySelectEl.disabled = true;
matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);
ocrSkipExistingTextEl.checked = ocrSkipExistingTextBaseline;
ocrOptimizeLevelEl.value = String(ocrOptimizeLevelBaseline);
ocrTextExtractionMethodEl.value = ocrTextExtractionMethodBaseline;
setOcrSearchButtonsEnabled(false);
setOcrSearchStatus('');

function setProcessingInfo(processingJobs) {
  if (!Array.isArray(processingJobs) || processingJobs.length === 0) {
    processingIndicatorEl.classList.add('hidden');
    processingTextEl.textContent = '';
    return;
  }

  processingIndicatorEl.classList.remove('hidden');
  processingTextEl.textContent = `Bearbetar ${processingJobs.length} fil(er)...`;
}

function syncSelectOptions(selectEl, placeholderText, options, lastSignature) {
  const signature = JSON.stringify(options);
  if (signature === lastSignature) {
    return lastSignature;
  }

  const currentValue = selectEl.value;
  selectEl.innerHTML = '';

  const placeholderOption = document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.hidden = true;
  placeholderOption.textContent = placeholderText;
  selectEl.appendChild(placeholderOption);

  options.forEach((item) => {
    const option = document.createElement('option');
    option.value = item.value;
    option.textContent = item.label;
    selectEl.appendChild(option);
  });

  const hasCurrentValue = options.some((item) => item.value === currentValue);
  selectEl.value = hasCurrentValue ? currentValue : '';

  return signature;
}

function renderClientSelect(clients) {
  const options = clients
    .filter((client) => client && typeof client.dirName === 'string' && client.dirName.trim() !== '')
    .map((client) => ({
      value: client.dirName,
      label: client.dirName
    }));
  const signature = JSON.stringify({
    action: EDIT_CLIENTS_OPTION_VALUE,
    options
  });
  if (signature === clientOptionsSignature) {
    return;
  }

  const currentValue = clientSelectEl.value;
  clientSelectEl.innerHTML = '';

  const placeholderOption = document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.hidden = true;
  placeholderOption.textContent = 'Välj huvudman';
  clientSelectEl.appendChild(placeholderOption);

  const editOption = document.createElement('option');
  editOption.value = EDIT_CLIENTS_OPTION_VALUE;
  editOption.textContent = 'Redigera huvudmän...';
  clientSelectEl.appendChild(editOption);

  const separatorOption = document.createElement('option');
  separatorOption.value = '__separator__';
  separatorOption.textContent = '──────────';
  separatorOption.disabled = true;
  clientSelectEl.appendChild(separatorOption);

  options.forEach((item) => {
    const option = document.createElement('option');
    option.value = item.value;
    option.textContent = item.label;
    clientSelectEl.appendChild(option);
  });

  const hasCurrentValue = options.some((item) => item.value === currentValue);
  clientSelectEl.value = hasCurrentValue ? currentValue : '';
  clientOptionsSignature = signature;
}

function renderSenderSelect(senders) {
  const options = senders
    .map((sender) => ({
      value: sender && typeof sender.slug === 'string' ? sender.slug.trim() : '',
      label: sender && typeof sender.name === 'string' ? sender.name.trim() : ''
    }))
    .filter((sender) => sender.value !== '' && sender.label !== '');
  senderOptionsSignature = syncSelectOptions(senderSelectEl, 'Välj avsändare', options, senderOptionsSignature);
}

function categoryDisplayName(category) {
  if (category && typeof category.name === 'string' && category.name.trim() !== '') {
    return category.name.trim();
  }
  if (category && typeof category.path === 'string' && category.path.trim() !== '') {
    return category.path.trim();
  }
  return '';
}

function renderCategorySelect(categories) {
  const options = categories
    .filter((category) => !Boolean(category && category.isSystemCategory))
    .map((category) => ({
      value: category && typeof category.id === 'string' ? category.id.trim() : '',
      label: categoryDisplayName(category)
    }))
    .filter((category) => category.value !== '' && category.label !== '');
  const signature = JSON.stringify({
    action: EDIT_CATEGORIES_OPTION_VALUE,
    options
  });
  if (signature === categoryOptionsSignature) {
    return;
  }

  const currentValue = categorySelectEl.value;
  categorySelectEl.innerHTML = '';

  const placeholderOption = document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.hidden = true;
  placeholderOption.textContent = 'Välj kategori';
  categorySelectEl.appendChild(placeholderOption);

  const editOption = document.createElement('option');
  editOption.value = EDIT_CATEGORIES_OPTION_VALUE;
  editOption.textContent = 'Redigera kategorier...';
  categorySelectEl.appendChild(editOption);

  const separatorOption = document.createElement('option');
  separatorOption.value = '__separator__';
  separatorOption.textContent = '──────────';
  separatorOption.disabled = true;
  categorySelectEl.appendChild(separatorOption);

  options.forEach((item) => {
    const option = document.createElement('option');
    option.value = item.value;
    option.textContent = item.label;
    categorySelectEl.appendChild(option);
  });

  const hasCurrentValue = options.some((item) => item.value === currentValue);
  categorySelectEl.value = hasCurrentValue ? currentValue : '';
  categoryOptionsSignature = signature;
}

function setClientForJob(job) {
  clientSelectEl.disabled = !job;

  if (!job) {
    clientSelectEl.value = '';
    return;
  }

  const manualValue = selectedClientByJobId.get(job.id);
  if (manualValue) {
    const hasManualOption = Array.from(clientSelectEl.options).some(
      (option) => option.value === manualValue
    );
    if (hasManualOption) {
      clientSelectEl.value = manualValue;
      return;
    }
  }

  if (!job.matchedClientDirName) {
    clientSelectEl.value = '';
    return;
  }

  const hasOption = Array.from(clientSelectEl.options).some(
    (option) => option.value === job.matchedClientDirName
  );

  clientSelectEl.value = hasOption ? job.matchedClientDirName : '';
}

function setSenderForJob(job) {
  senderSelectEl.disabled = !job;

  if (!job) {
    senderSelectEl.value = '';
    return;
  }

  const manualValue = selectedSenderByJobId.get(job.id);
  if (manualValue) {
    const hasManualOption = Array.from(senderSelectEl.options).some(
      (option) => option.value === manualValue
    );
    if (hasManualOption) {
      senderSelectEl.value = manualValue;
      return;
    }
  }

  if (!job.matchedSenderSlug) {
    senderSelectEl.value = '';
    return;
  }

  const hasOption = Array.from(senderSelectEl.options).some(
    (option) => option.value === job.matchedSenderSlug
  );
  senderSelectEl.value = hasOption ? job.matchedSenderSlug : '';
}

function setCategoryForJob(job) {
  categorySelectEl.disabled = !job;

  if (!job) {
    categorySelectEl.value = '';
    return;
  }

  const manualValue = selectedCategoryByJobId.get(job.id);
  if (manualValue) {
    const hasManualOption = Array.from(categorySelectEl.options).some(
      (option) => option.value === manualValue
    );
    if (hasManualOption) {
      categorySelectEl.value = manualValue;
      return;
    }
  }

  const topScore = Number(job.topMatchedCategoryScore ?? 0);
  const topId = typeof job.topMatchedCategoryId === 'string' ? job.topMatchedCategoryId : '';
  if (!(topScore > 0) || topId === '') {
    categorySelectEl.value = '';
    return;
  }

  const hasOptionById = Array.from(categorySelectEl.options).some(
    (option) => option.value === topId
  );
  categorySelectEl.value = hasOptionById ? topId : '';
}

function pdfUrlForJob(jobId) {
  return '/api/get-job-pdf.php?id=' + encodeURIComponent(jobId);
}

function clearPdfFrames() {
  pdfFrameEls.forEach((frameEl, frameIndex) => {
    frameEl.classList.remove('active');
    if (pdfFrameJobIds[frameIndex] !== '') {
      frameEl.removeAttribute('src');
      pdfFrameJobIds[frameIndex] = '';
    }
  });
}

function setPdfFrameJob(frameIndex, jobId) {
  const frameEl = pdfFrameEls[frameIndex];
  if (!frameEl) {
    return;
  }

  const normalizedJobId = typeof jobId === 'string' ? jobId : '';
  if (pdfFrameJobIds[frameIndex] === normalizedJobId) {
    return;
  }

  pdfFrameJobIds[frameIndex] = normalizedJobId;
  if (normalizedJobId === '') {
    frameEl.removeAttribute('src');
    return;
  }

  frameEl.src = pdfUrlForJob(normalizedJobId);
}

function updatePdfFrameWindow(jobId) {
  if (!jobId) {
    clearPdfFrames();
    return;
  }

  const selectedIndex = state.readyJobs.findIndex((job) => job.id === jobId);
  if (selectedIndex < 0) {
    clearPdfFrames();
    return;
  }

  const prevId = selectedIndex > 0 ? state.readyJobs[selectedIndex - 1].id : '';
  const currId = state.readyJobs[selectedIndex].id;
  const nextId = selectedIndex < state.readyJobs.length - 1 ? state.readyJobs[selectedIndex + 1].id : '';
  const slotJobIds = [prevId, currId, nextId];
  const slotFrameIndexes = [-1, -1, -1];
  const freeFrameIndexes = pdfFrameEls.map((_, index) => index);
  const slotOrder = [1, 0, 2];

  slotOrder.forEach((slotIndex) => {
    const targetJobId = slotJobIds[slotIndex];
    if (!targetJobId) {
      return;
    }

    const existingFrameIndex = pdfFrameJobIds.findIndex(
      (loadedJobId, frameIndex) => loadedJobId === targetJobId && freeFrameIndexes.includes(frameIndex)
    );
    if (existingFrameIndex < 0) {
      return;
    }

    slotFrameIndexes[slotIndex] = existingFrameIndex;
    const freeIndex = freeFrameIndexes.indexOf(existingFrameIndex);
    if (freeIndex >= 0) {
      freeFrameIndexes.splice(freeIndex, 1);
    }
  });

  slotOrder.forEach((slotIndex) => {
    const targetJobId = slotJobIds[slotIndex];
    if (!targetJobId || slotFrameIndexes[slotIndex] >= 0) {
      return;
    }

    const nextFreeFrame = freeFrameIndexes.shift();
    if (typeof nextFreeFrame !== 'number') {
      return;
    }
    slotFrameIndexes[slotIndex] = nextFreeFrame;
  });

  const targetJobIdsByFrame = pdfFrameEls.map(() => '');
  slotFrameIndexes.forEach((frameIndex, slotIndex) => {
    if (frameIndex < 0) {
      return;
    }
    targetJobIdsByFrame[frameIndex] = slotJobIds[slotIndex];
  });

  targetJobIdsByFrame.forEach((targetJobId, frameIndex) => {
    setPdfFrameJob(frameIndex, targetJobId);
  });

  pdfFrameEls.forEach((frameEl, frameIndex) => {
    frameEl.classList.toggle('active', frameIndex === slotFrameIndexes[1]);
  });
}

function setViewerJob(jobId) {
  if (currentViewMode === 'ocr') {
    setViewerOcr(jobId);
  } else if (currentViewMode === 'matches') {
    setViewerMatches(jobId);
  } else if (currentViewMode === 'meta') {
    setViewerMeta(jobId);
  } else {
    setViewerPdf(jobId);
  }
}

function setViewerPdf(jobId) {
  setOcrSearchVisible(false);
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  pdfStackEl.classList.remove('hidden');
  updatePdfFrameWindow(jobId);
}

async function setViewerOcr(jobId) {
  setOcrSearchVisible(true);
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  pdfStackEl.classList.add('hidden');
  ocrHighlightViewEl.classList.remove('hidden');
  ocrViewEl.classList.remove('hidden');

  if (!jobId) {
    loadedOcrJobId = '';
    ocrViewEl.value = '';
    refreshOcrSearch();
    return;
  }

  if (loadedOcrJobId === jobId) {
    return;
  }

  loadedOcrJobId = jobId;
  const requestSeq = ++ocrRequestSeq;
  ocrViewEl.value = 'Laddar OCR-data...';
  refreshOcrSearch();

  try {
    const response = await fetch('/api/get-job-ocr.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Kunde inte hämta OCR-data');
    }

    const payload = await response.json();
    if (requestSeq !== ocrRequestSeq) {
      return;
    }

    const text = payload && typeof payload.text === 'string' ? payload.text : '';
    ocrViewEl.value = text || '(Ingen OCR-text hittades)';
    refreshOcrSearch();
  } catch (error) {
    if (requestSeq !== ocrRequestSeq) {
      return;
    }
    ocrViewEl.value = 'Kunde inte ladda OCR-data.';
    refreshOcrSearch();
  }
}

function appendMatchesSection(container, title, categories, emptyText) {
  const header = document.createElement('h3');
  header.className = 'matches-header';
  header.textContent = title;
  container.appendChild(header);

  if (!Array.isArray(categories) || categories.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = emptyText;
    container.appendChild(empty);
    return;
  }

  const tableWrap = document.createElement('div');
  tableWrap.className = 'matches-table-wrap';
  const table = document.createElement('table');
  table.className = 'matches-table';

  const thead = document.createElement('thead');
  const headerRow = document.createElement('tr');
  ['Kategori', 'Totalpoäng', 'Minpoäng', 'Regeltext', 'Matchad text', 'Regelpoäng'].forEach((label) => {
    const th = document.createElement('th');
    th.textContent = label;
    if (label === 'Regelpoäng' || label === 'Totalpoäng' || label === 'Minpoäng') {
      th.className = 'is-numeric';
    }
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');

  categories.forEach((category) => {
    const name = category && typeof category.name === 'string' && category.name !== ''
      ? category.name
      : 'Namnlös kategori';
    const score = category && Number.isFinite(Number(category.score))
      ? Number(category.score)
      : 0;
    const minScore = category && Number.isFinite(Number(category.minScore))
      ? Number(category.minScore)
      : 1;

    const rules = category && Array.isArray(category.matchedRules) ? category.matchedRules : [];
    if (rules.length === 0) {
      const tr = document.createElement('tr');

      const categoryCell = document.createElement('td');
      categoryCell.textContent = name;
      tr.appendChild(categoryCell);

      const totalCell = document.createElement('td');
      totalCell.className = 'is-numeric';
      totalCell.textContent = String(score);
      tr.appendChild(totalCell);

      const minCell = document.createElement('td');
      minCell.className = 'is-numeric';
      minCell.textContent = String(minScore);
      tr.appendChild(minCell);

      const ruleCell = document.createElement('td');
      ruleCell.textContent = '(Inga ord matchade)';
      tr.appendChild(ruleCell);

      const sourceCell = document.createElement('td');
      sourceCell.textContent = '';
      tr.appendChild(sourceCell);

      const ruleScoreCell = document.createElement('td');
      ruleScoreCell.className = 'is-numeric';
      ruleScoreCell.textContent = '0';
      tr.appendChild(ruleScoreCell);

      tbody.appendChild(tr);
      return;
    }

    rules.forEach((rule, ruleIndex) => {
      const text = rule && typeof rule.text === 'string' ? rule.text : '';
      const sourceText = rule && typeof rule.sourceText === 'string' && rule.sourceText !== ''
        ? rule.sourceText
        : '';
      const ruleScore = rule && Number.isFinite(Number(rule.score)) ? Number(rule.score) : 0;

      const tr = document.createElement('tr');

      if (ruleIndex === 0) {
        const categoryCell = document.createElement('td');
        categoryCell.textContent = name;
        categoryCell.rowSpan = rules.length;
        tr.appendChild(categoryCell);

        const totalCell = document.createElement('td');
        totalCell.className = 'is-numeric summary-cell';
        totalCell.textContent = String(score);
        totalCell.rowSpan = rules.length;
        tr.appendChild(totalCell);

        const minCell = document.createElement('td');
        minCell.className = 'is-numeric summary-cell';
        minCell.textContent = String(minScore);
        minCell.rowSpan = rules.length;
        tr.appendChild(minCell);
      }

      const ruleCell = document.createElement('td');
      ruleCell.textContent = text;
      tr.appendChild(ruleCell);

      const sourceCell = document.createElement('td');
      sourceCell.textContent = sourceText;
      tr.appendChild(sourceCell);

      const ruleScoreCell = document.createElement('td');
      ruleScoreCell.className = 'is-numeric';
      ruleScoreCell.textContent = String(ruleScore);
      tr.appendChild(ruleScoreCell);

      tbody.appendChild(tr);
    });
  });

  table.appendChild(tbody);
  tableWrap.appendChild(table);
  container.appendChild(tableWrap);
}

function renderMatchesContent(payload) {
  matchesViewEl.innerHTML = '';

  const categories = payload && Array.isArray(payload.categories) ? payload.categories : [];
  const systemCategories = payload && Array.isArray(payload.systemCategories) ? payload.systemCategories : [];

  appendMatchesSection(matchesViewEl, 'Kategorier', categories, 'Inga kategorimatchningar hittades.');
  appendMatchesSection(matchesViewEl, 'Systemkategorier', systemCategories, 'Inga systemkategorimatchningar hittades.');
}

async function setViewerMatches(jobId) {
  setOcrSearchVisible(false);
  pdfStackEl.classList.add('hidden');
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  matchesViewEl.classList.remove('hidden');

  if (!jobId) {
    loadedMatchesJobId = '';
    matchesViewEl.innerHTML = '';
    return;
  }

  if (loadedMatchesJobId === jobId) {
    return;
  }

  loadedMatchesJobId = jobId;
  const requestSeq = ++matchesRequestSeq;
  matchesViewEl.innerHTML = '';
  const loading = document.createElement('div');
  loading.className = 'matches-empty';
  loading.textContent = 'Laddar matchningar...';
  matchesViewEl.appendChild(loading);

  try {
    const response = await fetch('/api/get-job-matches.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Kunde inte hämta matchningar');
    }

    const payload = await response.json();
    if (requestSeq !== matchesRequestSeq) {
      return;
    }

    renderMatchesContent(payload);
  } catch (error) {
    if (requestSeq !== matchesRequestSeq) {
      return;
    }
    matchesViewEl.innerHTML = '';
    const fail = document.createElement('div');
    fail.className = 'matches-empty';
    fail.textContent = 'Kunde inte ladda matchningsdata.';
    matchesViewEl.appendChild(fail);
  }
}

async function setViewerMeta(jobId) {
  setOcrSearchVisible(false);
  pdfStackEl.classList.add('hidden');
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.remove('hidden');

  if (!jobId) {
    loadedMetaJobId = '';
    metaViewEl.textContent = '';
    return;
  }

  if (loadedMetaJobId === jobId) {
    return;
  }

  loadedMetaJobId = jobId;
  const requestSeq = ++metaRequestSeq;
  metaViewEl.textContent = 'Laddar metadata...';

  try {
    const response = await fetch('/api/get-job-meta.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Kunde inte hämta metadata');
    }

    const payload = await response.json();
    if (requestSeq !== metaRequestSeq) {
      return;
    }

    const job = payload && payload.job && typeof payload.job === 'object' ? payload.job : null;
    metaViewEl.textContent = job ? JSON.stringify(job, null, 2) : '{}';
  } catch (error) {
    if (requestSeq !== metaRequestSeq) {
      return;
    }
    metaViewEl.textContent = 'Kunde inte ladda metadata.';
  }
}

function appendJobListSectionLabel(text) {
  const li = document.createElement('li');
  li.className = 'job-section-label';
  li.textContent = text;
  jobListEl.appendChild(li);
}

function findJobById(jobId) {
  if (!jobId) {
    return null;
  }

  const allJobs = []
    .concat(Array.isArray(state.readyJobs) ? state.readyJobs : [])
    .concat(Array.isArray(state.failedJobs) ? state.failedJobs : []);

  return allJobs.find((job) => job.id === jobId) || null;
}

function renderSelectedJobPanel() {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    selectedJobPanelEl.classList.add('is-empty');
    selectedJobNameEl.textContent = 'Inget jobb markerat';
    selectedJobMetaEl.textContent = 'Markera ett jobb i listan för att visa åtgärder.';
    selectedJobReprocessEl.disabled = true;
    selectedJobRerunOcrEl.disabled = true;
    return;
  }

  selectedJobPanelEl.classList.remove('is-empty');
  selectedJobNameEl.textContent = selectedJob.originalFilename || selectedJob.id;

  const metaParts = [];
  if (selectedJob.status === 'failed' && selectedJob.error) {
    metaParts.push('Fel: ' + selectedJob.error);
  } else {
    metaParts.push(selectedJob.status === 'failed' ? 'Status: Misslyckat' : 'Status: Klar');
  }
  selectedJobMetaEl.textContent = metaParts.join(' | ');
  selectedJobReprocessEl.disabled = !selectedJob.hasReviewPdf;
  selectedJobRerunOcrEl.disabled = !selectedJob.hasSourcePdf;
}

function renderJobList(readyJobs, failedJobs) {
  jobListEl.innerHTML = '';
  const hasReadyJobs = Array.isArray(readyJobs) && readyJobs.length > 0;
  const hasFailedJobs = Array.isArray(failedJobs) && failedJobs.length > 0;

  if (!hasReadyJobs && !hasFailedJobs) {
    const li = document.createElement('li');
    li.className = 'job-message';
    li.textContent = 'Inga klara jobb ännu.';
    jobListEl.appendChild(li);
    return;
  }

  if (hasReadyJobs) {
    readyJobs.forEach((job) => {
      const li = document.createElement('li');
      li.className = 'job-item';
      if (job.id === selectedJobId) {
        li.classList.add('selected');
      }

      const name = document.createElement('div');
      name.className = 'job-name';
      name.textContent = job.originalFilename;
      li.appendChild(name);

      if (job.matchedClientDirName) {
        const client = document.createElement('div');
        client.className = 'job-client';
        client.textContent = job.matchedClientDirName;
        li.appendChild(client);
      }

      li.addEventListener('click', () => {
        applySelectedJobId(job.id);
      });

      jobListEl.appendChild(li);
    });
  }

  if (hasFailedJobs) {
    if (hasReadyJobs) {
      appendJobListSectionLabel('Misslyckade jobb');
    }

    failedJobs.forEach((job) => {
      const li = document.createElement('li');
      li.className = 'job-item failed';
      if (job.id === selectedJobId) {
        li.classList.add('selected');
      }

      const name = document.createElement('div');
      name.className = 'job-name';
      name.textContent = job.originalFilename;
      li.appendChild(name);

      if (job.error) {
        const error = document.createElement('div');
        error.className = 'job-error';
        error.textContent = job.error;
        li.appendChild(error);
      }

      li.addEventListener('click', () => {
        applySelectedJobId(job.id);
      });

      jobListEl.appendChild(li);
    });
  }

  renderSelectedJobPanel();
}

function notifyFailedJobs(failedJobs) {
  if (!Array.isArray(failedJobs)) {
    return;
  }

  const freshFailures = [];
  failedJobs.forEach((job) => {
    const errorText = typeof job.error === 'string' ? job.error.trim() : '';
    const key = `${job.id || ''}::${errorText}`;
    if (!seenFailedJobKeys.has(key)) {
      seenFailedJobKeys.add(key);
      if (hasLoadedInitialJobsState) {
        freshFailures.push({
          filename: typeof job.originalFilename === 'string' ? job.originalFilename : 'okänd fil',
          error: errorText || 'Okänt fel'
        });
      }
    }
  });

  if (freshFailures.length === 0) {
    return;
  }

  const lines = freshFailures.map((failure) => `${failure.filename}: ${failure.error}`);
  alert('Ett eller flera jobb misslyckades.\n\n' + lines.join('\n\n'));
}

function setOcrSearchVisible(visible) {
  ocrSearchBarEl.classList.toggle('hidden', !visible);
}

function clamp(value, minValue, maxValue) {
  if (value < minValue) {
    return minValue;
  }
  if (value > maxValue) {
    return maxValue;
  }
  return value;
}

function getViewerWrapRect() {
  return ocrSearchBarEl.parentElement.getBoundingClientRect();
}

function ensureOcrSearchAbsolutePosition() {
  if (ocrSearchBarEl.style.left !== '' && ocrSearchBarEl.style.top !== '') {
    return;
  }

  const viewerRect = getViewerWrapRect();
  const barRect = ocrSearchBarEl.getBoundingClientRect();
  ocrSearchBarEl.style.left = `${Math.max(0, barRect.left - viewerRect.left)}px`;
  ocrSearchBarEl.style.top = `${Math.max(0, barRect.top - viewerRect.top)}px`;
  ocrSearchBarEl.style.right = 'auto';
}

function startOcrSearchDrag(event) {
  if (event.target !== ocrSearchBarEl) {
    return;
  }

  ensureOcrSearchAbsolutePosition();
  const viewerRect = getViewerWrapRect();
  const barRect = ocrSearchBarEl.getBoundingClientRect();
  ocrSearchDragState = {
    offsetX: event.clientX - barRect.left,
    offsetY: event.clientY - barRect.top,
    viewerWidth: viewerRect.width,
    viewerHeight: viewerRect.height
  };
  ocrSearchBarEl.classList.add('is-dragging');
  event.preventDefault();
}

function moveOcrSearchDrag(event) {
  if (!ocrSearchDragState) {
    return;
  }

  const viewerRect = getViewerWrapRect();
  const barWidth = ocrSearchBarEl.offsetWidth;
  const barHeight = ocrSearchBarEl.offsetHeight;
  const nextLeft = clamp(
    event.clientX - viewerRect.left - ocrSearchDragState.offsetX,
    0,
    Math.max(0, viewerRect.width - barWidth)
  );
  const nextTop = clamp(
    event.clientY - viewerRect.top - ocrSearchDragState.offsetY,
    0,
    Math.max(0, viewerRect.height - barHeight)
  );
  ocrSearchBarEl.style.left = `${nextLeft}px`;
  ocrSearchBarEl.style.top = `${nextTop}px`;
  ocrSearchBarEl.style.right = 'auto';
}

function stopOcrSearchDrag() {
  if (!ocrSearchDragState) {
    return;
  }
  ocrSearchDragState = null;
  ocrSearchBarEl.classList.remove('is-dragging');
}

function setOcrSearchStatus(text, isError = false) {
  ocrSearchStatusEl.textContent = text;
  ocrSearchStatusEl.classList.toggle('is-error', isError);
}

function setOcrSearchButtonsEnabled(enabled) {
  ocrSearchPrevEl.disabled = !enabled;
  ocrSearchNextEl.disabled = !enabled;
}

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function buildOcrSearchMatches(text, query, useRegex) {
  if (!query) {
    return [];
  }

  if (!useRegex) {
    const matches = [];
    let startIndex = 0;
    while (startIndex <= text.length) {
      const foundIndex = text.indexOf(query, startIndex);
      if (foundIndex < 0) {
        break;
      }
      matches.push({
        start: foundIndex,
        end: foundIndex + query.length
      });
      startIndex = foundIndex + Math.max(query.length, 1);
    }
    return matches;
  }

  const regex = new RegExp(query, 'gm');
  const matches = [];
  let found;
  while ((found = regex.exec(text)) !== null) {
    const matchedText = typeof found[0] === 'string' ? found[0] : '';
    matches.push({
      start: found.index,
      end: found.index + matchedText.length
    });
    if (matchedText.length === 0) {
      regex.lastIndex += 1;
    }
  }
  return matches;
}

function applyOcrSearchMatch(index) {
  if (index < 0 || index >= ocrSearchMatches.length) {
    ocrSearchActiveIndex = -1;
    renderOcrHighlightLayer();
    return;
  }

  const match = ocrSearchMatches[index];
  ocrSearchActiveIndex = index;
  setOcrSearchStatus(`${index + 1} / ${ocrSearchMatches.length}`);
  renderOcrHighlightLayer();
  scrollOcrMatchIntoView(match);
}

function refreshOcrSearch() {
  const query = ocrSearchInputEl.value;
  const text = ocrViewEl.value || '';

  if (!query) {
    ocrSearchMatches = [];
    ocrSearchActiveIndex = -1;
    setOcrSearchButtonsEnabled(false);
    setOcrSearchStatus('');
    renderOcrHighlightLayer();
    return;
  }

  try {
    ocrSearchMatches = buildOcrSearchMatches(text, query, ocrSearchRegexEl.checked);
  } catch (error) {
    ocrSearchMatches = [];
    ocrSearchActiveIndex = -1;
    setOcrSearchButtonsEnabled(false);
    setOcrSearchStatus('Ogiltig regex', true);
    renderOcrHighlightLayer();
    return;
  }

  if (ocrSearchMatches.length === 0) {
    ocrSearchActiveIndex = -1;
    setOcrSearchButtonsEnabled(false);
    setOcrSearchStatus('0 träffar');
    renderOcrHighlightLayer();
    return;
  }

  setOcrSearchButtonsEnabled(true);
  ocrSearchActiveIndex = -1;
  setOcrSearchStatus(`${ocrSearchMatches.length} träffar`);
  renderOcrHighlightLayer();
}

function stepOcrSearch(direction) {
  if (ocrSearchMatches.length === 0) {
    return;
  }

  const lastIndex = ocrSearchMatches.length - 1;
  let nextIndex = ocrSearchActiveIndex;

  if (nextIndex < 0) {
    nextIndex = direction > 0 ? 0 : lastIndex;
  } else {
    nextIndex += direction;
    if (nextIndex > lastIndex) {
      nextIndex = 0;
    } else if (nextIndex < 0) {
      nextIndex = lastIndex;
    }
  }

  applyOcrSearchMatch(nextIndex);
}

function renderOcrHighlightLayer() {
  const text = ocrViewEl.value || '';
  if (ocrSearchMatches.length === 0) {
    ocrHighlightViewEl.textContent = text;
    syncOcrHighlightScroll();
    return;
  }

  let html = '';
  let cursor = 0;

  ocrSearchMatches.forEach((match, index) => {
    if (match.start > cursor) {
      html += escapeHtml(text.slice(cursor, match.start));
    }

    const matchedText = text.slice(match.start, match.end);
    const className = index === ocrSearchActiveIndex ? ' class="is-active"' : '';
    html += `<mark${className}>${escapeHtml(matchedText)}</mark>`;
    cursor = match.end;
  });

  if (cursor < text.length) {
    html += escapeHtml(text.slice(cursor));
  }

  ocrHighlightViewEl.innerHTML = html;
  syncOcrHighlightScroll();
}

function syncOcrHighlightScroll() {
  ocrHighlightViewEl.scrollTop = ocrViewEl.scrollTop;
  ocrHighlightViewEl.scrollLeft = ocrViewEl.scrollLeft;
}

function scrollOcrMatchIntoView(match) {
  const textBeforeMatch = ocrViewEl.value.slice(0, match.start);
  const lineIndex = textBeforeMatch.split('\n').length - 1;
  const lineStart = textBeforeMatch.lastIndexOf('\n') + 1;
  const columnIndex = match.start - lineStart;
  const styles = window.getComputedStyle(ocrViewEl);
  const lineHeight = parseFloat(styles.lineHeight) || 17.4;
  const fontSize = parseFloat(styles.fontSize) || 12;
  const approxCharWidth = fontSize * 0.6;
  const topPadding = parseFloat(styles.paddingTop) || 0;
  const leftPadding = parseFloat(styles.paddingLeft) || 0;
  const targetTop = Math.max(0, topPadding + (lineIndex * lineHeight) - (ocrViewEl.clientHeight / 3));
  const targetLeft = Math.max(0, leftPadding + (columnIndex * approxCharWidth) - 40);
  ocrViewEl.scrollTop = targetTop;
  ocrViewEl.scrollLeft = targetLeft;
  syncOcrHighlightScroll();
}

function applySelectedJobId(jobId) {
  const selectedJob = findJobById(jobId);
  selectedJobId = selectedJob ? selectedJob.id : '';
  renderJobList(state.readyJobs, state.failedJobs);
  setViewerJob(selectedJobId);
  setClientForJob(selectedJob);
  setSenderForJob(selectedJob);
  setCategoryForJob(selectedJob);
}

function moveSelectionBy(offset) {
  if (!Number.isInteger(offset) || offset === 0) {
    return;
  }

  if (!Array.isArray(state.readyJobs) || state.readyJobs.length === 0) {
    return;
  }

  const currentIndex = state.readyJobs.findIndex((job) => job.id === selectedJobId);
  const safeCurrent = currentIndex >= 0 ? currentIndex : 0;
  const nextIndex = Math.max(0, Math.min(state.readyJobs.length - 1, safeCurrent + offset));
  if (nextIndex === safeCurrent && currentIndex >= 0) {
    return;
  }

  const targetJob = state.readyJobs[nextIndex];
  if (!targetJob) {
    return;
  }

  applySelectedJobId(targetJob.id);
}

function refreshSelection() {
  const readyJobs = state.readyJobs;

  if (readyJobs.length === 0) {
    applySelectedJobId('');
    return;
  }

  const stillExists = readyJobs.some((job) => job.id === selectedJobId);
  if (!stillExists) {
    selectedJobId = readyJobs[0].id;
  }
  applySelectedJobId(selectedJobId);
}

function applyState(nextState) {
  const shouldUpdateClients = Array.isArray(nextState.clients);
  const shouldUpdateSenders = Array.isArray(nextState.senders);
  const shouldUpdateCategories = Array.isArray(nextState.categories);

  state = {
    processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : state.processingJobs,
    readyJobs: Array.isArray(nextState.readyJobs) ? nextState.readyJobs : state.readyJobs,
    failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : state.failedJobs,
    clients: shouldUpdateClients ? nextState.clients : state.clients,
    senders: shouldUpdateSenders ? nextState.senders : state.senders,
    categories: shouldUpdateCategories ? nextState.categories : state.categories
  };

  const validJobIds = new Set(state.readyJobs.map((job) => job.id));
  Array.from(selectedClientByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedClientByJobId.delete(jobId);
    }
  });
  Array.from(selectedSenderByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedSenderByJobId.delete(jobId);
    }
  });
  Array.from(selectedCategoryByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedCategoryByJobId.delete(jobId);
    }
  });

  setProcessingInfo(state.processingJobs);
  notifyFailedJobs(state.failedJobs);
  if (shouldUpdateClients) {
    renderClientSelect(state.clients);
  }
  if (shouldUpdateSenders) {
    renderSenderSelect(state.senders);
  }
  if (shouldUpdateCategories) {
    renderCategorySelect(state.categories);
  }
  refreshSelection();
  if (!hasLoadedInitialJobsState) {
    hasLoadedInitialJobsState = true;
  }
}

function openSettingsModal() {
  settingsModalEl.classList.remove('hidden');
}

async function openClientsSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('clients');

  try {
    await loadClientsText();
  } catch (error) {
    alert('Kunde inte ladda huvudmän.');
    clientsBaselineText = clientsTextareaEl.value;
    updateSettingsActionButtons();
    return false;
  }

  clientsTextareaEl.focus();
  updateSettingsActionButtons();
  return true;
}

async function openCategoriesSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('categories');

  try {
    await loadCategories();
  } catch (error) {
    alert('Kunde inte ladda arkivstruktur.');
    categoriesDraft = [];
    systemCategoriesDraft = createDefaultSystemCategories();
    categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
    renderCategoriesEditor();
    renderSystemCategoryEditor();
    updateSettingsActionButtons();
    return false;
  }

  setArchiveTab('categories');
  categoriesAddCategoryEl.focus();
  updateSettingsActionButtons();
  return true;
}

function closeSettingsModal(force = false) {
  if (!force && !canLeaveCurrentSettingsView()) {
    return false;
  }

  settingsModalEl.classList.add('hidden');
  return true;
}

function setSettingsTab(tabId) {
  activeSettingsTabId = tabId;

  settingsTabEls.forEach((tabButton) => {
    const isActive = tabButton.dataset.settingsTab === tabId;
    tabButton.classList.toggle('active', isActive);
  });

  const panelIds = ['clients', 'matching', 'ocr-processing', 'categories', 'jobs', 'paths'];
  panelIds.forEach((id) => {
    const panel = document.getElementById('settings-panel-' + id);
    if (!panel) {
      return;
    }
    panel.classList.toggle('hidden', id !== tabId);
    panel.classList.toggle('active', id === tabId);
  });
}

function isEditableSettingsTab(tabId) {
  return tabId === 'clients'
    || tabId === 'matching'
    || tabId === 'ocr-processing'
    || tabId === 'categories'
    || tabId === 'paths';
}

function normalizedPathValue(value) {
  return String(value).trim();
}

function sanitizeInvoiceFieldMinConfidence(value, fallback = 0.7) {
  const parsed = Number.parseFloat(String(value));
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  if (parsed < 0) {
    return 0;
  }
  if (parsed > 1) {
    return 1;
  }
  return Math.round(parsed * 1000) / 1000;
}

function sanitizeOcrOptimizeLevel(value, fallback = 1) {
  const parsed = Number.parseInt(String(value), 10);
  if (!Number.isInteger(parsed) || parsed < 0 || parsed > 3) {
    return fallback;
  }
  return parsed;
}

function sanitizeOcrTextExtractionMethod(value, fallback = 'layout') {
  const normalized = String(value).trim().toLowerCase();
  if (normalized === 'bbox') {
    return 'bbox';
  }
  if (normalized === 'layout') {
    return 'layout';
  }
  return fallback;
}

function normalizedMatchingJson(replacements, invoiceFieldMinConfidence) {
  return JSON.stringify({
    replacements: replacements.map(sanitizeReplacement),
    invoiceFieldMinConfidence: sanitizeInvoiceFieldMinConfidence(invoiceFieldMinConfidence, 0.7)
  });
}

function normalizedOcrPdfSubstitutionsJson(replacements) {
  return JSON.stringify(replacements.map(sanitizeReplacement));
}

function normalizedCategoriesJson(categories, systemCategories) {
  return JSON.stringify({
    archiveFolders: categories.map(sanitizeArchiveFolder),
    systemCategories: sanitizeSystemCategories(systemCategories)
  });
}

function isClientsDirty() {
  return clientsTextareaEl.value !== clientsBaselineText;
}

function isMatchingDirty() {
  return normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft) !== matchingBaselineJson;
}

function isCategoriesDirty() {
  return normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft) !== categoriesBaselineJson;
}

function isOcrProcessingDirty() {
  return ocrSkipExistingTextEl.checked !== ocrSkipExistingTextBaseline
    || sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1) !== ocrOptimizeLevelBaseline
    || sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout') !== ocrTextExtractionMethodBaseline
    || normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft) !== ocrPdfSubstitutionsBaselineJson;
}

function isPathsDirty() {
  return normalizedPathValue(outputBasePathEl.value) !== pathsBaselineValue;
}

function isSettingsTabDirty(tabId) {
  if (tabId === 'clients') {
    return isClientsDirty();
  }
  if (tabId === 'matching') {
    return isMatchingDirty();
  }
  if (tabId === 'categories') {
    return isCategoriesDirty();
  }
  if (tabId === 'ocr-processing') {
    return isOcrProcessingDirty();
  }
  if (tabId === 'paths') {
    return isPathsDirty();
  }
  return false;
}

function hasAnyUnsavedSettingsChanges() {
  return isClientsDirty() || isMatchingDirty() || isOcrProcessingDirty() || isCategoriesDirty() || isPathsDirty();
}

function panelActionButtonsForTab(tabId) {
  if (tabId === 'clients') {
    return [clientsCancelEl, clientsApplyEl];
  }
  if (tabId === 'matching') {
    return [matchingCancelEl, matchingApplyEl];
  }
  if (tabId === 'categories') {
    return [categoriesCancelEl, categoriesApplyEl];
  }
  if (tabId === 'ocr-processing') {
    return [ocrProcessingCancelEl, ocrProcessingApplyEl];
  }
  if (tabId === 'paths') {
    return [pathsCancelEl, pathsApplyEl];
  }
  return [];
}

function updateSettingsActionButtons() {
  const clientsDirty = isClientsDirty();
  const matchingDirty = isMatchingDirty();
  const ocrProcessingDirty = isOcrProcessingDirty();
  const categoriesDirty = isCategoriesDirty();
  const pathsDirty = isPathsDirty();

  clientsCancelEl.disabled = !clientsDirty;
  clientsApplyEl.disabled = !clientsDirty;

  matchingCancelEl.disabled = !matchingDirty;
  matchingApplyEl.disabled = !matchingDirty;

  ocrProcessingCancelEl.disabled = !ocrProcessingDirty;
  ocrProcessingApplyEl.disabled = !ocrProcessingDirty;

  categoriesCancelEl.disabled = !categoriesDirty;
  categoriesApplyEl.disabled = !categoriesDirty;

  pathsCancelEl.disabled = !pathsDirty;
  pathsApplyEl.disabled = !pathsDirty;
}

function flashPanelActions(tabId) {
  const buttons = panelActionButtonsForTab(tabId);
  buttons.forEach((button) => {
    button.classList.remove('flash');
    void button.offsetWidth;
    button.classList.add('flash');
    window.setTimeout(() => {
      button.classList.remove('flash');
    }, 700);
  });
}

function canLeaveCurrentSettingsView() {
  if (!isEditableSettingsTab(activeSettingsTabId)) {
    return true;
  }

  if (!isSettingsTabDirty(activeSettingsTabId)) {
    return true;
  }

  flashPanelActions(activeSettingsTabId);
  return false;
}

function defaultRule() {
  return {
    text: '',
    score: 1
  };
}

function defaultCategory() {
  return {
    name: '',
    minScore: 1,
    rules: [defaultRule()]
  };
}

function defaultArchiveFolder() {
  return {
    name: '',
    path: '',
    categories: [defaultCategory()]
  };
}

function sanitizePositiveInt(value, fallback = 1) {
  const parsed = parseInt(String(value), 10);
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  return parsed < 1 ? 1 : parsed;
}

function defaultReplacement() {
  return {
    from: '',
    to: ''
  };
}

function sanitizeReplacement(row) {
  const input = row && typeof row === 'object' ? row : {};
  return {
    from: typeof input.from === 'string' ? input.from : '',
    to: typeof input.to === 'string' ? input.to : ''
  };
}

function sanitizeRule(rule) {
  const input = rule && typeof rule === 'object' ? rule : {};
  return {
    text: typeof input.text === 'string' ? input.text : '',
    score: sanitizePositiveInt(input.score, 1)
  };
}

function sanitizeCategory(category) {
  const input = category && typeof category === 'object' ? category : {};
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeRule);
  return {
    name: typeof input.name === 'string' ? input.name : '',
    minScore: sanitizePositiveInt(input.minScore, 1),
    rules: rules.length > 0 ? rules : [defaultRule()]
  };
}

function sanitizeArchiveFolder(archiveFolder) {
  const input = archiveFolder && typeof archiveFolder === 'object' ? archiveFolder : {};
  const rawCategories = Array.isArray(input.categories) ? input.categories : [];
  const categories = rawCategories.map(sanitizeCategory);
  return {
    name: typeof input.name === 'string' ? input.name : '',
    path: typeof input.path === 'string' ? input.path : '',
    categories: categories.length > 0 ? categories : [defaultCategory()]
  };
}

function sanitizeSystemCategoryByKey(key, category) {
  const defaults = SYSTEM_CATEGORIES[key];
  const input = category && typeof category === 'object' ? category : {};
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeRule);
  return {
    name: typeof input.name === 'string' && input.name.trim() !== ''
      ? input.name
      : defaults.name,
    isSystemCategory: true,
    minScore: sanitizePositiveInt(input.minScore, sanitizePositiveInt(defaults.minScore, 1)),
    rules: rules.length > 0 ? rules : defaults.rules.map(sanitizeRule)
  };
}

function createDefaultSystemCategories() {
  const categories = {};
  Object.keys(SYSTEM_CATEGORIES).forEach((key) => {
    categories[key] = sanitizeSystemCategoryByKey(key, SYSTEM_CATEGORIES[key]);
  });
  return categories;
}

function sanitizeSystemCategories(systemCategories) {
  const input = systemCategories && typeof systemCategories === 'object' ? systemCategories : {};
  const categories = {};
  Object.keys(SYSTEM_CATEGORIES).forEach((key) => {
    categories[key] = sanitizeSystemCategoryByKey(key, input[key]);
  });
  return categories;
}

function createFloatingField(labelText, inputEl, extraClass = '') {
  const wrapper = document.createElement('div');
  wrapper.className = 'floating-input-group' + (extraClass ? ' ' + extraClass : '');

  const label = document.createElement('label');
  label.className = 'floating-input-label';
  label.textContent = labelText;

  wrapper.appendChild(label);
  wrapper.appendChild(inputEl);
  return wrapper;
}

function renderMatchingEditor() {
  matchingListEl.innerHTML = '';

  if (matchingDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga ersättningar ännu.';
    matchingListEl.appendChild(empty);
    return;
  }

  matchingDraft.forEach((row, rowIndex) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'matching-row';

    const fromInput = document.createElement('input');
    fromInput.type = 'text';
    fromInput.placeholder = 'Ex: é';
    fromInput.value = row.from;
    fromInput.addEventListener('input', () => {
      matchingDraft[rowIndex].from = fromInput.value;
      updateSettingsActionButtons();
    });

    const toInput = document.createElement('input');
    toInput.type = 'text';
    toInput.placeholder = 'Ex: ö';
    toInput.value = row.to;
    toInput.addEventListener('input', () => {
      matchingDraft[rowIndex].to = toInput.value;
      updateSettingsActionButtons();
    });

    rowEl.appendChild(createFloatingField('Från', fromInput, 'matching-char-field'));
    rowEl.appendChild(createFloatingField('Till', toInput, 'matching-char-field'));

    if (rowIndex > 0) {
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'rule-remove';
      removeButton.textContent = 'Ta bort';
      removeButton.addEventListener('click', () => {
        matchingDraft.splice(rowIndex, 1);
        if (matchingDraft.length === 0) {
          matchingDraft.push(defaultReplacement());
        }
        renderMatchingEditor();
        updateSettingsActionButtons();
      });
      rowEl.appendChild(removeButton);
    } else {
      const placeholder = document.createElement('div');
      placeholder.className = 'rule-remove-placeholder';
      rowEl.appendChild(placeholder);
    }

    matchingListEl.appendChild(rowEl);
  });

  updateSettingsActionButtons();
}

function renderOcrPdfSubstitutionsEditor() {
  ocrPdfSubstitutionsListEl.innerHTML = '';

  if (ocrPdfSubstitutionsDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga substitutioner ännu.';
    ocrPdfSubstitutionsListEl.appendChild(empty);
    return;
  }

  ocrPdfSubstitutionsDraft.forEach((row, rowIndex) => {
    const rowEl = document.createElement('div');
    rowEl.className = 'matching-row';

    const fromInput = document.createElement('input');
    fromInput.type = 'text';
    fromInput.placeholder = 'Ex: 0K:';
    fromInput.value = row.from;
    fromInput.addEventListener('input', () => {
      ocrPdfSubstitutionsDraft[rowIndex].from = fromInput.value;
      renderOcrProcessingCommand();
      updateSettingsActionButtons();
    });

    const toInput = document.createElement('input');
    toInput.type = 'text';
    toInput.placeholder = 'Ex: OK:';
    toInput.value = row.to;
    toInput.addEventListener('input', () => {
      ocrPdfSubstitutionsDraft[rowIndex].to = toInput.value;
      renderOcrProcessingCommand();
      updateSettingsActionButtons();
    });

    rowEl.appendChild(createFloatingField('Från', fromInput, 'matching-char-field'));
    rowEl.appendChild(createFloatingField('Till', toInput, 'matching-char-field'));

    if (rowIndex > 0) {
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'rule-remove';
      removeButton.textContent = 'Ta bort';
      removeButton.addEventListener('click', () => {
        ocrPdfSubstitutionsDraft.splice(rowIndex, 1);
        if (ocrPdfSubstitutionsDraft.length === 0) {
          ocrPdfSubstitutionsDraft.push(defaultReplacement());
        }
        renderOcrPdfSubstitutionsEditor();
        renderOcrProcessingCommand();
        updateSettingsActionButtons();
      });
      rowEl.appendChild(removeButton);
    } else {
      const placeholder = document.createElement('div');
      placeholder.className = 'rule-remove-placeholder';
      rowEl.appendChild(placeholder);
    }

    ocrPdfSubstitutionsListEl.appendChild(rowEl);
  });

  updateSettingsActionButtons();
}

function renderCategoriesEditor() {
  categoriesListEl.innerHTML = '';

  const foldersLabel = document.createElement('div');
  foldersLabel.className = 'archive-folders-label';
  foldersLabel.textContent = 'Mappar';
  categoriesListEl.appendChild(foldersLabel);

  if (categoriesDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga mappar ännu.';
    categoriesListEl.appendChild(empty);
    return;
  }

  categoriesDraft.forEach((archiveFolder, archiveFolderIndex) => {
    const archiveFolderNode = document.createElement('div');
    archiveFolderNode.className = 'tree-node tree-folder';

    const archiveFolderRow = document.createElement('div');
    archiveFolderRow.className = 'tree-row';

    const archiveFolderDot = document.createElement('span');
    archiveFolderDot.className = 'tree-dot';
    archiveFolderRow.appendChild(archiveFolderDot);

    const archiveFolderBody = document.createElement('div');
    archiveFolderBody.className = 'tree-body folder-body';

    const archiveFolderFields = document.createElement('div');
    archiveFolderFields.className = 'folder-fields';

    const archiveFolderNameInput = document.createElement('input');
    archiveFolderNameInput.type = 'text';
    archiveFolderNameInput.placeholder = 'Ex: "Dokument"';
    archiveFolderNameInput.value = archiveFolder.name;
    archiveFolderNameInput.addEventListener('input', () => {
      categoriesDraft[archiveFolderIndex].name = archiveFolderNameInput.value;
      updateSettingsActionButtons();
    });

    const archiveFolderPathInput = document.createElement('input');
    archiveFolderPathInput.type = 'text';
    archiveFolderPathInput.placeholder = 'Ex: "dokument"';
    archiveFolderPathInput.value = archiveFolder.path;
    archiveFolderPathInput.addEventListener('input', () => {
      categoriesDraft[archiveFolderIndex].path = archiveFolderPathInput.value;
      updateSettingsActionButtons();
    });

    const removeArchiveFolderButton = document.createElement('button');
    removeArchiveFolderButton.type = 'button';
    removeArchiveFolderButton.className = 'category-remove';
    removeArchiveFolderButton.textContent = 'Ta bort mapp';
    removeArchiveFolderButton.addEventListener('click', () => {
      categoriesDraft.splice(archiveFolderIndex, 1);
      renderCategoriesEditor();
      updateSettingsActionButtons();
    });

    archiveFolderFields.appendChild(createFloatingField('Namn', archiveFolderNameInput));
    archiveFolderFields.appendChild(createFloatingField('Sökväg', archiveFolderPathInput));
    archiveFolderFields.appendChild(removeArchiveFolderButton);
    archiveFolderBody.appendChild(archiveFolderFields);

    const archiveFolderCategories = document.createElement('div');
    archiveFolderCategories.className = 'tree-children';

    const categoriesLabel = document.createElement('div');
    categoriesLabel.className = 'archive-level-label';
    categoriesLabel.textContent = 'Kategorier';
    archiveFolderCategories.appendChild(categoriesLabel);

    archiveFolder.categories.forEach((category, categoryIndex) => {
      const categoryNode = document.createElement('div');
      categoryNode.className = 'tree-node tree-category has-parent';

      const categoryRow = document.createElement('div');
      categoryRow.className = 'tree-row';

      const categoryDot = document.createElement('span');
      categoryDot.className = 'tree-dot';
      categoryRow.appendChild(categoryDot);

      const categoryBody = document.createElement('div');
      categoryBody.className = 'tree-body category-body';

      const removeCategoryButton = document.createElement('button');
      removeCategoryButton.type = 'button';
      removeCategoryButton.className = 'category-remove';
      removeCategoryButton.textContent = 'Ta bort kategori';
      removeCategoryButton.addEventListener('click', () => {
        categoriesDraft[archiveFolderIndex].categories.splice(categoryIndex, 1);
        if (categoriesDraft[archiveFolderIndex].categories.length === 0) {
          categoriesDraft[archiveFolderIndex].categories.push(defaultCategory());
        }
        renderCategoriesEditor();
        updateSettingsActionButtons();
      });

      const fields = document.createElement('div');
      fields.className = 'category-fields';

      const categoryNameInput = document.createElement('input');
      categoryNameInput.type = 'text';
      categoryNameInput.placeholder = 'Ex: "Fakturor"';
      categoryNameInput.value = category.name;
      categoryNameInput.addEventListener('input', () => {
        categoriesDraft[archiveFolderIndex].categories[categoryIndex].name = categoryNameInput.value;
        updateSettingsActionButtons();
      });

      const minScoreInput = document.createElement('input');
      minScoreInput.type = 'number';
      minScoreInput.step = '1';
      minScoreInput.min = '1';
      minScoreInput.value = String(category.minScore);
      minScoreInput.addEventListener('input', () => {
        categoriesDraft[archiveFolderIndex].categories[categoryIndex].minScore = sanitizePositiveInt(minScoreInput.value, 1);
        updateSettingsActionButtons();
      });

      fields.appendChild(createFloatingField('Namn', categoryNameInput));
      fields.appendChild(createFloatingField('Minpoäng', minScoreInput, 'score-field'));
      fields.appendChild(removeCategoryButton);
      categoryBody.appendChild(fields);

      const ruleList = document.createElement('div');
      ruleList.className = 'tree-children';

      const rulesLabel = document.createElement('div');
      rulesLabel.className = 'archive-level-label';
      rulesLabel.textContent = 'Regler';
      ruleList.appendChild(rulesLabel);

      category.rules.forEach((rule, ruleIndex) => {
        const ruleNode = document.createElement('div');
        ruleNode.className = 'tree-node tree-rule has-parent';

        const ruleRow = document.createElement('div');
        ruleRow.className = 'tree-row';

        const ruleDot = document.createElement('span');
        ruleDot.className = 'tree-dot';
        ruleRow.appendChild(ruleDot);

        const ruleBody = document.createElement('div');
        ruleBody.className = 'tree-body rule-body';

        const ruleFields = document.createElement('div');
        ruleFields.className = 'rule-fields';

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.placeholder = 'Ex: "Förfallodatum"';
        textInput.value = rule.text;
        textInput.addEventListener('input', () => {
          categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex].text = textInput.value;
          updateSettingsActionButtons();
        });

        const scoreInput = document.createElement('input');
        scoreInput.type = 'number';
        scoreInput.step = '1';
        scoreInput.min = '1';
        scoreInput.value = String(rule.score);
        scoreInput.addEventListener('input', () => {
          categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex].score = sanitizePositiveInt(scoreInput.value, 1);
          updateSettingsActionButtons();
        });

        ruleFields.appendChild(createFloatingField('Regeltext', textInput));
        ruleFields.appendChild(createFloatingField('Poäng', scoreInput, 'score-field'));

        if (ruleIndex > 0) {
          const removeRuleButton = document.createElement('button');
          removeRuleButton.type = 'button';
          removeRuleButton.className = 'rule-remove';
          removeRuleButton.textContent = 'Ta bort';
          removeRuleButton.addEventListener('click', () => {
            categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules.splice(ruleIndex, 1);
            if (categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules.length === 0) {
              categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules.push(defaultRule());
            }
            renderCategoriesEditor();
            updateSettingsActionButtons();
          });
          ruleFields.appendChild(removeRuleButton);
        } else {
          const placeholder = document.createElement('div');
          placeholder.className = 'rule-remove-placeholder';
          ruleFields.appendChild(placeholder);
        }

        ruleBody.appendChild(ruleFields);
        ruleRow.appendChild(ruleBody);
        ruleNode.appendChild(ruleRow);
        ruleList.appendChild(ruleNode);
      });

      categoryBody.appendChild(ruleList);

      const ruleActions = document.createElement('div');
      ruleActions.className = 'category-rule-actions';
      const addRuleButton = document.createElement('button');
      addRuleButton.type = 'button';
      addRuleButton.textContent = 'Lägg till regel';
      addRuleButton.addEventListener('click', () => {
        categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules.push(defaultRule());
        renderCategoriesEditor();
        updateSettingsActionButtons();
      });
      ruleActions.appendChild(addRuleButton);
      categoryBody.appendChild(ruleActions);

      categoryRow.appendChild(categoryBody);
      categoryNode.appendChild(categoryRow);
      archiveFolderCategories.appendChild(categoryNode);
    });

    archiveFolderBody.appendChild(archiveFolderCategories);

    const categoryActions = document.createElement('div');
    categoryActions.className = 'folder-actions';
    const addCategoryButton = document.createElement('button');
    addCategoryButton.type = 'button';
    addCategoryButton.textContent = 'Lägg till kategori';
    addCategoryButton.addEventListener('click', () => {
      categoriesDraft[archiveFolderIndex].categories.push(defaultCategory());
      renderCategoriesEditor();
      updateSettingsActionButtons();
    });
    categoryActions.appendChild(addCategoryButton);
    archiveFolderBody.appendChild(categoryActions);

    archiveFolderRow.appendChild(archiveFolderBody);
    archiveFolderNode.appendChild(archiveFolderRow);
    categoriesListEl.appendChild(archiveFolderNode);
  });

  updateSettingsActionButtons();
}

function renderSystemCategoryEditor() {
  systemCategoryEditorEl.innerHTML = '';

  const categoryKey = 'invoice';
  const defaultCategory = SYSTEM_CATEGORIES[categoryKey];
  const systemCategories = sanitizeSystemCategories(systemCategoriesDraft);
  systemCategoriesDraft = systemCategories;
  const category = systemCategories[categoryKey];

  const label = document.createElement('div');
  label.className = 'archive-folders-label';
  label.textContent = 'Systemkategorier';
  systemCategoryEditorEl.appendChild(label);

  const categoryNode = document.createElement('div');
  categoryNode.className = 'tree-node tree-category';
  categoryNode.dataset.system = 'true';
  categoryNode.dataset.systemCategory = 'true';

  const categoryRow = document.createElement('div');
  categoryRow.className = 'tree-row';

  const categoryDot = document.createElement('span');
  categoryDot.className = 'tree-dot';
  categoryRow.appendChild(categoryDot);

  const categoryBody = document.createElement('div');
  categoryBody.className = 'tree-body category-body';

  const fields = document.createElement('div');
  fields.className = 'category-fields';

  const categoryNameInput = document.createElement('input');
  categoryNameInput.type = 'text';
  categoryNameInput.placeholder = 'Ex: "Faktura"';
  categoryNameInput.value = category.name;
  categoryNameInput.addEventListener('input', () => {
    systemCategoriesDraft[categoryKey].name = categoryNameInput.value;
    updateSettingsActionButtons();
  });

  const minScoreInput = document.createElement('input');
  minScoreInput.type = 'number';
  minScoreInput.step = '1';
  minScoreInput.min = '1';
  minScoreInput.value = String(category.minScore);
  minScoreInput.addEventListener('input', () => {
    systemCategoriesDraft[categoryKey].minScore = sanitizePositiveInt(minScoreInput.value, 1);
    updateSettingsActionButtons();
  });

  const spacer = document.createElement('div');
  spacer.className = 'rule-remove-placeholder';

  fields.appendChild(createFloatingField('Namn', categoryNameInput));
  fields.appendChild(createFloatingField('Minpoäng', minScoreInput, 'score-field'));
  fields.appendChild(spacer);
  categoryBody.appendChild(fields);

  const ruleList = document.createElement('div');
  ruleList.className = 'tree-children';

  const rulesLabel = document.createElement('div');
  rulesLabel.className = 'archive-level-label';
  rulesLabel.textContent = 'Regler';
  ruleList.appendChild(rulesLabel);

  category.rules.forEach((rule, ruleIndex) => {
    const ruleNode = document.createElement('div');
    ruleNode.className = 'tree-node tree-rule has-parent';

    const ruleRow = document.createElement('div');
    ruleRow.className = 'tree-row';

    const ruleDot = document.createElement('span');
    ruleDot.className = 'tree-dot';
    ruleRow.appendChild(ruleDot);

    const ruleBody = document.createElement('div');
    ruleBody.className = 'tree-body rule-body';

    const ruleFields = document.createElement('div');
    ruleFields.className = 'rule-fields';

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = 'Ex: "Förfallodatum"';
    textInput.value = rule.text;
    textInput.addEventListener('input', () => {
      systemCategoriesDraft[categoryKey].rules[ruleIndex].text = textInput.value;
      updateSettingsActionButtons();
    });

    const scoreInput = document.createElement('input');
    scoreInput.type = 'number';
    scoreInput.step = '1';
    scoreInput.min = '1';
    scoreInput.value = String(rule.score);
    scoreInput.addEventListener('input', () => {
      systemCategoriesDraft[categoryKey].rules[ruleIndex].score = sanitizePositiveInt(scoreInput.value, 1);
      updateSettingsActionButtons();
    });

    ruleFields.appendChild(createFloatingField('Regeltext', textInput));
    ruleFields.appendChild(createFloatingField('Poäng', scoreInput, 'score-field'));

    if (ruleIndex > 0) {
      const removeRuleButton = document.createElement('button');
      removeRuleButton.type = 'button';
      removeRuleButton.className = 'rule-remove';
      removeRuleButton.textContent = 'Ta bort';
      removeRuleButton.addEventListener('click', () => {
        systemCategoriesDraft[categoryKey].rules.splice(ruleIndex, 1);
        if (systemCategoriesDraft[categoryKey].rules.length === 0) {
          systemCategoriesDraft[categoryKey].rules.push(defaultRule());
        }
        renderSystemCategoryEditor();
        updateSettingsActionButtons();
      });
      ruleFields.appendChild(removeRuleButton);
    } else {
      const placeholder = document.createElement('div');
      placeholder.className = 'rule-remove-placeholder';
      ruleFields.appendChild(placeholder);
    }

    ruleBody.appendChild(ruleFields);
    ruleRow.appendChild(ruleBody);
    ruleNode.appendChild(ruleRow);
    ruleList.appendChild(ruleNode);
  });

  categoryBody.appendChild(ruleList);

  const ruleActions = document.createElement('div');
  ruleActions.className = 'category-rule-actions';

  const addRuleButton = document.createElement('button');
  addRuleButton.type = 'button';
  addRuleButton.textContent = 'Lägg till regel';
  addRuleButton.addEventListener('click', () => {
    systemCategoriesDraft[categoryKey].rules.push(defaultRule());
    renderSystemCategoryEditor();
    updateSettingsActionButtons();
  });

  const restoreButton = document.createElement('button');
  restoreButton.type = 'button';
  restoreButton.textContent = 'Återställ';
  restoreButton.addEventListener('click', () => {
    systemCategoriesDraft[categoryKey].rules = defaultCategory.rules.map(sanitizeRule);
    renderSystemCategoryEditor();
    updateSettingsActionButtons();
  });

  ruleActions.appendChild(addRuleButton);
  ruleActions.appendChild(restoreButton);
  categoryBody.appendChild(ruleActions);

  categoryRow.appendChild(categoryBody);
  categoryNode.appendChild(categoryRow);
  systemCategoryEditorEl.appendChild(categoryNode);
}

function setArchiveTab(tabId) {
  activeArchiveTabId = tabId === 'system' ? 'system' : 'categories';
  archiveTabEls.forEach((button) => {
    const isActive = button.dataset.archiveTab === activeArchiveTabId;
    button.classList.toggle('active', isActive);
  });

  archiveViewCategoriesEl.classList.toggle('hidden', activeArchiveTabId !== 'categories');
  archiveViewSystemEl.classList.toggle('hidden', activeArchiveTabId !== 'system');
}

function renderOcrProcessingCommand() {
  const modeFlag = ocrSkipExistingTextEl.checked ? '--mode skip' : '--mode redo';
  const optimizeLevel = sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1);
  const deskewSegment = ocrSkipExistingTextEl.checked ? '--deskew ' : '';
  const extractionMethod = sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout');
  const substitutions = ocrPdfSubstitutionsDraft.map(sanitizeReplacement).filter((row) => row.from !== '' && row.to !== '');
  const pluginSegment = substitutions.length > 0
    ? '--plugin docflow_ocrmypdf_plugin.py --docflow-transform-script data/docflow_ocr_pdf_transform.py '
    : '';
  const extractionText = extractionMethod === 'bbox'
    ? 'Textuttag: pdftotext -bbox-layout -> bbox-grid'
    : 'Textuttag: pdftotext -layout';
  ocrProcessingCommandEl.textContent =
    'ocrmypdf ' + pluginSegment + '-l swe ' + deskewSegment + '--oversample 400 --tesseract-thresholding sauvola '
    + '--tesseract-pagesegmode 6 --output-type pdf '
    + '-O' + optimizeLevel
    + ' '
    + modeFlag
    + ' input.pdf output.pdf\n'
    + extractionText
    + (substitutions.length > 0 ? '\nPDF-textsubstitutioner: ' + substitutions.length + ' regel/rader' : '');
}

function startJbig2RefreshSpin() {
  jbig2RefreshButtonEl.disabled = true;
  jbig2RefreshButtonEl.classList.add('is-spinning');
}

function stopJbig2RefreshSpin(hideAfterStop) {
  const shouldHide = hideAfterStop === true;
  if (!jbig2RefreshButtonEl.classList.contains('is-spinning')) {
    jbig2RefreshButtonEl.disabled = false;
    jbig2RefreshButtonEl.classList.toggle('hidden', shouldHide);
    return;
  }

  if (jbig2RefreshButtonEl.dataset.stopPending === 'true') {
    jbig2RefreshButtonEl.dataset.hideAfterStop = shouldHide ? 'true' : 'false';
    return;
  }

  jbig2RefreshButtonEl.dataset.stopPending = 'true';
  jbig2RefreshButtonEl.dataset.hideAfterStop = shouldHide ? 'true' : 'false';
  jbig2RefreshButtonEl.addEventListener('animationiteration', () => {
    const finalHide = jbig2RefreshButtonEl.dataset.hideAfterStop === 'true';
    jbig2RefreshButtonEl.classList.remove('is-spinning');
    jbig2RefreshButtonEl.disabled = false;
    jbig2RefreshButtonEl.classList.toggle('hidden', finalHide);
    jbig2RefreshButtonEl.dataset.stopPending = 'false';
    jbig2RefreshButtonEl.dataset.hideAfterStop = 'false';
  }, { once: true });
}

function renderJbig2Status(jbig2, options = {}) {
  const installed = !!(jbig2 && jbig2.installed === true);
  const deferRefreshVisibility = options && options.deferRefreshVisibility === true;
  jbig2StatusBadgeEl.textContent = installed ? 'Installerad' : 'Ej installerad';
  jbig2StatusBadgeEl.classList.toggle('is-installed', installed);
  jbig2StatusBadgeEl.classList.toggle('is-missing', !installed);
  if (deferRefreshVisibility) {
    stopJbig2RefreshSpin(installed);
  } else {
    jbig2RefreshButtonEl.classList.remove('is-spinning');
    jbig2RefreshButtonEl.disabled = false;
    jbig2RefreshButtonEl.classList.toggle('hidden', installed);
  }
  jbig2StatusBadgeWrapEl.classList.remove('is-collapsed');
  jbig2StatusBadgeWrapEl.classList.remove('is-animating');
  void jbig2StatusBadgeWrapEl.offsetWidth;
  jbig2StatusBadgeWrapEl.classList.add('is-animating');

  const installCommand = jbig2 && typeof jbig2.installCommand === 'string' && jbig2.installCommand.trim() !== ''
    ? jbig2.installCommand.trim()
    : 'sudo apt install jbig2';
  jbig2InstallCommandEl.textContent = installCommand;
}

async function loadClientsText() {
  const response = await fetch('/api/get-clients.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda huvudmän');
  }

  const payload = await response.json();
  if (!payload || typeof payload.text !== 'string') {
    throw new Error('Ogiltigt svar för huvudmän');
  }

  clientsTextareaEl.value = payload.text;
  clientsBaselineText = payload.text;
  updateSettingsActionButtons();
}

async function loadMatchingSettings() {
  const response = await fetch('/api/get-matching-settings.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda matchningsinställningar');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.replacements)) {
    throw new Error('Ogiltigt svar för matchningsinställningar');
  }

  matchingDraft = payload.replacements.map(sanitizeReplacement);
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }
  matchingInvoiceFieldMinConfidenceDraft = sanitizeInvoiceFieldMinConfidence(payload.invoiceFieldMinConfidence, 0.7);
  matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);

  matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft);
  renderMatchingEditor();
  updateSettingsActionButtons();
}

async function loadPathSettings() {
  const response = await fetch('/api/get-config.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda konfiguration');
  }

  const payload = await response.json();
  if (!payload || typeof payload.outputBaseDirectory !== 'string') {
    throw new Error('Ogiltigt svar för konfiguration');
  }

  outputBasePathEl.value = payload.outputBaseDirectory;
  pathsBaselineValue = normalizedPathValue(payload.outputBaseDirectory);
  updateSettingsActionButtons();
}

async function loadOcrProcessingSettings(options = {}) {
  const response = await fetch('/api/get-config.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda OCR-inställningar');
  }

  const payload = await response.json();
  if (
    !payload
    || typeof payload.ocrSkipExistingText !== 'boolean'
    || !Number.isInteger(payload.ocrOptimizeLevel)
    || typeof payload.ocrTextExtractionMethod !== 'string'
    || !Array.isArray(payload.ocrPdfTextSubstitutions)
  ) {
    throw new Error('Ogiltigt svar för OCR-inställningar');
  }

  ocrSkipExistingTextEl.checked = payload.ocrSkipExistingText;
  ocrSkipExistingTextBaseline = payload.ocrSkipExistingText;
  ocrOptimizeLevelBaseline = sanitizeOcrOptimizeLevel(payload.ocrOptimizeLevel, 1);
  ocrOptimizeLevelEl.value = String(ocrOptimizeLevelBaseline);
  ocrTextExtractionMethodBaseline = sanitizeOcrTextExtractionMethod(payload.ocrTextExtractionMethod, 'layout');
  ocrTextExtractionMethodEl.value = ocrTextExtractionMethodBaseline;
  ocrPdfSubstitutionsDraft = Array.isArray(payload.ocrPdfTextSubstitutions)
    ? payload.ocrPdfTextSubstitutions.map(sanitizeReplacement)
    : [];
  if (ocrPdfSubstitutionsDraft.length === 0) {
    ocrPdfSubstitutionsDraft = [defaultReplacement()];
  }
  ocrPdfSubstitutionsBaselineJson = normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft);
  renderOcrPdfSubstitutionsEditor();
  renderJbig2Status(payload.jbig2, options);
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
}

async function loadCategories() {
  const response = await fetch('/api/get-categories.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda arkivstruktur');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.archiveFolders) || !payload.systemCategories || typeof payload.systemCategories !== 'object') {
    throw new Error('Ogiltigt svar för arkivstruktur');
  }

  categoriesDraft = payload.archiveFolders.map(sanitizeArchiveFolder);
  systemCategoriesDraft = sanitizeSystemCategories(payload.systemCategories);
  categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
  renderCategoriesEditor();
  renderSystemCategoryEditor();
  updateSettingsActionButtons();
}

async function saveClientsText() {
  const response = await fetch('/api/save-clients.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ text: clientsTextareaEl.value })
  });

  if (!response.ok) {
    throw new Error('Kunde inte spara huvudmän');
  }

  clientsBaselineText = clientsTextareaEl.value;
  updateSettingsActionButtons();
  await fetchState({ refreshClients: true });
}

async function saveMatchingSettings() {
  const normalized = matchingDraft.map(sanitizeReplacement);
  const invoiceFieldMinConfidence = sanitizeInvoiceFieldMinConfidence(matchingInvoiceFieldMinConfidenceDraft, 0.7);
  const response = await fetch('/api/save-matching-settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      replacements: normalized,
      invoiceFieldMinConfidence
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.replacements)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara matchningsinställningar';
    throw new Error(message);
  }

  matchingDraft = payload.replacements.map(sanitizeReplacement);
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }
  matchingInvoiceFieldMinConfidenceDraft = sanitizeInvoiceFieldMinConfidence(payload.invoiceFieldMinConfidence, 0.7);
  matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);

  matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft);
  renderMatchingEditor();
  updateSettingsActionButtons();
}

async function saveCategories() {
  const normalized = categoriesDraft.map(sanitizeArchiveFolder);
  const normalizedSystemCategories = sanitizeSystemCategories(systemCategoriesDraft);
  const response = await fetch('/api/save-categories.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      archiveFolders: normalized,
      systemCategories: normalizedSystemCategories
    })
  });

  const payload = await response.json().catch(() => null);
  if (
    !response.ok
    || !payload
    || payload.ok !== true
    || !Array.isArray(payload.archiveFolders)
    || !payload.systemCategories
    || typeof payload.systemCategories !== 'object'
  ) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara arkivstruktur';
    throw new Error(message);
  }

  categoriesDraft = payload.archiveFolders.map(sanitizeArchiveFolder);
  systemCategoriesDraft = sanitizeSystemCategories(payload.systemCategories);
  categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
  renderCategoriesEditor();
  renderSystemCategoryEditor();
  updateSettingsActionButtons();
  await fetchState({ refreshCategories: true });
}

async function savePathSettings() {
  const response = await fetch('/api/save-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ outputBaseDirectory: outputBasePathEl.value })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara sökvägsinställningar';
    throw new Error(message);
  }

  pathsBaselineValue = normalizedPathValue(outputBasePathEl.value);
  outputBasePathEl.value = pathsBaselineValue;
  updateSettingsActionButtons();
}

async function saveOcrProcessingSettings() {
  const normalizedSubstitutions = ocrPdfSubstitutionsDraft.map(sanitizeReplacement);
  const response = await fetch('/api/save-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      ocrSkipExistingText: ocrSkipExistingTextEl.checked,
      ocrOptimizeLevel: sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1),
      ocrTextExtractionMethod: sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout'),
      ocrPdfTextSubstitutions: normalizedSubstitutions
    })
  });

  const payload = await response.json().catch(() => null);
  if (
    !response.ok
    || !payload
    || payload.ok !== true
    || typeof payload.ocrSkipExistingText !== 'boolean'
    || !Number.isInteger(payload.ocrOptimizeLevel)
    || typeof payload.ocrTextExtractionMethod !== 'string'
    || !Array.isArray(payload.ocrPdfTextSubstitutions)
  ) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara OCR-inställningar';
    throw new Error(message);
  }

  ocrSkipExistingTextEl.checked = payload.ocrSkipExistingText;
  ocrSkipExistingTextBaseline = payload.ocrSkipExistingText;
  ocrOptimizeLevelBaseline = sanitizeOcrOptimizeLevel(payload.ocrOptimizeLevel, 1);
  ocrOptimizeLevelEl.value = String(ocrOptimizeLevelBaseline);
  ocrTextExtractionMethodBaseline = sanitizeOcrTextExtractionMethod(payload.ocrTextExtractionMethod, 'layout');
  ocrTextExtractionMethodEl.value = ocrTextExtractionMethodBaseline;
  ocrPdfSubstitutionsDraft = payload.ocrPdfTextSubstitutions.map(sanitizeReplacement);
  if (ocrPdfSubstitutionsDraft.length === 0) {
    ocrPdfSubstitutionsDraft = [defaultReplacement()];
  }
  ocrPdfSubstitutionsBaselineJson = normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft);
  renderOcrPdfSubstitutionsEditor();
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
}

async function resetAllJobs() {
  const response = await fetch('/api/reset-jobs.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    }
  });

  if (!response.ok) {
    throw new Error('Kunde inte återställa jobb');
  }

  const payload = await response.json();
  if (!payload || payload.ok !== true) {
    throw new Error('Reset jobs failed');
  }

  loadedOcrJobId = '';
  loadedMatchesJobId = '';
  loadedMetaJobId = '';
  clearPdfFrames();
  selectedJobId = '';
  closeSettingsModal();
  await fetchState();
}

async function reprocessSingleJob(jobId, mode) {
  const response = await fetch('/api/reset-jobs.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ jobId, mode })
  });

  if (!response.ok) {
    throw new Error('Kunde inte köra om jobbet');
  }

  const payload = await response.json();
  if (!payload || payload.ok !== true) {
    throw new Error('Reprocess job failed');
  }

  if (selectedJobId === jobId) {
    loadedOcrJobId = '';
    loadedMatchesJobId = '';
    loadedMetaJobId = '';
    clearPdfFrames();
    selectedJobId = '';
  }

  await fetchState();
}

async function handleSelectedJobReprocess(mode) {
  if (!selectedJobId) {
    return;
  }

  try {
    await reprocessSingleJob(selectedJobId, mode);
  } catch (error) {
    alert(error.message || 'Kunde inte köra om jobbet.');
  }
}

viewModeEl.addEventListener('change', () => {
  if (viewModeEl.value === 'ocr') {
    currentViewMode = 'ocr';
  } else if (viewModeEl.value === 'matches') {
    currentViewMode = 'matches';
  } else if (viewModeEl.value === 'meta') {
    currentViewMode = 'meta';
  } else {
    currentViewMode = 'pdf';
  }

  loadedOcrJobId = '';
  loadedMatchesJobId = '';
  loadedMetaJobId = '';
  setViewerJob(selectedJobId);
});

clientSelectEl.addEventListener('change', () => {
  if (clientSelectEl.value === EDIT_CLIENTS_OPTION_VALUE) {
    const selectedJob = state.readyJobs.find((job) => job.id === selectedJobId) || null;
    openClientsSettingsDirect().finally(() => {
      setClientForJob(selectedJob);
    });
    return;
  }

  if (!selectedJobId) {
    return;
  }

  const value = clientSelectEl.value;
  if (!value) {
    selectedClientByJobId.delete(selectedJobId);
    return;
  }

  selectedClientByJobId.set(selectedJobId, value);
});

senderSelectEl.addEventListener('change', () => {
  if (!selectedJobId) {
    return;
  }

  const value = senderSelectEl.value;
  if (!value) {
    selectedSenderByJobId.delete(selectedJobId);
    return;
  }

  selectedSenderByJobId.set(selectedJobId, value);
});

categorySelectEl.addEventListener('change', () => {
  if (categorySelectEl.value === EDIT_CATEGORIES_OPTION_VALUE) {
    const selectedJob = state.readyJobs.find((job) => job.id === selectedJobId) || null;
    openCategoriesSettingsDirect().finally(() => {
      setCategoryForJob(selectedJob);
    });
    return;
  }

  if (!selectedJobId) {
    return;
  }

  const value = categorySelectEl.value;
  if (!value) {
    selectedCategoryByJobId.delete(selectedJobId);
    return;
  }

  selectedCategoryByJobId.set(selectedJobId, value);
});

clientsTextareaEl.addEventListener('input', () => {
  updateSettingsActionButtons();
});

matchingInvoiceThresholdEl.addEventListener('input', () => {
  matchingInvoiceFieldMinConfidenceDraft = sanitizeInvoiceFieldMinConfidence(matchingInvoiceThresholdEl.value, 0.7);
  updateSettingsActionButtons();
});

ocrSkipExistingTextEl.addEventListener('change', () => {
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
});

ocrOptimizeLevelEl.addEventListener('change', () => {
  ocrOptimizeLevelEl.value = String(sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1));
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
});

ocrTextExtractionMethodEl.addEventListener('change', () => {
  ocrTextExtractionMethodEl.value = sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout');
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
});

ocrPdfSubstitutionsAddRowEl.addEventListener('click', () => {
  ocrPdfSubstitutionsDraft.push(defaultReplacement());
  renderOcrPdfSubstitutionsEditor();
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
});

outputBasePathEl.addEventListener('input', () => {
  updateSettingsActionButtons();
});

settingsButtonEl.addEventListener('click', async () => {
  await openClientsSettingsDirect();
  try {
    await loadMatchingSettings();
  } catch (error) {
    alert('Kunde inte ladda matchningsinställningar.');
    matchingDraft = [defaultReplacement()];
    matchingInvoiceFieldMinConfidenceDraft = 0.7;
    matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);
    matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft);
    renderMatchingEditor();
    updateSettingsActionButtons();
  }
  try {
    await loadOcrProcessingSettings();
  } catch (error) {
    alert('Kunde inte ladda OCR-inställningar.');
    ocrSkipExistingTextEl.checked = true;
    ocrSkipExistingTextBaseline = true;
    ocrOptimizeLevelBaseline = 1;
    ocrOptimizeLevelEl.value = '1';
    ocrTextExtractionMethodBaseline = 'layout';
    ocrTextExtractionMethodEl.value = 'layout';
    ocrPdfSubstitutionsDraft = [defaultReplacement()];
    ocrPdfSubstitutionsBaselineJson = normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft);
    renderOcrPdfSubstitutionsEditor();
    renderJbig2Status(null);
    renderOcrProcessingCommand();
    updateSettingsActionButtons();
  }
  try {
    await loadPathSettings();
  } catch (error) {
    alert('Kunde inte ladda sökvägsinställningar.');
    pathsBaselineValue = normalizedPathValue(outputBasePathEl.value);
    updateSettingsActionButtons();
  }
  try {
    await loadCategories();
  } catch (error) {
    alert('Kunde inte ladda arkivstruktur.');
    categoriesDraft = [];
    systemCategoriesDraft = createDefaultSystemCategories();
    categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
    renderCategoriesEditor();
    renderSystemCategoryEditor();
    updateSettingsActionButtons();
  }
  setArchiveTab('categories');
  clientsTextareaEl.focus();
  updateSettingsActionButtons();
});

clientsCancelEl.addEventListener('click', () => {
  clientsTextareaEl.value = clientsBaselineText;
  updateSettingsActionButtons();
});

clientsApplyEl.addEventListener('click', async () => {
  try {
    await saveClientsText();
  } catch (error) {
    alert('Kunde inte spara huvudmän.');
  }
});

matchingAddRowEl.addEventListener('click', () => {
  matchingDraft.push(defaultReplacement());
  renderMatchingEditor();
  updateSettingsActionButtons();
});

matchingCancelEl.addEventListener('click', () => {
  let parsed = {};
  try {
    parsed = JSON.parse(matchingBaselineJson);
  } catch (error) {
    parsed = {};
  }

  const replacements = Array.isArray(parsed.replacements) ? parsed.replacements : [];
  matchingDraft = replacements.map(sanitizeReplacement);
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }
  matchingInvoiceFieldMinConfidenceDraft = sanitizeInvoiceFieldMinConfidence(parsed.invoiceFieldMinConfidence, 0.7);
  matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);

  renderMatchingEditor();
  updateSettingsActionButtons();
});

matchingApplyEl.addEventListener('click', async () => {
  try {
    await saveMatchingSettings();
  } catch (error) {
    alert(error.message || 'Kunde inte spara matchningsinställningar.');
  }
});

ocrProcessingCancelEl.addEventListener('click', () => {
  ocrSkipExistingTextEl.checked = ocrSkipExistingTextBaseline;
  ocrOptimizeLevelEl.value = String(ocrOptimizeLevelBaseline);
  ocrTextExtractionMethodEl.value = ocrTextExtractionMethodBaseline;
  let parsed = [];
  try {
    parsed = JSON.parse(ocrPdfSubstitutionsBaselineJson);
  } catch (error) {
    parsed = [];
  }
  ocrPdfSubstitutionsDraft = Array.isArray(parsed) ? parsed.map(sanitizeReplacement) : [];
  if (ocrPdfSubstitutionsDraft.length === 0) {
    ocrPdfSubstitutionsDraft = [defaultReplacement()];
  }
  renderOcrPdfSubstitutionsEditor();
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
});

ocrProcessingApplyEl.addEventListener('click', async () => {
  try {
    await saveOcrProcessingSettings();
  } catch (error) {
    alert(error.message || 'Kunde inte spara OCR-inställningar.');
  }
});

jbig2RefreshButtonEl.addEventListener('click', async () => {
  startJbig2RefreshSpin();
  jbig2StatusBadgeWrapEl.classList.add('is-collapsed');
  try {
    await loadOcrProcessingSettings({ deferRefreshVisibility: true });
  } catch (error) {
    renderJbig2Status(null, { deferRefreshVisibility: true });
    alert('Kunde inte kontrollera JBIG2-status.');
  }
});

categoriesAddCategoryEl.addEventListener('click', () => {
  categoriesDraft.push(defaultArchiveFolder());
  renderCategoriesEditor();
  updateSettingsActionButtons();
});

categoriesCancelEl.addEventListener('click', () => {
  let parsed = {};
  try {
    parsed = JSON.parse(categoriesBaselineJson);
  } catch (error) {
    parsed = {};
  }
  const archiveFolders = Array.isArray(parsed.archiveFolders) ? parsed.archiveFolders : [];
  const systemCategories = parsed.systemCategories && typeof parsed.systemCategories === 'object'
    ? parsed.systemCategories
    : createDefaultSystemCategories();

  categoriesDraft = archiveFolders.map(sanitizeArchiveFolder);
  systemCategoriesDraft = sanitizeSystemCategories(systemCategories);
  renderCategoriesEditor();
  renderSystemCategoryEditor();
  updateSettingsActionButtons();
});

categoriesApplyEl.addEventListener('click', async () => {
  try {
    await saveCategories();
  } catch (error) {
    alert(error.message || 'Kunde inte spara arkivstruktur.');
  }
});

archiveTabEls.forEach((tabButton) => {
  tabButton.addEventListener('click', () => {
    const tabId = tabButton.dataset.archiveTab;
    if (!tabId || tabId === activeArchiveTabId) {
      return;
    }
    setArchiveTab(tabId);
  });
});

settingsTabEls.forEach((tabButton) => {
  tabButton.addEventListener('click', () => {
    const tabId = tabButton.dataset.settingsTab;
    if (!tabId) {
      return;
    }
    if (tabId === activeSettingsTabId) {
      return;
    }
    if (!canLeaveCurrentSettingsView()) {
      return;
    }
    setSettingsTab(tabId);
  });
});

settingsCloseEl.addEventListener('click', () => {
  closeSettingsModal();
});

pathsCancelEl.addEventListener('click', () => {
  outputBasePathEl.value = pathsBaselineValue;
  updateSettingsActionButtons();
});

pathsApplyEl.addEventListener('click', async () => {
  try {
    await savePathSettings();
  } catch (error) {
    alert(error.message || 'Kunde inte spara sökvägsinställningar.');
  }
});

settingsResetJobsEl.addEventListener('click', async () => {
  const confirmed = window.confirm(
    'Detta flyttar tillbaka alla source.pdf till inbox och tar bort alla jobbmappar. Fortsätta?'
  );
  if (!confirmed) {
    return;
  }

  try {
    await resetAllJobs();
  } catch (error) {
    alert('Kunde inte återställa jobb.');
  }
});

selectedJobReprocessEl.addEventListener('click', async () => {
  await handleSelectedJobReprocess('post-ocr');
});

selectedJobRerunOcrEl.addEventListener('click', async () => {
  await handleSelectedJobReprocess('full');
});

ocrSearchInputEl.addEventListener('input', () => {
  refreshOcrSearch();
});

ocrSearchRegexEl.addEventListener('change', () => {
  refreshOcrSearch();
});

ocrSearchPrevEl.addEventListener('click', () => {
  stepOcrSearch(-1);
});

ocrSearchNextEl.addEventListener('click', () => {
  stepOcrSearch(1);
});

ocrSearchInputEl.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter') {
    return;
  }
  event.preventDefault();
  if (event.shiftKey) {
    stepOcrSearch(-1);
    return;
  }
  stepOcrSearch(1);
});

ocrViewEl.addEventListener('scroll', () => {
  syncOcrHighlightScroll();
});

ocrSearchBarEl.addEventListener('mousedown', (event) => {
  startOcrSearchDrag(event);
});

window.addEventListener('mousemove', (event) => {
  moveOcrSearchDrag(event);
});

window.addEventListener('mouseup', () => {
  stopOcrSearchDrag();
});

settingsModalEl.addEventListener('click', (event) => {
  if (event.target === settingsModalEl) {
    closeSettingsModal();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && !settingsModalEl.classList.contains('hidden')) {
    closeSettingsModal();
    return;
  }

  if (event.key !== 'ArrowDown' && event.key !== 'ArrowUp') {
    return;
  }

  if (!settingsModalEl.classList.contains('hidden')) {
    return;
  }

  const target = event.target;
  if (target instanceof HTMLElement && target.closest('input, textarea, select, [contenteditable="true"]')) {
    return;
  }

  event.preventDefault();
  moveSelectionBy(event.key === 'ArrowDown' ? 1 : -1);
});

window.addEventListener('beforeunload', (event) => {
  if (!hasAnyUnsavedSettingsChanges()) {
    return;
  }

  event.preventDefault();
  event.returnValue = 'Du har osparade ändringar.';
});

async function fetchState(options = {}) {
  if (pollInFlight) {
    return;
  }

  const refreshClients = options.refreshClients === true;
  const refreshSenders = options.refreshSenders === true;
  const refreshCategories = options.refreshCategories === true;
  const includeClients = !hasLoadedClients || refreshClients;
  const includeSenders = !hasLoadedSenders || refreshSenders;
  const includeCategories = !hasLoadedCategories || refreshCategories;

  pollInFlight = true;
  try {
    const response = await fetch('/api/get-state.php', { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Kunde inte hämta status');
    }

    const nextState = await response.json();
    if (!nextState || !Array.isArray(nextState.readyJobs) || !Array.isArray(nextState.clients)) {
      throw new Error('Ogiltigt statussvar');
    }

    applyState({
      processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : [],
      readyJobs: nextState.readyJobs,
      failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : [],
      clients: includeClients && Array.isArray(nextState.clients) ? nextState.clients : undefined,
      senders: includeSenders && Array.isArray(nextState.senders) ? nextState.senders : undefined,
      categories: includeCategories && Array.isArray(nextState.categories) ? nextState.categories : undefined
    });

    if (includeClients) {
      hasLoadedClients = true;
    }
    if (includeSenders) {
      hasLoadedSenders = true;
    }
    if (includeCategories) {
      hasLoadedCategories = true;
    }
  } catch (error) {
    setProcessingInfo([]);
    jobListEl.innerHTML = '';
    const li = document.createElement('li');
    li.className = 'job-message';
    li.textContent = 'Kunde inte ladda status.';
    jobListEl.appendChild(li);
  } finally {
    pollInFlight = false;
  }
}

async function pollLoop() {
  await fetchState();
  pollTimer = window.setTimeout(pollLoop, 3000);
}

updateSettingsActionButtons();
renderJbig2Status(null);
renderOcrProcessingCommand();
pollLoop();
