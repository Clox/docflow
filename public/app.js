const jobListEl = document.getElementById('job-list');
const pdfStackEl = document.getElementById('pdf-stack');
const pdfFrameEls = Array.from(document.querySelectorAll('.pdf-frame'));
const ocrViewEl = document.getElementById('ocr-view');
const ocrHighlightViewEl = document.getElementById('ocr-highlight-view');
const ocrPagesViewEl = document.getElementById('ocr-pages-view');
const ocrSourceTabsEl = document.getElementById('ocr-source-tabs');
const ocrSourceTabEls = Array.from(document.querySelectorAll('[data-ocr-source]'));
const ocrPageControlsEl = document.getElementById('ocr-page-controls');
const ocrPageCurrentEl = document.getElementById('ocr-page-current');
const ocrPageTotalEl = document.getElementById('ocr-page-total');
const ocrZoomOutEl = document.getElementById('ocr-zoom-out');
const ocrZoomInputEl = document.getElementById('ocr-zoom-input');
const ocrZoomInEl = document.getElementById('ocr-zoom-in');
const ocrPageImageControlsEl = document.getElementById('ocr-page-image-controls');
const ocrPageImageToggleEl = document.getElementById('ocr-page-image-toggle');
const ocrShowPageImageEl = document.getElementById('ocr-show-page-image');
const ocrPageImageOpacityEl = document.getElementById('ocr-page-image-opacity');
const matchesViewEl = document.getElementById('matches-view');
const metaViewEl = document.getElementById('meta-view');
const viewModeEl = document.getElementById('view-mode');
const reviewViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="review"]') : null;
const ocrSearchBarEl = document.getElementById('ocr-search-bar');
const ocrSearchInputEl = document.getElementById('ocr-search-input');
const ocrSearchRegexEl = document.getElementById('ocr-search-regex');
const ocrSearchPrevEl = document.getElementById('ocr-search-prev');
const ocrSearchNextEl = document.getElementById('ocr-search-next');
const ocrSearchStatusEl = document.getElementById('ocr-search-status');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const jobListModeEl = document.getElementById('job-list-mode');
const clientSelectEl = document.getElementById('client-select');
const senderSelectEl = document.getElementById('sender-select');
const categorySelectEl = document.getElementById('category-select');
const filenameInputEl = document.getElementById('filename-input');
const archiveActionEl = document.getElementById('archive-action');
const appNoticesEl = document.getElementById('app-notices');
const archivedReviewPanelEl = document.getElementById('archived-review-panel');
const settingsButtonEl = document.getElementById('settings-button');
const settingsModalEl = document.getElementById('settings-modal');
const settingsTabEls = Array.from(document.querySelectorAll('[data-settings-tab]'));
const archivingReviewSettingsTabEl = document.querySelector('[data-settings-tab="archiving-review"]');
const settingsPanelActionsHostEl = document.getElementById('settings-panel-actions-host');
const settingsCloseEl = document.getElementById('settings-close');
const selectedJobPanelEl = document.getElementById('selected-job-panel');
const selectedJobNameEl = document.getElementById('selected-job-name');
const selectedJobMetaEl = document.getElementById('selected-job-meta');
const selectedJobReprocessEl = document.getElementById('selected-job-reprocess');
const settingsPanelTemplateIds = {
  clients: 'settings-template-clients',
  senders: 'settings-template-senders',
  matching: 'settings-template-matching',
  'ocr-processing': 'settings-template-ocr-processing',
  categories: 'settings-template-categories',
  labels: 'settings-template-labels',
  'data-fields': 'settings-template-data-fields',
  'archiving-review': 'settings-template-archiving-review',
  jobs: 'settings-template-jobs',
  paths: 'settings-template-paths',
  system: 'settings-template-system'
};
let clientsListEl = null;
let clientsAddRowEl = null;
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
let ocrSkipExistingTextEl = null;
let ocrOptimizeLevelEl = null;
let ocrTextExtractionMethodEl = null;
let ocrPdfSubstitutionsListEl = null;
let ocrPdfSubstitutionsAddRowEl = null;
let ocrProcessingCommandEl = null;
let ocrTextExtractionCommandEl = null;
let jbig2StatusBadgeWrapEl = null;
let jbig2StatusBadgeEl = null;
let jbig2InstallCommandEl = null;
let jbig2RefreshButtonEl = null;
let jbig2LocalInstallButtonEl = null;
let pythonStatusCardEl = null;
let pythonStatusBadgeWrapEl = null;
let pythonStatusBadgeEl = null;
let pythonInstallCommandEl = null;
let pythonRefreshButtonEl = null;
let pythonLocalInstallButtonEl = null;
let rapidocrStatusBadgeWrapEl = null;
let rapidocrStatusBadgeEl = null;
let rapidocrInstallCommandEl = null;
let rapidocrRefreshButtonEl = null;
let rapidocrInstallLogButtonEl = null;
let rapidocrLocalInstallButtonEl = null;
let ocrProcessingCancelEl = null;
let ocrProcessingApplyEl = null;
let categoriesListEl = null;
let categoriesAddCategoryEl = null;
let categoriesCancelEl = null;
let categoriesApplyEl = null;
let labelsListEl = null;
let systemLabelEditorEl = null;
let labelsAddRowEl = null;
let labelsCancelEl = null;
let labelsApplyEl = null;
let extractionFieldsEditorEl = null;
let systemExtractionFieldsEditorEl = null;
let extractionFieldsAddRowEl = null;
let extractionFieldsCancelEl = null;
let extractionFieldsApplyEl = null;
let extractionFieldsTabEls = [];
let extractionFieldsViewCustomEl = null;
let extractionFieldsViewSystemEl = null;
let archivingReviewStatusEl = null;
let archivingReviewSummaryEl = null;
let archivingReviewJobsEl = null;
let archivingReviewResetDraftEl = null;
let archivingReviewPublishEl = null;
let labelsTabEls = [];
let labelsViewCustomEl = null;
let labelsViewSystemEl = null;
let settingsResetJobsEl = null;
let systemStateTransportEl = null;
let outputBasePathEl = null;
let pathsCancelEl = null;
let pathsApplyEl = null;

let state = {
  processingJobs: [],
  readyJobs: [],
  archivedJobs: [],
  failedJobs: [],
  clients: [],
  senders: [],
  categories: [],
  archivingRules: {
    activeVersion: 1,
    hasUnpublishedChanges: false,
    needsRuleReviewCount: 0,
    publishedReview: {
      status: 'idle',
      analyzedCount: 0,
      totalCount: 0
    },
    draftReview: {
      activeArchivingRulesVersion: 1,
      hasUnpublishedChanges: false,
      changedSections: [],
      summary: {
        testedJobs: 0,
        unchanged: 0,
        improvements: 0,
        risks: 0,
        info: 0
      },
      jobs: [],
      session: {
        status: 'idle',
        analyzedCount: 0,
        totalCount: 0,
        foundCount: 0,
        remainingCount: 0
      },
      signature: ''
    },
    signature: ''
  }
};

const SYSTEM_LABELS = {
  invoice: {
    name: 'Faktura',
    minScore: 15,
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
      { text: 'e-faktura', score: 4 },
      { text: 'betalningsmottagare', score: 2 }
    ]
  },
  autogiro: {
    name: 'Autogiro',
    minScore: 3,
    rules: [
      { text: 'autogiro', score: 3 }
    ]
  }
};

const DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR = ', ';

const FILENAME_TEMPLATE_LEGACY_FIELDS = {
  date: {
    label: 'Datum',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält. Motsvarar tidigare datumfält i filnamnsmallen.',
  },
  category: {
    label: 'Kategori',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för kategori.',
  },
  supplier: {
    label: 'Leverantör',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för datafältet Leverantör.',
  },
  payment_receiver: {
    label: 'Betalningsmottagare',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för datafältet Betalningsmottagare.',
  },
  amount: {
    label: 'Belopp',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för datafältet Belopp.',
  },
  ocr: {
    label: 'OCR',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för datafältet OCR.',
  },
  client: {
    label: 'Huvudman',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för Huvudman.',
  },
  main_client: {
    label: 'Huvudman',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för Huvudman.',
  },
  sender: {
    label: 'Avsändare',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för Avsändare.',
  },
  payee: {
    label: 'Betalningsmottagare',
    tone: 'legacy',
    title: 'Äldre filnamnsmall-fält för Betalningsmottagare.',
  },
};

let selectedJobId = '';
let loadedOcrJobId = '';
let loadedOcrSource = '';
let loadedMatchesJobId = '';
let loadedMetaJobId = '';
let pdfFrameJobIds = pdfFrameEls.map(() => '');
let pollInFlight = false;
let stateStream = null;
let statePollTimer = null;
let stateUpdateTransport = 'polling';
let stateEventCursor = 0;
let currentViewMode = 'pdf';
let currentOcrSource = 'merged';
let currentOcrZoom = 100;
let currentOcrDocumentMode = 'text';
let ocrShowPageImage = false;
let ocrPageImageBlend = 0.5;
let ocrRequestSeq = 0;
let ocrSearchMatches = [];
let ocrSearchActiveIndex = -1;
let ocrDocumentPages = [];
let ocrRenderedPages = [];
let matchesRequestSeq = 0;
let metaRequestSeq = 0;
let preferredJobIdFromHash = '';
let categoriesDraft = [];
let labelsDraft = [];
let systemLabelsDraft = createDefaultSystemLabels();
let labelsBuiltInCollapsed = true;
let labelsCustomCollapsed = false;
let sendersDraft = [];
let matchingDraft = [];
let ocrSkipExistingTextBaseline = true;
let ocrOptimizeLevelBaseline = 1;
let ocrTextExtractionMethodBaseline = 'layout';
let ocrPdfSubstitutionsDraft = [];
let ocrPdfSubstitutionsBaselineJson = JSON.stringify([]);
let rapidocrInstallPollTimer = null;
let activeSettingsTabId = 'clients';
let activeSettingsFooterPanelId = '';
let activeLabelsTabId = 'labels';
let clientsDraft = [];
let clientsBaselineJson = '[]';
let clientDraftUiKeySeq = 1;
let sendersBaselineJson = '[]';
let sendersSortOrder = 'name';
let senderDraftUiKeySeq = 1;
let matchingBaselineJson = JSON.stringify({
  replacements: []
});
let pathsBaselineValue = '';
let categoriesBaselineJson = JSON.stringify([]);
let labelsBaselineJson = JSON.stringify({
  labels: [],
  systemLabels: systemLabelsDraft,
});
let extractionFieldsBaselineJson = JSON.stringify({
  fields: [],
  predefinedFields: [],
  systemFields: [],
});
let clientOptionsSignature = '';
let senderOptionsSignature = '';
let categoryOptionsSignature = '';
let hasLoadedClients = false;
let hasLoadedLabels = false;
const OCR_ZOOM_STEPS = [25, 33, 50, 67, 75, 80, 90, 100, 110, 125, 150, 175, 200, 250, 300, 400, 500];
const OCR_OBJECT_TEXT_FIT_X_SCALE_BY_SOURCE = {
  tesseract: 1.03,
  rapidocr: .98,
  merged: .98,
};
const OCR_OBJECT_TEXT_FIT_Y_SCALE_BY_SOURCE = {
  tesseract: 1.6,
  rapidocr: 1.00,
  merged: 1.00,
};
const DEBUG_FILENAME_TEMPLATE_NAV = false;
let hasLoadedSenders = false;
let hasLoadedCategories = false;
let hasLoadedInitialJobsState = false;
let selectedJobStateSig = '';
const selectedClientByJobId = new Map();
const selectedSenderByJobId = new Map();
const selectedCategoryByJobId = new Map();
const archivedReviewDraftByJobId = new Map();
const filenameByJobId = new Map();
const filenameSaveTimerByJobId = new Map();
const lastKnownJobDisplayById = new Map();
const pinnedProcessingJobIds = new Set();
const pinnedProcessingOrderById = new Map();
const ocrPageImageCache = new Map();
const jobListNodeByKey = new Map();
const seenFailedJobKeys = new Set();
const ocrViewContentBySource = new Map();
const ocrViewStateBySource = new Map();
const collapsedSenderUiKeys = new Set();
const selectedSenderUiKeys = new Set();
const mountedSettingsPanels = new Set();
const boundSettingsPanels = new Set();
const loadedSettingsPanels = new Set();
const EDIT_CLIENTS_OPTION_VALUE = '__edit_clients__';
const EDIT_SENDERS_OPTION_VALUE = '__edit_senders__';
const EDIT_CATEGORIES_OPTION_VALUE = '__edit_categories__';
const VALID_VIEW_MODES = new Set(['pdf', 'ocr', 'matches', 'meta', 'review']);
const VALID_JOB_LIST_MODES = new Set(['all', 'ready', 'processing', 'archived']);

let senderMergeState = null;
let currentJobListMode = 'ready';
let archivingRulesReviewPayload = null;
let archivingRulesReviewPayloadSignature = '';
let archivedJobReviewPayload = null;
let archivedReviewRequestSeq = 0;
let renderedArchivingReviewSignature = '';
let extractionFieldsDraft = [];
let predefinedExtractionFieldsDraft = [];
let systemExtractionFieldsDraft = [];
let extractionFieldsBuiltInCollapsed = true;
let extractionFieldsCustomCollapsed = false;
let activeExtractionFieldsTabId = 'fields';

jobListModeEl.value = currentJobListMode;

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
  clientSelectEl.disabled = !job || job.status !== 'ready' || job.archived === true;

  if (!job) {
    clientSelectEl.value = '';
    return;
  }

  const resolvedValue = effectiveClientDirName(job);
  if (resolvedValue) {
    const hasManualOption = Array.from(clientSelectEl.options).some(
      (option) => option.value === resolvedValue
    );
    if (hasManualOption) {
      clientSelectEl.value = resolvedValue;
      return;
    }
  }
  clientSelectEl.value = '';
}

function setSenderForJob(job) {
  senderSelectEl.disabled = !job || job.status !== 'ready' || job.archived === true;

  if (!job) {
    senderSelectEl.value = '';
    return;
  }

  const resolvedValue = effectiveSenderId(job);
  if (resolvedValue) {
    const hasManualOption = Array.from(senderSelectEl.options).some(
      (option) => option.value === resolvedValue
    );
    if (hasManualOption) {
      senderSelectEl.value = resolvedValue;
      return;
    }
  }
  senderSelectEl.value = '';
}

function setCategoryForJob(job) {
  categorySelectEl.disabled = !job || job.status !== 'ready' || job.archived === true;

  if (!job) {
    categorySelectEl.value = '';
    return;
  }

  const resolvedValue = effectiveCategoryId(job);
  if (resolvedValue) {
    const hasManualOption = Array.from(categorySelectEl.options).some(
      (option) => option.value === resolvedValue
    );
    if (hasManualOption) {
      categorySelectEl.value = resolvedValue;
      return;
    }
  }
  categorySelectEl.value = '';
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

  const visibleJobs = displayedJobsForCurrentListMode();
  const selectedIndex = visibleJobs.findIndex((job) => job.id === jobId);
  if (selectedIndex < 0) {
    setPdfFrameJob(0, jobId);
    setPdfFrameJob(1, '');
    setPdfFrameJob(2, '');
    pdfFrameEls.forEach((frameEl, frameIndex) => {
      frameEl.classList.toggle('active', frameIndex === 0);
    });
    return;
  }

  const prevId = selectedIndex > 0 ? visibleJobs[selectedIndex - 1].id : '';
  const currId = visibleJobs[selectedIndex].id;
  const nextId = selectedIndex < visibleJobs.length - 1 ? visibleJobs[selectedIndex + 1].id : '';
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

function draftAffectedArchivedJobIds() {
  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || state.archivingRules?.draftReview);
  return new Set(
    (Array.isArray(payload.jobs) ? payload.jobs : [])
      .map((item) => item && typeof item.jobId === 'string' ? item.jobId : '')
      .filter((jobId) => jobId !== '')
  );
}

function jobHasDraftReviewChange(jobId) {
  return typeof jobId === 'string' && jobId !== '' && draftAffectedArchivedJobIds().has(jobId);
}

function jobSupportsArchivedReview(job) {
  return !!(job && job.archived === true && ((job.needsRuleReview === true) || jobHasDraftReviewChange(job.id)));
}

