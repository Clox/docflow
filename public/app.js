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
const settingsCloseEl = document.getElementById('settings-close');
const selectedJobPanelEl = document.getElementById('selected-job-panel');
const selectedJobNameEl = document.getElementById('selected-job-name');
const selectedJobMetaEl = document.getElementById('selected-job-meta');
const selectedJobReprocessEl = document.getElementById('selected-job-reprocess');
const selectedJobRerunOcrEl = document.getElementById('selected-job-rerun-ocr');
const settingsPanelTemplateIds = {
  clients: 'settings-template-clients',
  senders: 'settings-template-senders',
  matching: 'settings-template-matching',
  'ocr-processing': 'settings-template-ocr-processing',
  categories: 'settings-template-categories',
  jobs: 'settings-template-jobs',
  paths: 'settings-template-paths'
};
let clientsTextareaEl = null;
let clientsCancelEl = null;
let clientsApplyEl = null;
let sendersListEl = null;
let sendersAddRowEl = null;
let sendersCancelEl = null;
let sendersApplyEl = null;
let sendersSortOrderEl = null;
let sendersExpandAllEl = null;
let sendersCollapseAllEl = null;
let sendersSelectedCountEl = null;
let sendersClearSelectionEl = null;
let sendersMergeSelectedEl = null;
let senderMergeOverlayEl = null;
let senderMergeEditorEl = null;
let senderMergeCancelEl = null;
let senderMergeApplyEl = null;
let matchingListEl = null;
let matchingAddRowEl = null;
let matchingCancelEl = null;
let matchingApplyEl = null;
let matchingInvoiceThresholdEl = null;
let ocrSkipExistingTextEl = null;
let ocrOptimizeLevelEl = null;
let ocrTextExtractionMethodEl = null;
let ocrPdfSubstitutionsListEl = null;
let ocrPdfSubstitutionsAddRowEl = null;
let ocrProcessingCommandEl = null;
let jbig2StatusBadgeWrapEl = null;
let jbig2StatusBadgeEl = null;
let jbig2InstallCommandEl = null;
let jbig2RefreshButtonEl = null;
let ocrProcessingCancelEl = null;
let ocrProcessingApplyEl = null;
let categoriesListEl = null;
let systemCategoryEditorEl = null;
let categoriesAddCategoryEl = null;
let categoriesCancelEl = null;
let categoriesApplyEl = null;
let archiveTabEls = [];
let archiveViewCategoriesEl = null;
let archiveViewSystemEl = null;
let settingsResetJobsEl = null;
let jobsStateTransportEl = null;
let outputBasePathEl = null;
let pathsCancelEl = null;
let pathsApplyEl = null;

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
let pollInFlight = false;
let stateStream = null;
let statePollTimer = null;
let stateUpdateTransport = 'polling';
let currentViewMode = 'pdf';
let ocrRequestSeq = 0;
let ocrSearchMatches = [];
let ocrSearchActiveIndex = -1;
let ocrSearchDragState = null;
let matchesRequestSeq = 0;
let metaRequestSeq = 0;
let preferredJobIdFromHash = '';
let categoriesDraft = [];
let systemCategoriesDraft = createDefaultSystemCategories();
let sendersDraft = [];
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
let sendersBaselineJson = '[]';
let sendersSortOrder = 'name';
let senderDraftUiKeySeq = 1;
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
const lastKnownJobDisplayById = new Map();
const pinnedProcessingJobIds = new Set();
const jobListNodeByKey = new Map();
const seenFailedJobKeys = new Set();
const collapsedSenderUiKeys = new Set();
const selectedSenderUiKeys = new Set();
const mountedSettingsPanels = new Set();
const boundSettingsPanels = new Set();
const loadedSettingsPanels = new Set();
const EDIT_CLIENTS_OPTION_VALUE = '__edit_clients__';
const EDIT_SENDERS_OPTION_VALUE = '__edit_senders__';
const EDIT_CATEGORIES_OPTION_VALUE = '__edit_categories__';
const VALID_VIEW_MODES = new Set(['pdf', 'ocr', 'matches', 'meta']);

let senderMergeState = null;

clientSelectEl.disabled = true;
senderSelectEl.disabled = true;
categorySelectEl.disabled = true;
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
      value: sender && Number.isInteger(sender.id) && sender.id > 0 ? String(sender.id) : '',
      label: sender && typeof sender.name === 'string' ? sender.name.trim() : ''
    }))
    .filter((sender) => sender.value !== '' && sender.label !== '');
  const signature = JSON.stringify({
    action: EDIT_SENDERS_OPTION_VALUE,
    options
  });
  if (signature === senderOptionsSignature) {
    return;
  }

  const currentValue = senderSelectEl.value;
  senderSelectEl.innerHTML = '';

  const placeholderOption = document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.hidden = true;
  placeholderOption.textContent = 'Välj avsändare';
  senderSelectEl.appendChild(placeholderOption);

  const editOption = document.createElement('option');
  editOption.value = EDIT_SENDERS_OPTION_VALUE;
  editOption.textContent = 'Redigera avsändare...';
  senderSelectEl.appendChild(editOption);

  const separatorOption = document.createElement('option');
  separatorOption.value = '__separator__';
  separatorOption.textContent = '──────────';
  separatorOption.disabled = true;
  senderSelectEl.appendChild(separatorOption);

  options.forEach((item) => {
    const option = document.createElement('option');
    option.value = item.value;
    option.textContent = item.label;
    senderSelectEl.appendChild(option);
  });

  const hasCurrentValue = options.some((item) => item.value === currentValue);
  senderSelectEl.value = hasCurrentValue ? currentValue : '';
  senderOptionsSignature = signature;
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
  clientSelectEl.disabled = !job || job.status === 'processing';

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
  senderSelectEl.disabled = !job || job.status === 'processing';

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

  const matchedSenderId = Number.isInteger(job.matchedSenderId) && job.matchedSenderId > 0
    ? String(job.matchedSenderId)
    : '';
  if (!matchedSenderId) {
    senderSelectEl.value = '';
    return;
  }

  const hasOption = Array.from(senderSelectEl.options).some(
    (option) => option.value === matchedSenderId
  );
  senderSelectEl.value = hasOption ? matchedSenderId : '';
}