function jobsNeedingRuleReview() {
  return (Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
    .filter((job) => job && job.archived === true && job.needsRuleReview === true);
}

function displayedArchivedReviewJobsForReadyMode() {
  const draftJobIds = draftAffectedArchivedJobIds();
  const jobsById = new Map(
    (Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
      .filter((job) => job && typeof job.id === 'string' && job.id !== '')
      .map((job) => [job.id, job])
  );
  const orderedIds = [];
  jobsNeedingRuleReview().forEach((job) => {
    if (job && typeof job.id === 'string' && job.id !== '') {
      orderedIds.push(job.id);
    }
  });
  Array.from(draftJobIds).forEach((jobId) => {
    if (!orderedIds.includes(jobId)) {
      orderedIds.push(jobId);
    }
  });
  return orderedIds
    .map((jobId) => jobsById.get(jobId) || null)
    .filter((job) => !!job);
}

function syncReviewViewModeAvailability(job, options = {}) {
  const allowFallback = options.allowFallback !== false;
  const available = jobSupportsArchivedReview(job);

  if (reviewViewOptionEl instanceof HTMLOptionElement) {
    reviewViewOptionEl.hidden = !available;
    reviewViewOptionEl.disabled = !available;
  }

  if (!available && currentViewMode === 'review' && allowFallback) {
    setViewMode('pdf');
    return false;
  }

  if (available && currentViewMode === 'review') {
    viewModeEl.value = 'review';
  }

  return available;
}

function setViewerJob(jobId) {
  if (currentViewMode === 'review') {
    setViewerReview(jobId);
  } else if (currentViewMode === 'ocr') {
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
  if (currentViewMode === 'ocr' && nextMode !== 'ocr') {
    saveCurrentOcrViewState();
  }
  currentViewMode = nextMode;
  viewModeEl.value = nextMode;
  loadedOcrJobId = '';
  loadedOcrSource = '';
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
  setOcrSourceTabsVisible(false);
  setOcrControlsVisible(false);
  setOcrPageImageToggleVisible(false);
  archivedReviewPanelEl.classList.add('hidden');
  ocrPagesViewEl.classList.add('hidden');
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  syncOcrHighlightPresentation();
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  pdfStackEl.classList.remove('hidden');
  updatePdfFrameWindow(jobId);
}

async function setViewerOcr(jobId) {
  if (
    loadedOcrJobId !== ''
    && (
      loadedOcrJobId !== jobId
      || loadedOcrSource !== currentOcrSource
    )
  ) {
    saveCurrentOcrViewState();
  }

  setOcrSearchVisible(true);
  setOcrSourceTabsVisible(true);
  setOcrControlsVisible(true);
  setOcrPageImageToggleVisible(true);
  archivedReviewPanelEl.classList.add('hidden');
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  pdfStackEl.classList.add('hidden');
  ocrPagesViewEl.classList.remove('hidden');
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  syncOcrHighlightPresentation();

  if (!jobId) {
    loadedOcrJobId = '';
    loadedOcrSource = '';
    setOcrDocumentText('');
    refreshOcrSearch();
    return;
  }

  const jobChanged = loadedOcrJobId !== '' && loadedOcrJobId !== jobId;
  if (jobChanged) {
    clearOcrViewCache();
  }

  if (loadedOcrJobId === jobId && loadedOcrSource === currentOcrSource && ocrDocumentPages.length > 0) {
    refreshOcrSearch();
    restoreOcrViewState(currentOcrSource);
    return;
  }

  const cachedContent = getCachedOcrViewContent(currentOcrSource);
  if (cachedContent && typeof cachedContent === 'object') {
    loadedOcrJobId = jobId;
    loadedOcrSource = currentOcrSource;
    setOcrDocumentPages(cachedContent.pages, cachedContent.text || '', cachedContent.mode || 'text');
    refreshOcrSearch();
    restoreOcrViewState(currentOcrSource);
    return;
  }

  loadedOcrJobId = jobId;
  loadedOcrSource = currentOcrSource;
  const requestSeq = ++ocrRequestSeq;
  setOcrDocumentText(`Laddar ${ocrSourceDisplayName(currentOcrSource)}...`);
  refreshOcrSearch();

  try {
    const response = await fetch(
      '/api/get-job-ocr.php?id='
        + encodeURIComponent(jobId)
        + '&source='
        + encodeURIComponent(currentOcrSource),
      { cache: 'no-store' }
    );
    if (!response.ok) {
      throw new Error('Kunde inte hämta OCR-data');
    }

    const payload = await response.json();
    if (requestSeq !== ocrRequestSeq) {
      return;
    }

    const text = payload && typeof payload.text === 'string' ? payload.text : '';
    const pages = payload && Array.isArray(payload.pages) ? payload.pages : null;
    const mode = payload && typeof payload.mode === 'string' ? payload.mode : 'text';
    const resolvedText = text || `(Ingen ${ocrSourceDisplayName(currentOcrSource)} hittades)`;
    setCachedOcrViewContent(currentOcrSource, {
      text: resolvedText,
      pages,
      mode,
    });
    setOcrDocumentPages(pages, resolvedText, mode);
    refreshOcrSearch();
    restoreOcrViewState(currentOcrSource);
  } catch (error) {
    if (requestSeq !== ocrRequestSeq) {
      return;
    }
    const resolvedText = `Kunde inte ladda ${ocrSourceDisplayName(currentOcrSource)}.`;
    setCachedOcrViewContent(currentOcrSource, {
      text: resolvedText,
      pages: null,
      mode: 'text',
    });
    setOcrDocumentText(resolvedText);
    refreshOcrSearch();
    restoreOcrViewState(currentOcrSource);
  }
}

function appendMatchesSection(container, title, categories, emptyText, entityLabel = 'Kategori') {
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
  [entityLabel, 'Totalpoäng', 'Minpoäng', 'Regeltext', 'Matchad text', 'Regelpoäng'].forEach((label) => {
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
  const labels = payload && Array.isArray(payload.labels) ? payload.labels : [];

  appendMatchesSection(matchesViewEl, 'Kategorier', categories, 'Inga kategorimatchningar hittades.');
  appendMatchesSection(matchesViewEl, 'Etiketter', labels, 'Inga etikettmatchningar hittades.', 'Etikett');
}

async function setViewerMatches(jobId) {
  setOcrSearchVisible(false);
  setOcrSourceTabsVisible(false);
  setOcrControlsVisible(false);
  setOcrPageImageToggleVisible(false);
  archivedReviewPanelEl.classList.add('hidden');
  ocrPagesViewEl.classList.add('hidden');
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
  setOcrSourceTabsVisible(false);
  setOcrControlsVisible(false);
  setOcrPageImageToggleVisible(false);
  archivedReviewPanelEl.classList.add('hidden');
  ocrPagesViewEl.classList.add('hidden');
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

async function setViewerReview(jobId) {
  setOcrSearchVisible(false);
  setOcrSourceTabsVisible(false);
  setOcrControlsVisible(false);
  setOcrPageImageToggleVisible(false);
  ocrPagesViewEl.classList.add('hidden');
  pdfStackEl.classList.add('hidden');
  ocrHighlightViewEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
  matchesViewEl.classList.add('hidden');
  metaViewEl.classList.add('hidden');
  archivedReviewPanelEl.classList.remove('hidden');

  if (!jobId) {
    archivedJobReviewPayload = null;
    renderArchivedReviewPanel();
    return;
  }

  const selectedJob = findJobById(jobId);
  if (!jobSupportsArchivedReview(selectedJob)) {
    archivedJobReviewPayload = null;
    renderArchivedReviewPanel();
    return;
  }

  try {
    await loadArchivedReview(jobId);
  } catch (error) {
    renderArchivedReviewPanel();
  }
}

function setJobListMode(mode, options = {}) {
  const nextMode = VALID_JOB_LIST_MODES.has(mode) ? mode : 'ready';
  if (nextMode !== 'ready') {
    pinnedProcessingJobIds.clear();
  }
  currentJobListMode = nextMode;
  jobListModeEl.value = currentJobListMode;
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  if (options.refreshSelection !== false) {
    refreshSelection();
  }
}

async function openArchivingReviewSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('archiving-review');

  try {
    await ensureSettingsPanelReady('archiving-review', { reload: true });
  } catch (error) {
    alert('Kunde inte ladda regelgranskningen.');
    return false;
  }

  return true;
}

function renderAppNotices() {
  if (!(appNoticesEl instanceof HTMLElement)) {
    return;
  }

  appNoticesEl.replaceChildren();
  const notices = [];

  if (state.archivingRules && state.archivingRules.hasUnpublishedChanges === true) {
    const notice = document.createElement('div');
    notice.className = 'app-notice is-warning';
    const text = document.createElement('span');
    text.textContent = 'Det finns ett utkast till arkiveringsregler som ännu inte är aktiverat.';
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Granska ändringar';
    button.addEventListener('click', () => {
      openArchivingReviewSettingsDirect();
    });
    notice.append(text, button);
    notices.push(notice);
  }

  const needsRuleReviewCount = Array.isArray(state.archivedJobs)
    ? state.archivedJobs.filter((job) => job && job.needsRuleReview === true).length
    : (Number.parseInt(String(state.archivingRules?.needsRuleReviewCount || 0), 10) || 0);
  if (needsRuleReviewCount > 0) {
    const notice = document.createElement('div');
    notice.className = 'app-notice is-info';
    const text = document.createElement('span');
    text.textContent = `${needsRuleReviewCount} arkiverade jobb behöver ses över mot aktuella regler.`;
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Visa jobb';
    button.addEventListener('click', () => {
      const firstReviewJob = jobsNeedingRuleReview()[0] || null;
      setJobListMode('ready');
      if (firstReviewJob && typeof firstReviewJob.id === 'string') {
        applySelectedJobId(firstReviewJob.id);
        setViewMode('review');
      }
    });
    notice.append(text, button);
    notices.push(notice);
  }

  if (notices.length === 0) {
    appNoticesEl.classList.add('hidden');
    syncArchivingReviewTabIndicator();
    return;
  }

  appNoticesEl.classList.remove('hidden');
  appNoticesEl.replaceChildren(...notices);
  syncArchivingReviewTabIndicator();
}

function syncArchivingReviewTabIndicator() {
  if (!(archivingReviewSettingsTabEl instanceof HTMLElement)) {
    return;
  }
  const hasChanges = state.archivingRules && state.archivingRules.hasUnpublishedChanges === true;
  archivingReviewSettingsTabEl.classList.toggle('has-unpublished-changes', hasChanges);
  archivingReviewSettingsTabEl.title = hasChanges
    ? 'Det finns ett utkast till arkiveringsregler som ännu inte är aktiverat.'
    : '';
}

function deepCloneJson(value) {
  return JSON.parse(JSON.stringify(value));
}

function emptyArchivingReviewPayload() {
  return {
    activeArchivingRulesVersion: 1,
    hasUnpublishedChanges: false,
    changedSections: [],
    summary: {
      testedJobs: 0,
      unchanged: 0,
      improvements: 0,
      risks: 0,
      info: 0,
    },
    jobs: [],
    session: {
      status: 'idle',
      analyzedCount: 0,
      totalCount: 0,
      foundCount: 0,
      remainingCount: 0,
    },
    signature: '',
  };
}

function normalizeArchivingReviewPayload(payload) {
  const next = payload && typeof payload === 'object' ? payload : {};
  const summary = next.summary && typeof next.summary === 'object' ? next.summary : {};
  const session = next.session && typeof next.session === 'object' ? next.session : {};
  const normalized = {
    activeArchivingRulesVersion: Number.parseInt(String(next.activeArchivingRulesVersion || 1), 10) || 1,
    hasUnpublishedChanges: next.hasUnpublishedChanges === true,
    changedSections: Array.isArray(next.changedSections) ? next.changedSections.filter((value) => typeof value === 'string' && value.trim()) : [],
    summary: {
      testedJobs: Number.parseInt(String(summary.testedJobs || 0), 10) || 0,
      unchanged: Number.parseInt(String(summary.unchanged || 0), 10) || 0,
      improvements: Number.parseInt(String(summary.improvements || 0), 10) || 0,
      risks: Number.parseInt(String(summary.risks || 0), 10) || 0,
      info: Number.parseInt(String(summary.info || 0), 10) || 0,
    },
    jobs: Array.isArray(next.jobs) ? next.jobs : [],
    session: {
      status: typeof session.status === 'string' ? session.status : 'idle',
      analyzedCount: Number.parseInt(String(session.analyzedCount || 0), 10) || 0,
      totalCount: Number.parseInt(String(session.totalCount || 0), 10) || 0,
      foundCount: Number.parseInt(String(session.foundCount || 0), 10) || 0,
      remainingCount: Number.parseInt(String(session.remainingCount || 0), 10) || 0,
    },
    signature: typeof next.signature === 'string' ? next.signature : '',
  };

  if (!normalized.signature) {
    normalized.signature = JSON.stringify(normalized);
  }

  return normalized;
}

function normalizeArchivingRulesStatePayload(payload, fallback = state.archivingRules) {
  const next = payload && typeof payload === 'object' ? payload : {};
  return {
    activeVersion: Number.parseInt(String(next.activeVersion || fallback.activeVersion || 1), 10) || 1,
    hasUnpublishedChanges: next.hasUnpublishedChanges === true,
    needsRuleReviewCount: Number.parseInt(String(next.needsRuleReviewCount || 0), 10) || 0,
    publishedReview: next.publishedReview && typeof next.publishedReview === 'object'
      ? {
        status: typeof next.publishedReview.status === 'string' ? next.publishedReview.status : 'idle',
        analyzedCount: Number.parseInt(String(next.publishedReview.analyzedCount || 0), 10) || 0,
        totalCount: Number.parseInt(String(next.publishedReview.totalCount || 0), 10) || 0,
      }
      : fallback.publishedReview,
    draftReview: normalizeArchivingReviewPayload(next.draftReview),
    signature: typeof next.signature === 'string' ? next.signature : '',
  };
}

function syncArchivingReviewPayloadFromState(force = false) {
  const nextPayload = normalizeArchivingReviewPayload(state.archivingRules && state.archivingRules.draftReview);
  if (!force && archivingRulesReviewPayloadSignature === nextPayload.signature) {
    return;
  }

  archivingRulesReviewPayload = nextPayload;
  archivingRulesReviewPayloadSignature = nextPayload.signature;
  if (activeSettingsTabId === 'archiving-review' && !settingsModalEl.classList.contains('hidden')) {
    renderArchivingRuleReview(force);
  }
}

function applyArchivingRulesStatePayload(payload, options = {}) {
  state.archivingRules = normalizeArchivingRulesStatePayload(payload, state.archivingRules);
  renderAppNotices();
  syncArchivingReviewTabIndicator();
  syncArchivingReviewPayloadFromState(options.forceRender === true);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
  if (currentViewMode === 'review' && selectedJobId) {
    loadArchivedReview(selectedJobId, true).catch(() => {
      renderArchivedReviewPanel();
    });
  }
}

function findReviewLabelName(payload, labelId) {
  const normalized = typeof labelId === 'string' ? labelId.trim() : '';
  if (!normalized) {
    return '';
  }
  const labels = Array.isArray(payload && payload.availableLabels) ? payload.availableLabels : [];
  const match = labels.find((label) => label && typeof label.id === 'string' && label.id === normalized) || null;
  return match && typeof match.name === 'string' ? match.name : normalized;
}

function reviewFieldDefinitions(payload, system = false) {
  const list = system
    ? (Array.isArray(payload && payload.availableSystemFields) ? payload.availableSystemFields : [])
    : (Array.isArray(payload && payload.availableFields) ? payload.availableFields : []);
  return list.filter((field) => field && typeof field.key === 'string');
}

function ensureArchivedReviewDraft(jobId, payload) {
  const existing = archivedReviewDraftByJobId.get(jobId);
  const draftSignature = typeof payload?._draftSignature === 'string'
    ? payload._draftSignature
    : jobStateSignature(findJobById(jobId));
  if (existing && existing._signature === draftSignature) {
    return existing;
  }

  const source = payload && payload.activeAutoResult && typeof payload.activeAutoResult === 'object'
    ? payload.activeAutoResult
    : {};
  const draft = {
    principalId: typeof source.principalId === 'string' ? source.principalId : '',
    senderId: source.senderId ? String(source.senderId) : '',
    categoryId: typeof source.categoryId === 'string' ? source.categoryId : '',
    filename: typeof source.filename === 'string' ? source.filename : '',
    labels: Array.isArray(source.labels) ? [...source.labels] : [],
    fields: source.fields && typeof source.fields === 'object' ? deepCloneJson(source.fields) : {},
    systemFields: source.systemFields && typeof source.systemFields === 'object' ? deepCloneJson(source.systemFields) : {},
    _signature: draftSignature,
  };
  archivedReviewDraftByJobId.set(jobId, draft);
  return draft;
}

function renderArchivedReviewTags(container, values, payload) {
  container.innerHTML = '';
  const labels = Array.isArray(values) ? values : [];
  if (labels.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'archived-review-empty';
    empty.textContent = 'Inga etiketter';
    container.appendChild(empty);
    return;
  }
  labels.forEach((labelId) => {
    const tag = document.createElement('span');
    tag.className = 'archived-review-tag';
    tag.textContent = findReviewLabelName(payload, labelId);
    container.appendChild(tag);
  });
}

function renderArchivedReviewFieldRows(container, values, definitions, editable, onChange) {
  container.innerHTML = '';
  const defs = Array.isArray(definitions) ? definitions : [];
  if (defs.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'archived-review-empty';
    empty.textContent = 'Inga fält';
    container.appendChild(empty);
    return;
  }
  defs.forEach((field) => {
    const row = document.createElement('label');
    row.className = 'archived-review-field-row';
    const name = document.createElement('span');
    name.className = 'archived-review-field-name';
    name.textContent = typeof field.name === 'string' ? field.name : field.key;
    const input = document.createElement('input');
    input.type = 'text';
    input.value = values && Object.prototype.hasOwnProperty.call(values, field.key) && values[field.key] !== null
      ? String(values[field.key])
      : '';
    input.disabled = !editable;
    if (editable) {
      input.addEventListener('input', () => onChange(field.key, input.value));
    }
    row.append(name, input);
    container.appendChild(row);
  });
}

function buildArchivedReviewColumn(titleText) {
  const column = document.createElement('div');
  column.className = 'archived-review-column';
  const title = document.createElement('h4');
  title.textContent = titleText;
  column.appendChild(title);
  return column;
}

async function saveArchivedReviewAction(action) {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob || selectedJob.archived !== true || selectedJob.needsRuleReview !== true) {
    return;
  }

  const payload = archivedJobReviewPayload && archivedJobReviewPayload.jobId === selectedJob.id
    ? archivedJobReviewPayload
    : null;
  const draft = payload ? ensureArchivedReviewDraft(selectedJob.id, payload) : null;

  const body = {
    jobId: selectedJob.id,
    action,
  };
  if (action === 'manual' && draft) {
    body.principalId = draft.principalId || null;
    body.senderId = draft.senderId || null;
    body.categoryId = draft.categoryId || null;
    body.filename = draft.filename || null;
    body.labels = draft.labels;
    body.fields = draft.fields;
    body.systemFields = draft.systemFields;
  }

  const response = await fetch('/api/save-archived-job-review.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(body)
  });
  const result = await response.json().catch(() => null);
  if (!response.ok || !result || result.ok !== true) {
    throw new Error(result && typeof result.error === 'string' ? result.error : 'Kunde inte spara granskningsresultatet.');
  }

  archivedReviewDraftByJobId.delete(selectedJob.id);
  archivedJobReviewPayload = null;
  loadedMetaJobId = '';
  loadedMatchesJobId = '';
  loadedOcrJobId = '';
  applyStateEntry(result.entry);
  renderArchivedReviewPanel();
}

async function loadArchivedReview(jobId, force = false) {
  if (!jobId) {
    archivedJobReviewPayload = null;
    renderArchivedReviewPanel();
    return;
  }

  const selectedJob = findJobById(jobId);
  if (!selectedJob || !jobSupportsArchivedReview(selectedJob)) {
    archivedJobReviewPayload = null;
    renderArchivedReviewPanel();
    return;
  }

  const reviewStateSignature = typeof state.archivingRules?.signature === 'string'
    ? state.archivingRules.signature
    : '';
  if (!force && archivedJobReviewPayload && archivedJobReviewPayload.jobId === jobId && archivedJobReviewPayload._jobSignature === jobStateSignature(selectedJob)) {
    if (archivedJobReviewPayload._reviewStateSignature === reviewStateSignature) {
      renderArchivedReviewPanel();
      return;
    }
  }

  const requestSeq = ++archivedReviewRequestSeq;
  archivedJobReviewPayload = {
    jobId,
    loading: true,
    _jobSignature: jobStateSignature(selectedJob),
    _reviewStateSignature: reviewStateSignature,
  };
  renderArchivedReviewPanel();

  const response = await fetch('/api/get-archived-job-review.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda jobbgranskningen.');
  }
  const payload = await response.json();
  if (requestSeq !== archivedReviewRequestSeq) {
    return;
  }
  archivedJobReviewPayload = {
    ...payload,
    _jobSignature: jobStateSignature(selectedJob),
    _reviewStateSignature: reviewStateSignature,
    _draftSignature: JSON.stringify({
      archivedValue: payload && payload.archivedValue ? payload.archivedValue : null,
      activeAutoResult: payload && payload.activeAutoResult ? payload.activeAutoResult : null,
      draftAutoResult: payload && payload.draftAutoResult ? payload.draftAutoResult : null,
      reviewStateSignature,
    }),
  };
  renderArchivedReviewPanel();
}

function renderArchivedReviewPanel() {
  if (!(archivedReviewPanelEl instanceof HTMLElement)) {
    return;
  }

  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob || !jobSupportsArchivedReview(selectedJob)) {
    archivedReviewPanelEl.classList.add('hidden');
    archivedReviewPanelEl.replaceChildren();
    return;
  }

  archivedReviewPanelEl.classList.remove('hidden');
  const payload = archivedJobReviewPayload && archivedJobReviewPayload.jobId === selectedJob.id
    ? archivedJobReviewPayload
    : null;
  const reviewIsActionable = selectedJob.needsRuleReview === true;

  if (!payload || payload.loading === true) {
    archivedReviewPanelEl.textContent = 'Laddar jobbgranskning...';
    return;
  }

  const draft = ensureArchivedReviewDraft(selectedJob.id, payload);
  const wrapper = document.createElement('div');
  wrapper.className = 'archived-review-layout';

  const header = document.createElement('div');
  header.className = 'archived-review-header';
  const title = document.createElement('h3');
  title.textContent = 'Följ upp arkiverat jobb';
  const summary = document.createElement('div');
  summary.className = 'archived-review-summary-line';
  const classification = payload.classification && typeof payload.classification === 'object'
    ? String(payload.classification.type || 'info')
    : 'info';
  if (!reviewIsActionable) {
    summary.textContent = 'Det här är en förhandsgranskning av hur utkastet skulle påverka jobbet.';
  } else {
    summary.textContent = classification === 'risk'
      ? 'Det nya förslaget avviker på punkter som bör granskas.'
      : classification === 'improvement'
      ? 'Det nya förslaget ligger närmare tidigare godkänt värde.'
      : 'Det nya förslaget ändrar autoresultatet.';
  }
  header.append(title, summary);
  const changeItems = Array.isArray(payload.classification && payload.classification.changeItems)
    ? payload.classification.changeItems
    : [];
  if (changeItems.length > 0) {
    const list = document.createElement('ul');
    list.className = 'archiving-review-change-list archived-review-change-list';
    changeItems.forEach((item) => {
      const row = document.createElement('li');
      row.className = `archiving-review-change is-${item && item.type ? item.type : 'info'}`;
      const message = document.createElement('div');
      message.className = 'archiving-review-change-message';
      message.textContent = item && typeof item.message === 'string' ? item.message : '';
      row.appendChild(message);
      if (item && typeof item.detail === 'string' && item.detail) {
        const detail = document.createElement('div');
        detail.className = 'archiving-review-change-detail';
        detail.textContent = item.detail;
        row.appendChild(detail);
      }
      list.appendChild(row);
    });
    header.appendChild(list);
  }
  wrapper.appendChild(header);

  const columns = document.createElement('div');
  columns.className = 'archived-review-columns';
  const archivedColumn = buildArchivedReviewColumn('Arkiverat värde');
  const proposedColumn = buildArchivedReviewColumn('Nytt föreslaget värde');

  const archivedValue = payload.archivedValue && typeof payload.archivedValue === 'object' ? payload.archivedValue : {};
  const addSelectPair = (labelText, archivedValueText, currentValue, options, onChange) => {
    const archivedGroup = document.createElement('label');
    archivedGroup.className = 'archived-review-control';
    const archivedLabel = document.createElement('span');
    archivedLabel.textContent = labelText;
    const archivedSelect = document.createElement('select');
    archivedSelect.disabled = true;
    const archivedOption = document.createElement('option');
    archivedOption.value = '';
    archivedOption.textContent = archivedValueText && options.some((option) => option.value === String(archivedValueText))
      ? options.find((option) => option.value === String(archivedValueText)).label
      : (archivedValueText || '—');
    archivedSelect.appendChild(archivedOption);
    archivedColumn.appendChild(archivedGroup);
    archivedGroup.append(archivedLabel, archivedSelect);

    const proposedGroup = document.createElement('label');
    proposedGroup.className = 'archived-review-control';
    const proposedLabel = document.createElement('span');
    proposedLabel.textContent = labelText;
    const proposedSelect = document.createElement('select');
    const blankOption = document.createElement('option');
    blankOption.value = '';
    blankOption.textContent = 'Välj...';
    proposedSelect.appendChild(blankOption);
    options.forEach((option) => {
      const node = document.createElement('option');
      node.value = option.value;
      node.textContent = option.label;
      proposedSelect.appendChild(node);
    });
    proposedSelect.value = currentValue || '';
    proposedSelect.disabled = !reviewIsActionable;
    if (reviewIsActionable) {
      proposedSelect.addEventListener('change', () => onChange(proposedSelect.value));
    }
    proposedGroup.append(proposedLabel, proposedSelect);
    proposedColumn.appendChild(proposedGroup);
  };

  addSelectPair(
    'Huvudman',
    typeof archivedValue.principalId === 'string' ? archivedValue.principalId : '',
    draft.principalId,
    (Array.isArray(state.clients) ? state.clients : []).map((client) => ({
      value: client && typeof client.dirName === 'string' ? client.dirName : '',
      label: client && typeof client.dirName === 'string' ? client.dirName : ''
    })).filter((option) => option.value && option.label),
    (value) => {
      draft.principalId = value;
    }
  );

  addSelectPair(
    'Avsändare',
    archivedValue.senderId ? String(archivedValue.senderId) : '',
    draft.senderId,
    (Array.isArray(state.senders) ? state.senders : []).map((sender) => ({
      value: sender && Number.isInteger(sender.id) ? String(sender.id) : '',
      label: sender && typeof sender.name === 'string' ? sender.name : ''
    })).filter((option) => option.value && option.label),
    (value) => {
      draft.senderId = value;
    }
  );

  addSelectPair(
    'Kategori',
    typeof archivedValue.categoryId === 'string' ? archivedValue.categoryId : '',
    draft.categoryId,
    (Array.isArray(state.categories) ? state.categories : []).map((category) => ({
      value: category && typeof category.id === 'string' ? category.id : '',
      label: category && typeof category.name === 'string' ? category.name : ''
    })).filter((option) => option.value && option.label),
    (value) => {
      draft.categoryId = value;
    }
  );

  const archivedFilename = document.createElement('label');
  archivedFilename.className = 'archived-review-control';
  archivedFilename.innerHTML = '<span>Filnamn</span>';
  const archivedFilenameInput = document.createElement('input');
  archivedFilenameInput.type = 'text';
  archivedFilenameInput.disabled = true;
  archivedFilenameInput.value = typeof archivedValue.filename === 'string' ? archivedValue.filename : '';
  archivedFilename.appendChild(archivedFilenameInput);
  archivedColumn.appendChild(archivedFilename);

  const proposedFilename = document.createElement('label');
  proposedFilename.className = 'archived-review-control';
  proposedFilename.innerHTML = '<span>Filnamn</span>';
  const proposedFilenameInput = document.createElement('input');
  proposedFilenameInput.type = 'text';
  proposedFilenameInput.value = draft.filename || '';
  proposedFilenameInput.disabled = !reviewIsActionable;
  if (reviewIsActionable) {
    proposedFilenameInput.addEventListener('input', () => {
      draft.filename = proposedFilenameInput.value;
    });
  }
  proposedFilename.appendChild(proposedFilenameInput);
  proposedColumn.appendChild(proposedFilename);

  const archivedLabelsSection = document.createElement('div');
  archivedLabelsSection.className = 'archived-review-section';
  archivedLabelsSection.innerHTML = '<h5>Arkiverade etiketter</h5>';
  const archivedLabelsWrap = document.createElement('div');
  archivedLabelsWrap.className = 'archived-review-tags';
  renderArchivedReviewTags(archivedLabelsWrap, archivedValue.labels, payload);
  archivedLabelsSection.appendChild(archivedLabelsWrap);
  archivedColumn.appendChild(archivedLabelsSection);

  const proposedLabelsSection = document.createElement('div');
  proposedLabelsSection.className = 'archived-review-section';
  proposedLabelsSection.innerHTML = '<h5>Nya etiketter</h5>';
  const labelList = document.createElement('div');
  labelList.className = 'archived-review-checkboxes';
  (Array.isArray(payload.availableLabels) ? payload.availableLabels : []).forEach((label) => {
    if (!label || typeof label.id !== 'string') {
      return;
    }
    const row = document.createElement('label');
    row.className = 'archived-review-checkbox';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.checked = draft.labels.includes(label.id);
    input.disabled = !reviewIsActionable;
    if (reviewIsActionable) {
      input.addEventListener('change', () => {
        draft.labels = input.checked
          ? Array.from(new Set([...draft.labels, label.id]))
          : draft.labels.filter((item) => item !== label.id);
      });
    }
    const text = document.createElement('span');
    text.textContent = typeof label.name === 'string' ? label.name : label.id;
    row.append(input, text);
    labelList.appendChild(row);
  });
  proposedLabelsSection.appendChild(labelList);
  proposedColumn.appendChild(proposedLabelsSection);

  const archivedFieldsSection = document.createElement('div');
  archivedFieldsSection.className = 'archived-review-section';
  archivedFieldsSection.innerHTML = '<h5>Arkiverade datafält</h5>';
  const archivedFieldsWrap = document.createElement('div');
  renderArchivedReviewFieldRows(archivedFieldsWrap, archivedValue.fields || {}, reviewFieldDefinitions(payload, false), false, () => {});
  archivedFieldsSection.appendChild(archivedFieldsWrap);
  archivedColumn.appendChild(archivedFieldsSection);

  const proposedFieldsSection = document.createElement('div');
  proposedFieldsSection.className = 'archived-review-section';
  proposedFieldsSection.innerHTML = '<h5>Nya datafält</h5>';
  const proposedFieldsWrap = document.createElement('div');
  renderArchivedReviewFieldRows(proposedFieldsWrap, draft.fields || {}, reviewFieldDefinitions(payload, false), reviewIsActionable, (key, value) => {
    if (!value.trim()) {
      delete draft.fields[key];
    } else {
      draft.fields[key] = value;
    }
  });
  proposedFieldsSection.appendChild(proposedFieldsWrap);
  proposedColumn.appendChild(proposedFieldsSection);

  const archivedSystemFieldsSection = document.createElement('div');
  archivedSystemFieldsSection.className = 'archived-review-section';
  archivedSystemFieldsSection.innerHTML = '<h5>Arkiverade systemdatafält</h5>';
  const archivedSystemFieldsWrap = document.createElement('div');
  renderArchivedReviewFieldRows(archivedSystemFieldsWrap, archivedValue.systemFields || {}, reviewFieldDefinitions(payload, true), false, () => {});
  archivedSystemFieldsSection.appendChild(archivedSystemFieldsWrap);
  archivedColumn.appendChild(archivedSystemFieldsSection);

  const proposedSystemFieldsSection = document.createElement('div');
  proposedSystemFieldsSection.className = 'archived-review-section';
  proposedSystemFieldsSection.innerHTML = '<h5>Nya systemdatafält</h5>';
  const proposedSystemFieldsWrap = document.createElement('div');
  renderArchivedReviewFieldRows(proposedSystemFieldsWrap, draft.systemFields || {}, reviewFieldDefinitions(payload, true), reviewIsActionable, (key, value) => {
    if (!value.trim()) {
      delete draft.systemFields[key];
    } else {
      draft.systemFields[key] = value;
    }
  });
  proposedSystemFieldsSection.appendChild(proposedSystemFieldsWrap);
  proposedColumn.appendChild(proposedSystemFieldsSection);

  columns.append(archivedColumn, proposedColumn);
  wrapper.appendChild(columns);

  const actions = document.createElement('div');
  actions.className = 'archived-review-actions';
  if (!reviewIsActionable) {
    const previewNotice = document.createElement('div');
    previewNotice.className = 'archived-review-empty';
    previewNotice.textContent = 'Det här jobbet kan följas upp här efter att reglerna aktiverats.';
    actions.appendChild(previewNotice);
    wrapper.appendChild(actions);
    archivedReviewPanelEl.replaceChildren(wrapper);
    return;
  }
  const keepButton = document.createElement('button');
  keepButton.type = 'button';
  keepButton.textContent = 'Behåll arkiveringen';
  keepButton.addEventListener('click', async () => {
    try {
      await saveArchivedReviewAction('keep');
    } catch (error) {
      alert(error.message || 'Kunde inte avsluta granskningen.');
    }
  });
  const useNewButton = document.createElement('button');
  useNewButton.type = 'button';
  useNewButton.textContent = 'Använd nya förslag';
  useNewButton.addEventListener('click', async () => {
    try {
      await saveArchivedReviewAction('use-new');
    } catch (error) {
      alert(error.message || 'Kunde inte använda nya förslag.');
    }
  });
  const saveManualButton = document.createElement('button');
  saveManualButton.type = 'button';
  saveManualButton.textContent = 'Spara manuellt';
  saveManualButton.addEventListener('click', async () => {
    try {
      await saveArchivedReviewAction('manual');
    } catch (error) {
      alert(error.message || 'Kunde inte spara manuella ändringar.');
    }
  });
  actions.append(keepButton, useNewButton, saveManualButton);
  wrapper.appendChild(actions);

  archivedReviewPanelEl.replaceChildren(wrapper);
}

async function loadArchivingRuleReview() {
  syncArchivingReviewPayloadFromState(true);
}

function renderArchivingRuleReview(force = false) {
  if (!(archivingReviewSummaryEl instanceof HTMLElement) || !(archivingReviewJobsEl instanceof HTMLElement) || !(archivingReviewStatusEl instanceof HTMLElement)) {
    return;
  }

  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || emptyArchivingReviewPayload());
  if (!force && payload.signature && payload.signature === renderedArchivingReviewSignature) {
    return;
  }
  renderedArchivingReviewSignature = payload.signature;
  const summary = payload.summary && typeof payload.summary === 'object' ? payload.summary : {};
  const jobs = Array.isArray(payload.jobs) ? payload.jobs : [];
  const session = payload.session && typeof payload.session === 'object' ? payload.session : {};
  const sessionStatus = typeof session.status === 'string' ? session.status : 'idle';
  const analyzedCount = Number.parseInt(String(session.analyzedCount || 0), 10) || 0;
  const totalCount = Number.parseInt(String(session.totalCount || 0), 10) || 0;
  const foundCount = Number.parseInt(String(session.foundCount || 0), 10) || jobs.length;

  archivingReviewSummaryEl.innerHTML = '';
  const summaryItems = [
    ['Testade jobb', summary.testedJobs || 0],
    ['Oförändrade', summary.unchanged || 0],
    ['Förbättringar', summary.improvements || 0],
    ['Risker', summary.risks || 0],
    ['Infoändringar', summary.info || 0],
  ];
  summaryItems.forEach(([label, value]) => {
    const card = document.createElement('div');
    card.className = 'archiving-review-summary-card';
    const number = document.createElement('strong');
    number.textContent = String(value);
    const text = document.createElement('span');
    text.textContent = label;
    card.append(number, text);
    archivingReviewSummaryEl.appendChild(card);
  });

  if (payload.hasUnpublishedChanges === true) {
    archivingReviewStatusEl.classList.remove('hidden');
    const lines = document.createElement('div');
    lines.className = 'archiving-review-status-lines';

    const intro = document.createElement('div');
    intro.textContent = 'Det finns ett utkast till arkiveringsregler.';
    lines.appendChild(intro);

    if (Array.isArray(payload.changedSections) && payload.changedSections.length > 0) {
      const changedWrap = document.createElement('div');
      const changedLabel = document.createElement('div');
      changedLabel.textContent = 'Ändringar har gjorts i:';
      const changedList = document.createElement('ul');
      changedList.className = 'archiving-review-status-list';
      payload.changedSections.forEach((section) => {
        const item = document.createElement('li');
        item.textContent = section;
        changedList.appendChild(item);
      });
      changedWrap.append(changedLabel, changedList);
      lines.appendChild(changedWrap);
    }

    const statusLine = document.createElement('div');
    if (sessionStatus === 'running') {
      statusLine.textContent = `Analysen av arkiverade jobb pågår. ${analyzedCount} / ${totalCount} analyserade. ${foundCount} jobb hittade hittills. Fler jobb kan tillkomma medan analysen fortsätter.`;
    } else {
      statusLine.textContent = 'Analysen av arkiverade jobb är klar.';
    }
    lines.appendChild(statusLine);

    archivingReviewStatusEl.replaceChildren(lines);
  } else {
    archivingReviewStatusEl.classList.add('hidden');
    archivingReviewStatusEl.replaceChildren();
  }

  archivingReviewJobsEl.innerHTML = '';
  if (jobs.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = sessionStatus === 'running'
      ? 'Inga påverkade arkiverade jobb hittade ännu.'
      : totalCount === 0
      ? 'Det finns inga arkiverade jobb att jämföra mot utkastet ännu.'
      : 'Inga arkiverade jobb påverkas av utkastet just nu.';
    archivingReviewJobsEl.appendChild(empty);
    return;
  }

  jobs.forEach((item) => {
    const row = document.createElement('div');
    row.className = `archiving-review-job is-${item.classification && item.classification.type ? item.classification.type : 'info'}`;
    const body = document.createElement('div');
    body.className = 'archiving-review-job-body';
    const title = document.createElement('div');
    title.className = 'archiving-review-job-title';
    title.textContent = item.originalFilename || item.jobId;
    body.appendChild(title);
    const changeItems = Array.isArray(item.classification && item.classification.changeItems)
      ? item.classification.changeItems
      : [];
    if (changeItems.length > 0) {
      const changeList = document.createElement('ul');
      changeList.className = 'archiving-review-change-list';
      changeItems.forEach((changeItem) => {
        const changeRow = document.createElement('li');
        changeRow.className = `archiving-review-change is-${changeItem && changeItem.type ? changeItem.type : 'info'}`;
        const message = document.createElement('div');
        message.className = 'archiving-review-change-message';
        message.textContent = changeItem && typeof changeItem.message === 'string' ? changeItem.message : '';
        changeRow.appendChild(message);
        if (changeItem && typeof changeItem.detail === 'string' && changeItem.detail) {
          const detail = document.createElement('div');
          detail.className = 'archiving-review-change-detail';
          detail.textContent = changeItem.detail;
          changeRow.appendChild(detail);
        }
        changeList.appendChild(changeRow);
      });
      body.appendChild(changeList);
    }
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Öppna arkiverat jobb';
    button.addEventListener('click', () => {
      closeSettingsModal(true);
      setJobListMode('ready');
      applySelectedJobId(item.jobId);
      setViewMode('review');
    });
    row.append(body, button);
    archivingReviewJobsEl.appendChild(row);
  });
}

async function resetArchivingRulesDraft() {
  const response = await fetch('/api/reset-archiving-rules-draft.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: '{}'
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte kassera utkastet.');
  }
  await Promise.all([loadCategories(), loadLabels({ reload: true }), loadExtractionFields(), fetchState({ force: true, refreshCategories: true })]);
}

async function publishArchivingRules() {
  const response = await fetch('/api/publish-archiving-rules.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: '{}'
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte aktivera reglerna.');
  }

  await Promise.all([loadCategories(), loadLabels({ reload: true }), loadExtractionFields(), fetchState({ force: true, refreshCategories: true })]);
  const flaggedCount = payload.flaggedArchivedJobs && Number.isInteger(payload.flaggedArchivedJobs.flaggedCount)
    ? payload.flaggedArchivedJobs.flaggedCount
    : 0;
  if (flaggedCount > 0) {
    setJobListMode('ready');
    const firstReviewJob = jobsNeedingRuleReview()[0] || null;
    if (firstReviewJob && typeof firstReviewJob.id === 'string') {
      applySelectedJobId(firstReviewJob.id);
      setViewMode('review');
    }
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

function ensureJobListSectionLabelNode(text, key = 'label:failed') {
  const node = ensureJobListNode(key, () => {
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
  spinner.className = 'job-item-spinner is-hidden';
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
  if (!li._spinnerEl) {
    return;
  }
  li._spinnerEl.classList.add('is-hidden');
}

function updateReadyJobListItem(li, job) {
  li.className = 'job-item';
  if (job.status === 'processing') {
    li.classList.add('is-processing');
  }
  if (job.archived === true) {
    li.classList.add('is-archived');
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

  const spinner = ensureJobListItemSpinnerNode(li);
  if (job.status === 'processing') {
    spinner.classList.remove('is-hidden');
  } else {
    removeJobListItemSpinnerNode(li);
  }
}

function updateArchivedJobListItem(li, job) {
  li.className = 'job-item is-archived';
  if (job.needsRuleReview === true) {
    li.classList.add('is-needs-review');
  }
  if (job.id === selectedJobId) {
    li.classList.add('selected');
  }

  li.dataset.jobId = job.id;
  li._nameEl.textContent = job.originalFilename;

  const archivedLabel = job.needsRuleReview === true
    ? `Behöver ses över${job.filename ? ' • ' + job.filename : ''}`
    : jobHasDraftReviewChange(job.id)
      ? `Påverkas av utkast${job.filename ? ' • ' + job.filename : ''}`
    : (job.filename
      ? job.filename
      : (typeof job.archivedAt === 'string' && job.archivedAt ? job.archivedAt : ''));

  if (archivedLabel) {
    const secondary = ensureJobListItemSecondaryNode(li, 'job-client');
    secondary.textContent = archivedLabel;
  } else {
    removeJobListItemSecondaryNode(li);
  }

  removeJobListItemSpinnerNode(li);
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

function displayedReviewJobsForReadyMode() {
  return displayedArchivedReviewJobsForReadyMode();
}

function renderJobList(processingJobs, readyJobs, failedJobs) {
  const displayedReadyJobs = buildDisplayedReadyJobs(processingJobs, readyJobs);
  const displayedArchivedJobs = Array.isArray(state.archivedJobs) ? state.archivedJobs : [];
  const displayedReviewJobs = displayedReviewJobsForReadyMode();
  const safeFailedJobs = Array.isArray(failedJobs) ? failedJobs : [];
  const desiredNodes = [];
  const activeKeys = new Set();
  const safeProcessingJobs = Array.isArray(processingJobs) ? processingJobs : [];
  const isReadyMode = currentJobListMode === 'ready';
  const isAllMode = currentJobListMode === 'all';
  const isProcessingMode = currentJobListMode === 'processing';
  const isArchivedMode = currentJobListMode === 'archived';

  if (
    ((isReadyMode || isAllMode) && displayedReadyJobs.length === 0 && displayedReviewJobs.length === 0 && safeProcessingJobs.length === 0 && displayedArchivedJobs.length === 0 && safeFailedJobs.length === 0)
    || (isProcessingMode && safeProcessingJobs.length === 0 && safeFailedJobs.length === 0)
    || (isArchivedMode && displayedArchivedJobs.length === 0)
  ) {
    const messageNode = ensureJobListMessageNode();
    messageNode.textContent = isAllMode
      ? 'Inga jobb ännu.'
      : isProcessingMode
      ? 'Inga jobb bearbetas just nu.'
      : (isArchivedMode ? 'Inga arkiverade jobb ännu.' : 'Inga jobb att granska just nu.');
    desiredNodes.push(messageNode);
    activeKeys.add('message:empty');
  } else {
    if (isAllMode) {
      if (safeProcessingJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Bearbetas', 'label:all:processing');
        desiredNodes.push(labelNode);
        activeKeys.add('label:all:processing');
        safeProcessingJobs.forEach((job) => {
          const key = `all:processing:${job.id}`;
          const li = ensureJobListItemNode(key);
          updateReadyJobListItem(li, job);
          desiredNodes.push(li);
          activeKeys.add(key);
        });
      }

      if (displayedReadyJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Att granska', 'label:all:ready');
        desiredNodes.push(labelNode);
        activeKeys.add('label:all:ready');
        displayedReadyJobs.forEach((job) => {
          const key = `all:ready:${job.id}`;
          const li = ensureJobListItemNode(key);
          updateReadyJobListItem(li, job);
          desiredNodes.push(li);
          activeKeys.add(key);
        });
      }

      if (displayedArchivedJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Arkiverade jobb', 'label:all:archived');
        desiredNodes.push(labelNode);
        activeKeys.add('label:all:archived');
        displayedArchivedJobs.forEach((job) => {
          const key = `all:archived:${job.id}`;
          const li = ensureJobListItemNode(key);
          updateArchivedJobListItem(li, job);
          desiredNodes.push(li);
          activeKeys.add(key);
        });
      }

      if (safeFailedJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Misslyckade jobb', 'label:all:failed');
        desiredNodes.push(labelNode);
        activeKeys.add('label:all:failed');
        safeFailedJobs.forEach((job) => {
          const key = `all:failed:${job.id}`;
          const li = ensureJobListItemNode(key);
          updateFailedJobListItem(li, job);
          desiredNodes.push(li);
          activeKeys.add(key);
        });
      }
    } else if (isReadyMode) {
      if (displayedReviewJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Arkiverade jobb att granska', 'label:ready:review');
        desiredNodes.push(labelNode);
        activeKeys.add('label:ready:review');
        displayedReviewJobs.forEach((job) => {
          const key = `ready:review:${job.id}`;
          const li = ensureJobListItemNode(key);
          updateArchivedJobListItem(li, job);
          desiredNodes.push(li);
          activeKeys.add(key);
        });
      }

      if (displayedReadyJobs.length > 0 && displayedReviewJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Nya jobb', 'label:ready:jobs');
        desiredNodes.push(labelNode);
        activeKeys.add('label:ready:jobs');
      }

      displayedReadyJobs.forEach((job) => {
        const key = `ready:${job.id}`;
        const li = ensureJobListItemNode(key);
        updateReadyJobListItem(li, job);
        desiredNodes.push(li);
        activeKeys.add(key);
      });
    } else if (isProcessingMode) {
      safeProcessingJobs.forEach((job) => {
        const key = `processing:${job.id}`;
        const li = ensureJobListItemNode(key);
        updateReadyJobListItem(li, job);
        desiredNodes.push(li);
        activeKeys.add(key);
      });
    } else if (isArchivedMode) {
      displayedArchivedJobs.forEach((job) => {
        const key = `archived:${job.id}`;
        const li = ensureJobListItemNode(key);
        updateArchivedJobListItem(li, job);
        desiredNodes.push(li);
        activeKeys.add(key);
      });
    }

    if (isProcessingMode && safeFailedJobs.length > 0) {
      if (safeProcessingJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Misslyckade jobb', 'label:failed');
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

  const readyOrArchivedOrFailedJobs = []
    .concat(Array.isArray(state.readyJobs) ? state.readyJobs : [])
    .concat(Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
    .concat(Array.isArray(state.failedJobs) ? state.failedJobs : []);

  const directJob = readyOrArchivedOrFailedJobs.find((entry) => entry.id === jobId) || null;
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

function jobStateSignature(job) {
  if (!job || typeof job !== 'object') {
    return '';
  }

  const error = typeof job.error === 'string' ? job.error : '';
  const updatedAt = typeof job.updatedAt === 'string' ? job.updatedAt : '';
  const archivedAt = typeof job.archivedAt === 'string' ? job.archivedAt : '';
  return [
    typeof job.id === 'string' ? job.id : '',
    typeof job.status === 'string' ? job.status : '',
    updatedAt,
    archivedAt,
    error,
  ].join('|');
}

function jobsForCurrentListMode() {
  if (currentJobListMode === 'all') {
    return []
      .concat(Array.isArray(state.processingJobs) ? state.processingJobs : [])
      .concat(Array.isArray(state.readyJobs) ? state.readyJobs : [])
      .concat(Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
      .concat(Array.isArray(state.failedJobs) ? state.failedJobs : []);
  }
  if (currentJobListMode === 'processing') {
    return Array.isArray(state.processingJobs) ? state.processingJobs : [];
  }
  if (currentJobListMode === 'archived') {
    return Array.isArray(state.archivedJobs) ? state.archivedJobs : [];
  }
  return displayedJobsForCurrentListMode();
}

function displayedJobsForCurrentListMode() {
  if (currentJobListMode === 'ready') {
    return []
      .concat(displayedReviewJobsForReadyMode())
      .concat(buildDisplayedReadyJobs(state.processingJobs, state.readyJobs));
  }
  if (currentJobListMode === 'all') {
    return []
      .concat(Array.isArray(state.processingJobs) ? state.processingJobs : [])
      .concat(buildDisplayedReadyJobs([], state.readyJobs))
      .concat(Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
      .concat(Array.isArray(state.failedJobs) ? state.failedJobs : []);
  }
  return jobsForCurrentListMode();
}

function isJobVisibleInCurrentList(jobId) {
  return displayedJobsForCurrentListMode().some((job) => job && job.id === jobId);
}

function findCategoryById(categoryId) {
  if (!categoryId) {
    return null;
  }
  return (Array.isArray(state.categories) ? state.categories : []).find((category) => category && category.id === categoryId) || null;
}

function findSenderById(senderId) {
  const normalizedId = Number.parseInt(String(senderId || ''), 10);
  if (!Number.isInteger(normalizedId) || normalizedId < 1) {
    return null;
  }
  return (Array.isArray(state.senders) ? state.senders : []).find((sender) => Number(sender && sender.id) === normalizedId) || null;
}

function autoArchivingResultForJob(job) {
  const analysis = job && typeof job === 'object' && job.analysis && typeof job.analysis === 'object'
    ? job.analysis
    : null;
  return analysis && analysis.autoArchivingResult && typeof analysis.autoArchivingResult === 'object'
    ? analysis.autoArchivingResult
    : null;
}

function effectiveClientDirName(job) {
  const isKnownClient = (value) => {
    const normalized = typeof value === 'string' ? value.trim() : '';
    if (!normalized) {
      return false;
    }
    return Array.isArray(state.clients) && state.clients.some((client) => {
      return client && typeof client.dirName === 'string' && client.dirName.trim() === normalized;
    });
  };

  if (!job) {
    return '';
  }
  const localValue = selectedClientByJobId.get(job.id);
  if (isKnownClient(localValue)) {
    return localValue;
  }
  if (typeof job.selectedClientDirName === 'string' && isKnownClient(job.selectedClientDirName)) {
    return job.selectedClientDirName.trim();
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.principalId === 'string' && isKnownClient(autoResult.principalId)) {
    return autoResult.principalId.trim();
  }
  if (typeof job.matchedClientDirName === 'string' && isKnownClient(job.matchedClientDirName)) {
    return job.matchedClientDirName.trim();
  }
  return '';
}

function effectiveSenderId(job) {
  const isKnownSender = (value) => {
    const normalized = String(value || '').trim();
    if (!normalized) {
      return false;
    }
    return Array.isArray(state.senders) && state.senders.some((sender) => {
      return sender && Number.isInteger(sender.id) && String(sender.id) === normalized;
    });
  };

  if (!job) {
    return '';
  }
  const localValue = selectedSenderByJobId.get(job.id);
  if (isKnownSender(localValue)) {
    return localValue;
  }
  if (Number.isInteger(job.selectedSenderId) && job.selectedSenderId > 0 && isKnownSender(job.selectedSenderId)) {
    return String(job.selectedSenderId);
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && Number.isInteger(Number(autoResult.senderId)) && Number(autoResult.senderId) > 0 && isKnownSender(autoResult.senderId)) {
    return String(autoResult.senderId);
  }
  const preselectedSender = job.analysis && typeof job.analysis === 'object' && job.analysis.preselectedSender && typeof job.analysis.preselectedSender === 'object'
    ? job.analysis.preselectedSender
    : null;
  if (preselectedSender && Number.isInteger(Number(preselectedSender.id)) && Number(preselectedSender.id) > 0 && isKnownSender(preselectedSender.id)) {
    return String(preselectedSender.id);
  }
  const senderLookup = job.analysis && typeof job.analysis === 'object' && job.analysis.senderLookup && typeof job.analysis.senderLookup === 'object'
    ? job.analysis.senderLookup
    : null;
  const lookupSender = senderLookup && senderLookup.sender && typeof senderLookup.sender === 'object'
    ? senderLookup.sender
    : null;
  if (lookupSender && Number.isInteger(Number(lookupSender.id)) && Number(lookupSender.id) > 0 && isKnownSender(lookupSender.id)) {
    return String(lookupSender.id);
  }
  if (Number.isInteger(job.matchedSenderId) && job.matchedSenderId > 0 && isKnownSender(job.matchedSenderId)) {
    return String(job.matchedSenderId);
  }
  return '';
}

function effectiveCategoryId(job) {
  const isKnownCategory = (value) => {
    const normalized = typeof value === 'string' ? value.trim() : '';
    if (!normalized) {
      return false;
    }
    return Array.isArray(state.categories) && state.categories.some((category) => {
      return category
        && typeof category.id === 'string'
        && category.id.trim() === normalized;
    });
  };

  if (!job) {
    return '';
  }
  const localValue = selectedCategoryByJobId.get(job.id);
  if (isKnownCategory(localValue)) {
    return localValue;
  }
  if (typeof job.selectedCategoryId === 'string' && isKnownCategory(job.selectedCategoryId)) {
    return job.selectedCategoryId.trim();
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.categoryId === 'string' && isKnownCategory(autoResult.categoryId)) {
    return autoResult.categoryId.trim();
  }
  if (typeof job.topMatchedCategoryId === 'string' && isKnownCategory(job.topMatchedCategoryId)) {
    return job.topMatchedCategoryId.trim();
  }
  return '';
}

function formatFilenameAmount(value) {
  const amount = Number(value);
  if (!Number.isFinite(amount)) {
    return null;
  }
  return amount.toLocaleString('sv-SE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function buildFilenameFieldValues(job) {
  if (!job) {
    return new Map();
  }

  const extractionFields = job.analysis && typeof job.analysis === 'object' && job.analysis.extractionFields && typeof job.analysis.extractionFields === 'object'
    ? job.analysis.extractionFields
    : {};
  const autoResult = autoArchivingResultForJob(job);
  const clientDirName = effectiveClientDirName(job);
  const sender = findSenderById(effectiveSenderId(job));
  const category = findCategoryById(effectiveCategoryId(job));

  const values = new Map();
  const setValue = (key, value) => {
    if (value === null || value === undefined) {
      return;
    }
    const text = String(value).trim();
    if (text !== '') {
      values.set(key, text);
    }
  };

  setValue('category', category && category.name);
  setValue('client', clientDirName);
  setValue('main_client', clientDirName);
  setValue('sender', sender && sender.name);

  const extractionFieldValue = (key) => {
    const field = extractionFields && typeof extractionFields === 'object' ? extractionFields[key] : null;
    if (field && typeof field === 'object' && Object.prototype.hasOwnProperty.call(field, 'value')) {
      return field.value;
    }
    return field;
  };

  setValue('supplier', extractionFieldValue('supplier'));
  setValue('payment_receiver', extractionFieldValue('payment_receiver'));
  setValue('payee', extractionFieldValue('payment_receiver'));
  setValue('amount', formatFilenameAmount(extractionFieldValue('amount')));
  setValue('ocr', extractionFieldValue('ocr'));
  setValue('date', extractionFieldValue('due_date'));
  setValue('due_date', extractionFieldValue('due_date'));
  setValue('swift', extractionFieldValue('swift'));
  setValue('iban', extractionFieldValue('iban'));

  Object.entries(extractionFields).forEach(([fieldKey, field]) => {
    const entry = field && typeof field === 'object' && Object.prototype.hasOwnProperty.call(field, 'value')
      ? field
      : null;
    const key = entry && typeof entry.key === 'string'
      ? entry.key.trim()
      : (typeof fieldKey === 'string' ? fieldKey.trim() : '');
    const rawValue = entry ? entry.value : field;
    const value = typeof rawValue === 'string'
      ? rawValue.trim()
      : (rawValue === null || rawValue === undefined ? '' : String(rawValue).trim());
    if (key && value) {
      setValue(key, value);
    }
  });

  const labelIds = Array.isArray(autoResult && autoResult.labels)
    ? autoResult.labels
    : (Array.isArray(job.analysis && job.analysis.labels) ? job.analysis.labels : []);
  const labelNames = labelIds
    .map((labelId) => filenameTemplateLabelNameById(labelId))
    .filter((name) => typeof name === 'string' && name.trim() !== '');
  if (labelNames.length > 0) {
    values.set('__labels', labelNames);
  }

  return values;
}

function evaluateFilenameTemplateParts(parts, fieldValues) {
  if (!Array.isArray(parts) || parts.length === 0) {
    return '';
  }

  let result = '';
  parts.forEach((part) => {
    if (!part || typeof part !== 'object') {
      return;
    }

    if (part.type === 'field' || part.type === 'dataField' || part.type === 'systemField') {
      const key = typeof part.key === 'string' ? part.key.trim() : '';
      const rawValue = key ? fieldValues.get(key) : '';
      const value = typeof rawValue === 'string' ? rawValue.trim() : '';
      if (value === '') {
        return;
      }
      result += evaluateFilenameTemplateParts(part.prefixParts || [], fieldValues);
      result += value;
      result += evaluateFilenameTemplateParts(part.suffixParts || [], fieldValues);
      return;
    }

    if (part.type === 'category') {
      const value = String(fieldValues.get('category') || '').trim();
      if (value === '') {
        return;
      }
      result += evaluateFilenameTemplateParts(part.prefixParts || [], fieldValues);
      result += value;
      result += evaluateFilenameTemplateParts(part.suffixParts || [], fieldValues);
      return;
    }

    if (part.type === 'labels') {
      const labelNames = fieldValues.get('__labels');
      const separator = typeof part.separator === 'string' ? part.separator : DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR;
      const value = Array.isArray(labelNames)
        ? labelNames.map((item) => String(item || '').trim()).filter((item) => item !== '').join(separator)
        : '';
      if (value === '') {
        return;
      }
      result += evaluateFilenameTemplateParts(part.prefixParts || [], fieldValues);
      result += value;
      result += evaluateFilenameTemplateParts(part.suffixParts || [], fieldValues);
      return;
    }

    if (part.type === 'firstAvailable') {
      const candidates = Array.isArray(part.parts) ? part.parts : [];
      let resolved = '';
      for (const candidate of candidates) {
        resolved = evaluateFilenameTemplateParts([candidate], fieldValues);
        if (resolved !== '') {
          break;
        }
      }
      if (resolved === '') {
        return;
      }
      result += evaluateFilenameTemplateParts(part.prefixParts || [], fieldValues);
      result += resolved;
      result += evaluateFilenameTemplateParts(part.suffixParts || [], fieldValues);
      return;
    }

    result += typeof part.value === 'string' ? part.value : '';
  });

  return result;
}

function generateFilenameForJob(job) {
  if (!job) {
    return '';
  }

  const category = findCategoryById(effectiveCategoryId(job));
  const template = category && category.filenameTemplate && typeof category.filenameTemplate === 'object'
    ? sanitizeFilenameTemplate(category.filenameTemplate)
    : { parts: [] };
  const fieldValues = buildFilenameFieldValues(job);
  const rendered = evaluateFilenameTemplateParts(template.parts || [], fieldValues)
    .replace(/\s+/g, ' ')
    .trim();

  if (rendered !== '') {
    return rendered.endsWith('.pdf') ? rendered : `${rendered}.pdf`;
  }

  return job.originalFilename || 'dokument.pdf';
}

function displayedFilenameForJob(job) {
  if (!job) {
    return '';
  }
  const localValue = filenameByJobId.get(job.id);
  if (localValue) {
    return localValue;
  }
  if (typeof job.filename === 'string' && job.filename.trim() !== '') {
    return job.filename.trim();
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.filename === 'string' && autoResult.filename.trim() !== '') {
    return autoResult.filename.trim();
  }
  return generateFilenameForJob(job);
}

function syncFilenameField(job) {
  if (!filenameInputEl) {
    return;
  }

  const disabled = !job || job.status !== 'ready' || job.archived === true;
  filenameInputEl.disabled = disabled;
  filenameInputEl.value = job ? displayedFilenameForJob(job) : '';
}

function updateArchiveAction(job) {
  if (!archiveActionEl) {
    return;
  }

  if (!job) {
    archiveActionEl.disabled = true;
    archiveActionEl.textContent = 'Arkivera';
    archiveActionEl.title = 'Markera ett jobb först.';
    return;
  }

  const isArchived = job.archived === true;
  archiveActionEl.textContent = isArchived ? 'Återställ' : 'Arkivera';
  if (isArchived) {
    archiveActionEl.disabled = false;
    archiveActionEl.title = 'Flyttar tillbaka den arkiverade PDF-filen till jobbet och markerar jobbet som ej arkiverat.';
    return;
  }

  const missingFields = [];
  if (!effectiveClientDirName(job)) {
    missingFields.push('Huvudman');
  }
  if (!effectiveSenderId(job)) {
    missingFields.push('Avsändare');
  }
  if (!effectiveCategoryId(job)) {
    missingFields.push('Kategori');
  }
  if (!String(filenameInputEl ? filenameInputEl.value : displayedFilenameForJob(job)).trim()) {
    missingFields.push('Filnamn');
  }

  archiveActionEl.disabled = job.status !== 'ready' || missingFields.length > 0;
  archiveActionEl.title = archiveActionEl.disabled
    ? (job.status !== 'ready'
      ? 'Jobbet måste vara klart innan det kan arkiveras.'
      : `Fyll i ${missingFields.join(', ')} innan jobbet kan arkiveras.`)
    : 'Flyttar review.pdf till vald huvudmans arkivmapp med angivet filnamn.';
}

let saveSelectedJobFieldsSeq = 0;

function applyStateEntry(entry, options = {}) {
  if (!entry || typeof entry !== 'object' || !entry.job || typeof entry.job !== 'object') {
    return;
  }
  const list = typeof entry.list === 'string' ? entry.list : '';
  if (!list) {
    return;
  }
  applyJobEvents([{
    type: 'job.upsert',
    list,
    job: entry.job,
    preserveListPosition: options.preserveListPosition === true,
  }]);
}

function restoreSelectedJobEditorState() {
  const currentJob = findJobById(selectedJobId);
  setClientForJob(currentJob);
  setSenderForJob(currentJob);
  setCategoryForJob(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
}

async function saveSelectedJobFields(jobId, payload) {
  const requestSeq = ++saveSelectedJobFieldsSeq;
  const response = await fetch('/api/save-job-fields.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      jobId,
      ...payload,
    })
  });

  const data = await response.json().catch(() => null);
  if (!response.ok || !data || data.ok !== true || !data.job || typeof data.job !== 'object') {
    const message = data && typeof data.error === 'string' ? data.error : 'Kunde inte spara jobbdata';
    throw new Error(message);
  }

  if (requestSeq !== saveSelectedJobFieldsSeq) {
    return;
  }

  applyStateEntry(data.entry);
}

function scheduleFilenameSave(jobId, filename) {
  const existingTimer = filenameSaveTimerByJobId.get(jobId);
  if (existingTimer) {
    window.clearTimeout(existingTimer);
  }

  const nextTimer = window.setTimeout(async () => {
    filenameSaveTimerByJobId.delete(jobId);
    try {
      await saveSelectedJobFields(jobId, { filename });
    } catch (error) {
      restoreSelectedJobEditorState();
      alert(error.message || 'Kunde inte spara filnamn.');
    }
  }, 350);

  filenameSaveTimerByJobId.set(jobId, nextTimer);
}

function renderSelectedJobPanel() {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    syncReviewViewModeAvailability(null, { allowFallback: false });
    selectedJobPanelEl.classList.add('is-empty');
    selectedJobNameEl.textContent = 'Inget jobb markerat';
    selectedJobMetaEl.textContent = 'Markera ett jobb i listan för att visa åtgärder.';
    selectedJobReprocessEl.disabled = true;
    syncFilenameField(null);
    updateArchiveAction(null);
    return;
  }

  syncReviewViewModeAvailability(selectedJob, { allowFallback: false });
  selectedJobPanelEl.classList.remove('is-empty');
  selectedJobNameEl.textContent = selectedJob.originalFilename || selectedJob.id;

  const metaLines = [];
  const appendLine = (text, extraClass = '') => {
    const line = document.createElement('div');
    line.className = extraClass ? `selected-job-meta-line ${extraClass}` : 'selected-job-meta-line';
    line.textContent = text;
    metaLines.push(line);
  };

  if (selectedJob.status === 'processing') {
    appendLine('Status: Bearbetas');
  } else if (selectedJob.status === 'failed') {
    appendLine('Status: Misslyckat');
  } else if (selectedJob.archived === true) {
    appendLine('Status: Arkiverat');
  } else {
    appendLine('Status: Klar');
  }

  if (selectedJob && selectedJob.ocr && selectedJob.ocr.usedExistingText === true) {
    appendLine('Dokumentet hade redan OCR-text. OCR-steget hoppades över.');
  }

  if (selectedJob.status === 'failed' && selectedJob.error) {
    appendLine('Fel: ' + selectedJob.error, 'is-error');
  }
  if (selectedJob.archived === true && typeof selectedJob.archivedAt === 'string' && selectedJob.archivedAt) {
    appendLine('Arkiverat: ' + selectedJob.archivedAt.replace('T', ' ').replace(/([+-]\d{2}:\d{2}|Z)$/, '').trim());
  }
  if (selectedJob.archived === true && selectedJob.needsRuleReview === true) {
    appendLine('Behöver ses över enligt aktuella regler.', 'is-warning');
  } else if (selectedJob.archived === true && jobHasDraftReviewChange(selectedJob.id)) {
    appendLine('Påverkas av utkast till arkiveringsregler.', 'is-warning');
  }

  selectedJobMetaEl.replaceChildren(...metaLines);
  selectedJobReprocessEl.disabled = selectedJob.status === 'processing' || selectedJob.archived === true || !selectedJob.hasReviewPdf;
  syncFilenameField(selectedJob);
  updateArchiveAction(selectedJob);
}

function buildDisplayedReadyJobs(processingJobs, readyJobs) {
  const displayed = Array.isArray(readyJobs)
    ? readyJobs.map((job, index) => {
        const snapshot = lastKnownJobDisplayById.get(job.id);
        const pinnedOrder = pinnedProcessingOrderById.get(job.id);
        return {
          ...job,
          _displayOrder: typeof snapshot?._displayOrder === 'number'
            ? snapshot._displayOrder
            : (typeof pinnedOrder === 'number' ? pinnedOrder : index)
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
          : (
            typeof snapshot._displayOrder === 'number'
              ? snapshot._displayOrder
              : (typeof pinnedProcessingOrderById.get(processingJob.id) === 'number'
                ? pinnedProcessingOrderById.get(processingJob.id)
                : Number.MAX_SAFE_INTEGER)
          )
      });
      return;
    }

    displayed.push({
      ...processingJob,
      _displayOrder: orderById.has(processingJob.id)
        ? orderById.get(processingJob.id)
        : (typeof pinnedProcessingOrderById.get(processingJob.id) === 'number'
          ? pinnedProcessingOrderById.get(processingJob.id)
          : Number.MAX_SAFE_INTEGER)
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

function normalizeOcrSource(source) {
  return source === 'tesseract' || source === 'rapidocr' || source === 'merged' || source === 'merged-objects'
    ? source
    : 'merged';
}

function setOcrSourceTabsVisible(visible) {
  ocrSourceTabsEl.classList.toggle('hidden', !visible);
}

function setOcrControlsVisible(visible) {
  ocrPageControlsEl.classList.toggle('hidden', !visible);
}

function setOcrPageImageToggleVisible(visible) {
  if (!ocrPageImageControlsEl) {
    return;
  }
  ocrPageImageControlsEl.classList.toggle('hidden', !visible);
}

function updateOcrPageImageControls() {
  if (ocrShowPageImageEl) {
    ocrShowPageImageEl.checked = ocrShowPageImage;
  }
  if (ocrPageImageOpacityEl) {
    ocrPageImageOpacityEl.disabled = !ocrShowPageImage;
    ocrPageImageOpacityEl.value = String(Math.round(ocrPageImageBlend * 100));
  }
  let imageOpacity = 0;
  let surfaceOpacity = 1;
  if (ocrShowPageImage) {
    const blend = Number.isFinite(ocrPageImageBlend)
      ? Math.max(0, Math.min(1, ocrPageImageBlend))
      : 0.5;
    imageOpacity = blend <= 0.5 ? blend / 0.5 : 1;
    surfaceOpacity = blend >= 0.5 ? (1 - blend) / 0.5 : 1;
  }
  ocrPagesViewEl.style.setProperty('--ocr-page-image-opacity', String(imageOpacity));
  ocrPagesViewEl.style.setProperty('--ocr-page-surface-opacity', String(surfaceOpacity));
}

function setActiveOcrSource(source, options = {}) {
  const reload = options.reload !== false;
  const nextSource = normalizeOcrSource(source);
  if (currentViewMode === 'ocr' && nextSource !== currentOcrSource) {
    saveCurrentOcrViewState();
  }
  currentOcrSource = nextSource;
  ocrSearchInputEl.placeholder = `Sök i ${ocrSourceDisplayName(nextSource)}`;
  ocrSourceTabEls.forEach((buttonEl) => {
    const isActive = buttonEl.dataset.ocrSource === nextSource;
    buttonEl.classList.toggle('active', isActive);
    buttonEl.setAttribute('aria-selected', isActive ? 'true' : 'false');
  });
  if (reload && currentViewMode === 'ocr') {
    loadedOcrJobId = '';
    loadedOcrSource = '';
    setViewerOcr(selectedJobId);
  }
}

function ocrSourceDisplayName(source) {
  if (source === 'tesseract') {
    return 'Tesseract-objekt';
  }
  if (source === 'rapidocr') {
    return 'RapidOCR-objekt';
  }
  if (source === 'merged-objects') {
    return 'Sammanfogade objekt';
  }
  return 'Sammanfogad text';
}

function parseOcrZoomValue(value) {
  const digitsOnly = String(value || '').replace(/\D+/g, '');
  if (digitsOnly === '') {
    return null;
  }
  const parsed = Number.parseInt(digitsOnly, 10);
  if (!Number.isFinite(parsed) || parsed <= 0) {
    return null;
  }
  return parsed;
}

function formatOcrZoomValue(value) {
  const parsed = parseOcrZoomValue(value);
  return parsed === null ? '' : `${parsed}%`;
}

function updateOcrZoomControls() {
  ocrZoomInputEl.value = formatOcrZoomValue(currentOcrZoom);
  ocrZoomOutEl.disabled = !OCR_ZOOM_STEPS.some((step) => step < currentOcrZoom);
  ocrZoomInEl.disabled = !OCR_ZOOM_STEPS.some((step) => step > currentOcrZoom);
}

function getCurrentVisibleOcrPageNumber() {
  if (ocrRenderedPages.length === 0) {
    return 1;
  }

  const anchor = ocrPagesViewEl.scrollTop + (ocrPagesViewEl.clientHeight * 0.25);
  let currentPage = ocrRenderedPages[0].number;
  ocrRenderedPages.forEach((page) => {
    if (page.wrapperEl.offsetTop <= anchor) {
      currentPage = page.number;
    }
  });
  return currentPage;
}

function updateOcrPageControls() {
  const pageCount = ocrDocumentPages.length;
  const currentPage = pageCount > 0 ? getCurrentVisibleOcrPageNumber() : 0;
  ocrPageTotalEl.textContent = String(pageCount);
  ocrPageCurrentEl.value = String(currentPage);
  ocrPageCurrentEl.min = pageCount > 0 ? '1' : '0';
  ocrPageCurrentEl.max = String(pageCount);
  updateOcrZoomControls();
}

function buildOcrPageImageUrl(pageNumber) {
  const jobId = loadedOcrJobId || selectedJobId;
  if (!jobId || !Number.isInteger(pageNumber) || pageNumber < 1) {
    return '';
  }
  return '/api/get-job-page-image.php?id='
    + encodeURIComponent(jobId)
    + '&page='
    + encodeURIComponent(String(pageNumber))
    + '&dpi=150&variant=review';
}

function ocrPageImageCacheKey(pageNumber) {
  const jobId = loadedOcrJobId || selectedJobId;
  if (!jobId || !Number.isInteger(pageNumber) || pageNumber < 1) {
    return '';
  }
  return `${jobId}:review:${pageNumber}:150`;
}

function getOrCreateOcrPageImage(pageNumber) {
  const cacheKey = ocrPageImageCacheKey(pageNumber);
  const imageUrl = buildOcrPageImageUrl(pageNumber);
  if (!cacheKey || !imageUrl) {
    return null;
  }

  const existing = ocrPageImageCache.get(cacheKey) || null;
  if (existing && existing.imageEl instanceof HTMLImageElement) {
    return existing.imageEl;
  }

  const imageEl = document.createElement('img');
  imageEl.className = 'ocr-page-image';
  imageEl.alt = '';
  imageEl.decoding = 'async';
  imageEl.loading = 'eager';
  imageEl.src = imageUrl;
  ocrPageImageCache.set(cacheKey, {
    imageEl,
    url: imageUrl,
  });
  return imageEl;
}

function appendOcrPageImage(wrapperEl, pageNumber) {
  if (!ocrShowPageImage) {
    return;
  }
  const imageEl = getOrCreateOcrPageImage(pageNumber);
  if (!(imageEl instanceof HTMLImageElement)) {
    return;
  }
  wrapperEl.classList.add('has-page-image');
  wrapperEl.appendChild(imageEl);
}

function applyOcrZoom() {
  const scale = currentOcrZoom / 100;
  ocrPagesViewEl.style.setProperty('--ocr-page-scale', String(scale));
  updateOcrPageImageControls();
  renderOcrPages();
  updateOcrPageControls();
}

function setOcrZoom(nextZoom, options = {}) {
  const normalizedZoom = parseOcrZoomValue(nextZoom);
  if (normalizedZoom === null) {
    updateOcrZoomControls();
    return;
  }

  const preservePage = options.preservePage !== false;
  const targetPage = preservePage ? getCurrentVisibleOcrPageNumber() : null;
  currentOcrZoom = normalizedZoom;
  applyOcrZoom();
  if (targetPage !== null) {
    scrollOcrPageIntoView(targetPage);
  }
}

function stepOcrZoom(direction) {
  const candidateSteps = OCR_ZOOM_STEPS.filter((step) => direction < 0
    ? step < currentOcrZoom
    : step > currentOcrZoom);
  if (candidateSteps.length === 0) {
    updateOcrZoomControls();
    return;
  }
  const nextZoom = direction < 0
    ? candidateSteps[candidateSteps.length - 1]
    : candidateSteps[0];
  setOcrZoom(nextZoom);
}

function commitOcrZoomInput() {
  const parsedZoom = parseOcrZoomValue(ocrZoomInputEl.value);
  if (parsedZoom === null) {
    updateOcrZoomControls();
    return;
  }
  setOcrZoom(parsedZoom);
}

function scrollOcrPageIntoView(pageNumber) {
  const normalizedPageNumber = Math.max(1, Math.min(ocrRenderedPages.length, Number.parseInt(String(pageNumber), 10) || 1));
  const targetPage = ocrRenderedPages.find((page) => page.number === normalizedPageNumber);
  if (!targetPage) {
    updateOcrPageControls();
    return;
  }
  ocrPagesViewEl.scrollTop = Math.max(0, targetPage.wrapperEl.offsetTop - 10);
  ocrPagesViewEl.scrollLeft = 0;
  updateOcrPageControls();
}

function saveCurrentOcrViewState() {
  const source = normalizeOcrSource(loadedOcrSource);
  if (!loadedOcrSource) {
    return;
  }
  ocrViewStateBySource.set(source, {
    scrollTop: ocrPagesViewEl.scrollTop,
    scrollLeft: ocrPagesViewEl.scrollLeft,
  });
}

function getCachedOcrViewContent(source) {
  const normalizedSource = normalizeOcrSource(source);
  return ocrViewContentBySource.has(normalizedSource) ? ocrViewContentBySource.get(normalizedSource) : null;
}

function setCachedOcrViewContent(source, content) {
  const normalizedSource = normalizeOcrSource(source);
  if (typeof content === 'string') {
    ocrViewContentBySource.set(normalizedSource, {
      text: content,
      pages: null,
    });
    return;
  }
  ocrViewContentBySource.set(normalizedSource, content);
}

function restoreOcrViewState(source) {
  const state = ocrViewStateBySource.get(normalizeOcrSource(source)) || null;
  const scrollTop = state && Number.isFinite(Number(state.scrollTop)) ? Number(state.scrollTop) : 0;
  const scrollLeft = state && Number.isFinite(Number(state.scrollLeft)) ? Number(state.scrollLeft) : 0;

  window.requestAnimationFrame(() => {
    window.requestAnimationFrame(() => {
      ocrPagesViewEl.scrollTop = scrollTop;
      ocrPagesViewEl.scrollLeft = scrollLeft;
      syncOcrHighlightScroll();
    });
  });
}

function clearOcrViewCache() {
  ocrViewContentBySource.clear();
  ocrViewStateBySource.clear();
  ocrPageImageCache.clear();
}

function splitOcrTextIntoPages(text) {
  const rawText = typeof text === 'string' ? text.replace(/\r\n/g, '\n') : '';
  if (rawText === '') {
    return [{ number: 1, text: '', start: 0, end: 0 }];
  }

  const markerRegex = /^=== PAGE (\d+) ===\n?/gm;
  const markers = Array.from(rawText.matchAll(markerRegex));
  let pageTexts = [];

  if (markers.length > 0) {
    markers.forEach((match, index) => {
      const markerText = match[0] || '';
      const start = (match.index ?? 0) + markerText.length;
      const end = index + 1 < markers.length ? (markers[index + 1].index ?? rawText.length) : rawText.length;
      const pageText = rawText
        .slice(start, end)
        .replace(/^\n+/, '')
        .replace(/\n+$/, '');
      pageTexts.push({
        number: Number.parseInt(match[1] || '', 10) || index + 1,
        text: pageText,
      });
    });
  } else if (rawText.includes('\f')) {
    pageTexts = rawText.split('\f').map((pageText, index) => ({
      number: index + 1,
      text: pageText.replace(/^\n+/, '').replace(/\n+$/, ''),
    }));
  } else {
    pageTexts = [{ number: 1, text: rawText }];
  }

  let cursor = 0;
  return pageTexts.map((page, index) => {
    const normalizedText = typeof page.text === 'string' ? page.text : '';
    const start = cursor;
    const end = start + normalizedText.length;
    cursor = end + (index < pageTexts.length - 1 ? 2 : 0);
    return {
      number: page.number,
      text: normalizedText,
      start,
      end,
    };
  });
}

function normalizeWordRect(bbox) {
  if (bbox && typeof bbox === 'object' && !Array.isArray(bbox)) {
    const x0 = Number(bbox.x0);
    const y0 = Number(bbox.y0);
    const x1 = Number(bbox.x1);
    const y1 = Number(bbox.y1);
    if ([x0, y0, x1, y1].every((value) => Number.isFinite(value))) {
      return {
        x0: Math.min(x0, x1),
        y0: Math.min(y0, y1),
        x1: Math.max(x0, x1),
        y1: Math.max(y0, y1),
      };
    }
  }

  if (!Array.isArray(bbox) || bbox.length === 0) {
    return null;
  }

  if (bbox.length === 4 && bbox.every((value) => Number.isFinite(Number(value)))) {
    const x0 = Number(bbox[0]);
    const y0 = Number(bbox[1]);
    const x1 = Number(bbox[2]);
    const y1 = Number(bbox[3]);
    return {
      x0: Math.min(x0, x1),
      y0: Math.min(y0, y1),
      x1: Math.max(x0, x1),
      y1: Math.max(y0, y1),
    };
  }

  const points = bbox
    .filter((point) => Array.isArray(point) && point.length >= 2 && Number.isFinite(Number(point[0])) && Number.isFinite(Number(point[1])))
    .map((point) => ({
      x: Number(point[0]),
      y: Number(point[1]),
    }));

  if (points.length === 0) {
    return null;
  }

  const xs = points.map((point) => point.x);
  const ys = points.map((point) => point.y);
  return {
    x0: Math.min(...xs),
    y0: Math.min(...ys),
    x1: Math.max(...xs),
    y1: Math.max(...ys),
  };
}

function buildObjectSearchRows(words) {
  const orderedWords = [...words].sort((left, right) => {
    const yDiff = left.rect.y0 - right.rect.y0;
    if (Math.abs(yDiff) > Math.max(left.rect.height, right.rect.height) * 0.35) {
      return yDiff;
    }
    return left.rect.x0 - right.rect.x0;
  });

  const rows = [];
  orderedWords.forEach((word) => {
    const centerY = word.rect.y0 + (word.rect.height / 2);
    const row = rows.find((candidate) => {
      const tolerance = Math.max(candidate.height, word.rect.height) * 0.5;
      return Math.abs(candidate.centerY - centerY) <= tolerance;
    });
    if (!row) {
      rows.push({
        centerY,
        height: word.rect.height,
        words: [word],
      });
      return;
    }
    row.words.push(word);
    row.centerY = ((row.centerY * (row.words.length - 1)) + centerY) / row.words.length;
    row.height = Math.max(row.height, word.rect.height);
  });

  rows.forEach((row) => {
    row.words.sort((left, right) => left.rect.x0 - right.rect.x0);
    const heights = row.words
      .map((word) => Number(word.rect.height))
      .filter((height) => Number.isFinite(height) && height > 0)
      .sort((left, right) => left - right);
    if (heights.length > 0) {
      const middleIndex = Math.floor(heights.length / 2);
      row.typicalHeight = heights.length % 2 === 0
        ? (heights[middleIndex - 1] + heights[middleIndex]) / 2
        : heights[middleIndex];
    } else {
      row.typicalHeight = row.height;
    }
  });
  rows.sort((left, right) => left.centerY - right.centerY);
  return rows;
}

function buildObjectPageSearchModel(words) {
  const rows = buildObjectSearchRows(words);
  let searchText = '';
  const searchEntries = [];

  rows.forEach((row, rowIndex) => {
    row.words.forEach((word, wordIndex) => {
      if (searchText !== '') {
        searchText += wordIndex === 0 ? '\n' : ' ';
      }
      const start = searchText.length;
      searchText += word.text;
      searchEntries.push({
        wordIndex: word.index,
        start,
        end: searchText.length,
      });
    });
    if (rowIndex < rows.length - 1) {
      // handled by the next row's first word
    }
  });

  return {
    searchText,
    searchEntries,
    rows,
  };
}

function normalizeObjectWord(word, index) {
  const text = typeof (word && word.text) === 'string' ? word.text : '';
  const rect = normalizeWordRect(word && word.bbox);
  if (text === '' || rect === null) {
    return null;
  }

  const scoreCandidate = word && Number.isFinite(Number(word.score))
    ? Number(word.score)
    : null;

  return {
    index,
    text,
    rect: {
      x0: rect.x0,
      y0: rect.y0,
      x1: rect.x1,
      y1: rect.y1,
      width: Math.max(1, rect.x1 - rect.x0),
      height: Math.max(1, rect.y1 - rect.y0),
    },
    score: scoreCandidate,
    raw: word && typeof word.raw === 'object' && word.raw !== null ? word.raw : null,
  };
}

function objectRenderSource(source) {
  return source === 'merged-objects' ? 'merged' : source;
}

function normalizeOcrPages(pages, fallbackText = '', mode = 'text') {
  if (!Array.isArray(pages) || pages.length === 0) {
    return splitOcrTextIntoPages(fallbackText).map((page) => ({
      ...page,
      renderMode: 'text',
      searchText: page.text,
    }));
  }

  const normalizedPages = pages.map((page, index) => {
    const number = Number.parseInt(String(page && page.number), 10) || index + 1;
    const text = typeof (page && page.text) === 'string' ? page.text.replace(/\r\n/g, '\n') : '';
    if (mode !== 'objects') {
      return {
        number,
        text,
        renderMode: 'text',
        searchText: text,
      };
    }

    const words = Array.isArray(page && page.words)
      ? page.words
        .map((word, wordIndex) => normalizeObjectWord(word, wordIndex))
        .filter((word) => word !== null)
      : [];
    const searchModel = buildObjectPageSearchModel(words);
    (searchModel.rows || []).forEach((row) => {
      row.words.forEach((word) => {
        word.rowHeight = row.height;
        word.rowTypicalHeight = Number.isFinite(Number(row.typicalHeight))
          ? Number(row.typicalHeight)
          : row.height;
      });
    });
    const derivedWidth = words.reduce((maxWidth, word) => Math.max(maxWidth, word.rect.x1), 0);
    const derivedHeight = words.reduce((maxHeight, word) => Math.max(maxHeight, word.rect.y1), 0);
    const pageWidth = Number.isFinite(Number(page && page.pageWidth)) ? Number(page.pageWidth) : derivedWidth;
    const pageHeight = Number.isFinite(Number(page && page.pageHeight)) ? Number(page.pageHeight) : derivedHeight;

    return {
      number,
      text,
      renderMode: 'objects',
      pageWidth: Math.max(1, pageWidth),
      pageHeight: Math.max(1, pageHeight),
      words,
      searchText: searchModel.searchText || text,
      searchEntries: searchModel.searchEntries,
    };
  });

  let cursor = 0;
  return normalizedPages.map((page, index) => {
    const searchText = typeof page.searchText === 'string' ? page.searchText : page.text;
    const start = cursor;
    const end = start + searchText.length;
    cursor = end + (index < normalizedPages.length - 1 ? 2 : 0);
    return {
      ...page,
      start,
      end,
    };
  });
}

function buildOcrPageHighlightHtml(pageText, pageMatches) {
  if (!Array.isArray(pageMatches) || pageMatches.length === 0) {
    return escapeHtml(pageText);
  }

  let html = '';
  let cursor = 0;
  pageMatches.forEach((match, index) => {
    if (match.start > cursor) {
      html += escapeHtml(pageText.slice(cursor, match.start));
    }
    const matchedText = pageText.slice(match.start, match.end);
    const className = match.globalIndex === ocrSearchActiveIndex ? ' class="is-active"' : '';
    html += `<mark${className}>${escapeHtml(matchedText)}</mark>`;
    cursor = match.end;
  });
  if (cursor < pageText.length) {
    html += escapeHtml(pageText.slice(cursor));
  }
  return html;
}

function measureOcrPageWidth(textareaEl, pageText) {
  const lines = String(pageText || '').split('\n');
  const longestLineLength = lines.reduce(
    (maxLength, line) => Math.max(maxLength, Array.from(line).length),
    0
  );
  const styles = window.getComputedStyle(textareaEl);
  const fontSize = parseFloat(styles.fontSize) || 12;
  const scale = currentOcrZoom / 100;
  const charWidth = fontSize * 0.6;
  const horizontalPadding = ((parseFloat(styles.paddingLeft) || 0) + (parseFloat(styles.paddingRight) || 0));
  const borderWidth = ((parseFloat(styles.borderLeftWidth) || 0) + (parseFloat(styles.borderRightWidth) || 0));
  const contentWidth = longestLineLength * charWidth;
  const extraPadding = 24 * scale;
  return Math.ceil(contentWidth + horizontalPadding + borderWidth + extraPadding);
}

function resizeOcrPage(wrapperEl, highlightEl, textareaEl, targetWidth = null) {
  const pageText = textareaEl.value || '';
  const nextWidth = Number.isFinite(Number(targetWidth))
    ? Number(targetWidth)
    : measureOcrPageWidth(textareaEl, pageText);
  wrapperEl.style.width = `${nextWidth}px`;
  highlightEl.style.width = `${nextWidth}px`;
  textareaEl.style.width = `${nextWidth}px`;
  textareaEl.style.height = '1px';
  const nextHeight = Math.max(highlightEl.scrollHeight, textareaEl.scrollHeight);
  wrapperEl.style.height = `${nextHeight}px`;
  textareaEl.style.height = `${nextHeight}px`;
}

function buildObjectPageMatchLookup(page, pageMatches) {
  const matchedWordIndexes = new Set();
  const activeWordIndexes = new Set();
  const activeMatch = ocrSearchActiveIndex >= 0 ? pageMatches.find((match) => match.globalIndex === ocrSearchActiveIndex) : null;

  (page.searchEntries || []).forEach((entry) => {
    const overlapsAny = pageMatches.some((match) => entry.start < match.end && entry.end > match.start);
    if (overlapsAny) {
      matchedWordIndexes.add(entry.wordIndex);
    }
    if (activeMatch && entry.start < activeMatch.end && entry.end > activeMatch.start) {
      activeWordIndexes.add(entry.wordIndex);
    }
  });

  return {
    matchedWordIndexes,
    activeWordIndexes,
  };
}

function buildWordTooltip(word) {
  const parts = [word.text];
  if (Number.isFinite(word.score)) {
    parts.push(`Score: ${Number(word.score).toFixed(4)}`);
  }
  parts.push(`BBox: ${Math.round(word.rect.x0)}, ${Math.round(word.rect.y0)}, ${Math.round(word.rect.x1)}, ${Math.round(word.rect.y1)}`);
  return parts.join('\n');
}

function fitTextSvg(el, text, xScale = 1.03, yScale = 1.6) {
  const font = [
    'Arial, Helvetica, sans-serif',
    'Tahoma, Geneva, sans-serif',
    'Trebuchet MS, sans-serif',
    'Impact, sans-serif',
    'system-ui, sans-serif',
    'Georgia, serif'
  ].join(', ');

  const NS = 'http://www.w3.org/2000/svg';

  const w = el.clientWidth;
  const h = el.clientHeight;

  el.innerHTML = '';
  if (!(w > 0) || !(h > 0)) {
    return;
  }

  const svg = document.createElementNS(NS, 'svg');
  svg.setAttribute('width', '100%');
  svg.setAttribute('height', '100%');
  svg.setAttribute('viewBox', `0 0 ${w} ${h}`);

  const txt = document.createElementNS(NS, 'text');
  txt.textContent = text;
  txt.setAttribute('x', '0');
  txt.setAttribute('y', '0');
  txt.setAttribute('font-size', '100');
  txt.setAttribute('font-family', font);
  txt.setAttribute('xml:space', 'preserve');

  svg.appendChild(txt);
  el.appendChild(svg);

  const bbox = txt.getBBox();
  if (!bbox.width || !bbox.height) {
    return;
  }

  const baseScaleX = w / bbox.width;
  const baseScaleY = h / bbox.height;

  const sx = baseScaleX * xScale;
  const sy = baseScaleY * yScale;

  const finalW = bbox.width * sx;
  const finalH = bbox.height * sy;

  const tx = -bbox.x * sx + (w - finalW) / 2;
  const ty = -bbox.y * sy + (h - finalH) / 2;

  txt.setAttribute(
    'transform',
    `matrix(${sx},0,0,${sy},${tx},${ty})`
  );
}

function renderObjectOcrPage(page, pageMatches, objectScale) {
  const wrapperEl = document.createElement('div');
  wrapperEl.className = 'ocr-page ocr-page--objects';
  wrapperEl.dataset.pageNumber = String(page.number);
  wrapperEl.style.width = `${Math.ceil(page.pageWidth * objectScale)}px`;
  wrapperEl.style.height = `${Math.ceil(page.pageHeight * objectScale)}px`;
  appendOcrPageImage(wrapperEl, page.number);

  const surfaceEl = document.createElement('div');
  surfaceEl.className = 'ocr-page-surface';
  surfaceEl.style.width = `${Math.ceil(page.pageWidth * objectScale)}px`;
  surfaceEl.style.height = `${Math.ceil(page.pageHeight * objectScale)}px`;

  const matchLookup = buildObjectPageMatchLookup(page, pageMatches);
  const wordElements = new Map();
  const wordsToFit = [];

  const layeredWords = [...page.words].sort((left, right) => {
    const leftArea = left.rect.width * left.rect.height;
    const rightArea = right.rect.width * right.rect.height;
    if (leftArea !== rightArea) {
      return rightArea - leftArea;
    }
    if (left.rect.y0 !== right.rect.y0) {
      return left.rect.y0 - right.rect.y0;
    }
    return left.rect.x0 - right.rect.x0;
  });

  layeredWords.forEach((word) => {
    const wordEl = document.createElement('div');
    wordEl.className = 'ocr-word-box';
    if (matchLookup.matchedWordIndexes.has(word.index)) {
      wordEl.classList.add('is-match');
    }
    if (matchLookup.activeWordIndexes.has(word.index)) {
      wordEl.classList.add('is-active');
    }
    const scaledLeft = word.rect.x0 * objectScale;
    const scaledTop = word.rect.y0 * objectScale;
    const scaledWidth = Math.max(1, word.rect.width * objectScale);
    const scaledHeight = Math.max(1, word.rect.height * objectScale);
    wordEl.style.left = `${scaledLeft}px`;
    wordEl.style.top = `${scaledTop}px`;
    wordEl.style.width = `${scaledWidth}px`;
    wordEl.style.height = `${scaledHeight}px`;
    wordEl.title = buildWordTooltip(word);
    surfaceEl.appendChild(wordEl);
    wordElements.set(word.index, wordEl);
    wordsToFit.push({ wordEl, text: word.text });
  });

  wrapperEl.appendChild(surfaceEl);
  ocrPagesViewEl.appendChild(wrapperEl);
  const renderSource = objectRenderSource(currentOcrSource);
  const xScale = Number.isFinite(Number(OCR_OBJECT_TEXT_FIT_X_SCALE_BY_SOURCE[renderSource]))
    ? Number(OCR_OBJECT_TEXT_FIT_X_SCALE_BY_SOURCE[renderSource])
    : 1.03;
  const yScale = Number.isFinite(Number(OCR_OBJECT_TEXT_FIT_Y_SCALE_BY_SOURCE[renderSource]))
    ? Number(OCR_OBJECT_TEXT_FIT_Y_SCALE_BY_SOURCE[renderSource])
    : 1.6;
  wordsToFit.forEach(({ wordEl, text }) => {
    fitTextSvg(wordEl, text, xScale, yScale);
  });

  return {
    ...page,
    wrapperEl,
    surfaceEl,
    wordElements,
    objectScale,
  };
}

function renderTextOcrPage(page, pageMatches, targetWidth) {
  const wrapperEl = document.createElement('div');
  wrapperEl.className = 'ocr-page';
  wrapperEl.dataset.pageNumber = String(page.number);

  appendOcrPageImage(wrapperEl, page.number);

  const highlightEl = document.createElement('pre');
  highlightEl.className = 'ocr-page-highlight';
  highlightEl.innerHTML = buildOcrPageHighlightHtml(page.text, pageMatches);

  const textareaEl = document.createElement('textarea');
  textareaEl.className = 'ocr-page-text';
  textareaEl.readOnly = true;
  textareaEl.wrap = 'off';
  textareaEl.spellcheck = false;
  textareaEl.autocorrect = 'off';
  textareaEl.autocapitalize = 'off';
  textareaEl.value = page.text;
  textareaEl.addEventListener('focus', () => {
    syncOcrHighlightPresentation();
  });
  textareaEl.addEventListener('blur', () => {
    syncOcrHighlightPresentation();
  });

  wrapperEl.appendChild(highlightEl);
  wrapperEl.appendChild(textareaEl);
  ocrPagesViewEl.appendChild(wrapperEl);
  resizeOcrPage(wrapperEl, highlightEl, textareaEl, targetWidth);

  return {
    ...page,
    wrapperEl,
    highlightEl,
    textareaEl,
  };
}

function renderOcrPages() {
  const pages = ocrDocumentPages;
  ocrRenderedPages = [];
  ocrPagesViewEl.innerHTML = '';
  const objectPages = pages.filter((page) => page.renderMode === 'objects');
  const maxObjectPageWidth = objectPages.reduce((maxWidth, page) => Math.max(maxWidth, page.pageWidth || 0), 0);
  const availableWidth = Math.max(360, ocrPagesViewEl.clientWidth - 32);
  const fitScale = maxObjectPageWidth > 0 ? (availableWidth / maxObjectPageWidth) : 1;
  const objectScale = fitScale * (currentOcrZoom / 100);
  let documentTextPageWidth = 0;
  const textPageMeasurements = new Map();

  pages.forEach((page) => {
    if (page.renderMode !== 'text') {
      return;
    }
    const measureEl = document.createElement('textarea');
    measureEl.className = 'ocr-page-text';
    measureEl.readOnly = true;
    measureEl.wrap = 'off';
    measureEl.value = page.text;
    ocrPagesViewEl.appendChild(measureEl);
    const pageWidth = measureOcrPageWidth(measureEl, page.text);
    documentTextPageWidth = Math.max(documentTextPageWidth, pageWidth);
    textPageMeasurements.set(page.number, pageWidth);
    measureEl.remove();
  });

  pages.forEach((page) => {
    const pageMatches = ocrSearchMatches
      .map((match, globalIndex) => ({
        start: Math.max(match.start, page.start) - page.start,
        end: Math.min(match.end, page.end) - page.start,
        globalIndex,
      }))
      .filter((match) => match.start < match.end);

    if (page.renderMode === 'objects') {
      ocrRenderedPages.push(renderObjectOcrPage(page, pageMatches, objectScale));
      return;
    }

    ocrRenderedPages.push(
      renderTextOcrPage(page, pageMatches, documentTextPageWidth || textPageMeasurements.get(page.number) || null)
    );
  });

  syncOcrHighlightPresentation();
  updateOcrPageControls();
}

function rerenderOcrPagesPreservingScroll() {
  const scrollTop = ocrPagesViewEl.scrollTop;
  const scrollLeft = ocrPagesViewEl.scrollLeft;
  renderOcrPages();
  window.requestAnimationFrame(() => {
    ocrPagesViewEl.scrollTop = scrollTop;
    ocrPagesViewEl.scrollLeft = scrollLeft;
    syncOcrHighlightScroll();
    updateOcrPageControls();
  });
}

function setOcrDocumentText(rawText) {
  currentOcrDocumentMode = 'text';
  ocrDocumentPages = normalizeOcrPages([], rawText, 'text');
  ocrViewEl.value = ocrDocumentPages.map((page) => page.searchText || page.text).join('\n\n');
  renderOcrPages();
}

function setOcrDocumentPages(pages, fallbackText = '', mode = 'text') {
  currentOcrDocumentMode = mode;
  ocrDocumentPages = normalizeOcrPages(pages, fallbackText, mode);
  ocrViewEl.value = ocrDocumentPages.map((page) => page.searchText || page.text).join('\n\n');
  renderOcrPages();
}

function setOcrSearchStatus(text, isError = false) {
  ocrSearchStatusEl.textContent = text;
  ocrSearchStatusEl.classList.toggle('is-error', isError);
}

function setOcrSearchButtonsEnabled(enabled) {
  ocrSearchPrevEl.disabled = !enabled;
  ocrSearchNextEl.disabled = !enabled;
}

function syncOcrHighlightPresentation() {
  const activeElement = document.activeElement;
  const selectionMode = activeElement instanceof HTMLElement
    && activeElement.classList.contains('ocr-page-text');
  ocrPagesViewEl.classList.toggle('is-selection-mode', selectionMode);
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
    renderOcrPages();
    return;
  }

  try {
    ocrSearchMatches = buildOcrSearchMatches(text, query, ocrSearchRegexEl.checked);
  } catch (error) {
    ocrSearchMatches = [];
    ocrSearchActiveIndex = -1;
    setOcrSearchButtonsEnabled(false);
    setOcrSearchStatus('Ogiltig regex', true);
    renderOcrPages();
    return;
  }

  if (ocrSearchMatches.length === 0) {
    ocrSearchActiveIndex = -1;
    setOcrSearchButtonsEnabled(false);
    setOcrSearchStatus('0 träffar');
    renderOcrPages();
    return;
  }

  setOcrSearchButtonsEnabled(true);
  ocrSearchActiveIndex = -1;
  setOcrSearchStatus(`${ocrSearchMatches.length} träffar`);
  renderOcrPages();
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
  renderOcrPages();
}

function syncOcrHighlightScroll() {
  return;
}

function scrollOcrMatchIntoView(match) {
  const page = ocrRenderedPages.find((entry) => match.start >= entry.start && match.start <= entry.end);
  if (!page) {
    return;
  }

  if (page.renderMode === 'objects') {
    const activeEntries = (page.searchEntries || []).filter(
      (entry) => entry.start < (match.end - page.start) && entry.end > (match.start - page.start)
    );
    const targetEntry = activeEntries[0] || null;
    const targetWordEl = targetEntry ? page.wordElements.get(targetEntry.wordIndex) : null;
    if (!targetWordEl) {
      return;
    }

    const pageRect = ocrPagesViewEl.getBoundingClientRect();
    const targetRect = targetWordEl.getBoundingClientRect();
    const targetTop = ocrPagesViewEl.scrollTop + (targetRect.top - pageRect.top) - (ocrPagesViewEl.clientHeight / 3);
    const targetLeft = ocrPagesViewEl.scrollLeft + (targetRect.left - pageRect.left) - 40;
    ocrPagesViewEl.scrollTop = Math.max(0, targetTop);
    ocrPagesViewEl.scrollLeft = Math.max(0, targetLeft);
    syncOcrHighlightScroll();
    return;
  }

  const localIndex = Math.max(0, match.start - page.start);
  const textBeforeMatch = page.text.slice(0, localIndex);
  const lineIndex = textBeforeMatch.split('\n').length - 1;
  const lineStart = textBeforeMatch.lastIndexOf('\n') + 1;
  const columnIndex = localIndex - lineStart;
  const styles = window.getComputedStyle(page.textareaEl);
  const lineHeight = parseFloat(styles.lineHeight) || 17.4;
  const fontSize = parseFloat(styles.fontSize) || 12;
  const approxCharWidth = fontSize * 0.6;
  const topPadding = parseFloat(styles.paddingTop) || 0;
  const leftPadding = parseFloat(styles.paddingLeft) || 0;
  const targetTop = Math.max(
    0,
    page.wrapperEl.offsetTop + topPadding + (lineIndex * lineHeight) - (ocrPagesViewEl.clientHeight / 3)
  );
  const targetLeft = Math.max(0, leftPadding + (columnIndex * approxCharWidth) - 40);
  ocrPagesViewEl.scrollTop = targetTop;
  ocrPagesViewEl.scrollLeft = targetLeft;
  syncOcrHighlightScroll();
}

function applySelectedJobId(jobId, options = {}) {
  const syncHash = options.syncHash !== false;
  const selectedJob = findJobById(jobId);
  selectedJobId = selectedJob ? selectedJob.id : '';
  syncReviewViewModeAvailability(selectedJob);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  setViewerJob(selectedJobId);
  selectedJobStateSig = jobStateSignature(selectedJob);
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

  const currentJobs = displayedJobsForCurrentListMode();
  if (!Array.isArray(currentJobs) || currentJobs.length === 0) {
    return;
  }

  const currentIndex = currentJobs.findIndex((job) => job.id === selectedJobId);
  const safeCurrent = currentIndex >= 0 ? currentIndex : 0;
  const nextIndex = Math.max(0, Math.min(currentJobs.length - 1, safeCurrent + offset));
  if (nextIndex === safeCurrent && currentIndex >= 0) {
    return;
  }

  const targetJob = currentJobs[nextIndex];
  if (!targetJob) {
    return;
  }

  applySelectedJobId(targetJob.id);
}

function refreshSelection() {
  if (preferredJobIdFromHash) {
    const preferredJob = findJobById(preferredJobIdFromHash);
    if (preferredJob && isJobVisibleInCurrentList(preferredJob.id)) {
      applySelectedJobId(preferredJob.id, { syncHash: false });
      updateHashState();
      return;
    }
  }

  const visibleJobs = displayedJobsForCurrentListMode();
  if (!Array.isArray(visibleJobs) || visibleJobs.length === 0) {
    if (currentJobListMode === 'processing' && Array.isArray(state.failedJobs) && state.failedJobs.length > 0) {
      applySelectedJobId(state.failedJobs[0].id, { syncHash: false });
      updateHashState();
      return;
    }
    applySelectedJobId('', { syncHash: false });
    updateHashState();
    return;
  }

  const currentSelection = findJobById(selectedJobId);
  if (currentSelection && isJobVisibleInCurrentList(currentSelection.id)) {
    const nextJobStateSig = jobStateSignature(currentSelection);
    const shouldRefreshViewer = currentSelection.status !== 'processing'
      && nextJobStateSig !== selectedJobStateSig;
    selectedJobId = currentSelection.id;
    syncReviewViewModeAvailability(currentSelection);
    if (shouldRefreshViewer) {
      setViewerJob(currentSelection.id);
    }
    selectedJobStateSig = nextJobStateSig;
    setClientForJob(currentSelection);
    setSenderForJob(currentSelection);
    setCategoryForJob(currentSelection);
    syncFilenameField(currentSelection);
    updateArchiveAction(currentSelection);
    updateHashState();
    return;
  }

  applySelectedJobId(visibleJobs[0].id, { syncHash: false });
  updateHashState();
}

function applyState(nextState) {
  const previousDisplayedReadyJobs = buildDisplayedReadyJobs(state.processingJobs, state.readyJobs);
  previousDisplayedReadyJobs.forEach((job, index) => {
    if (!job || typeof job.id !== 'string' || job.id === '') {
      return;
    }
    lastKnownJobDisplayById.set(job.id, { ...job, _displayOrder: index });
  });

  const shouldUpdateClients = Array.isArray(nextState.clients);
  const shouldUpdateSenders = Array.isArray(nextState.senders);
  const shouldUpdateCategories = Array.isArray(nextState.categories);
  const shouldUpdateArchivingRules = nextState.archivingRules && typeof nextState.archivingRules === 'object';
  const nextArchivingRules = shouldUpdateArchivingRules
    ? normalizeArchivingRulesStatePayload(nextState.archivingRules, state.archivingRules)
    : state.archivingRules;

  state = {
    processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : state.processingJobs,
    readyJobs: Array.isArray(nextState.readyJobs) ? nextState.readyJobs : state.readyJobs,
    archivedJobs: Array.isArray(nextState.archivedJobs) ? nextState.archivedJobs : state.archivedJobs,
    failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : state.failedJobs,
    clients: shouldUpdateClients ? nextState.clients : state.clients,
    senders: shouldUpdateSenders ? nextState.senders : state.senders,
    categories: shouldUpdateCategories ? nextState.categories : state.categories,
    archivingRules: nextArchivingRules
  };

  const validJobIds = new Set(
    []
      .concat(Array.isArray(state.readyJobs) ? state.readyJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.archivedJobs) ? state.archivedJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.processingJobs) ? state.processingJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.failedJobs) ? state.failedJobs.map((job) => job.id) : [])
  );
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
  Array.from(filenameByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      filenameByJobId.delete(jobId);
    }
  });

  const activeJobIds = new Set(
    []
      .concat(Array.isArray(state.processingJobs) ? state.processingJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.readyJobs) ? state.readyJobs.map((job) => job.id) : [])
      .concat(Array.isArray(state.archivedJobs) ? state.archivedJobs.map((job) => job.id) : [])
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
  Array.from(pinnedProcessingOrderById.keys()).forEach((jobId) => {
    if (!activeProcessingJobIds.has(jobId)) {
      pinnedProcessingOrderById.delete(jobId);
    }
  });

  setProcessingInfo(state.processingJobs);
  notifyFailedJobs(state.failedJobs);
  renderAppNotices();
  syncArchivingReviewTabIndicator();
  syncArchivingReviewPayloadFromState();
  if (shouldUpdateClients) {
    renderClientSelect(state.clients);
  }
  if (shouldUpdateSenders) {
    renderSenderSelect(state.senders);
  }
  if (shouldUpdateCategories) {
    renderCategorySelect(state.categories);
  }
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
  if (!hasLoadedInitialJobsState) {
    hasLoadedInitialJobsState = true;
  }
}

function cloneJobList(list) {
  return Array.isArray(list)
    ? list
      .filter((job) => job && typeof job === 'object')
      .map((job) => ({ ...job }))
    : [];
}

function sortJobsForList(listKey, jobs) {
  const sorted = cloneJobList(jobs);
  sorted.sort((a, b) => {
    const aValue = listKey === 'archivedJobs'
      ? String(a.archivedAt || a.createdAt || '')
      : String(a.createdAt || '');
    const bValue = listKey === 'archivedJobs'
      ? String(b.archivedAt || b.createdAt || '')
      : String(b.createdAt || '');
    return bValue.localeCompare(aValue);
  });
  return sorted;
}

function insertJobAtIndex(list, job, index) {
  const next = cloneJobList(list);
  const normalizedIndex = Number.isInteger(index) ? index : next.length;
  const targetIndex = Math.max(0, Math.min(next.length, normalizedIndex));
  next.splice(targetIndex, 0, { ...job });
  return next;
}

function removeJobFromAllLists(nextState, jobId) {
  nextState.processingJobs = nextState.processingJobs.filter((job) => job && job.id !== jobId);
  nextState.readyJobs = nextState.readyJobs.filter((job) => job && job.id !== jobId);
  nextState.archivedJobs = nextState.archivedJobs.filter((job) => job && job.id !== jobId);
  nextState.failedJobs = nextState.failedJobs.filter((job) => job && job.id !== jobId);
}

function captureReadyListPosition(jobId, processingJobs, readyJobs) {
  if (typeof jobId !== 'string' || jobId === '') {
    return;
  }

  const displayedReadyJobs = buildDisplayedReadyJobs(processingJobs, readyJobs);
  const readyIndex = displayedReadyJobs.findIndex((job) => job && job.id === jobId);
  if (readyIndex < 0) {
    return;
  }

  const snapshot = displayedReadyJobs[readyIndex];
  if (!snapshot || typeof snapshot !== 'object') {
    return;
  }

  lastKnownJobDisplayById.set(jobId, {
    ...snapshot,
    _displayOrder: readyIndex,
  });
  pinnedProcessingOrderById.set(jobId, readyIndex);
}

function applyJobEvents(events) {
  if (!Array.isArray(events) || events.length === 0) {
    return;
  }

  const nextState = {
    processingJobs: cloneJobList(state.processingJobs),
    readyJobs: cloneJobList(state.readyJobs),
    archivedJobs: cloneJobList(state.archivedJobs),
    failedJobs: cloneJobList(state.failedJobs),
    clients: state.clients,
    senders: state.senders,
    categories: state.categories,
    archivingRules: state.archivingRules,
  };
  let mutated = false;
  let archivingRulesMutated = false;

  events.forEach((eventPayload) => {
    if (!eventPayload || typeof eventPayload !== 'object') {
      return;
    }

    const eventId = Number.parseInt(String(eventPayload.id || ''), 10);
    if (Number.isInteger(eventId) && eventId > stateEventCursor) {
      stateEventCursor = eventId;
    }

    if (eventPayload.type === 'archivingRules.update') {
      if (eventPayload.archivingRules && typeof eventPayload.archivingRules === 'object') {
        const nextArchivingRules = normalizeArchivingRulesStatePayload(eventPayload.archivingRules, nextState.archivingRules);
        if ((nextArchivingRules.signature || '') !== (nextState.archivingRules && nextState.archivingRules.signature || '')) {
          nextState.archivingRules = nextArchivingRules;
          archivingRulesMutated = true;
        }
      }
      return;
    }

    if (eventPayload.type === 'job.remove') {
      const jobId = typeof eventPayload.jobId === 'string' ? eventPayload.jobId.trim() : '';
      if (!jobId) {
        return;
      }
      pinnedProcessingJobIds.delete(jobId);
      pinnedProcessingOrderById.delete(jobId);
      removeJobFromAllLists(nextState, jobId);
      mutated = true;
      return;
    }

    if (eventPayload.type !== 'job.upsert') {
      return;
    }

    const listKey = typeof eventPayload.list === 'string' ? eventPayload.list.trim() : '';
    const validList = listKey === 'processingJobs'
      || listKey === 'readyJobs'
      || listKey === 'archivedJobs'
      || listKey === 'failedJobs';
    const job = eventPayload.job && typeof eventPayload.job === 'object' ? eventPayload.job : null;
    const jobId = job && typeof job.id === 'string' ? job.id.trim() : '';
    if (!validList || !job || !jobId) {
      return;
    }

    const existingTargetJob = (Array.isArray(nextState[listKey]) ? nextState[listKey] : []).find((entry) => entry && entry.id === jobId) || null;
    const existsInOtherList = ['processingJobs', 'readyJobs', 'archivedJobs', 'failedJobs']
      .filter((key) => key !== listKey)
      .some((key) => Array.isArray(nextState[key]) && nextState[key].some((entry) => entry && entry.id === jobId));
    if (
      !existsInOtherList
      && existingTargetJob
      && JSON.stringify(existingTargetJob) === JSON.stringify(job)
    ) {
      return;
    }

    if (listKey === 'processingJobs' && eventPayload.preserveListPosition === true) {
      captureReadyListPosition(jobId, nextState.processingJobs, nextState.readyJobs);
      pinnedProcessingJobIds.add(jobId);
      const existingProcessingJob = nextState.processingJobs.find((entry) => entry && entry.id === jobId) || null;
      if (existingProcessingJob && existingProcessingJob.status === 'processing') {
        return;
      }
    } else if (listKey !== 'processingJobs') {
      pinnedProcessingJobIds.delete(jobId);
      const pinnedOrder = pinnedProcessingOrderById.get(jobId);
      if (typeof pinnedOrder === 'number') {
        lastKnownJobDisplayById.set(jobId, {
          ...job,
          _displayOrder: pinnedOrder,
        });
      }
    }

    removeJobFromAllLists(nextState, jobId);
    if (listKey === 'readyJobs') {
      const preferredIndex = (() => {
        const snapshot = lastKnownJobDisplayById.get(jobId);
        if (snapshot && typeof snapshot._displayOrder === 'number') {
          return snapshot._displayOrder;
        }
        const pinnedOrder = pinnedProcessingOrderById.get(jobId);
        return typeof pinnedOrder === 'number' ? pinnedOrder : null;
      })();
      if (preferredIndex !== null) {
        nextState.readyJobs = insertJobAtIndex(nextState.readyJobs, job, preferredIndex);
      } else {
        nextState.readyJobs.push({ ...job });
        nextState.readyJobs = sortJobsForList(listKey, nextState.readyJobs);
      }
    } else {
      nextState[listKey].push({ ...job });
      nextState[listKey] = sortJobsForList(listKey, nextState[listKey]);
    }
    mutated = true;
  });

  if (mutated) {
    applyState(nextState);
    return;
  }

  if (archivingRulesMutated) {
    applyArchivingRulesStatePayload(nextState.archivingRules);
  }
}

function applyOptimisticReprocess(jobId) {
  const sourceJob = findJobById(jobId);
  if (!sourceJob) {
    return false;
  }

  captureReadyListPosition(jobId, state.processingJobs, state.readyJobs);
  pinnedProcessingJobIds.add(jobId);
  clearOcrViewCache();

  const processingJob = {
    ...sourceJob,
    status: 'processing',
    error: null
  };

  applyState({
    processingJobs: sortJobsForList('processingJobs', [
      processingJob,
      ...state.processingJobs.filter((job) => job && job.id !== jobId)
    ]),
    readyJobs: state.readyJobs.filter((job) => job && job.id !== jobId),
    failedJobs: state.failedJobs.filter((job) => job && job.id !== jobId)
  });

  if (selectedJobId === jobId) {
    loadedOcrJobId = '';
    loadedOcrSource = '';
    loadedMatchesJobId = '';
    loadedMetaJobId = '';
    clearPdfFrames();
  }

  return true;
}

function openSettingsModal() {
  settingsModalEl.classList.remove('hidden');
}

function settingsPanelEl(tabId) {
  return document.getElementById('settings-panel-' + tabId);
}

function restoreSettingsFooterActions(panelId) {
  if (!panelId) {
    return;
  }
  const panel = settingsPanelEl(panelId);
  if (!(panel instanceof HTMLElement)) {
    return;
  }
  const actionRow = panel._settingsFooterActionRow;
  const placeholder = panel._settingsFooterActionPlaceholder;
  if (!(actionRow instanceof HTMLElement) || !(placeholder instanceof HTMLElement) || placeholder.parentNode !== panel) {
    return;
  }
  placeholder.replaceWith(actionRow);
  panel._settingsFooterActionRow = null;
  panel._settingsFooterActionPlaceholder = null;
}

function syncSettingsFooterActions(tabId) {
  if (!(settingsPanelActionsHostEl instanceof HTMLElement)) {
    return;
  }
  if (activeSettingsFooterPanelId === tabId) {
    const activePanel = settingsPanelEl(tabId);
    const activeRow = activePanel instanceof HTMLElement ? activePanel._settingsFooterActionRow : null;
    settingsPanelActionsHostEl.replaceChildren();
    if (activeRow instanceof HTMLElement) {
      settingsPanelActionsHostEl.appendChild(activeRow);
      return;
    }
    activeSettingsFooterPanelId = '';
  }
  if (activeSettingsFooterPanelId && activeSettingsFooterPanelId !== tabId) {
    restoreSettingsFooterActions(activeSettingsFooterPanelId);
  }
  settingsPanelActionsHostEl.replaceChildren();
  activeSettingsFooterPanelId = '';

  const panel = settingsPanelEl(tabId);
  if (!(panel instanceof HTMLElement)) {
    return;
  }
  const actionRow = Array.from(panel.children).find((child) => child instanceof HTMLElement && child.classList.contains('panel-actions'));
  if (!(actionRow instanceof HTMLElement)) {
    return;
  }
  const placeholder = document.createElement('div');
  placeholder.className = 'settings-panel-actions-placeholder';
  panel.replaceChild(placeholder, actionRow);
  panel._settingsFooterActionRow = actionRow;
  panel._settingsFooterActionPlaceholder = placeholder;
  settingsPanelActionsHostEl.appendChild(actionRow);
  activeSettingsFooterPanelId = tabId;
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
    clientsListEl = document.getElementById('clients-list');
    clientsAddRowEl = document.getElementById('clients-add-row');
    clientsCancelEl = document.getElementById('clients-cancel');
    clientsApplyEl = document.getElementById('clients-apply');
    clientsAddRowEl.addEventListener('click', () => {
      clientsDraft.push(defaultClientDraft());
      renderClientsEditor();
      updateSettingsActionButtons();
    });
    clientsCancelEl.addEventListener('click', () => {
      let parsed = [];
      try {
        parsed = JSON.parse(clientsBaselineJson);
      } catch (error) {
        parsed = [];
      }
      clientsDraft = Array.isArray(parsed) ? parsed.map(sanitizeClientDraft) : [];
      renderClientsEditor();
      updateSettingsActionButtons();
    });
    clientsApplyEl.addEventListener('click', async () => {
      try {
        await saveClientsSettings();
      } catch (error) {
        alert(error.message || 'Kunde inte spara huvudmän.');
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
	    ocrTextExtractionCommandEl = document.getElementById('ocr-text-extraction-command');
	    jbig2StatusBadgeWrapEl = document.getElementById('jbig2-status-badge-wrap');
	    jbig2StatusBadgeEl = document.getElementById('jbig2-status-badge');
	    jbig2InstallCommandEl = document.getElementById('jbig2-install-command');
	    jbig2RefreshButtonEl = document.getElementById('jbig2-refresh-button');
      jbig2LocalInstallButtonEl = document.getElementById('jbig2-local-install-button');
	    pythonStatusCardEl = document.getElementById('python-status-card');
	    pythonStatusBadgeWrapEl = document.getElementById('python-status-badge-wrap');
	    pythonStatusBadgeEl = document.getElementById('python-status-badge');
	    pythonInstallCommandEl = document.getElementById('python-install-command');
	    pythonRefreshButtonEl = document.getElementById('python-refresh-button');
      pythonLocalInstallButtonEl = document.getElementById('python-local-install-button');
	    rapidocrStatusBadgeWrapEl = document.getElementById('rapidocr-status-badge-wrap');
	    rapidocrStatusBadgeEl = document.getElementById('rapidocr-status-badge');
	    rapidocrInstallCommandEl = document.getElementById('rapidocr-install-command');
	    rapidocrRefreshButtonEl = document.getElementById('rapidocr-refresh-button');
      rapidocrInstallLogButtonEl = document.getElementById('rapidocr-install-log-button');
      rapidocrLocalInstallButtonEl = document.getElementById('rapidocr-local-install-button');
      bindSettingsCommandCopyButtons(settingsPanelEl(tabId));
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
        await loadOcrProcessingSettings({ deferRefreshVisibility: true, statusTarget: 'jbig2' });
      } catch (error) {
        renderJbig2Status(null, { deferRefreshVisibility: true });
        alert('Kunde inte kontrollera JBIG2-status.');
      }
    });
    pythonRefreshButtonEl.addEventListener('click', async () => {
      startStatusRefreshSpin(pythonRefreshButtonEl);
      pythonStatusBadgeWrapEl.classList.add('is-collapsed');
      try {
        await loadOcrProcessingSettings({ deferRefreshVisibility: true, statusTarget: 'python' });
      } catch (error) {
        renderPythonStatus(null, { deferRefreshVisibility: true });
        alert('Kunde inte kontrollera Python 3-status.');
      }
    });
    rapidocrRefreshButtonEl.addEventListener('click', async () => {
      startStatusRefreshSpin(rapidocrRefreshButtonEl);
      rapidocrStatusBadgeWrapEl.classList.add('is-collapsed');
      try {
        await loadOcrProcessingSettings({ deferRefreshVisibility: true, statusTarget: 'rapidocr' });
      } catch (error) {
        renderRapidocrStatus(null, { deferRefreshVisibility: true });
        alert('Kunde inte kontrollera RapidOCR-status.');
      }
    });
    rapidocrLocalInstallButtonEl.addEventListener('click', async () => {
      try {
        await installLocalTool('rapidocr');
      } catch (error) {
        alert(error.message || 'Kunde inte installera RapidOCR lokalt.');
      }
    });
    rapidocrInstallLogButtonEl.addEventListener('click', async () => {
      try {
        const log = await fetchLocalToolInstallLog('rapidocr');
        alert(log || 'Ingen installationslogg finns ännu.');
      } catch (error) {
        alert(error.message || 'Kunde inte läsa installationsloggen.');
      }
    });
    renderJbig2Status(null);
    renderPythonStatus(null);
    renderRapidocrStatus(null);
    renderOcrProcessingCommand();
  } else if (tabId === 'categories') {
    categoriesListEl = document.getElementById('categories-list');
    categoriesAddCategoryEl = document.getElementById('categories-add-category');
    categoriesCancelEl = document.getElementById('categories-cancel');
    categoriesApplyEl = document.getElementById('categories-apply');
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
      categoriesDraft = Array.isArray(parsed) ? parsed.map(sanitizeArchiveFolder) : [];
      renderCategoriesEditor();
      updateSettingsActionButtons();
    });
    categoriesApplyEl.addEventListener('click', async () => {
      try {
        await saveCategories();
      } catch (error) {
        alert(error.message || 'Kunde inte spara arkivstruktur.');
      }
    });
  } else if (tabId === 'labels') {
    labelsListEl = document.getElementById('labels-list');
    systemLabelEditorEl = document.getElementById('system-label-editor');
    labelsAddRowEl = document.getElementById('labels-add-row');
    labelsCancelEl = document.getElementById('labels-cancel');
    labelsApplyEl = document.getElementById('labels-apply');
    labelsAddRowEl.addEventListener('click', () => {
      labelsDraft.push(defaultLabel());
      renderLabelsEditor();
      updateSettingsActionButtons();
    });
    labelsCancelEl.addEventListener('click', () => {
      let parsed = [];
      try {
        parsed = JSON.parse(labelsBaselineJson);
      } catch (error) {
        parsed = {};
      }
      labelsDraft = Array.isArray(parsed.labels) ? parsed.labels.map(sanitizeLabel) : [];
      systemLabelsDraft = sanitizeSystemLabels(parsed.systemLabels);
      renderLabelsEditor();
      updateSettingsActionButtons();
    });
    labelsApplyEl.addEventListener('click', async () => {
      try {
        await saveLabels();
      } catch (error) {
        alert(error.message || 'Kunde inte spara etiketter.');
      }
    });
  } else if (tabId === 'data-fields') {
    extractionFieldsEditorEl = document.getElementById('extraction-fields-editor');
    systemExtractionFieldsEditorEl = document.getElementById('system-extraction-fields-editor');
    extractionFieldsAddRowEl = document.getElementById('extraction-fields-add-row');
    extractionFieldsCancelEl = document.getElementById('extraction-fields-cancel');
    extractionFieldsApplyEl = document.getElementById('extraction-fields-apply');
    extractionFieldsTabEls = Array.from(document.querySelectorAll('[data-extraction-fields-tab]'));
    extractionFieldsViewCustomEl = document.getElementById('extraction-fields-view-custom');
    extractionFieldsViewSystemEl = document.getElementById('extraction-fields-view-system');
    extractionFieldsTabEls.forEach((tabButton) => {
      tabButton.addEventListener('click', () => {
        const nextTabId = tabButton.dataset.extractionFieldsTab;
        if (!nextTabId || nextTabId === activeExtractionFieldsTabId) {
          return;
        }
        setExtractionFieldsTab(nextTabId);
      });
    });
    extractionFieldsAddRowEl.addEventListener('click', () => {
      if (activeExtractionFieldsTabId !== 'fields') {
        return;
      }
      extractionFieldsDraft.push(defaultExtractionField());
      renderExtractionFieldsEditor();
      renderSystemExtractionFieldsEditor();
      updateSettingsActionButtons();
    });
    extractionFieldsCancelEl.addEventListener('click', () => {
      let parsed = {};
      try {
        parsed = JSON.parse(extractionFieldsBaselineJson);
      } catch (error) {
        parsed = {};
      }
      extractionFieldsDraft = Array.isArray(parsed.fields)
        ? parsed.fields.map((field, index) => sanitizeExtractionField(field, index))
        : [];
      predefinedExtractionFieldsDraft = Array.isArray(parsed.predefinedFields)
        ? parsed.predefinedFields.map((field, index) => sanitizeExtractionField(field, index))
        : [];
      systemExtractionFieldsDraft = Array.isArray(parsed.systemFields)
        ? parsed.systemFields.map((field, index) => sanitizeExtractionField(field, index))
        : [];
      renderExtractionFieldsEditor();
      renderSystemExtractionFieldsEditor();
      updateSettingsActionButtons();
    });
    extractionFieldsApplyEl.addEventListener('click', async () => {
      try {
        await saveExtractionFields();
      } catch (error) {
        alert(error.message || 'Kunde inte spara datafält.');
      }
    });
    setExtractionFieldsTab('fields');
  } else if (tabId === 'archiving-review') {
    archivingReviewStatusEl = document.getElementById('archiving-review-status');
    archivingReviewSummaryEl = document.getElementById('archiving-review-summary');
    archivingReviewJobsEl = document.getElementById('archiving-review-jobs');
    archivingReviewResetDraftEl = document.getElementById('archiving-review-reset-draft');
    archivingReviewPublishEl = document.getElementById('archiving-review-publish');
    archivingReviewResetDraftEl.addEventListener('click', async () => {
      try {
        await resetArchivingRulesDraft();
      } catch (error) {
        alert(error.message || 'Kunde inte kassera utkastet.');
      }
    });
    archivingReviewPublishEl.addEventListener('click', async () => {
      try {
        await publishArchivingRules();
      } catch (error) {
        alert(error.message || 'Kunde inte aktivera reglerna.');
      }
    });
  } else if (tabId === 'jobs') {
    settingsResetJobsEl = document.getElementById('settings-reset-jobs');
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
  } else if (tabId === 'system') {
    systemStateTransportEl = document.getElementById('system-state-transport');
    systemStateTransportEl.value = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
    systemStateTransportEl.addEventListener('change', async () => {
      const previousTransport = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
      const nextTransport = sanitizeStateUpdateTransport(systemStateTransportEl.value, previousTransport);
      if (nextTransport === previousTransport) {
        systemStateTransportEl.value = previousTransport;
        return;
      }

      systemStateTransportEl.disabled = true;
      try {
        const savedTransport = await saveStateTransportSetting(nextTransport);
        systemStateTransportEl.value = savedTransport;
        syncStateUpdateTransport();
        if (savedTransport === 'polling') {
          scheduleStatePoll(0);
        }
      } catch (error) {
        systemStateTransportEl.value = previousTransport;
        alert(error.message || 'Kunde inte spara uppdateringsmetod.');
      } finally {
        systemStateTransportEl.disabled = false;
      }
    });
  }

  boundSettingsPanels.add(tabId);
}

async function ensureSettingsPanelReady(tabId, options = {}) {
  bindSettingsPanelRefs(tabId);
  if (tabId === 'system' && systemStateTransportEl) {
    systemStateTransportEl.value = sanitizeStateUpdateTransport(stateUpdateTransport, 'polling');
  }

  const reload = options.reload === true;
  if (loadedSettingsPanels.has(tabId) && !reload) {
    return true;
  }

  if (tabId === 'clients') {
    await loadClientsSettings();
  } else if (tabId === 'senders') {
    await loadSendersSettings();
  } else if (tabId === 'matching') {
    await loadMatchingSettings();
  } else if (tabId === 'ocr-processing') {
    await loadOcrProcessingSettings(options);
  } else if (tabId === 'categories') {
    await Promise.all([loadCategories(), loadExtractionFields(), loadLabels()]);
    renderCategoriesEditor();
  } else if (tabId === 'labels') {
    await loadLabels();
  } else if (tabId === 'data-fields') {
    await loadExtractionFields();
  } else if (tabId === 'archiving-review') {
    await loadArchivingRuleReview();
  } else if (tabId === 'paths') {
    await loadPathSettings();
  } else if (tabId === 'system') {
    // State transport setting is hydrated from /api/get-state.php and saved via /api/save-config.php.
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
    clientsDraft = [];
    clientsBaselineJson = normalizedClientsJson(clientsDraft);
    renderClientsEditor();
    updateSettingsActionButtons();
    return false;
  }

  focusFirstClientsField();
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
    categoriesBaselineJson = JSON.stringify(categoriesDraft.map(sanitizeArchiveFolder));
    renderCategoriesEditor();
    updateSettingsActionButtons();
    return false;
  }

  categoriesAddCategoryEl.focus();
  updateSettingsActionButtons();
  return true;
}

function closeSettingsModal(force = false) {
  if (!force && !canLeaveCurrentSettingsView()) {
    return false;
  }

  restoreSettingsFooterActions(activeSettingsFooterPanelId);
  if (settingsPanelActionsHostEl instanceof HTMLElement) {
    settingsPanelActionsHostEl.replaceChildren();
  }
  activeSettingsFooterPanelId = '';
  closeSenderMergeOverlay();
  stopRapidocrInstallPolling();
  settingsModalEl.classList.add('hidden');
  return true;
}

function setSettingsTab(tabId) {
  if (activeSettingsTabId === 'ocr-processing' && tabId !== 'ocr-processing') {
    stopRapidocrInstallPolling();
  }
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

  const panelIds = ['clients', 'senders', 'matching', 'ocr-processing', 'categories', 'labels', 'data-fields', 'archiving-review', 'jobs', 'paths', 'system'];
  panelIds.forEach((id) => {
    const panel = document.getElementById('settings-panel-' + id);
    if (!panel) {
      return;
    }
    panel.classList.toggle('hidden', id !== tabId);
    panel.classList.toggle('active', id === tabId);
  });

  syncSettingsFooterActions(tabId);
}

function isEditableSettingsTab(tabId) {
  return tabId === 'clients'
    || tabId === 'senders'
    || tabId === 'matching'
    || tabId === 'ocr-processing'
    || tabId === 'categories'
    || tabId === 'labels'
    || tabId === 'data-fields'
    || tabId === 'paths';
}

function normalizedPathValue(value) {
  return String(value).trim();
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

function normalizedMatchingJson(replacements) {
  return JSON.stringify({
    replacements: replacements.map(sanitizeReplacement)
  });
}

function normalizedOcrPdfSubstitutionsJson(replacements) {
  return JSON.stringify(replacements.map(sanitizeReplacement));
}

function normalizedSendersJson(senders) {
  return JSON.stringify(senders.map(sanitizeSenderDraft));
}

function normalizedCategoriesJson(categories) {
  return JSON.stringify(categories.map(sanitizeArchiveFolder));
}

function normalizedLabelsJson(labels, systemLabels = systemLabelsDraft) {
  return JSON.stringify({
    labels: labels.map(sanitizeLabel),
    systemLabels: sanitizeSystemLabels(systemLabels),
  });
}

function normalizedExtractionFieldsJson(
  extractionFields,
  predefinedExtractionFields = predefinedExtractionFieldsDraft,
  systemExtractionFields = systemExtractionFieldsDraft
) {
  return JSON.stringify(
    {
      fields: sanitizeExtractionFields(extractionFields),
      predefinedFields: sanitizeExtractionFields(predefinedExtractionFields),
      systemFields: sanitizeExtractionFields(systemExtractionFields),
    }
  );
}

function isClientsDirty() {
  return normalizedClientsJson(clientsDraft) !== clientsBaselineJson;
}

function isMatchingDirty() {
  return normalizedMatchingJson(matchingDraft) !== matchingBaselineJson;
}

function isSendersDirty() {
  return normalizedSendersJson(sendersDraft) !== sendersBaselineJson;
}

function isCategoriesDirty() {
  return normalizedCategoriesJson(categoriesDraft) !== categoriesBaselineJson;
}

function isLabelsDirty() {
  return normalizedLabelsJson(labelsDraft, systemLabelsDraft) !== labelsBaselineJson;
}

function isExtractionFieldsDirty() {
  return normalizedExtractionFieldsJson(extractionFieldsDraft) !== extractionFieldsBaselineJson;
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
  if (tabId === 'labels') {
    return isLabelsDirty();
  }
  if (tabId === 'data-fields') {
    return isExtractionFieldsDirty();
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
  return isClientsDirty() || isSendersDirty() || isMatchingDirty() || isOcrProcessingDirty() || isCategoriesDirty() || isLabelsDirty() || isExtractionFieldsDirty() || isPathsDirty();
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
  if (tabId === 'labels') {
    return [labelsCancelEl, labelsApplyEl];
  }
  if (tabId === 'data-fields') {
    return [extractionFieldsCancelEl, extractionFieldsApplyEl];
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
  const categoriesError = categoriesValidationError();
  const labelsDirty = isLabelsDirty();
  const labelsError = labelsValidationError();
  const extractionFieldsDirty = isExtractionFieldsDirty();
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
    categoriesApplyEl.disabled = !categoriesDirty || categoriesError !== '';
    categoriesApplyEl.title = categoriesError || '';
  }

  if (labelsCancelEl && labelsApplyEl) {
    labelsCancelEl.disabled = !labelsDirty;
    labelsApplyEl.disabled = !labelsDirty || labelsError !== '';
    labelsApplyEl.title = labelsError || '';
  }

  if (extractionFieldsCancelEl && extractionFieldsApplyEl) {
    extractionFieldsCancelEl.disabled = !extractionFieldsDirty;
    extractionFieldsApplyEl.disabled = !extractionFieldsDirty;
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
    type: 'text',
    text: '',
    labelId: '',
    score: 1
  };
}

function defaultCategory() {
  return {
    name: '',
    minScore: 1,
    rules: [defaultRule()],
  };
}

function defaultLabel() {
  return {
    id: '',
    name: '',
    minScore: 1,
    rules: [defaultRule()],
  };
}

function defaultArchiveFolder() {
  return {
    name: '',
    path: '',
    filenameTemplate: {
      parts: [defaultFilenameTemplatePart('text')]
    },
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

function clientUiKey(row) {
  if (row && typeof row.uiKey === 'string' && row.uiKey.trim() !== '') {
    return row.uiKey.trim();
  }
  return `tmp-client-${clientDraftUiKeySeq++}`;
}

function sanitizeClientDraft(row) {
  const input = row && typeof row === 'object' ? row : {};
  const uiKey = clientUiKey(input);

  const firstName = typeof input.firstName === 'string' ? input.firstName : '';
  const lastName = typeof input.lastName === 'string' ? input.lastName : '';

  let folderName = typeof input.folderName === 'string' ? input.folderName : '';
  if (folderName === '' && typeof input.dirName === 'string') {
    folderName = input.dirName;
  }
  if (folderName === '' && typeof input.displayName === 'string') {
    folderName = input.displayName;
  }
  if (folderName === '') {
    folderName = `${firstName} ${lastName}`.trim();
  }

  const pinRaw = input.personalIdentityNumber;
  const personalIdentityNumber = typeof pinRaw === 'string' || typeof pinRaw === 'number'
    ? String(pinRaw)
    : '';

  return {
    uiKey,
    firstName,
    lastName,
    folderName,
    personalIdentityNumber
  };
}

function serializeClientDraft(row) {
  const client = sanitizeClientDraft(row);
  return {
    firstName: client.firstName.trim(),
    lastName: client.lastName.trim(),
    folderName: client.folderName.trim(),
    personalIdentityNumber: client.personalIdentityNumber.trim()
  };
}

function normalizedClientsJson(clients) {
  return JSON.stringify(clients.map(serializeClientDraft));
}

function defaultClientDraft() {
  return sanitizeClientDraft({
    uiKey: `tmp-client-${clientDraftUiKeySeq++}`,
    firstName: '',
    lastName: '',
    folderName: '',
    personalIdentityNumber: ''
  });
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

function applySenderEditorCollapsedState(senderNode, isCollapsed) {
  if (!senderNode) {
    return;
  }
  const toggleButton = senderNode.querySelector('.sender-toggle');
  if (toggleButton) {
    toggleButton.classList.toggle('is-collapsed', isCollapsed);
    const title = isCollapsed ? 'Expandera avsändare' : 'Kontrahera avsändare';
    toggleButton.title = title;
    toggleButton.setAttribute('aria-label', title);
  }
  const senderDetails = senderNode.querySelector('.sender-details');
  if (senderDetails) {
    senderDetails.hidden = isCollapsed;
  }
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
  const rawType = String(input.type || 'text').trim().toLowerCase();
  const type = rawType === 'label' ? 'label' : 'text';
  return {
    type,
    text: typeof input.text === 'string' ? input.text : '',
    labelId: typeof input.labelId === 'string' ? input.labelId : '',
    score: sanitizePositiveInt(input.score, 1)
  };
}

function sanitizeSystemLabelRule(rule) {
  return sanitizeLabelRule(rule);
}

function sanitizeLabelRule(rule) {
  return sanitizeRule(rule);
}

function sanitizeCategoryRule(rule) {
  return sanitizeRule(rule);
}

function sanitizeCategory(category) {
  const input = category && typeof category === 'object' ? category : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeCategoryRule);
  return {
    id: slugifyText(name, '-', ''),
    name,
    minScore: sanitizePositiveInt(input.minScore, 1),
    rules: rules.length > 0 ? rules : [defaultRule()],
  };
}

function sanitizeArchiveFolder(archiveFolder) {
  const input = archiveFolder && typeof archiveFolder === 'object' ? archiveFolder : {};
  const rawCategories = Array.isArray(input.categories) ? input.categories : [];
  const categories = rawCategories.map(sanitizeCategory);
  const migratedFilenameTemplate = input.filenameTemplate && typeof input.filenameTemplate === 'object'
    ? input.filenameTemplate
    : rawCategories.find((category) => category && typeof category === 'object' && category.filenameTemplate && typeof category.filenameTemplate === 'object')?.filenameTemplate;
  return {
    name: typeof input.name === 'string' ? input.name : '',
    path: typeof input.path === 'string' ? input.path : '',
    filenameTemplate: sanitizeFilenameTemplate(migratedFilenameTemplate),
    categories: categories.length > 0 ? categories : [defaultCategory()]
  };
}

function sanitizeLabel(label) {
  const input = label && typeof label === 'object' ? label : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeLabelRule);
  return {
    id: slugifyText(name, '-', ''),
    name,
    minScore: sanitizePositiveInt(input.minScore, 1),
    rules: rules.length > 0 ? rules : [defaultRule()],
  };
}

function sanitizeLabels(labels) {
  return Array.isArray(labels) ? labels.map(sanitizeLabel) : [];
}

function sanitizeSystemLabelByKey(key, label) {
  const defaults = SYSTEM_LABELS[key];
  const input = label && typeof label === 'object' ? label : {};
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeSystemLabelRule);
  const name = typeof input.name === 'string' && input.name.trim() !== ''
    ? input.name
    : defaults.name;
  return {
    id: slugifyText(name, '-', 'label'),
    systemLabelKey: key,
    isSystemLabel: true,
    name,
    minScore: sanitizePositiveInt(input.minScore, sanitizePositiveInt(defaults.minScore, 1)),
    rules: rules.length > 0 ? rules : defaults.rules.map(sanitizeSystemLabelRule),
  };
}

function createDefaultSystemLabels() {
  const labels = {};
  Object.keys(SYSTEM_LABELS).forEach((key) => {
    labels[key] = sanitizeSystemLabelByKey(key, SYSTEM_LABELS[key]);
  });
  return labels;
}

function sanitizeSystemLabels(systemLabels) {
  const input = systemLabels && typeof systemLabels === 'object' ? systemLabels : {};
  const labels = {};
  Object.keys(SYSTEM_LABELS).forEach((key) => {
    labels[key] = sanitizeSystemLabelByKey(key, input[key]);
  });
  return labels;
}

function systemLabelOptions() {
  return Object.values(sanitizeSystemLabels(systemLabelsDraft))
    .map((label) => ({
      value: typeof label.id === 'string' ? label.id.trim() : '',
      label: typeof label.name === 'string' ? label.name.trim() : '',
    }))
    .filter((label) => label.value !== '' && label.label !== '');
}

function categoryRuleLabelOptions() {
  const options = [...systemLabelOptions(), ...sanitizeLabels(labelsDraft)
    .map((label) => ({
      value: typeof label.id === 'string' ? label.id.trim() : '',
      label: typeof label.name === 'string' ? label.name.trim() : '',
    }))
    .filter((label) => label.value !== '' && label.label !== '')];

  const deduped = new Map();
  options.forEach((option) => {
    if (!deduped.has(option.value)) {
      deduped.set(option.value, option);
    }
  });
  return Array.from(deduped.values());
}

function duplicateCategoryIds(folders) {
  const counts = new Map();
  (Array.isArray(folders) ? folders : []).forEach((folder) => {
    const categories = Array.isArray(folder && folder.categories) ? folder.categories : [];
    categories.map(sanitizeCategory).forEach((category) => {
      const id = typeof category.id === 'string' ? category.id.trim() : '';
      if (!id) {
        return;
      }
      counts.set(id, (counts.get(id) || 0) + 1);
    });
  });
  return new Set(Array.from(counts.entries()).filter(([, count]) => count > 1).map(([id]) => id));
}

function categoriesValidationError() {
  const duplicates = duplicateCategoryIds(categoriesDraft);
  if (duplicates.size > 0) {
    return `Kategori-id krockar: ${Array.from(duplicates).join(', ')}`;
  }
  for (const folder of categoriesDraft) {
    const categories = Array.isArray(folder && folder.categories) ? folder.categories : [];
    const blankCategory = categories.map(sanitizeCategory).find((category) => !category.name.trim() || !category.id.trim());
    if (blankCategory) {
      return 'Alla kategorier måste ha ett namn.';
    }
  }
  return '';
}

function duplicateLabelIds(labels, systemLabels = systemLabelsDraft) {
  const counts = new Map();
  Object.values(sanitizeSystemLabels(systemLabels)).forEach((label) => {
    const id = typeof label.id === 'string' ? label.id.trim() : '';
    if (!id) {
      return;
    }
    counts.set(id, (counts.get(id) || 0) + 1);
  });
  labels.map(sanitizeLabel).forEach((label) => {
    const id = typeof label.id === 'string' ? label.id.trim() : '';
    if (!id) {
      return;
    }
    counts.set(id, (counts.get(id) || 0) + 1);
  });
  return new Set(Array.from(counts.entries()).filter(([, count]) => count > 1).map(([id]) => id));
}

function labelsValidationError() {
  const duplicates = duplicateLabelIds(labelsDraft, systemLabelsDraft);
  if (duplicates.size > 0) {
    return `Etikett-id krockar: ${Array.from(duplicates).join(', ')}`;
  }
  const blankLabel = labelsDraft.map(sanitizeLabel).find((label) => !label.name.trim() || !label.id.trim());
  if (blankLabel) {
    return 'Alla etiketter måste ha ett namn.';
  }
  return '';
}

function sanitizeFilenameTemplateParts(parts, depth = 0) {
  if (!Array.isArray(parts) || depth > 6) {
    return [];
  }

  return parts
    .map((part) => sanitizeFilenameTemplatePart(part, depth + 1))
    .filter((part) => part !== null);
}

function sanitizeFilenameTemplateCandidateParts(parts, depth = 0) {
  return sanitizeFilenameTemplateParts(parts, depth)
    .filter((part) => part && typeof part === 'object' && part.type !== 'text');
}

function migrateLegacyFilenameTemplateField(key) {
  const normalizedKey = typeof key === 'string' ? key.trim() : '';
  if (!normalizedKey) {
    return null;
  }

  if (normalizedKey === 'category') {
    return { type: 'category' };
  }
  if (normalizedKey === 'date') {
    return {
      type: 'dataField',
      key: 'due_date',
    };
  }
  if (normalizedKey === 'payee') {
    return {
      type: 'dataField',
      key: 'payment_receiver',
    };
  }
  if (filenameTemplateSystemFieldOptions().some((field) => field.key === normalizedKey)) {
    return {
      type: 'systemField',
      key: normalizedKey,
    };
  }
  if (normalizedKey === 'client' || normalizedKey === 'main_client' || normalizedKey === 'sender') {
    return {
      type: 'field',
      key: normalizedKey,
    };
  }
  return {
    type: 'dataField',
    key: normalizedKey,
  };
}

function sanitizeFilenameTemplatePart(part, depth = 0) {
  const input = part && typeof part === 'object' ? part : null;
  if (!input || depth > 6) {
    return null;
  }

  const type = typeof input.type === 'string' ? input.type.trim() : 'text';
  const prefixParts = sanitizeFilenameTemplateParts(input.prefixParts, depth + 1);
  const suffixParts = sanitizeFilenameTemplateParts(input.suffixParts, depth + 1);
  if (type === 'field') {
    const migrated = migrateLegacyFilenameTemplateField(input.key);
    if (!migrated) {
      return null;
    }
    return {
      ...migrated,
      prefixParts,
      suffixParts,
    };
  }
  if (type === 'dataField' || type === 'systemField') {
    const key = typeof input.key === 'string' ? input.key.trim() : '';
    if (!key) {
      return null;
    }
    return {
      type,
      key,
      prefixParts,
      suffixParts,
    };
  }
  if (type === 'category') {
    return {
      type: 'category',
      prefixParts,
      suffixParts,
    };
  }
  if (type === 'labels') {
    return {
      type: 'labels',
      separator: typeof input.separator === 'string' ? input.separator : DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR,
      prefixParts,
      suffixParts,
    };
  }
  if (type === 'firstAvailable') {
    return {
      type: 'firstAvailable',
      parts: sanitizeFilenameTemplateCandidateParts(input.parts, depth + 1),
      prefixParts,
      suffixParts,
    };
  }
  return {
    type: 'text',
    value: typeof input.value === 'string' ? input.value : '',
  };
}

function sanitizeFilenameTemplate(template) {
  const input = template && typeof template === 'object' ? template : {};
  return {
    parts: sanitizeFilenameTemplateParts(input.parts)
  };
}

function defaultFilenameTemplatePart(type = 'text') {
  if (type === 'field') {
    return {
      type: 'field',
      key: 'sender',
      prefixParts: [],
      suffixParts: [],
    };
  }
  if (type === 'category') {
    return {
      type: 'category',
      prefixParts: [],
      suffixParts: [],
    };
  }
  if (type === 'dataField') {
    return {
      type: 'dataField',
      key: filenameTemplateDataFieldOptions()[0]?.key || 'amount',
      prefixParts: [],
      suffixParts: [],
    };
  }
  if (type === 'systemField') {
    return {
      type: 'systemField',
      key: filenameTemplateSystemFieldOptions()[0]?.key || 'document_date',
      prefixParts: [],
      suffixParts: [],
    };
  }
  if (type === 'labels') {
    return {
      type: 'labels',
      separator: DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR,
      prefixParts: [],
      suffixParts: [],
    };
  }
  if (type === 'firstAvailable') {
    return {
      type: 'firstAvailable',
      parts: [],
      prefixParts: [],
      suffixParts: [],
    };
  }
  return {
    type: 'text',
    value: '',
  };
}

function defaultExtractionField() {
  return {
    key: '',
    name: '',
    searchString: '',
  };
}

function sanitizeExtractionField(field, fallbackIndex = 0) {
  const input = field && typeof field === 'object' ? field : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const normalizedKey = typeof input.key === 'string' && input.key.trim() !== ''
    ? input.key.trim()
    : normalizeConfigKey(name || `field_${fallbackIndex + 1}`);
  return {
    key: normalizedKey,
    name,
    searchString: typeof input.searchString === 'string'
      ? input.searchString
      : (typeof input.query === 'string' ? input.query : ''),
    extractor: typeof input.extractor === 'string' && input.extractor.trim() !== ''
      ? input.extractor.trim()
      : 'generic_label',
    predefinedFieldKey: typeof input.predefinedFieldKey === 'string' ? input.predefinedFieldKey : '',
    isPredefinedField: input.isPredefinedField === true,
    systemFieldKey: typeof input.systemFieldKey === 'string' ? input.systemFieldKey : '',
    isSystemField: input.isSystemField === true,
  };
}

function sanitizeExtractionFields(fields) {
  return Array.isArray(fields) ? fields.map((field, index) => sanitizeExtractionField(field, index)) : [];
}

function filenameTemplateLegacyFieldMeta(key) {
  return typeof key === 'string' && key in FILENAME_TEMPLATE_LEGACY_FIELDS
    ? FILENAME_TEMPLATE_LEGACY_FIELDS[key]
    : null;
}

function filenameTemplateSystemFieldTitle(fieldKey, fieldName) {
  const key = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  const name = typeof fieldName === 'string' ? fieldName.trim() : '';
  if (key === 'document_date') {
    return 'Dokumentdatum är systemets bästa gissning på dokumentets huvuddatum när inget tydligt datumfält finns.';
  }
  return name
    ? `Lägger till värdet för systemdatafältet "${name}" i filnamnet.`
    : 'Lägger till värdet för valt systemdatafält i filnamnet.';
}

function filenameTemplateSystemFieldOptions() {
  const seenKeys = new Set();
  return sanitizeExtractionFields(systemExtractionFieldsDraft)
    .map((field, index) => sanitizeExtractionField(field, index))
    .filter((field) => {
      const key = typeof field.key === 'string' ? field.key.trim() : '';
      const name = typeof field.name === 'string' ? field.name.trim() : '';
      if (!key || !name || seenKeys.has(key)) {
        return false;
      }
      seenKeys.add(key);
      return true;
    })
    .map((field) => ({
      key: field.key,
      label: field.name,
      tone: 'system',
      title: filenameTemplateSystemFieldTitle(field.key, field.name),
    }));
}

function filenameTemplateDataFieldOptions() {
  const options = [];
  const seenKeys = new Set();
  [...predefinedExtractionFieldsDraft, ...extractionFieldsDraft].forEach((field, index) => {
    const normalized = sanitizeExtractionField(field, index);
    const key = typeof normalized.key === 'string' ? normalized.key.trim() : '';
    const name = typeof normalized.name === 'string' ? normalized.name.trim() : '';
    if (!key || !name || seenKeys.has(key)) {
      return;
    }
    seenKeys.add(key);
    options.push({
      key,
      label: name,
      tone: 'data',
      title: `Lägger till värdet för datafältet "${name}" i filnamnet.`,
    });
  });
  return options;
}

function filenameTemplateLabelDefinitions() {
  const definitions = [];
  const seenIds = new Set();

  [...Object.values(sanitizeSystemLabels(systemLabelsDraft)), ...sanitizeLabels(labelsDraft)].forEach((label) => {
    const id = typeof label.id === 'string' ? label.id.trim() : '';
    const name = typeof label.name === 'string' ? label.name.trim() : '';
    if (!id || !name || seenIds.has(id)) {
      return;
    }
    seenIds.add(id);
    definitions.push({
      id,
      name,
    });
  });

  return definitions;
}

function filenameTemplateLabelNameById(labelId) {
  const normalizedId = typeof labelId === 'string' ? labelId.trim() : '';
  if (!normalizedId) {
    return '';
  }
  const match = filenameTemplateLabelDefinitions().find((label) => label.id === normalizedId);
  return match ? match.name : normalizedId;
}

function filenameTemplateInsertOptions() {
  return [
    ...filenameTemplateSystemFieldOptions().map((field) => ({
      type: 'systemField',
      key: field.key,
      label: field.label,
      tone: field.tone,
      title: field.title,
    })),
    {
      type: 'category',
      label: 'Kategori',
      tone: 'category',
      title: 'Lägger till dokumentets kategori i filnamnet.',
    },
    {
      type: 'dataField',
      label: 'Datafält',
      tone: 'data',
      title: 'Lägger till värdet från valt datafält i filnamnet.',
    },
    {
      type: 'labels',
      label: 'Etiketter',
      tone: 'labels',
      title: 'Lägger till dokumentets etiketter i filnamnet.',
    },
    {
      type: 'firstAvailable',
      label: 'Första tillgängliga',
      tone: 'special',
      title: 'Använder den första kandidaten som faktiskt har ett värde.',
    },
  ];
}

function slugifyText(value, separator = '-', fallback = '') {
  const safeSeparator = separator === '_' ? '_' : '-';
  const normalized = String(value || '')
    .trim()
    .toLowerCase()
    .normalize('NFKD')
    .replace(/[\u0300-\u036f]/g, '')
    .replace(/æ/g, 'ae')
    .replace(/œ/g, 'oe')
    .replace(/ø/g, 'o')
    .replace(/ß/g, 'ss')
    .replace(/þ/g, 'th')
    .replace(/ð/g, 'd')
    .replace(/ł/g, 'l')
    .replace(/[^a-z0-9]+/g, safeSeparator)
    .replace(new RegExp(`\\${safeSeparator}+`, 'g'), safeSeparator)
    .replace(new RegExp(`^\\${safeSeparator}+|\\${safeSeparator}+$`, 'g'), '');
  return normalized || fallback;
}

function normalizeConfigKey(value) {
  return slugifyText(value, '_', 'field');
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

function createTreeRow(options = {}) {
  const row = document.createElement('div');
  row.className = 'tree-row';
  if (options.markerless) {
    row.classList.add('tree-row-no-marker');
  }
  return row;
}

function createTreeChildren(options = {}) {
  const children = document.createElement('div');
  children.className = 'tree-children';
  if (options.markerless) {
    children.classList.add('tree-children-markerless');
  }
  return children;
}

function appendTreeBodyIcon(bodyEl, className) {
  const icon = document.createElement('span');
  icon.className = className;
  icon.setAttribute('aria-hidden', 'true');
  bodyEl.appendChild(icon);
  return icon;
}

function appendTreeBodyLock(bodyEl, title = 'Låst etikett') {
  const lock = document.createElement('span');
  lock.className = 'tree-body-icon-lock';
  lock.setAttribute('aria-hidden', 'true');
  lock.title = title;
  bodyEl.classList.add('has-top-lock');
  bodyEl.appendChild(lock);
  return lock;
}

function renderClientsEditor() {
  if (!clientsListEl) {
    return;
  }

  clientsListEl.innerHTML = '';
  if (clientsDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga huvudmän ännu.';
    clientsListEl.appendChild(empty);
    return;
  }

  const fragment = document.createDocumentFragment();
  clientsDraft.forEach((row, rowIndex) => {
    const clientNode = document.createElement('div');
    clientNode.className = 'tree-node tree-folder';
    clientNode.dataset.clientUiKey = clientUiKey(row);

    const clientRow = createTreeRow({ markerless: true });

    const clientBody = document.createElement('div');
    clientBody.className = 'tree-body folder-body';
    appendTreeBodyIcon(clientBody, 'tree-body-icon client-card-icon');

    const fields = document.createElement('div');
    fields.className = 'client-fields';

    const folderInput = document.createElement('input');
    folderInput.type = 'text';
    folderInput.placeholder = 'Ex: Johan Andersson';
    folderInput.value = row.folderName;
    folderInput.addEventListener('input', () => {
      clientsDraft[rowIndex].folderName = folderInput.value;
      updateSettingsActionButtons();
    });

    const firstNameInput = document.createElement('input');
    firstNameInput.type = 'text';
    firstNameInput.placeholder = 'Ex: Johan';
    firstNameInput.value = row.firstName || '';
    firstNameInput.addEventListener('input', () => {
      clientsDraft[rowIndex].firstName = firstNameInput.value;
      updateSettingsActionButtons();
    });

    const lastNameInput = document.createElement('input');
    lastNameInput.type = 'text';
    lastNameInput.placeholder = 'Ex: Andersson';
    lastNameInput.value = row.lastName || '';
    lastNameInput.addEventListener('input', () => {
      clientsDraft[rowIndex].lastName = lastNameInput.value;
      updateSettingsActionButtons();
    });

    const pinInput = document.createElement('input');
    pinInput.type = 'text';
    pinInput.placeholder = 'Ex: 19900101-1234';
    pinInput.value = row.personalIdentityNumber;
    pinInput.addEventListener('input', () => {
      clientsDraft[rowIndex].personalIdentityNumber = pinInput.value;
      updateSettingsActionButtons();
    });

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'category-remove';
    removeButton.textContent = 'Ta bort huvudman';
    removeButton.addEventListener('click', () => {
      clientsDraft.splice(rowIndex, 1);
      renderClientsEditor();
      updateSettingsActionButtons();
    });

    fields.appendChild(createFloatingField('Visningsnamn/Mappnamn', folderInput));
    fields.appendChild(createFloatingField('Förnamn', firstNameInput));
    fields.appendChild(createFloatingField('Efternamn', lastNameInput));
    fields.appendChild(createFloatingField('Personnummer', pinInput));
    fields.appendChild(removeButton);

    clientBody.appendChild(fields);
    clientRow.appendChild(clientBody);
    clientNode.appendChild(clientRow);
    fragment.appendChild(clientNode);
  });

  clientsListEl.appendChild(fragment);
}

function focusFirstClientsField() {
  if (!clientsListEl) {
    return;
  }
  const firstInput = clientsListEl.querySelector('input');
  if (firstInput) {
    firstInput.focus();
  }
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

  const senderRow = createTreeRow();

  const senderBody = document.createElement('div');
  senderBody.className = 'tree-body folder-body';
  appendTreeBodyIcon(senderBody, 'tree-body-icon sender-card-icon');

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
    applySenderEditorCollapsedState(senderNode, collapsedSenderUiKeys.has(currentSenderUiKey));
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

  const senderDetails = document.createElement('div');
  senderDetails.className = 'sender-details';
  senderDetails.hidden = isCollapsed;

  const senderFields = document.createElement('div');
  senderFields.className = 'sender-fields';
  senderFields.appendChild(createFloatingField('Domän', domainInput));
  senderFields.appendChild(removeButton);
  senderFields.appendChild(createFloatingField('Anteckningar', notesInput, 'sender-notes-field'));
  senderDetails.appendChild(senderFields);

  const paymentList = createTreeChildren({ markerless: true });

  const paymentsLabel = document.createElement('div');
  paymentsLabel.className = 'archive-level-label';
  paymentsLabel.textContent = 'Betalnummer';
  paymentList.appendChild(paymentsLabel);

  row.paymentNumbers.forEach((payment, paymentIndex) => {
    const paymentNode = document.createElement('div');
    paymentNode.className = 'tree-node tree-category has-parent';

    const paymentRow = createTreeRow({ markerless: true });

    const paymentBody = document.createElement('div');
    paymentBody.className = 'tree-body category-body';
    appendTreeBodyIcon(paymentBody, 'tree-body-icon sender-payment-icon');

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

  const rootRow = createTreeRow({ markerless: true });

  const rootBody = document.createElement('div');
  rootBody.className = 'tree-body folder-body';
  appendTreeBodyIcon(rootBody, 'tree-body-icon sender-card-icon');

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

  const paymentList = createTreeChildren({ markerless: true });

  const paymentsLabel = document.createElement('div');
  paymentsLabel.className = 'archive-level-label';
  paymentsLabel.textContent = 'Betalnummer';
  paymentList.appendChild(paymentsLabel);

  draft.paymentNumbers.forEach((payment, paymentIndex) => {
    const paymentNode = document.createElement('div');
    paymentNode.className = 'tree-node tree-category has-parent';

    const paymentRow = createTreeRow({ markerless: true });

    const paymentBody = document.createElement('div');
    paymentBody.className = 'tree-body category-body';
    appendTreeBodyIcon(paymentBody, 'tree-body-icon sender-payment-icon');

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

function renderSingleExtractionFieldEditor(container, collection, index, options = {}) {
  if (!(container instanceof HTMLElement) || !Array.isArray(collection)) {
    return;
  }
  const field = sanitizeExtractionField(collection[index], index);
  const showLock = options.showLock === true;
  const allowRemove = options.allowRemove !== false;
  const readOnly = options.readOnly === true;
  const isDocumentDateField = field.extractor === 'document_date'
    || field.systemFieldKey === 'document_date';

  const fieldNode = document.createElement('div');
  fieldNode.className = 'tree-node tree-category';
  if (showLock) {
    fieldNode.dataset.systemField = 'true';
  }

  const fieldRow = createTreeRow({ markerless: true });
  const fieldBody = document.createElement('div');
  fieldBody.className = 'tree-body category-body';
  appendTreeBodyIcon(fieldBody, 'tree-body-icon tree-body-icon-category');
  if (showLock) {
    appendTreeBodyLock(fieldBody, 'Låst datafält');
  }

  const fields = document.createElement('div');
  fields.className = 'category-fields category-fields--wide';

  const nameInput = document.createElement('input');
  nameInput.type = 'text';
  nameInput.placeholder = 'Ex: "Huvudman"';
  nameInput.value = field.name;
  if (readOnly) {
    nameInput.disabled = true;
  } else {
    nameInput.addEventListener('input', () => {
      collection[index].name = nameInput.value;
      if (!String(collection[index].key || '').trim()) {
        collection[index].key = normalizeConfigKey(nameInput.value || `field_${index + 1}`);
      }
      updateSettingsActionButtons();
    });
  }

  const queryInput = document.createElement('input');
  queryInput.type = 'text';
  queryInput.placeholder = isDocumentDateField ? 'Särskild intern heuristik' : 'Ex: "huvudman"';
  queryInput.value = isDocumentDateField
    ? 'Ort + datum / brevhuvud / fristående datum'
    : field.searchString;
  if (readOnly || isDocumentDateField) {
    queryInput.disabled = true;
  } else {
    queryInput.addEventListener('input', () => {
      collection[index].searchString = queryInput.value;
      updateSettingsActionButtons();
    });
  }

  const keyInput = document.createElement('input');
  keyInput.type = 'text';
  keyInput.value = field.key;
  keyInput.disabled = true;

  fields.appendChild(createFloatingField('Namn', nameInput));
  fields.appendChild(createFloatingField(isDocumentDateField ? 'Extraktion' : 'Söksträng', queryInput));
  fields.appendChild(createFloatingField('Nyckel', keyInput));

  if (allowRemove) {
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'category-remove';
    removeButton.textContent = 'Ta bort';
    removeButton.addEventListener('click', () => {
      collection.splice(index, 1);
      renderExtractionFieldsEditor();
      renderSystemExtractionFieldsEditor();
      updateSettingsActionButtons();
    });
    fields.appendChild(removeButton);
  }

  fieldBody.appendChild(fields);
  fieldRow.appendChild(fieldBody);
  fieldNode.appendChild(fieldRow);
  container.appendChild(fieldNode);
}

function renderExtractionFieldsEditor() {
  if (!extractionFieldsEditorEl) {
    return;
  }

  extractionFieldsEditorEl.innerHTML = '';

  const builtInGroup = createEditorGroup('Fördefinerade', extractionFieldsBuiltInCollapsed, () => {
    extractionFieldsBuiltInCollapsed = !extractionFieldsBuiltInCollapsed;
  }, renderExtractionFieldsEditor);
  const ownGroup = createEditorGroup('Egna', extractionFieldsCustomCollapsed, () => {
    extractionFieldsCustomCollapsed = !extractionFieldsCustomCollapsed;
  }, renderExtractionFieldsEditor);
  ownGroup.section.classList.add('labels-editor-group--spaced');

  extractionFieldsEditorEl.appendChild(builtInGroup.section);
  extractionFieldsEditorEl.appendChild(ownGroup.section);

  if (predefinedExtractionFieldsDraft.length === 0) {
    const emptyBuiltIn = document.createElement('div');
    emptyBuiltIn.className = 'categories-empty';
    emptyBuiltIn.textContent = 'Inga fördefinerade datafält ännu.';
    builtInGroup.content.appendChild(emptyBuiltIn);
  } else {
    predefinedExtractionFieldsDraft.forEach((field, index) => {
      renderSingleExtractionFieldEditor(builtInGroup.content, predefinedExtractionFieldsDraft, index, {
        showLock: true,
        allowRemove: false,
      });
    });
  }

  if (extractionFieldsDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga datafält ännu.';
    ownGroup.content.appendChild(empty);
    return;
  }

  extractionFieldsDraft.forEach((field, index) => {
    renderSingleExtractionFieldEditor(ownGroup.content, extractionFieldsDraft, index, {
      showLock: false,
      allowRemove: true,
    });
  });
}

function renderSystemExtractionFieldsEditor() {
  if (!systemExtractionFieldsEditorEl) {
    return;
  }

  systemExtractionFieldsEditorEl.innerHTML = '';

  const label = document.createElement('div');
  label.className = 'archive-folders-label';
  label.textContent = 'Systemdatafält';
  systemExtractionFieldsEditorEl.appendChild(label);

  if (systemExtractionFieldsDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga systemdatafält ännu.';
    systemExtractionFieldsEditorEl.appendChild(empty);
    return;
  }

  systemExtractionFieldsDraft.forEach((field, index) => {
    renderSingleExtractionFieldEditor(systemExtractionFieldsEditorEl, systemExtractionFieldsDraft, index, {
      showLock: true,
      allowRemove: false,
      readOnly: true,
    });
  });
}

function syncLabelsEditorValidation() {
  if (!labelsListEl) {
    return;
  }

  const duplicateIds = duplicateLabelIds(labelsDraft);
  Array.from(labelsListEl.querySelectorAll('[data-label-index]')).forEach((input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }
    const labelIndex = parseInt(input.dataset.labelIndex || '', 10);
    const label = Number.isInteger(labelIndex) ? sanitizeLabel(labelsDraft[labelIndex]) : null;
    const id = label && typeof label.id === 'string' ? label.id.trim() : '';
    const name = label && typeof label.name === 'string' ? label.name.trim() : '';
    const message = !name || !id
      ? 'Etiketten måste ha ett namn.'
      : duplicateIds.has(id)
        ? `Etikett-id krockar: ${id}`
        : '';
    input.classList.toggle('settings-field-invalid', message !== '');
    input.title = message;
  });
}

function createEditorGroup(title, collapsed, onToggle, onRender) {
  const section = document.createElement('section');
  section.className = 'labels-editor-group';

  const toggle = document.createElement('button');
  toggle.type = 'button';
  toggle.className = collapsed
    ? 'archive-folders-label settings-group-toggle is-collapsed'
    : 'archive-folders-label settings-group-toggle';
  toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
  toggle.textContent = title;
  toggle.addEventListener('click', () => {
    onToggle();
    if (typeof onRender === 'function') {
      onRender();
    }
  });

  const content = document.createElement('div');
  content.className = 'labels-editor-group-content';
  content.classList.toggle('hidden', collapsed);

  section.appendChild(toggle);
  section.appendChild(content);
  return { section, content };
}

function setExtractionFieldsTab(tabId) {
  if (!Array.isArray(extractionFieldsTabEls) || !extractionFieldsViewCustomEl || !extractionFieldsViewSystemEl) {
    return;
  }
  activeExtractionFieldsTabId = tabId === 'system' ? 'system' : 'fields';
  extractionFieldsTabEls.forEach((button) => {
    const isActive = button.dataset.extractionFieldsTab === activeExtractionFieldsTabId;
    button.classList.toggle('active', isActive);
  });
  extractionFieldsViewCustomEl.classList.toggle('hidden', activeExtractionFieldsTabId !== 'fields');
  extractionFieldsViewSystemEl.classList.toggle('hidden', activeExtractionFieldsTabId !== 'system');
  if (extractionFieldsAddRowEl) {
    extractionFieldsAddRowEl.classList.toggle('hidden', activeExtractionFieldsTabId === 'system');
    extractionFieldsAddRowEl.textContent = 'Lägg till datafält';
  }
}

function labelRuleOptions(currentLabelId = '') {
  const normalizedCurrentId = typeof currentLabelId === 'string' ? currentLabelId.trim() : '';
  return categoryRuleLabelOptions().filter((option) => option.value !== normalizedCurrentId);
}

function currentLabelDraftForEditor(options = {}) {
  if (options && options.builtIn === true) {
    const labelKey = typeof options.labelKey === 'string' ? options.labelKey.trim() : '';
    return labelKey !== '' ? systemLabelsDraft[labelKey] : null;
  }
  const labelIndex = Number.isInteger(options?.labelIndex) ? options.labelIndex : -1;
  return labelIndex >= 0 ? labelsDraft[labelIndex] : null;
}

function sanitizedLabelDraftForEditor(options = {}) {
  const draft = currentLabelDraftForEditor(options);
  if (!draft) {
    return null;
  }
  if (options && options.builtIn === true) {
    const labelKey = typeof options.labelKey === 'string' ? options.labelKey.trim() : '';
    return labelKey !== '' ? sanitizeSystemLabelByKey(labelKey, draft) : null;
  }
  return sanitizeLabel(draft);
}

function renderSingleLabelEditor(container, options = {}) {
  if (!(container instanceof HTMLElement)) {
    return;
  }

  const currentLabel = sanitizedLabelDraftForEditor(options);
  if (!currentLabel) {
    return;
  }

  const builtIn = options && options.builtIn === true;
  const labelNode = document.createElement('div');
  labelNode.className = 'tree-node tree-category';
  if (builtIn) {
    labelNode.dataset.systemLabel = 'true';
  }

  const labelRow = createTreeRow({ markerless: true });
  const labelBody = document.createElement('div');
  labelBody.className = 'tree-body category-body';
  appendTreeBodyIcon(labelBody, 'tree-body-icon tree-body-icon-category');
  if (builtIn) {
    appendTreeBodyLock(labelBody);
  }

  const fields = document.createElement('div');
  fields.className = 'category-fields category-fields--wide';

  const nameInput = document.createElement('input');
  nameInput.type = 'text';
  nameInput.placeholder = 'Ex: "Bostadstillägg"';
  nameInput.value = currentLabel.name;
  if (!builtIn && Number.isInteger(options.labelIndex)) {
    nameInput.dataset.labelIndex = String(options.labelIndex);
  }
  nameInput.addEventListener('input', () => {
    const draft = currentLabelDraftForEditor(options);
    if (!draft) {
      return;
    }
    draft.name = nameInput.value;
    const sanitized = sanitizedLabelDraftForEditor(options);
    idInput.value = sanitized && typeof sanitized.id === 'string' ? sanitized.id : '';
    syncLabelsEditorValidation();
    updateSettingsActionButtons();
  });

  const idInput = document.createElement('input');
  idInput.type = 'text';
  idInput.value = typeof currentLabel.id === 'string' ? currentLabel.id : '';
  idInput.disabled = true;
  if (!builtIn && Number.isInteger(options.labelIndex)) {
    idInput.dataset.labelIndex = String(options.labelIndex);
  }

  const minScoreInput = document.createElement('input');
  minScoreInput.type = 'number';
  minScoreInput.step = '1';
  minScoreInput.min = '1';
  minScoreInput.value = String(currentLabel.minScore);
  minScoreInput.addEventListener('input', () => {
    const draft = currentLabelDraftForEditor(options);
    if (!draft) {
      return;
    }
    draft.minScore = sanitizePositiveInt(minScoreInput.value, 1);
    updateSettingsActionButtons();
  });

  fields.appendChild(createFloatingField('Namn', nameInput));
  fields.appendChild(createFloatingField('ID', idInput));
  fields.appendChild(createFloatingField('Minpoäng', minScoreInput, 'score-field'));

  if (!builtIn) {
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'category-remove';
    removeButton.textContent = 'Ta bort etikett';
    removeButton.addEventListener('click', () => {
      const labelIndex = Number.isInteger(options.labelIndex) ? options.labelIndex : -1;
      if (labelIndex < 0) {
        return;
      }
      labelsDraft.splice(labelIndex, 1);
      renderLabelsEditor();
      updateSettingsActionButtons();
    });
    fields.appendChild(removeButton);
  }

  labelBody.appendChild(fields);

  const ruleList = createTreeChildren({ markerless: true });
  const rulesLabel = document.createElement('div');
  rulesLabel.className = 'archive-level-label';
  rulesLabel.textContent = 'Regler';
  ruleList.appendChild(rulesLabel);

  currentLabel.rules.forEach((rule, ruleIndex) => {
    const ruleNode = document.createElement('div');
    ruleNode.className = 'tree-node tree-rule has-parent';

    const ruleRow = createTreeRow({ markerless: true });
    const ruleBody = document.createElement('div');
    ruleBody.className = 'tree-body rule-body';
    appendTreeBodyIcon(ruleBody, 'tree-body-icon tree-body-icon-rule');

    const ruleFields = document.createElement('div');
    ruleFields.className = 'rule-fields';

    const typeSelect = document.createElement('select');
    [
      ['text', 'Innehåller text...'],
      ['label', 'Har etikett...']
    ].forEach(([value, optionLabel]) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = optionLabel;
      typeSelect.appendChild(option);
    });
    typeSelect.value = rule.type === 'label' ? 'label' : 'text';
    typeSelect.addEventListener('change', () => {
      const draft = currentLabelDraftForEditor(options);
      const sanitizedCurrent = sanitizedLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      const nextType = typeSelect.value === 'label' ? 'label' : 'text';
      draft.rules[ruleIndex].type = nextType;
      if (nextType === 'label') {
        const availableOptions = labelRuleOptions(sanitizedCurrent && sanitizedCurrent.id ? sanitizedCurrent.id : '');
        const fallbackLabelId = availableOptions[0] && typeof availableOptions[0].value === 'string'
          ? availableOptions[0].value
          : '';
        draft.rules[ruleIndex].labelId = availableOptions.some((option) => option.value === draft.rules[ruleIndex].labelId)
          ? draft.rules[ruleIndex].labelId
          : fallbackLabelId;
        draft.rules[ruleIndex].text = '';
      } else {
        draft.rules[ruleIndex].labelId = '';
      }
      renderLabelsEditor();
      updateSettingsActionButtons();
    });

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = 'Ex: "förfallodatum"';
    textInput.value = rule.text;
    textInput.addEventListener('input', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].text = textInput.value;
      updateSettingsActionButtons();
    });

    const labelSelect = document.createElement('select');
    const availableOptions = labelRuleOptions(currentLabel.id);
    if (availableOptions.length === 0) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Inga etiketter';
      labelSelect.appendChild(option);
      labelSelect.disabled = true;
    } else {
      availableOptions.forEach((optionData) => {
        const option = document.createElement('option');
        option.value = optionData.value;
        option.textContent = optionData.label;
        labelSelect.appendChild(option);
      });
      const currentLabelId = typeof rule.labelId === 'string' ? rule.labelId.trim() : '';
      const resolvedLabelId = availableOptions.some((option) => option.value === currentLabelId)
        ? currentLabelId
        : availableOptions[0].value;
      labelSelect.value = resolvedLabelId;
      const draft = currentLabelDraftForEditor(options);
      if (draft && Array.isArray(draft.rules) && draft.rules[ruleIndex]) {
        draft.rules[ruleIndex].labelId = resolvedLabelId;
      }
    }
    labelSelect.addEventListener('change', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].labelId = labelSelect.value;
      updateSettingsActionButtons();
    });

    const scoreInput = document.createElement('input');
    scoreInput.type = 'number';
    scoreInput.step = '1';
    scoreInput.min = '1';
    scoreInput.value = String(rule.score);
    scoreInput.addEventListener('input', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].score = sanitizePositiveInt(scoreInput.value, 1);
      updateSettingsActionButtons();
    });

    const removeRuleButton = document.createElement('button');
    removeRuleButton.type = 'button';
    removeRuleButton.className = 'rule-remove';
    removeRuleButton.textContent = 'Ta bort';
    removeRuleButton.addEventListener('click', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules)) {
        return;
      }
      draft.rules.splice(ruleIndex, 1);
      if (draft.rules.length === 0) {
        draft.rules.push(defaultRule());
      }
      renderLabelsEditor();
      updateSettingsActionButtons();
    });

    ruleFields.appendChild(createFloatingField('Regeltyp', typeSelect));
    if (rule.type === 'label') {
      ruleFields.appendChild(createFloatingField('Etikett', labelSelect));
    } else {
      ruleFields.appendChild(createFloatingField('Regeltext', textInput));
    }
    ruleFields.appendChild(createFloatingField('Poäng', scoreInput, 'score-field'));
    ruleFields.appendChild(removeRuleButton);
    ruleBody.appendChild(ruleFields);
    ruleRow.appendChild(ruleBody);
    ruleNode.appendChild(ruleRow);
    ruleList.appendChild(ruleNode);
  });

  const ruleActions = document.createElement('div');
  ruleActions.className = 'category-rule-actions';
  const addRuleButton = document.createElement('button');
  addRuleButton.type = 'button';
  addRuleButton.textContent = 'Lägg till regel';
  addRuleButton.addEventListener('click', () => {
    const draft = currentLabelDraftForEditor(options);
    if (!draft || !Array.isArray(draft.rules)) {
      return;
    }
    draft.rules.push(defaultRule());
    renderLabelsEditor();
    updateSettingsActionButtons();
  });
  ruleActions.appendChild(addRuleButton);

  if (builtIn && typeof options.labelKey === 'string' && SYSTEM_LABELS[options.labelKey]) {
    const restoreButton = document.createElement('button');
    restoreButton.type = 'button';
    restoreButton.textContent = 'Återställ';
    restoreButton.addEventListener('click', () => {
      const labelKey = options.labelKey;
      const defaults = SYSTEM_LABELS[labelKey];
      if (!defaults || !systemLabelsDraft[labelKey]) {
        return;
      }
      systemLabelsDraft[labelKey].name = defaults.name;
      systemLabelsDraft[labelKey].minScore = sanitizePositiveInt(defaults.minScore, 1);
      systemLabelsDraft[labelKey].rules = defaults.rules.map(sanitizeLabelRule);
      renderLabelsEditor();
      updateSettingsActionButtons();
    });
    ruleActions.appendChild(restoreButton);
  }

  labelBody.appendChild(ruleList);
  labelBody.appendChild(ruleActions);
  labelRow.appendChild(labelBody);
  labelNode.appendChild(labelRow);
  container.appendChild(labelNode);
}