function setCategoryForJob(job) {
  categorySelectEl.disabled = !job || job.status === 'processing';

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

function sanitizeViewMode(mode) {
  return VALID_VIEW_MODES.has(mode) ? mode : 'pdf';
}

function parseHashState() {
  const hash = window.location.hash.startsWith('#') ? window.location.hash.slice(1) : window.location.hash;
  const params = new URLSearchParams(hash);
  const jobId = typeof params.get('job') === 'string' ? params.get('job').trim() : '';
  const view = sanitizeViewMode((params.get('view') || '').trim());
  return { jobId, view };
}

function updateHashState() {
  preferredJobIdFromHash = selectedJobId;
  const params = new URLSearchParams();
  if (selectedJobId) {
    params.set('job', selectedJobId);
  }
  params.set('view', currentViewMode);
  const nextHash = params.toString();
  const currentHash = window.location.hash.startsWith('#') ? window.location.hash.slice(1) : window.location.hash;
  if (nextHash === currentHash) {
    return;
  }

  const nextUrl = `${window.location.pathname}${window.location.search}${nextHash ? `#${nextHash}` : ''}`;
  window.history.replaceState(null, '', nextUrl);
}

function setViewMode(mode, options = {}) {
  const syncHash = options.syncHash !== false;
  const nextMode = sanitizeViewMode(mode);
  currentViewMode = nextMode;
  viewModeEl.value = nextMode;
  loadedOcrJobId = '';
  loadedMatchesJobId = '';
  loadedMetaJobId = '';
  setViewerJob(selectedJobId);
  if (syncHash) {
    updateHashState();
  }
}

function applyHashState() {
  const hashState = parseHashState();
  preferredJobIdFromHash = hashState.jobId;
  setViewMode(hashState.view, { syncHash: false });

  if (!preferredJobIdFromHash) {
    return;
  }

  const hashJob = findJobById(preferredJobIdFromHash);
  if (hashJob) {
    applySelectedJobId(preferredJobIdFromHash, { syncHash: false });
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

function ensureJobListNode(key, createNode) {
  let node = jobListNodeByKey.get(key) || null;
  if (node) {
    return node;
  }

  node = createNode();
  node.dataset.jobListKey = key;
  jobListNodeByKey.set(key, node);
  return node;
}

function ensureJobListMessageNode() {
  return ensureJobListNode('message:empty', () => {
    const li = document.createElement('li');
    li.className = 'job-message';
    return li;
  });
}

function ensureJobListSectionLabelNode(text) {
  const node = ensureJobListNode('label:failed', () => {
    const li = document.createElement('li');
    li.className = 'job-section-label';
    return li;
  });
  node.textContent = text;
  return node;
}

function ensureJobListItemNode(key) {
  return ensureJobListNode(key, () => {
    const li = document.createElement('li');
    const name = document.createElement('div');
    name.className = 'job-name';
    li.appendChild(name);
    li._nameEl = name;
    li.addEventListener('click', () => {
      const jobId = li.dataset.jobId || '';
      if (jobId) {
        applySelectedJobId(jobId);
      }
    });
    return li;
  });
}

function ensureJobListItemSecondaryNode(li, className) {
  if (li._secondaryEl && li._secondaryEl.className === className) {
    return li._secondaryEl;
  }

  if (li._secondaryEl && li._secondaryEl.parentNode === li) {
    li.removeChild(li._secondaryEl);
  }

  const secondary = document.createElement('div');
  secondary.className = className;
  li.appendChild(secondary);
  li._secondaryEl = secondary;
  return secondary;
}

function ensureJobListItemSpinnerNode(li) {
  if (li._spinnerEl) {
    return li._spinnerEl;
  }

  const spinner = document.createElement('span');
  spinner.className = 'job-item-spinner';
  spinner.setAttribute('aria-hidden', 'true');
  li.appendChild(spinner);
  li._spinnerEl = spinner;
  return spinner;
}

function removeJobListItemSecondaryNode(li) {
  if (li._secondaryEl && li._secondaryEl.parentNode === li) {
    li.removeChild(li._secondaryEl);
  }
  li._secondaryEl = null;
}

function removeJobListItemSpinnerNode(li) {
  if (li._spinnerEl && li._spinnerEl.parentNode === li) {
    li.removeChild(li._spinnerEl);
  }
  li._spinnerEl = null;
}

function updateReadyJobListItem(li, job) {
  li.className = 'job-item';
  if (job.status === 'processing') {
    li.classList.add('is-processing');
  }
  if (job.id === selectedJobId) {
    li.classList.add('selected');
  }

  li.dataset.jobId = job.id;
  li._nameEl.textContent = job.originalFilename;

  if (job.matchedClientDirName) {
    const client = ensureJobListItemSecondaryNode(li, 'job-client');
    client.textContent = job.matchedClientDirName;
  } else {
    removeJobListItemSecondaryNode(li);
  }

  if (job.status === 'processing') {
    ensureJobListItemSpinnerNode(li);
  } else {
    removeJobListItemSpinnerNode(li);
  }
}

function updateFailedJobListItem(li, job) {
  li.className = 'job-item failed';
  if (job.id === selectedJobId) {
    li.classList.add('selected');
  }

  li.dataset.jobId = job.id;
  li._nameEl.textContent = job.originalFilename;

  if (job.error) {
    const error = ensureJobListItemSecondaryNode(li, 'job-error');
    error.textContent = job.error;
  } else {
    removeJobListItemSecondaryNode(li);
  }

  removeJobListItemSpinnerNode(li);
}

function renderJobList(processingJobs, readyJobs, failedJobs) {
  const displayedReadyJobs = buildDisplayedReadyJobs(processingJobs, readyJobs);
  const safeFailedJobs = Array.isArray(failedJobs) ? failedJobs : [];
  const desiredNodes = [];
  const activeKeys = new Set();

  if (displayedReadyJobs.length === 0 && safeFailedJobs.length === 0) {
    const messageNode = ensureJobListMessageNode();
    messageNode.textContent = 'Inga klara jobb ännu.';
    desiredNodes.push(messageNode);
    activeKeys.add('message:empty');
  } else {
    displayedReadyJobs.forEach((job) => {
      const key = `ready:${job.id}`;
      const li = ensureJobListItemNode(key);
      updateReadyJobListItem(li, job);
      desiredNodes.push(li);
      activeKeys.add(key);
    });

    if (safeFailedJobs.length > 0) {
      if (displayedReadyJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Misslyckade jobb');
        desiredNodes.push(labelNode);
        activeKeys.add('label:failed');
      }

      safeFailedJobs.forEach((job) => {
        const key = `failed:${job.id}`;
        const li = ensureJobListItemNode(key);
        updateFailedJobListItem(li, job);
        desiredNodes.push(li);
        activeKeys.add(key);
      });
    }
  }

  desiredNodes.forEach((node) => {
    jobListEl.appendChild(node);
  });

  Array.from(jobListNodeByKey.entries()).forEach(([key, node]) => {
    if (activeKeys.has(key)) {
      return;
    }

    if (node.parentNode === jobListEl) {
      jobListEl.removeChild(node);
    }
    jobListNodeByKey.delete(key);
  });

  renderSelectedJobPanel();
}

function findJobById(jobId) {
  if (!jobId) {
    return null;
  }

  const readyOrFailedJobs = []
    .concat(Array.isArray(state.readyJobs) ? state.readyJobs : [])
    .concat(Array.isArray(state.failedJobs) ? state.failedJobs : []);

  const directJob = readyOrFailedJobs.find((entry) => entry.id === jobId) || null;
  if (directJob) {
    return directJob;
  }

  const processingJob = Array.isArray(state.processingJobs)
    ? state.processingJobs.find((entry) => entry.id === jobId) || null
    : null;
  if (!processingJob) {
    return null;
  }

  const snapshot = lastKnownJobDisplayById.get(jobId);
  if (!snapshot) {
    return processingJob;
  }

  return {
    ...snapshot,
    ...processingJob,
    matchedClientDirName: snapshot.matchedClientDirName,
    matchedSenderId: snapshot.matchedSenderId,
    topMatchedCategoryId: snapshot.topMatchedCategoryId,
    topMatchedCategoryScore: snapshot.topMatchedCategoryScore
  };
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
  } else if (selectedJob.status === 'processing') {
    metaParts.push('Status: Bearbetas');
  } else {
    metaParts.push(selectedJob.status === 'failed' ? 'Status: Misslyckat' : 'Status: Klar');
  }
  selectedJobMetaEl.textContent = metaParts.join(' | ');
  selectedJobReprocessEl.disabled = selectedJob.status === 'processing' || !selectedJob.hasReviewPdf;
  selectedJobRerunOcrEl.disabled = selectedJob.status === 'processing' || !selectedJob.hasSourcePdf;
}

function buildDisplayedReadyJobs(processingJobs, readyJobs) {
  const displayed = Array.isArray(readyJobs)
    ? readyJobs.map((job, index) => {
        const snapshot = lastKnownJobDisplayById.get(job.id);
        return {
          ...job,
          _displayOrder: typeof snapshot?._displayOrder === 'number' ? snapshot._displayOrder : index
        };
      })
    : [];
  const orderById = new Map(displayed.map((job) => [job.id, job._displayOrder]));
  const processingById = new Map(
    (Array.isArray(processingJobs) ? processingJobs : [])
      .filter((job) => job && typeof job.id === 'string' && job.id !== '')
      .map((job) => [job.id, job])
  );
  const pinnedJobs = Array.from(pinnedProcessingJobIds)
    .map((jobId) => processingById.get(jobId) || null)
    .filter((job) => job !== null);

  if (pinnedJobs.length === 0) {
    return displayed;
  }

  pinnedJobs.forEach((processingJob) => {
    const snapshot = lastKnownJobDisplayById.get(processingJob.id);
    if (snapshot) {
      displayed.push({
        ...snapshot,
        status: 'processing',
        matchedClientDirName: '\u00A0',
        _displayOrder: orderById.has(processingJob.id)
          ? orderById.get(processingJob.id)
          : (typeof snapshot._displayOrder === 'number' ? snapshot._displayOrder : Number.MAX_SAFE_INTEGER)
      });
      return;
    }

    displayed.push({
      ...processingJob,
      _displayOrder: orderById.has(processingJob.id)
        ? orderById.get(processingJob.id)
        : Number.MAX_SAFE_INTEGER
    });
  });

  displayed.sort((a, b) => {
    const aOrder = typeof a._displayOrder === 'number' ? a._displayOrder : null;
    const bOrder = typeof b._displayOrder === 'number' ? b._displayOrder : null;
    if (aOrder !== null || bOrder !== null) {
      if (aOrder === null) {
        return 1;
      }
      if (bOrder === null) {
        return -1;
      }
      if (aOrder !== bOrder) {
        return aOrder - bOrder;
      }
    }
    return String(b.createdAt || '').localeCompare(String(a.createdAt || ''));
  });
  return displayed;
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

function applySelectedJobId(jobId, options = {}) {
  const syncHash = options.syncHash !== false;
  const selectedJob = findJobById(jobId);
  selectedJobId = selectedJob ? selectedJob.id : '';
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  setViewerJob(selectedJobId);
  setClientForJob(selectedJob);
  setSenderForJob(selectedJob);
  setCategoryForJob(selectedJob);
  if (syncHash) {
    updateHashState();
  }
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
  if (preferredJobIdFromHash) {
    const preferredJob = findJobById(preferredJobIdFromHash);
    if (preferredJob) {
      applySelectedJobId(preferredJob.id, { syncHash: false });
      updateHashState();
      return;
    }
  }

  if (!Array.isArray(state.readyJobs) || state.readyJobs.length === 0) {
    const failedFallback = Array.isArray(state.failedJobs) && state.failedJobs.length > 0
      ? state.failedJobs[0]
      : null;
    applySelectedJobId(failedFallback ? failedFallback.id : '', { syncHash: false });
    updateHashState();
    return;
  }

  const currentSelection = findJobById(selectedJobId);
  if (currentSelection) {
    applySelectedJobId(currentSelection.id, { syncHash: false });
    updateHashState();
    return;
  }

  applySelectedJobId(state.readyJobs[0].id, { syncHash: false });
  updateHashState();
}

function applyState(nextState) {
  const previousReadyJobs = Array.isArray(state.readyJobs) ? state.readyJobs : [];
  previousReadyJobs.forEach((job, index) => {
    if (!job || typeof job.id !== 'string' || job.id === '') {
      return;
    }
    lastKnownJobDisplayById.set(job.id, { ...job, _displayOrder: index });
  });

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

  const activeJobIds = new Set(
    []
      .concat(Array.isArray(state.processingJobs) ? state.processingJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.readyJobs) ? state.readyJobs.map((job) => job.id) : [])
      .filter((jobId) => typeof jobId === 'string' && jobId !== '')
  );
  Array.from(lastKnownJobDisplayById.keys()).forEach((jobId) => {
    if (!activeJobIds.has(jobId)) {
      lastKnownJobDisplayById.delete(jobId);
    }
  });

  const activeProcessingJobIds = new Set(
    (Array.isArray(state.processingJobs) ? state.processingJobs : [])
      .map((job) => job && typeof job.id === 'string' ? job.id : '')
      .filter((jobId) => jobId !== '')
  );
  Array.from(pinnedProcessingJobIds).forEach((jobId) => {
    if (!activeProcessingJobIds.has(jobId)) {
      pinnedProcessingJobIds.delete(jobId);
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

function settingsPanelEl(tabId) {
  return document.getElementById('settings-panel-' + tabId);
}

function mountSettingsPanel(tabId) {
  if (mountedSettingsPanels.has(tabId)) {
    return;
  }

  const panel = settingsPanelEl(tabId);
  const templateId = settingsPanelTemplateIds[tabId];
  const template = templateId ? document.getElementById(templateId) : null;
  if (!panel || !(template instanceof HTMLTemplateElement)) {
    return;
  }

  panel.replaceChildren(template.content.cloneNode(true));
  mountedSettingsPanels.add(tabId);
}

function bindSettingsPanelRefs(tabId) {
  if (boundSettingsPanels.has(tabId)) {
    return;
  }

  mountSettingsPanel(tabId);

  if (tabId === 'clients') {
    clientsTextareaEl = document.getElementById('clients-textarea');
    clientsCancelEl = document.getElementById('clients-cancel');
    clientsApplyEl = document.getElementById('clients-apply');
    clientsTextareaEl.addEventListener('input', () => {
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
  } else if (tabId === 'senders') {
    sendersListEl = document.getElementById('senders-list');
    sendersAddRowEl = document.getElementById('senders-add-row');
    sendersCancelEl = document.getElementById('senders-cancel');
    sendersApplyEl = document.getElementById('senders-apply');
    sendersSortOrderEl = document.getElementById('senders-sort-order');
    sendersExpandAllEl = document.getElementById('senders-expand-all');
    sendersCollapseAllEl = document.getElementById('senders-collapse-all');
    sendersSelectedCountEl = document.getElementById('senders-selected-count');
    sendersClearSelectionEl = document.getElementById('senders-clear-selection');
    sendersMergeSelectedEl = document.getElementById('senders-merge-selected');
    senderMergeOverlayEl = document.getElementById('sender-merge-overlay');
    senderMergeEditorEl = document.getElementById('sender-merge-editor');
    senderMergeCancelEl = document.getElementById('sender-merge-cancel');
    senderMergeApplyEl = document.getElementById('sender-merge-apply');
    sendersSortOrderEl.value = sendersSortOrder;
    sendersAddRowEl.addEventListener('click', () => {
      sendersDraft.push(defaultSenderDraft());
      renderSendersEditor();
      updateSettingsActionButtons();
    });
    sendersSortOrderEl.addEventListener('change', () => {
      const previousSortOrder = sendersSortOrder;
      sendersSortOrder = String(sendersSortOrderEl.value || 'name');
      if (sendersSortOrder === 'similarity' && previousSortOrder !== 'similarity') {
        collapsedSenderUiKeys.clear();
        sendersDraft.forEach((row) => {
          collapsedSenderUiKeys.add(senderUiKey(row));
        });
      }
      renderSendersEditor();
    });
    sendersClearSelectionEl.addEventListener('click', () => {
      clearSenderSelections();
      renderSendersEditor();
    });
    sendersExpandAllEl.addEventListener('click', () => {
      collapsedSenderUiKeys.clear();
      renderSendersEditor();
    });
    sendersCollapseAllEl.addEventListener('click', () => {
      collapsedSenderUiKeys.clear();
      sendersDraft.forEach((row) => {
        collapsedSenderUiKeys.add(senderUiKey(row));
      });
      renderSendersEditor();
    });
    sendersMergeSelectedEl.addEventListener('click', () => {
      openSenderMergeOverlay();
    });
    senderMergeCancelEl.addEventListener('click', () => {
      closeSenderMergeOverlay();
    });
    senderMergeApplyEl.addEventListener('click', async () => {
      try {
        await applySenderMerge();
      } catch (error) {
        alert(error.message || 'Kunde inte slå ihop avsändare.');
      }
    });
    senderMergeOverlayEl.addEventListener('click', (event) => {
      if (event.target === senderMergeOverlayEl) {
        closeSenderMergeOverlay();
      }
    });
    sendersCancelEl.addEventListener('click', () => {
      let parsed = [];
      try {
        parsed = JSON.parse(sendersBaselineJson);
      } catch (error) {
        parsed = [];
      }
      sendersDraft = Array.isArray(parsed) ? parsed.map(sanitizeSenderDraft) : [];
      clearSenderSelections();
      closeSenderMergeOverlay();
      renderSendersEditor();
      updateSettingsActionButtons();
    });
    sendersApplyEl.addEventListener('click', async () => {
      try {
        await saveSendersSettings();
      } catch (error) {
        alert(error.message || 'Kunde inte spara avsändare.');
      }
    });
  } else if (tabId === 'matching') {
    matchingListEl = document.getElementById('matching-list');
    matchingAddRowEl = document.getElementById('matching-add-row');
    matchingCancelEl = document.getElementById('matching-cancel');
    matchingApplyEl = document.getElementById('matching-apply');
    matchingInvoiceThresholdEl = document.getElementById('matching-invoice-threshold');
    matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);
    matchingInvoiceThresholdEl.addEventListener('input', () => {
      matchingInvoiceFieldMinConfidenceDraft = sanitizeInvoiceFieldMinConfidence(matchingInvoiceThresholdEl.value, 0.7);
      updateSettingsActionButtons();
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
  } else if (tabId === 'ocr-processing') {
    ocrSkipExistingTextEl = document.getElementById('ocr-skip-existing-text');
    ocrOptimizeLevelEl = document.getElementById('ocr-optimize-level');
    ocrTextExtractionMethodEl = document.getElementById('ocr-text-extraction-method');
    ocrPdfSubstitutionsListEl = document.getElementById('ocr-pdf-substitutions-list');
    ocrPdfSubstitutionsAddRowEl = document.getElementById('ocr-pdf-substitutions-add-row');
    ocrProcessingCommandEl = document.getElementById('ocr-processing-command');
    jbig2StatusBadgeWrapEl = document.getElementById('jbig2-status-badge-wrap');
    jbig2StatusBadgeEl = document.getElementById('jbig2-status-badge');
    jbig2InstallCommandEl = document.getElementById('jbig2-install-command');
    jbig2RefreshButtonEl = document.getElementById('jbig2-refresh-button');
    ocrProcessingCancelEl = document.getElementById('ocr-processing-cancel');
    ocrProcessingApplyEl = document.getElementById('ocr-processing-apply');
    ocrSkipExistingTextEl.checked = ocrSkipExistingTextBaseline;
    ocrOptimizeLevelEl.value = String(ocrOptimizeLevelBaseline);
    ocrTextExtractionMethodEl.value = ocrTextExtractionMethodBaseline;
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
    renderJbig2Status(null);
    renderOcrProcessingCommand();
  } else if (tabId === 'categories') {
    categoriesListEl = document.getElementById('categories-list');
    systemCategoryEditorEl = document.getElementById('system-category-editor');
    categoriesAddCategoryEl = document.getElementById('categories-add-category');
    categoriesCancelEl = document.getElementById('categories-cancel');
    categoriesApplyEl = document.getElementById('categories-apply');
    archiveTabEls = Array.from(document.querySelectorAll('[data-archive-tab]'));
    archiveViewCategoriesEl = document.getElementById('archive-view-categories');
    archiveViewSystemEl = document.getElementById('archive-view-system');
    archiveTabEls.forEach((tabButton) => {
      tabButton.addEventListener('click', () => {
        const nextTabId = tabButton.dataset.archiveTab;
        if (!nextTabId || nextTabId === activeArchiveTabId) {
          return;
        }
        setArchiveTab(nextTabId);
      });
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
      categoriesDraft = Array.isArray(parsed.archiveFolders) ? parsed.archiveFolders.map(sanitizeArchiveFolder) : [];
      systemCategoriesDraft = sanitizeSystemCategories(parsed.systemCategories);
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
  } else if (tabId === 'jobs') {
    jobsStateTransportEl = document.getElementById('jobs-state-transport');
    settingsResetJobsEl = document.getElementById('settings-reset-jobs');
    jobsStateTransportEl.value = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
    jobsStateTransportEl.addEventListener('change', async () => {
      const previousTransport = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
      const nextTransport = sanitizeStateUpdateTransport(jobsStateTransportEl.value, previousTransport);
      jobsStateTransportEl.disabled = true;
      settingsResetJobsEl.disabled = true;
      try {
        await saveStateTransportSetting(nextTransport);
        window.location.reload();
      } catch (error) {
        jobsStateTransportEl.value = previousTransport;
        alert(error.message || 'Kunde inte spara uppdateringsmetod.');
      } finally {
        jobsStateTransportEl.disabled = false;
        settingsResetJobsEl.disabled = false;
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
  } else if (tabId === 'paths') {
    outputBasePathEl = document.getElementById('output-base-path');
    pathsCancelEl = document.getElementById('paths-cancel');
    pathsApplyEl = document.getElementById('paths-apply');
    outputBasePathEl.addEventListener('input', () => {
      updateSettingsActionButtons();
    });
    pathsCancelEl.addEventListener('click', () => {
      outputBasePathEl.value = pathsBaselineValue;
      updateSettingsActionButtons();
    });
    pathsApplyEl.addEventListener('click', async () => {
      try {
        await savePathSettings();
      } catch (error) {
        alert(error.message || 'Kunde inte spara sökvägar.');
      }
    });
  }

  boundSettingsPanels.add(tabId);
}

async function ensureSettingsPanelReady(tabId, options = {}) {
  bindSettingsPanelRefs(tabId);

  if (tabId === 'jobs' && jobsStateTransportEl) {
    jobsStateTransportEl.value = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
  }

  const reload = options.reload === true;
  if (loadedSettingsPanels.has(tabId) && !reload) {
    return true;
  }

  if (tabId === 'clients') {
    await loadClientsText();
  } else if (tabId === 'senders') {
    await loadSendersSettings();
  } else if (tabId === 'matching') {
    await loadMatchingSettings();
  } else if (tabId === 'ocr-processing') {
    await loadOcrProcessingSettings(options);
  } else if (tabId === 'categories') {
    await loadCategories();
    setArchiveTab('categories');
  } else if (tabId === 'paths') {
    await loadPathSettings();
  }

  loadedSettingsPanels.add(tabId);
  return true;
}

async function openClientsSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('clients');

  try {
    await ensureSettingsPanelReady('clients');
  } catch (error) {
    alert('Kunde inte ladda huvudmän.');
    clientsBaselineText = clientsTextareaEl ? clientsTextareaEl.value : '';
    updateSettingsActionButtons();
    return false;
  }

  clientsTextareaEl.focus();
  updateSettingsActionButtons();
  return true;
}

async function openSendersSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('senders');

  try {
    await ensureSettingsPanelReady('senders');
  } catch (error) {
    alert('Kunde inte ladda avsändare.');
    sendersDraft = [];
    sendersBaselineJson = normalizedSendersJson(sendersDraft);
    renderSendersEditor();
    updateSettingsActionButtons();
    return false;
  }

  sendersAddRowEl.focus();
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
    await ensureSettingsPanelReady('categories');
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

  closeSenderMergeOverlay();
  settingsModalEl.classList.add('hidden');
  return true;
}

function setSettingsTab(tabId) {
  if (tabId !== 'senders') {
    closeSenderMergeOverlay();
  }
  mountSettingsPanel(tabId);
  bindSettingsPanelRefs(tabId);
  activeSettingsTabId = tabId;

  settingsTabEls.forEach((tabButton) => {
    const isActive = tabButton.dataset.settingsTab === tabId;
    tabButton.classList.toggle('active', isActive);
  });

  const panelIds = ['clients', 'senders', 'matching', 'ocr-processing', 'categories', 'jobs', 'paths'];
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
    || tabId === 'senders'
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

function normalizedSendersJson(senders) {
  return JSON.stringify(senders.map(sanitizeSenderDraft));
}

function normalizedCategoriesJson(categories, systemCategories) {
  return JSON.stringify({
    archiveFolders: categories.map(sanitizeArchiveFolder),
    systemCategories: sanitizeSystemCategories(systemCategories)
  });
}

function isClientsDirty() {
  if (!clientsTextareaEl) {
    return false;
  }
  return clientsTextareaEl.value !== clientsBaselineText;
}

function isMatchingDirty() {
  return normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft) !== matchingBaselineJson;
}

function isSendersDirty() {
  return normalizedSendersJson(sendersDraft) !== sendersBaselineJson;
}

function isCategoriesDirty() {
  return normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft) !== categoriesBaselineJson;
}

function isOcrProcessingDirty() {
  if (!ocrSkipExistingTextEl || !ocrOptimizeLevelEl || !ocrTextExtractionMethodEl) {
    return false;
  }
  return ocrSkipExistingTextEl.checked !== ocrSkipExistingTextBaseline
    || sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1) !== ocrOptimizeLevelBaseline
    || sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout') !== ocrTextExtractionMethodBaseline
    || normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft) !== ocrPdfSubstitutionsBaselineJson;
}

function isPathsDirty() {
  if (!outputBasePathEl) {
    return false;
  }
  return normalizedPathValue(outputBasePathEl.value) !== pathsBaselineValue;
}

function isSettingsTabDirty(tabId) {
  if (tabId === 'clients') {
    return isClientsDirty();
  }
  if (tabId === 'matching') {
    return isMatchingDirty();
  }
  if (tabId === 'senders') {
    return isSendersDirty();
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
  return isClientsDirty() || isSendersDirty() || isMatchingDirty() || isOcrProcessingDirty() || isCategoriesDirty() || isPathsDirty();
}

function panelActionButtonsForTab(tabId) {
  if (tabId === 'clients') {
    return [clientsCancelEl, clientsApplyEl];
  }
  if (tabId === 'senders') {
    return [sendersCancelEl, sendersApplyEl];
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
  const sendersDirty = isSendersDirty();
  const matchingDirty = isMatchingDirty();
  const ocrProcessingDirty = isOcrProcessingDirty();
  const categoriesDirty = isCategoriesDirty();
  const pathsDirty = isPathsDirty();

  if (clientsCancelEl && clientsApplyEl) {
    clientsCancelEl.disabled = !clientsDirty;
    clientsApplyEl.disabled = !clientsDirty;
  }

  if (sendersCancelEl && sendersApplyEl) {
    sendersCancelEl.disabled = !sendersDirty;
    sendersApplyEl.disabled = !sendersDirty;
  }

  if (matchingCancelEl && matchingApplyEl) {
    matchingCancelEl.disabled = !matchingDirty;
    matchingApplyEl.disabled = !matchingDirty;
  }

  if (ocrProcessingCancelEl && ocrProcessingApplyEl) {
    ocrProcessingCancelEl.disabled = !ocrProcessingDirty;
    ocrProcessingApplyEl.disabled = !ocrProcessingDirty;
  }

  if (categoriesCancelEl && categoriesApplyEl) {
    categoriesCancelEl.disabled = !categoriesDirty;
    categoriesApplyEl.disabled = !categoriesDirty;
  }

  if (pathsCancelEl && pathsApplyEl) {
    pathsCancelEl.disabled = !pathsDirty;
    pathsApplyEl.disabled = !pathsDirty;
  }
}

function flashPanelActions(tabId) {
  const buttons = panelActionButtonsForTab(tabId).filter((button) => button instanceof HTMLElement);
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

function sanitizeStateUpdateTransport(value, fallback = 'polling') {
  const normalizedValue = String(value || '').trim().toLowerCase();
  if (normalizedValue === 'sse') {
    return 'sse';
  }
  if (normalizedValue === 'polling') {
    return 'polling';
  }
  return String(fallback || '').trim().toLowerCase() === 'sse' ? 'sse' : 'polling';
}

async function saveStateTransportSetting(nextTransport) {
  const normalizedTransport = sanitizeStateUpdateTransport(nextTransport, 'polling');
  const response = await fetch('/api/save-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ stateUpdateTransport: normalizedTransport })
  });

  const payload = await response.json().catch(() => null);
  if (
    !response.ok
    || !payload
    || payload.ok !== true
    || typeof payload.stateUpdateTransport !== 'string'
  ) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara uppdateringsmetod';
    throw new Error(message);
  }

  stateUpdateTransport = sanitizeStateUpdateTransport(payload.stateUpdateTransport, stateUpdateTransport);
  return stateUpdateTransport;
}

function defaultReplacement() {
  return {
    from: '',
    to: ''
  };
}

function defaultSenderDraft() {
  return {
    uiKey: `tmp-${senderDraftUiKeySeq++}`,
    id: null,
    name: '',
    orgNumber: '',
    domain: '',
    kind: '',
    notes: '',
    paymentNumbers: []
  };
}

function defaultSenderPaymentDraft() {
  return {
    id: null,
    type: 'bankgiro',
    number: ''
  };
}

function digitsOnly(value) {
  return String(value || '').replace(/\D+/g, '');
}

function formatSenderPaymentNumberForDisplay(type, value) {
  const digits = digitsOnly(value);
  if (!digits) {
    return '';
  }

  if (type === 'bankgiro') {
    if (digits.length >= 5) {
      return `${digits.slice(0, -4)}-${digits.slice(-4)}`;
    }
    return digits;
  }

  if (type === 'plusgiro') {
    if (digits.length >= 2) {
      return `${digits.slice(0, -1)}-${digits.slice(-1)}`;
    }
    return digits;
  }

  return digits;
}

function sanitizeReplacement(row) {
  const input = row && typeof row === 'object' ? row : {};
  return {
    from: typeof input.from === 'string' ? input.from : '',
    to: typeof input.to === 'string' ? input.to : ''
  };
}

function sanitizeSenderDraft(row) {
  const input = row && typeof row === 'object' ? row : {};
  const idValue = input.id;
  const id = Number.isInteger(idValue) && idValue > 0
    ? idValue
    : null;
  const uiKey = typeof input.uiKey === 'string' && input.uiKey.trim() !== ''
    ? input.uiKey.trim()
    : `tmp-${senderDraftUiKeySeq++}`;
  const rawPaymentNumbers = Array.isArray(input.paymentNumbers) ? input.paymentNumbers : [];
  return {
    uiKey,
    id,
    name: typeof input.name === 'string' ? input.name : '',
    orgNumber: typeof input.orgNumber === 'string' ? input.orgNumber : '',
    domain: typeof input.domain === 'string' ? input.domain : '',
    kind: typeof input.kind === 'string' ? input.kind : '',
    notes: typeof input.notes === 'string' ? input.notes : '',
    paymentNumbers: rawPaymentNumbers.map(sanitizeSenderPaymentDraft)
  };
}

function sanitizeSenderPaymentDraft(row) {
  const input = row && typeof row === 'object' ? row : {};
  const idValue = input.id;
  const id = Number.isInteger(idValue) && idValue > 0
    ? idValue
    : null;
  const type = String(input.type || 'bankgiro').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
  return {
    id,
    type,
    number: typeof input.number === 'string' ? input.number : ''
  };
}

function senderSortFieldValue(row, field) {
  if (!row || typeof row !== 'object') {
    return '';
  }

  if (field === 'orgNumber') {
    return String(row.orgNumber || '').trim().toLowerCase();
  }

  if (field === 'domain') {
    return String(row.domain || '').trim().toLowerCase();
  }

  return String(row.name || '').trim().toLowerCase();
}

function senderUiKey(row) {
  if (row && Number.isInteger(row.id) && row.id > 0) {
    return `id-${row.id}`;
  }
  if (row && typeof row.uiKey === 'string' && row.uiKey.trim() !== '') {
    return row.uiKey.trim();
  }
  return `tmp-${senderDraftUiKeySeq++}`;
}

function normalizeSenderNameForSimilarity(value) {
  return String(value || '')
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9]+/g, ' ')
    .trim()
    .replace(/\s+/g, ' ');
}

function bigramsForSimilarity(value) {
  const compact = String(value || '').replace(/\s+/g, '');
  if (compact.length < 2) {
    return compact === '' ? [] : [compact];
  }
  const grams = [];
  for (let index = 0; index < compact.length - 1; index += 1) {
    grams.push(compact.slice(index, index + 2));
  }
  return grams;
}

function diceSimilarity(a, b) {
  const aGrams = bigramsForSimilarity(a);
  const bGrams = bigramsForSimilarity(b);
  if (aGrams.length === 0 || bGrams.length === 0) {
    return 0;
  }
  const counts = new Map();
  aGrams.forEach((gram) => {
    counts.set(gram, (counts.get(gram) || 0) + 1);
  });
  let overlap = 0;
  bGrams.forEach((gram) => {
    const count = counts.get(gram) || 0;
    if (count > 0) {
      overlap += 1;
      counts.set(gram, count - 1);
    }
  });
  return (2 * overlap) / (aGrams.length + bGrams.length);
}

function tokenJaccardSimilarity(a, b) {
  const aTokens = normalizeSenderNameForSimilarity(a).split(' ').filter(Boolean);
  const bTokens = normalizeSenderNameForSimilarity(b).split(' ').filter(Boolean);
  if (aTokens.length === 0 || bTokens.length === 0) {
    return 0;
  }
  const aSet = new Set(aTokens);
  const bSet = new Set(bTokens);
  let intersection = 0;
  aSet.forEach((token) => {
    if (bSet.has(token)) {
      intersection += 1;
    }
  });
  const union = new Set([...aSet, ...bSet]).size;
  return union > 0 ? intersection / union : 0;
}

function senderNameSimilarity(a, b) {
  const normalizedA = normalizeSenderNameForSimilarity(a);
  const normalizedB = normalizeSenderNameForSimilarity(b);
  if (normalizedA === '' || normalizedB === '') {
    return 0;
  }
  if (normalizedA === normalizedB) {
    return 1;
  }
  const dice = diceSimilarity(normalizedA, normalizedB);
  const token = tokenJaccardSimilarity(normalizedA, normalizedB);
  const contains = normalizedA.includes(normalizedB) || normalizedB.includes(normalizedA) ? 1 : 0;
  return Math.max(0, Math.min(1, (dice * 0.6) + (token * 0.3) + (contains * 0.1)));
}

function similarityScoreLabel(score) {
  return Number.isFinite(score) ? score.toFixed(2) : '0.00';
}

function uniqueSenderFieldOptions(rows, field, config = {}) {
  const ignoreEmpty = config.ignoreEmpty === true;
  const seen = new Set();
  const values = [];
  rows.forEach((row) => {
    const rawValue = row && typeof row[field] === 'string' ? row[field] : '';
    const normalizedValue = rawValue.trim();
    if (ignoreEmpty && normalizedValue === '') {
      return;
    }
    const key = normalizedValue.toLowerCase();
    if (seen.has(key)) {
      return;
    }
    seen.add(key);
    values.push(normalizedValue);
  });
  return values;
}

function mergedSenderNotes(rows) {
  const parts = [];
  const seen = new Set();
  rows.forEach((row) => {
    const notes = row && typeof row.notes === 'string' ? row.notes.trim() : '';
    if (notes === '') {
      return;
    }
    if (seen.has(notes)) {
      return;
    }
    seen.add(notes);
    parts.push(notes);
  });
  return parts.join('\n\n');
}

function mergedSenderPaymentNumbers(rows) {
  const seen = new Set();
  const payments = [];
  rows.forEach((row) => {
    const paymentRows = Array.isArray(row && row.paymentNumbers) ? row.paymentNumbers : [];
    paymentRows.forEach((payment) => {
      const normalized = sanitizeSenderPaymentDraft(payment);
      const key = `${normalized.type}:${digitsOnly(normalized.number)}`;
      if (digitsOnly(normalized.number) === '' || seen.has(key)) {
        return;
      }
      seen.add(key);
      payments.push(normalized);
    });
  });
  payments.sort((a, b) => {
    const aKey = `${a.type}:${digitsOnly(a.number)}`;
    const bKey = `${b.type}:${digitsOnly(b.number)}`;
    return aKey.localeCompare(bKey, 'sv');
  });
  return payments;
}

function closeSenderMergeOverlay() {
  senderMergeState = null;
  if (senderMergeOverlayEl) {
    senderMergeOverlayEl.classList.add('hidden');
  }
}

function updateSendersSelectionSummary() {
  if (sendersSelectedCountEl) {
    sendersSelectedCountEl.textContent = `Antal markerade avsändare: ${selectedSenderUiKeys.size}`;
  }
  if (sendersClearSelectionEl) {
    sendersClearSelectionEl.disabled = selectedSenderUiKeys.size === 0;
  }
  if (sendersMergeSelectedEl) {
    sendersMergeSelectedEl.disabled = selectedSenderUiKeys.size < 2;
  }
}

function clearSenderSelections() {
  selectedSenderUiKeys.clear();
  updateSendersSelectionSummary();
}

function updateSimilarityGroupCheckboxState(groupEl) {
  if (!groupEl) {
    return;
  }
  const groupCheckbox = groupEl.querySelector('.sender-group-checkbox');
  if (!groupCheckbox) {
    return;
  }
  const senderCheckboxes = Array.from(groupEl.querySelectorAll('.sender-select-checkbox'));
  if (senderCheckboxes.length === 0) {
    groupCheckbox.checked = false;
    groupCheckbox.indeterminate = false;
    return;
  }
  const selectedCount = senderCheckboxes.filter((checkbox) => checkbox.checked).length;
  groupCheckbox.checked = selectedCount === senderCheckboxes.length;
  groupCheckbox.indeterminate = selectedCount > 0 && selectedCount < senderCheckboxes.length;
}

function buildSenderMergeState() {
  const selectedEntries = sendersDraft
    .map((row, rowIndex) => ({ row, rowIndex, uiKey: senderUiKey(row) }))
    .filter((entry) => selectedSenderUiKeys.has(entry.uiKey));

  if (selectedEntries.length < 2) {
    return null;
  }

  const rows = selectedEntries.map((entry) => entry.row);
  const nameOptions = uniqueSenderFieldOptions(rows, 'name');
  const orgNumberOptions = uniqueSenderFieldOptions(rows, 'orgNumber', { ignoreEmpty: true });
  const domainOptions = uniqueSenderFieldOptions(rows, 'domain', { ignoreEmpty: true });
  const baseEntry = selectedEntries[0];
  const baseUiKey = baseEntry.uiKey;
  const baseId = Number.isInteger(baseEntry.row.id) && baseEntry.row.id > 0 ? baseEntry.row.id : null;
  const uiKey = senderUiKey(baseEntry.row);

  return {
    baseUiKey,
    sourceUiKeys: selectedEntries.map((entry) => entry.uiKey),
    draft: sanitizeSenderDraft({
      uiKey,
      id: baseId,
      name: nameOptions[0] || '',
      orgNumber: orgNumberOptions[0] || '',
      domain: domainOptions[0] || '',
      notes: mergedSenderNotes(rows),
      paymentNumbers: mergedSenderPaymentNumbers(rows)
    }),
    fieldOptions: {
      name: nameOptions,
      orgNumber: orgNumberOptions,
      domain: domainOptions
    }
  };
}

function getSortedSenderEntries() {
  const entries = sendersDraft.map((row, rowIndex) => ({ row, rowIndex }));

  entries.sort((a, b) => {
    if (sendersSortOrder === 'paymentCount') {
      const aCount = Array.isArray(a.row.paymentNumbers) ? a.row.paymentNumbers.length : 0;
      const bCount = Array.isArray(b.row.paymentNumbers) ? b.row.paymentNumbers.length : 0;
      if (aCount !== bCount) {
        return bCount - aCount;
      }
    } else {
      const aValue = senderSortFieldValue(a.row, sendersSortOrder);
      const bValue = senderSortFieldValue(b.row, sendersSortOrder);
      if (aValue !== bValue) {
        if (aValue === '') {
          return 1;
        }
        if (bValue === '') {
          return -1;
        }
        return aValue.localeCompare(bValue, 'sv');
      }
    }

    const aName = senderSortFieldValue(a.row, 'name');
    const bName = senderSortFieldValue(b.row, 'name');
    if (aName !== bName) {
      if (aName === '') {
        return 1;
      }
      if (bName === '') {
        return -1;
      }
      return aName.localeCompare(bName, 'sv');
    }

    return a.rowIndex - b.rowIndex;
  });

  return entries;
}

function buildSimilarSenderGroups() {
  const entries = sendersDraft.map((row, rowIndex) => ({ row, rowIndex }));
  if (entries.length === 0) {
    return [];
  }

  const pairScores = new Map();
  const adjacency = new Map();
  const threshold = 0.72;
  entries.forEach((entry, index) => {
    adjacency.set(index, []);
  });

  for (let leftIndex = 0; leftIndex < entries.length; leftIndex += 1) {
    for (let rightIndex = leftIndex + 1; rightIndex < entries.length; rightIndex += 1) {
      const score = senderNameSimilarity(entries[leftIndex].row.name, entries[rightIndex].row.name);
      pairScores.set(`${leftIndex}:${rightIndex}`, score);
      if (score >= threshold) {
        adjacency.get(leftIndex).push({ index: rightIndex, score });
        adjacency.get(rightIndex).push({ index: leftIndex, score });
      }
    }
  }

  const visited = new Set();
  const groups = [];

  function pairScore(indexA, indexB) {
    const left = Math.min(indexA, indexB);
    const right = Math.max(indexA, indexB);
    return pairScores.get(`${left}:${right}`) || 0;
  }

  entries.forEach((_, startIndex) => {
    if (visited.has(startIndex)) {
      return;
    }
    const queue = [startIndex];
    const componentIndexes = [];
    visited.add(startIndex);
    while (queue.length > 0) {
      const currentIndex = queue.shift();
      componentIndexes.push(currentIndex);
      (adjacency.get(currentIndex) || []).forEach(({ index: neighborIndex }) => {
        if (visited.has(neighborIndex)) {
          return;
        }
        visited.add(neighborIndex);
        queue.push(neighborIndex);
      });
    }

    const componentEntries = componentIndexes.map((index) => entries[index]);
    let groupScore = 0;
    componentIndexes.forEach((leftIndex, leftPosition) => {
      for (let rightPosition = leftPosition + 1; rightPosition < componentIndexes.length; rightPosition += 1) {
        groupScore = Math.max(groupScore, pairScore(leftIndex, componentIndexes[rightPosition]));
      }
    });

    let anchorIndex = componentIndexes[0];
    let anchorStrength = -1;
    componentIndexes.forEach((candidateIndex) => {
      const totalScore = componentIndexes.reduce((sum, otherIndex) => {
        if (candidateIndex === otherIndex) {
          return sum;
        }
        return sum + pairScore(candidateIndex, otherIndex);
      }, 0);
      if (totalScore > anchorStrength) {
        anchorStrength = totalScore;
        anchorIndex = candidateIndex;
      }
    });

    componentEntries.sort((a, b) => {
      const aScore = pairScore(a.rowIndex, anchorIndex);
      const bScore = pairScore(b.rowIndex, anchorIndex);
      if (aScore !== bScore) {
        return bScore - aScore;
      }
      return senderSortFieldValue(a.row, 'name').localeCompare(senderSortFieldValue(b.row, 'name'), 'sv');
    });

    groups.push({
      entries: componentEntries,
      score: groupScore,
      isGroup: componentEntries.length > 1
    });
  });

  groups.sort((a, b) => {
    if (a.isGroup !== b.isGroup) {
      return a.isGroup ? -1 : 1;
    }
    if (a.isGroup && b.isGroup && a.score !== b.score) {
      return b.score - a.score;
    }
    const aName = senderSortFieldValue(a.entries[0].row, 'name');
    const bName = senderSortFieldValue(b.entries[0].row, 'name');
    return aName.localeCompare(bName, 'sv');
  });

  return groups;
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

function buildSenderEditorNode(row, rowIndex) {
  const currentSenderUiKey = senderUiKey(row);
  const isCollapsed = collapsedSenderUiKeys.has(currentSenderUiKey);
  const senderNode = document.createElement('div');
  senderNode.className = 'tree-node tree-folder';
  senderNode.dataset.senderUiKey = currentSenderUiKey;

  const senderRow = document.createElement('div');
  senderRow.className = 'tree-row';

  const senderBody = document.createElement('div');
  senderBody.className = 'tree-body folder-body';

  const senderIcon = document.createElement('span');
  senderIcon.className = 'sender-card-icon';
  senderIcon.setAttribute('aria-hidden', 'true');
  senderBody.appendChild(senderIcon);

  const nameInput = document.createElement('input');
  nameInput.type = 'text';
  nameInput.placeholder = 'Ex: Region Värmland';
  nameInput.value = row.name;
  nameInput.addEventListener('input', () => {
    sendersDraft[rowIndex].name = nameInput.value;
    updateSettingsActionButtons();
  });

  const orgNumberInput = document.createElement('input');
  orgNumberInput.type = 'text';
  orgNumberInput.placeholder = 'Ex: 556677-8899';
  orgNumberInput.value = row.orgNumber;
  orgNumberInput.addEventListener('input', () => {
    sendersDraft[rowIndex].orgNumber = orgNumberInput.value;
    updateSettingsActionButtons();
  });

  const domainInput = document.createElement('input');
  domainInput.type = 'text';
  domainInput.placeholder = 'Ex: regionvarmland.se';
  domainInput.value = row.domain;
  domainInput.addEventListener('input', () => {
    sendersDraft[rowIndex].domain = domainInput.value;
    updateSettingsActionButtons();
  });

  const notesInput = document.createElement('textarea');
  notesInput.placeholder = 'Anteckningar';
  notesInput.value = row.notes;
  notesInput.addEventListener('input', () => {
    sendersDraft[rowIndex].notes = notesInput.value;
    updateSettingsActionButtons();
  });

  const removeButton = document.createElement('button');
  removeButton.type = 'button';
  removeButton.className = 'category-remove';
  removeButton.textContent = 'Ta bort avsändare';
  removeButton.addEventListener('click', () => {
    selectedSenderUiKeys.delete(currentSenderUiKey);
    collapsedSenderUiKeys.delete(currentSenderUiKey);
    sendersDraft.splice(rowIndex, 1);
    renderSendersEditor();
    updateSettingsActionButtons();
  });

  const senderHeader = document.createElement('div');
  senderHeader.className = 'sender-header';

  const toggleButton = document.createElement('button');
  toggleButton.type = 'button';
  toggleButton.className = isCollapsed ? 'sender-toggle is-collapsed' : 'sender-toggle';
  toggleButton.title = isCollapsed ? 'Expandera avsändare' : 'Kontrahera avsändare';
  toggleButton.setAttribute('aria-label', toggleButton.title);
  toggleButton.addEventListener('click', () => {
    if (collapsedSenderUiKeys.has(currentSenderUiKey)) {
      collapsedSenderUiKeys.delete(currentSenderUiKey);
    } else {
      collapsedSenderUiKeys.add(currentSenderUiKey);
    }
    if (sendersSortOrder === 'similarity' && senderNode.isConnected) {
      const replacementNode = buildSenderEditorNode(row, rowIndex);
      senderNode.replaceWith(replacementNode);
      updateSimilarityGroupCheckboxState(replacementNode.closest('.sender-similarity-group'));
      return;
    }
    renderSendersEditor();
  });
  senderRow.appendChild(toggleButton);

  const senderSelectCheckbox = document.createElement('input');
  senderSelectCheckbox.type = 'checkbox';
  senderSelectCheckbox.className = 'sender-select-checkbox';
  senderSelectCheckbox.checked = selectedSenderUiKeys.has(currentSenderUiKey);
  senderSelectCheckbox.title = 'Markera avsändare';
  senderSelectCheckbox.addEventListener('change', () => {
    if (senderSelectCheckbox.checked) {
      selectedSenderUiKeys.add(currentSenderUiKey);
    } else {
      selectedSenderUiKeys.delete(currentSenderUiKey);
    }
    updateSendersSelectionSummary();
    updateSimilarityGroupCheckboxState(senderNode.closest('.sender-similarity-group'));
  });

  const senderSummaryFields = document.createElement('div');
  senderSummaryFields.className = 'sender-summary-fields';
  senderSummaryFields.appendChild(senderSelectCheckbox);
  senderSummaryFields.appendChild(createFloatingField('Namn', nameInput));
  senderSummaryFields.appendChild(createFloatingField('Org.nr', orgNumberInput));
  senderHeader.appendChild(senderSummaryFields);
  senderBody.appendChild(senderHeader);

  if (!isCollapsed) {
    const senderDetails = document.createElement('div');
    senderDetails.className = 'sender-details';

    const senderFields = document.createElement('div');
    senderFields.className = 'sender-fields';
    senderFields.appendChild(createFloatingField('Domän', domainInput));
    senderFields.appendChild(removeButton);
    senderFields.appendChild(createFloatingField('Anteckningar', notesInput, 'sender-notes-field'));
    senderDetails.appendChild(senderFields);

    const paymentList = document.createElement('div');
    paymentList.className = 'tree-children';

    const paymentsLabel = document.createElement('div');
    paymentsLabel.className = 'archive-level-label';
    paymentsLabel.textContent = 'Betalnummer';
    paymentList.appendChild(paymentsLabel);

    row.paymentNumbers.forEach((payment, paymentIndex) => {
      const paymentNode = document.createElement('div');
      paymentNode.className = 'tree-node tree-category has-parent';

      const paymentRow = document.createElement('div');
      paymentRow.className = 'tree-row';

      const paymentDot = document.createElement('span');
      paymentDot.className = 'tree-dot sender-payment-dot';
      paymentRow.appendChild(paymentDot);

      const paymentBody = document.createElement('div');
      paymentBody.className = 'tree-body category-body';

      const paymentIcon = document.createElement('span');
      paymentIcon.className = 'sender-payment-icon';
      paymentIcon.setAttribute('aria-hidden', 'true');
      paymentBody.appendChild(paymentIcon);

      const paymentFields = document.createElement('div');
      paymentFields.className = 'sender-payment-fields';

      const typeSelect = document.createElement('select');
      const bankgiroOption = document.createElement('option');
      bankgiroOption.value = 'bankgiro';
      bankgiroOption.textContent = 'Bankgiro';
      typeSelect.appendChild(bankgiroOption);
      const plusgiroOption = document.createElement('option');
      plusgiroOption.value = 'plusgiro';
      plusgiroOption.textContent = 'Plusgiro';
      typeSelect.appendChild(plusgiroOption);
      typeSelect.value = payment.type;
      typeSelect.addEventListener('change', () => {
        sendersDraft[rowIndex].paymentNumbers[paymentIndex].type = typeSelect.value === 'plusgiro' ? 'plusgiro' : 'bankgiro';
        numberInput.value = formatSenderPaymentNumberForDisplay(
          sendersDraft[rowIndex].paymentNumbers[paymentIndex].type,
          numberInput.value
        );
        sendersDraft[rowIndex].paymentNumbers[paymentIndex].number = numberInput.value;
        updateSettingsActionButtons();
      });

      const numberInput = document.createElement('input');
      numberInput.type = 'text';
      numberInput.placeholder = 'Ex: 5051-6822';
      numberInput.value = formatSenderPaymentNumberForDisplay(payment.type, payment.number);
      numberInput.addEventListener('input', () => {
        sendersDraft[rowIndex].paymentNumbers[paymentIndex].number = numberInput.value;
        updateSettingsActionButtons();
      });
      numberInput.addEventListener('blur', () => {
        const formatted = formatSenderPaymentNumberForDisplay(typeSelect.value, numberInput.value);
        numberInput.value = formatted;
        sendersDraft[rowIndex].paymentNumbers[paymentIndex].number = formatted;
        updateSettingsActionButtons();
      });

      const removePaymentButton = document.createElement('button');
      removePaymentButton.type = 'button';
      removePaymentButton.className = 'category-remove';
      removePaymentButton.textContent = 'Ta bort';
      removePaymentButton.addEventListener('click', () => {
        sendersDraft[rowIndex].paymentNumbers.splice(paymentIndex, 1);
        renderSendersEditor();
        updateSettingsActionButtons();
      });

      paymentFields.appendChild(createFloatingField('Typ', typeSelect));
      paymentFields.appendChild(createFloatingField('Nummer', numberInput));
      paymentFields.appendChild(removePaymentButton);
      paymentBody.appendChild(paymentFields);
      paymentRow.appendChild(paymentBody);
      paymentNode.appendChild(paymentRow);
      paymentList.appendChild(paymentNode);
    });

    senderDetails.appendChild(paymentList);

    const senderActions = document.createElement('div');
    senderActions.className = 'folder-actions';
    const addPaymentButton = document.createElement('button');
    addPaymentButton.type = 'button';
    addPaymentButton.textContent = 'Lägg till betalnummer';
    addPaymentButton.addEventListener('click', () => {
      sendersDraft[rowIndex].paymentNumbers.push(defaultSenderPaymentDraft());
      renderSendersEditor();
      updateSettingsActionButtons();
    });
    senderActions.appendChild(addPaymentButton);
    senderDetails.appendChild(senderActions);
    senderBody.appendChild(senderDetails);
  }

  senderRow.appendChild(senderBody);
  senderNode.appendChild(senderRow);
  return senderNode;
}

function buildSimilarityGroupNode(group) {
  if (!group.isGroup) {
    return buildSenderEditorNode(group.entries[0].row, group.entries[0].rowIndex);
  }

  const wrapper = document.createElement('div');
  wrapper.className = 'sender-similarity-group';
  wrapper.style.setProperty('--sender-group-size', String(group.entries.length));

  const groupCheckbox = document.createElement('input');
  groupCheckbox.type = 'checkbox';
  groupCheckbox.className = 'sender-group-checkbox';
  const groupUiKeys = group.entries.map((entry) => senderUiKey(entry.row));
  const selectedCount = groupUiKeys.filter((uiKey) => selectedSenderUiKeys.has(uiKey)).length;
  groupCheckbox.checked = selectedCount === groupUiKeys.length;
  groupCheckbox.indeterminate = selectedCount > 0 && selectedCount < groupUiKeys.length;
  groupCheckbox.title = 'Markera hela gruppen';
  groupCheckbox.addEventListener('change', () => {
    groupUiKeys.forEach((uiKey) => {
      if (groupCheckbox.checked) {
        selectedSenderUiKeys.add(uiKey);
      } else {
        selectedSenderUiKeys.delete(uiKey);
      }
    });
    wrapper.querySelectorAll('.sender-select-checkbox').forEach((checkbox) => {
      checkbox.checked = groupCheckbox.checked;
    });
    updateSimilarityGroupCheckboxState(wrapper);
    updateSendersSelectionSummary();
  });

  const brace = document.createElement('div');
  brace.className = 'sender-similarity-brace';
  brace.title = `Likhet ${similarityScoreLabel(group.score)}`;
  brace.setAttribute('aria-label', brace.title);
  const braceSvg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  braceSvg.setAttribute('viewBox', '0 0 36 100');
  braceSvg.setAttribute('preserveAspectRatio', 'none');
  braceSvg.setAttribute('aria-hidden', 'true');
  const bracePath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  bracePath.setAttribute('d', 'M32 2C12 2 16 18 16 32V42C16 47 12 50 5 50C12 50 16 53 16 58V68C16 82 12 98 32 98');
  bracePath.setAttribute('fill', 'none');
  bracePath.setAttribute('stroke', 'currentColor');
  bracePath.setAttribute('stroke-width', '4');
  bracePath.setAttribute('vector-effect', 'non-scaling-stroke');
  bracePath.setAttribute('stroke-linecap', 'round');
  bracePath.setAttribute('stroke-linejoin', 'round');
  braceSvg.appendChild(bracePath);
  brace.appendChild(braceSvg);

  const body = document.createElement('div');
  body.className = 'sender-similarity-items';
  group.entries.forEach((entry) => {
    body.appendChild(buildSenderEditorNode(entry.row, entry.rowIndex));
  });

  wrapper.appendChild(groupCheckbox);
  wrapper.appendChild(brace);
  wrapper.appendChild(body);
  return wrapper;
}

function renderSendersEditor() {
  const fragment = document.createDocumentFragment();

  if (sendersDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga avsändare ännu.';
    fragment.appendChild(empty);
    sendersListEl.replaceChildren(fragment);
    updateSendersSelectionSummary();
    return;
  }

  if (sendersSortOrder === 'similarity') {
    buildSimilarSenderGroups().forEach((group) => {
      fragment.appendChild(buildSimilarityGroupNode(group));
    });
  } else {
    getSortedSenderEntries().forEach(({ row, rowIndex }) => {
      fragment.appendChild(buildSenderEditorNode(row, rowIndex));
    });
  }

  sendersListEl.replaceChildren(fragment);
  updateSettingsActionButtons();
  updateSendersSelectionSummary();
}

function createSenderMergeField(label, fieldName, options, draft, onChange, extraClassName = '') {
  if (Array.isArray(options) && options.length > 1) {
    const select = document.createElement('select');
    const normalizedOptions = [];
    const seen = new Set();
    options.forEach((optionValue) => {
      const value = String(optionValue || '');
      if (seen.has(value)) {
        return;
      }
      seen.add(value);
      normalizedOptions.push(value);
      const option = document.createElement('option');
      option.value = value;
      option.textContent = value === '' ? '(Tomt)' : value;
      select.appendChild(option);
    });
    select.value = normalizedOptions.includes(draft[fieldName]) ? draft[fieldName] : normalizedOptions[0];
    select.addEventListener('change', () => onChange(select.value));
    return createFloatingField(label, select, extraClassName);
  }

  const input = document.createElement('input');
  input.type = 'text';
  input.value = draft[fieldName];
  input.addEventListener('input', () => onChange(input.value));
  return createFloatingField(label, input, extraClassName);
}

function renderSenderMergeEditor() {
  if (!senderMergeEditorEl || !senderMergeState) {
    return;
  }

  const { draft, fieldOptions } = senderMergeState;
  const rootNode = document.createElement('div');
  rootNode.className = 'tree-node tree-folder';

  const rootRow = document.createElement('div');
  rootRow.className = 'tree-row';

  const rootDot = document.createElement('span');
  rootDot.className = 'tree-dot';
  rootRow.appendChild(rootDot);

  const rootBody = document.createElement('div');
  rootBody.className = 'tree-body folder-body';

  const rootIcon = document.createElement('span');
  rootIcon.className = 'sender-card-icon';
  rootIcon.setAttribute('aria-hidden', 'true');
  rootBody.appendChild(rootIcon);

  const senderFields = document.createElement('div');
  senderFields.className = 'sender-fields';

  senderFields.appendChild(createSenderMergeField('Namn', 'name', fieldOptions.name, draft, (value) => {
    senderMergeState.draft.name = value;
  }));
  senderFields.appendChild(createSenderMergeField('Org.nr', 'orgNumber', fieldOptions.orgNumber, draft, (value) => {
    senderMergeState.draft.orgNumber = value;
  }));
  senderFields.appendChild(createSenderMergeField('Domän', 'domain', fieldOptions.domain, draft, (value) => {
    senderMergeState.draft.domain = value;
  }));

  const notesInput = document.createElement('textarea');
  notesInput.placeholder = 'Anteckningar';
  notesInput.value = draft.notes;
  notesInput.addEventListener('input', () => {
    senderMergeState.draft.notes = notesInput.value;
  });
  senderFields.appendChild(createFloatingField('Anteckningar', notesInput, 'sender-notes-field'));
  rootBody.appendChild(senderFields);

  const paymentList = document.createElement('div');
  paymentList.className = 'tree-children';

  const paymentsLabel = document.createElement('div');
  paymentsLabel.className = 'archive-level-label';
  paymentsLabel.textContent = 'Betalnummer';
  paymentList.appendChild(paymentsLabel);

  draft.paymentNumbers.forEach((payment, paymentIndex) => {
    const paymentNode = document.createElement('div');
    paymentNode.className = 'tree-node tree-category has-parent';

    const paymentRow = document.createElement('div');
    paymentRow.className = 'tree-row';

    const paymentDot = document.createElement('span');
    paymentDot.className = 'tree-dot sender-payment-dot';
    paymentRow.appendChild(paymentDot);

    const paymentBody = document.createElement('div');
    paymentBody.className = 'tree-body category-body';

    const paymentIcon = document.createElement('span');
    paymentIcon.className = 'sender-payment-icon';
    paymentIcon.setAttribute('aria-hidden', 'true');
    paymentBody.appendChild(paymentIcon);

    const paymentFields = document.createElement('div');
    paymentFields.className = 'sender-payment-fields';

    const typeSelect = document.createElement('select');
    [
      ['bankgiro', 'Bankgiro'],
      ['plusgiro', 'Plusgiro']
    ].forEach(([value, label]) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = label;
      typeSelect.appendChild(option);
    });
    typeSelect.value = payment.type;
    typeSelect.addEventListener('change', () => {
      senderMergeState.draft.paymentNumbers[paymentIndex].type = typeSelect.value === 'plusgiro' ? 'plusgiro' : 'bankgiro';
      numberInput.value = formatSenderPaymentNumberForDisplay(
        senderMergeState.draft.paymentNumbers[paymentIndex].type,
        numberInput.value
      );
      senderMergeState.draft.paymentNumbers[paymentIndex].number = numberInput.value;
    });

    const numberInput = document.createElement('input');
    numberInput.type = 'text';
    numberInput.value = formatSenderPaymentNumberForDisplay(payment.type, payment.number);
    numberInput.addEventListener('input', () => {
      senderMergeState.draft.paymentNumbers[paymentIndex].number = numberInput.value;
    });
    numberInput.addEventListener('blur', () => {
      const formatted = formatSenderPaymentNumberForDisplay(typeSelect.value, numberInput.value);
      numberInput.value = formatted;
      senderMergeState.draft.paymentNumbers[paymentIndex].number = formatted;
    });

    const removePaymentButton = document.createElement('button');
    removePaymentButton.type = 'button';
    removePaymentButton.className = 'category-remove';
    removePaymentButton.textContent = 'Ta bort';
    removePaymentButton.addEventListener('click', () => {
      senderMergeState.draft.paymentNumbers.splice(paymentIndex, 1);
      renderSenderMergeEditor();
    });

    paymentFields.appendChild(createFloatingField('Typ', typeSelect));
    paymentFields.appendChild(createFloatingField('Nummer', numberInput));
    paymentFields.appendChild(removePaymentButton);
    paymentBody.appendChild(paymentFields);
    paymentRow.appendChild(paymentBody);
    paymentNode.appendChild(paymentRow);
    paymentList.appendChild(paymentNode);
  });

  rootBody.appendChild(paymentList);

  const mergeActions = document.createElement('div');
  mergeActions.className = 'folder-actions';
  const addPaymentButton = document.createElement('button');
  addPaymentButton.type = 'button';
  addPaymentButton.textContent = 'Lägg till betalnummer';
  addPaymentButton.addEventListener('click', () => {
    senderMergeState.draft.paymentNumbers.push(defaultSenderPaymentDraft());
    renderSenderMergeEditor();
  });
  mergeActions.appendChild(addPaymentButton);
  rootBody.appendChild(mergeActions);

  rootRow.appendChild(rootBody);
  rootNode.appendChild(rootRow);
  senderMergeEditorEl.replaceChildren(rootNode);
}

function openSenderMergeOverlay() {
  senderMergeState = buildSenderMergeState();
  if (!senderMergeState || !senderMergeOverlayEl) {
    return;
  }
  renderSenderMergeEditor();
  senderMergeOverlayEl.classList.remove('hidden');
}

async function applySenderMerge() {
  if (!senderMergeState) {
    return;
  }

  const previousSendersDraft = sendersDraft.map(sanitizeSenderDraft);
  const previousSelectedSenderUiKeys = new Set(selectedSenderUiKeys);
  const previousCollapsedSenderUiKeys = new Set(collapsedSenderUiKeys);
  const previousMergeState = JSON.parse(JSON.stringify(senderMergeState));
  const mergedDraft = sanitizeSenderDraft(senderMergeState.draft);
  const sourceUiKeys = new Set(senderMergeState.sourceUiKeys);
  const nextSendersDraft = [];
  let insertedMergedRow = false;

  sendersDraft.forEach((row) => {
    const rowUiKey = senderUiKey(row);
    if (!sourceUiKeys.has(rowUiKey)) {
      nextSendersDraft.push(row);
      return;
    }
    if (!insertedMergedRow && rowUiKey === senderMergeState.baseUiKey) {
      nextSendersDraft.push(mergedDraft);
      insertedMergedRow = true;
    }
  });

  if (!insertedMergedRow) {
    nextSendersDraft.push(mergedDraft);
  }

  sendersDraft = nextSendersDraft.map(sanitizeSenderDraft);
  senderMergeState.sourceUiKeys.forEach((uiKey) => {
    selectedSenderUiKeys.delete(uiKey);
    collapsedSenderUiKeys.delete(uiKey);
  });
  selectedSenderUiKeys.add(senderUiKey(mergedDraft));
  renderSendersEditor();
  updateSettingsActionButtons();
  if (senderMergeApplyEl) {
    senderMergeApplyEl.disabled = true;
  }
  if (senderMergeCancelEl) {
    senderMergeCancelEl.disabled = true;
  }

  try {
    await saveSendersSettings();
  } catch (error) {
    sendersDraft = previousSendersDraft.map(sanitizeSenderDraft);
    selectedSenderUiKeys.clear();
    previousSelectedSenderUiKeys.forEach((uiKey) => selectedSenderUiKeys.add(uiKey));
    collapsedSenderUiKeys.clear();
    previousCollapsedSenderUiKeys.forEach((uiKey) => collapsedSenderUiKeys.add(uiKey));
    senderMergeState = previousMergeState;
    renderSendersEditor();
    renderSenderMergeEditor();
    updateSettingsActionButtons();
    throw error;
  } finally {
    if (senderMergeApplyEl) {
      senderMergeApplyEl.disabled = false;
    }
    if (senderMergeCancelEl) {
      senderMergeCancelEl.disabled = false;
    }
  }
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
  if (!archiveViewCategoriesEl || !archiveViewSystemEl || !Array.isArray(archiveTabEls)) {
    return;
  }
  activeArchiveTabId = tabId === 'system' ? 'system' : 'categories';
  archiveTabEls.forEach((button) => {
    const isActive = button.dataset.archiveTab === activeArchiveTabId;
    button.classList.toggle('active', isActive);
  });

  archiveViewCategoriesEl.classList.toggle('hidden', activeArchiveTabId !== 'categories');
  archiveViewSystemEl.classList.toggle('hidden', activeArchiveTabId !== 'system');
}

function renderOcrProcessingCommand() {
  if (!ocrSkipExistingTextEl || !ocrOptimizeLevelEl || !ocrTextExtractionMethodEl || !ocrProcessingCommandEl) {
    return;
  }
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
  if (!jbig2RefreshButtonEl) {
    return;
  }
  jbig2RefreshButtonEl.disabled = true;
  jbig2RefreshButtonEl.classList.add('is-spinning');
}

function stopJbig2RefreshSpin(hideAfterStop) {
  if (!jbig2RefreshButtonEl) {
    return;
  }
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
  if (!jbig2StatusBadgeEl || !jbig2StatusBadgeWrapEl || !jbig2RefreshButtonEl || !jbig2InstallCommandEl) {
    return;
  }
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

async function loadSendersSettings() {
  const response = await fetch('/api/get-senders.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda avsändare');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.senders)) {
    throw new Error('Ogiltigt svar för avsändare');
  }

  sendersDraft = payload.senders.map(sanitizeSenderDraft);
  sendersBaselineJson = normalizedSendersJson(sendersDraft);
  clearSenderSelections();
  closeSenderMergeOverlay();
  renderSendersEditor();
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

async function saveSendersSettings() {
  const normalized = sendersDraft.map(sanitizeSenderDraft);
  const response = await fetch('/api/save-senders.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ senders: normalized })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.senders)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara avsändare';
    throw new Error(message);
  }

  sendersDraft = payload.senders.map(sanitizeSenderDraft);
  sendersBaselineJson = normalizedSendersJson(sendersDraft);
  clearSenderSelections();
  closeSenderMergeOverlay();
  renderSendersEditor();
  updateSettingsActionButtons();
  await fetchState({ refreshSenders: true });
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

function cloneCurrentStateForRollback() {
  return {
    processingJobs: Array.isArray(state.processingJobs) ? state.processingJobs.map((job) => ({ ...job })) : [],
    readyJobs: Array.isArray(state.readyJobs) ? state.readyJobs.map((job) => ({ ...job })) : [],
    failedJobs: Array.isArray(state.failedJobs) ? state.failedJobs.map((job) => ({ ...job })) : [],
    clients: state.clients,
    senders: state.senders,
    categories: state.categories
  };
}

function applyOptimisticReprocess(jobId) {
  const sourceJob = findJobById(jobId);
  if (!sourceJob) {
    return false;
  }

  pinnedProcessingJobIds.add(jobId);

  const processingJob = {
    ...sourceJob,
    status: 'processing',
    error: null
  };

  applyState({
    processingJobs: [
      processingJob,
      ...state.processingJobs.filter((job) => job && job.id !== jobId)
    ],
    readyJobs: state.readyJobs.filter((job) => job && job.id !== jobId),
    failedJobs: state.failedJobs.filter((job) => job && job.id !== jobId)
  });

  if (selectedJobId === jobId) {
    loadedOcrJobId = '';
    loadedMatchesJobId = '';
    loadedMetaJobId = '';
    clearPdfFrames();
  }

  return true;
}

async function reprocessSingleJob(jobId, mode) {
  const rollbackState = cloneCurrentStateForRollback();
  applyOptimisticReprocess(jobId);
  try {
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
    }

    await fetchState();
  } catch (error) {
    applyState(rollbackState);
    throw error;
  }
}

async function handleSelectedJobReprocess(mode) {
  if (!selectedJobId) {
    return;
  }

  try {
    await reprocessSingleJob(selectedJobId, mode);
  } catch (error) {
    await fetchState();
    alert(error.message || 'Kunde inte köra om jobbet.');
  }
}

viewModeEl.addEventListener('change', () => {
  setViewMode(viewModeEl.value);
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
  if (senderSelectEl.value === EDIT_SENDERS_OPTION_VALUE) {
    const selectedJob = findJobById(selectedJobId);
    openSendersSettingsDirect().finally(() => {
      setSenderForJob(selectedJob);
    });
    return;
  }

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

settingsButtonEl.addEventListener('click', async () => {
  await openClientsSettingsDirect();
});

settingsTabEls.forEach((tabButton) => {
  tabButton.addEventListener('click', async () => {
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
    openSettingsModal();
    setSettingsTab(tabId);
    try {
      await ensureSettingsPanelReady(tabId);
    } catch (error) {
      if (tabId === 'clients') {
        alert('Kunde inte ladda huvudmän.');
        clientsBaselineText = clientsTextareaEl ? clientsTextareaEl.value : '';
      } else if (tabId === 'senders') {
        alert('Kunde inte ladda avsändare.');
        sendersDraft = [];
        sendersBaselineJson = normalizedSendersJson(sendersDraft);
        renderSendersEditor();
      } else if (tabId === 'matching') {
        alert('Kunde inte ladda matchningsinställningar.');
        matchingDraft = [defaultReplacement()];
        matchingInvoiceFieldMinConfidenceDraft = 0.7;
        if (matchingInvoiceThresholdEl) {
          matchingInvoiceThresholdEl.value = String(matchingInvoiceFieldMinConfidenceDraft);
        }
        matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingInvoiceFieldMinConfidenceDraft);
        renderMatchingEditor();
      } else if (tabId === 'ocr-processing') {
        alert('Kunde inte ladda OCR-inställningar.');
        ocrSkipExistingTextBaseline = true;
        ocrOptimizeLevelBaseline = 1;
        ocrTextExtractionMethodBaseline = 'layout';
        if (ocrSkipExistingTextEl) {
          ocrSkipExistingTextEl.checked = true;
        }
        if (ocrOptimizeLevelEl) {
          ocrOptimizeLevelEl.value = '1';
        }
        if (ocrTextExtractionMethodEl) {
          ocrTextExtractionMethodEl.value = 'layout';
        }
        ocrPdfSubstitutionsDraft = [defaultReplacement()];
        ocrPdfSubstitutionsBaselineJson = normalizedOcrPdfSubstitutionsJson(ocrPdfSubstitutionsDraft);
        renderOcrPdfSubstitutionsEditor();
        renderJbig2Status(null);
        renderOcrProcessingCommand();
      } else if (tabId === 'categories') {
        alert('Kunde inte ladda arkivstruktur.');
        categoriesDraft = [];
        systemCategoriesDraft = createDefaultSystemCategories();
        categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
        renderCategoriesEditor();
        renderSystemCategoryEditor();
      } else if (tabId === 'paths') {
        alert('Kunde inte ladda sökvägsinställningar.');
        pathsBaselineValue = normalizedPathValue(outputBasePathEl ? outputBasePathEl.value : '');
      }
      updateSettingsActionButtons();
    }
  });
});

settingsCloseEl.addEventListener('click', () => {
  closeSettingsModal();
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

    stateUpdateTransport = sanitizeStateUpdateTransport(
      nextState.stateUpdateTransport,
      stateUpdateTransport
    );

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
    if (options.syncTransport !== false) {
      syncStateUpdateTransport();
    }
  }
}

function stopStateStream() {
  if (!stateStream) {
    return;
  }

  stateStream.close();
  stateStream = null;
}

function scheduleStatePoll(delay = 1500) {
  if (statePollTimer !== null) {
    window.clearTimeout(statePollTimer);
  }

  statePollTimer = window.setTimeout(() => {
    statePollTimer = null;
    fetchState();
  }, delay);
}

function startStateStream() {
  if (stateStream) {
    return;
  }

  const stream = new EventSource('/api/stream-state.php');
  stream.addEventListener('state', (event) => {
    if (!event || typeof event.data !== 'string' || event.data === '') {
      return;
    }

    try {
      const nextState = JSON.parse(event.data);
      if (
        !nextState
        || !Array.isArray(nextState.processingJobs)
        || !Array.isArray(nextState.readyJobs)
        || !Array.isArray(nextState.failedJobs)
      ) {
        return;
      }

      applyState({
        processingJobs: nextState.processingJobs,
        readyJobs: nextState.readyJobs,
        failedJobs: nextState.failedJobs
      });
    } catch (error) {
      // Ignore malformed stream payloads and wait for next event.
    }
  });

  stream.addEventListener('error', () => {
    if (stateStream !== stream) {
      return;
    }
  });

  stateStream = stream;
}

function syncStateUpdateTransport() {
  if (stateUpdateTransport === 'sse') {
    if (statePollTimer !== null) {
      window.clearTimeout(statePollTimer);
      statePollTimer = null;
    }
    startStateStream();
    return;
  }

  stopStateStream();
  scheduleStatePoll();
}

updateSettingsActionButtons();
renderJbig2Status(null);
renderOcrProcessingCommand();
applyHashState();
window.addEventListener('hashchange', () => {
  applyHashState();
});
fetchState().finally(() => {
  syncStateUpdateTransport();
});