function renderLabelsEditor() {
  if (!labelsListEl) {
    return;
  }

  labelsListEl.innerHTML = '';
  const builtInGroup = createEditorGroup('Fördefinerade', labelsBuiltInCollapsed, () => {
    labelsBuiltInCollapsed = !labelsBuiltInCollapsed;
  }, renderLabelsEditor);
  const ownGroup = createEditorGroup('Egna', labelsCustomCollapsed, () => {
    labelsCustomCollapsed = !labelsCustomCollapsed;
  }, renderLabelsEditor);
  ownGroup.section.classList.add('labels-editor-group--spaced');

  labelsListEl.appendChild(builtInGroup.section);
  labelsListEl.appendChild(ownGroup.section);

  if (labelsDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga etiketter ännu.';
    ownGroup.content.appendChild(empty);
  }

  labelsDraft.forEach((labelDraft, labelIndex) => {
    renderSingleLabelEditor(ownGroup.content, {
      builtIn: false,
      labelIndex,
    });
  });

  Object.keys(sanitizeSystemLabels(systemLabelsDraft)).forEach((labelKey) => {
    renderSingleLabelEditor(builtInGroup.content, {
      builtIn: true,
      labelKey,
    });
  });

  syncLabelsEditorValidation();
}

function normalizeEditableFilenameTemplateParts(parts) {
  const sanitized = sanitizeFilenameTemplateParts(parts);
  if (sanitized.length === 0) {
    return [defaultFilenameTemplatePart('text')];
  }

  const normalized = [];
  const appendTextPart = (value = '') => {
    const textValue = typeof value === 'string' ? value : '';
    const previous = normalized[normalized.length - 1] || null;
    if (previous && previous.type === 'text') {
      previous.value = String(previous.value || '') + textValue;
      return;
    }
    normalized.push({
      type: 'text',
      value: textValue,
    });
  };

  sanitized.forEach((part) => {
    if (part.type === 'text') {
      appendTextPart(part.value || '');
      return;
    }

    if (normalized.length === 0) {
      appendTextPart('');
    }
    normalized.push(part);
    appendTextPart('');
  });

  return normalized.length > 0 ? normalized : [defaultFilenameTemplatePart('text')];
}

function createFilenameTemplateToolbar(context) {
  const toolbar = document.createElement('div');
  toolbar.className = 'filename-template-toolbar';

  const bindInsertButton = (button, createPart) => {
    const preserveSelection = (event) => {
      event.preventDefault();
    };
    button.addEventListener('mousedown', preserveSelection);
    button.addEventListener('pointerdown', preserveSelection);
    button.addEventListener('click', () => {
      if (!context || typeof context.insertPart !== 'function') {
        return;
      }
      context.insertPart(createPart());
    });
  };

  filenameTemplateInsertOptions().forEach((definition) => {
    const chip = document.createElement('button');
    chip.type = 'button';
    chip.className = `filename-template-chip filename-template-chip--${definition.tone || 'data'}`;
    chip.textContent = definition.label;
    if (definition.title) {
      chip.title = definition.title;
    }
    bindInsertButton(chip, () => {
      const part = defaultFilenameTemplatePart(definition.type);
      if (typeof definition.key === 'string' && definition.key.trim() !== '') {
        part.key = definition.key.trim();
      }
      return part;
    });
    toolbar.appendChild(chip);
  });

  return toolbar;
}

function createFilenameTemplatePartsEditor(parts, onChange, depth = 0, context = null, options = {}) {
  if (!Array.isArray(parts)) {
    parts = [];
  }
  parts.splice(0, parts.length, ...sanitizeFilenameTemplateParts(parts));

  const isSlotEditor = options && options.variant === 'slot';
  const inlinePlaceholder = options && typeof options.placeholder === 'string'
    ? options.placeholder
    : '';
  const wrapper = document.createElement('div');
  wrapper.className = depth === 0 ? 'filename-template-editor' : 'filename-template-editor is-nested';
  if (isSlotEditor) {
    wrapper.classList.add('is-slot');
  }
  const sharedContext = context && typeof context === 'object'
    ? context
    : { insertPart: null };

  const partDisplayMeta = (part) => {
    if (!part || typeof part !== 'object') {
      return {
        label: '',
        tone: 'legacy',
        title: '',
      };
    }

    if (part.type === 'category') {
      return {
        label: 'Kategori',
        tone: 'category',
        title: 'Lägger till dokumentets kategori i filnamnet.',
      };
    }
    if (part.type === 'dataField') {
      const field = filenameTemplateDataFieldOptions().find((candidate) => candidate.key === part.key) || null;
      return {
        label: 'Datafält',
        tone: 'data',
        title: field
          ? `Lägger till värdet för datafältet "${field.label}" i filnamnet.`
          : 'Lägger till värdet från valt datafält i filnamnet.',
      };
    }
    if (part.type === 'systemField') {
      const field = filenameTemplateSystemFieldOptions().find((candidate) => candidate.key === part.key) || null;
      return {
        label: field ? field.label : (part.key || 'Systemdatafält'),
        tone: 'system',
        title: filenameTemplateSystemFieldTitle(part.key, field ? field.label : part.key),
      };
    }
    if (part.type === 'labels') {
      return {
        label: 'Etiketter',
        tone: 'labels',
        title: 'Lägger till dokumentets etiketter i filnamnet.',
      };
    }
    if (part.type === 'firstAvailable') {
      return {
        label: 'Första tillgängliga',
        tone: 'special',
        title: 'Använder den första kandidaten som faktiskt har ett värde.',
      };
    }
    const legacyField = filenameTemplateLegacyFieldMeta(part.key);
    return {
      label: legacyField ? legacyField.label : (part.key || 'Fält'),
      tone: legacyField && legacyField.tone ? legacyField.tone : 'legacy',
      title: legacyField && legacyField.title ? legacyField.title : 'Äldre filnamnsmall-fält.',
    };
  };

  if (!context && !isSlotEditor) {
    wrapper.appendChild(createFilenameTemplateToolbar(sharedContext));
  }

  const sequence = document.createElement(depth === 0 && !isSlotEditor ? 'div' : 'span');
  sequence.className = 'filename-template-inline-flow';
  if (isSlotEditor) {
    sequence.classList.add('is-slot');
  }
  const collapseTextParts = (inputParts) => {
    const collapsed = [];
    const appendText = (value) => {
      const textValue = typeof value === 'string' ? value : '';
      if (textValue === '') {
        return;
      }
      const previous = collapsed[collapsed.length - 1] || null;
      if (previous && previous.type === 'text') {
        previous.value = String(previous.value || '') + textValue;
        return;
      }
      collapsed.push({ type: 'text', value: textValue });
    };

    (Array.isArray(inputParts) ? inputParts : []).forEach((part) => {
      const normalized = sanitizeFilenameTemplatePart(part);
      if (!normalized) {
        return;
      }
      if (normalized.type === 'text') {
        appendText(normalized.value || '');
        return;
      }
      collapsed.push(normalized);
    });
    return collapsed;
  };

  const replaceParts = (targetParts, nextParts) => {
    targetParts.splice(0, targetParts.length, ...collapseTextParts(nextParts));
  };

  const setCaretToEnd = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    editable.focus();
    const range = document.createRange();
    range.selectNodeContents(editable);
    range.collapse(false);
    const selection = window.getSelection();
    if (!selection) {
      return;
    }
    selection.removeAllRanges();
    selection.addRange(range);
  };

  const ensureRangeInEditable = (editable) => {
    const selection = window.getSelection();
    if (!selection) {
      return null;
    }
    if (selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      const anchorNode = range.startContainer;
      if (anchorNode && editable.contains(anchorNode)) {
        return range;
      }
    }
    setCaretToEnd(editable);
    return selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
  };

  const isTokenNode = (node) =>
    node instanceof HTMLElement
    && node.classList.contains('filename-template-dom-token');

  const debugFilenameTemplateNav = (...args) => {
    if (!DEBUG_FILENAME_TEMPLATE_NAV) {
      return;
    }
    console.log('[filename-nav]', ...args);
  };

  const describeEditable = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      return null;
    }
    const slot = editable.closest('.filename-template-inline-token-slot');
    const token = editable.closest('.filename-template-dom-token');
    const slotName = slot instanceof HTMLElement
      ? Array.from(slot.classList).find((className) => className.startsWith('filename-template-inline-token-slot--')) || 'root'
      : 'root';
    const tokenName = token instanceof HTMLElement
      ? token.querySelector('.filename-template-inline-token-label')?.textContent?.trim()
        || token.querySelector('.filename-template-inline-token')?.textContent?.trim()
        || 'token'
      : null;
    const selection = window.getSelection();
    let anchor = null;
    if (selection && selection.rangeCount > 0) {
      const range = selection.getRangeAt(0);
      anchor = {
        containerType: range.startContainer.nodeType,
        containerText: range.startContainer.nodeType === Node.TEXT_NODE
          ? range.startContainer.nodeValue
          : range.startContainer.textContent,
        offset: range.startOffset,
      };
    }
    return {
      slotName,
      tokenName,
      text: editable.textContent,
      childNodes: editable.childNodes.length,
      anchor,
    };
  };

  const removeAdjacentToken = (editable, direction) => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
      return false;
    }
    const range = selection.getRangeAt(0);
    if (!range.collapsed) {
      return false;
    }

    const node = range.startContainer;
    const offset = range.startOffset;

    if (node.nodeType === Node.TEXT_NODE) {
      if (direction === 'back' && offset === 0) {
        const prev = node.previousSibling;
        if (isTokenNode(prev)) {
          prev.remove();
          return true;
        }
      }
      if (direction === 'fwd' && offset === node.nodeValue.length) {
        const next = node.nextSibling;
        if (isTokenNode(next)) {
          next.remove();
          return true;
        }
      }
    }

    if (node.nodeType === Node.ELEMENT_NODE) {
      if (direction === 'back') {
        const prev = node.childNodes[offset - 1];
        if (isTokenNode(prev)) {
          prev.remove();
          return true;
        }
      }
      if (direction === 'fwd') {
        const next = node.childNodes[offset];
        if (isTokenNode(next)) {
          next.remove();
          return true;
        }
      }
    }
    return false;
  };

const setCaretAtEditableBoundary = (editable, direction) => {
  if (!(editable instanceof HTMLElement)) {
    return false;
  }

  editable.focus();

  const selection = window.getSelection();
  if (!selection) {
    return false;
  }

  const range = document.createRange();

  if (editable.childNodes.length === 0) {
    range.setStart(editable, 0);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
    return true;
  }

  if (direction === 'back') {
    range.selectNodeContents(editable);
    range.collapse(true);
  } else {
    range.selectNodeContents(editable);
    range.collapse(false);
  }

  selection.removeAllRanges();
  selection.addRange(range);
  return true;
};

const isCaretAtEditableBoundary = (editable, direction) => {
	if (!(editable instanceof HTMLElement)) {
		return false;
	}

	const selection = window.getSelection();
	if (!selection || selection.rangeCount === 0) {
		return false;
	}

	const range = selection.getRangeAt(0);
	if (!range.collapsed) {
		return false;
	}

	const container = range.startContainer;
	if (container !== editable && !editable.contains(container)) {
		return false;
	}

	const boundaryRange = document.createRange();
	boundaryRange.selectNodeContents(editable);

	try {
		if (direction === 'back') {
			boundaryRange.setEnd(range.startContainer, range.startOffset);
		} else {
			boundaryRange.setStart(range.startContainer, range.startOffset);
		}
		const result = boundaryRange.toString() === '';
		debugFilenameTemplateNav('boundary', {
			direction,
			editable: describeEditable(editable),
			result,
			text: boundaryRange.toString(),
		});
		return result;
	} catch (error) {
		debugFilenameTemplateNav('boundary-error', {
			direction,
			editable: describeEditable(editable),
			error: String(error),
		});
		return false;
	}
};

  const setCaretAdjacentToNode = (editable, node, direction) => {
    if (!(editable instanceof HTMLElement) || !node || !editable.contains(node)) {
      return false;
    }
    const index = Array.prototype.indexOf.call(editable.childNodes, node);
    if (index < 0) {
      return false;
    }
    editable.focus();
    const range = document.createRange();
    range.setStart(editable, direction === 'back' ? index : index + 1);
    range.collapse(true);
    const selection = window.getSelection();
    if (!selection) {
      return false;
    }
    selection.removeAllRanges();
    selection.addRange(range);
    return true;
  };

	const adjacentTokenAtCaret = (editable, direction) => {
	const selection = window.getSelection();
	if (!selection || selection.rangeCount === 0) {
		return null;
	}

	const range = selection.getRangeAt(0);
	if (!range.collapsed) {
		return null;
	}

	const directToken = Array.from(editable.childNodes).find((childNode) => {
		if (!isTokenNode(childNode)) {
			return false;
		}
		const pointRange = document.createRange();
		if (direction === 'back') {
			pointRange.setStartAfter(childNode);
		} else {
			pointRange.setStartBefore(childNode);
		}
		pointRange.collapse(true);
		try {
			return pointRange.comparePoint(range.startContainer, range.startOffset) === 0;
		} catch (error) {
			return false;
		}
	});
	if (directToken) {
		return directToken;
	}

	let node = range.startContainer;
	let offset = range.startOffset;

	while (node && node !== editable) {
		if (node.nodeType === Node.TEXT_NODE) {
		const textLength = (node.nodeValue || '').length;

		if (direction === 'back') {
			if (offset > 0) {
			return null;
			}

			const sibling = node.previousSibling;
			if (isTokenNode(sibling)) {
			return sibling;
			}
		} else {
			if (offset < textLength) {
			return null;
			}

			const sibling = node.nextSibling;
			if (isTokenNode(sibling)) {
			return sibling;
			}
		}
		} else if (node.nodeType === Node.ELEMENT_NODE) {
		const candidate = direction === 'back'
			? node.childNodes[offset - 1] || null
			: node.childNodes[offset] || null;

		if (isTokenNode(candidate)) {
			return candidate;
		}
		}

		const parent = node.parentNode;
		if (!(parent instanceof Node)) {
		break;
		}

		offset = Array.prototype.indexOf.call(parent.childNodes, node);
		if (direction === 'fwd') {
		offset += 1;
		}
		node = parent;
	}

	if (node === editable && node.nodeType === Node.ELEMENT_NODE) {
		const candidate = direction === 'back'
		? node.childNodes[offset - 1] || null
		: node.childNodes[offset] || null;

		if (isTokenNode(candidate)) {
		return candidate;
		}
	}

	return null;
	};

	const tokenEditables = (token) => {
	if (!(token instanceof HTMLElement)) {
		return [];
	}

	const slotSelectors = [
		'.filename-template-inline-token-slot--prefix.filename-template-editable',
		'.filename-template-inline-token-slot--candidates.filename-template-editable',
		'.filename-template-inline-token-slot--suffix.filename-template-editable',
	];

	return slotSelectors
		.map((selector) => token.querySelector(selector))
		.filter((editable) => editable instanceof HTMLElement);
	};

  const focusTokenBoundaryEditable = (token, direction) => {
    const editables = tokenEditables(token);
    if (editables.length === 0) {
      return false;
    }
    const targetEditable = direction === 'back'
      ? editables[editables.length - 1]
      : editables[0];
    setActiveEditable(targetEditable);
    return setCaretAtEditableBoundary(targetEditable, direction === 'back' ? 'fwd' : 'back');
  };

	const moveCaretAcrossTokenBoundary = (editable, direction) => {
	if (!(editable instanceof HTMLElement)) {
		return false;
	}

	debugFilenameTemplateNav('move-start', {
		direction,
		editable: describeEditable(editable),
	});

	const nearbyToken = adjacentTokenAtCaret(editable, direction);
	if (nearbyToken) {
		debugFilenameTemplateNav('nearby-token', {
			direction,
			editable: describeEditable(editable),
			token: nearbyToken.querySelector('.filename-template-inline-token-label')?.textContent?.trim()
			  || nearbyToken.querySelector('.filename-template-inline-token')?.textContent?.trim()
			  || 'token',
		});
		return focusTokenBoundaryEditable(nearbyToken, direction);
	}

	const atBoundary = isCaretAtEditableBoundary(editable, direction);
	if (!atBoundary) {
		debugFilenameTemplateNav('not-at-boundary', {
			direction,
			editable: describeEditable(editable),
		});
		return false;
	}

	const currentToken = editable.closest('.filename-template-dom-token');
	if (!(currentToken instanceof HTMLElement)) {
		debugFilenameTemplateNav('no-current-token', {
			direction,
			editable: describeEditable(editable),
		});
		return false;
	}

	const editables = tokenEditables(currentToken);
	const currentIndex = editables.indexOf(editable);
	debugFilenameTemplateNav('token-editables', {
		direction,
		editable: describeEditable(editable),
		currentIndex,
		editables: editables.map((node) => describeEditable(node)),
	});
	if (currentIndex >= 0) {
		const nextIndex = direction === 'back' ? currentIndex - 1 : currentIndex + 1;
		if (nextIndex >= 0 && nextIndex < editables.length) {
		const siblingEditable = editables[nextIndex];
		debugFilenameTemplateNav('sibling-editable', {
			direction,
			from: describeEditable(editable),
			to: describeEditable(siblingEditable),
		});
		setActiveEditable(siblingEditable);
		return setCaretAtEditableBoundary(siblingEditable, direction === 'back' ? 'fwd' : 'back');
		}
	}

	const ownerEditable = currentToken.parentElement instanceof HTMLElement
		? currentToken.parentElement.closest('.filename-template-editable')
		: null;
	if (!(ownerEditable instanceof HTMLElement)) {
		debugFilenameTemplateNav('no-owner-editable', {
			direction,
			editable: describeEditable(editable),
		});
		return false;
	}

	debugFilenameTemplateNav('owner-adjacent-node', {
		direction,
		editable: describeEditable(editable),
		ownerEditable: describeEditable(ownerEditable),
	});
	setActiveEditable(ownerEditable);
	return setCaretAdjacentToNode(ownerEditable, currentToken, direction);
	};

  const createPartObject = (part) => sanitizeFilenameTemplatePart(part) || defaultFilenameTemplatePart('text');

let activeEditable = null;

  const setActiveEditable = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    if (activeEditable === editable) {
      sharedContext.insertPart = (part) => insertPartIntoEditable(editable, part);
      return;
    }
    wrapper.querySelectorAll('.filename-template-editable.is-active').forEach((node) => node.classList.remove('is-active'));
    editable.classList.add('is-active');
    activeEditable = editable;
    sharedContext.insertPart = (part) => insertPartIntoEditable(editable, part);
  };

  const syncEditableFromDom = (editable) => {
    if (!(editable instanceof HTMLElement) || !Array.isArray(editable._filenameTemplateTargetParts)) {
      return;
    }
    const chipsOnly = editable._filenameTemplateChipsOnly === true;
    Array.from(editable.childNodes).forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE || isTokenNode(child)) {
        return;
      }
      const fallbackText = child.textContent || '';
      if (!chipsOnly && fallbackText !== '') {
        editable.insertBefore(document.createTextNode(fallbackText), child);
      }
      child.remove();
    });
    editable.normalize();
    const nextParts = [];
    const appendText = (value) => {
      if (value === '') {
        return;
      }
      const previous = nextParts[nextParts.length - 1] || null;
      if (previous && previous.type === 'text') {
        previous.value = String(previous.value || '') + value;
        return;
      }
      nextParts.push({ type: 'text', value });
    };

    Array.from(editable.childNodes).forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE) {
        if (!chipsOnly) {
          appendText(child.nodeValue || '');
        }
        return;
      }
      if (!isTokenNode(child)) {
        return;
      }
      const partObject = child._filenameTemplatePart;
      const normalized = sanitizeFilenameTemplatePart(partObject);
      if (normalized) {
        nextParts.push(normalized);
      }
    });

    replaceParts(editable._filenameTemplateTargetParts, nextParts);
    onChange();
  };

  const attachEditableHandlers = (editable) => {
    const isInnermostEditableEventTarget = (eventTarget) => {
      if (!(eventTarget instanceof Node)) {
        return false;
      }
      const closestEditable = eventTarget instanceof HTMLElement
        ? eventTarget.closest('.filename-template-editable')
        : eventTarget.parentElement instanceof HTMLElement
          ? eventTarget.parentElement.closest('.filename-template-editable')
          : null;
      return closestEditable === editable;
    };

    editable.spellcheck = false;
    editable.setAttribute('contenteditable', 'true');
    editable.setAttribute('tabindex', '0');
    editable.classList.add('filename-template-editable');
    if (inlinePlaceholder) {
      editable.dataset.placeholder = inlinePlaceholder;
    }
    editable.addEventListener('focus', () => setActiveEditable(editable));
    editable.addEventListener('click', (event) => {
      if (!isInnermostEditableEventTarget(event.target)) {
        return;
      }
      setActiveEditable(editable);
    });
    editable.addEventListener('beforeinput', (event) => {
      if (!editable._filenameTemplateChipsOnly || !isInnermostEditableEventTarget(event.target)) {
        return;
      }
      const inputType = typeof event.inputType === 'string' ? event.inputType : '';
      if (inputType.startsWith('insert')) {
        event.preventDefault();
      }
    });
    editable.addEventListener('paste', (event) => {
      if (editable._filenameTemplateChipsOnly && isInnermostEditableEventTarget(event.target)) {
        event.preventDefault();
      }
    });
    editable.addEventListener('drop', (event) => {
      if (editable._filenameTemplateChipsOnly && isInnermostEditableEventTarget(event.target)) {
        event.preventDefault();
      }
    });
    editable.addEventListener('input', (event) => {
      if (!isInnermostEditableEventTarget(event.target)) {
        return;
      }
      setActiveEditable(editable);
      syncEditableFromDom(editable);
    });
    editable.addEventListener('keydown', (event) => {
      if (!isInnermostEditableEventTarget(event.target)) {
        return;
      }
      setActiveEditable(editable);
      if (event.key === 'Enter') {
        event.preventDefault();
        return;
      }
      if (editable._filenameTemplateChipsOnly && event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
        event.preventDefault();
        return;
      }
      if (event.key === 'Backspace' && removeAdjacentToken(editable, 'back')) {
        event.preventDefault();
        syncEditableFromDom(editable);
        return;
      }
      if (event.key === 'Delete' && removeAdjacentToken(editable, 'fwd')) {
        event.preventDefault();
        syncEditableFromDom(editable);
        return;
      }
      if ((event.key === 'ArrowLeft' || event.key === 'ArrowRight') && moveCaretAcrossTokenBoundary(editable, event.key === 'ArrowLeft' ? 'back' : 'fwd')) {
        event.preventDefault();
      }
    });
  };

  const renderEditorParts = (editable, targetParts) => {
    editable.replaceChildren();
    targetParts.forEach((part) => {
      if (part.type === 'text') {
        editable.appendChild(document.createTextNode(part.value || ''));
        return;
      }
      editable.appendChild(createTokenNode(part, editable));
    });
  };

  const buildSlotEditor = (targetParts, placeholder, slotClassName = '') => {
    const slotEditable = document.createElement('span');
    slotEditable.className = `filename-template-inline-token-slot ${slotClassName} filename-template-inline-flow is-slot`.trim();
    if (placeholder) {
      slotEditable.dataset.placeholder = placeholder;
    }
    slotEditable._filenameTemplateTargetParts = targetParts;
    slotEditable._filenameTemplateChipsOnly = slotClassName.includes('filename-template-inline-token-slot--candidates');
    attachEditableHandlers(slotEditable);
    renderEditorParts(slotEditable, targetParts);
    return slotEditable;
  };

  const createTokenNode = (part, ownerEditable) => {
    const normalizedPart = createPartObject(part);
    const meta = partDisplayMeta(normalizedPart);
    const token = document.createElement('span');
    token.className = 'filename-template-dom-token';
    token.setAttribute('contenteditable', 'false');
    token._filenameTemplatePart = normalizedPart;
    if (meta.title) {
      token.title = meta.title;
    }

    const shell = document.createElement('span');
    shell.className = 'filename-template-inline-token-shell';
    shell.appendChild(buildSlotEditor(normalizedPart.prefixParts, '', 'filename-template-inline-token-slot--prefix'));

    const center = document.createElement('span');
    center.className = `filename-template-inline-token-center filename-template-inline-token-center--${meta.tone || 'legacy'}`;

    const label = document.createElement('span');
    label.className = `filename-template-inline-token filename-template-inline-token--${meta.tone || 'legacy'}`;
    if (meta.title) {
      label.title = meta.title;
    }
    const labelText = document.createElement('span');
    labelText.className = 'filename-template-inline-token-label';
    labelText.textContent = meta.label;
    label.appendChild(labelText);
    center.appendChild(label);

    const syncPartControlChange = () => {
      token._filenameTemplatePart = normalizedPart;
      if (ownerEditable instanceof HTMLElement) {
        syncEditableFromDom(ownerEditable);
      } else {
        onChange();
      }
    };

    if (normalizedPart.type === 'dataField') {
      const select = document.createElement('select');
      select.className = 'filename-template-inline-token-select';
      const options = filenameTemplateDataFieldOptions();
      if (options.length === 0) {
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Inga datafält';
        select.appendChild(option);
        select.disabled = true;
      } else {
        options.forEach((optionData) => {
          const option = document.createElement('option');
          option.value = optionData.key;
          option.textContent = optionData.label;
          select.appendChild(option);
        });
        const fallbackKey = options[0]?.key || '';
        select.value = options.some((option) => option.key === normalizedPart.key)
          ? normalizedPart.key
          : fallbackKey;
        normalizedPart.key = select.value;
      }
      select.title = meta.title || 'Välj vilket datafält som ska skrivas in i filnamnet.';
      select.addEventListener('change', () => {
        normalizedPart.key = select.value;
        syncPartControlChange();
      });
      center.appendChild(select);
    } else if (normalizedPart.type === 'labels') {
      const separatorInput = document.createElement('input');
      separatorInput.type = 'text';
      separatorInput.className = 'filename-template-inline-token-input';
      separatorInput.value = typeof normalizedPart.separator === 'string'
        ? normalizedPart.separator
        : DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR;
      separatorInput.placeholder = ', ';
      separatorInput.title = 'Separator mellan etiketter i filnamnet.';
      separatorInput.setAttribute('aria-label', 'Separator mellan etiketter');
      separatorInput.addEventListener('input', () => {
        normalizedPart.separator = separatorInput.value;
        syncPartControlChange();
      });
      center.appendChild(separatorInput);
    } else if (normalizedPart.type === 'firstAvailable') {
      center.appendChild(buildSlotEditor(
        normalizedPart.parts,
        'Kandidater',
        'filename-template-inline-token-slot--candidates'
      ));
    }

    shell.appendChild(center);

    shell.appendChild(buildSlotEditor(normalizedPart.suffixParts, '', 'filename-template-inline-token-slot--suffix'));
    token.appendChild(shell);
    return token;
  };

  const insertPartIntoEditable = (editable, part) => {
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    const normalizedPart = createPartObject(part);
    const range = ensureRangeInEditable(editable);
    if (!range) {
      return;
    }
    range.deleteContents();
    const tokenNode = createTokenNode(normalizedPart, editable);
    range.insertNode(tokenNode);
    syncEditableFromDom(editable);

    const firstSlot = tokenNode.querySelector('.filename-template-editable');
    if (firstSlot instanceof HTMLElement) {
      setActiveEditable(firstSlot);
      setCaretToEnd(firstSlot);
      return;
    }
    setActiveEditable(editable);
    setCaretToEnd(editable);
  };

  sequence._filenameTemplateTargetParts = parts;
  attachEditableHandlers(sequence);
  sequence.dataset.placeholder = depth === 0 && !isSlotEditor
    ? 'Skriv filnamnsmall...'
    : inlinePlaceholder;
  renderEditorParts(sequence, parts);
  wrapper.appendChild(sequence);
  if (!context && !isSlotEditor) {
    setActiveEditable(sequence);
  }

  return wrapper;
}

function syncCategoriesEditorValidation() {
  if (!categoriesListEl) {
    return;
  }

  const duplicateIds = duplicateCategoryIds(categoriesDraft);
  Array.from(categoriesListEl.querySelectorAll('[data-category-folder-index][data-category-index]')).forEach((input) => {
    if (!(input instanceof HTMLInputElement)) {
      return;
    }
    const folderIndex = parseInt(input.dataset.categoryFolderIndex || '', 10);
    const categoryIndex = parseInt(input.dataset.categoryIndex || '', 10);
    const folder = Number.isInteger(folderIndex) ? categoriesDraft[folderIndex] : null;
    const category = folder && Array.isArray(folder.categories) ? sanitizeCategory(folder.categories[categoryIndex]) : null;
    const id = category && typeof category.id === 'string' ? category.id.trim() : '';
    const name = category && typeof category.name === 'string' ? category.name.trim() : '';
    const message = !name || !id
      ? 'Kategorin måste ha ett namn.'
      : duplicateIds.has(id)
        ? `Kategori-id krockar: ${id}`
        : '';
    input.classList.toggle('settings-field-invalid', message !== '');
    input.title = message;
  });
}

function renderCategoriesEditor() {
  if (!categoriesListEl) {
    return;
  }
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

    const archiveFolderRow = createTreeRow({ markerless: true });

    const archiveFolderBody = document.createElement('div');
    archiveFolderBody.className = 'tree-body folder-body';
    appendTreeBodyIcon(archiveFolderBody, 'tree-body-icon tree-body-icon-folder');

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

    const filenameTemplateLabel = document.createElement('div');
    filenameTemplateLabel.className = 'archive-level-label';
    filenameTemplateLabel.textContent = 'Filnamnsmall';
    archiveFolderBody.appendChild(filenameTemplateLabel);
    const filenameTemplate = sanitizeFilenameTemplate(
      categoriesDraft[archiveFolderIndex].filenameTemplate
    );
    categoriesDraft[archiveFolderIndex].filenameTemplate = filenameTemplate;
    archiveFolderBody.appendChild(
      createFilenameTemplatePartsEditor(
        filenameTemplate.parts,
        () => {
          updateSettingsActionButtons();
        }
      )
    );

    const archiveFolderCategories = createTreeChildren({ markerless: true });

    const categoriesLabel = document.createElement('div');
    categoriesLabel.className = 'archive-level-label';
    categoriesLabel.textContent = 'Kategorier';
    archiveFolderCategories.appendChild(categoriesLabel);

    archiveFolder.categories.forEach((category, categoryIndex) => {
      const categoryNode = document.createElement('div');
      categoryNode.className = 'tree-node tree-category has-parent';

      const categoryRow = createTreeRow({ markerless: true });

      const categoryBody = document.createElement('div');
      categoryBody.className = 'tree-body category-body';
      appendTreeBodyIcon(categoryBody, 'tree-body-icon tree-body-icon-category');

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
      categoryNameInput.dataset.categoryFolderIndex = String(archiveFolderIndex);
      categoryNameInput.dataset.categoryIndex = String(categoryIndex);
      categoryNameInput.addEventListener('input', () => {
        categoriesDraft[archiveFolderIndex].categories[categoryIndex].name = categoryNameInput.value;
        syncCategoriesEditorValidation();
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

      const ruleList = createTreeChildren({ markerless: true });

      const rulesLabel = document.createElement('div');
      rulesLabel.className = 'archive-level-label';
      rulesLabel.textContent = 'Regler';
      ruleList.appendChild(rulesLabel);

      category.rules.forEach((rule, ruleIndex) => {
        const ruleNode = document.createElement('div');
        ruleNode.className = 'tree-node tree-rule has-parent';

        const ruleRow = createTreeRow({ markerless: true });

        const ruleBody = document.createElement('div');
        ruleBody.className = 'tree-body rule-body';
        appendTreeBodyIcon(ruleBody, 'tree-body-icon tree-body-icon-rule');

        const ruleFields = document.createElement('div');
        ruleFields.className = 'rule-fields';
        const labelOptions = categoryRuleLabelOptions();

        const typeSelect = document.createElement('select');
        [
          ['text', 'Innehåller text...'],
          ['label', 'Har etikett...'],
        ].forEach(([value, label]) => {
          const option = document.createElement('option');
          option.value = value;
          option.textContent = label;
          typeSelect.appendChild(option);
        });
        typeSelect.value = rule.type === 'label'
          ? 'label'
          : 'text';
        typeSelect.addEventListener('change', () => {
          const nextType = typeSelect.value === 'label' ? 'label' : 'text';
          const nextRule = categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex];
          nextRule.type = nextType;
          if (nextType === 'label') {
            nextRule.text = '';
            const fallbackLabelId = labelOptions[0] && typeof labelOptions[0].value === 'string'
              ? labelOptions[0].value
              : '';
            nextRule.labelId = labelOptions.some((option) => option.value === nextRule.labelId)
              ? nextRule.labelId
              : fallbackLabelId;
          } else {
            nextRule.labelId = '';
          }
          renderCategoriesEditor();
          updateSettingsActionButtons();
        });

        const textInput = document.createElement('input');
        textInput.type = 'text';
        textInput.placeholder = 'Ex: "Förfallodatum"';
        textInput.value = rule.text;
        textInput.addEventListener('input', () => {
          categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex].text = textInput.value;
          updateSettingsActionButtons();
        });

        const labelSelect = document.createElement('select');
        if (labelOptions.length === 0) {
          const option = document.createElement('option');
          option.value = '';
          option.textContent = 'Inga etiketter';
          labelSelect.appendChild(option);
          labelSelect.disabled = true;
        } else {
          labelOptions.forEach((optionData) => {
            const option = document.createElement('option');
            option.value = optionData.value;
            option.textContent = optionData.label;
            labelSelect.appendChild(option);
          });
          const currentLabelId = typeof rule.labelId === 'string' ? rule.labelId.trim() : '';
          const resolvedLabelId = labelOptions.some((option) => option.value === currentLabelId)
            ? currentLabelId
            : labelOptions[0].value;
          labelSelect.value = resolvedLabelId;
          categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex].labelId = resolvedLabelId;
        }
        labelSelect.addEventListener('change', () => {
          categoriesDraft[archiveFolderIndex].categories[categoryIndex].rules[ruleIndex].labelId = labelSelect.value;
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

        ruleFields.appendChild(createFloatingField('Regeltyp', typeSelect));
        if (rule.type === 'label') {
          ruleFields.appendChild(createFloatingField('Etikett', labelSelect));
        } else {
          ruleFields.appendChild(createFloatingField('Regeltext', textInput));
        }
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

    syncCategoriesEditorValidation();

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

function renderOcrProcessingCommand() {
  if (!ocrSkipExistingTextEl || !ocrOptimizeLevelEl || !ocrTextExtractionMethodEl || !ocrProcessingCommandEl) {
    return;
  }
  const modeFlag = ocrSkipExistingTextEl.checked ? '--mode skip' : '--mode redo';
  const optimizeLevel = sanitizeOcrOptimizeLevel(ocrOptimizeLevelEl.value, 1);
  const deskewSegment = ocrSkipExistingTextEl.checked ? '--deskew ' : '';
  const extractionMethod = sanitizeOcrTextExtractionMethod(ocrTextExtractionMethodEl.value, 'layout');
  const substitutions = ocrPdfSubstitutionsDraft.map(sanitizeReplacement).filter((row) => row.from !== '' && row.to !== '');
  let pluginSegment = '--plugin docflow_ocrmypdf_plugin.py ';
  if (substitutions.length > 0) {
    pluginSegment += '--docflow-transform-script docflow_transform_runtime.py --docflow-transform-config data/docflow_ocr_transform_config.json ';
  }
  ocrProcessingCommandEl.textContent =
    'ocrmypdf ' + pluginSegment + '-j 1 -l swe ' + deskewSegment + '--tesseract-thresholding sauvola '
    + '--tesseract-pagesegmode 6 --output-type pdf '
    + '-O' + optimizeLevel
    + ' '
    + modeFlag
    + ' input.pdf output.pdf';

  if (ocrTextExtractionCommandEl) {
    ocrTextExtractionCommandEl.textContent = extractionMethod === 'bbox'
      ? 'pdftotext -bbox-layout input.pdf -'
      : 'pdftotext -layout input.pdf ocr.txt';
  }
}

async function copyTextToClipboard(text) {
  const value = String(text || '');
  if (!value) {
    return false;
  }

  if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
    await navigator.clipboard.writeText(value);
    return true;
  }

  const textarea = document.createElement('textarea');
  textarea.value = value;
  textarea.setAttribute('readonly', '');
  textarea.style.position = 'fixed';
  textarea.style.top = '-9999px';
  document.body.appendChild(textarea);
  textarea.select();
  let success = false;
  try {
    success = document.execCommand('copy');
  } finally {
    textarea.remove();
  }
  return success;
}

function bindSettingsCommandCopyButtons(panelEl) {
  if (!panelEl) {
    return;
  }

  const buttonEls = Array.from(panelEl.querySelectorAll('.settings-command-copy[data-copy-target]'));
  buttonEls.forEach((buttonEl) => {
    if (buttonEl.dataset.bound === 'true') {
      return;
    }
    buttonEl.dataset.bound = 'true';
    buttonEl.addEventListener('click', async () => {
      const targetId = String(buttonEl.dataset.copyTarget || '');
      const targetEl = targetId ? document.getElementById(targetId) : null;
      const text = targetEl ? String(targetEl.textContent || '').trim() : '';
      if (!text) {
        alert('Det finns inget kommando att kopiera.');
        return;
      }

      const originalLabel = buttonEl.textContent || 'Kopiera';
      try {
        const copied = await copyTextToClipboard(text);
        if (!copied) {
          throw new Error('copy_failed');
        }
        buttonEl.textContent = 'Kopierad';
        window.setTimeout(() => {
          buttonEl.textContent = originalLabel;
        }, 1200);
      } catch (error) {
        buttonEl.textContent = 'Fel';
        window.setTimeout(() => {
          buttonEl.textContent = originalLabel;
        }, 1200);
        alert('Kunde inte kopiera kommandot.');
      }
    });
  });
}

function startStatusRefreshSpin(buttonEl) {
  if (!buttonEl) {
    return;
  }
  buttonEl.disabled = true;
  buttonEl.classList.add('is-spinning');
}

function stopStatusRefreshSpin(buttonEl, hideAfterStop) {
  if (!buttonEl) {
    return;
  }
  const shouldHide = hideAfterStop === true;
  if (!buttonEl.classList.contains('is-spinning')) {
    buttonEl.disabled = false;
    buttonEl.classList.toggle('hidden', shouldHide);
    return;
  }

  if (buttonEl.dataset.stopPending === 'true') {
    buttonEl.dataset.hideAfterStop = shouldHide ? 'true' : 'false';
    return;
  }

  buttonEl.dataset.stopPending = 'true';
  buttonEl.dataset.hideAfterStop = shouldHide ? 'true' : 'false';
  buttonEl.addEventListener('animationiteration', () => {
    const finalHide = buttonEl.dataset.hideAfterStop === 'true';
    buttonEl.classList.remove('is-spinning');
    buttonEl.disabled = false;
    buttonEl.classList.toggle('hidden', finalHide);
    buttonEl.dataset.stopPending = 'false';
    buttonEl.dataset.hideAfterStop = 'false';
  }, { once: true });
}

function renderInstallableOcrToolStatus(status, elements, fallbackInstallCommand, options = {}) {
  if (!elements.badgeEl || !elements.badgeWrapEl || !elements.refreshButtonEl || !elements.installCommandEl) {
    return;
  }
  const installed = !!(status && status.installed === true);
  const isInstalling = !!(status && status.isInstalling === true);
  const installState = status && typeof status.installState === 'string' ? status.installState : '';
  const isFailed = installState === 'failed';
  const installScope = status && typeof status.installScope === 'string' ? status.installScope : '';
  const installStatusMessage = status && typeof status.installStatusMessage === 'string'
    ? status.installStatusMessage.trim()
    : '';
  const installedText = installScope === 'local' ? 'Installerad lokalt' : 'Installerad globalt';
  const deferRefreshVisibility = options && options.deferRefreshVisibility === true;
  const animate = !options || options.animate !== false;
  elements.badgeEl.textContent = isInstalling
    ? 'Installerar...'
    : (isFailed ? 'Installation misslyckades' : (installed ? installedText : 'Ej installerad'));
  elements.badgeEl.title = installStatusMessage;
  elements.badgeEl.classList.toggle('is-installed', installed && !isInstalling);
  elements.badgeEl.classList.toggle('is-installing', isInstalling);
  elements.badgeEl.classList.toggle('is-failed', isFailed);
  elements.badgeEl.classList.toggle('is-missing', !installed && !isInstalling && !isFailed);
  if (deferRefreshVisibility) {
    stopStatusRefreshSpin(elements.refreshButtonEl, installed || isInstalling);
  } else {
    elements.refreshButtonEl.classList.remove('is-spinning');
    elements.refreshButtonEl.disabled = isInstalling;
    elements.refreshButtonEl.classList.toggle('hidden', installed || isInstalling);
  }
  elements.badgeWrapEl.classList.remove('is-collapsed');
  elements.badgeWrapEl.classList.remove('is-animating');
  if (animate) {
    void elements.badgeWrapEl.offsetWidth;
    elements.badgeWrapEl.classList.add('is-animating');
  }

  const installCommand = status && typeof status.installCommand === 'string' && status.installCommand.trim() !== ''
    ? status.installCommand.trim()
    : fallbackInstallCommand;
  elements.installCommandEl.textContent = installCommand;

  if (elements.localInstallButtonEl) {
    const localSupported = !!(status && status.localInstallSupported === true);
    const localReason = status && typeof status.localInstallReason === 'string' ? status.localInstallReason : '';
    const isLocalInstalled = installScope === 'local';
    const isGlobalInstalled = installScope === 'global';
    elements.localInstallButtonEl.textContent = isInstalling ? 'Installerar...' : 'Installera lokalt';
    elements.localInstallButtonEl.disabled = isInstalling || isLocalInstalled || isGlobalInstalled || !localSupported;
    elements.localInstallButtonEl.title = isInstalling
      ? installStatusMessage
      : (isLocalInstalled
      ? 'Redan installerad lokalt'
      : (isGlobalInstalled
      ? 'Redan installerad globalt'
      : (localSupported ? 'Installera i Docflows lokala Python-miljö' : localReason)));
  }

  if (elements.logButtonEl) {
    const hasInstallLog = !!(status && status.hasInstallLog === true);
    elements.logButtonEl.classList.toggle('hidden', !hasInstallLog);
    elements.logButtonEl.disabled = !hasInstallLog;
    elements.logButtonEl.title = hasInstallLog ? 'Visa installationslogg' : 'Ingen installationslogg finns';
  }
}

function startJbig2RefreshSpin() {
  startStatusRefreshSpin(jbig2RefreshButtonEl);
}

function renderJbig2Status(jbig2, options = {}) {
  renderInstallableOcrToolStatus(jbig2, {
    badgeEl: jbig2StatusBadgeEl,
    badgeWrapEl: jbig2StatusBadgeWrapEl,
    refreshButtonEl: jbig2RefreshButtonEl,
    installCommandEl: jbig2InstallCommandEl,
    localInstallButtonEl: jbig2LocalInstallButtonEl,
  }, 'sudo apt install jbig2', options);
}

function renderPythonStatus(python, options = {}) {
  renderInstallableOcrToolStatus(python, {
    badgeEl: pythonStatusBadgeEl,
    badgeWrapEl: pythonStatusBadgeWrapEl,
    refreshButtonEl: pythonRefreshButtonEl,
    installCommandEl: pythonInstallCommandEl,
    localInstallButtonEl: pythonLocalInstallButtonEl,
  }, 'sudo apt install python3 python3-pip python3-venv', options);
  renderPythonDependentStatuses(python);
}

function renderRapidocrStatus(rapidocr, options = {}) {
  renderInstallableOcrToolStatus(rapidocr, {
    badgeEl: rapidocrStatusBadgeEl,
    badgeWrapEl: rapidocrStatusBadgeWrapEl,
    refreshButtonEl: rapidocrRefreshButtonEl,
    installCommandEl: rapidocrInstallCommandEl,
    logButtonEl: rapidocrInstallLogButtonEl,
    localInstallButtonEl: rapidocrLocalInstallButtonEl,
  }, 'python3 -m pip install --break-system-packages rapidocr onnxruntime', options);
  syncRapidocrInstallPolling(rapidocr);
}

function stopRapidocrInstallPolling() {
  if (rapidocrInstallPollTimer === null) {
    return;
  }
  window.clearTimeout(rapidocrInstallPollTimer);
  rapidocrInstallPollTimer = null;
}

function scheduleRapidocrInstallPolling(delay = 2000) {
  stopRapidocrInstallPolling();
  rapidocrInstallPollTimer = window.setTimeout(async () => {
    rapidocrInstallPollTimer = null;
    if (settingsModalEl.classList.contains('hidden') || activeSettingsTabId !== 'ocr-processing') {
      return;
    }
    try {
      await loadOcrProcessingSettings({ statusTarget: 'rapidocr', animate: false });
    } catch (error) {
      scheduleRapidocrInstallPolling(2500);
    }
  }, delay);
}

function syncRapidocrInstallPolling(rapidocr) {
  if (rapidocr && rapidocr.isInstalling === true) {
    scheduleRapidocrInstallPolling();
    return;
  }
  stopRapidocrInstallPolling();
}

async function installLocalTool(tool) {
  const response = await fetch('/api/install-local-tool.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ tool })
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch (error) {
    payload = null;
  }

  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte starta lokal installation.');
  }

  await loadOcrProcessingSettings({ statusTarget: tool, animate: false });
}

async function fetchLocalToolInstallLog(tool) {
  const response = await fetch(`/api/get-local-tool-install-log.php?tool=${encodeURIComponent(tool)}`, {
    cache: 'no-store'
  });

  let payload = null;
  try {
    payload = await response.json();
  } catch (error) {
    payload = null;
  }

  if (!response.ok || !payload || typeof payload.log !== 'string') {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte läsa installationsloggen.');
  }

  return payload.log;
}

function renderOcrToolStatuses(payload, options = {}) {
  const target = typeof options.statusTarget === 'string' ? options.statusTarget : '';
  const silentOptions = { ...options, animate: false, deferRefreshVisibility: false };

  if (target === 'jbig2') {
    renderJbig2Status(payload.jbig2, options);
    return;
  }
  if (target === 'python') {
    renderPythonStatus(payload.python, options);
    renderRapidocrStatus(payload.rapidocr, silentOptions);
    return;
  }
  if (target === 'rapidocr') {
    renderPythonStatus(payload.python, silentOptions);
    renderRapidocrStatus(payload.rapidocr, options);
    return;
  }

  renderJbig2Status(payload.jbig2, options);
  renderPythonStatus(payload.python, options);
  renderRapidocrStatus(payload.rapidocr, options);
}

function renderPythonDependentStatuses(python) {
  if (!pythonStatusCardEl) {
    return;
  }

  const enabled = !!(python && python.installed);
  const childCards = Array.from(pythonStatusCardEl.querySelectorAll('.ocr-status-card-child'));
  childCards.forEach((cardEl) => {
    cardEl.classList.toggle('is-disabled', !enabled);
    const refreshButtons = Array.from(cardEl.querySelectorAll('.ocr-status-refresh'));
    refreshButtons.forEach((buttonEl) => {
      buttonEl.disabled = !enabled;
      buttonEl.title = enabled ? 'Kontrollera igen' : 'Kräver Python 3';
      buttonEl.setAttribute('aria-label', enabled ? 'Kontrollera igen' : 'Kräver Python 3');
    });
    const actionButtons = Array.from(cardEl.querySelectorAll('.ocr-status-action-button'));
    actionButtons.forEach((buttonEl) => {
      if (buttonEl.disabled && enabled) {
        return;
      }
      if (!enabled) {
        buttonEl.disabled = true;
        buttonEl.title = 'Kräver Python 3';
      }
    });
  });
}

async function loadClientsSettings() {
  const response = await fetch('/api/get-clients.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda huvudmän');
  }

  const payload = await response.json();
  if (!payload || typeof payload.text !== 'string') {
    throw new Error('Ogiltigt svar för huvudmän');
  }

  const rawText = payload.text.trim();
  let parsed = [];
  if (rawText !== '') {
    parsed = JSON.parse(rawText);
  }
  if (!Array.isArray(parsed)) {
    throw new Error('clients.json måste vara en JSON-lista');
  }

  clientsDraft = parsed.map(sanitizeClientDraft);
  clientsBaselineJson = normalizedClientsJson(clientsDraft);
  renderClientsEditor();
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
  matchingBaselineJson = normalizedMatchingJson(matchingDraft);
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
    || !payload.jbig2
    || typeof payload.jbig2 !== 'object'
    || !payload.python
    || typeof payload.python !== 'object'
    || !payload.rapidocr
    || typeof payload.rapidocr !== 'object'
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
  renderOcrToolStatuses(payload, options);
  renderOcrProcessingCommand();
  updateSettingsActionButtons();
}

async function loadCategories() {
  const response = await fetch('/api/get-categories.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda arkivstruktur');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.archiveFolders)) {
    throw new Error('Ogiltigt svar för arkivstruktur');
  }

  categoriesDraft = payload.archiveFolders.map(sanitizeArchiveFolder);
  categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft);
  renderCategoriesEditor();
  updateSettingsActionButtons();
}

async function loadLabels(options = {}) {
  const reload = options.reload === true;
  if (hasLoadedLabels && !reload) {
    if (labelsListEl) {
      renderLabelsEditor();
    }
    updateSettingsActionButtons();
    return;
  }

  const response = await fetch('/api/get-labels.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda etiketter');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.labels) || !payload.systemLabels || typeof payload.systemLabels !== 'object') {
    throw new Error('Ogiltigt svar för etiketter');
  }

  labelsDraft = payload.labels.map(sanitizeLabel);
  systemLabelsDraft = sanitizeSystemLabels(payload.systemLabels);
  labelsBaselineJson = normalizedLabelsJson(labelsDraft, systemLabelsDraft);
  hasLoadedLabels = true;
  renderLabelsEditor();
  if (categoriesListEl) {
    renderCategoriesEditor();
  }
  updateSettingsActionButtons();
}

async function loadExtractionFields() {
  const response = await fetch('/api/get-extraction-fields.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda datafält');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.fields) || !Array.isArray(payload.predefinedFields) || !Array.isArray(payload.systemFields)) {
    throw new Error('Ogiltigt svar för datafält');
  }

  extractionFieldsDraft = payload.fields.map((field, index) => sanitizeExtractionField(field, index));
  predefinedExtractionFieldsDraft = payload.predefinedFields.map((field, index) => sanitizeExtractionField(field, index));
  systemExtractionFieldsDraft = payload.systemFields.map((field, index) => sanitizeExtractionField(field, index));
  extractionFieldsBaselineJson = normalizedExtractionFieldsJson(extractionFieldsDraft, predefinedExtractionFieldsDraft, systemExtractionFieldsDraft);
  renderExtractionFieldsEditor();
  renderSystemExtractionFieldsEditor();
  if (categoriesListEl) {
    renderCategoriesEditor();
  }
  updateSettingsActionButtons();
}

async function saveClientsSettings() {
  const normalized = clientsDraft.map(serializeClientDraft);
  const text = JSON.stringify(normalized, null, 2);
  const response = await fetch('/api/save-clients.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ text })
  });

  if (!response.ok) {
    throw new Error('Kunde inte spara huvudmän');
  }

  clientsBaselineJson = JSON.stringify(normalized);
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
  const response = await fetch('/api/save-matching-settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      replacements: normalized
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
  matchingBaselineJson = normalizedMatchingJson(matchingDraft);
  renderMatchingEditor();
  updateSettingsActionButtons();
}

async function saveCategories() {
  const validationError = categoriesValidationError();
  if (validationError) {
    throw new Error(validationError);
  }

  const normalized = categoriesDraft.map(sanitizeArchiveFolder);
  const response = await fetch('/api/save-categories.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      archiveFolders: normalized,
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.archiveFolders)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara arkivstruktur';
    throw new Error(message);
  }

  categoriesDraft = payload.archiveFolders.map(sanitizeArchiveFolder);
  categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft);
  renderCategoriesEditor();
  updateSettingsActionButtons();
  await fetchState({ refreshCategories: true, force: true });
}

async function saveLabels() {
  const validationError = labelsValidationError();
  if (validationError) {
    throw new Error(validationError);
  }

  const normalizedLabels = labelsDraft.map(sanitizeLabel);
  const normalizedSystemLabels = sanitizeSystemLabels(systemLabelsDraft);
  const response = await fetch('/api/save-labels.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      labels: normalizedLabels,
      systemLabels: normalizedSystemLabels,
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.labels) || !payload.systemLabels || typeof payload.systemLabels !== 'object') {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara etiketter';
    throw new Error(message);
  }

  labelsDraft = payload.labels.map(sanitizeLabel);
  systemLabelsDraft = sanitizeSystemLabels(payload.systemLabels);
  labelsBaselineJson = normalizedLabelsJson(labelsDraft, systemLabelsDraft);
  renderLabelsEditor();
  if (categoriesListEl) {
    renderCategoriesEditor();
  }
  updateSettingsActionButtons();
  await fetchState({ force: true });
}

async function saveExtractionFields() {
  const normalizedExtractionFields = extractionFieldsDraft.map((field, index) => sanitizeExtractionField(field, index));
  const normalizedPredefinedExtractionFields = predefinedExtractionFieldsDraft.map((field, index) => sanitizeExtractionField(field, index));
  const normalizedSystemExtractionFields = systemExtractionFieldsDraft.map((field, index) => sanitizeExtractionField(field, index));
  const response = await fetch('/api/save-extraction-fields.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      fields: normalizedExtractionFields,
      predefinedFields: normalizedPredefinedExtractionFields,
      systemFields: normalizedSystemExtractionFields,
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.fields) || !Array.isArray(payload.predefinedFields) || !Array.isArray(payload.systemFields)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara datafält';
    throw new Error(message);
  }

  extractionFieldsDraft = payload.fields.map((field, index) => sanitizeExtractionField(field, index));
  predefinedExtractionFieldsDraft = payload.predefinedFields.map((field, index) => sanitizeExtractionField(field, index));
  systemExtractionFieldsDraft = payload.systemFields.map((field, index) => sanitizeExtractionField(field, index));
  extractionFieldsBaselineJson = normalizedExtractionFieldsJson(extractionFieldsDraft, predefinedExtractionFieldsDraft, systemExtractionFieldsDraft);
  renderExtractionFieldsEditor();
  renderSystemExtractionFieldsEditor();
  if (categoriesListEl) {
    renderCategoriesEditor();
  }
  updateSettingsActionButtons();
  await fetchState({ force: true });
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

  clearOcrViewCache();
  loadedOcrJobId = '';
  loadedOcrSource = '';
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
    archivedJobs: Array.isArray(state.archivedJobs) ? state.archivedJobs.map((job) => ({ ...job })) : [],
    failedJobs: Array.isArray(state.failedJobs) ? state.failedJobs.map((job) => ({ ...job })) : [],
    clients: state.clients,
    senders: state.senders,
    categories: state.categories
  };
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
      body: JSON.stringify({
        jobId,
        mode,
      })
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
      loadedOcrSource = '';
      loadedMatchesJobId = '';
      loadedMetaJobId = '';
      clearPdfFrames();
    }

    if (stateUpdateTransport !== 'sse') {
      scheduleStatePoll(0);
    }
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
    if (stateUpdateTransport !== 'sse') {
      scheduleStatePoll(0);
    }
    alert(error.message || 'Kunde inte köra om jobbet.');
  }
}

viewModeEl.addEventListener('change', () => {
  setViewMode(viewModeEl.value);
});

jobListModeEl.addEventListener('change', () => {
  setJobListMode(jobListModeEl.value);
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

  const selectedJob = findJobById(selectedJobId);
  if (selectedJob && selectedJob.archived === true) {
    setClientForJob(selectedJob);
    return;
  }

  const value = clientSelectEl.value;
  if (!value) {
    selectedClientByJobId.delete(selectedJobId);
  } else {
    selectedClientByJobId.set(selectedJobId, value);
  }
  const currentJob = findJobById(selectedJobId);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  saveSelectedJobFields(selectedJobId, { selectedClientDirName: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte spara huvudman.');
  });
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

  const selectedJob = findJobById(selectedJobId);
  if (selectedJob && selectedJob.archived === true) {
    setSenderForJob(selectedJob);
    return;
  }

  const value = senderSelectEl.value;
  if (!value) {
    selectedSenderByJobId.delete(selectedJobId);
  } else {
    selectedSenderByJobId.set(selectedJobId, value);
  }
  const currentJob = findJobById(selectedJobId);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  saveSelectedJobFields(selectedJobId, { selectedSenderId: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte spara avsändare.');
  });
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

  const selectedJob = findJobById(selectedJobId);
  if (selectedJob && selectedJob.archived === true) {
    setCategoryForJob(selectedJob);
    return;
  }

  const value = categorySelectEl.value;
  if (!value) {
    selectedCategoryByJobId.delete(selectedJobId);
  } else {
    selectedCategoryByJobId.set(selectedJobId, value);
  }
  const currentJob = findJobById(selectedJobId);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  saveSelectedJobFields(selectedJobId, { selectedCategoryId: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte spara kategori.');
  });
});

filenameInputEl.addEventListener('input', () => {
  if (!selectedJobId) {
    return;
  }
  const currentJob = findJobById(selectedJobId);
  if (!currentJob || currentJob.archived === true) {
    syncFilenameField(currentJob);
    return;
  }

  const value = filenameInputEl.value;
  if (!value.trim()) {
    filenameByJobId.delete(selectedJobId);
  } else {
    filenameByJobId.set(selectedJobId, value);
  }
  updateArchiveAction(currentJob);
  scheduleFilenameSave(selectedJobId, value);
});

archiveActionEl.addEventListener('click', async () => {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    return;
  }

  const action = selectedJob.archived === true ? 'restore' : 'archive';
  const filenameTimer = filenameSaveTimerByJobId.get(selectedJob.id);
  if (filenameTimer) {
    window.clearTimeout(filenameTimer);
    filenameSaveTimerByJobId.delete(selectedJob.id);
  }
  archiveActionEl.disabled = true;
  try {
    const response = await fetch('/api/archive-job.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        jobId: selectedJob.id,
        action,
        selectedClientDirName: effectiveClientDirName(selectedJob) || null,
        selectedSenderId: effectiveSenderId(selectedJob) || null,
        selectedCategoryId: effectiveCategoryId(selectedJob) || null,
        filename: filenameInputEl.value || displayedFilenameForJob(selectedJob),
      })
    });
    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload || payload.ok !== true) {
      const message = payload && typeof payload.error === 'string'
        ? payload.error
        : (action === 'restore' ? 'Kunde inte återställa jobbet.' : 'Kunde inte arkivera jobbet.');
      throw new Error(message);
    }

    filenameByJobId.delete(selectedJob.id);
    applyStateEntry(payload.entry);
  } catch (error) {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte uppdatera arkiveringen.');
  } finally {
    updateArchiveAction(findJobById(selectedJobId));
  }
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
        clientsDraft = [];
        clientsBaselineJson = normalizedClientsJson(clientsDraft);
        renderClientsEditor();
      } else if (tabId === 'senders') {
        alert('Kunde inte ladda avsändare.');
        sendersDraft = [];
        sendersBaselineJson = normalizedSendersJson(sendersDraft);
        renderSendersEditor();
      } else if (tabId === 'matching') {
        alert('Kunde inte ladda matchningsinställningar.');
        matchingDraft = [defaultReplacement()];
        matchingBaselineJson = normalizedMatchingJson(matchingDraft);
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
        categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft);
        renderCategoriesEditor();
      } else if (tabId === 'labels') {
        alert('Kunde inte ladda etiketter.');
        labelsDraft = [];
        systemLabelsDraft = createDefaultSystemLabels();
        labelsBaselineJson = normalizedLabelsJson(labelsDraft, systemLabelsDraft);
        renderLabelsEditor();
      } else if (tabId === 'data-fields') {
        alert('Kunde inte ladda datafält.');
        extractionFieldsDraft = [];
        predefinedExtractionFieldsDraft = [];
        systemExtractionFieldsDraft = [];
        extractionFieldsBaselineJson = normalizedExtractionFieldsJson(extractionFieldsDraft, predefinedExtractionFieldsDraft, systemExtractionFieldsDraft);
        renderExtractionFieldsEditor();
        renderSystemExtractionFieldsEditor();
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

if (ocrShowPageImageEl) {
  ocrShowPageImageEl.addEventListener('change', () => {
    ocrShowPageImage = ocrShowPageImageEl.checked;
    updateOcrPageImageControls();
    if (currentViewMode === 'ocr') {
      rerenderOcrPagesPreservingScroll();
    }
  });
}

if (ocrPageImageOpacityEl) {
  ocrPageImageOpacityEl.addEventListener('input', () => {
    const nextValue = Number.parseInt(ocrPageImageOpacityEl.value, 10);
    ocrPageImageBlend = Number.isFinite(nextValue)
      ? Math.max(0, Math.min(100, nextValue)) / 100
      : 0.5;
    updateOcrPageImageControls();
  });
}

ocrSourceTabEls.forEach((buttonEl) => {
  buttonEl.addEventListener('click', () => {
    const source = buttonEl.dataset.ocrSource || 'merged';
    if (normalizeOcrSource(source) === currentOcrSource) {
      return;
    }
    setActiveOcrSource(source);
  });
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

ocrPageCurrentEl.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter') {
    return;
  }
  event.preventDefault();
  scrollOcrPageIntoView(ocrPageCurrentEl.value);
});

ocrPageCurrentEl.addEventListener('focus', () => {
  ocrPageCurrentEl.select();
});

ocrPageCurrentEl.addEventListener('input', () => {
  scrollOcrPageIntoView(ocrPageCurrentEl.value);
});

ocrZoomOutEl.addEventListener('click', () => {
  stepOcrZoom(-1);
});

ocrZoomInEl.addEventListener('click', () => {
  stepOcrZoom(1);
});

ocrZoomInputEl.addEventListener('keydown', (event) => {
  if (event.key !== 'Enter') {
    return;
  }
  event.preventDefault();
  commitOcrZoomInput();
});

ocrZoomInputEl.addEventListener('focus', () => {
  ocrZoomInputEl.select();
});

ocrZoomInputEl.addEventListener('blur', () => {
  commitOcrZoomInput();
});

ocrZoomInputEl.addEventListener('change', () => {
  commitOcrZoomInput();
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

ocrPagesViewEl.addEventListener('scroll', () => {
  saveCurrentOcrViewState();
  syncOcrHighlightScroll();
  updateOcrPageControls();
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
  const force = options.force === true;
  const includeClients = !hasLoadedClients || refreshClients;
  const includeSenders = !hasLoadedSenders || refreshSenders;
  const includeCategories = !hasLoadedCategories || refreshCategories;

  pollInFlight = true;
  try {
    const params = new URLSearchParams();
    if (includeClients) {
      params.set('includeClients', '1');
    }
    if (includeSenders) {
      params.set('includeSenders', '1');
    }
    if (includeCategories) {
      params.set('includeCategories', '1');
    }
    if (!force && !includeClients && !includeSenders && !includeCategories && hasLoadedInitialJobsState) {
      params.set('afterEventId', String(stateEventCursor));
    }

    const url = params.toString() ? `/api/get-state.php?${params.toString()}` : '/api/get-state.php';
    const response = await fetch(url, { cache: 'no-store' });
    if (response.status === 204) {
      return;
    }
    if (!response.ok) {
      throw new Error('Kunde inte hämta status');
    }

    const nextState = await response.json();
    if (!nextState || !Array.isArray(nextState.readyJobs) || !Array.isArray(nextState.archivedJobs)) {
      throw new Error('Ogiltigt statussvar');
    }

    stateUpdateTransport = sanitizeStateUpdateTransport(
      nextState.stateUpdateTransport,
      stateUpdateTransport
    );

    if (Array.isArray(nextState.events)) {
      applyJobEvents(nextState.events);
      const lastEventId = Number.parseInt(String(nextState.lastEventId || ''), 10);
      if (Number.isInteger(lastEventId) && lastEventId > stateEventCursor) {
        stateEventCursor = lastEventId;
      }
      return;
    }

    applyState({
      processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : [],
      readyJobs: nextState.readyJobs,
      archivedJobs: nextState.archivedJobs,
      failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : [],
      archivingRules: nextState.archivingRules && typeof nextState.archivingRules === 'object' ? nextState.archivingRules : undefined,
      clients: includeClients && Array.isArray(nextState.clients) ? nextState.clients : undefined,
      senders: includeSenders && Array.isArray(nextState.senders) ? nextState.senders : undefined,
      categories: includeCategories && Array.isArray(nextState.categories) ? nextState.categories : undefined
    });

    const lastEventId = Number.parseInt(String(nextState.lastEventId || ''), 10);
    if (Number.isInteger(lastEventId) && lastEventId >= 0) {
      stateEventCursor = lastEventId;
    }

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
    if (!hasLoadedInitialJobsState) {
      setProcessingInfo([]);
      jobListEl.innerHTML = '';
      const li = document.createElement('li');
      li.className = 'job-message';
      li.textContent = 'Kunde inte ladda status.';
      jobListEl.appendChild(li);
    } else {
      console.error(error);
    }
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

  const streamUrl = `/api/stream-state.php?afterEventId=${encodeURIComponent(String(stateEventCursor))}`;
  const stream = new EventSource(streamUrl);
  stream.addEventListener('job', (event) => {
    if (!event || typeof event.data !== 'string' || event.data === '') {
      return;
    }

    try {
      const jobEvent = JSON.parse(event.data);
      if (!jobEvent || typeof jobEvent !== 'object') {
        return;
      }
      const eventId = Number.parseInt(String(event.lastEventId || ''), 10);
      if (Number.isInteger(eventId) && eventId > stateEventCursor) {
        stateEventCursor = eventId;
      }
      applyJobEvents([jobEvent]);
    } catch (error) {
      // Ignore malformed stream payloads and wait for next event.
    }
  });

  stream.addEventListener('keepalive', (event) => {
    if (!event || typeof event.data !== 'string' || event.data === '') {
      return;
    }
    try {
      const payload = JSON.parse(event.data);
      const lastEventId = Number.parseInt(String(payload && payload.lastEventId || ''), 10);
      if (Number.isInteger(lastEventId) && lastEventId > stateEventCursor) {
        stateEventCursor = lastEventId;
      }
    } catch (error) {
      // Ignore keepalive payload parse failures.
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
updateOcrPageImageControls();
applyOcrZoom();
setActiveOcrSource(currentOcrSource, { reload: false });
applyHashState();
window.addEventListener('hashchange', () => {
  applyHashState();
});
Promise.all([
  fetchState(),
  loadLabels().catch(() => {
    labelsDraft = [];
    systemLabelsDraft = createDefaultSystemLabels();
    labelsBaselineJson = normalizedLabelsJson(labelsDraft, systemLabelsDraft);
    console.error('Kunde inte ladda etiketter vid app-start.');
  }),
  loadExtractionFields().catch(() => {
    extractionFieldsDraft = [];
    predefinedExtractionFieldsDraft = [];
    systemExtractionFieldsDraft = [];
    extractionFieldsBaselineJson = normalizedExtractionFieldsJson(extractionFieldsDraft, predefinedExtractionFieldsDraft, systemExtractionFieldsDraft);
    console.error('Kunde inte ladda datafält vid app-start.');
  }),
]).finally(() => {
  syncStateUpdateTransport();
});
