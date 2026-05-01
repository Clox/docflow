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
const pdfViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="pdf"]') : null;
const ocrViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="ocr"]') : null;
const matchesViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="matches"]') : null;
const metaViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="meta"]') : null;
const reviewViewOptionEl = viewModeEl ? viewModeEl.querySelector('option[value="review"]') : null;
const ocrSearchBarEl = document.getElementById('ocr-search-bar');
const ocrSearchInputEl = document.getElementById('ocr-search-input');
const ocrSearchRegexEl = document.getElementById('ocr-search-regex');
const ocrSearchPrevEl = document.getElementById('ocr-search-prev');
const ocrSearchNextEl = document.getElementById('ocr-search-next');
const ocrSearchStatusEl = document.getElementById('ocr-search-status');
const ocrMenuWrapEl = document.getElementById('ocr-menu-wrap');
const ocrMenuButtonEl = document.getElementById('ocr-menu-button');
const ocrMenuEl = document.getElementById('ocr-menu');
const ocrDownloadActionEl = document.getElementById('ocr-download-action');
const mainEl = document.querySelector('.main');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const jobListModeEl = document.getElementById('job-list-mode');
const jobListMenuWrapEl = document.getElementById('job-list-menu-wrap');
const jobListMenuButtonEl = document.getElementById('job-list-menu-button');
const jobListMenuEl = document.getElementById('job-list-menu');
const jobListReanalyzeAllActionEl = document.getElementById('job-list-reanalyze-all-action');
const selectedJobActionsMenuWrapEl = document.getElementById('selected-job-actions-menu-wrap');
const selectedJobActionsMenuButtonEl = document.getElementById('selected-job-actions-menu-button');
const selectedJobActionsMenuEl = document.getElementById('selected-job-actions-menu');
const selectedJobDeleteActionEl = document.getElementById('selected-job-delete-action');
const sidebarEl = document.querySelector('.sidebar');
const sidebarSplitterEl = document.getElementById('sidebar-splitter');
const clientSelectEl = document.getElementById('client-select');
const senderSelectEl = document.getElementById('sender-select');
const folderSelectEl = document.getElementById('folder-select');
const resetClientActionEl = document.getElementById('reset-client-action');
const resetSenderActionEl = document.getElementById('reset-sender-action');
const resetFolderActionEl = document.getElementById('reset-folder-action');
const jobLabelsFieldEl = document.getElementById('job-labels-field');
const jobLabelsSummaryEl = document.getElementById('job-labels-summary');
const jobLabelsOverlayEl = document.getElementById('job-labels-overlay');
const jobLabelsOverlayCloseEl = document.getElementById('job-labels-overlay-close');
const jobLabelsComboboxEl = document.getElementById('job-labels-combobox');
const jobLabelsComboboxListEl = document.getElementById('job-labels-combobox-list');
const jobLabelsSelectedEl = document.getElementById('job-labels-selected');
const jobExtractionFieldsSectionEl = document.getElementById('job-extraction-fields-section');
const jobLabelsFieldGroupEl = jobLabelsFieldEl ? jobLabelsFieldEl.closest('.field-group-job-labels') : null;
const resetLabelsActionEl = document.getElementById('reset-labels-action');
const filenameInputEl = document.getElementById('filename-input');
const filenameControlRowEl = filenameInputEl ? filenameInputEl.closest('.field-group-control-row') : null;
const resetFilenameActionEl = document.getElementById('reset-filename-action');
const archiveActionEl = document.getElementById('archive-action');
const appNoticesEl = document.getElementById('app-notices');
const topbarEl = document.querySelector('.topbar');
const archivedReviewPanelEl = document.getElementById('archived-review-panel');
const settingsButtonEl = document.getElementById('settings-button');
const settingsModalEl = document.getElementById('settings-modal');
const settingsDialogEl = document.getElementById('settings-dialog');
const settingsDialogResizeHandleEl = document.getElementById('settings-dialog-resize-handle');
const settingsTabEls = Array.from(document.querySelectorAll('[data-settings-tab]'));
const archivingReviewSettingsTabEl = document.querySelector('[data-settings-tab="archiving-review"]');
const settingsPanelActionsHostEl = document.getElementById('settings-panel-actions-host');
const settingsCloseEl = document.getElementById('settings-close');
const selectedJobPanelEl = document.getElementById('selected-job-panel');
const selectedJobActionsPanelEl = document.getElementById('selected-job-actions-panel');
const selectedJobNameEl = document.getElementById('selected-job-name');
const selectedJobMetaEl = document.getElementById('selected-job-meta');
const selectedJobClientsSectionEl = document.getElementById('selected-job-clients-section');
const selectedJobClientLinkedInfoEl = document.getElementById('selected-job-client-linked-info');
const selectedJobSendersSectionEl = document.getElementById('selected-job-senders-section');
const selectedJobSenderLinkedInfoEl = document.getElementById('selected-job-sender-linked-info');
const selectedJobSenderUnknownSectionEl = document.getElementById('selected-job-sender-unknown-section');
const selectedJobSenderUnknownInfoEl = document.getElementById('selected-job-sender-unknown-info');
const selectedJobStatusEl = document.getElementById('selected-job-status');
let selectedJobReprocessEl = document.getElementById('selected-job-reprocess');
const dismissArchivedUpdateActionEl = document.getElementById('dismiss-archived-update-action');
const selectedJobActionsWarningEl = document.getElementById('selected-job-actions-warning');
const selectedJobActionsWarningReprocessEl = document.getElementById('selected-job-actions-warning-reprocess');
const settingsPanelTemplateIds = {
  clients: 'settings-template-clients',
  senders: 'settings-template-senders',
  matching: 'settings-template-matching',
  'ocr-processing': 'settings-template-ocr-processing',
  'archive-structure': 'settings-template-archive-structure',
  labels: 'settings-template-labels',
  'data-fields': 'settings-template-data-fields',
  'archiving-review': 'settings-template-archiving-review',
  paths: 'settings-template-paths',
  system: 'settings-template-system',
  extensions: 'settings-template-extensions'
};

function developmentUiEnabled() {
  const isLocalHost = ['localhost', '127.0.0.1'].includes(window.location.hostname);
  if (!isLocalHost) {
    return false;
  }
  return window.localStorage.getItem('docflowShowDevControls') === '1';
}

function reprocessUiEnabled() {
  return window.localStorage.getItem('docflowShowDevControls') === '1';
}

function bindSelectedJobReprocessButton(buttonEl) {
  if (!(buttonEl instanceof HTMLButtonElement) || buttonEl.dataset.bound === '1') {
    return buttonEl;
  }
  buttonEl.dataset.bound = '1';
  buttonEl.addEventListener('click', async (event) => {
    if (event.ctrlKey || event.metaKey) {
      await handleSelectedJobReprocess('full', { forceOcr: true });
      return;
    }
    await handleSelectedJobReprocess('post-ocr');
  });
  return buttonEl;
}

function syncSelectedJobReprocessButton() {
  const existing = document.getElementById('selected-job-reprocess');
  if (reprocessUiEnabled()) {
    if (existing instanceof HTMLButtonElement) {
      selectedJobReprocessEl = bindSelectedJobReprocessButton(existing);
      return selectedJobReprocessEl;
    }
    if (!(archiveActionEl instanceof HTMLButtonElement) || !(archiveActionEl.parentNode instanceof Node)) {
      selectedJobReprocessEl = null;
      return null;
    }
    const buttonEl = document.createElement('button');
    buttonEl.id = 'selected-job-reprocess';
    buttonEl.type = 'button';
    buttonEl.disabled = true;
    buttonEl.textContent = 'Analysera igen';
    archiveActionEl.parentNode.insertBefore(buttonEl, archiveActionEl);
    selectedJobReprocessEl = bindSelectedJobReprocessButton(buttonEl);
    return selectedJobReprocessEl;
  }
  if (existing instanceof HTMLElement) {
    existing.remove();
  }
  selectedJobReprocessEl = null;
  return null;
}

let clientsListEl = null;
let clientsAddRowEl = null;
let clientsCancelEl = null;
let clientsApplyEl = null;
let sendersListEl = null;
let sendersUnlinkedListEl = null;
let sendersAddRowEl = null;
let sendersCancelEl = null;
let sendersApplyEl = null;
let sendersSortOrderEl = null;
let sendersExpandAllEl = null;
let sendersCollapseAllEl = null;
let sendersViewSendersEl = null;
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
let matchingNoisePenaltyEl = null;
let matchingTrailingDelimiterPenaltyEl = null;
let matchingOtherMatchKeyPenaltyEl = null;
let matchingRightYOffsetPenaltyEl = null;
let matchingDownXOffsetPenaltyEl = null;
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
let archivingReviewActionsEl = null;
let pythonLocalInstallButtonEl = null;
let rapidocrStatusBadgeWrapEl = null;
let rapidocrStatusBadgeEl = null;
let rapidocrInstallCommandEl = null;
let rapidocrRefreshButtonEl = null;
let rapidocrInstallLogButtonEl = null;
let rapidocrLocalInstallButtonEl = null;
let ocrProcessingCancelEl = null;
let ocrProcessingApplyEl = null;
let archiveStructureListEl = null;
let archiveStructureAddFolderEl = null;
let archiveStructureFolderSortEl = null;
let archiveStructureCancelEl = null;
let archiveStructureApplyEl = null;
let labelsListEl = null;
let systemLabelEditorEl = null;
let labelsAddRowEl = null;
let labelsImportRowEl = null;
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
let archivingReviewTemplateChangesEl = null;
let archivingReviewJobsEl = null;
let labelsTabEls = [];
let labelsViewCustomEl = null;
let labelsViewSystemEl = null;
let settingsResetJobsEl = null;
let systemStateTransportEl = null;
let systemChromeExtensionStatusEl = null;
let systemChromeExtensionTestEl = null;
let systemChromeExtensionSuppressMissingEl = null;
let systemChromeExtensionDebugEl = null;
let systemChromeExtensionPageEl = null;
let systemChromeExtensionDirectoryEl = null;
let systemChromeExtensionCopyPageEl = null;
let systemChromeExtensionCopyDirectoryEl = null;
let inputInboxPathEl = null;
let outputBasePathEl = null;
let pathsCancelEl = null;
let pathsApplyEl = null;

let state = {
  processingJobs: [],
  readyJobs: [],
  archivedJobs: [],
  failedJobs: [],
  senderOrganizationLookupQueue: {
    remainingCount: 0,
    item: null,
  },
  senderPayeeLookupQueue: {
    remainingCount: 0,
    item: null,
  },
  clients: [],
  senders: [],
  archiveFolders: [],
  archivingRules: {
    activeVersion: 1,
    hasPendingArchivedUpdates: false,
    pendingArchivedUpdateCount: 0,
    updateReview: {
      activeArchivingRulesVersion: 1,
      changedSections: [],
      templateChanges: [],
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
      reason: '',
      signature: ''
    },
    signature: ''
  }
};

const SYSTEM_LABELS = {
  invoice: {
    name: 'Faktura',
    description: 'Dokument som är en faktura eller betalningsavi.',
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
    description: 'Dokument som rör autogiro eller automatisk betalning.',
    minScore: 3,
    rules: [
      { text: 'autogiro', score: 3 }
    ]
  }
};

const DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR = ', ';

let selectedJobId = '';
let loadedOcrJobId = '';
let loadedOcrSource = '';
let loadedMatchesJobId = '';
let loadedMatchesPayload = null;
let loadedMetaJobId = '';
let pdfFrameJobIds = pdfFrameEls.map(() => '');
let pollInFlight = false;
let pendingFetchStateOptions = null;
let stateStream = null;
let statePollTimer = null;
let stateUpdateTransport = 'polling';
let stateEventCursor = 0;
let archivingRulesLocalRevision = 0;
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
let archiveFoldersDraft = [];
let archiveStructureFolderSortMode = 'path';
let labelsDraft = [];
let systemLabelsDraft = createDefaultSystemLabels();
let labelsBuiltInCollapsed = true;
let labelsCustomCollapsed = false;
let sendersDraft = [];
let sendersUnlinkedIdentifiers = [];
let matchingDraft = [];
let matchingPositionAdjustmentDraft = defaultMatchingPositionAdjustmentSettings();
let matchingDataFieldAcceptanceThresholdDraft = 0.5;
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
let matchingBaselineJson = normalizedMatchingJson(
  [],
  defaultMatchingPositionAdjustmentSettings(),
  matchingDataFieldAcceptanceThresholdDraft
);
let pathsBaselineValue = '';
let inboxPathBaselineValue = '';
let archiveStructureBaselineJson = JSON.stringify({
  archiveFolders: [],
});
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
let folderOptionsSignature = '';
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
const selectedFolderByJobId = new Map();
const selectedLabelIdsByJobId = new Map();
const selectedExtractionFieldValuesByJobId = new Map();
const archivedReviewDraftByJobId = new Map();
const archivedReviewPayloadByJobId = new Map();
const filenameByJobId = new Map();
const filenameSaveTimerByJobId = new Map();
const lastKnownJobDisplayById = new Map();
const pinnedProcessingJobIds = new Set();
const pinnedProcessingOrderById = new Map();
const reprocessWatchJobIds = new Set();
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
const loadingSettingsPanels = new Set();
const EDIT_CLIENTS_OPTION_VALUE = '__edit_clients__';
const EDIT_SENDERS_OPTION_VALUE = '__edit_senders__';
const EDIT_ARCHIVE_STRUCTURE_OPTION_VALUE = '__edit_archive_structure__';
const VALID_VIEW_MODES = new Set(['pdf', 'ocr', 'matches', 'meta', 'review']);
const SETTINGS_DIALOG_MIN_WIDTH_PX = 720;
const SETTINGS_DIALOG_MIN_HEIGHT_PX = 520;
const SETTINGS_DIALOG_VIEWPORT_MARGIN_PX = 12;
const SETTINGS_DIALOG_DRAG_HANDLE_HEIGHT_PX = 44;
let settingsDialogLayout = null;
let settingsDialogDragState = null;
let settingsDialogResizeState = null;
const VALID_JOB_LIST_MODES = new Set(['all', 'ready', 'archived-review', 'processing', 'archived']);
const SIDEBAR_LIST_SIZE_STORAGE_KEY = 'docflow.sidebar.listSizePercent';
const DEFAULT_SIDEBAR_LIST_SIZE_PERCENT = 35;
const MIN_SIDEBAR_LIST_SIZE_PERCENT = 14;
const MAX_SIDEBAR_LIST_SIZE_PERCENT = 86;

let senderMergeState = null;
let currentJobListMode = 'ready';
let showDismissedArchivedReviewJobs = false;
let archivingRulesReviewPayload = null;
let archivingRulesReviewPayloadSignature = '';
let bulkResetWatchState = null;
let archivedJobReviewPayload = null;
let archivedReviewRequestSeq = 0;
let activeSidebarSplitPointerId = null;
let renderedArchivingReviewSignature = '';
let extractionFieldsDraft = [];
let predefinedExtractionFieldsDraft = [];
let systemExtractionFieldsDraft = [];
let extractionFieldsBuiltInCollapsed = true;
let extractionFieldsCustomCollapsed = false;
let activeExtractionFieldsTabId = 'fields';
let chromeExtensionRequiredId = '';
let chromeExtensionRequiredVersion = '';
let chromeExtensionDirectory = '';
let chromeExtensionSuppressMissingNotice = false;
let chromeExtensionRuntime = {
  status: 'unknown',
  version: '',
  lastError: '',
  organizationLookupLastError: '',
  swedbankSessionAvailable: null,
  hasAnySwedbankTab: false,
  hasAnyAllabolagTab: false,
  loginRequired: false,
  profileSelectionRequired: false,
  missingOrganizationCount: 0,
  missingPayeeCount: 0,
};
let chromeExtensionPingInFlight = false;
let chromeExtensionOrganizationLookupInFlight = false;
let chromeExtensionPayeeLookupInFlight = false;
let chromeExtensionPresenceTimer = null;
let jobLabelsOverlayOpen = false;
let jobLabelsDropdownOpen = false;
let jobLabelsFilterText = '';
let jobLabelsActiveOptionIndex = -1;
let jobLabelsRenderedOptions = [];
let jobLabelsOverlayPointerDownInside = false;
let jobLabelsOverlayMutating = false;
let jobLabelsSummaryRenderFrame = null;
let jobExtractionFieldInlineEditState = null;
let appNoticesOverflowOpen = false;
let topbarExpandedMetricsFrame = null;
const APP_NOTICES_COLLAPSED_MAX_HEIGHT = 76;
const uiTextMeasureCanvas = document.createElement('canvas');
const uiTextMeasureContext = uiTextMeasureCanvas.getContext('2d');

jobListModeEl.value = currentJobListMode;

clientSelectEl.disabled = true;
senderSelectEl.disabled = true;
folderSelectEl.disabled = true;
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

function countUnarchivedJobsInState() {
  return (
    (Array.isArray(state.processingJobs) ? state.processingJobs.length : 0)
    + (Array.isArray(state.readyJobs) ? state.readyJobs.length : 0)
    + (Array.isArray(state.failedJobs) ? state.failedJobs.length : 0)
  );
}

function requestStateRefresh(delay = 0) {
  if (stateUpdateTransport === 'sse') {
    return;
  }
  scheduleStatePoll(delay);
}

function clearBulkResetWatch() {
  bulkResetWatchState = null;
}

function updateBulkResetWatchFromState() {
  if (!bulkResetWatchState) {
    return;
  }

  const expectedJobCount = Number.isInteger(bulkResetWatchState.expectedJobCount)
    ? bulkResetWatchState.expectedJobCount
    : 0;
  const activeProcessingCount = Array.isArray(state.processingJobs) ? state.processingJobs.length : 0;
  const currentUnarchivedJobCount = countUnarchivedJobsInState();
  const settled = expectedJobCount > 0
    && currentUnarchivedJobCount >= expectedJobCount
    && activeProcessingCount === 0;
  const timedOut = (Date.now() - bulkResetWatchState.startedAtMs) >= 120000;
  if (settled || timedOut) {
    clearBulkResetWatch();
  }
}

function startBulkResetWatch(expectedJobCount) {
  clearBulkResetWatch();
  if (!Number.isInteger(expectedJobCount) || expectedJobCount < 1) {
    return;
  }

  bulkResetWatchState = {
    expectedJobCount,
    startedAtMs: Date.now(),
  };
}

function pruneReprocessWatchJobs() {
  Array.from(reprocessWatchJobIds).forEach((jobId) => {
    const job = findJobById(jobId);
    if (!job || job.status !== 'processing') {
      reprocessWatchJobIds.delete(jobId);
    }
  });
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

function archiveFolderDisplayName(folder) {
  if (folder && typeof folder.name === 'string' && folder.name.trim() !== '') {
    return folder.name.trim();
  }
  const template = folder && folder.pathTemplate && typeof folder.pathTemplate === 'object'
    ? sanitizeFilenameTemplate(folder.pathTemplate)
    : sanitizeFilenameTemplate(folder && folder.path);
  const firstText = Array.isArray(template.parts)
    ? template.parts.find((part) => part && typeof part === 'object' && part.type === 'text' && String(part.value || '').trim() !== '')
    : null;
  if (firstText && typeof firstText.value === 'string' && firstText.value.trim() !== '') {
    return firstText.value.trim();
  }
  return '';
}

function renderFolderSelect(folders) {
  const options = (Array.isArray(folders) ? folders : [])
    .map((folder, index) => ({
      value: folder && typeof folder.id === 'string' ? folder.id.trim() : '',
      label: archiveFolderDisplayName(folder),
      sortOrder: Number.isFinite(Number(folder && folder.sortOrder)) ? Number(folder.sortOrder) : (index + 1),
    }))
    .filter((option) => option.value !== '' && option.label !== '')
    .sort((left, right) => {
      if (left.sortOrder !== right.sortOrder) {
        return left.sortOrder - right.sortOrder;
      }
      return left.label.localeCompare(right.label, 'sv');
    });
  const signature = JSON.stringify({
    action: EDIT_ARCHIVE_STRUCTURE_OPTION_VALUE,
    options,
  });
  if (signature === folderOptionsSignature) {
    return;
  }

  const currentValue = folderSelectEl.value;
  folderSelectEl.innerHTML = '';

  const placeholderOption = document.createElement('option');
  placeholderOption.value = '';
  placeholderOption.hidden = true;
  placeholderOption.textContent = 'Välj mapp';
  folderSelectEl.appendChild(placeholderOption);

  const editOption = document.createElement('option');
  editOption.value = EDIT_ARCHIVE_STRUCTURE_OPTION_VALUE;
  editOption.textContent = 'Redigera arkivstruktur...';
  folderSelectEl.appendChild(editOption);

  const separatorOption = document.createElement('option');
  separatorOption.value = '__separator__';
  separatorOption.textContent = '──────────';
  separatorOption.disabled = true;
  folderSelectEl.appendChild(separatorOption);

  options.forEach((item) => {
    const option = document.createElement('option');
    option.value = item.value;
    option.textContent = item.label;
    folderSelectEl.appendChild(option);
  });

  const hasCurrentValue = options.some((item) => item.value === currentValue);
  folderSelectEl.value = hasCurrentValue ? currentValue : '';
  folderOptionsSignature = signature;
}

function clampSidebarListSizePercent(value) {
  const numeric = Number(value);
  if (!Number.isFinite(numeric)) {
    return DEFAULT_SIDEBAR_LIST_SIZE_PERCENT;
  }
  return Math.max(MIN_SIDEBAR_LIST_SIZE_PERCENT, Math.min(MAX_SIDEBAR_LIST_SIZE_PERCENT, numeric));
}

function sidebarVariablePanelsMetrics() {
  if (!(sidebarEl instanceof HTMLElement)) {
    return null;
  }
  const sidebarRect = sidebarEl.getBoundingClientRect();
  if (sidebarRect.height <= 0) {
    return null;
  }
  const titleHeight = (() => {
    const titleEl = sidebarEl.querySelector('.sidebar-title');
    return titleEl instanceof HTMLElement ? titleEl.getBoundingClientRect().height : 0;
  })();
  const splitterHeight = sidebarSplitterEl instanceof HTMLElement
    ? sidebarSplitterEl.getBoundingClientRect().height
    : 12;
  const dividerHeight = (() => {
    const dividerEl = sidebarEl.querySelector('.sidebar-divider');
    return dividerEl instanceof HTMLElement ? dividerEl.getBoundingClientRect().height : 8;
  })();
  const actionsHeight = selectedJobActionsPanelEl instanceof HTMLElement
    ? selectedJobActionsPanelEl.getBoundingClientRect().height
    : 0;
  const availableHeight = Math.max(0, sidebarRect.height - titleHeight - splitterHeight - dividerHeight - actionsHeight);
  return {
    titleHeight,
    availableHeight,
    sidebarHeight: sidebarRect.height,
  };
}

function applySidebarListSizePercent(value, persist = true) {
  if (!(sidebarEl instanceof HTMLElement)) {
    return;
  }
  const nextValue = clampSidebarListSizePercent(value);
  const metrics = sidebarVariablePanelsMetrics();
  if (metrics && metrics.availableHeight > 0) {
    const nextPixelSize = metrics.titleHeight + ((metrics.availableHeight * nextValue) / 100);
    sidebarEl.style.setProperty('--sidebar-list-size', `${nextPixelSize}px`);
  } else {
    sidebarEl.style.setProperty('--sidebar-list-size', `${nextValue}%`);
  }
  if (!persist) {
    return;
  }
  try {
    window.localStorage.setItem(SIDEBAR_LIST_SIZE_STORAGE_KEY, String(nextValue));
  } catch (error) {
    // Ignore local storage failures and keep the in-memory split.
  }
}

function restoreSidebarListSizePercent() {
  let nextValue = DEFAULT_SIDEBAR_LIST_SIZE_PERCENT;
  try {
    const stored = window.localStorage.getItem(SIDEBAR_LIST_SIZE_STORAGE_KEY);
    if (stored !== null) {
      nextValue = clampSidebarListSizePercent(Number.parseFloat(stored));
    }
  } catch (error) {
    nextValue = DEFAULT_SIDEBAR_LIST_SIZE_PERCENT;
  }
  applySidebarListSizePercent(nextValue, false);
}

function updateSidebarSplitFromPointer(clientY) {
  const metrics = sidebarVariablePanelsMetrics();
  if (!metrics || !(sidebarEl instanceof HTMLElement)) {
    return;
  }
  const rect = sidebarEl.getBoundingClientRect();
  if (rect.height <= 0 || metrics.availableHeight <= 0) {
    return;
  }
  const relativeY = clientY - rect.top - metrics.titleHeight;
  const nextPercent = (relativeY / metrics.availableHeight) * 100;
  applySidebarListSizePercent(nextPercent);
}

function stopSidebarSplitDrag(event = null) {
  if (sidebarSplitterEl instanceof HTMLElement && activeSidebarSplitPointerId !== null) {
    try {
      if (event && typeof event.pointerId === 'number' && sidebarSplitterEl.hasPointerCapture(event.pointerId)) {
        sidebarSplitterEl.releasePointerCapture(event.pointerId);
      }
    } catch (error) {
      // Ignore capture release failures.
    }
    sidebarSplitterEl.classList.remove('is-dragging');
  }
  document.body.classList.remove('is-resizing-sidebar');
  activeSidebarSplitPointerId = null;
}

function startSidebarSplitDrag(event) {
  if (!(sidebarEl instanceof HTMLElement) || !(sidebarSplitterEl instanceof HTMLElement)) {
    return;
  }
  if (typeof event.button === 'number' && event.button !== 0) {
    return;
  }
  event.preventDefault();
  activeSidebarSplitPointerId = typeof event.pointerId === 'number' ? event.pointerId : null;
  sidebarSplitterEl.classList.add('is-dragging');
  document.body.classList.add('is-resizing-sidebar');
  try {
    if (activeSidebarSplitPointerId !== null) {
      sidebarSplitterEl.setPointerCapture(activeSidebarSplitPointerId);
    }
  } catch (error) {
    // Ignore pointer capture failures and keep dragging with window events.
  }
  updateSidebarSplitFromPointer(event.clientY);
}

function handleSidebarSplitPointerMove(event) {
  if (activeSidebarSplitPointerId === null) {
    return;
  }
  if (typeof event.pointerId === 'number' && event.pointerId !== activeSidebarSplitPointerId) {
    return;
  }
  updateSidebarSplitFromPointer(event.clientY);
}

function selectedJobStatusInfo(job) {
  if (!job || job.status !== 'processing') {
    return null;
  }

  const isReprocess = typeof job.reprocessMode === 'string' && job.reprocessMode.trim() !== '';
  const isAutoReprocess = job.analysisAutoReprocessQueued === true;

  return {
    text: isReprocess && !isAutoReprocess ? 'Analyseras på nytt...' : 'Analyseras...',
  };
}

function renderSelectedJobStatus(job) {
  if (!(selectedJobStatusEl instanceof HTMLElement)) {
    return;
  }

  const statusInfo = selectedJobStatusInfo(job);
  if (!statusInfo) {
    selectedJobStatusEl.hidden = true;
    selectedJobStatusEl.replaceChildren();
    return;
  }

  selectedJobStatusEl.hidden = false;
  selectedJobStatusEl.replaceChildren();

  const spinnerEl = document.createElement('span');
  spinnerEl.className = 'spinner selected-job-status-spinner';
  spinnerEl.setAttribute('aria-hidden', 'true');
  selectedJobStatusEl.appendChild(spinnerEl);
  selectedJobStatusEl.appendChild(document.createTextNode(statusInfo.text));
}

function setClientForJob(job) {
  clientSelectEl.disabled = !selectedJobArchivingEditable(job);

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
  senderSelectEl.disabled = !selectedJobArchivingEditable(job);

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

function setFolderForJob(job) {
  folderSelectEl.disabled = !selectedJobArchivingEditable(job);

  if (!job) {
    folderSelectEl.value = '';
    return;
  }

  const resolvedValue = effectiveFolderId(job);
  if (resolvedValue) {
    const hasManualOption = Array.from(folderSelectEl.options).some(
      (option) => option.value === resolvedValue
    );
    if (hasManualOption) {
      folderSelectEl.value = resolvedValue;
      return;
    }
  }
  folderSelectEl.value = '';
}

function normalizeSelectedLabelIds(input) {
  const resolved = [];
  const seen = new Set();
  (Array.isArray(input) ? input : []).forEach((value) => {
    const labelId = typeof value === 'string' ? value.trim() : '';
    if (!labelId || seen.has(labelId)) {
      return;
    }
    seen.add(labelId);
    resolved.push(labelId);
  });
  return resolved;
}

function effectiveSelectedLabelIds(job) {
  if (!job) {
    return [];
  }
  if (selectedLabelIdsByJobId.has(job.id)) {
    return normalizeSelectedLabelIds(selectedLabelIdsByJobId.get(job.id));
  }
  if (Array.isArray(job.selectedLabelIds)) {
    return normalizeSelectedLabelIds(job.selectedLabelIds);
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && Array.isArray(autoResult.labels)) {
    return normalizeSelectedLabelIds(autoResult.labels);
  }
  if (job.analysis && typeof job.analysis === 'object' && Array.isArray(job.analysis.labels)) {
    return normalizeSelectedLabelIds(job.analysis.labels);
  }
  return [];
}

function normalizeSelectedExtractionFieldValueList(input) {
  const resolved = [];
  const seen = new Set();
  (Array.isArray(input) ? input : []).forEach((value) => {
    const resolvedValue = typeof value === 'string' ? value.trim() : '';
    if (resolvedValue === '' || seen.has(resolvedValue)) {
      return;
    }
    seen.add(resolvedValue);
    resolved.push(resolvedValue);
  });
  return resolved;
}

function normalizeSelectedExtractionFieldSelection(input) {
  if (!input || typeof input !== 'object') {
    return null;
  }

  const manualValues = normalizeSelectedExtractionFieldValueList(input.manualValues);
  const excludedValues = normalizeSelectedExtractionFieldValueList(input.excludedValues);
  const primaryValue = typeof input.primaryValue === 'string'
    ? input.primaryValue.trim()
    : '';
  if (manualValues.length < 1 && excludedValues.length < 1 && primaryValue === '') {
    return null;
  }

  return {
    manualValues,
    excludedValues,
    primaryValue: primaryValue !== '' ? primaryValue : null,
  };
}

function normalizeSelectedExtractionFieldValues(input) {
  if (!input || typeof input !== 'object' || Array.isArray(input)) {
    return {};
  }
  const resolved = {};
  Object.entries(input).forEach(([fieldKey, selection]) => {
    const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
    if (normalizedKey === '') {
      return;
    }
    const normalizedSelection = normalizeSelectedExtractionFieldSelection(selection);
    if (!normalizedSelection) {
      return;
    }
    resolved[normalizedKey] = normalizedSelection;
  });
  return resolved;
}

function selectedJobLabelsEditable(job) {
  return selectedJobArchivingEditable(job);
}

function effectiveSelectedExtractionFieldValues(job) {
  if (!job) {
    return {};
  }
  if (selectedExtractionFieldValuesByJobId.has(job.id)) {
    return normalizeSelectedExtractionFieldValues(selectedExtractionFieldValuesByJobId.get(job.id));
  }
  if (job.selectedExtractionFieldValues && typeof job.selectedExtractionFieldValues === 'object') {
    return normalizeSelectedExtractionFieldValues(job.selectedExtractionFieldValues);
  }
  return {};
}

function effectiveSelectedExtractionFieldSelection(job, fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!job || normalizedKey === '') {
    return null;
  }
  const selections = effectiveSelectedExtractionFieldValues(job);
  return sanitizeSelectedExtractionFieldSelectionForJob(job, normalizedKey, selections[normalizedKey]);
}

function sanitizeSelectedExtractionFieldSelectionForJob(job, fieldKey, input) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  const normalizedSelection = normalizeSelectedExtractionFieldSelection(input);
  if (!job || normalizedKey === '' || !normalizedSelection) {
    return normalizedSelection;
  }

  const acceptedValues = new Set(
    extractionFieldAcceptedRowsForJob(job, normalizedKey)
      .map((row) => (row && typeof row.value === 'string' ? row.value.trim() : ''))
      .filter((value) => value !== '')
  );
  const manualValues = normalizeSelectedExtractionFieldValueList(normalizedSelection.manualValues)
    .filter((value) => value !== '' && !acceptedValues.has(value));
  const excludedValues = normalizeSelectedExtractionFieldValueList(normalizedSelection.excludedValues);
  const primaryValue = typeof normalizedSelection.primaryValue === 'string'
    ? normalizedSelection.primaryValue.trim()
    : '';

  if (manualValues.length < 1 && excludedValues.length < 1 && primaryValue === '') {
    return null;
  }

  return {
    manualValues,
    excludedValues,
    primaryValue: primaryValue !== '' ? primaryValue : null,
  };
}

function sanitizeSelectedExtractionFieldValuesForJob(job, input) {
  const normalized = normalizeSelectedExtractionFieldValues(input);
  if (!job) {
    return normalized;
  }

  const resolved = {};
  Object.entries(normalized).forEach(([fieldKey, selection]) => {
    const sanitizedSelection = sanitizeSelectedExtractionFieldSelectionForJob(job, fieldKey, selection);
    if (sanitizedSelection) {
      resolved[fieldKey] = sanitizedSelection;
    }
  });
  return resolved;
}

function extractionFieldComparisonKeysForJob(job) {
  const keys = new Set();
  if (!job) {
    return [];
  }

  const meta = job.analysis && typeof job.analysis === 'object' && job.analysis.extractionFieldMeta && typeof job.analysis.extractionFieldMeta === 'object'
    ? job.analysis.extractionFieldMeta
    : {};
  Object.keys(meta || {}).forEach((fieldKey) => {
    const normalized = typeof fieldKey === 'string' ? fieldKey.trim() : '';
    if (normalized !== '') {
      keys.add(normalized);
    }
  });

  const selections = effectiveSelectedExtractionFieldValues(job);
  Object.keys(selections || {}).forEach((fieldKey) => {
    const normalized = typeof fieldKey === 'string' ? fieldKey.trim() : '';
    if (normalized !== '') {
      keys.add(normalized);
    }
  });

  const requiredKeys = requiredExtractionFieldKeysForJob(job);
  requiredKeys.forEach((fieldKey) => {
    const normalized = typeof fieldKey === 'string' ? fieldKey.trim() : '';
    if (normalized !== '') {
      keys.add(normalized);
    }
  });

  return Array.from(keys).sort((left, right) => left.localeCompare(right, 'sv'));
}

function extractionFieldAutoPrimaryValueForJob(job, fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!job || normalizedKey === '') {
    return '';
  }

  const acceptedRows = extractionFieldAcceptedRowsForJob(job, normalizedKey);
  const primaryValue = acceptedRows.length > 0 && typeof acceptedRows[0].value === 'string'
    ? acceptedRows[0].value.trim()
    : '';
  if (primaryValue !== '') {
    return primaryValue;
  }

  const meta = extractionFieldAnalysisForJob(job, normalizedKey);
  const autoValues = Array.isArray(meta && meta.values) ? meta.values : [];
  const fallback = typeof autoValues[0] === 'string' ? autoValues[0].trim() : '';
  return fallback;
}

function extractionFieldSelectionDiffersFromAuto(job, fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!job || normalizedKey === '') {
    return false;
  }

  const selection = selectedExtractionFieldSelectionForJob(job, normalizedKey);
  if (!selection) {
    return false;
  }

  const manualValues = normalizeSelectedExtractionFieldValueList(selection.manualValues);
  const excludedValues = normalizeSelectedExtractionFieldValueList(selection.excludedValues);
  const primaryValue = typeof selection.primaryValue === 'string' ? selection.primaryValue.trim() : '';
  const autoPrimaryValue = extractionFieldAutoPrimaryValueForJob(job, normalizedKey);

  if (manualValues.length > 0 || excludedValues.length > 0) {
    return true;
  }
  if (primaryValue !== '' && primaryValue !== autoPrimaryValue) {
    return true;
  }
  return false;
}

function extractionFieldSelectionsDifferFromAuto(job) {
  const comparisonKeys = extractionFieldComparisonKeysForJob(job);
  if (comparisonKeys.length < 1) {
    return false;
  }

  return comparisonKeys.some((fieldKey) => extractionFieldSelectionDiffersFromAuto(job, fieldKey));
}

function extractionFieldAnalysisForJob(job, fieldKey) {
  if (!job || !job.analysis || typeof job.analysis !== 'object') {
    return null;
  }
  const meta = job.analysis.extractionFieldMeta;
  if (!meta || typeof meta !== 'object') {
    return null;
  }
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (normalizedKey === '') {
    return null;
  }
  return meta[normalizedKey] && typeof meta[normalizedKey] === 'object' ? meta[normalizedKey] : null;
}

function extractionFieldAcceptedRowsForJob(job, fieldKey) {
  const meta = extractionFieldAnalysisForJob(job, fieldKey);
  if (!meta) {
    return [];
  }
  const threshold = Number.isFinite(Number(matchingDataFieldAcceptanceThresholdDraft))
    ? Number(matchingDataFieldAcceptanceThresholdDraft)
    : 0.5;
  const rows = Array.isArray(meta.matches) ? meta.matches : [];
  return rows
    .map((match) => {
      if (!match || typeof match !== 'object') {
        return null;
      }
      const value = typeof match.value === 'string' ? match.value.trim() : '';
      if (value === '') {
        return null;
      }
      const finalConfidence = Number.isFinite(Number(match.finalConfidence)) ? Number(match.finalConfidence) : null;
      const confidence = Number.isFinite(Number(match.confidence)) ? Number(match.confidence) : null;
      const resolvedConfidence = finalConfidence ?? confidence ?? 0;
      return {
        value,
        confidence: resolvedConfidence,
        finalConfidence,
        manual: false,
      };
    })
    .filter((row) => row && row.value !== '' && row.finalConfidence !== null && row.finalConfidence >= threshold);
}

function primaryExtractionFieldValueForJob(job, fieldKey) {
  const meta = extractionFieldAnalysisForJob(job, fieldKey);
  const selection = effectiveSelectedExtractionFieldSelection(job, fieldKey);
  const acceptedRows = extractionFieldAcceptedRowsForJob(job, fieldKey);
  const autoValues = Array.isArray(meta && meta.values) ? meta.values : [];
  const candidateValues = acceptedRows.map((row) => row.value).filter((value) => value !== '');
  const manualValues = selection && Array.isArray(selection.manualValues) ? selection.manualValues : [];

  if (selection && typeof selection.primaryValue === 'string' && selection.primaryValue.trim() !== '') {
    const primary = selection.primaryValue.trim();
    if (candidateValues.includes(primary) || manualValues.includes(primary)) {
      return primary;
    }
  }

  if (manualValues.length > 0) {
    return manualValues[0];
  }

  if (candidateValues.length > 0) {
    return candidateValues[0];
  }

  if (autoValues.length > 0) {
    const firstAuto = typeof autoValues[0] === 'string' ? autoValues[0].trim() : '';
    if (firstAuto !== '') {
      return firstAuto;
    }
  }

  return '';
}

function extractionFieldDisplayNameByKey(fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!normalizedKey) {
    return '';
  }

  const knownFields = [...predefinedExtractionFieldsDraft, ...extractionFieldsDraft, ...systemExtractionFieldsDraft];
  const match = knownFields
    .map((field, index) => sanitizeExtractionField(field, index))
    .find((field) => field && typeof field.key === 'string' && field.key.trim() === normalizedKey);
  return match && typeof match.name === 'string' && match.name.trim() !== ''
    ? match.name.trim()
    : normalizedKey;
}

function collectFilenameTemplateFieldKeysFromParts(parts, keys = new Set()) {
  (Array.isArray(parts) ? parts : []).forEach((part) => {
    if (!part || typeof part !== 'object') {
      return;
    }

    if (part.type === 'dataField') {
      const key = typeof part.key === 'string' ? part.key.trim() : '';
      if (key !== '') {
        keys.add(key);
      }
    }

    collectFilenameTemplateFieldKeysFromParts(part.prefixParts || [], keys);
    collectFilenameTemplateFieldKeysFromParts(part.suffixParts || [], keys);

    if (part.type === 'firstAvailable') {
      collectFilenameTemplateFieldKeysFromParts(part.parts || [], keys);
      return;
    }

    if (part.type === 'ifLabels') {
      collectFilenameTemplateFieldKeysFromParts(part.thenParts || [], keys);
      collectFilenameTemplateFieldKeysFromParts(part.elseParts || [], keys);
    }
  });

  return keys;
}

function requiredExtractionFieldKeysForJob(job) {
  const keys = new Set();
  if (!job) {
    return keys;
  }

  const folder = findArchiveFolderById(effectiveFolderId(job));
  if (folder && folder.pathTemplate && typeof folder.pathTemplate === 'object') {
    collectFilenameTemplateFieldKeysFromParts(
      sanitizeFilenameTemplate(folder.pathTemplate).parts || [],
      keys
    );
  }

  if (folder) {
    const template = selectArchiveFolderFilenameTemplateByLabelIds(folder, effectiveSelectedLabelIds(job));
    if (template && template.template && typeof template.template === 'object') {
      collectFilenameTemplateFieldKeysFromParts(
        sanitizeFilenameTemplate(template.template).parts || [],
        keys
      );
    }
  }

  return keys;
}

function extractionFieldVisibleRowsForJob(job, fieldKey) {
  const meta = extractionFieldAnalysisForJob(job, fieldKey);
  if (!meta) {
    return [];
  }

  const selection = effectiveSelectedExtractionFieldSelection(job, fieldKey);
  const manualValues = normalizeSelectedExtractionFieldValueList(selection && selection.manualValues);
  const excludedValues = new Set(normalizeSelectedExtractionFieldValueList(selection && selection.excludedValues));
  const primaryValue = primaryExtractionFieldValueForJob(job, fieldKey);
  const acceptedRows = extractionFieldAcceptedRowsForJob(job, fieldKey)
    .filter((row) => row && row.value && !excludedValues.has(row.value));
  const acceptedValues = new Set(acceptedRows.map((row) => row.value));
  const manualRows = manualValues
    .filter((value) => value !== '' && !excludedValues.has(value) && !acceptedValues.has(value))
    .map((value) => ({
      value,
      confidence: null,
      finalConfidence: null,
      manual: true,
    }));

  const rowsByValue = new Map();
  const addRow = (row, source) => {
    if (!row || typeof row.value !== 'string' || row.value.trim() === '') {
      return;
    }
    const value = row.value.trim();
    const existing = rowsByValue.get(value) || null;
    const next = {
      ...row,
      value,
      manual: source === 'manual' || row.manual === true || (existing && existing.manual === true),
      accepted: source === 'accepted' || row.accepted === true || (existing && existing.accepted === true),
      primary: value === primaryValue,
    };
    rowsByValue.set(value, next);
  };

  acceptedRows.forEach((row) => addRow(row, 'accepted'));
  manualRows.forEach((row) => addRow(row, 'manual'));

  if (primaryValue !== '' && !rowsByValue.has(primaryValue) && !excludedValues.has(primaryValue)) {
    addRow({
      value: primaryValue,
      confidence: null,
      finalConfidence: null,
      manual: true,
      synthetic: true,
    }, 'manual');
  }

  const rows = Array.from(rowsByValue.values());
  rows.sort((left, right) => {
    const leftPrimary = left.primary === true ? 1 : 0;
    const rightPrimary = right.primary === true ? 1 : 0;
    if (leftPrimary !== rightPrimary) {
      return rightPrimary - leftPrimary;
    }

    const leftManual = left.manual === true ? 1 : 0;
    const rightManual = right.manual === true ? 1 : 0;
    if (leftManual !== rightManual) {
      return rightManual - leftManual;
    }

    const leftConfidence = typeof left.finalConfidence === 'number'
      ? left.finalConfidence
      : (typeof left.confidence === 'number' ? left.confidence : -1);
    const rightConfidence = typeof right.finalConfidence === 'number'
      ? right.finalConfidence
      : (typeof right.confidence === 'number' ? right.confidence : -1);
    if (leftConfidence !== rightConfidence) {
      return rightConfidence - leftConfidence;
    }

    return left.value.localeCompare(right.value, 'sv');
  });

  return rows;
}

function currentSelectedJobLabelOptions() {
  return archiveRuleLabelOptionsList()
    .map((option) => ({
      value: typeof option.value === 'string' ? option.value.trim() : '',
      label: typeof option.label === 'string' ? option.label.trim() : '',
    }))
    .filter((option) => option.value !== '' && option.label !== '')
    .sort((left, right) => left.label.localeCompare(right.label, 'sv'));
}

function labelDisplayName(labelId) {
  const normalizedId = typeof labelId === 'string' ? labelId.trim() : '';
  if (!normalizedId) {
    return '';
  }
  return filenameTemplateLabelNameById(normalizedId) || normalizedId;
}

function createJobLabelsSummaryChip(text, className = 'job-labels-summary-chip') {
  const chipEl = document.createElement('span');
  chipEl.className = className;
  const textEl = document.createElement('span');
  textEl.className = className === 'job-labels-summary-overflow'
    ? ''
    : 'job-labels-summary-chip-text';
  textEl.textContent = text;
  chipEl.appendChild(textEl);
  return chipEl;
}

function measureUiTextWidth(text, font) {
  if (!uiTextMeasureContext) {
    return String(text || '').length * 8;
  }
  uiTextMeasureContext.font = font || '14px Arial';
  return uiTextMeasureContext.measureText(String(text || '')).width;
}

function computeExpandableRowMaxWidth(fieldGroupEl) {
  if (!(fieldGroupEl instanceof HTMLElement)) {
    return Math.max(320, window.innerWidth - 48);
  }
  const rect = fieldGroupEl.getBoundingClientRect();
  return Math.max(rect.width, window.innerWidth - rect.left - 24);
}

function syncFilenameExpandedWidth(job = findJobById(selectedJobId)) {
  if (!(filenameInputEl instanceof HTMLInputElement) || !(filenameControlRowEl instanceof HTMLElement)) {
    return;
  }

  const baseWidth = filenameControlRowEl.offsetWidth || filenameInputEl.offsetWidth || 0;
  const font = window.getComputedStyle(filenameInputEl).font;
  const text = job ? displayedFilenameForJob(job) : filenameInputEl.value;
  const textWidth = Math.ceil(measureUiTextWidth(text || filenameInputEl.placeholder || '', font) + 28);
  const resetWidth = resetFilenameActionEl instanceof HTMLButtonElement && !resetFilenameActionEl.hidden
    ? (resetFilenameActionEl.offsetWidth || 28) + 6
    : 0;
  const desiredWidth = Math.max(baseWidth, textWidth + resetWidth);
  const maxWidth = computeExpandableRowMaxWidth(filenameControlRowEl.closest('.field-group'));
  const fieldGroupEl = filenameControlRowEl.closest('.field-group');
  if (!(fieldGroupEl instanceof HTMLElement)) {
    return;
  }
  fieldGroupEl.style.setProperty('--filename-expanded-row-width', `${Math.min(desiredWidth, maxWidth)}px`);
  fieldGroupEl.style.setProperty('--filename-expanded-max-width', `${Math.floor(maxWidth)}px`);
}

function setFilenameFieldExpanded(expanded) {
  const fieldGroupEl = filenameInputEl ? filenameInputEl.closest('.field-group-filename') : null;
  if (!(fieldGroupEl instanceof HTMLElement) || !(filenameInputEl instanceof HTMLInputElement)) {
    return;
  }
  const canExpand = expanded === true && filenameInputEl.disabled !== true;
  fieldGroupEl.classList.toggle('is-expanded', canExpand);
}

function syncJobLabelsExpandedWidth(job = findJobById(selectedJobId)) {
  if (!(jobLabelsFieldGroupEl instanceof HTMLElement) || !(jobLabelsFieldEl instanceof HTMLButtonElement)) {
    return;
  }

  const controlRowEl = jobLabelsFieldEl.closest('.field-group-control-row');
  if (!(controlRowEl instanceof HTMLElement)) {
    return;
  }

  const baseWidth = controlRowEl.offsetWidth || jobLabelsFieldEl.offsetWidth || 0;
  const labelNames = normalizeSelectedLabelIds(effectiveSelectedLabelIds(job))
    .map((labelId) => labelDisplayName(labelId))
    .filter((labelName) => labelName !== '');
  const chipFont = `11px ${window.getComputedStyle(jobLabelsFieldEl).fontFamily}`;
  const chipWidths = labelNames.length > 0
    ? labelNames.map((labelName) => Math.ceil(measureUiTextWidth(labelName, chipFont) + 24))
    : [Math.ceil(measureUiTextWidth('Inga etiketter', window.getComputedStyle(jobLabelsFieldEl).font) + 20)];
  const chipsTotalWidth = chipWidths.reduce((sum, width) => sum + width, 0) + Math.max(0, chipWidths.length - 1) * 6 + 18;
  const resetWidth = resetLabelsActionEl instanceof HTMLButtonElement && !resetLabelsActionEl.hidden
    ? (resetLabelsActionEl.offsetWidth || 28) + 6
    : 0;
  const desiredWidth = Math.max(baseWidth, chipsTotalWidth + resetWidth);
  const maxWidth = computeExpandableRowMaxWidth(jobLabelsFieldGroupEl);
  jobLabelsFieldGroupEl.style.setProperty('--job-labels-expanded-row-width', `${Math.min(desiredWidth, maxWidth)}px`);
  jobLabelsFieldGroupEl.style.setProperty('--job-labels-expanded-max-width', `${Math.floor(maxWidth)}px`);
}

function queueTopbarExpandedMetricsSync() {
  if (topbarExpandedMetricsFrame !== null) {
    window.cancelAnimationFrame(topbarExpandedMetricsFrame);
  }
  topbarExpandedMetricsFrame = window.requestAnimationFrame(() => {
    topbarExpandedMetricsFrame = null;
    if (!(topbarEl instanceof HTMLElement) || !(appNoticesEl instanceof HTMLElement)) {
      return;
    }
    const expandedListEl = appNoticesEl.querySelector('.app-notices-expanded-list');
    if (!(expandedListEl instanceof HTMLElement) || appNoticesOverflowOpen !== true) {
      topbarEl.style.setProperty('--app-notices-extra-height', '0px');
      topbarEl.style.setProperty('--app-notices-expanded-list-height', '0px');
      return;
    }
    const expandedHeight = expandedListEl.scrollHeight;
    topbarEl.style.setProperty('--app-notices-expanded-list-height', `${expandedHeight}px`);
    topbarEl.style.setProperty('--app-notices-extra-height', `${expandedHeight + 8}px`);
  });
}

function normalizedAppNoticeText(text) {
  return typeof text === 'string'
    ? text.replace(/\s+/gu, ' ').trim()
    : '';
}

function renderJobLabelsSummary(labelIds) {
  if (!(jobLabelsSummaryEl instanceof HTMLElement)) {
    return;
  }

  const summaryExpanded = jobLabelsOverlayOpen;
  const labels = normalizeSelectedLabelIds(labelIds)
    .map((labelId) => labelDisplayName(labelId))
    .filter((labelName) => labelName !== '');

  jobLabelsSummaryEl.replaceChildren();
  jobLabelsSummaryEl.classList.toggle('is-empty', labels.length < 1);

  if (labels.length < 1) {
    jobLabelsSummaryEl.textContent = 'Inga etiketter';
    return;
  }

  if (summaryExpanded) {
    labels.forEach((labelName) => {
      jobLabelsSummaryEl.appendChild(createJobLabelsSummaryChip(labelName));
    });
    return;
  }

  const renderVisibleCount = (visibleCount) => {
    jobLabelsSummaryEl.replaceChildren();
    labels.slice(0, visibleCount).forEach((labelName) => {
      jobLabelsSummaryEl.appendChild(createJobLabelsSummaryChip(labelName));
    });
    if (visibleCount < labels.length) {
      jobLabelsSummaryEl.appendChild(createJobLabelsSummaryChip(`+${labels.length - visibleCount}`, 'job-labels-summary-overflow'));
    }
  };

  renderVisibleCount(labels.length);
  const maxWidth = jobLabelsSummaryEl.clientWidth;
  if (!(maxWidth > 0)) {
    return;
  }

  let visibleCount = labels.length;
  while (visibleCount > 0 && jobLabelsSummaryEl.scrollWidth > maxWidth + 1) {
    visibleCount -= 1;
    renderVisibleCount(visibleCount);
  }
  if (visibleCount === 0 && jobLabelsSummaryEl.scrollWidth > maxWidth + 1) {
    renderVisibleCount(0);
  }
}

function scheduleJobLabelsSummaryRender(job = findJobById(selectedJobId)) {
  if (jobLabelsSummaryRenderFrame !== null) {
    window.cancelAnimationFrame(jobLabelsSummaryRenderFrame);
  }
  jobLabelsSummaryRenderFrame = window.requestAnimationFrame(() => {
    jobLabelsSummaryRenderFrame = null;
    renderJobLabelsSummary(effectiveSelectedLabelIds(job));
    syncJobLabelsExpandedWidth(job);
  });
}

function selectedJobLabelDropdownOptions(job = findJobById(selectedJobId)) {
  const normalizedFilter = jobLabelsFilterText.trim().toLocaleLowerCase('sv');
  const selectedSet = new Set(effectiveSelectedLabelIds(job));
  const allOptions = currentSelectedJobLabelOptions();
  const filteredOptions = allOptions.filter((option) => {
    if (selectedSet.has(option.value)) {
      return false;
    }
    if (normalizedFilter === '') {
      return true;
    }
    return option.label.toLocaleLowerCase('sv').includes(normalizedFilter)
      || option.value.toLocaleLowerCase('sv').includes(normalizedFilter);
  });

  const createLabelName = jobLabelsFilterText.trim();
  const createLabelId = slugifyText(createLabelName, '-', '');
  const hasExactMatch = createLabelName !== '' && allOptions.some((option) => {
    return option.value === createLabelId
      || option.label.trim().toLocaleLowerCase('sv') === createLabelName.toLocaleLowerCase('sv');
  });

  const options = filteredOptions.map((option) => ({
    type: 'label',
    value: option.value,
    label: option.label,
  }));

  if (createLabelName !== '' && createLabelId !== '' && !hasExactMatch) {
    options.push({
      type: 'create',
      value: createLabelId,
      label: createLabelName,
    });
  }

  return options;
}

function renderSelectedJobLabelsChips(job = findJobById(selectedJobId)) {
  if (!(jobLabelsSelectedEl instanceof HTMLElement)) {
    return;
  }

  const labelIds = effectiveSelectedLabelIds(job);
  const selectedLabels = labelIds
    .map((labelId) => ({
      id: labelId,
      name: labelDisplayName(labelId),
    }))
    .filter((label) => label.name !== '');

  jobLabelsSelectedEl.replaceChildren();
  jobLabelsSelectedEl.classList.toggle('is-empty', selectedLabels.length < 1);

  if (selectedLabels.length < 1) {
    jobLabelsSelectedEl.textContent = 'Inga etiketter valda.';
    return;
  }

  selectedLabels.forEach((label) => {
    const chipEl = document.createElement('span');
    chipEl.className = 'job-labels-selected-chip';

    const textEl = document.createElement('span');
    textEl.className = 'job-labels-selected-chip-text';
    textEl.textContent = label.name;

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'job-labels-selected-chip-remove';
    removeButton.setAttribute('aria-label', `Ta bort etiketten ${label.name}`);
    removeButton.textContent = '✕';
    removeButton.addEventListener('mousedown', (event) => {
      event.preventDefault();
      event.stopPropagation();
    });
    removeButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      try {
        await removeLabelFromSelectedJob(label.id);
      } catch (error) {
        alert(error.message || 'Kunde inte ta bort etiketten.');
      }
    });

    chipEl.append(textEl, removeButton);
    jobLabelsSelectedEl.appendChild(chipEl);
  });
}

function renderSelectedJobExtractionFieldsSection(job = findJobById(selectedJobId)) {
  if (!(jobExtractionFieldsSectionEl instanceof HTMLElement)) {
    return;
  }

  jobExtractionFieldsSectionEl.replaceChildren();

  if (!job) {
    return;
  }

  const requiredKeys = requiredExtractionFieldKeysForJob(job);
  const meta = job.analysis && typeof job.analysis === 'object' && job.analysis.extractionFieldMeta && typeof job.analysis.extractionFieldMeta === 'object'
    ? job.analysis.extractionFieldMeta
    : {};
  const selectionKeys = Object.keys(effectiveSelectedExtractionFieldValues(job));
  const dataFieldOptions = filenameTemplateDataFieldOptions();
  const knownDataFieldKeys = new Set(dataFieldOptions.map((option) => option.key));
  const restrictToKnownDataFields = knownDataFieldKeys.size > 0;
  const fieldKeys = Array.from(new Set([
    ...requiredKeys,
    ...Object.keys(meta || {}),
    ...selectionKeys,
  ])).filter((fieldKey) => {
    if (typeof fieldKey !== 'string' || fieldKey.trim() === '') {
      return false;
    }
    return !restrictToKnownDataFields || knownDataFieldKeys.has(fieldKey.trim());
  });

  const cards = fieldKeys
    .map((fieldKey) => {
      const key = fieldKey.trim();
      const name = extractionFieldDisplayNameByKey(key);
      const rows = extractionFieldVisibleRowsForJob(job, key);
      const required = requiredKeys.has(key);
      if (rows.length < 1 && !required) {
        return null;
      }
      return {
        key,
        name,
        rows,
        required,
      };
    })
    .filter(Boolean)
    .sort((left, right) => left.name.localeCompare(right.name, 'sv'));

  const header = document.createElement('div');
  header.className = 'job-extraction-fields-section-header';
  const headerTitle = document.createElement('div');
  headerTitle.className = 'job-extraction-fields-section-title';
  headerTitle.textContent = 'Datafält';
  header.appendChild(headerTitle);
  jobExtractionFieldsSectionEl.appendChild(header);

  const inlineEditState = jobExtractionFieldInlineEditState
    && jobExtractionFieldInlineEditState.jobId === job.id
    ? jobExtractionFieldInlineEditState
    : null;
  let addFieldSelect = null;
  let addValueInput = null;
  let inlineEditInputToFocus = null;

  const list = document.createElement('div');
  list.className = 'job-extraction-fields-list';

  const openAddSectionForField = (fieldKey) => {
    if (!(addFieldSelect instanceof HTMLSelectElement) || !(addValueInput instanceof HTMLInputElement)) {
      return;
    }
    if (Array.from(addFieldSelect.options).some((option) => option.value === fieldKey)) {
      addFieldSelect.value = fieldKey;
    }
    addValueInput.focus({ preventScroll: true });
    addValueInput.select();
  };

  const beginInlineEdit = (fieldKey, value) => {
    beginJobExtractionFieldInlineEdit(job, fieldKey, value);
  };

  const commitInlineEdit = async (fieldKey, previousValue, inputEl) => {
    const nextValue = typeof inputEl.value === 'string' ? inputEl.value.trim() : '';
    if (nextValue === '') {
      clearJobExtractionFieldInlineEditState();
      renderSelectedJobExtractionFieldsSection(findJobById(selectedJobId));
      return;
    }
    try {
      const currentJob = findJobById(selectedJobId);
      if (!currentJob) {
        return;
      }
      const currentSelections = normalizeSelectedExtractionFieldValues(effectiveSelectedExtractionFieldValues(currentJob));
      currentSelections[fieldKey] = replaceSelectedExtractionFieldValue(currentJob, fieldKey, previousValue, nextValue);
      await persistSelectedJobExtractionFieldValues(currentSelections);
      clearJobExtractionFieldInlineEditState();
      renderSelectedJobExtractionFieldsSection(findJobById(selectedJobId));
    } catch (error) {
      alert(error.message || 'Kunde inte uppdatera värdet.');
    }
  };

  const createValueLine = (card, row, options = {}) => {
    const line = document.createElement('div');
    line.className = 'job-extraction-field-line';
    line.classList.toggle('is-primary', row.primary === true);
    line.classList.toggle('is-manual', row.manual === true);

    if (options.showRadio === true) {
      const radio = document.createElement('input');
      radio.type = 'radio';
      radio.className = 'job-extraction-field-line-radio';
      radio.name = `job-extraction-field-primary-${card.key}`;
      radio.checked = row.primary === true;
      radio.disabled = false;
      radio.addEventListener('change', async () => {
        try {
          const jobForUpdate = findJobById(selectedJobId);
          if (!jobForUpdate) {
            return;
          }
          const nextSelection = setSelectedExtractionFieldPrimaryValue(
            jobForUpdate,
            card.key,
            row.value,
            { addToManual: row.accepted !== true }
          );
          if (!nextSelection) {
            return;
          }
          const selections = normalizeSelectedExtractionFieldValues(effectiveSelectedExtractionFieldValues(jobForUpdate));
          selections[card.key] = nextSelection;
          await persistSelectedJobExtractionFieldValues(selections);
        } catch (error) {
          alert(error.message || 'Kunde inte uppdatera datafält.');
        }
      });
      line.appendChild(radio);
    }

    if (options.prefixText) {
      const prefix = document.createElement('span');
      prefix.className = 'job-extraction-field-line-prefix';
      prefix.textContent = `${options.prefixText}:`;
      line.appendChild(prefix);
    }

    const valueIsEditing = inlineEditState
      && inlineEditState.fieldKey === card.key
      && inlineEditState.value === row.value;

    if (valueIsEditing) {
      let inlineEditCancelled = false;
      let inlineEditCommitInProgress = false;
      const input = document.createElement('input');
      input.type = 'text';
      input.className = 'job-extraction-field-inline-input';
      input.value = typeof inlineEditState.draftValue === 'string' ? inlineEditState.draftValue : row.value;
      input.spellcheck = false;
      input.autocomplete = 'off';
      input.setAttribute('aria-label', `Redigera värdet ${row.value}`);
      input.addEventListener('input', () => {
        if (jobExtractionFieldInlineEditState
          && jobExtractionFieldInlineEditState.jobId === job.id
          && jobExtractionFieldInlineEditState.fieldKey === card.key
          && jobExtractionFieldInlineEditState.value === row.value) {
          jobExtractionFieldInlineEditState.draftValue = input.value;
        }
      });
      input.addEventListener('blur', () => {
        window.requestAnimationFrame(() => {
          if (inlineEditCancelled || inlineEditCommitInProgress || document.activeElement === input) {
            return;
          }
          inlineEditCommitInProgress = true;
          commitInlineEdit(card.key, row.value, input).finally(() => {
            inlineEditCommitInProgress = false;
          });
        });
      });
      input.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          event.preventDefault();
          event.stopPropagation();
          clearJobExtractionFieldInlineEditState();
          inlineEditCancelled = true;
          renderSelectedJobExtractionFieldsSection(findJobById(selectedJobId));
          return;
        }
        if (event.key === 'Enter') {
          event.preventDefault();
          event.stopPropagation();
          if (inlineEditCommitInProgress) {
            return;
          }
          inlineEditCommitInProgress = true;
          commitInlineEdit(card.key, row.value, input).finally(() => {
            inlineEditCommitInProgress = false;
          });
        }
      });
      line.appendChild(input);
      inlineEditInputToFocus = input;
    } else {
      const valueWrap = document.createElement('div');
      valueWrap.className = 'job-extraction-field-line-value-wrap';

      const valueButton = document.createElement('button');
      valueButton.type = 'button';
      valueButton.className = 'job-extraction-field-line-value';
      valueButton.textContent = row.value;
      valueButton.setAttribute('aria-label', `Redigera värdet ${row.value}`);
      valueButton.addEventListener('click', () => {
        beginInlineEdit(card.key, row.value);
      });
      valueWrap.appendChild(valueButton);

      const editHint = document.createElement('button');
      editHint.type = 'button';
      editHint.className = 'job-extraction-field-inline-hint';
      editHint.textContent = '✎';
      editHint.title = 'Redigera värdet';
      editHint.setAttribute('aria-label', `Redigera värdet ${row.value}`);
      editHint.addEventListener('mousedown', (event) => {
        event.preventDefault();
        event.stopPropagation();
      });
      editHint.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        beginInlineEdit(card.key, row.value);
      });
      valueWrap.appendChild(editHint);

      line.appendChild(valueWrap);
    }

    if (row.manual === true) {
      const badge = document.createElement('span');
      badge.className = 'job-extraction-field-line-badge';
      badge.textContent = 'manuell';
      line.appendChild(badge);
    }

    const actions = document.createElement('div');
    actions.className = 'job-extraction-field-line-actions';

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'job-extraction-field-inline-button is-remove';
    removeButton.textContent = '×';
    removeButton.title = 'Ta bort värdet';
    removeButton.setAttribute('aria-label', `Ta bort värdet ${row.value}`);
    removeButton.addEventListener('mousedown', (event) => {
      event.preventDefault();
      event.stopPropagation();
    });
    removeButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      try {
        const jobForUpdate = findJobById(selectedJobId);
        if (!jobForUpdate) {
          return;
        }
        const currentSelections = normalizeSelectedExtractionFieldValues(effectiveSelectedExtractionFieldValues(jobForUpdate));
        const nextSelection = removeSelectedExtractionFieldValue(jobForUpdate, card.key, row.value);
        if (!nextSelection) {
          delete currentSelections[card.key];
        } else {
          currentSelections[card.key] = nextSelection;
        }
        await persistSelectedJobExtractionFieldValues(currentSelections);
      } catch (error) {
        alert(error.message || 'Kunde inte ta bort värdet.');
      }
    });
    actions.appendChild(removeButton);

    line.appendChild(actions);
    return line;
  };

  cards.forEach((card) => {
    const item = document.createElement('div');
    item.className = 'job-extraction-field-item';
    item.dataset.fieldKey = card.key;

    if (card.rows.length > 1) {
      const title = document.createElement('div');
      title.className = 'job-extraction-field-item-title';
      title.textContent = card.name;
      item.appendChild(title);
    }

    const valuesWrap = document.createElement('div');
    valuesWrap.className = 'job-extraction-field-item-values';

    if (card.rows.length < 1) {
      const missingRow = document.createElement('div');
      missingRow.className = 'job-extraction-field-line is-missing';

      const prefix = document.createElement('span');
      prefix.className = 'job-extraction-field-line-prefix';
      prefix.textContent = `${card.name}:`;
      missingRow.appendChild(prefix);

      const missingText = document.createElement('span');
      missingText.className = 'job-extraction-field-line-value is-missing';
      missingText.textContent = 'saknas';
      missingRow.appendChild(missingText);

      const addButton = document.createElement('button');
      addButton.type = 'button';
      addButton.className = 'job-extraction-field-inline-button is-add';
      addButton.textContent = 'Lägg till';
      addButton.title = 'Lägg till värde';
      addButton.setAttribute('aria-label', `Lägg till värde för ${card.name}`);
      addButton.addEventListener('click', () => {
        openAddSectionForField(card.key);
      });
      missingRow.appendChild(addButton);

      valuesWrap.appendChild(missingRow);
    } else if (card.rows.length === 1) {
      valuesWrap.appendChild(createValueLine(card, card.rows[0], { prefixText: card.name, showRadio: false }));
    } else {
      card.rows.forEach((row) => {
        valuesWrap.appendChild(createValueLine(card, row, { showRadio: true }));
      });
    }

    item.appendChild(valuesWrap);
    list.appendChild(item);
  });

  if (cards.length > 0) {
    jobExtractionFieldsSectionEl.appendChild(list);
  } else {
    const empty = document.createElement('div');
    empty.className = 'job-extraction-fields-empty';
    empty.textContent = 'Inga datafält att redigera.';
    jobExtractionFieldsSectionEl.appendChild(empty);
  }

  const addSection = document.createElement('div');
  addSection.className = 'job-extraction-field-add-section';

  const addSectionTitle = document.createElement('div');
  addSectionTitle.className = 'job-extraction-field-add-section-title';
  addSectionTitle.textContent = 'Lägg till datafält';

  const addRow = document.createElement('div');
  addRow.className = 'job-extraction-field-add-row';

  addFieldSelect = document.createElement('select');
  addFieldSelect.className = 'job-extraction-field-add-select';
  addFieldSelect.setAttribute('aria-label', 'Välj datafält');

  const addFieldPlaceholder = document.createElement('option');
  addFieldPlaceholder.value = '';
  addFieldPlaceholder.hidden = true;
  addFieldPlaceholder.textContent = 'Välj datafält...';
  addFieldSelect.appendChild(addFieldPlaceholder);

  const addFieldOptions = extractionFieldAddOptions();
  addFieldOptions.forEach((optionData) => {
    const option = document.createElement('option');
    option.value = optionData.key;
    option.textContent = optionData.label;
    option.title = optionData.title || optionData.label;
    addFieldSelect.appendChild(option);
  });
  addFieldSelect.disabled = addFieldOptions.length < 1;
  addFieldSelect.value = '';

  addValueInput = document.createElement('input');
  addValueInput.type = 'text';
  addValueInput.className = 'job-extraction-field-manual-input';
  addValueInput.placeholder = 'Värde...';
  addValueInput.spellcheck = false;
  addValueInput.autocomplete = 'off';
  addValueInput.setAttribute('aria-label', 'Värde');

  const addButton = document.createElement('button');
  addButton.type = 'button';
  addButton.className = 'job-extraction-field-add-button';
  addButton.textContent = 'Lägg till';

  const submitManualValue = async () => {
    const fieldKey = addFieldSelect.value.trim();
    const manualValue = addValueInput.value.trim();
    if (fieldKey === '') {
      addFieldSelect.focus({ preventScroll: true });
      return;
    }
    if (manualValue === '') {
      addValueInput.focus({ preventScroll: true });
      return;
    }
    try {
      const jobForUpdate = findJobById(selectedJobId);
      if (!jobForUpdate) {
        return;
      }
      jobLabelsOverlayMutating = true;
      const currentSelections = normalizeSelectedExtractionFieldValues(effectiveSelectedExtractionFieldValues(jobForUpdate));
      currentSelections[fieldKey] = replaceSelectedExtractionFieldValue(jobForUpdate, fieldKey, '', manualValue);
      await persistSelectedJobExtractionFieldValues(currentSelections);
      window.requestAnimationFrame(() => {
        if (!jobLabelsOverlayOpen || !(jobLabelsOverlayEl instanceof HTMLElement)) {
          jobLabelsOverlayMutating = false;
          return;
        }
        const nextAddValueInput = jobLabelsOverlayEl.querySelector('.job-extraction-field-add-section .job-extraction-field-manual-input');
        if (nextAddValueInput instanceof HTMLInputElement) {
          nextAddValueInput.focus({ preventScroll: true });
          nextAddValueInput.select();
        }
      });
    } catch (error) {
      jobLabelsOverlayMutating = false;
      alert(error.message || 'Kunde inte lägga till värdet.');
    }
  };

  addButton.addEventListener('click', submitManualValue);
  addValueInput.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') {
      event.preventDefault();
      submitManualValue();
    }
  });
  addFieldSelect.addEventListener('keydown', (event) => {
    if (event.key === 'Enter' && !event.shiftKey && !event.altKey && !event.metaKey && !event.ctrlKey) {
      event.preventDefault();
      addValueInput.focus({ preventScroll: true });
    }
  });

  addRow.append(addFieldSelect, addValueInput, addButton);
  addSection.append(addSectionTitle, addRow);
  jobExtractionFieldsSectionEl.appendChild(addSection);

  if (inlineEditInputToFocus instanceof HTMLInputElement) {
    window.requestAnimationFrame(() => {
      if (!jobExtractionFieldInlineEditState
        || jobExtractionFieldInlineEditState.jobId !== job.id
        || jobExtractionFieldInlineEditState.fieldKey !== inlineEditInputToFocus.closest('.job-extraction-field-item')?.dataset.fieldKey) {
        return;
      }
      inlineEditInputToFocus.focus({ preventScroll: true });
      inlineEditInputToFocus.select();
    });
  }
}

function renderJobLabelsComboboxOptions(job = findJobById(selectedJobId)) {
  if (!(jobLabelsComboboxListEl instanceof HTMLElement)) {
    return;
  }

  jobLabelsRenderedOptions = selectedJobLabelDropdownOptions(job);
  if (jobLabelsRenderedOptions.length < 1) {
    jobLabelsActiveOptionIndex = -1;
  } else if (jobLabelsActiveOptionIndex < 0 || jobLabelsActiveOptionIndex >= jobLabelsRenderedOptions.length) {
    jobLabelsActiveOptionIndex = 0;
  }

  jobLabelsComboboxListEl.replaceChildren();
  jobLabelsComboboxListEl.classList.toggle('is-open', jobLabelsDropdownOpen);

  if (jobLabelsRenderedOptions.length < 1) {
    if (jobLabelsComboboxEl instanceof HTMLInputElement) {
      jobLabelsComboboxEl.removeAttribute('aria-activedescendant');
    }
    const emptyEl = document.createElement('div');
    emptyEl.className = 'job-labels-combobox-empty';
    emptyEl.textContent = jobLabelsFilterText.trim() === '' ? 'Inga fler etiketter.' : 'Ingen träff.';
    jobLabelsComboboxListEl.appendChild(emptyEl);
    return;
  }

  jobLabelsRenderedOptions.forEach((option, index) => {
    const optionButton = document.createElement('button');
    optionButton.type = 'button';
    optionButton.className = 'job-labels-combobox-option';
    if (option.type === 'create') {
      optionButton.classList.add('job-labels-combobox-option-create');
      optionButton.textContent = `Skapa etikett: "${option.label}"`;
    } else {
      optionButton.textContent = option.label;
    }
    optionButton.setAttribute('role', 'option');
    optionButton.id = `job-labels-option-${index}`;
    optionButton.setAttribute('aria-selected', index === jobLabelsActiveOptionIndex ? 'true' : 'false');
    optionButton.classList.toggle('is-active', index === jobLabelsActiveOptionIndex);
    optionButton.tabIndex = -1;
    optionButton.addEventListener('mousedown', (event) => {
      event.preventDefault();
    });
    optionButton.addEventListener('mouseenter', () => {
      jobLabelsActiveOptionIndex = index;
      syncJobLabelsActiveOptionState();
    });
    optionButton.addEventListener('click', async () => {
      await commitJobLabelsComboboxOption(option);
    });
    jobLabelsComboboxListEl.appendChild(optionButton);
  });

  if (jobLabelsComboboxEl instanceof HTMLInputElement && jobLabelsActiveOptionIndex >= 0) {
    jobLabelsComboboxEl.setAttribute('aria-activedescendant', `job-labels-option-${jobLabelsActiveOptionIndex}`);
  }
}

function syncJobLabelsActiveOptionState() {
  if (!(jobLabelsComboboxListEl instanceof HTMLElement)) {
    return;
  }

  const optionEls = Array.from(jobLabelsComboboxListEl.querySelectorAll('.job-labels-combobox-option'));
  optionEls.forEach((optionEl, index) => {
    const isActive = index === jobLabelsActiveOptionIndex;
    optionEl.classList.toggle('is-active', isActive);
    optionEl.setAttribute('aria-selected', isActive ? 'true' : 'false');
  });

  if (jobLabelsComboboxEl instanceof HTMLInputElement) {
    if (jobLabelsActiveOptionIndex >= 0) {
      jobLabelsComboboxEl.setAttribute('aria-activedescendant', `job-labels-option-${jobLabelsActiveOptionIndex}`);
    } else {
      jobLabelsComboboxEl.removeAttribute('aria-activedescendant');
    }
  }
}

function renderJobLabelsOverlay(job = findJobById(selectedJobId)) {
  if (!(jobLabelsFieldEl instanceof HTMLButtonElement) || !(jobLabelsOverlayEl instanceof HTMLElement)) {
    return;
  }

  const editable = selectedJobLabelsEditable(job);
  const overlayInteractive = jobLabelsOverlayOpen && editable;
  jobLabelsFieldEl.disabled = !editable;
  jobLabelsFieldEl.tabIndex = overlayInteractive ? -1 : 0;
  jobLabelsFieldEl.setAttribute('aria-expanded', jobLabelsOverlayOpen ? 'true' : 'false');
  jobLabelsOverlayEl.setAttribute('aria-hidden', jobLabelsOverlayOpen ? 'false' : 'true');
  jobLabelsOverlayEl.classList.toggle('is-open', jobLabelsOverlayOpen);
  jobLabelsOverlayEl.tabIndex = overlayInteractive ? 0 : -1;
  if (overlayInteractive) {
    jobLabelsOverlayEl.removeAttribute('inert');
  } else {
    jobLabelsOverlayEl.setAttribute('inert', '');
  }
  if (jobLabelsFieldGroupEl instanceof HTMLElement) {
    jobLabelsFieldGroupEl.classList.toggle('is-open', jobLabelsOverlayOpen);
  }

  if (jobLabelsComboboxEl instanceof HTMLInputElement) {
    jobLabelsComboboxEl.disabled = !overlayInteractive;
    jobLabelsComboboxEl.value = jobLabelsFilterText;
    jobLabelsComboboxEl.setAttribute('aria-expanded', overlayInteractive && jobLabelsDropdownOpen ? 'true' : 'false');
  }
  if (jobLabelsSelectedEl instanceof HTMLElement) {
    jobLabelsSelectedEl.tabIndex = overlayInteractive ? 0 : -1;
  }

  if (overlayInteractive) {
    renderJobLabelsComboboxOptions(job);
    renderSelectedJobLabelsChips(job);
    renderSelectedJobExtractionFieldsSection(job);
  } else {
    jobLabelsDropdownOpen = false;
    jobLabelsRenderedOptions = [];
    jobLabelsActiveOptionIndex = -1;
    if (jobLabelsComboboxListEl instanceof HTMLElement) {
      jobLabelsComboboxListEl.classList.remove('is-open');
      jobLabelsComboboxListEl.replaceChildren();
    }
    if (jobLabelsComboboxEl instanceof HTMLInputElement) {
      jobLabelsComboboxEl.removeAttribute('aria-activedescendant');
      jobLabelsComboboxEl.value = '';
    }
    if (jobExtractionFieldsSectionEl instanceof HTMLElement) {
      jobExtractionFieldsSectionEl.replaceChildren();
    }
  }

  scheduleJobLabelsSummaryRender(job);
}

function closeJobLabelsOverlay(options = {}) {
  if (!jobLabelsOverlayOpen) {
    return;
  }

  jobLabelsOverlayOpen = false;
  jobLabelsDropdownOpen = false;
  jobLabelsFilterText = '';
  jobLabelsActiveOptionIndex = -1;
  jobLabelsOverlayPointerDownInside = false;
  jobLabelsOverlayMutating = false;
  clearJobExtractionFieldInlineEditState();
  renderJobLabelsOverlay(findJobById(selectedJobId));
  if (options.restoreFocus === true && jobLabelsFieldEl instanceof HTMLButtonElement && !jobLabelsFieldEl.disabled) {
    jobLabelsFieldEl.focus({ preventScroll: true });
  }
}

function openJobLabelsOverlay(options = {}) {
  const job = findJobById(selectedJobId);
  if (!selectedJobLabelsEditable(job)) {
    return;
  }

  jobLabelsOverlayOpen = true;
  jobLabelsDropdownOpen = false;
  jobLabelsFilterText = '';
  jobLabelsActiveOptionIndex = -1;
  renderJobLabelsOverlay(job);
  if (options.focusOverlay === true) {
    window.requestAnimationFrame(() => {
      if (!(jobLabelsOverlayEl instanceof HTMLElement)) {
        return;
      }
      jobLabelsOverlayEl.focus({ preventScroll: true });
    });
    return;
  }
  if (options.focusCombobox === true) {
    window.requestAnimationFrame(() => {
      if (!(jobLabelsComboboxEl instanceof HTMLInputElement)) {
        return;
      }
      jobLabelsComboboxEl.focus({ preventScroll: true });
      jobLabelsComboboxEl.select();
    });
  }
}

function toggleJobLabelsOverlay() {
  if (jobLabelsOverlayOpen) {
    closeJobLabelsOverlay({ restoreFocus: true });
    return;
  }
  openJobLabelsOverlay({ focusOverlay: true });
}

async function persistSelectedJobLabelIds(nextLabelIds) {
  const job = findJobById(selectedJobId);
  if (!selectedJobLabelsEditable(job)) {
    return;
  }

  const previousCurrentFolder = effectiveFolderId(job);
  const previousCurrentFilename = String(displayedFilenameForJob(job) || '').trim();
  const previousProposed = proposedArchivingResultForJob(job);
  const normalizedNext = normalizeSelectedLabelIds(nextLabelIds);
  const hadLocalValue = selectedLabelIdsByJobId.has(job.id);
  const previousLocalValue = hadLocalValue ? normalizeSelectedLabelIds(selectedLabelIdsByJobId.get(job.id)) : null;

  selectedLabelIdsByJobId.set(job.id, normalizedNext);
  updateArchivedReviewDraftFromSidebar(job);
  const currentJobAfterLabelChange = findJobById(selectedJobId);
  const nextProposed = proposedArchivingResultForJob(currentJobAfterLabelChange);
  syncCurrentActionValuesFromProposalChange(
    job.id,
    previousCurrentFolder,
    previousCurrentFilename,
    previousProposed,
    nextProposed,
    { syncFolder: true }
  );
  setLabelsForJob(findJobById(selectedJobId));
  const currentJob = findJobById(selectedJobId);
  setFolderForJob(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);

  try {
    if (archivedReviewModeActiveForJob(job)) {
      return;
    }
    saveSelectedJobFields(job.id, { selectedLabelIds: normalizedNext }).catch((error) => {
      if (hadLocalValue) {
        selectedLabelIdsByJobId.set(job.id, previousLocalValue);
      } else {
        selectedLabelIdsByJobId.delete(job.id);
      }
      setLabelsForJob(findJobById(selectedJobId));
      const rollbackJob = findJobById(selectedJobId);
      syncFilenameField(rollbackJob);
      updateArchiveAction(rollbackJob);
      updateSelectedJobResetActions(rollbackJob);
      alert(error.message || 'Kunde inte spara etiketter.');
    });
  } catch (error) {
    if (hadLocalValue) {
      selectedLabelIdsByJobId.set(job.id, previousLocalValue);
    } else {
      selectedLabelIdsByJobId.delete(job.id);
    }
    setLabelsForJob(findJobById(selectedJobId));
    const rollbackJob = findJobById(selectedJobId);
    syncFilenameField(rollbackJob);
    updateArchiveAction(rollbackJob);
    updateSelectedJobResetActions(rollbackJob);
    throw error;
  }
}

function selectedExtractionFieldSelectionForJob(job, fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!job || normalizedKey === '') {
    return null;
  }
  const selections = effectiveSelectedExtractionFieldValues(job);
  return sanitizeSelectedExtractionFieldSelectionForJob(job, normalizedKey, selections[normalizedKey]);
}

function clearJobExtractionFieldInlineEditState() {
  jobExtractionFieldInlineEditState = null;
}

function beginJobExtractionFieldInlineEdit(job, fieldKey, value) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  const normalizedValue = typeof value === 'string' ? value.trim() : '';
  if (!job || normalizedKey === '' || normalizedValue === '') {
    return;
  }
  jobExtractionFieldInlineEditState = {
    jobId: job.id,
    fieldKey: normalizedKey,
    value: normalizedValue,
    draftValue: normalizedValue,
  };
  renderSelectedJobExtractionFieldsSection(findJobById(selectedJobId));
}

function replaceSelectedExtractionFieldValue(job, fieldKey, previousValue, nextValue) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  const normalizedPrevious = typeof previousValue === 'string' ? previousValue.trim() : '';
  const normalizedNext = typeof nextValue === 'string' ? nextValue.trim() : '';
  if (!job || normalizedKey === '' || normalizedNext === '') {
    return null;
  }

  const currentSelection = selectedExtractionFieldSelectionForJob(job, normalizedKey) || {
    manualValues: [],
    excludedValues: [],
    primaryValue: null,
  };
  const acceptedRows = extractionFieldAcceptedRowsForJob(job, normalizedKey);
  const acceptedValues = acceptedRows.map((row) => row.value).filter((item) => item !== '');
  const nextIsAccepted = acceptedValues.includes(normalizedNext);
  const previousIsAccepted = acceptedValues.includes(normalizedPrevious);
  const nextSelection = {
    manualValues: Array.isArray(currentSelection.manualValues)
      ? currentSelection.manualValues.filter((candidate) => candidate !== normalizedPrevious && candidate !== normalizedNext)
      : [],
    excludedValues: Array.isArray(currentSelection.excludedValues)
      ? currentSelection.excludedValues.filter((candidate) => candidate !== normalizedPrevious && candidate !== normalizedNext)
      : [],
    primaryValue: normalizedNext,
  };

  if (previousIsAccepted && normalizedPrevious !== normalizedNext) {
    nextSelection.excludedValues.push(normalizedPrevious);
  }

  if (!nextIsAccepted) {
    nextSelection.manualValues.unshift(normalizedNext);
  } else {
    nextSelection.excludedValues = nextSelection.excludedValues.filter((value) => value !== normalizedNext);
  }

  return sanitizeSelectedExtractionFieldSelectionForJob(job, normalizedKey, nextSelection);
}

function setSelectedExtractionFieldPrimaryValue(job, fieldKey, nextValue, options = {}) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!job || normalizedKey === '') {
    return null;
  }

  const currentSelection = selectedExtractionFieldSelectionForJob(job, normalizedKey) || {
    manualValues: [],
    excludedValues: [],
    primaryValue: null,
  };
  const normalizedValue = typeof nextValue === 'string' ? nextValue.trim() : '';
  if (normalizedValue === '') {
    return currentSelection;
  }

  const nextSelection = {
    manualValues: Array.isArray(currentSelection.manualValues) ? [...currentSelection.manualValues] : [],
    excludedValues: Array.isArray(currentSelection.excludedValues) ? [...currentSelection.excludedValues] : [],
    primaryValue: normalizedValue,
  };

  const acceptedRows = extractionFieldAcceptedRowsForJob(job, normalizedKey);
  const acceptedValues = acceptedRows.map((row) => row.value).filter((value) => value !== '');
  const manualIndex = nextSelection.manualValues.indexOf(normalizedValue);
  if (manualIndex >= 0) {
    nextSelection.manualValues.splice(manualIndex, 1);
    nextSelection.manualValues.unshift(normalizedValue);
  } else if (!acceptedValues.includes(normalizedValue)) {
    nextSelection.manualValues.unshift(normalizedValue);
  }

  nextSelection.excludedValues = nextSelection.excludedValues.filter((value) => value !== normalizedValue);

  if (options.addToManual === true && !nextSelection.manualValues.includes(normalizedValue)) {
    nextSelection.manualValues.unshift(normalizedValue);
  }

  return sanitizeSelectedExtractionFieldSelectionForJob(job, normalizedKey, nextSelection);
}

function removeSelectedExtractionFieldValue(job, fieldKey, value) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  const normalizedValue = typeof value === 'string' ? value.trim() : '';
  if (!job || normalizedKey === '' || normalizedValue === '') {
    return null;
  }

  const currentSelection = selectedExtractionFieldSelectionForJob(job, normalizedKey) || {
    manualValues: [],
    excludedValues: [],
    primaryValue: null,
  };
  const acceptedRows = extractionFieldAcceptedRowsForJob(job, normalizedKey);
  const acceptedValues = acceptedRows.map((row) => row.value).filter((item) => item !== '');
  const nextSelection = {
    manualValues: Array.isArray(currentSelection.manualValues)
      ? currentSelection.manualValues.filter((candidate) => candidate !== normalizedValue)
      : [],
    excludedValues: Array.isArray(currentSelection.excludedValues)
      ? [...currentSelection.excludedValues]
      : [],
    primaryValue: currentSelection.primaryValue,
  };

  if (acceptedValues.includes(normalizedValue)) {
    if (!nextSelection.excludedValues.includes(normalizedValue)) {
      nextSelection.excludedValues.push(normalizedValue);
    }
  }

  if (nextSelection.primaryValue === normalizedValue) {
    nextSelection.primaryValue = null;
  }

  return sanitizeSelectedExtractionFieldSelectionForJob(job, normalizedKey, nextSelection);
}

async function persistSelectedJobExtractionFieldValues(nextSelections) {
  const job = findJobById(selectedJobId);
  if (!selectedJobLabelsEditable(job)) {
    return;
  }

  const previousCurrentFilename = String(displayedFilenameForJob(job) || '').trim();
  const previousProposed = proposedArchivingResultForJob(job);
  const normalizedNext = sanitizeSelectedExtractionFieldValuesForJob(job, nextSelections);
  const hadLocalValue = selectedExtractionFieldValuesByJobId.has(job.id);
  const previousLocalValue = hadLocalValue ? normalizeSelectedExtractionFieldValues(selectedExtractionFieldValuesByJobId.get(job.id)) : null;

  selectedExtractionFieldValuesByJobId.set(job.id, normalizedNext);
  updateArchivedReviewDraftFromSidebar(job);
  const currentJobAfterFieldChange = findJobById(selectedJobId);
  const nextProposed = proposedArchivingResultForJob(currentJobAfterFieldChange);
  syncCurrentActionValuesFromProposalChange(
    job.id,
    effectiveFolderId(job),
    previousCurrentFilename,
    previousProposed,
    nextProposed
  );
  const currentJob = findJobById(selectedJobId);
  setLabelsForJob(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
  refreshLoadedMatchesView();

  try {
    if (archivedReviewModeActiveForJob(job)) {
      return;
    }
    saveSelectedJobFields(job.id, { selectedExtractionFieldValues: normalizedNext }).catch((error) => {
      if (hadLocalValue) {
        selectedExtractionFieldValuesByJobId.set(job.id, previousLocalValue);
      } else {
        selectedExtractionFieldValuesByJobId.delete(job.id);
      }
      const rollbackJob = findJobById(selectedJobId);
      setLabelsForJob(rollbackJob);
      syncFilenameField(rollbackJob);
      updateArchiveAction(rollbackJob);
      updateSelectedJobResetActions(rollbackJob);
      refreshLoadedMatchesView();
      alert(error.message || 'Kunde inte spara datafält.');
    });
  } catch (error) {
    if (hadLocalValue) {
      selectedExtractionFieldValuesByJobId.set(job.id, previousLocalValue);
    } else {
      selectedExtractionFieldValuesByJobId.delete(job.id);
    }
    const rollbackJob = findJobById(selectedJobId);
    setLabelsForJob(rollbackJob);
    syncFilenameField(rollbackJob);
    updateArchiveAction(rollbackJob);
    updateSelectedJobResetActions(rollbackJob);
    refreshLoadedMatchesView();
    throw error;
  }
}

async function applyLabelToSelectedJob(labelId) {
  const job = findJobById(selectedJobId);
  if (!selectedJobLabelsEditable(job)) {
    return;
  }

  const currentIds = effectiveSelectedLabelIds(job);
  if (currentIds.includes(labelId)) {
    return;
  }

  await persistSelectedJobLabelIds([...currentIds, labelId]);
  jobLabelsFilterText = '';
  jobLabelsDropdownOpen = true;
  jobLabelsActiveOptionIndex = 0;
  renderJobLabelsOverlay(findJobById(selectedJobId));
  if (jobLabelsComboboxEl instanceof HTMLInputElement) {
    jobLabelsComboboxEl.focus({ preventScroll: true });
  }
}

async function removeLabelFromSelectedJob(labelId) {
  const job = findJobById(selectedJobId);
  if (!selectedJobLabelsEditable(job)) {
    return;
  }

  const currentIds = effectiveSelectedLabelIds(job);
  await persistSelectedJobLabelIds(currentIds.filter((value) => value !== labelId));
  renderJobLabelsOverlay(findJobById(selectedJobId));
}

async function createAndApplyLabelToSelectedJob(labelName) {
  const normalizedName = typeof labelName === 'string' ? labelName.trim() : '';
  const normalizedId = slugifyText(normalizedName, '-', '');
  if (!normalizedName || !normalizedId) {
    return;
  }

  const existingOption = currentSelectedJobLabelOptions().find((option) => option.value === normalizedId);
  if (existingOption) {
    await applyLabelToSelectedJob(existingOption.value);
    return;
  }

  const previousLabelsDraft = JSON.parse(JSON.stringify(labelsDraft));
  labelsDraft = [...labelsDraft, sanitizeLabel({
    name: normalizedName,
    minScore: 1,
    rules: [],
  })];

  try {
    await saveLabels();
  } catch (error) {
    labelsDraft = previousLabelsDraft.map(sanitizeLabel);
    if (labelsListEl) {
      renderLabelsEditor();
    }
    if (archiveStructureListEl) {
      renderArchiveStructureEditor();
    }
    updateSettingsActionButtons();
    renderJobLabelsOverlay(findJobById(selectedJobId));
    throw error;
  }

  const createdOption = currentSelectedJobLabelOptions().find((option) => option.value === normalizedId);
  if (!createdOption) {
    throw new Error('Kunde inte skapa etiketten.');
  }

  await applyLabelToSelectedJob(createdOption.value);
}

async function commitJobLabelsComboboxOption(option = null) {
  const resolvedOption = option || jobLabelsRenderedOptions[jobLabelsActiveOptionIndex] || jobLabelsRenderedOptions[0] || null;
  if (!resolvedOption) {
    return;
  }

  try {
    if (resolvedOption.type === 'create') {
      await createAndApplyLabelToSelectedJob(resolvedOption.label);
      return;
    }
    await applyLabelToSelectedJob(resolvedOption.value);
  } catch (error) {
    alert(error.message || 'Kunde inte uppdatera etiketter.');
  }
}

function setLabelsForJob(job) {
  if (!(jobLabelsFieldEl instanceof HTMLButtonElement)) {
    return;
  }

  if (!job) {
    closeJobLabelsOverlay();
    jobLabelsFieldEl.disabled = true;
    renderJobLabelsSummary([]);
    if (jobLabelsSelectedEl instanceof HTMLElement) {
      jobLabelsSelectedEl.replaceChildren();
      jobLabelsSelectedEl.classList.add('is-empty');
      jobLabelsSelectedEl.textContent = 'Inga etiketter valda.';
    }
    return;
  }

  if (!selectedJobLabelsEditable(job) && jobLabelsOverlayOpen) {
    closeJobLabelsOverlay();
  }

  jobLabelsFieldEl.disabled = !selectedJobLabelsEditable(job);
  renderJobLabelsOverlay(job);
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

function affectedArchivedJobIdsFromArchivingUpdate() {
  return new Set(
    activeArchivedReviewItems()
      .map((item) => item && typeof item.jobId === 'string' ? item.jobId : '')
      .filter((jobId) => jobId !== '')
  );
}

function jobHasArchivingUpdateChange(jobId) {
  return typeof jobId === 'string' && jobId !== '' && affectedArchivedJobIdsFromArchivingUpdate().has(jobId);
}

function jobSupportsArchivedReview(job) {
  return !!(job && job.archived === true);
}

function archivedReviewModeActiveForJob(job) {
  return !!(job && job.archived === true && currentJobListMode === 'archived-review');
}

function selectedArchivedReviewPayload(job) {
  if (!archivedReviewModeActiveForJob(job)) {
    return null;
  }
  return archivedJobReviewPayload
    && archivedJobReviewPayload.jobId === job.id
    && archivedJobReviewPayload.loading !== true
    ? archivedJobReviewPayload
    : null;
}

function archivedReviewCacheKey(job) {
  return job && typeof job.id === 'string' ? job.id : '';
}

function cachedArchivedReviewPayload(job) {
  const key = archivedReviewCacheKey(job);
  if (!key) {
    return null;
  }
  return archivedReviewPayloadByJobId.get(key) || null;
}

function archivedReviewPayloadIsCurrent(job, payload) {
  if (!job || !payload || payload.jobId !== job.id) {
    return false;
  }
  return payload._jobSignature === jobStateSignature(job)
    && payload._reviewStateSignature === (typeof state.archivingRules?.signature === 'string' ? state.archivingRules.signature : '');
}

function archivedReviewSidebarEditable(job) {
  const payload = selectedArchivedReviewPayload(job);
  return !!(payload && payload.isActionable === true);
}

function selectedJobArchivingEditable(job) {
  return !!job && job.status === 'ready' && (job.archived !== true || archivedReviewSidebarEditable(job));
}

function clearArchivedReviewEditorState(jobId) {
  if (typeof jobId !== 'string' || jobId === '') {
    return;
  }
  if (jobExtractionFieldInlineEditState && jobExtractionFieldInlineEditState.jobId === jobId) {
    clearJobExtractionFieldInlineEditState();
  }
  selectedClientByJobId.delete(jobId);
  selectedSenderByJobId.delete(jobId);
  selectedFolderByJobId.delete(jobId);
  selectedLabelIdsByJobId.delete(jobId);
  selectedExtractionFieldValuesByJobId.delete(jobId);
  filenameByJobId.delete(jobId);
}

function syncArchivedReviewEditorState(job, payload = null) {
  if (!archivedReviewModeActiveForJob(job)) {
    return;
  }
  const resolvedPayload = payload && payload.jobId === job.id ? payload : selectedArchivedReviewPayload(job);
  if (!resolvedPayload || resolvedPayload.loading === true) {
    return;
  }
  const draft = ensureArchivedReviewDraft(job.id, resolvedPayload);
  if (draft.clientId) {
    selectedClientByJobId.set(job.id, draft.clientId);
  } else {
    selectedClientByJobId.delete(job.id);
  }
  if (draft.senderId) {
    selectedSenderByJobId.set(job.id, draft.senderId);
  } else {
    selectedSenderByJobId.delete(job.id);
  }
  if (draft.folderId) {
    selectedFolderByJobId.set(job.id, draft.folderId);
  } else {
    selectedFolderByJobId.delete(job.id);
  }
  selectedLabelIdsByJobId.set(job.id, Array.isArray(draft.labels) ? [...draft.labels] : []);
  if (draft.filename && String(draft.filename).trim() !== '') {
    filenameByJobId.set(job.id, draft.filename);
  } else {
    filenameByJobId.delete(job.id);
  }
}

function updateArchivedReviewDraftFromSidebar(job) {
  if (!archivedReviewModeActiveForJob(job)) {
    return;
  }
  const payload = selectedArchivedReviewPayload(job);
  if (!payload) {
    return;
  }
  const draft = ensureArchivedReviewDraft(job.id, payload);
  draft.clientId = effectiveClientDirName(job) || '';
  draft.senderId = effectiveSenderId(job) ? String(effectiveSenderId(job)) : '';
  draft.folderId = effectiveFolderId(job) || '';
  draft.filename = filenameByJobId.get(job.id) || displayedFilenameForJob(job) || '';
  draft.labels = effectiveSelectedLabelIds(job);
}

function archivedReviewDraftMatchesProposal(job, payload) {
  const draft = ensureArchivedReviewDraft(job.id, payload);
  const source = payload && payload.currentAutoResult && typeof payload.currentAutoResult === 'object'
    ? payload.currentAutoResult
    : {};
  return JSON.stringify({
    clientId: draft.clientId || '',
    senderId: draft.senderId || '',
    folderId: draft.folderId || '',
    filename: draft.filename || '',
    labels: Array.isArray(draft.labels) ? [...draft.labels].sort() : [],
  }) === JSON.stringify({
    clientId: typeof source.clientId === 'string' ? source.clientId : '',
    senderId: source.senderId ? String(source.senderId) : '',
    folderId: typeof source.folderId === 'string' ? source.folderId : '',
    filename: typeof source.filename === 'string' ? source.filename : '',
    labels: Array.isArray(source.labels) ? [...source.labels].sort() : [],
  });
}

function jobsNeedingRuleReview() {
  const affectedIds = affectedArchivedJobIdsFromArchivingUpdate();
  return (Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
    .filter((job) => job && job.archived === true && typeof job.id === 'string' && affectedIds.has(job.id));
}

function displayedArchivedReviewJobsForReadyMode() {
  const displayedItems = displayedArchivedReviewItems();
  const jobsById = new Map(
    (Array.isArray(state.archivedJobs) ? state.archivedJobs : [])
      .filter((job) => job && typeof job.id === 'string' && job.id !== '')
      .map((job) => [job.id, job])
  );
  const orderedIds = [];
  displayedItems.forEach((item) => {
    const jobId = item && typeof item.jobId === 'string' ? item.jobId : '';
    if (jobId !== '' && !orderedIds.includes(jobId)) {
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

function syncPrimaryViewModeAvailability(job, options = {}) {
  if (viewModeEl instanceof HTMLSelectElement) {
    viewModeEl.value = currentViewMode;
  }

  return true;
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
  syncOcrMenuState(findJobById(jobId));
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
  syncOcrMenuState(findJobById(jobId));
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

  const job = findJobById(jobId);
  if (job && job.status === 'processing') {
    loadedOcrJobId = '';
    loadedOcrSource = '';
    setOcrDocumentText(`Ingen ${ocrSourceDisplayName(currentOcrSource)} tillgänglig medan dokumentet bearbetas.`);
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
    if (response.status === 404) {
      const resolvedText = `(Ingen ${ocrSourceDisplayName(currentOcrSource)} hittades)`;
      setCachedOcrViewContent(currentOcrSource, {
        text: resolvedText,
        pages: null,
        mode: 'text',
      });
      setOcrDocumentText(resolvedText);
      refreshOcrSearch();
      restoreOcrViewState(currentOcrSource);
      return;
    }
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

function appendRuleMatchesSection(container, title, entities, emptyText, entityLabel = 'Regel') {
  const header = document.createElement('h3');
  header.className = 'matches-header';
  header.textContent = title;
  container.appendChild(header);

  if (!Array.isArray(entities) || entities.length === 0) {
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
  [entityLabel, 'Totalpoäng', 'Minpoäng', 'Regel', 'Matchad text', 'Regelpoäng'].forEach((label) => {
    const th = document.createElement('th');
    th.textContent = label;
    if (label === 'Regelpoäng' || label === 'Totalpoäng' || label === 'Minpoäng') {
      th.className = 'is-numeric';
    }
    if (label === 'Regel') {
      th.classList.add('matches-group-detail-start');
    }
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');

  entities.forEach((entity, entityIndex) => {
    if (entityIndex > 0) {
      const separatorRow = document.createElement('tr');
      separatorRow.className = 'matches-group-separator';
      const separatorCell = document.createElement('td');
      separatorCell.colSpan = 6;
      separatorCell.textContent = '';
      separatorRow.appendChild(separatorCell);
      tbody.appendChild(separatorRow);
    }

    const name = entity && typeof entity.name === 'string' && entity.name !== ''
      ? entity.name
      : `Namnlös ${entityLabel.toLowerCase()}`;
    const score = entity && Number.isFinite(Number(entity.score))
      ? Number(entity.score)
      : 0;
    const minScore = entity && Number.isFinite(Number(entity.minScore))
      ? Number(entity.minScore)
      : 1;

    const rules = entity && Array.isArray(entity.matchedRules) ? entity.matchedRules : [];
    if (rules.length === 0) {
      const tr = document.createElement('tr');
      tr.classList.add('matches-group-start', 'matches-group-end');

      const entityCell = document.createElement('td');
      entityCell.textContent = name;
      tr.appendChild(entityCell);

      const totalCell = document.createElement('td');
      totalCell.className = 'is-numeric';
      totalCell.textContent = String(score);
      tr.appendChild(totalCell);

      const minCell = document.createElement('td');
      minCell.className = 'is-numeric';
      minCell.textContent = String(minScore);
      tr.appendChild(minCell);

      const ruleCell = document.createElement('td');
      ruleCell.className = 'matches-group-detail-start';
      ruleCell.textContent = '(Inga regler matchade)';
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
      const ruleType = rule && typeof rule.type === 'string' ? rule.type : 'text';
      const ruleScore = rule && Number.isFinite(Number(rule.score)) ? Number(rule.score) : 0;

      const tr = document.createElement('tr');
      if (ruleIndex === 0) {
        tr.classList.add('matches-group-start');
      }
      if (ruleIndex === rules.length - 1) {
        tr.classList.add('matches-group-end');
      }
      if (ruleIndex > 0) {
        tr.classList.add('matches-group-rowspan-continuation');
      }

      if (ruleIndex === 0) {
        const entityCell = document.createElement('td');
        entityCell.textContent = name;
        entityCell.rowSpan = rules.length;
        tr.appendChild(entityCell);

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
      ruleCell.className = 'matches-group-detail-start';
      const ruleTypeLabel = ({
        text: 'Innehåller text',
        sender_is: 'Avsändare är',
        sender_name_contains: 'Avsändarnamn innehåller',
        field_exists: 'Fält finns',
      })[ruleType] || 'Regel';
      let ruleValueText = text;
      if (ruleType === 'sender_is' || ruleType === 'sender_name_contains' || ruleType === 'field_exists') {
        const separatorIndex = text.indexOf(':');
        ruleValueText = separatorIndex >= 0 ? text.slice(separatorIndex + 1).trim() : text;
      }
      const ruleTypeEl = document.createElement('span');
      ruleTypeEl.className = 'matches-rule-type';
      ruleTypeEl.textContent = `${ruleTypeLabel}:`;
      ruleCell.appendChild(ruleTypeEl);
      if (ruleValueText !== '') {
        ruleCell.appendChild(document.createTextNode(' '));
        const ruleValueEl = document.createElement('span');
        ruleValueEl.className = 'matches-rule-value';
        ruleValueEl.textContent = ruleValueText;
        ruleCell.appendChild(ruleValueEl);
      }
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

function appendFieldMatchesSection(container, title, fieldsByKey, emptyText, options = {}) {
  const header = document.createElement('h3');
  header.className = 'matches-header';
  header.textContent = title;
  container.appendChild(header);
  const currentJob = options && typeof options === 'object' && options.job && typeof options.job === 'object'
    ? options.job
    : findJobById(loadedMatchesJobId || selectedJobId);
  const acceptanceThreshold = Number.isFinite(Number(matchingDataFieldAcceptanceThresholdDraft))
    ? Number(matchingDataFieldAcceptanceThresholdDraft)
    : 0.5;

  const fieldGroups = fieldsByKey && typeof fieldsByKey === 'object'
    ? Object.entries(fieldsByKey)
      .map(([fieldKey, field]) => {
        if (!field || typeof field !== 'object') {
          return null;
        }
        const normalizedFieldKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
        if (normalizedFieldKey === '') {
          return null;
        }
        const matches = Array.isArray(field.matches)
          ? field.matches
            .map((match) => {
              if (!match || typeof match !== 'object') {
                return null;
              }
              const rawValue = Object.prototype.hasOwnProperty.call(match, 'value') ? match.value : null;
              if (rawValue === null || rawValue === undefined || rawValue === '') {
                return null;
              }
              return {
                value: rawValue,
                raw: typeof match.raw === 'string' ? match.raw : '',
                matchText: typeof match.matchText === 'string' ? match.matchText : '',
                extractedRaw: typeof match.extractedRaw === 'string' ? match.extractedRaw : '',
                source: typeof match.source === 'string' ? match.source : '',
                labelText: typeof match.labelText === 'string' ? match.labelText : '',
                between: typeof match.between === 'string' ? match.between : '',
                searchTerm: typeof match.searchTerm === 'string' ? match.searchTerm : '',
                confidence: Number.isFinite(Number(match.confidence)) ? Number(match.confidence) : null,
                baseConfidence: Number.isFinite(Number(match.baseConfidence)) ? Number(match.baseConfidence) : null,
                finalConfidence: Number.isFinite(Number(match.finalConfidence)) ? Number(match.finalConfidence) : null,
                noisePenalty: Number.isFinite(Number(match.noisePenalty)) ? Number(match.noisePenalty) : null,
                trailingDelimiterPenalty: Number.isFinite(Number(match.trailingDelimiterPenalty)) ? Number(match.trailingDelimiterPenalty) : null,
                otherMatchKeyPenalty: Number.isFinite(Number(match.otherMatchKeyPenalty)) ? Number(match.otherMatchKeyPenalty) : null,
                positionPenalty: Number.isFinite(Number(match.positionPenalty)) ? Number(match.positionPenalty) : (Number.isFinite(Number(match.directionPenalty)) ? Number(match.directionPenalty) : null),
                positionPenaltyAxis: typeof match.positionPenaltyAxis === 'string' ? match.positionPenaltyAxis : '',
                mainDirection: typeof match.mainDirection === 'string' ? match.mainDirection : '',
                noiseText: typeof match.noiseText === 'string' ? match.noiseText : '',
                noiseSegments: Array.isArray(match.noiseSegments)
                  ? match.noiseSegments
                    .map((segment) => {
                      if (!segment || typeof segment !== 'object') {
                        return null;
                      }
                      return {
                        text: typeof segment.text === 'string' ? segment.text : '',
                        lineIndex: Number.isInteger(segment.lineIndex) ? segment.lineIndex : null,
                        start: Number.isInteger(segment.start) ? segment.start : null,
                        end: Number.isInteger(segment.end) ? segment.end : null,
                      };
                    })
                    .filter((segment) => segment && segment.text !== '' && Number.isInteger(segment.lineIndex) && Number.isInteger(segment.start) && Number.isInteger(segment.end) && segment.end > segment.start)
                  : [],
                score: Number.isFinite(Number(match.score)) ? Number(match.score) : null,
                matchType: typeof match.matchType === 'string' ? match.matchType : '',
                lineIndex: Number.isInteger(match.lineIndex) ? match.lineIndex : Number.MAX_SAFE_INTEGER,
                labelLineIndex: Number.isInteger(match.labelLineIndex) ? match.labelLineIndex : null,
                start: Number.isInteger(match.start) ? match.start : Number.MAX_SAFE_INTEGER,
                accepted: Number.isFinite(Number(match.finalConfidence)) ? Number(match.finalConfidence) >= acceptanceThreshold : false,
              };
            })
            .filter(Boolean)
          : [];
        const fallbackValue = Object.prototype.hasOwnProperty.call(field, 'value') ? field.value : null;
        const rows = matches.length > 0
          ? matches
          : (fallbackValue === null || fallbackValue === undefined || fallbackValue === ''
            ? []
            : [{
              value: fallbackValue,
              raw: typeof field.raw === 'string' ? field.raw : '',
              matchText: typeof field.matchText === 'string' ? field.matchText : '',
              extractedRaw: typeof field.extractedRaw === 'string' ? field.extractedRaw : '',
              source: typeof field.source === 'string' ? field.source : '',
              labelText: typeof field.labelText === 'string' ? field.labelText : '',
              between: typeof field.between === 'string' ? field.between : '',
              searchTerm: '',
              confidence: Number.isFinite(Number(field.confidence)) ? Number(field.confidence) : null,
              baseConfidence: Number.isFinite(Number(field.baseConfidence)) ? Number(field.baseConfidence) : null,
              finalConfidence: Number.isFinite(Number(field.finalConfidence)) ? Number(field.finalConfidence) : null,
              noisePenalty: Number.isFinite(Number(field.noisePenalty)) ? Number(field.noisePenalty) : null,
              trailingDelimiterPenalty: Number.isFinite(Number(field.trailingDelimiterPenalty)) ? Number(field.trailingDelimiterPenalty) : null,
              otherMatchKeyPenalty: Number.isFinite(Number(field.otherMatchKeyPenalty)) ? Number(field.otherMatchKeyPenalty) : null,
              positionPenalty: Number.isFinite(Number(field.positionPenalty)) ? Number(field.positionPenalty) : (Number.isFinite(Number(field.directionPenalty)) ? Number(field.directionPenalty) : null),
              positionPenaltyAxis: typeof field.positionPenaltyAxis === 'string' ? field.positionPenaltyAxis : '',
              mainDirection: typeof field.mainDirection === 'string' ? field.mainDirection : '',
              noiseText: typeof field.noiseText === 'string' ? field.noiseText : '',
              noiseSegments: Array.isArray(field.noiseSegments)
                ? field.noiseSegments
                  .map((segment) => {
                    if (!segment || typeof segment !== 'object') {
                      return null;
                    }
                    return {
                      text: typeof segment.text === 'string' ? segment.text : '',
                      lineIndex: Number.isInteger(segment.lineIndex) ? segment.lineIndex : null,
                      start: Number.isInteger(segment.start) ? segment.start : null,
                      end: Number.isInteger(segment.end) ? segment.end : null,
                    };
                  })
                  .filter((segment) => segment && segment.text !== '' && Number.isInteger(segment.lineIndex) && Number.isInteger(segment.start) && Number.isInteger(segment.end) && segment.end > segment.start)
                : [],
              score: Number.isFinite(Number(field.score)) ? Number(field.score) : null,
              matchType: typeof field.matchType === 'string' ? field.matchType : '',
              lineIndex: Number.MAX_SAFE_INTEGER,
              labelLineIndex: Number.isInteger(field.labelLineIndex) ? field.labelLineIndex : null,
              start: Number.MAX_SAFE_INTEGER,
              accepted: Number.isFinite(Number(field.finalConfidence)) ? Number(field.finalConfidence) >= acceptanceThreshold : false,
            }]);
        if (rows.length === 0) {
          return null;
        }
        return {
          key: typeof field.key === 'string' && field.key.trim() !== '' ? field.key.trim() : normalizedFieldKey,
          name: typeof field.name === 'string' && field.name.trim() !== '' ? field.name.trim() : normalizedFieldKey,
          rows: rows
            .slice()
            .sort((left, right) => {
              const leftConfidence = typeof left.confidence === 'number' ? left.confidence : -1;
              const rightConfidence = typeof right.confidence === 'number' ? right.confidence : -1;
              if (leftConfidence !== rightConfidence) {
                return rightConfidence - leftConfidence;
              }
              if (left.lineIndex !== right.lineIndex) {
                return left.lineIndex - right.lineIndex;
              }
              return left.start - right.start;
            }),
        };
      })
      .filter(Boolean)
    : [];

  if (fieldGroups.length === 0) {
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
  ['Datafält', 'Värde', 'Träff', 'Straff', 'Säkerhet'].forEach((label) => {
    const th = document.createElement('th');
    th.textContent = label;
    if (label === 'Säkerhet') {
      th.className = 'is-numeric';
    }
    if (label === 'Värde') {
      th.classList.add('matches-group-detail-start');
    }
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');
  const formatMatchConfidence = (row) => {
    if (row && row.matchType === 'document_date_heuristic') {
      if (typeof row.score === 'number' && Number.isFinite(row.score)) {
        const hasFraction = Math.abs(row.score % 1) > 0.000001;
        return `${row.score.toLocaleString('sv-SE', {
          minimumFractionDigits: hasFraction ? 2 : 0,
          maximumFractionDigits: hasFraction ? 2 : 0,
        })} poäng`;
      }
      return '';
    }

    if (typeof row?.confidence === 'number' && Number.isFinite(row.confidence)) {
      return `${(row.confidence * 100).toLocaleString('sv-SE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}%`;
    }

    return '';
  };
  const appendInlinePart = (container, text, className = '') => {
    if (typeof text !== 'string' || text === '') {
      return;
    }
    const span = document.createElement('span');
    if (className) {
      span.className = className;
    }
    span.textContent = text;
    container.appendChild(span);
  };
  const normalizeWhitespaceForMatch = (text) => {
    if (typeof text !== 'string' || text === '') {
      return { text: '', indexMap: [] };
    }
    let normalized = '';
    const indexMap = [];
    let pendingSpaceIndex = -1;
    for (let index = 0; index < text.length; index += 1) {
      const character = text[index];
      if (/\s/u.test(character)) {
        if (normalized !== '' && pendingSpaceIndex === -1) {
          pendingSpaceIndex = index;
        }
        continue;
      }
      if (pendingSpaceIndex !== -1) {
        normalized += ' ';
        indexMap.push(pendingSpaceIndex);
        pendingSpaceIndex = -1;
      }
      normalized += character.toLocaleLowerCase('sv-SE');
      indexMap.push(index);
    }
    return { text: normalized, indexMap };
  };
  const findNormalizedTextSpan = (haystack, needle) => {
    if (typeof haystack !== 'string' || typeof needle !== 'string') {
      return null;
    }
    const normalizedNeedleSource = needle.trim();
    if (normalizedNeedleSource === '') {
      return null;
    }
    const normalizedHaystack = normalizeWhitespaceForMatch(haystack);
    const normalizedNeedle = normalizeWhitespaceForMatch(normalizedNeedleSource);
    if (normalizedHaystack.text === '' || normalizedNeedle.text === '') {
      return null;
    }
    const startIndex = normalizedHaystack.text.indexOf(normalizedNeedle.text);
    if (startIndex < 0) {
      return null;
    }
    const endIndex = startIndex + normalizedNeedle.text.length - 1;
    const start = normalizedHaystack.indexMap[startIndex];
    const end = normalizedHaystack.indexMap[endIndex] + 1;
    if (!Number.isInteger(start) || !Number.isInteger(end) || start < 0 || end <= start) {
      return null;
    }
    return { start, end };
  };
  const mergeHighlightRanges = (ranges) => {
    if (!Array.isArray(ranges) || ranges.length === 0) {
      return [];
    }
    const normalizedRanges = ranges
      .map((range) => {
        const start = Number.isInteger(range?.start) ? range.start : null;
        const end = Number.isInteger(range?.end) ? range.end : null;
        if (start === null || end === null || end <= start) {
          return null;
        }
        return { start, end };
      })
      .filter(Boolean)
      .sort((left, right) => left.start - right.start);
    if (normalizedRanges.length === 0) {
      return [];
    }
    return normalizedRanges.reduce((merged, range) => {
      const previous = merged[merged.length - 1];
      if (!previous || range.start > previous.end) {
        merged.push({ ...range });
        return merged;
      }
      previous.end = Math.max(previous.end, range.end);
      return merged;
    }, []);
  };
  const buildDisplaySegmentProjection = (text, compressWhitespace = false) => {
    if (typeof text !== 'string' || text === '') {
      return { text: '', map: [] };
    }
    if (!compressWhitespace) {
      return {
        text,
        map: Array.from({ length: text.length }, (_, index) => ({ start: index, end: index + 1 })),
      };
    }

    let displayText = '';
    const map = [];
    for (let index = 0; index < text.length;) {
      const character = text[index];
      if (!/\s/u.test(character)) {
        displayText += character;
        map.push({ start: index, end: index + 1 });
        index += 1;
        continue;
      }

      let end = index + 1;
      while (end < text.length && /\s/u.test(text[end])) {
        end += 1;
      }
      displayText += ' ';
      map.push({ start: index, end });
      index = end;
    }

    return { text: displayText, map };
  };
  const appendHighlightedSegmentText = (container, text, highlightRanges = []) => {
    if (!(container instanceof HTMLElement) || typeof text !== 'string' || text === '') {
      return;
    }
    const resolvedRanges = mergeHighlightRanges(highlightRanges)
      .map((range) => ({
        start: Math.max(0, Math.min(text.length, range.start)),
        end: Math.max(0, Math.min(text.length, range.end)),
      }))
      .filter((range) => range.end > range.start);
    if (resolvedRanges.length === 0) {
      appendInlinePart(container, text);
      return;
    }
    let cursor = 0;
    resolvedRanges.forEach((range) => {
      if (range.start > cursor) {
        appendInlinePart(container, text.slice(cursor, range.start));
      }
      appendInlinePart(container, text.slice(range.start, range.end), 'noise matches-noise');
      cursor = Math.max(cursor, range.end);
    });
    if (cursor < text.length) {
      appendInlinePart(container, text.slice(cursor));
    }
  };
  const projectSourceRangesToDisplay = (ranges, displayMap) => {
    if (!Array.isArray(ranges) || ranges.length === 0 || !Array.isArray(displayMap) || displayMap.length === 0) {
      return [];
    }
    const displayRanges = [];
    displayMap.forEach((entry, displayIndex) => {
      if (!entry || !Number.isInteger(entry.start) || !Number.isInteger(entry.end) || entry.end <= entry.start) {
        return;
      }
      const intersects = ranges.some((range) => range.end > entry.start && range.start < entry.end);
      if (intersects) {
        displayRanges.push({ start: displayIndex, end: displayIndex + 1 });
      }
    });
    return mergeHighlightRanges(displayRanges);
  };
  const resolveNoiseRangesFromSegments = (row, combinedText) => {
    const noiseSegments = Array.isArray(row?.noiseSegments) ? row.noiseSegments : [];
    if (noiseSegments.length === 0 || typeof combinedText !== 'string' || combinedText === '') {
      return null;
    }

    const candidateLineIndex = Number.isInteger(row?.lineIndex) ? row.lineIndex : null;
    const candidateStart = Number.isInteger(row?.start) ? row.start : null;
    const keyText = typeof row?.labelText === 'string' ? row.labelText : '';
    const betweenText = typeof row?.between === 'string' ? row.between : '';
    if (candidateLineIndex === null || candidateStart === null) {
      return null;
    }

    const combinedStart = candidateStart - keyText.length - betweenText.length;
    if (!Number.isInteger(combinedStart) || combinedStart < 0) {
      return null;
    }

    const ranges = [];
    for (const noiseSegment of noiseSegments) {
      if (!noiseSegment || typeof noiseSegment !== 'object') {
        return null;
      }
      if (!Number.isInteger(noiseSegment.lineIndex) || noiseSegment.lineIndex !== candidateLineIndex) {
        return null;
      }
      if (!Number.isInteger(noiseSegment.start) || !Number.isInteger(noiseSegment.end) || noiseSegment.end <= noiseSegment.start) {
        return null;
      }
      const localStart = noiseSegment.start - combinedStart;
      const localEnd = noiseSegment.end - combinedStart;
      if (localStart < 0 || localEnd > combinedText.length || localEnd <= localStart) {
        return null;
      }
      const projectedText = combinedText.slice(localStart, localEnd);
      const normalizedProjected = normalizeWhitespaceForMatch(projectedText).text;
      const normalizedNoise = normalizeWhitespaceForMatch(typeof noiseSegment.text === 'string' ? noiseSegment.text : '').text;
      if (normalizedProjected === '' || normalizedProjected !== normalizedNoise) {
        return null;
      }
      ranges.push({ start: localStart, end: localEnd });
    }

    return mergeHighlightRanges(ranges);
  };
  const projectHighlightRangesToSegment = (ranges, segmentStart, segmentEnd) => {
    if (!Array.isArray(ranges) || ranges.length === 0) {
      return [];
    }
    return mergeHighlightRanges(ranges
      .map((range) => {
        const overlapStart = Math.max(segmentStart, range.start);
        const overlapEnd = Math.min(segmentEnd, range.end);
        if (overlapEnd <= overlapStart) {
          return null;
        }
        return {
          start: overlapStart - segmentStart,
          end: overlapEnd - segmentStart,
        };
      })
      .filter(Boolean));
  };
  const resolveNoiseRanges = (row, combinedText) => {
    const segmentRanges = resolveNoiseRangesFromSegments(row, combinedText);
    if (Array.isArray(segmentRanges) && segmentRanges.length > 0) {
      return segmentRanges;
    }

    const noiseText = typeof row?.noiseText === 'string' ? row.noiseText.trim() : '';
    const noiseSpan = noiseText !== '' ? findNormalizedTextSpan(combinedText, noiseText) : null;
    return noiseSpan ? [noiseSpan] : [];
  };
  const renderHitText = (cell, row) => {
    if (!(cell instanceof HTMLElement)) {
      return;
    }
    const keyText = typeof row?.labelText === 'string' ? row.labelText : '';
    const betweenText = typeof row?.between === 'string' ? row.between : '';
    const matchText = typeof row?.matchText === 'string' ? row.matchText : '';
    const valueText = matchText !== '' ? matchText : (typeof row?.raw === 'string' ? row.raw : '');
    const labelLineIndex = Number.isInteger(row?.labelLineIndex) ? row.labelLineIndex : null;
    const candidateLineIndex = Number.isInteger(row?.lineIndex) ? row.lineIndex : null;
    const isDifferentRows = labelLineIndex !== null && candidateLineIndex !== null
      ? labelLineIndex !== candidateLineIndex
      : row?.source === 'nearby';
    const displayBetweenText = betweenText === '' && isDifferentRows && keyText !== '' && valueText !== ''
      ? ' '
      : betweenText;
    const combinedText = `${keyText}${betweenText}${valueText}`;
    if (combinedText === '') {
      cell.textContent = '–';
      return;
    }

    const noiseRanges = resolveNoiseRanges(row, combinedText);
    const segments = [
      {
        sourceText: keyText,
        display: buildDisplaySegmentProjection(keyText, false),
        className: 'key',
      },
      {
        sourceText: betweenText,
        display: buildDisplaySegmentProjection(displayBetweenText, true),
        className: 'between',
      },
      {
        sourceText: valueText,
        display: buildDisplaySegmentProjection(valueText, false),
        className: 'value',
      },
    ];
    let offset = 0;
    segments.forEach((segment) => {
      if (segment.sourceText === '' && segment.display.text === '') {
        return;
      }
      const segmentEl = document.createElement('span');
      segmentEl.className = segment.className;
      const segmentStart = offset;
      const segmentEnd = offset + segment.sourceText.length;
      const localSourceRanges = segment.sourceText !== ''
        ? projectHighlightRangesToSegment(noiseRanges, segmentStart, segmentEnd)
        : [];
      const localDisplayRanges = segment.sourceText !== ''
        ? projectSourceRangesToDisplay(localSourceRanges, segment.display.map)
        : [];
      if (localDisplayRanges.length > 0) {
        appendHighlightedSegmentText(segmentEl, segment.display.text, localDisplayRanges);
      } else {
        appendInlinePart(segmentEl, segment.display.text);
      }
      cell.appendChild(segmentEl);
      offset = segmentEnd;
    });
    if (isDifferentRows) {
      const badge = document.createElement('span');
      badge.className = 'matches-hit-row-badge';
      badge.textContent = 'olika rader';
      cell.appendChild(document.createTextNode(' '));
      cell.appendChild(badge);
    }
  };
  const appendMatchPenalties = (cell, row) => {
    const parts = [];
    const formatPenaltyPercent = (value) =>
      `${(value * 100).toLocaleString('sv-SE', { minimumFractionDigits: 0, maximumFractionDigits: 2 })}%`;

    if (typeof row?.noisePenalty === 'number' && Number.isFinite(row.noisePenalty) && row.noisePenalty > 0) {
      const noiseEl = document.createElement('span');
      noiseEl.className = 'matches-penalty-text';
      noiseEl.textContent = `Brus -${formatPenaltyPercent(row.noisePenalty)}`;
      parts.push(noiseEl);
    }
    if (typeof row?.trailingDelimiterPenalty === 'number' && Number.isFinite(row.trailingDelimiterPenalty) && row.trailingDelimiterPenalty > 0) {
      const delimiterEl = document.createElement('span');
      delimiterEl.className = 'matches-penalty-text';
      delimiterEl.textContent = `Avslutstecken -${formatPenaltyPercent(row.trailingDelimiterPenalty)}`;
      parts.push(delimiterEl);
    }
    if (typeof row?.otherMatchKeyPenalty === 'number' && Number.isFinite(row.otherMatchKeyPenalty) && row.otherMatchKeyPenalty > 0) {
      const otherKeyEl = document.createElement('span');
      otherKeyEl.className = 'matches-penalty-text';
      otherKeyEl.textContent = `Annan nyckel -${formatPenaltyPercent(row.otherMatchKeyPenalty)}`;
      parts.push(otherKeyEl);
    }
    if (typeof row?.positionPenalty === 'number' && Number.isFinite(row.positionPenalty) && row.positionPenalty > 0) {
      const positionEl = document.createElement('span');
      let label = 'Position';
      if (row.positionPenaltyAxis === 'x') {
        label = 'X-avvikelse';
      } else if (row.positionPenaltyAxis === 'y') {
        label = 'Y-avvikelse';
      } else if (row.positionPenaltyAxis === 'invalid') {
        label = 'Fel riktning';
      }
      positionEl.textContent = `${label} -${formatPenaltyPercent(row.positionPenalty)}`;
      parts.push(positionEl);
    }

    if (parts.length === 0) {
      cell.textContent = '–';
      return;
    }

    parts.forEach((part, index) => {
      if (index > 0) {
        cell.appendChild(document.createTextNode(', '));
      }
      cell.appendChild(part);
    });
  };

  fieldGroups.forEach((fieldGroup, groupIndex) => {
      if (groupIndex > 0) {
        const separatorRow = document.createElement('tr');
        separatorRow.className = 'matches-group-separator';
        const separatorCell = document.createElement('td');
        separatorCell.colSpan = 5;
        separatorCell.textContent = '';
        separatorRow.appendChild(separatorCell);
        tbody.appendChild(separatorRow);
      }

    fieldGroup.rows.forEach((row, rowIndex) => {
      const tr = document.createElement('tr');
      if (rowIndex === 0) {
        tr.classList.add('matches-group-start');
      }
      if (rowIndex === fieldGroup.rows.length - 1) {
        tr.classList.add('matches-group-end');
      }
      if (rowIndex > 0) {
        tr.classList.add('matches-group-rowspan-continuation');
      }

      if (rowIndex === 0) {
        const nameCell = document.createElement('td');
        nameCell.className = 'matches-field-name-cell';
        nameCell.textContent = fieldGroup.name || fieldGroup.key || 'Namnlöst datafält';
        nameCell.rowSpan = fieldGroup.rows.length;
        tr.appendChild(nameCell);
      }

      const valueCell = document.createElement('td');
      valueCell.className = 'matches-group-detail-start';
      if (currentJob && selectedJobLabelsEditable(currentJob)) {
        valueCell.classList.add('is-clickable');
        valueCell.addEventListener('click', async () => {
          try {
            const currentSelections = normalizeSelectedExtractionFieldValues(effectiveSelectedExtractionFieldValues(currentJob));
            currentSelections[fieldGroup.key] = replaceSelectedExtractionFieldValue(currentJob, fieldGroup.key, '', row.value);
            await persistSelectedJobExtractionFieldValues(currentSelections);
          } catch (error) {
            alert(error.message || 'Kunde inte uppdatera datafält.');
          }
        });
      }
      const valueText = document.createElement('span');
      valueText.textContent = String(row.value);
      valueCell.appendChild(valueText);
      tr.appendChild(valueCell);

      const hitCell = document.createElement('td');
      hitCell.className = 'matches-hit-cell';
      renderHitText(hitCell, row);
      tr.appendChild(hitCell);

      const penaltiesCell = document.createElement('td');
      appendMatchPenalties(penaltiesCell, row);
      tr.appendChild(penaltiesCell);

      const confidenceCell = document.createElement('td');
      confidenceCell.className = 'is-numeric';
      confidenceCell.textContent = formatMatchConfidence(row);
      tr.appendChild(confidenceCell);

      tbody.appendChild(tr);
    });
  });

  table.appendChild(tbody);
  tableWrap.appendChild(table);
  container.appendChild(tableWrap);
}

function appendClientMatchesSection(container, title, clientMatches, emptyMessage) {
  const heading = document.createElement('h3');
  heading.className = 'matches-header';
  heading.textContent = title;
  container.appendChild(heading);

  if (!Array.isArray(clientMatches) || clientMatches.length < 1) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = emptyMessage;
    container.appendChild(empty);
    return;
  }

  const groups = clientMatches
    .map((match) => {
      const displayName = typeof match?.displayName === 'string' ? match.displayName.trim() : '';
      const signals = Array.isArray(match?.signals)
        ? match.signals.filter((signal) => signal && typeof signal === 'object')
        : [];
      if (!displayName || signals.length < 1) {
        return null;
      }
      return { displayName, signals };
    })
    .filter(Boolean);

  if (groups.length < 1) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = emptyMessage;
    container.appendChild(empty);
    return;
  }

  const tableWrap = document.createElement('div');
  tableWrap.className = 'matches-table-wrap';

  const table = document.createElement('table');
  table.className = 'matches-table';

  const thead = document.createElement('thead');
  const headerRow = document.createElement('tr');
  ['Huvudman', 'Matchning', 'Värde'].forEach((label) => {
    const th = document.createElement('th');
    th.textContent = label;
    headerRow.appendChild(th);
  });
  thead.appendChild(headerRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');
  groups.forEach((group, groupIndex) => {
    if (groupIndex > 0) {
      const separatorRow = document.createElement('tr');
      separatorRow.className = 'matches-group-separator';
      const separatorCell = document.createElement('td');
      separatorCell.colSpan = 3;
      separatorRow.appendChild(separatorCell);
      tbody.appendChild(separatorRow);
    }

    group.signals.forEach((signal, signalIndex) => {
      const tr = document.createElement('tr');
      if (signalIndex === 0) {
        tr.classList.add('matches-group-start');
      }
      if (signalIndex === group.signals.length - 1) {
        tr.classList.add('matches-group-end');
      }
      if (signalIndex > 0) {
        tr.classList.add('matches-group-rowspan-continuation');
      }

      if (signalIndex === 0) {
        const nameCell = document.createElement('td');
        nameCell.className = 'matches-field-name-cell';
        nameCell.textContent = group.displayName;
        nameCell.rowSpan = group.signals.length;
        tr.appendChild(nameCell);
      }

      const typeCell = document.createElement('td');
      typeCell.className = 'matches-group-detail-start';
      typeCell.textContent = typeof signal.label === 'string' && signal.label.trim() !== '' ? signal.label.trim() : 'Matchning';
      tr.appendChild(typeCell);

      const valueCell = document.createElement('td');
      valueCell.textContent = typeof signal.value === 'string' ? signal.value : '';
      tr.appendChild(valueCell);

      tbody.appendChild(tr);
    });
  });

  table.appendChild(tbody);
  tableWrap.appendChild(table);
  container.appendChild(tableWrap);
}

function renderMatchesContent(payload) {
  matchesViewEl.innerHTML = '';

  const labels = payload && Array.isArray(payload.labels) ? payload.labels : [];
  const fields = payload && typeof payload.fields === 'object' && payload.fields !== null ? payload.fields : {};
  const clients = payload && Array.isArray(payload.clients) ? payload.clients : [];
  const job = findJobById(loadedMatchesJobId || selectedJobId);

  appendRuleMatchesSection(matchesViewEl, 'Etiketter', labels, 'Inga etikettmatchningar hittades.', 'Etikett');
  appendFieldMatchesSection(matchesViewEl, 'Datafält', fields, 'Inga datafältsmatchningar hittades.', { job });
  appendClientMatchesSection(matchesViewEl, 'Huvudman', clients, 'Inga huvudmansmatchningar hittades.');
}

function refreshLoadedMatchesView() {
  if (!loadedMatchesPayload || matchesViewEl.classList.contains('hidden')) {
    return;
  }
  renderMatchesContent(loadedMatchesPayload);
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
    loadedMatchesPayload = null;
    matchesViewEl.innerHTML = '';
    return;
  }

  const job = findJobById(jobId);
  if (job && job.status === 'processing') {
    loadedMatchesJobId = '';
    loadedMatchesPayload = null;
    matchesViewEl.innerHTML = '';
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = 'Inga matchningsdata tillgängliga medan dokumentet bearbetas.';
    matchesViewEl.appendChild(empty);
    return;
  }

  if (loadedMatchesJobId === jobId) {
    return;
  }

  loadedMatchesJobId = jobId;
  loadedMatchesPayload = null;
  const requestSeq = ++matchesRequestSeq;
  matchesViewEl.innerHTML = '';
  const loading = document.createElement('div');
  loading.className = 'matches-empty';
  loading.textContent = 'Laddar matchningar...';
  matchesViewEl.appendChild(loading);

  try {
    const response = await fetch('/api/get-job-matches.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (response.status === 404) {
      loadedMatchesPayload = null;
      matchesViewEl.innerHTML = '';
      const empty = document.createElement('div');
      empty.className = 'matches-empty';
      empty.textContent = 'Inga matchningsdata hittades.';
      matchesViewEl.appendChild(empty);
      return;
    }
    if (!response.ok) {
      throw new Error('Kunde inte hämta matchningar');
    }

    const payload = await response.json();
    if (requestSeq !== matchesRequestSeq) {
      return;
    }

    loadedMatchesPayload = payload;
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

function changedArchivedReviewItems() {
  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || state.archivingRules?.updateReview);
  return (Array.isArray(payload.jobs) ? payload.jobs : [])
    .filter((item) => item && typeof item === 'object');
}

function activeArchivedReviewItems() {
  return changedArchivedReviewItems()
    .filter((item) => item.dismissedForVersion !== true);
}

function displayedArchivedReviewItems() {
  return showDismissedArchivedReviewJobs
    ? changedArchivedReviewItems()
    : activeArchivedReviewItems();
}

function setShowDismissedArchivedReviewJobs(value) {
  const nextValue = value === true;
  if (showDismissedArchivedReviewJobs === nextValue) {
    return;
  }
  showDismissedArchivedReviewJobs = nextValue;
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
}

function closeJobListMenu() {
  if (!(jobListMenuButtonEl instanceof HTMLButtonElement) || !(jobListMenuEl instanceof HTMLElement)) {
    return;
  }
  jobListMenuButtonEl.setAttribute('aria-expanded', 'false');
  jobListMenuEl.classList.add('hidden');
}

function closeOcrMenu() {
  if (!(ocrMenuButtonEl instanceof HTMLButtonElement) || !(ocrMenuEl instanceof HTMLElement)) {
    return;
  }
  ocrMenuButtonEl.setAttribute('aria-expanded', 'false');
  ocrMenuEl.classList.add('hidden');
}

function closeSelectedJobActionsMenu() {
  if (!(selectedJobActionsMenuButtonEl instanceof HTMLButtonElement) || !(selectedJobActionsMenuEl instanceof HTMLElement)) {
    return;
  }
  selectedJobActionsMenuButtonEl.setAttribute('aria-expanded', 'false');
  selectedJobActionsMenuEl.classList.add('hidden');
}

function toggleJobListMenu(forceOpen = null) {
  if (!(jobListMenuButtonEl instanceof HTMLButtonElement) || !(jobListMenuEl instanceof HTMLElement)) {
    return;
  }
  const nextOpen = forceOpen === null
    ? jobListMenuEl.classList.contains('hidden')
    : forceOpen === true;
  jobListMenuButtonEl.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
  jobListMenuEl.classList.toggle('hidden', !nextOpen);
}

function toggleOcrMenu(forceOpen = null) {
  if (!(ocrMenuButtonEl instanceof HTMLButtonElement) || !(ocrMenuEl instanceof HTMLElement)) {
    return;
  }
  if (ocrMenuButtonEl.disabled) {
    closeOcrMenu();
    return;
  }
  const nextOpen = forceOpen === null
    ? ocrMenuEl.classList.contains('hidden')
    : forceOpen === true;
  ocrMenuButtonEl.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
  ocrMenuEl.classList.toggle('hidden', !nextOpen);
}

function toggleSelectedJobActionsMenu(forceOpen = null) {
  if (!(selectedJobActionsMenuButtonEl instanceof HTMLButtonElement) || !(selectedJobActionsMenuEl instanceof HTMLElement)) {
    return;
  }
  if (selectedJobActionsMenuButtonEl.disabled) {
    closeSelectedJobActionsMenu();
    return;
  }
  const nextOpen = forceOpen === null
    ? selectedJobActionsMenuEl.classList.contains('hidden')
    : forceOpen === true;
  selectedJobActionsMenuButtonEl.setAttribute('aria-expanded', nextOpen ? 'true' : 'false');
  selectedJobActionsMenuEl.classList.toggle('hidden', !nextOpen);
}

function updateSelectedJobActionsMenu(job) {
  const hasJob = !!job;

  if (selectedJobActionsMenuButtonEl instanceof HTMLButtonElement) {
    selectedJobActionsMenuButtonEl.disabled = !hasJob;
    selectedJobActionsMenuButtonEl.title = hasJob ? 'Fler åtgärder för dokumentet.' : 'Markera ett jobb först.';
    selectedJobActionsMenuButtonEl.setAttribute('aria-expanded', 'false');
  }

  if (selectedJobDeleteActionEl instanceof HTMLButtonElement) {
    selectedJobDeleteActionEl.disabled = !hasJob;
    selectedJobDeleteActionEl.title = hasJob ? 'Tar bort dokumentet från Docflow.' : 'Markera ett jobb först.';
  }

  if (!hasJob) {
    closeSelectedJobActionsMenu();
  }
}

function setJobListMode(mode, options = {}) {
  const nextMode = VALID_JOB_LIST_MODES.has(mode) ? mode : 'ready';
  if (nextMode !== 'ready') {
    pinnedProcessingJobIds.clear();
  }
  if (nextMode === 'archived-review' && currentViewMode === 'review') {
    setViewMode('pdf');
  }
  currentJobListMode = nextMode;
  jobListModeEl.value = currentJobListMode;
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  if (options.refreshSelection !== false) {
    refreshSelection();
  }
  closeJobListMenu();
  closeSelectedJobActionsMenu();
  const selectedJob = findJobById(selectedJobId);
  updateArchiveAction(selectedJob);
  updateSelectedJobResetActions(selectedJob);
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

async function openExtensionsSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('extensions');

  try {
    await ensureSettingsPanelReady('extensions', { reload: true });
  } catch (error) {
    alert('Kunde inte ladda Tillägg.');
    return false;
  }

  return true;
}

function extensionMessagingAvailable() {
  return !!(window.chrome && chrome.runtime && typeof chrome.runtime.sendMessage === 'function');
}

function chromeExtensionIsCurrentVersion(version) {
  return version !== '' && version === chromeExtensionRequiredVersion;
}

function chromeExtensionIsUsable() {
  return chromeExtensionRuntime.status === 'installed' && chromeExtensionIsCurrentVersion(chromeExtensionRuntime.version);
}

function normalizeSenderPayeeLookupQueueItem(input) {
  if (!input || typeof input !== 'object') {
    return null;
  }

  const paymentId = Number.parseInt(String(input.paymentId || ''), 10);
  if (!Number.isInteger(paymentId) || paymentId < 1) {
    return null;
  }

  const type = String(input.type || '').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
  const normalizedNumber = String(input.normalizedNumber || '').trim();
  const number = String(input.number || '').trim();

  return {
    paymentId,
    senderId: Number.isInteger(Number.parseInt(String(input.senderId || ''), 10))
      ? Number.parseInt(String(input.senderId || ''), 10)
      : null,
    senderName: String(input.senderName || '').trim(),
    type,
    number,
    normalizedNumber: normalizedNumber !== '' ? normalizedNumber : number,
    payeeName: String(input.payeeName || '').trim(),
    payeeLookupStatus: String(input.payeeLookupStatus || '').trim(),
  };
}

function normalizeSenderPayeeLookupQueue(input) {
  const remainingCount = Number.parseInt(String(input && input.remainingCount || 0), 10) || 0;
  return {
    remainingCount: Math.max(0, remainingCount),
    item: normalizeSenderPayeeLookupQueueItem(input && input.item),
  };
}

function normalizeSenderOrganizationLookupQueueItem(input) {
  if (!input || typeof input !== 'object') {
    return null;
  }

  const organizationId = Number.parseInt(String(input.organizationId || ''), 10);
  if (!Number.isInteger(organizationId) || organizationId < 1) {
    return null;
  }

  const normalizedOrganizationNumber = String(input.normalizedOrganizationNumber || '').trim();
  const organizationNumber = String(input.organizationNumber || '').trim();

  return {
    organizationId,
    senderId: Number.isInteger(Number.parseInt(String(input.senderId || ''), 10))
      ? Number.parseInt(String(input.senderId || ''), 10)
      : null,
    senderName: String(input.senderName || '').trim(),
    organizationNumber,
    normalizedOrganizationNumber: normalizedOrganizationNumber !== '' ? normalizedOrganizationNumber : organizationNumber,
    organizationName: String(input.organizationName || '').trim(),
    source: String(input.source || '').trim(),
  };
}

function normalizeSenderOrganizationLookupQueue(input) {
  const remainingCount = Number.parseInt(String(input && input.remainingCount || 0), 10) || 0;
  return {
    remainingCount: Math.max(0, remainingCount),
    item: normalizeSenderOrganizationLookupQueueItem(input && input.item),
  };
}

function currentSenderOrganizationLookupQueue() {
  if (!state || !state.senderOrganizationLookupQueue || typeof state.senderOrganizationLookupQueue !== 'object') {
    return {
      remainingCount: 0,
      item: null,
    };
  }
  return state.senderOrganizationLookupQueue;
}

function currentSenderOrganizationLookupQueueItem() {
  return currentSenderOrganizationLookupQueue().item;
}

function currentSenderOrganizationLookupRemainingCount() {
  return Math.max(0, Number.parseInt(String(currentSenderOrganizationLookupQueue().remainingCount || 0), 10) || 0);
}

function currentSenderPayeeLookupQueue() {
  if (!state || !state.senderPayeeLookupQueue || typeof state.senderPayeeLookupQueue !== 'object') {
    return {
      remainingCount: 0,
      item: null,
    };
  }
  return state.senderPayeeLookupQueue;
}

function currentSenderPayeeLookupQueueItem() {
  return currentSenderPayeeLookupQueue().item;
}

function currentSenderPayeeLookupRemainingCount() {
  return Math.max(0, Number.parseInt(String(currentSenderPayeeLookupQueue().remainingCount || 0), 10) || 0);
}

function applyChromeExtensionConfigPayload(payload) {
  if (!payload || typeof payload !== 'object') {
    return;
  }

  chromeExtensionRequiredId = typeof payload.chromeExtensionId === 'string' ? payload.chromeExtensionId.trim() : chromeExtensionRequiredId;
  chromeExtensionRequiredVersion = typeof payload.chromeExtensionVersion === 'string' ? payload.chromeExtensionVersion.trim() : chromeExtensionRequiredVersion;
  chromeExtensionDirectory = typeof payload.chromeExtensionDirectory === 'string' ? payload.chromeExtensionDirectory.trim() : chromeExtensionDirectory;
  chromeExtensionSuppressMissingNotice = payload.chromeExtensionSuppressMissingNotice === true;
  renderAppNotices();
  renderSystemChromeExtensionStatus();
}

function setChromeExtensionRuntime(nextState = {}) {
  chromeExtensionRuntime = {
    ...chromeExtensionRuntime,
    ...(nextState && typeof nextState === 'object' ? nextState : {}),
  };
  console.info('[Docflow] chromeExtensionRuntime', {
    ...chromeExtensionRuntime,
    senderOrganizationLookupQueue: currentSenderOrganizationLookupQueue(),
    senderPayeeLookupQueue: currentSenderPayeeLookupQueue(),
  });
  renderAppNotices();
  renderSystemChromeExtensionStatus();
  syncChromeExtensionPresencePolling();
  renderSelectedJobSenderSection(findJobById(selectedJobId));
}

async function loadChromeExtensionConfig() {
  const response = await fetch('/api/get-config.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda extension-konfiguration.');
  }

  const payload = await response.json();
  if (!payload || typeof payload.chromeExtensionId !== 'string' || typeof payload.chromeExtensionVersion !== 'string') {
    throw new Error('Ogiltigt svar för extension-konfiguration.');
  }

  applyChromeExtensionConfigPayload(payload);
  return payload;
}

function updateSystemChromeExtensionDebug(message) {
  if (systemChromeExtensionDebugEl instanceof HTMLElement) {
    systemChromeExtensionDebugEl.textContent = typeof message === 'string' ? message : '';
  }
}

function renderSystemChromeExtensionStatus() {
  if (!(systemChromeExtensionStatusEl instanceof HTMLElement)) {
    return;
  }

  const status = chromeExtensionRuntime.status;
  let text = 'Kontrollerar...';
  systemChromeExtensionStatusEl.className = 'ocr-status-badge';

  if (status === 'installed') {
    text = `Installerat${chromeExtensionRuntime.version ? ` (${chromeExtensionRuntime.version})` : ''}`;
    systemChromeExtensionStatusEl.classList.add('is-installed');
  } else if (status === 'outdated') {
    text = `Utdaterat${chromeExtensionRuntime.version ? ` (${chromeExtensionRuntime.version})` : ''}`;
    systemChromeExtensionStatusEl.classList.add('is-failed');
  } else if (status === 'missing') {
    text = 'Ej installerat';
    systemChromeExtensionStatusEl.classList.add('is-missing');
  }

  systemChromeExtensionStatusEl.textContent = text;

  if (systemChromeExtensionSuppressMissingEl instanceof HTMLInputElement) {
    systemChromeExtensionSuppressMissingEl.checked = chromeExtensionSuppressMissingNotice === true;
  }
  if (systemChromeExtensionPageEl instanceof HTMLInputElement) {
    systemChromeExtensionPageEl.value = 'chrome://extensions';
  }
  if (systemChromeExtensionDirectoryEl instanceof HTMLInputElement) {
    systemChromeExtensionDirectoryEl.value = chromeExtensionDirectory;
    systemChromeExtensionDirectoryEl.title = chromeExtensionDirectory;
    syncReadonlyInputWidth(systemChromeExtensionDirectoryEl);
  }
}

function syncReadonlyInputWidth(inputEl) {
  if (!(inputEl instanceof HTMLInputElement)) {
    return;
  }

  const value = String(inputEl.value || '');
  const widthCh = Math.max(28, value.length + 1);
  inputEl.style.width = `${widthCh}ch`;
}

function sendMessageToChromeExtension(message) {
  return new Promise((resolve, reject) => {
    if (!extensionMessagingAvailable()) {
      reject(new Error('Chrome-tillägg kan inte nås från den här webbläsaren.'));
      return;
    }
    if (!chromeExtensionRequiredId) {
      reject(new Error('Chrome-tilläggets id saknas.'));
      return;
    }

    try {
      chrome.runtime.sendMessage(chromeExtensionRequiredId, message, (response) => {
        const lastError = chrome.runtime.lastError;
        if (lastError) {
          reject(new Error(lastError.message || 'Chrome-tillägget svarade inte.'));
          return;
        }
        resolve(response || null);
      });
    } catch (error) {
      reject(error instanceof Error ? error : new Error(String(error || 'Unknown extension error')));
    }
  });
}

async function pingChromeExtension(options = {}) {
  if (chromeExtensionPingInFlight) {
    return chromeExtensionRuntime;
  }

  chromeExtensionPingInFlight = true;
  try {
    if (options.reloadConfig === true || !chromeExtensionRequiredId || !chromeExtensionRequiredVersion) {
      await loadChromeExtensionConfig();
    }

    const payload = await sendMessageToChromeExtension({ type: 'docflow.ping' });
    if (!payload || payload.ok !== true || typeof payload.version !== 'string') {
      throw new Error('Ogiltigt svar från Chrome-tillägget.');
    }

    const nextStatus = chromeExtensionIsCurrentVersion(payload.version) ? 'installed' : 'outdated';
    const hadUsableExtension = chromeExtensionIsUsable();
    const swedbankSessionAvailable = payload.swedbankSessionAvailable === true;
    const pendingPayeeLookups = currentSenderPayeeLookupRemainingCount();
    const pendingOrganizationLookups = currentSenderOrganizationLookupRemainingCount();
    const hasAnyAllabolagTab = payload.hasAnyAllabolagTab === true;
    setChromeExtensionRuntime({
      status: nextStatus,
      version: payload.version.trim(),
      lastError: '',
      organizationLookupLastError: '',
      swedbankSessionAvailable,
      hasAnySwedbankTab: payload.hasAnySwedbankTab === true,
      hasAnyAllabolagTab,
      loginRequired: swedbankSessionAvailable
        ? false
        : (chromeExtensionRuntime.loginRequired === true && pendingPayeeLookups > 0),
      profileSelectionRequired: swedbankSessionAvailable
        ? (chromeExtensionRuntime.profileSelectionRequired === true && pendingPayeeLookups > 0)
        : false,
      missingOrganizationCount: pendingOrganizationLookups,
    });

    if (!hadUsableExtension && nextStatus === 'installed') {
      maybeStartChromeExtensionOrganizationLookup();
      maybeStartChromeExtensionPayeeLookup();
    }
    if (
      nextStatus === 'installed'
      && currentSenderPayeeLookupRemainingCount() < 1
      && currentSenderOrganizationLookupRemainingCount() < 1
    ) {
      queueMicrotask(() => {
        fetchState({ force: true, syncTransport: false }).catch((error) => {
          console.error('[Docflow] fetchState after extension ping failed', error);
        });
      });
    }
  } catch (error) {
    setChromeExtensionRuntime({
      status: 'missing',
      version: '',
      lastError: error instanceof Error ? error.message : String(error || 'Chrome-tillägget svarade inte.'),
      organizationLookupLastError: '',
      swedbankSessionAvailable: null,
      hasAnySwedbankTab: false,
      hasAnyAllabolagTab: false,
      profileSelectionRequired: false,
    });
  } finally {
    chromeExtensionPingInFlight = false;
  }

  return chromeExtensionRuntime;
}

function clearChromeExtensionPresenceTimer() {
  if (chromeExtensionPresenceTimer !== null) {
    window.clearTimeout(chromeExtensionPresenceTimer);
    chromeExtensionPresenceTimer = null;
  }
}

function shouldMonitorChromeExtensionPresence() {
  if (!extensionMessagingAvailable()) {
    return false;
  }
  return chromeExtensionRuntime.status === 'missing' || chromeExtensionRuntime.status === 'outdated';
}

function shouldRetrySwedbankLookupAfterLogin() {
  return chromeExtensionIsUsable()
    && (chromeExtensionRuntime.loginRequired === true || chromeExtensionRuntime.profileSelectionRequired === true)
    && currentSenderPayeeLookupRemainingCount() > 0;
}

function shouldRetryOrganizationLookupAfterOpen() {
  return chromeExtensionIsUsable()
    && currentSenderOrganizationLookupRemainingCount() > 0;
}

function scheduleChromeExtensionPresenceCheck(delay = 1500) {
  clearChromeExtensionPresenceTimer();
  if (!shouldMonitorChromeExtensionPresence()) {
    return;
  }
  chromeExtensionPresenceTimer = window.setTimeout(async () => {
    chromeExtensionPresenceTimer = null;
    if (document.hidden) {
      scheduleChromeExtensionPresenceCheck(delay);
      return;
    }
    try {
      await pingChromeExtension();
    } catch (_error) {
      // pingChromeExtension updates runtime state on its own
    } finally {
      if (shouldMonitorChromeExtensionPresence()) {
        scheduleChromeExtensionPresenceCheck(1500);
      }
    }
  }, Math.max(200, delay));
}

function syncChromeExtensionPresencePolling() {
  if (shouldMonitorChromeExtensionPresence()) {
    scheduleChromeExtensionPresenceCheck(1500);
    return;
  }
  clearChromeExtensionPresenceTimer();
}

function syncChromeExtensionPayeeQueueRuntimeFromState() {
  const remainingCount = currentSenderPayeeLookupRemainingCount();
  const loginRequired = chromeExtensionRuntime.loginRequired === true && remainingCount > 0;
  const profileSelectionRequired = chromeExtensionRuntime.profileSelectionRequired === true && remainingCount > 0;
  if (
    chromeExtensionRuntime.missingPayeeCount === remainingCount
    && chromeExtensionRuntime.loginRequired === loginRequired
    && chromeExtensionRuntime.profileSelectionRequired === profileSelectionRequired
  ) {
    return;
  }

  setChromeExtensionRuntime({
    missingPayeeCount: remainingCount,
      loginRequired,
      profileSelectionRequired,
  });
}

function syncChromeExtensionOrganizationQueueRuntimeFromState() {
  const remainingCount = currentSenderOrganizationLookupRemainingCount();
  if (chromeExtensionRuntime.missingOrganizationCount === remainingCount) {
    return;
  }

  setChromeExtensionRuntime({
      missingOrganizationCount: remainingCount,
  });
}

function shouldShowAllabolagOpenNotice() {
  return chromeExtensionRuntime.status === 'installed'
    && currentSenderOrganizationLookupRemainingCount() > 0
    && chromeExtensionRuntime.hasAnyAllabolagTab === false;
}

function maybeStartChromeExtensionOrganizationLookup() {
  if (chromeExtensionOrganizationLookupInFlight || !chromeExtensionIsUsable()) {
    return;
  }

  const remainingCount = currentSenderOrganizationLookupRemainingCount();
  const item = currentSenderOrganizationLookupQueueItem();
  if (remainingCount < 1 || !item) {
    return;
  }
  if (chromeExtensionRuntime.hasAnyAllabolagTab === false) {
    setChromeExtensionRuntime({
      missingOrganizationCount: remainingCount,
      organizationLookupLastError: '',
    });
    return;
  }

  processMissingOrganizationNames().catch((error) => {
    setChromeExtensionRuntime({
      missingOrganizationCount: remainingCount,
      organizationLookupLastError: error instanceof Error ? error.message : String(error || 'Allabolag-uppslaget misslyckades.'),
    });
    console.error(error);
  });
}

async function processMissingOrganizationNames() {
  if (chromeExtensionOrganizationLookupInFlight || !chromeExtensionIsUsable()) {
    return;
  }

  const queue = currentSenderOrganizationLookupQueue();
  const remainingCount = Math.max(0, Number.parseInt(String(queue.remainingCount || 0), 10) || 0);
  const item = queue.item && typeof queue.item === 'object' ? queue.item : null;
  setChromeExtensionRuntime({
    missingOrganizationCount: remainingCount,
  });

  if (!item) {
    return;
  }

  if (chromeExtensionRuntime.hasAnyAllabolagTab === false) {
    return;
  }

  chromeExtensionOrganizationLookupInFlight = true;
  try {
    const extensionPayload = await sendMessageToChromeExtension({
      type: 'docflow.lookupOrganizationName',
      organizationNumber: item.normalizedOrganizationNumber || item.organizationNumber,
    });

    if (!extensionPayload || extensionPayload.ok !== true) {
      const openRequired = extensionPayload && extensionPayload.openRequired === true;
      setChromeExtensionRuntime({
        hasAnyAllabolagTab: openRequired ? false : chromeExtensionRuntime.hasAnyAllabolagTab,
        missingOrganizationCount: remainingCount,
        organizationLookupLastError: openRequired
          ? ''
          : (
            extensionPayload && typeof extensionPayload.message === 'string'
              ? extensionPayload.message
              : 'Allabolag-uppslaget misslyckades.'
          ),
      });
      return;
    }

    const resolvedOrganizationName = typeof extensionPayload.organizationName === 'string'
      ? extensionPayload.organizationName.trim()
      : '';
    const resolvedAlternativeNames = Array.isArray(extensionPayload.alternativeNames)
      ? extensionPayload.alternativeNames
          .map((value) => typeof value === 'string' ? value.trim() : '')
          .filter((value) => value !== '')
      : [];
    if (resolvedOrganizationName === '') {
      setChromeExtensionRuntime({
        missingOrganizationCount: remainingCount,
        organizationLookupLastError: 'Allabolag svarade utan företagsnamn för organisationsnumret.',
      });
      return;
    }

    const saveResponse = await fetch('/api/save-sender-organization-name.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        organizationId: item.organizationId,
        organizationName: resolvedOrganizationName,
        alternativeNames: resolvedAlternativeNames,
        currentSelectedJobId: selectedJobId || null,
      }),
    });
    const savePayload = await saveResponse.json().catch(() => null);
    if (!saveResponse.ok || !savePayload || savePayload.ok !== true) {
      throw new Error(savePayload && typeof savePayload.error === 'string' ? savePayload.error : 'Kunde inte spara organizationName.');
    }

    setChromeExtensionRuntime({
      hasAnyAllabolagTab: true,
      missingOrganizationCount: Number.parseInt(String(savePayload.remainingCount || 0), 10) || 0,
      organizationLookupLastError: '',
    });
    await fetchState({ refreshSenders: true, force: true, syncTransport: false });
  } catch (error) {
    setChromeExtensionRuntime({
      missingOrganizationCount: remainingCount,
      organizationLookupLastError: error instanceof Error ? error.message : String(error || 'Allabolag-uppslaget misslyckades.'),
    });
    throw error;
  } finally {
    chromeExtensionOrganizationLookupInFlight = false;
  }
}

async function openAllabolagLookupFlow() {
  const item = currentSenderOrganizationLookupQueueItem();
  if (!item) {
    return;
  }

  try {
    const payload = await sendMessageToChromeExtension({
      type: 'docflow.openAllabolagSearch',
      organizationNumber: item.normalizedOrganizationNumber || item.organizationNumber,
    });
    if (!payload || payload.ok !== true) {
      throw new Error(payload && typeof payload.message === 'string' ? payload.message : 'Kunde inte öppna allabolag.se.');
    }
    setChromeExtensionRuntime({
      hasAnyAllabolagTab: true,
      organizationLookupLastError: '',
    });
    window.setTimeout(() => {
      pingChromeExtension().finally(() => {
        maybeStartChromeExtensionOrganizationLookup();
      });
    }, 1200);
  } catch (error) {
    alert(error instanceof Error ? error.message : 'Kunde inte öppna allabolag.se.');
  }
}

function maybeStartChromeExtensionPayeeLookup() {
  if (chromeExtensionPayeeLookupInFlight || !chromeExtensionIsUsable()) {
    return;
  }

  const remainingCount = currentSenderPayeeLookupRemainingCount();
  const item = currentSenderPayeeLookupQueueItem();
  if (remainingCount < 1 || !item) {
    return;
  }
  if (chromeExtensionRuntime.loginRequired === true) {
    return;
  }

  processMissingPayeeNames().catch((error) => {
    const remainingCount = currentSenderPayeeLookupRemainingCount();
    setChromeExtensionRuntime({
      missingPayeeCount: remainingCount,
      loginRequired: remainingCount > 0 && chromeExtensionRuntime.hasAnySwedbankTab === false,
      profileSelectionRequired: false,
      lastError: error instanceof Error ? error.message : String(error || 'Swedbank-uppslaget misslyckades.'),
    });
    console.error(error);
  });
}

async function processMissingPayeeNames() {
  if (chromeExtensionPayeeLookupInFlight || !chromeExtensionIsUsable()) {
    return;
  }

  const queue = currentSenderPayeeLookupQueue();
  const remainingCount = Math.max(0, Number.parseInt(String(queue.remainingCount || 0), 10) || 0);
  const item = queue.item && typeof queue.item === 'object' ? queue.item : null;
  setChromeExtensionRuntime({
    missingPayeeCount: remainingCount,
    loginRequired: chromeExtensionRuntime.loginRequired === true && remainingCount > 0,
    profileSelectionRequired: chromeExtensionRuntime.profileSelectionRequired === true && remainingCount > 0,
  });

  if (!item) {
    return;
  }

  chromeExtensionPayeeLookupInFlight = true;
  try {
    const extensionPayload = await sendMessageToChromeExtension({
      type: 'docflow.lookupPayee',
      lookupType: item.type,
      number: item.normalizedNumber || item.number,
    });

    if (!extensionPayload || extensionPayload.ok !== true) {
      const payeeNotFound = extensionPayload && extensionPayload.errorCode === 'PAYEE_NOT_FOUND';
      if (payeeNotFound) {
        const saveResponse = await fetch('/api/save-sender-payment-payee.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({
            paymentId: item.paymentId,
            payeeName: null,
            lookupStatus: 'not_found',
            currentSelectedJobId: selectedJobId || null,
          }),
        });
        const savePayload = await saveResponse.json().catch(() => null);
        if (!saveResponse.ok || !savePayload || savePayload.ok !== true) {
          throw new Error(savePayload && typeof savePayload.error === 'string' ? savePayload.error : 'Kunde inte spara status för betalnummer utan mottagare.');
        }

        setChromeExtensionRuntime({
          swedbankSessionAvailable: true,
          loginRequired: false,
          profileSelectionRequired: false,
          missingPayeeCount: Number.parseInt(String(savePayload.remainingCount || 0), 10) || 0,
          lastError: '',
        });
        await fetchState({ refreshSenders: true, force: true, syncTransport: false });
        return;
      }

      const loginRequired = extensionPayload && extensionPayload.loginRequired === true;
      const profileSelectionRequired = extensionPayload && extensionPayload.profileSelectionRequired === true;
      setChromeExtensionRuntime({
        swedbankSessionAvailable: profileSelectionRequired
          ? true
          : (loginRequired ? false : chromeExtensionRuntime.swedbankSessionAvailable),
        loginRequired,
        profileSelectionRequired,
        missingPayeeCount: remainingCount,
        lastError: extensionPayload && typeof extensionPayload.message === 'string'
          ? extensionPayload.message
          : 'Swedbank-uppslaget misslyckades.',
      });
      return;
    }

    const resolvedPayeeName = typeof extensionPayload.payeeName === 'string'
      ? extensionPayload.payeeName.trim()
      : '';
    if (resolvedPayeeName === '') {
      setChromeExtensionRuntime({
        swedbankSessionAvailable: true,
        loginRequired: false,
        profileSelectionRequired: false,
        missingPayeeCount: remainingCount,
        lastError: 'Swedbank svarade utan payeeName för betalnumret.',
      });
      return;
    }

    const saveResponse = await fetch('/api/save-sender-payment-payee.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        paymentId: item.paymentId,
        payeeName: resolvedPayeeName,
        currentSelectedJobId: selectedJobId || null,
      }),
    });
    const savePayload = await saveResponse.json().catch(() => null);
    if (!saveResponse.ok || !savePayload || savePayload.ok !== true) {
      throw new Error(savePayload && typeof savePayload.error === 'string' ? savePayload.error : 'Kunde inte spara payeeName.');
    }

    setChromeExtensionRuntime({
      swedbankSessionAvailable: true,
      loginRequired: false,
      profileSelectionRequired: false,
      missingPayeeCount: Number.parseInt(String(savePayload.remainingCount || 0), 10) || 0,
      lastError: '',
    });
    await fetchState({ refreshSenders: true, force: true, syncTransport: false });
  } catch (error) {
    setChromeExtensionRuntime({
      missingPayeeCount: remainingCount,
      loginRequired: remainingCount > 0 && (
        chromeExtensionRuntime.loginRequired === true
        || chromeExtensionRuntime.hasAnySwedbankTab === false
      ),
      profileSelectionRequired: false,
      lastError: error instanceof Error ? error.message : String(error || 'Swedbank-uppslaget misslyckades.'),
    });
    throw error;
  } finally {
    chromeExtensionPayeeLookupInFlight = false;
  }
}

async function openSwedbankLoginFlow() {
  try {
    const payload = await sendMessageToChromeExtension({ type: 'docflow.openSwedbankLogin' });
    if (!payload || payload.ok !== true) {
      throw new Error(payload && typeof payload.message === 'string' ? payload.message : 'Kunde inte öppna Swedbank-inloggningen.');
    }
    setChromeExtensionRuntime({
      loginRequired: false,
      profileSelectionRequired: false,
      lastError: '',
    });
    window.setTimeout(() => {
      pingChromeExtension().finally(() => {
        maybeStartChromeExtensionPayeeLookup();
      });
    }, 1200);
  } catch (error) {
    alert(error instanceof Error ? error.message : 'Kunde inte öppna Swedbank.');
  }
}

async function extensionTest() {
  try {
    const runtime = await pingChromeExtension({ reloadConfig: true });
    console.log('Docflow extension test', {
      requiredId: chromeExtensionRequiredId,
      requiredVersion: chromeExtensionRequiredVersion,
      runtime,
    });
    updateSystemChromeExtensionDebug(
      runtime.status === 'installed'
        ? `Kontakt OK. Version ${runtime.version}.`
        : runtime.status === 'outdated'
          ? `Fel version. Installerad: ${runtime.version}, krävd: ${chromeExtensionRequiredVersion}.`
          : 'Tillägget svarade inte.'
    );
    if (chromeExtensionIsUsable()) {
      maybeStartChromeExtensionPayeeLookup();
    }
    return runtime;
  } catch (error) {
    console.error('Docflow extension test failed', error);
    updateSystemChromeExtensionDebug(error instanceof Error ? error.message : 'Test misslyckades.');
    throw error;
  }
}

window.extensionTest = extensionTest;

function closeAppNoticesOverflow() {
  if (!appNoticesOverflowOpen) {
    return;
  }
  appNoticesOverflowOpen = false;
  renderAppNotices();
}

function createAppNoticeButtonElement(action) {
  if (!action || typeof action.label !== 'string' || typeof action.onClick !== 'function') {
    return null;
  }
  const button = document.createElement('button');
  button.type = 'button';
  button.textContent = action.label;
  button.addEventListener('click', () => {
    closeAppNoticesOverflow();
    action.onClick();
  });
  return button;
}

function createAppNoticeElement(notice, options = {}) {
  const noticeEl = document.createElement('div');
  noticeEl.className = `app-notice is-${notice.kind || 'info'}`;
  if (options.inline === true) {
    noticeEl.classList.add('app-notice--inline');
  }
  if (options.expanded === true) {
    noticeEl.classList.add('app-notice--expanded');
  }
  if (options.measuring === true) {
    noticeEl.classList.add('app-notice--measuring');
  }

  const textEl = document.createElement('span');
  textEl.className = 'app-notice-text';
  textEl.textContent = normalizedAppNoticeText(notice.text);
  noticeEl.appendChild(textEl);

  const actionButtonEl = createAppNoticeButtonElement(notice.action || null);
  if (actionButtonEl instanceof HTMLButtonElement) {
    noticeEl.appendChild(actionButtonEl);
  }

  return noticeEl;
}

function createAppNoticesOverflowControls(overflowCount) {
  const controlsEl = document.createElement('div');
  controlsEl.className = 'app-notices-overflow-controls';

  const overflowCountEl = document.createElement('span');
  overflowCountEl.className = 'app-notices-more-count';
  overflowCountEl.textContent = `+${overflowCount}`;
  controlsEl.appendChild(overflowCountEl);

  const overflowButtonEl = document.createElement('button');
  overflowButtonEl.type = 'button';
  overflowButtonEl.className = 'app-notices-toggle';
  overflowButtonEl.textContent = appNoticesOverflowOpen ? 'Dölj' : 'Visa alla';
  overflowButtonEl.setAttribute('aria-expanded', appNoticesOverflowOpen ? 'true' : 'false');
  overflowButtonEl.title = appNoticesOverflowOpen ? 'Dölj notifieringar' : 'Visa alla notifieringar';
  const toggleOverflow = () => {
    appNoticesOverflowOpen = !appNoticesOverflowOpen;
    renderAppNotices();
  };
  overflowButtonEl.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    toggleOverflow();
  });
  overflowButtonEl.addEventListener('click', (event) => {
    if (event.detail !== 0) {
      return;
    }
    event.preventDefault();
    event.stopPropagation();
    toggleOverflow();
  });
  controlsEl.appendChild(overflowButtonEl);

  return controlsEl;
}

function appNoticeTextLineCount(textEl) {
  if (!(textEl instanceof HTMLElement)) {
    return 0;
  }
  const style = window.getComputedStyle(textEl);
  let lineHeight = Number.parseFloat(style.lineHeight);
  if (!Number.isFinite(lineHeight) || lineHeight <= 0) {
    const fontSize = Number.parseFloat(style.fontSize);
    lineHeight = Number.isFinite(fontSize) && fontSize > 0 ? fontSize * 1.2 : 16;
  }
  return Math.max(1, Math.round(textEl.getBoundingClientRect().height / lineHeight));
}

function measureCollapsedAppNoticeLayout(notices) {
  if (!(appNoticesEl instanceof HTMLElement) || !(topbarEl instanceof HTMLElement) || !Array.isArray(notices) || notices.length < 1) {
    return {
      visibleCount: Array.isArray(notices) ? notices.length : 0,
      widths: [],
      overflowCount: 0,
    };
  }

  const width = Math.max(0, Math.floor(appNoticesEl.getBoundingClientRect().width));
  if (width < 1) {
    return {
      visibleCount: notices.length,
      widths: [],
      overflowCount: 0,
    };
  }

  const MIN_NOTICE_WIDTH = 100;
  const WIDTH_STEP = 10;

  const measureHostEl = document.createElement('div');
  measureHostEl.className = 'app-notices app-notices--measuring';
  measureHostEl.style.position = 'fixed';
  measureHostEl.style.left = '-10000px';
  measureHostEl.style.top = '0';
  measureHostEl.style.width = `${width}px`;
  measureHostEl.style.visibility = 'hidden';
  measureHostEl.style.pointerEvents = 'none';

  const previewRowEl = document.createElement('div');
  previewRowEl.className = 'app-notices-preview-row';

  const previewEl = document.createElement('div');
  previewEl.className = 'app-notices-preview';
  previewRowEl.appendChild(previewEl);
  measureHostEl.appendChild(previewRowEl);
  document.body.appendChild(measureHostEl);

  const measureState = (visibleNotices, widths, overflowCount) => {
    previewEl.replaceChildren();
    const noticeEls = visibleNotices.map((notice, index) => {
      const noticeEl = createAppNoticeElement(notice, { inline: true, measuring: true });
      if (Number.isFinite(widths[index]) && widths[index] > 0) {
        noticeEl.style.width = `${Math.round(widths[index])}px`;
      }
      previewEl.appendChild(noticeEl);
      return noticeEl;
    });
    if (overflowCount > 0) {
      previewEl.appendChild(createAppNoticesOverflowControls(overflowCount));
    }

    const lineCounts = noticeEls.map((noticeEl) => {
      const textEl = noticeEl.querySelector('.app-notice-text');
      return appNoticeTextLineCount(textEl);
    });
    return {
      fits: previewEl.scrollWidth <= width + 1,
      lineCounts,
    };
  };

  const naturalWidths = notices.map((notice) => {
    const noticeEl = createAppNoticeElement(notice, { inline: true, measuring: true });
    previewEl.appendChild(noticeEl);
    return Math.ceil(noticeEl.getBoundingClientRect().width);
  });
  previewEl.replaceChildren();

  try {
    for (let visibleCount = notices.length; visibleCount >= 1; visibleCount -= 1) {
      const visibleNotices = notices.slice(0, visibleCount);
      const overflowCount = notices.length - visibleCount;
      const widths = naturalWidths.slice(0, visibleCount);

      let result = measureState(visibleNotices, widths, overflowCount);
      if (result.fits) {
        return { visibleCount, widths, overflowCount };
      }

      for (let noticeIndex = 0; noticeIndex < widths.length; noticeIndex += 1) {
        let lastGoodWidth = widths[noticeIndex];
        while ((widths[noticeIndex] - WIDTH_STEP) >= MIN_NOTICE_WIDTH) {
          widths[noticeIndex] -= WIDTH_STEP;
          result = measureState(visibleNotices, widths, overflowCount);
          const lineCount = result.lineCounts[noticeIndex] || 0;
          if (lineCount > 2) {
            widths[noticeIndex] = lastGoodWidth;
            result = measureState(visibleNotices, widths, overflowCount);
            break;
          }
          lastGoodWidth = widths[noticeIndex];
          if (result.fits) {
            return { visibleCount, widths, overflowCount };
          }
        }
      }

      result = measureState(visibleNotices, widths, overflowCount);
      if (result.fits) {
        return { visibleCount, widths, overflowCount };
      }
    }

    return {
      visibleCount: Math.min(1, notices.length),
      widths: naturalWidths.slice(0, 1),
      overflowCount: Math.max(0, notices.length - 1),
    };
  } finally {
    measureHostEl.remove();
  }
}

function collectAppNoticeDescriptors() {
  const notices = [];
  const pendingPayeeLookups = currentSenderPayeeLookupRemainingCount();
  const pendingOrganizationLookups = currentSenderOrganizationLookupRemainingCount();
  const showSwedbankLoginNotice =
    chromeExtensionRuntime.status === 'installed'
    && pendingPayeeLookups > 0
    && (
      chromeExtensionRuntime.profileSelectionRequired === true
      ||
      chromeExtensionRuntime.loginRequired === true
      || chromeExtensionRuntime.hasAnySwedbankTab === false
    );
  const showAllabolagOpenNotice = shouldShowAllabolagOpenNotice();

  if (state.archivingRules && state.archivingRules.hasPendingArchivedUpdates === true) {
    notices.push({
      kind: 'warning',
      text: 'Arkiveringsregler har ändrats. Du kan uppdatera arkiverade dokument.',
      action: {
        label: 'Uppdatera arkivering',
        onClick: () => {
          openArchivingReviewSettingsDirect();
        }
      }
    });
  }

  if (chromeExtensionRuntime.status === 'missing' && chromeExtensionSuppressMissingNotice !== true) {
    notices.push({
      kind: 'warning',
      text: 'Chrome-tillägget för Swedbank saknas eller svarar inte.',
      action: {
        label: 'Visa tillägg',
        onClick: () => {
          openExtensionsSettingsDirect();
        }
      }
    });
  } else if (chromeExtensionRuntime.status === 'outdated') {
    notices.push({
      kind: 'warning',
      text: `Chrome-tillägget är utdaterat. Krävd version är ${chromeExtensionRequiredVersion || 'okänd'}.`,
      action: {
        label: 'Visa tillägg',
        onClick: () => {
          openExtensionsSettingsDirect();
        }
      }
    });
  }

  if (showSwedbankLoginNotice) {
    notices.push({
      kind: 'error',
      text: chromeExtensionRuntime.profileSelectionRequired === true
        ? 'Swedbank kräver att du väljer profil för att hämta namn för betalnummer.'
        : 'Swedbank är inte tillgängligt just nu. Logga in för att hämta namn för betalnummer.',
      action: {
        label: chromeExtensionRuntime.profileSelectionRequired === true ? 'Välj profil' : 'Logga in',
        onClick: () => {
          openSwedbankLoginFlow();
        }
      }
    });
  } else if (
    chromeExtensionRuntime.status === 'installed'
    && pendingPayeeLookups > 0
    && chromeExtensionRuntime.lastError
  ) {
    notices.push({
      kind: 'error',
      text: `Swedbank-uppslaget misslyckades: ${chromeExtensionRuntime.lastError}`,
    });
  }

  if (showAllabolagOpenNotice) {
    notices.push({
      kind: 'warning',
      text: 'Allabolag.se behöver vara öppet för att hämta namn för organisationsnummer.',
      action: {
        label: 'Öppna allabolag.se',
        onClick: () => {
          openAllabolagLookupFlow();
        }
      }
    });
  } else if (
    chromeExtensionRuntime.status === 'installed'
    && pendingOrganizationLookups > 0
    && chromeExtensionRuntime.organizationLookupLastError
  ) {
    notices.push({
      kind: 'error',
      text: `Allabolag-uppslaget misslyckades: ${chromeExtensionRuntime.organizationLookupLastError}`,
    });
  }

  return notices;
}

function renderAppNotices() {
  if (!(appNoticesEl instanceof HTMLElement)) {
    return;
  }

  const notices = collectAppNoticeDescriptors();
  appNoticesEl.replaceChildren();
  if (topbarEl instanceof HTMLElement) {
    topbarEl.classList.remove('is-notices-expanded');
  }

  if (notices.length === 0) {
    appNoticesOverflowOpen = false;
    appNoticesEl.classList.add('hidden');
    if (topbarEl instanceof HTMLElement) {
      topbarEl.style.setProperty('--app-notices-extra-height', '0px');
      topbarEl.style.setProperty('--app-notices-expanded-list-height', '0px');
    }
    syncArchivingReviewTabIndicator();
    return;
  }

  appNoticesEl.classList.remove('hidden');
  const previewRowEl = document.createElement('div');
  previewRowEl.className = 'app-notices-preview-row';
  const previewEl = document.createElement('div');
  previewEl.className = 'app-notices-preview';
  previewRowEl.appendChild(previewEl);
  appNoticesEl.appendChild(previewRowEl);

  const collapsedLayout = measureCollapsedAppNoticeLayout(notices);
  const visibleCount = Math.max(0, Math.min(notices.length, collapsedLayout.visibleCount));
  const overflowCount = Math.max(0, notices.length - visibleCount);
  if (overflowCount < 1) {
    appNoticesOverflowOpen = false;
  }

  notices.slice(0, overflowCount > 0 ? visibleCount : notices.length).forEach((notice, index) => {
    const noticeEl = createAppNoticeElement(notice, { inline: true });
    const width = collapsedLayout.widths[index];
    if (Number.isFinite(width) && width > 0) {
      noticeEl.style.width = `${Math.round(width)}px`;
    }
    previewEl.appendChild(noticeEl);
  });
  if (overflowCount > 0) {
    previewEl.appendChild(createAppNoticesOverflowControls(overflowCount));
  }

  if (overflowCount > 0 && appNoticesOverflowOpen) {
    if (topbarEl instanceof HTMLElement) {
      topbarEl.classList.add('is-notices-expanded');
    }
    const expandedListEl = document.createElement('div');
    expandedListEl.className = 'app-notices-expanded-list';
    notices.forEach((notice) => {
      expandedListEl.appendChild(createAppNoticeElement(notice, { expanded: true }));
    });
    appNoticesEl.appendChild(expandedListEl);
  }

  queueTopbarExpandedMetricsSync();
  syncArchivingReviewTabIndicator();
}

function syncArchivingReviewTabIndicator() {
  if (!(archivingReviewSettingsTabEl instanceof HTMLElement)) {
    return;
  }
  const hasChanges = state.archivingRules && state.archivingRules.hasPendingArchivedUpdates === true;
  archivingReviewSettingsTabEl.classList.toggle('has-unpublished-changes', hasChanges);
  archivingReviewSettingsTabEl.title = hasChanges
    ? 'Arkiverade dokument kan uppdateras mot aktuella regler.'
    : '';
}

function deepCloneJson(value) {
  return JSON.parse(JSON.stringify(value));
}

function emptyArchivingReviewPayload() {
  return {
    activeArchivingRulesVersion: 1,
    changedSections: [],
    templateChanges: [],
    summary: {
      archivedJobs: 0,
      testedJobs: 0,
      affected: 0,
      dismissed: 0,
      unchanged: 0,
      improvements: 0,
      risks: 0,
      info: 0,
    },
    jobs: [],
    session: {
      status: 'idle',
      ignoreDismissed: false,
      analyzedCount: 0,
      totalCount: 0,
      foundCount: 0,
      remainingCount: 0,
    },
    reason: '',
    signature: '',
  };
}

function normalizeArchivingReviewPayload(payload) {
  const next = payload && typeof payload === 'object' ? payload : {};
  const summary = next.summary && typeof next.summary === 'object' ? next.summary : {};
  const session = next.session && typeof next.session === 'object' ? next.session : {};
  const normalized = {
    activeArchivingRulesVersion: Number.parseInt(String(next.activeArchivingRulesVersion || 1), 10) || 1,
    changedSections: Array.isArray(next.changedSections) ? next.changedSections.filter((value) => typeof value === 'string' && value.trim()) : [],
    templateChanges: Array.isArray(next.templateChanges)
      ? next.templateChanges
        .filter((item) => item && typeof item === 'object')
        .map((item) => ({
          filenameTemplateId: typeof item.filenameTemplateId === 'string' ? item.filenameTemplateId : '',
          filenameTemplateName: typeof item.filenameTemplateName === 'string' ? item.filenameTemplateName : '',
          archiveFolderId: typeof item.archiveFolderId === 'string' ? item.archiveFolderId : '',
          archiveFolderName: typeof item.archiveFolderName === 'string' ? item.archiveFolderName : '',
          before: typeof item.before === 'string' ? item.before : '',
          after: typeof item.after === 'string' ? item.after : '',
        }))
      : [],
    summary: {
      archivedJobs: Number.parseInt(String(summary.archivedJobs || summary.testedJobs || 0), 10) || 0,
      testedJobs: Number.parseInt(String(summary.testedJobs || 0), 10) || 0,
      affected: Number.parseInt(String(summary.affected || 0), 10) || 0,
      dismissed: Number.parseInt(String(summary.dismissed || 0), 10) || 0,
      unchanged: Number.parseInt(String(summary.unchanged || 0), 10) || 0,
      improvements: Number.parseInt(String(summary.improvements || 0), 10) || 0,
      risks: Number.parseInt(String(summary.risks || 0), 10) || 0,
      info: Number.parseInt(String(summary.info || 0), 10) || 0,
    },
    jobs: Array.isArray(next.jobs)
      ? next.jobs
        .filter((item) => item && typeof item === 'object')
        .map((item) => ({
          ...item,
          archivedVersion: Number.parseInt(String(item.archivedVersion || 0), 10) || 0,
          dismissedAnalysisVersion: Number.parseInt(String(item.dismissedAnalysisVersion || 0), 10) || 0,
          dismissedForVersion: item.dismissedForVersion === true,
        }))
      : [],
    session: {
      status: typeof session.status === 'string' ? session.status : 'idle',
      ignoreDismissed: false,
      analyzedCount: Number.parseInt(String(session.analyzedCount || 0), 10) || 0,
      totalCount: Number.parseInt(String(session.totalCount || 0), 10) || 0,
      foundCount: Number.parseInt(String(session.foundCount || 0), 10) || 0,
      remainingCount: Number.parseInt(String(session.remainingCount || 0), 10) || 0,
    },
    reason: typeof next.reason === 'string' ? next.reason : '',
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
    hasPendingArchivedUpdates: next.hasPendingArchivedUpdates === true,
    pendingArchivedUpdateCount: Number.parseInt(String(next.pendingArchivedUpdateCount || 0), 10) || 0,
    updateReview: normalizeArchivingReviewPayload(next.updateReview),
    signature: typeof next.signature === 'string' ? next.signature : '',
  };
}

function syncArchivingReviewPayloadFromState(force = false) {
  const nextPayload = normalizeArchivingReviewPayload(state.archivingRules && state.archivingRules.updateReview);
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
  const previousSignature = typeof state.archivingRules?.signature === 'string' ? state.archivingRules.signature : '';
  state.archivingRules = normalizeArchivingRulesStatePayload(payload, state.archivingRules);
  if (state.archivingRules.signature !== previousSignature) {
    archivedReviewPayloadByJobId.clear();
  }
  renderAppNotices();
  syncArchivingReviewTabIndicator();
  syncArchivingReviewPayloadFromState(options.forceRender === true);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
  if ((currentViewMode === 'review' || currentJobListMode === 'archived-review') && selectedJobId) {
    loadArchivedReview(selectedJobId, true).catch(() => {
      renderArchivedReviewPanel();
    });
  }
}

function mergeFetchStateOptions(baseOptions = {}, nextOptions = {}) {
  const base = baseOptions && typeof baseOptions === 'object' ? baseOptions : {};
  const next = nextOptions && typeof nextOptions === 'object' ? nextOptions : {};
  const merged = {
    refreshClients: base.refreshClients === true || next.refreshClients === true,
    refreshSenders: base.refreshSenders === true || next.refreshSenders === true,
    refreshArchiveStructure: base.refreshArchiveStructure === true || next.refreshArchiveStructure === true,
    force: base.force === true || next.force === true,
  };
  if (base.syncTransport === false && next.syncTransport === false) {
    merged.syncTransport = false;
  }
  return merged;
}

function syncStateEventCursorFromPayload(payload) {
  const lastEventId = Number.parseInt(String(payload && payload.lastEventId || ''), 10);
  if (Number.isInteger(lastEventId) && lastEventId > stateEventCursor) {
    stateEventCursor = lastEventId;
  }
}

function applyArchivingRulesPayloadFromResponse(payload, options = {}) {
  syncStateEventCursorFromPayload(payload);
  if (
    options.bumpLocalRevision !== true
    && Number.isInteger(options.expectedLocalRevision)
    && options.expectedLocalRevision !== archivingRulesLocalRevision
  ) {
    return;
  }
  if (!payload || !payload.archivingRules || typeof payload.archivingRules !== 'object') {
    return;
  }
  if (options.bumpLocalRevision === true) {
    archivingRulesLocalRevision += 1;
  }
  applyArchivingRulesStatePayload(payload.archivingRules, {
    forceRender: options.forceRender === true
  });
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

  const source = payload && payload.currentAutoResult && typeof payload.currentAutoResult === 'object'
    ? payload.currentAutoResult
    : {};
  const draft = {
    clientId: typeof source.clientId === 'string' ? source.clientId : '',
    senderId: source.senderId ? String(source.senderId) : '',
    folderId: typeof source.folderId === 'string' ? source.folderId : '',
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
    const rawValue = values && Object.prototype.hasOwnProperty.call(values, field.key)
      ? values[field.key]
      : null;
    const displayValue = Array.isArray(rawValue)
      ? (rawValue[0] ?? '')
      : rawValue;
    input.value = displayValue !== null && displayValue !== undefined
      ? String(displayValue)
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

function enrichArchivedReviewPayload(job, payload) {
  const reviewStateSignature = typeof state.archivingRules?.signature === 'string'
    ? state.archivingRules.signature
    : '';
  return {
    ...payload,
    _jobSignature: jobStateSignature(job),
    _reviewStateSignature: reviewStateSignature,
    _draftSignature: JSON.stringify({
      archivedValue: payload && payload.archivedValue ? payload.archivedValue : null,
      historicalAutoResult: payload && payload.historicalAutoResult ? payload.historicalAutoResult : null,
      currentAutoResult: payload && payload.currentAutoResult ? payload.currentAutoResult : null,
      reviewStateSignature,
    }),
  };
}

async function fetchArchivedReviewPayload(job, options = {}) {
  if (!job || !jobSupportsArchivedReview(job)) {
    return null;
  }
  const force = options.force === true;
  const cached = !force ? cachedArchivedReviewPayload(job) : null;
  if (cached && archivedReviewPayloadIsCurrent(job, cached)) {
    return cached;
  }
  const response = await fetch('/api/get-archived-job-review.php?id=' + encodeURIComponent(job.id), { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda jobbgranskningen.');
  }
  const payload = await response.json();
  const enriched = enrichArchivedReviewPayload(job, payload);
  archivedReviewPayloadByJobId.set(job.id, enriched);
  return enriched;
}

function preloadArchivedReviewPayloadsAroundJob(jobId) {
  if (currentJobListMode !== 'archived-review') {
    return;
  }
  const jobs = displayedJobsForCurrentListMode();
  const index = jobs.findIndex((job) => job && job.id === jobId);
  if (index < 0) {
    return;
  }
  [index - 1, index, index + 1].forEach((candidateIndex) => {
    const job = jobs[candidateIndex] || null;
    if (!job || !jobSupportsArchivedReview(job)) {
      return;
    }
    const cached = cachedArchivedReviewPayload(job);
    if (cached && archivedReviewPayloadIsCurrent(job, cached)) {
      return;
    }
    fetchArchivedReviewPayload(job).catch(() => {});
  });
}

function applyOptimisticArchivedReviewResolution(jobId, options = {}) {
  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || state.archivingRules?.updateReview);
  if (!payload.jobs.some((item) => item && item.jobId === jobId)) {
    return;
  }

  const dismissed = options.dismissed === true;
  const remainingJobs = payload.jobs
    .map((item) => {
      if (!item || item.jobId !== jobId) {
        return item;
      }
      if (!dismissed) {
        return null;
      }
      return {
        ...item,
        dismissedForVersion: true,
        dismissedAnalysisVersion: Number.parseInt(String(state.archivingRules?.activeVersion || 0), 10) || 0,
      };
    })
    .filter(Boolean);
  const nextSummary = {
    ...payload.summary,
    affected: Math.max(0, (payload.summary.affected || 0) - 1),
    dismissed: dismissed
      ? ((payload.summary.dismissed || 0) + 1)
      : (payload.summary.dismissed || 0),
  };
  const nextPayload = {
    ...payload,
    jobs: remainingJobs,
    summary: nextSummary,
    session: {
      ...payload.session,
      ignoreDismissed: false,
      foundCount: remainingJobs.length,
    },
    signature: '',
  };
  const nextPendingCount = remainingJobs.filter((item) => item && item.dismissedForVersion !== true).length;

  state.archivingRules = {
    ...state.archivingRules,
    hasPendingArchivedUpdates: nextPendingCount > 0 || nextPayload.session.status === 'running',
    pendingArchivedUpdateCount: nextPendingCount,
    updateReview: nextPayload,
  };
  syncArchivingReviewPayloadFromState(true);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
}

function decrementArchivingReviewSummaryBucket(summary, type) {
  if (!summary || typeof summary !== 'object') {
    return summary;
  }
  if (type === 'improvement') {
    summary.improvements = Math.max(0, (summary.improvements || 0) - 1);
  } else if (type === 'risk') {
    summary.risks = Math.max(0, (summary.risks || 0) - 1);
  } else if (type === 'info') {
    summary.info = Math.max(0, (summary.info || 0) - 1);
  } else {
    summary.unchanged = Math.max(0, (summary.unchanged || 0) - 1);
  }
  return summary;
}

function clearLocalArchivedReviewStateForJob(jobId) {
  if (typeof jobId !== 'string' || jobId === '') {
    return;
  }

  archivedReviewDraftByJobId.delete(jobId);
  clearArchivedReviewEditorState(jobId);
  archivedReviewPayloadByJobId.delete(jobId);
  if (archivedJobReviewPayload && archivedJobReviewPayload.jobId === jobId) {
    archivedJobReviewPayload = null;
  }
}

function archivingRulesStateWithoutJob(archivingRulesState, job) {
  if (!job || typeof job.id !== 'string' || job.id === '') {
    return archivingRulesState;
  }

  const payload = normalizeArchivingReviewPayload(archivingRulesState && archivingRulesState.updateReview);
  if (!payload.jobs.some((item) => item && item.jobId === job.id)) {
    return archivingRulesState;
  }

  const activeVersion = Number.parseInt(String(archivingRulesState?.activeVersion || 0), 10) || 0;
  const payloadJob = payload.jobs.find((item) => item && item.jobId === job.id) || null;
  const reviewPayload = archivedJobReviewPayload && archivedJobReviewPayload.jobId === job.id
    ? archivedJobReviewPayload
    : (cachedArchivedReviewPayload(job) || null);
  const classificationType = payloadJob && payloadJob.classification && typeof payloadJob.classification.type === 'string'
    ? payloadJob.classification.type
    : (reviewPayload && reviewPayload.classification && typeof reviewPayload.classification.type === 'string'
      ? reviewPayload.classification.type
      : 'unchanged');
  const dismissedForVersion = payloadJob
    ? payloadJob.dismissedForVersion === true
    : ((Number.parseInt(String(job.dismissedAnalysisVersion || 0), 10) || 0) === activeVersion);
  const wasAffected = classificationType !== 'unchanged' && !dismissedForVersion;
  const remainingJobs = payload.jobs.filter((item) => !(item && item.jobId === job.id));
  const nextSummary = decrementArchivingReviewSummaryBucket({
    ...payload.summary,
    archivedJobs: Math.max(0, (payload.summary.archivedJobs || payload.summary.testedJobs || 0) - 1),
    testedJobs: Math.max(0, (payload.summary.testedJobs || 0) - 1),
    affected: wasAffected ? Math.max(0, (payload.summary.affected || 0) - 1) : (payload.summary.affected || 0),
    dismissed: dismissedForVersion ? Math.max(0, (payload.summary.dismissed || 0) - 1) : (payload.summary.dismissed || 0),
  }, classificationType);
  const nextPayload = {
    ...payload,
    jobs: remainingJobs,
    summary: nextSummary,
    session: {
      ...payload.session,
      foundCount: remainingJobs.length,
    },
    signature: '',
  };
  const nextPendingCount = remainingJobs.filter((item) => item && item.dismissedForVersion !== true).length;

  return {
    ...(archivingRulesState && typeof archivingRulesState === 'object' ? archivingRulesState : state.archivingRules),
    hasPendingArchivedUpdates: nextSummary.affected > 0 || nextPayload.session.status === 'running',
    pendingArchivedUpdateCount: nextPendingCount,
    updateReview: nextPayload,
  };
}

function removeJobFromArchivingReviewLocalState(job) {
  if (!job || typeof job.id !== 'string' || job.id === '') {
    return false;
  }

  clearLocalArchivedReviewStateForJob(job.id);
  const nextArchivingRulesState = archivingRulesStateWithoutJob(state.archivingRules, job);
  const changed = nextArchivingRulesState !== state.archivingRules;
  state.archivingRules = nextArchivingRulesState;
  return changed;
}

function applyOptimisticArchivedReviewUnarchive(job) {
  if (!job || typeof job.id !== 'string' || job.id === '') {
    return;
  }

  removeJobFromArchivingReviewLocalState(job);
  syncArchivingReviewPayloadFromState(true);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  refreshSelection();
}

async function saveArchivedReviewAction(action) {
  const selectedJob = findJobById(selectedJobId);
  const payload = selectedJob && archivedJobReviewPayload && archivedJobReviewPayload.jobId === selectedJob.id
    ? archivedJobReviewPayload
    : null;
  if (!selectedJob || selectedJob.archived !== true || !payload || payload.isActionable !== true) {
    return;
  }
  const draft = payload ? ensureArchivedReviewDraft(selectedJob.id, payload) : null;

  const body = {
    jobId: selectedJob.id,
    action,
  };
  if (action === 'manual' && draft) {
    body.clientId = draft.clientId || null;
    body.senderId = draft.senderId || null;
    body.folderId = draft.folderId || null;
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
    throw new Error(result && typeof result.error === 'string' ? result.error : 'Kunde inte uppdatera den arkiverade posten.');
  }

  archivedReviewDraftByJobId.delete(selectedJob.id);
  clearArchivedReviewEditorState(selectedJob.id);
  archivedReviewPayloadByJobId.delete(selectedJob.id);
  archivedJobReviewPayload = null;
  loadedMetaJobId = '';
  loadedMatchesJobId = '';
  loadedOcrJobId = '';
  applyOptimisticArchivedReviewResolution(selectedJob.id, { dismissed: action === 'dismiss' });
  applyStateEntry(result.entry);
  await fetchState({ force: true, refreshArchiveStructure: true });
  const nextSelectedJob = selectedJobId === selectedJob.id ? findJobById(selectedJob.id) : null;
  if (currentJobListMode === 'archived-review' && nextSelectedJob && nextSelectedJob.archived === true) {
    await loadArchivedReview(selectedJob.id, true);
  } else {
    renderArchivedReviewPanel();
  }
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

  const cached = !force ? cachedArchivedReviewPayload(selectedJob) : null;
  if (cached && archivedReviewPayloadIsCurrent(selectedJob, cached)) {
    archivedJobReviewPayload = cached;
    syncArchivedReviewEditorState(selectedJob, cached);
    setClientForJob(selectedJob);
    setSenderForJob(selectedJob);
    setFolderForJob(selectedJob);
    setLabelsForJob(selectedJob);
    syncFilenameField(selectedJob);
    updateArchiveAction(selectedJob);
    updateSelectedJobResetActions(selectedJob);
    renderArchivedReviewPanel();
    preloadArchivedReviewPayloadsAroundJob(jobId);
    return;
  }

  const requestSeq = ++archivedReviewRequestSeq;
  archivedJobReviewPayload = {
    jobId,
    loading: true,
    _jobSignature: jobStateSignature(selectedJob),
    _reviewStateSignature: typeof state.archivingRules?.signature === 'string' ? state.archivingRules.signature : '',
  };
  renderArchivedReviewPanel();

  const payload = await fetchArchivedReviewPayload(selectedJob, { force });
  if (requestSeq !== archivedReviewRequestSeq) {
    return;
  }
  archivedJobReviewPayload = payload;
  syncArchivedReviewEditorState(selectedJob, archivedJobReviewPayload);
  setClientForJob(selectedJob);
  setSenderForJob(selectedJob);
  setFolderForJob(selectedJob);
  setLabelsForJob(selectedJob);
  syncFilenameField(selectedJob);
  updateArchiveAction(selectedJob);
  updateSelectedJobResetActions(selectedJob);
  renderArchivedReviewPanel();
  preloadArchivedReviewPayloadsAroundJob(jobId);
}

function createArchivingReviewLabelChip(labelName) {
  const chip = document.createElement('span');
  chip.className = 'job-labels-selected-chip archiving-review-change-label-chip';
  const text = document.createElement('span');
  text.className = 'job-labels-selected-chip-text';
  text.textContent = labelName;
  chip.appendChild(text);
  return chip;
}

function renderArchivingReviewChangeMessage(messageEl, item) {
  if (!(messageEl instanceof HTMLElement)) {
    return;
  }
  if (item && item.field === 'labels' && typeof item.labelName === 'string' && item.labelName.trim() !== '') {
    messageEl.classList.add('has-chip');
    const prefix = document.createElement('span');
    prefix.textContent = typeof item.messagePrefix === 'string' && item.messagePrefix.trim() !== ''
      ? item.messagePrefix
      : (typeof item.message === 'string' ? item.message : 'Etikett:');
    messageEl.appendChild(prefix);
    messageEl.appendChild(createArchivingReviewLabelChip(item.labelName.trim()));
    if (typeof item.metaText === 'string' && item.metaText.trim() !== '') {
      const meta = document.createElement('span');
      meta.className = 'archiving-review-change-label-meta';
      meta.textContent = `(${item.metaText.trim()})`;
      messageEl.appendChild(meta);
    }
    return;
  }
  messageEl.textContent = item && typeof item.message === 'string' ? item.message : '';
}

function buildArchivingReviewChangeList(changeItems) {
  const list = document.createElement('ul');
  list.className = 'archiving-review-change-list';
  changeItems.forEach((item) => {
    const row = document.createElement('li');
    row.className = `archiving-review-change is-${item && item.type ? item.type : 'info'}`;
    const message = document.createElement('div');
    message.className = 'archiving-review-change-message';
    renderArchivingReviewChangeMessage(message, item);
    row.appendChild(message);
    if (item && typeof item.detail === 'string' && item.detail) {
      const detail = document.createElement('div');
      detail.className = 'archiving-review-change-detail';
      detail.textContent = item.detail;
      row.appendChild(detail);
    }
    list.appendChild(row);
  });
  return list;
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
  const reviewIsActionable = payload && payload.isActionable === true;

  if (!payload || payload.loading === true) {
    archivedReviewPanelEl.textContent = 'Laddar jobbgranskning...';
    return;
  }

  const wrapper = document.createElement('div');
  wrapper.className = 'archived-review-layout';

  const header = document.createElement('div');
  header.className = 'archived-review-header';
  const title = document.createElement('h3');
  title.textContent = 'Uppdatera arkiverat jobb';
  const summary = document.createElement('div');
  summary.className = 'archived-review-summary-line';
  const classification = payload.classification && typeof payload.classification === 'object'
    ? String(payload.classification.type || 'info')
    : 'info';
  if (!reviewIsActionable) {
    summary.textContent = 'Det här arkiverade jobbet påverkas inte av aktuella regler just nu.';
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
    const list = buildArchivingReviewChangeList(changeItems);
    list.classList.add('archived-review-change-list');
    header.appendChild(list);
  }
  wrapper.appendChild(header);

  const note = document.createElement('div');
  note.className = 'archived-review-empty';
  note.textContent = reviewIsActionable
    ? 'Använd sidomenyn för att uppdatera eller avfärda dokumentet för aktuell version.'
    : 'Det här jobbet behöver inte uppdateras just nu.';
  wrapper.appendChild(note);

  archivedReviewPanelEl.replaceChildren(wrapper);
}

async function loadArchivingRuleReview() {
  syncArchivingReviewPayloadFromState(true);
}

function renderArchivingRuleReview(force = false) {
  if (!(archivingReviewSummaryEl instanceof HTMLElement) || !(archivingReviewTemplateChangesEl instanceof HTMLElement) || !(archivingReviewJobsEl instanceof HTMLElement) || !(archivingReviewStatusEl instanceof HTMLElement) || !(archivingReviewActionsEl instanceof HTMLElement)) {
    return;
  }

  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || emptyArchivingReviewPayload());
  if (!force && payload.signature && payload.signature === renderedArchivingReviewSignature) {
    return;
  }
  renderedArchivingReviewSignature = payload.signature;
  const summary = payload.summary && typeof payload.summary === 'object' ? payload.summary : {};
  const jobs = Array.isArray(payload.jobs) ? payload.jobs : [];
  const visibleJobs = jobs.filter((item) => item && item.dismissedForVersion !== true);
  const templateChanges = Array.isArray(payload.templateChanges) ? payload.templateChanges : [];
  const session = payload.session && typeof payload.session === 'object' ? payload.session : {};
  const sessionStatus = typeof session.status === 'string' ? session.status : 'idle';
  const analyzedCount = Number.parseInt(String(session.analyzedCount || 0), 10) || 0;
  const totalCount = Number.parseInt(String(session.totalCount || 0), 10) || 0;
  const foundCount = Number.parseInt(String(session.foundCount || 0), 10) || jobs.length;
  const actionableCount = visibleJobs.length;

  archivingReviewSummaryEl.innerHTML = '';
  archivingReviewTemplateChangesEl.innerHTML = '';
  archivingReviewJobsEl.innerHTML = '';
  archivingReviewActionsEl.innerHTML = '';

  archivingReviewSummaryEl.classList.remove('hidden');
  if (templateChanges.length > 0) {
    archivingReviewTemplateChangesEl.classList.remove('hidden');
    templateChanges.forEach((change) => {
      const item = document.createElement('div');
      item.className = 'archiving-review-template-change';
      const templateName = change.filenameTemplateName || change.archiveFolderName || change.archiveFolderId || 'okänd filnamnsregel';
      const title = document.createElement('div');
      title.className = 'archiving-review-template-change-title';
      title.textContent = `${templateName} har ändrats.`;
      const detail = document.createElement('div');
      detail.className = 'archiving-review-template-change-detail';
      detail.textContent = `Från: ${change.before || 'Tom filnamnsmall'}  Till: ${change.after || 'Tom filnamnsmall'}`;
      item.append(title, detail);
      archivingReviewTemplateChangesEl.appendChild(item);
    });
  } else {
    archivingReviewTemplateChangesEl.classList.add('hidden');
  }

  const summaryItems = [
    ['Arkiverade dokument', summary.archivedJobs || summary.testedJobs || 0],
    ['Påverkas', summary.affected || 0],
    ['Påverkas inte', summary.unchanged || 0],
    ['Avfärdade', summary.dismissed || 0],
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

  archivingReviewStatusEl.classList.remove('hidden');
  const actions = document.createElement('div');
  actions.className = 'panel-actions';
  const openListButton = document.createElement('button');
  openListButton.type = 'button';
  openListButton.textContent = 'Öppna Arkiverade att granska';
  openListButton.disabled = foundCount < 1;
  openListButton.addEventListener('click', () => {
    closeSettingsModal(true);
    setJobListMode('archived-review');
    if (currentViewMode === 'review') {
      setViewMode('pdf');
    }
  });
  const statusActions = document.createElement('div');
  statusActions.className = 'panel-actions';
  const updateAllButton = document.createElement('button');
  updateAllButton.type = 'button';
  updateAllButton.textContent = 'Applicera nya regler på alla påverkade';
  updateAllButton.title = 'Applicera de nya arkiveringsreglerna på alla påverkade arkiverade dokument';
  updateAllButton.disabled = actionableCount < 1 || sessionStatus === 'running';
  updateAllButton.addEventListener('click', async () => {
    try {
      await updateAllArchivedReviewJobs();
    } catch (error) {
      alert(error.message || 'Kunde inte uppdatera alla arkiverade dokument.');
    }
  });
  const rerunAnalysisButton = document.createElement('button');
  rerunAnalysisButton.type = 'button';
  rerunAnalysisButton.textContent = 'Kör om analys';
  rerunAnalysisButton.title = 'Omanalyserar arkiverade dokument med aktuella regler. Dokumenten ligger kvar oförändrade i arkivet tills en uppdatering godkänns, men nya ändringar kan föreslås om reglerna nu ger ett annat resultat.';
  rerunAnalysisButton.disabled = sessionStatus === 'running';
  rerunAnalysisButton.addEventListener('click', async () => {
    try {
      await restartArchivingUpdateReview(false);
    } catch (error) {
      alert(error.message || 'Kunde inte köra om analysen för arkiverade dokument.');
    }
  });
  statusActions.append(openListButton, updateAllButton);
  actions.append(rerunAnalysisButton);
  if (developmentUiEnabled()) {
    const devButton = document.createElement('button');
    devButton.type = 'button';
    devButton.textContent = 'Dev: Höj arkiveringsversion';
    devButton.disabled = archivingReviewLoading;
    devButton.addEventListener('click', async () => {
      try {
        await bumpArchivingRulesVersionDev();
      } catch (error) {
        alert(error.message || 'Kunde inte höja arkiveringsversionen.');
      }
    });
    actions.appendChild(devButton);
  }
  archivingReviewActionsEl.appendChild(actions);

  const statusLine = document.createElement('div');
  statusLine.className = 'archiving-review-status-lines';
  if (sessionStatus === 'running') {
    statusLine.textContent = `Analyserar arkiverade dokument... ${analyzedCount} / ${totalCount} analyserade.`;
  } else if (totalCount === 0) {
    statusLine.textContent = 'Det finns inga arkiverade jobb att analysera just nu.';
  } else if ((summary.affected || 0) > 0) {
    statusLine.textContent = `${summary.affected} ${summary.affected === 1 ? 'arkiverat dokument behöver' : 'arkiverade dokument behöver'} granskas eller uppdateras.`;
  } else {
    statusLine.textContent = 'Inga arkiverade dokument påverkas just nu.';
  }
  archivingReviewStatusEl.replaceChildren(statusLine, statusActions);
  if (visibleJobs.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = sessionStatus === 'running'
      ? 'Inga påverkade arkiverade jobb hittade ännu.'
      : totalCount === 0
      ? 'Det finns inga arkiverade jobb att jämföra mot ännu.'
      : 'Inga arkiverade jobb behöver uppdateras just nu.';
    archivingReviewJobsEl.appendChild(empty);
    updateSettingsActionButtons();
    return;
  }

  visibleJobs.forEach((item) => {
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
      body.appendChild(buildArchivingReviewChangeList(changeItems));
    }
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'Öppna arkiverat jobb';
    button.addEventListener('click', () => {
      closeSettingsModal(true);
      setJobListMode('archived-review');
      applySelectedJobId(item.jobId);
      if (currentViewMode === 'review') {
        setViewMode('pdf');
      }
    });
    row.append(body, button);
    archivingReviewJobsEl.appendChild(row);
  });

  updateSettingsActionButtons();
}

function watchReprocessedJobIdsFromPayload(payload) {
  const reprocessedJobIds = Array.isArray(payload?.reprocessedJobs?.reprocessedJobIds)
    ? payload.reprocessedJobs.reprocessedJobIds.filter((jobId) => typeof jobId === 'string' && jobId !== '')
    : [];
  if (reprocessedJobIds.length < 1) {
    return;
  }
  const mode = typeof payload?.reprocessedJobs?.mode === 'string' && payload.reprocessedJobs.mode.trim() !== ''
    ? payload.reprocessedJobs.mode.trim()
    : 'post-ocr';
  const forceOcr = mode === 'full';
  reprocessedJobIds.forEach((jobId) => {
    applyOptimisticReprocess(jobId, mode, { forceOcr });
    reprocessWatchJobIds.add(jobId);
  });
  requestStateRefresh(0);
}

async function bumpArchivingRulesVersionDev() {
  const response = await fetch('/api/bump-archiving-rules-version.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: '{}'
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte höja arkiveringsversionen.');
  }

  applyArchivingRulesPayloadFromResponse(payload, { bumpLocalRevision: true, forceRender: true });
  await Promise.all([loadArchiveStructure(), loadLabels({ reload: true }), loadExtractionFields(), fetchState({ force: true, refreshArchiveStructure: true })]);
  watchReprocessedJobIdsFromPayload(payload);
}

async function restartArchivingUpdateReview(ignoreDismissed = false) {
  const response = await fetch('/api/restart-archiving-update-review.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      ignoreDismissed,
    })
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte starta om analysen.');
  }

  applyArchivingRulesStatePayload(payload.archivingRules, { forceRender: true });
  await fetchState({ force: true, refreshArchiveStructure: true });
}

async function reanalyzeAllDocuments() {
  const response = await fetch('/api/reanalyze-all-documents.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: '{}'
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string' ? payload.error : 'Kunde inte köra om analysen för alla dokument.');
  }

  applyArchivingRulesPayloadFromResponse(payload, { forceRender: true });
  watchReprocessedJobIdsFromPayload(payload);
  if (!Array.isArray(payload?.reprocessedJobs?.reprocessedJobIds) || payload.reprocessedJobs.reprocessedJobIds.length < 1) {
    await fetchState({ force: true, refreshArchiveStructure: true });
  }
}

async function updateAllArchivedReviewJobs() {
  const payload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || state.archivingRules?.updateReview);
  const jobs = activeArchivedReviewItems();
  const jobIds = jobs
    .map((item) => item && typeof item.jobId === 'string' ? item.jobId : '')
    .filter((jobId) => jobId !== '');
  if (jobIds.length < 1) {
    return;
  }
  archivedJobReviewPayload = null;
  const response = await fetch('/api/apply-archiving-update-batch.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ jobIds })
  });
  const result = await response.json().catch(() => null);
  if (!response.ok || !result || result.ok !== true) {
    throw new Error(result && typeof result.error === 'string'
      ? result.error
      : 'Kunde inte uppdatera alla arkiverade dokument.');
  }
  jobIds.forEach((jobId) => {
    archivedReviewDraftByJobId.delete(jobId);
    clearArchivedReviewEditorState(jobId);
  });
  await fetchState({ force: true, refreshArchiveStructure: true });
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

function ensureArchivedReviewListControlNode() {
  const node = ensureJobListNode('control:archived-review:dismissed', () => {
    const li = document.createElement('li');
    li.className = 'job-list-control-row';
    const label = document.createElement('label');
    label.className = 'job-list-toggle';
    const input = document.createElement('input');
    input.type = 'checkbox';
    input.addEventListener('change', () => {
      setShowDismissedArchivedReviewJobs(input.checked);
    });
    const text = document.createElement('span');
    text.textContent = 'Visa även avfärdade';
    label.append(input, text);
    li.appendChild(label);
    li._inputEl = input;
    return li;
  });
  if (node._inputEl instanceof HTMLInputElement) {
    node._inputEl.checked = showDismissedArchivedReviewJobs;
  }
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
  if (jobHasArchivingUpdateChange(job.id)) {
    li.classList.add('is-needs-review');
  }
  const reviewItem = currentJobListMode === 'archived-review'
    ? changedArchivedReviewItems().find((item) => item && item.jobId === job.id)
    : null;
  const dismissedForVersion = reviewItem && reviewItem.dismissedForVersion === true;
  if (job.id === selectedJobId) {
    li.classList.add('selected');
  }

  li.dataset.jobId = job.id;
  li._nameEl.textContent = job.originalFilename;

  const archivedLabel = dismissedForVersion
    ? `Avfärdad för aktuell version${job.filename ? ' • ' + job.filename : ''}`
    : jobHasArchivingUpdateChange(job.id)
    ? `Behöver uppdateras${job.filename ? ' • ' + job.filename : ''}`
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
  const isArchivedReviewMode = currentJobListMode === 'archived-review';
  const isAllMode = currentJobListMode === 'all';
  const isProcessingMode = currentJobListMode === 'processing';
  const isArchivedMode = currentJobListMode === 'archived';

  if (isArchivedReviewMode) {
    const controlNode = ensureArchivedReviewListControlNode();
    desiredNodes.push(controlNode);
    activeKeys.add('control:archived-review:dismissed');
  }

  if (
    ((isReadyMode || isAllMode) && displayedReadyJobs.length === 0 && displayedReviewJobs.length === 0 && safeProcessingJobs.length === 0 && displayedArchivedJobs.length === 0 && safeFailedJobs.length === 0)
    || (isArchivedReviewMode && displayedReviewJobs.length === 0)
    || (isProcessingMode && safeProcessingJobs.length === 0 && safeFailedJobs.length === 0)
    || (isArchivedMode && displayedArchivedJobs.length === 0)
  ) {
    const messageNode = ensureJobListMessageNode();
    messageNode.textContent = isAllMode
      ? 'Inga jobb ännu.'
      : isArchivedReviewMode
      ? 'Inga arkiverade dokument att granska just nu.'
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
      displayedReadyJobs.forEach((job) => {
        const key = `ready:${job.id}`;
        const li = ensureJobListItemNode(key);
        updateReadyJobListItem(li, job);
        desiredNodes.push(li);
        activeKeys.add(key);
      });
    } else if (isArchivedReviewMode) {
      if (displayedReviewJobs.length > 0) {
        const labelNode = ensureJobListSectionLabelNode('Arkiverade att granska', 'label:ready:review');
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
  if (currentJobListMode === 'archived-review') {
    return displayedArchivedReviewJobsForReadyMode();
  }
  if (currentJobListMode === 'archived') {
    return Array.isArray(state.archivedJobs) ? state.archivedJobs : [];
  }
  return displayedJobsForCurrentListMode();
}

function displayedJobsForCurrentListMode() {
  if (currentJobListMode === 'ready') {
    return buildDisplayedReadyJobs(state.processingJobs, state.readyJobs);
  }
  if (currentJobListMode === 'archived-review') {
    return displayedArchivedReviewJobsForReadyMode();
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

function nextVisibleJobIdAfterRemoval(jobId) {
  const visibleJobs = displayedJobsForCurrentListMode().filter((job) => job && typeof job.id === 'string' && job.id !== '');
  const currentIndex = visibleJobs.findIndex((job) => job.id === jobId);
  if (currentIndex < 0) {
    return '';
  }

  const nextJob = visibleJobs[currentIndex + 1] || visibleJobs[currentIndex - 1] || null;
  return nextJob && typeof nextJob.id === 'string' ? nextJob.id : '';
}

function findArchiveFolderById(folderId) {
  if (!folderId) {
    return null;
  }
  return (Array.isArray(state.archiveFolders) ? state.archiveFolders : []).find((folder) => folder && folder.id === folderId) || null;
}

function findDraftArchiveFolderById(folderId) {
  const normalizedId = typeof folderId === 'string' ? folderId.trim() : '';
  if (!normalizedId) {
    return null;
  }

  for (const folder of Array.isArray(archiveFoldersDraft) ? archiveFoldersDraft : []) {
    if (folder && typeof folder === 'object' && folder.id === normalizedId) {
      return folder;
    }
  }

  return null;
}

function findFilenameTemplateById(templateId) {
  const normalizedId = typeof templateId === 'string' ? templateId.trim() : '';
  if (!normalizedId) {
    return null;
  }
  for (const folder of Array.isArray(state.archiveFolders) ? state.archiveFolders : []) {
    const templates = Array.isArray(folder && folder.filenameTemplates) ? folder.filenameTemplates : [];
    for (let index = 0; index < templates.length; index += 1) {
      const template = templates[index];
      if (template && template.id === normalizedId) {
        return {
          ...template,
          folderId: folder && typeof folder.id === 'string' ? folder.id : '',
          templateIndex: index,
        };
      }
    }
  }
  return null;
}

function findDraftFilenameTemplateById(templateId) {
  const normalizedId = typeof templateId === 'string' ? templateId.trim() : '';
  if (!normalizedId) {
    return null;
  }
  for (const folder of Array.isArray(archiveFoldersDraft) ? archiveFoldersDraft : []) {
    const templates = Array.isArray(folder && folder.filenameTemplates) ? folder.filenameTemplates : [];
    for (let index = 0; index < templates.length; index += 1) {
      const template = templates[index];
      if (template && template.id === normalizedId) {
        return {
          ...template,
          folderId: folder && typeof folder.id === 'string' ? folder.id : '',
          templateIndex: index,
        };
      }
    }
  }
  return null;
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

function proposedArchivingResultForJob(job) {
  if (!job) {
    return null;
  }
  const reviewPayload = selectedArchivedReviewPayload(job);
  if (reviewPayload && reviewPayload.currentAutoResult && typeof reviewPayload.currentAutoResult === 'object') {
    return reviewPayload.currentAutoResult;
  }
  const autoResult = autoArchivingResultForJob(job);
  if (!autoResult) {
    return null;
  }
  const currentLabelIds = normalizeSelectedLabelIds(effectiveSelectedLabelIds(job));
  const proposedFolder = selectArchiveFolderByLabelIds(currentLabelIds);
  const proposedFolderId = proposedFolder && typeof proposedFolder.id === 'string' ? proposedFolder.id.trim() : '';
  const proposedTemplate = proposedFolder
    ? selectArchiveFolderFilenameTemplateByLabelIds(proposedFolder, currentLabelIds)
    : null;
  const proposedTemplateId = proposedTemplate && proposedTemplate.template && typeof proposedTemplate.template.id === 'string'
    ? proposedTemplate.template.id.trim()
    : '';
  return {
    ...autoResult,
    folderId: proposedFolderId !== '' ? proposedFolderId : null,
    filenameTemplateId: proposedTemplateId !== '' ? proposedTemplateId : null,
    filename: generateFilenameForJob(job, {
      folderId: proposedFolderId,
      labelIds: currentLabelIds,
      filenameTemplateId: proposedTemplateId,
    }),
  };
}

function selectArchiveFolderByLabelIds(labelIds) {
  const normalizedLabelIds = normalizeSelectedLabelIds(labelIds);
  const folders = Array.isArray(state.archiveFolders) ? state.archiveFolders : [];
  const candidates = [];

  folders.forEach((folder, folderIndex) => {
    if (!folder || typeof folder !== 'object') {
      return;
    }
    const folderId = typeof folder.id === 'string' ? folder.id.trim() : '';
    if (!folderId) {
      return;
    }

    let bestMatchedCount = 0;
    let bestConditionCount = 0;
    const templates = Array.isArray(folder.filenameTemplates) ? folder.filenameTemplates : [];
    templates.forEach((template) => {
      if (!template || typeof template !== 'object') {
        return;
      }
      const templateLabelIds = normalizeLabelIdList(template.labelIds);
      if (templateLabelIds.length === 0) {
        return;
      }
      const matchedCount = templateLabelIds.filter((labelId) => normalizedLabelIds.includes(labelId)).length;
      if (matchedCount < templateLabelIds.length) {
        return;
      }
      if (matchedCount > bestMatchedCount || (matchedCount === bestMatchedCount && templateLabelIds.length > bestConditionCount)) {
        bestMatchedCount = matchedCount;
        bestConditionCount = templateLabelIds.length;
      }
    });

    if (bestMatchedCount < 1) {
      return;
    }

    candidates.push({
      folder,
      folderIndex,
      matchedCount: bestMatchedCount,
      conditionCount: bestConditionCount,
    });
  });

  if (candidates.length < 1) {
    return null;
  }

  candidates.sort((left, right) => {
    const matchedCompare = right.matchedCount - left.matchedCount;
    if (matchedCompare !== 0) {
      return matchedCompare;
    }
    const conditionCompare = right.conditionCount - left.conditionCount;
    if (conditionCompare !== 0) {
      return conditionCompare;
    }
    return left.folderIndex - right.folderIndex;
  });

  return candidates[0] ? candidates[0].folder : null;
}

function selectArchiveFolderFilenameTemplateByLabelIds(folder, labelIds) {
  if (!folder || typeof folder !== 'object') {
    return null;
  }
  const templates = Array.isArray(folder.filenameTemplates) ? folder.filenameTemplates : [];
  if (templates.length < 1) {
    return null;
  }

  const normalizedLabelIds = normalizeSelectedLabelIds(labelIds);
  const candidates = [];
  templates.forEach((template, templateIndex) => {
    if (!template || typeof template !== 'object') {
      return;
    }
    const templateLabelIds = normalizeLabelIdList(template.labelIds);
    const matchedCount = templateLabelIds.filter((labelId) => normalizedLabelIds.includes(labelId)).length;
    if (templateLabelIds.length > 0 && matchedCount < templateLabelIds.length) {
      return;
    }
    candidates.push({
      template,
      templateIndex,
      matchedCount,
      conditionCount: templateLabelIds.length,
    });
  });

  if (candidates.length < 1) {
    const firstTemplate = templates[0] || null;
    return firstTemplate && typeof firstTemplate === 'object' ? firstTemplate : null;
  }

  candidates.sort((left, right) => {
    const matchedCompare = right.matchedCount - left.matchedCount;
    if (matchedCompare !== 0) {
      return matchedCompare;
    }
    const conditionCompare = right.conditionCount - left.conditionCount;
    if (conditionCompare !== 0) {
      return conditionCompare;
    }
    return left.templateIndex - right.templateIndex;
  });

  return candidates[0] ? candidates[0].template : null;
}

function normalizeAutoArchivingResultScalarValue(value) {
  if (typeof value === 'string') {
    return value.trim();
  }
  if (typeof value === 'number' && Number.isFinite(value)) {
    return String(value).trim();
  }
  return '';
}

function syncUnarchivedJobAutoProposalChange(currentJob, nextJob) {
  if (!currentJob || !nextJob || currentJob.archived === true || nextJob.archived === true) {
    return;
  }

  const previousProposed = proposedArchivingResultForJob(currentJob);
  const nextProposed = proposedArchivingResultForJob(nextJob);
  if (!previousProposed || !nextProposed) {
    return;
  }

  const jobId = typeof currentJob.id === 'string' ? currentJob.id : '';
  if (!jobId) {
    return;
  }

  const currentClientId = effectiveClientDirName(currentJob);
  const previousClientId = normalizeAutoArchivingResultScalarValue(previousProposed.clientId);
  const nextClientId = normalizeAutoArchivingResultScalarValue(nextProposed.clientId);
  if (currentClientId === previousClientId) {
    if (selectedClientByJobId.has(jobId)) {
      if (nextClientId !== '') {
        selectedClientByJobId.set(jobId, nextClientId);
      } else {
        selectedClientByJobId.delete(jobId);
      }
    } else {
      nextJob.selectedClientDirName = nextClientId !== '' ? nextClientId : null;
    }
  }

  const currentSenderId = effectiveSenderId(currentJob);
  const previousSenderId = normalizeAutoArchivingResultScalarValue(previousProposed.senderId);
  const nextSenderId = normalizeAutoArchivingResultScalarValue(nextProposed.senderId);
  if (currentSenderId === previousSenderId) {
    if (selectedSenderByJobId.has(jobId)) {
      if (nextSenderId !== '') {
        selectedSenderByJobId.set(jobId, nextSenderId);
      } else {
        selectedSenderByJobId.delete(jobId);
      }
    } else {
      nextJob.selectedSenderId = nextSenderId !== '' ? Number.parseInt(nextSenderId, 10) || null : null;
    }
  }

  const currentFolderId = effectiveFolderId(currentJob);
  const previousFolderId = normalizeAutoArchivingResultScalarValue(previousProposed.folderId);
  const nextFolderId = normalizeAutoArchivingResultScalarValue(nextProposed.folderId);
  if (currentFolderId === previousFolderId) {
    if (selectedFolderByJobId.has(jobId)) {
      if (nextFolderId !== '') {
        selectedFolderByJobId.set(jobId, nextFolderId);
      } else {
        selectedFolderByJobId.delete(jobId);
      }
    } else {
      nextJob.selectedFolderId = nextFolderId !== '' ? nextFolderId : null;
    }
  }

  const currentFilename = String(displayedFilenameForJob(currentJob) || '').trim();
  const previousFilename = normalizeAutoArchivingResultScalarValue(previousProposed.filename);
  const nextFilename = normalizeAutoArchivingResultScalarValue(nextProposed.filename);
  if (currentFilename === previousFilename) {
    if (filenameByJobId.has(jobId)) {
      if (nextFilename !== '') {
        filenameByJobId.set(jobId, nextFilename);
      } else {
        filenameByJobId.delete(jobId);
      }
    } else {
      nextJob.filename = nextFilename !== '' ? nextFilename : null;
    }
  }

  const currentLabelIds = normalizeComparableLabelIds(effectiveSelectedLabelIds(currentJob));
  const previousLabelIds = normalizeComparableLabelIds(previousProposed.labels);
  const nextLabelIds = normalizeComparableLabelIds(nextProposed.labels);
  if (arrayValuesEqual(currentLabelIds, previousLabelIds)) {
    if (selectedLabelIdsByJobId.has(jobId)) {
      if (nextLabelIds.length > 0) {
        selectedLabelIdsByJobId.set(jobId, [...nextLabelIds]);
      } else {
        selectedLabelIdsByJobId.delete(jobId);
      }
    } else {
      nextJob.selectedLabelIds = nextLabelIds.length > 0 ? [...nextLabelIds] : null;
    }
  }
}

function resetActionsModeActive() {
  return currentJobListMode === 'ready' || currentJobListMode === 'archived-review';
}

function clientDisplayNameByDirName(dirName) {
  const normalized = typeof dirName === 'string' ? dirName.trim() : '';
  if (!normalized) {
    return '';
  }
  const match = Array.isArray(state.clients)
    ? state.clients.find((client) => client && typeof client.dirName === 'string' && client.dirName.trim() === normalized)
    : null;
  return match && typeof match.dirName === 'string' ? match.dirName.trim() : normalized;
}

function normalizeComparableLabelIds(value) {
  const list = Array.isArray(value) ? value : [];
  return Array.from(new Set(
    list
      .map((item) => String(item || '').trim())
      .filter((item) => item !== '')
  )).sort();
}

function arrayValuesEqual(left, right) {
  const a = Array.isArray(left) ? left : [];
  const b = Array.isArray(right) ? right : [];
  if (a.length !== b.length) {
    return false;
  }
  for (let index = 0; index < a.length; index += 1) {
    if (a[index] !== b[index]) {
      return false;
    }
  }
  return true;
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
  if (selectedClientByJobId.has(job.id)) {
    const localValue = selectedClientByJobId.get(job.id);
    if (isKnownClient(localValue)) {
      return String(localValue).trim();
    }
    return '';
  }
  if (typeof job.selectedClientDirName === 'string' && isKnownClient(job.selectedClientDirName)) {
    return job.selectedClientDirName.trim();
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.clientId === 'string' && isKnownClient(autoResult.clientId)) {
    return autoResult.clientId.trim();
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
  if (selectedSenderByJobId.has(job.id)) {
    const localValue = selectedSenderByJobId.get(job.id);
    if (isKnownSender(localValue)) {
      return String(localValue).trim();
    }
    return '';
  }
  if (Number.isInteger(job.selectedSenderId) && job.selectedSenderId > 0 && isKnownSender(job.selectedSenderId)) {
    return String(job.selectedSenderId);
  }

  const senderRows = senderLinkedRowsForJob(job);
  const firstSummarySender = senderRows.find((row) => {
    return row
      && Number.isInteger(Number(row.senderId))
      && Number(row.senderId) > 0
      && isKnownSender(row.senderId);
  }) || null;
  if (firstSummarySender) {
    return String(firstSummarySender.senderId);
  }
  return '';
}

function senderSummaryForJob(job) {
  return job && job.senderSummary && typeof job.senderSummary === 'object'
    ? job.senderSummary
    : null;
}

function senderUnknownObservationRowsForJob(job) {
  const summary = senderSummaryForJob(job);
  return summary && Array.isArray(summary.unknownObservations)
    ? summary.unknownObservations.filter((row) => row && typeof row === 'object')
    : [];
}

function senderLinkedRowsForJob(job) {
  const summary = senderSummaryForJob(job);
  return summary && Array.isArray(summary.senders)
    ? summary.senders.filter((row) => row && typeof row === 'object')
    : [];
}

function clientMatchRowsForJob(job) {
  const analysis = job && job.analysis && typeof job.analysis === 'object'
    ? job.analysis
    : null;
  return analysis && Array.isArray(analysis.clientMatches)
    ? analysis.clientMatches.filter((row) => row && typeof row === 'object')
    : [];
}

function selectedJobSenderObservationSpinnerPaused(observation) {
  if (!observation || typeof observation !== 'object') {
    return false;
  }
  const type = typeof observation.type === 'string' ? observation.type.trim().toLowerCase() : '';
  if (type === 'organization_number') {
    return currentSenderOrganizationLookupRemainingCount() > 0
      && chromeExtensionRuntime.hasAnyAllabolagTab === false;
  }
  return (
    (chromeExtensionRuntime.loginRequired === true || chromeExtensionRuntime.profileSelectionRequired === true)
    && chromeExtensionRuntime.missingPayeeCount > 0
  );
}

function setSelectedJobSenderSectionVisibility(sectionEl, visible) {
  if (!(sectionEl instanceof HTMLElement)) {
    return;
  }
  if (visible) {
    sectionEl.removeAttribute('hidden');
    return;
  }
  sectionEl.setAttribute('hidden', '');
}

function clearSelectedJobSenderSectionMessage(message) {
  if (!(selectedJobSenderLinkedInfoEl instanceof HTMLElement)) {
    return;
  }
  setSelectedJobSenderSectionVisibility(selectedJobSendersSectionEl, true);
  setSelectedJobSenderSectionVisibility(selectedJobSenderUnknownSectionEl, false);
  selectedJobSenderLinkedInfoEl.textContent = message;
  if (selectedJobSenderUnknownInfoEl instanceof HTMLElement) {
    selectedJobSenderUnknownInfoEl.replaceChildren();
  }
}

function clearSelectedJobClientSectionMessage(message) {
  if (!(selectedJobClientLinkedInfoEl instanceof HTMLElement)) {
    return;
  }
  if (selectedJobClientsSectionEl instanceof HTMLElement) {
    selectedJobClientsSectionEl.removeAttribute('hidden');
  }
  selectedJobClientLinkedInfoEl.textContent = message;
}

function applySelectedClientValue(value) {
  if (!selectedJobId) {
    return;
  }

  const selectedJob = findJobById(selectedJobId);
  const previousCurrentFolder = effectiveFolderId(selectedJob);
  const previousCurrentFilename = String(displayedFilenameForJob(selectedJob) || '').trim();
  const previousProposed = proposedArchivingResultForJob(selectedJob);
  if (!value) {
    selectedClientByJobId.delete(selectedJobId);
  } else {
    selectedClientByJobId.set(selectedJobId, value);
  }

  if (clientSelectEl instanceof HTMLSelectElement && clientSelectEl.value !== value) {
    clientSelectEl.value = value;
  }

  const currentJob = findJobById(selectedJobId);
  updateArchivedReviewDraftFromSidebar(currentJob);
  const nextProposed = proposedArchivingResultForJob(currentJob);
  syncCurrentActionValuesFromProposalChange(
    selectedJobId,
    previousCurrentFolder,
    previousCurrentFilename,
    previousProposed,
    nextProposed,
    { syncFolder: false }
  );
  renderSelectedJobClientSection(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
  if (archivedReviewModeActiveForJob(selectedJob)) {
    return;
  }
  saveSelectedJobFields(selectedJobId, { selectedClientDirName: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    renderSelectedJobClientSection(findJobById(selectedJobId));
    alert(error.message || 'Kunde inte spara huvudman.');
  });
}

function applySelectedSenderValue(value) {
  if (!selectedJobId) {
    return;
  }

  const selectedJob = findJobById(selectedJobId);
  const previousCurrentFolder = effectiveFolderId(selectedJob);
  const previousCurrentFilename = String(displayedFilenameForJob(selectedJob) || '').trim();
  const previousProposed = proposedArchivingResultForJob(selectedJob);
  if (!value) {
    selectedSenderByJobId.delete(selectedJobId);
  } else {
    selectedSenderByJobId.set(selectedJobId, value);
  }

  if (senderSelectEl instanceof HTMLSelectElement && senderSelectEl.value !== value) {
    senderSelectEl.value = value;
  }

  const currentJob = findJobById(selectedJobId);
  updateArchivedReviewDraftFromSidebar(currentJob);
  const nextProposed = proposedArchivingResultForJob(currentJob);
  syncCurrentActionValuesFromProposalChange(
    selectedJobId,
    previousCurrentFolder,
    previousCurrentFilename,
    previousProposed,
    nextProposed,
    { syncFolder: false }
  );
  renderSelectedJobSenderSection(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
  if (archivedReviewModeActiveForJob(selectedJob)) {
    return;
  }
  saveSelectedJobFields(selectedJobId, { selectedSenderId: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    renderSelectedJobSenderSection(findJobById(selectedJobId));
    alert(error.message || 'Kunde inte spara avsändare.');
  });
}

function applySelectedFolderValue(value) {
  if (!selectedJobId) {
    return;
  }

  const selectedJob = findJobById(selectedJobId);
  const previousCurrentFolder = effectiveFolderId(selectedJob);
  const previousCurrentFilename = String(displayedFilenameForJob(selectedJob) || '').trim();
  const previousProposed = proposedArchivingResultForJob(selectedJob);
  if (!value) {
    selectedFolderByJobId.delete(selectedJobId);
  } else {
    selectedFolderByJobId.set(selectedJobId, value);
  }

  if (folderSelectEl instanceof HTMLSelectElement && folderSelectEl.value !== value) {
    folderSelectEl.value = value;
  }

  const currentJob = findJobById(selectedJobId);
  updateArchivedReviewDraftFromSidebar(currentJob);
  const nextProposed = proposedArchivingResultForJob(currentJob);
  syncCurrentActionValuesFromProposalChange(
    selectedJobId,
    previousCurrentFolder,
    previousCurrentFilename,
    previousProposed,
    nextProposed,
    { syncFolder: false }
  );
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
  if (archivedReviewModeActiveForJob(selectedJob)) {
    return;
  }
  saveSelectedJobFields(selectedJobId, { selectedFolderId: value || null }).catch((error) => {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte spara mapp.');
  });
}

async function resetSelectedJobFieldToProposed(fieldKey) {
  const job = findJobById(selectedJobId);
  const proposed = proposedArchivingResultForJob(job);
  if (!job || !selectedJobArchivingEditable(job) || !proposed || typeof proposed !== 'object') {
    return;
  }
  const archivedReviewMode = archivedReviewModeActiveForJob(job);

  if (fieldKey === 'client') {
    const nextValue = typeof proposed.clientId === 'string' ? proposed.clientId.trim() : '';
    if (!archivedReviewMode) {
      applySelectedClientValue(nextValue);
      return;
    }
    if (!nextValue) {
      selectedClientByJobId.delete(job.id);
    } else {
      selectedClientByJobId.set(job.id, nextValue);
    }
    setClientForJob(findJobById(job.id));
    const currentJob = findJobById(job.id);
    updateArchivedReviewDraftFromSidebar(currentJob);
    renderSelectedJobClientSection(currentJob);
    syncFilenameField(currentJob);
    updateArchiveAction(currentJob);
    updateSelectedJobResetActions(currentJob);
    return;
  }
  if (fieldKey === 'sender') {
    const nextValue = proposed.senderId ? String(proposed.senderId).trim() : '';
    if (!archivedReviewMode) {
      applySelectedSenderValue(nextValue);
      return;
    }
    if (!nextValue) {
      selectedSenderByJobId.delete(job.id);
    } else {
      selectedSenderByJobId.set(job.id, nextValue);
    }
    setSenderForJob(findJobById(job.id));
    const currentJob = findJobById(job.id);
    updateArchivedReviewDraftFromSidebar(currentJob);
    renderSelectedJobSenderSection(currentJob);
    syncFilenameField(currentJob);
    updateArchiveAction(currentJob);
    updateSelectedJobResetActions(currentJob);
    return;
  }
  if (fieldKey === 'folder') {
    const nextValue = typeof proposed.folderId === 'string' ? proposed.folderId.trim() : '';
    if (!archivedReviewMode) {
      applySelectedFolderValue(nextValue);
      return;
    }
    if (!nextValue) {
      selectedFolderByJobId.delete(job.id);
    } else {
      selectedFolderByJobId.set(job.id, nextValue);
    }
    setFolderForJob(findJobById(job.id));
    const currentJob = findJobById(job.id);
    updateArchivedReviewDraftFromSidebar(currentJob);
    syncFilenameField(currentJob);
    updateArchiveAction(currentJob);
    updateSelectedJobResetActions(currentJob);
    return;
  }
  if (fieldKey === 'filename') {
    const nextValue = proposedFilenameForJob(job, proposed);
    if (!archivedReviewMode) {
      applySelectedFilenameValue(nextValue);
      return;
    }
    if (!nextValue) {
      filenameByJobId.delete(job.id);
    } else {
      filenameByJobId.set(job.id, nextValue);
    }
    const currentJob = findJobById(job.id);
    syncFilenameField(currentJob);
    updateArchivedReviewDraftFromSidebar(currentJob);
    updateArchiveAction(currentJob);
    updateSelectedJobResetActions(currentJob);
    return;
  }
  if (fieldKey === 'labels') {
    if (!archivedReviewMode) {
      const normalizedLabels = normalizeComparableLabelIds(proposed.labels);
      const previousCurrentFolder = effectiveFolderId(job);
      const previousCurrentFilename = String(displayedFilenameForJob(job) || '').trim();
      const previousProposed = proposedArchivingResultForJob(job);
      const hadLocalLabels = selectedLabelIdsByJobId.has(job.id);
      const previousLocalLabels = hadLocalLabels ? normalizeComparableLabelIds(selectedLabelIdsByJobId.get(job.id)) : null;
      const hadLocalFields = selectedExtractionFieldValuesByJobId.has(job.id);
      const previousLocalFields = hadLocalFields ? normalizeSelectedExtractionFieldValues(selectedExtractionFieldValuesByJobId.get(job.id)) : null;

      selectedLabelIdsByJobId.set(job.id, normalizedLabels);
      selectedExtractionFieldValuesByJobId.delete(job.id);

      const currentJobAfterReset = findJobById(job.id);
      const nextProposed = proposedArchivingResultForJob(currentJobAfterReset);
      syncCurrentActionValuesFromProposalChange(
        job.id,
        previousCurrentFolder,
        previousCurrentFilename,
        previousProposed,
        nextProposed,
        { syncFolder: true }
      );

      const currentJob = findJobById(job.id);
      updateArchivedReviewDraftFromSidebar(currentJob);
      setLabelsForJob(currentJob);
      syncFilenameField(currentJob);
      updateArchiveAction(currentJob);
      updateSelectedJobResetActions(currentJob);
      refreshLoadedMatchesView();

      saveSelectedJobFields(job.id, {
        selectedLabelIds: normalizedLabels,
        selectedExtractionFieldValues: null,
      }).catch((error) => {
        if (hadLocalLabels) {
          selectedLabelIdsByJobId.set(job.id, previousLocalLabels);
        } else {
          selectedLabelIdsByJobId.delete(job.id);
        }
        if (hadLocalFields) {
          selectedExtractionFieldValuesByJobId.set(job.id, previousLocalFields);
        } else {
          selectedExtractionFieldValuesByJobId.delete(job.id);
        }
        const rollbackJob = findJobById(job.id);
        const rollbackCurrentFolder = effectiveFolderId(rollbackJob);
        const rollbackCurrentFilename = String(displayedFilenameForJob(rollbackJob) || '').trim();
        syncCurrentActionValuesFromProposalChange(
          job.id,
          rollbackCurrentFolder,
          rollbackCurrentFilename,
          nextProposed,
          previousProposed,
          { syncFolder: true }
        );
        updateArchivedReviewDraftFromSidebar(rollbackJob);
        setLabelsForJob(rollbackJob);
        syncFilenameField(rollbackJob);
        updateArchiveAction(rollbackJob);
        updateSelectedJobResetActions(rollbackJob);
        refreshLoadedMatchesView();
        alert(error.message || 'Kunde inte återställa etiketter och datafält.');
      });
      return;
    }
    selectedLabelIdsByJobId.set(job.id, normalizeComparableLabelIds(proposed.labels));
    selectedExtractionFieldValuesByJobId.delete(job.id);
    const currentJob = findJobById(job.id);
    updateArchivedReviewDraftFromSidebar(currentJob);
    setLabelsForJob(currentJob);
    syncFilenameField(currentJob);
    updateArchiveAction(currentJob);
    updateSelectedJobResetActions(currentJob);
    refreshLoadedMatchesView();
  }
}

function renderSelectedJobClientSection(job) {
  if (!(selectedJobClientLinkedInfoEl instanceof HTMLElement)) {
    return;
  }

  if (!job) {
    clearSelectedJobClientSectionMessage('Ingen huvudmansinformation tillgänglig ännu.');
    return;
  }

  const clients = clientMatchRowsForJob(job);
  if (clients.length < 1) {
    clearSelectedJobClientSectionMessage('Ingen huvudmansinformation tillgänglig ännu.');
    return;
  }

  const clientList = document.createElement('ul');
  clientList.className = 'selected-job-sender-linked-list';
  const resolvedSelectedClientDirName = effectiveClientDirName(job);
  clients.forEach((clientRow, index) => {
    const dirName = typeof clientRow.dirName === 'string' ? clientRow.dirName.trim() : '';
    const displayName = typeof clientRow.displayName === 'string' && clientRow.displayName.trim() !== ''
      ? clientRow.displayName.trim()
      : dirName;
    const matchedName = typeof clientRow.matchedName === 'string' ? clientRow.matchedName.trim() : '';
    const matchedPin = typeof clientRow.matchedPersonalIdentityNumber === 'string'
      ? clientRow.matchedPersonalIdentityNumber.trim()
      : '';
    if (displayName === '') {
      return;
    }

    const item = document.createElement('li');
    item.className = 'selected-job-sender-linked-item';

    const clientChoiceId = `selected-job-client-choice-${index}`;
    const clientChoiceWrap = document.createElement('label');
    clientChoiceWrap.className = 'selected-job-sender-radio-wrap';
    clientChoiceWrap.htmlFor = clientChoiceId;

    const clientChoice = document.createElement('input');
    clientChoice.type = 'radio';
    clientChoice.name = 'selected-job-client-choice';
    clientChoice.id = clientChoiceId;
    clientChoice.className = 'selected-job-sender-radio';
    clientChoice.value = dirName;
    clientChoice.checked = dirName !== '' && dirName === resolvedSelectedClientDirName;
    clientChoice.disabled = !selectedJobArchivingEditable(job) || dirName === '';
    clientChoice.addEventListener('change', () => {
      if (!clientChoice.checked || dirName === '') {
        return;
      }
      applySelectedClientValue(dirName);
    });
    clientChoiceWrap.appendChild(clientChoice);

    const body = document.createElement('div');
    body.className = 'selected-job-sender-linked-body';

    const header = document.createElement('div');
    header.className = 'selected-job-sender-linked-header is-found';

    const headerMain = document.createElement('div');
    headerMain.className = 'selected-job-sender-linked-header-main';

    const headerName = document.createElement('div');
    headerName.className = 'selected-job-sender-linked-title';
    headerName.textContent = displayName;
    headerMain.appendChild(headerName);
    header.appendChild(headerMain);

    const openButton = document.createElement('button');
    openButton.type = 'button';
    openButton.className = 'selected-job-sender-open';
    openButton.textContent = '↗';
    openButton.title = 'Öppna i huvudmansregister';
    openButton.setAttribute('aria-label', 'Öppna i huvudmansregister');
    openButton.addEventListener('click', () => {
      openClientInRegister(dirName);
    });
    header.appendChild(openButton);

    const components = document.createElement('div');
    components.className = 'selected-job-sender-components';

    const appendComponentRow = (label, valueText) => {
      if (typeof valueText !== 'string' || valueText.trim() === '') {
        return;
      }
      const row = document.createElement('div');
      row.className = 'selected-job-sender-component-row is-found';

      const marker = document.createElement('span');
      marker.className = 'selected-job-sender-component-marker';
      marker.textContent = '✓';

      const text = document.createElement('span');
      text.className = 'selected-job-sender-component-text';

      const key = document.createElement('span');
      key.className = 'selected-job-sender-component-key';
      key.textContent = label;

      const value = document.createElement('span');
      value.className = 'selected-job-sender-component-value';
      value.textContent = ` ${valueText.trim()}`;

      text.append(key, value);
      row.append(text, marker);
      components.appendChild(row);
    };

    appendComponentRow('Namn', matchedName);
    appendComponentRow('Personnummer', matchedPin);

    body.append(header, components);
    item.append(clientChoiceWrap, body);
    clientList.appendChild(item);
  });

  if (clientList.childElementCount < 1) {
    clearSelectedJobClientSectionMessage('Ingen huvudmansinformation tillgänglig ännu.');
    return;
  }

  selectedJobClientLinkedInfoEl.replaceChildren(clientList);
  if (selectedJobClientsSectionEl instanceof HTMLElement) {
    selectedJobClientsSectionEl.removeAttribute('hidden');
  }
}

function renderSelectedJobSenderSection(job) {
  if (!(selectedJobSenderLinkedInfoEl instanceof HTMLElement) || !(selectedJobSenderUnknownInfoEl instanceof HTMLElement)) {
    return;
  }

  if (!job) {
    clearSelectedJobSenderSectionMessage('Ingen avsändarinformation tillgänglig ännu.');
    return;
  }

  const observations = senderUnknownObservationRowsForJob(job);
  const senders = senderLinkedRowsForJob(job);
  if (observations.length < 1 && senders.length < 1) {
    clearSelectedJobSenderSectionMessage('Ingen avsändarinformation tillgänglig ännu.');
    return;
  }

  if (observations.length > 0) {
    const unknownList = document.createElement('ul');
    unknownList.className = 'selected-job-sender-unknown-list';
    observations.forEach((observation) => {
      const item = document.createElement('li');
      item.className = 'selected-job-sender-unknown-item';

      const text = document.createElement('span');
      text.className = 'selected-job-sender-unknown-text';
      const itemLabel = typeof observation.itemLabel === 'string' ? observation.itemLabel.trim() : '';
      const itemValue = typeof observation.itemValue === 'string' ? observation.itemValue.trim() : '';
      text.textContent = `${itemLabel} ${itemValue}`.trim();
      item.appendChild(text);

      const status = typeof observation.status === 'string' ? observation.status.trim() : 'pending';
      if (status === 'pending') {
        const spinner = document.createElement('span');
        spinner.className = 'spinner selected-job-sender-observation-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        spinner.classList.toggle('is-paused', selectedJobSenderObservationSpinnerPaused(observation));
        item.appendChild(spinner);
      }

      unknownList.appendChild(item);
    });
    selectedJobSenderUnknownInfoEl.replaceChildren(unknownList);
    setSelectedJobSenderSectionVisibility(selectedJobSenderUnknownSectionEl, true);
  } else {
    selectedJobSenderUnknownInfoEl.replaceChildren();
    setSelectedJobSenderSectionVisibility(selectedJobSenderUnknownSectionEl, false);
  }

  if (senders.length > 0) {
    const senderList = document.createElement('ul');
    senderList.className = 'selected-job-sender-linked-list';
    const resolvedSelectedSenderId = effectiveSenderId(job);
    senders.forEach((senderRow) => {
      const item = document.createElement('li');
      item.className = 'selected-job-sender-linked-item';

      const senderChoiceId = `selected-job-sender-choice-${String(senderRow.senderId || '') || 'none'}`;
      const senderChoiceWrap = document.createElement('label');
      senderChoiceWrap.className = 'selected-job-sender-radio-wrap';
      senderChoiceWrap.htmlFor = senderChoiceId;

      const senderChoice = document.createElement('input');
      senderChoice.type = 'radio';
      senderChoice.name = 'selected-job-sender-choice';
      senderChoice.id = senderChoiceId;
      senderChoice.className = 'selected-job-sender-radio';
      senderChoice.value = String(senderRow.senderId || '');
      senderChoice.checked = senderChoice.value !== '' && senderChoice.value === resolvedSelectedSenderId;
      senderChoice.disabled = !job || job.status !== 'ready' || job.archived === true;
      senderChoice.addEventListener('change', () => {
        if (!senderChoice.checked) {
          return;
        }
        applySelectedSenderValue(senderChoice.value);
      });
      senderChoiceWrap.appendChild(senderChoice);

      const body = document.createElement('div');
      body.className = 'selected-job-sender-linked-body';

      const header = document.createElement('div');
      const headerFound = senderRow && senderRow.headerFound === true;
      header.className = `selected-job-sender-linked-header ${headerFound ? 'is-found' : 'is-missing'}`;

      const headerMain = document.createElement('div');
      headerMain.className = 'selected-job-sender-linked-header-main';

      const headerName = document.createElement('div');
      headerName.className = 'selected-job-sender-linked-title';
      headerName.textContent = typeof senderRow.name === 'string' ? senderRow.name : '';
      headerMain.appendChild(headerName);
      header.appendChild(headerMain);

      const openButton = document.createElement('button');
      openButton.type = 'button';
      openButton.className = 'selected-job-sender-open';
      openButton.textContent = '↗';
      openButton.title = 'Öppna i avsändarregister';
      openButton.setAttribute('aria-label', 'Öppna i avsändarregister');
      openButton.addEventListener('click', () => {
        openSenderInRegister(Number.parseInt(String(senderRow.senderId || 0), 10) || 0);
      });
      header.appendChild(openButton);

      const components = document.createElement('div');
      components.className = 'selected-job-sender-components';

      const appendComponentRow = (label, valueText, found) => {
        const row = document.createElement('div');
        row.className = `selected-job-sender-component-row ${found ? 'is-found' : 'is-missing'}`;

        const marker = document.createElement('span');
        marker.className = 'selected-job-sender-component-marker';
        marker.textContent = found ? '✓' : '';

        const text = document.createElement('span');
        text.className = 'selected-job-sender-component-text';

        const key = document.createElement('span');
        key.className = 'selected-job-sender-component-key';
        key.textContent = label;

        const value = document.createElement('span');
        value.className = 'selected-job-sender-component-value';
        value.textContent = valueText !== '' ? ` ${valueText}` : '';

        text.append(key, value);
        row.append(text, marker);
        components.appendChild(row);
      };

      const nameComponents = Array.isArray(senderRow.nameComponents) ? senderRow.nameComponents : [];
      nameComponents.forEach((nameComponent) => {
        if (!nameComponent || typeof nameComponent !== 'object') {
          return;
        }
        const label = typeof nameComponent.label === 'string' && nameComponent.label.trim() !== ''
          ? nameComponent.label.trim()
          : 'Namn';
        const valueText = typeof nameComponent.value === 'string' ? nameComponent.value.trim() : '';
        if (valueText === '') {
          return;
        }
        appendComponentRow(label, valueText, nameComponent.found === true);
      });

      if (senderRow.organizationNumber && typeof senderRow.organizationNumber === 'object') {
        appendComponentRow('Org.nr', String(senderRow.organizationNumber.value || '').trim(), senderRow.organizationNumber.found === true);
      }

      const paymentParts = Array.isArray(senderRow.paymentNumbers) ? senderRow.paymentNumbers : [];
      paymentParts.forEach((payment) => {
        const paymentLabel = payment && payment.label ? String(payment.label).trim() : '';
        const paymentValue = payment && payment.value ? String(payment.value).trim() : '';
        if (paymentLabel === '' && paymentValue === '') {
          return;
        }
        appendComponentRow(paymentLabel, paymentValue, payment && payment.found === true);
      });

      body.append(header, components);
      item.append(senderChoiceWrap, body);
      senderList.appendChild(item);
    });
    selectedJobSenderLinkedInfoEl.replaceChildren(senderList);
    setSelectedJobSenderSectionVisibility(selectedJobSendersSectionEl, true);
  } else {
    if (observations.length > 0) {
      selectedJobSenderLinkedInfoEl.replaceChildren();
      setSelectedJobSenderSectionVisibility(selectedJobSendersSectionEl, false);
    } else {
      const emptySenders = document.createElement('div');
      emptySenders.className = 'selected-job-sender-empty';
      emptySenders.textContent = 'Inga avsändare kopplade ännu.';
      selectedJobSenderLinkedInfoEl.replaceChildren(emptySenders);
      setSelectedJobSenderSectionVisibility(selectedJobSendersSectionEl, true);
    }
  }
}

function effectiveFolderId(job) {
  const isKnownFolder = (value) => {
    const normalized = typeof value === 'string' ? value.trim() : '';
    if (!normalized) {
      return false;
    }
    return Array.isArray(state.archiveFolders) && state.archiveFolders.some((folder) => {
      return folder
        && typeof folder.id === 'string'
        && folder.id.trim() === normalized;
    });
  };

  if (!job) {
    return '';
  }
  if (selectedFolderByJobId.has(job.id)) {
    const localValue = selectedFolderByJobId.get(job.id);
    if (isKnownFolder(localValue)) {
      return String(localValue).trim();
    }
    return '';
  }
  if (typeof job.selectedFolderId === 'string' && isKnownFolder(job.selectedFolderId)) {
    return job.selectedFolderId.trim();
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.folderId === 'string' && isKnownFolder(autoResult.folderId)) {
    return autoResult.folderId.trim();
  }
  return '';
}

function effectiveFilenameTemplateId(job) {
  const effectiveFolder = findArchiveFolderById(effectiveFolderId(job));
  const isKnownFilenameTemplate = (value) => {
    const normalized = typeof value === 'string' ? value.trim() : '';
    if (!normalized) {
      return false;
    }
    const templates = Array.isArray(effectiveFolder && effectiveFolder.filenameTemplates) ? effectiveFolder.filenameTemplates : [];
    return templates.some((template) => template && typeof template.id === 'string' && template.id.trim() === normalized);
  };

  if (!job) {
    return '';
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.filenameTemplateId === 'string' && isKnownFilenameTemplate(autoResult.filenameTemplateId)) {
    return autoResult.filenameTemplateId.trim();
  }
  const firstTemplate = Array.isArray(effectiveFolder && effectiveFolder.filenameTemplates)
    ? effectiveFolder.filenameTemplates.find((template) => template && typeof template.id === 'string' && template.id.trim() !== '')
    : null;
  if (firstTemplate && typeof firstTemplate.id === 'string') {
    return firstTemplate.id.trim();
  }
  return '';
}

function formatFilenameAmount(value) {
  const amount = Number(value);
  if (!Number.isFinite(amount)) {
    return null;
  }
  return amount.toFixed(2).replace('.', ',');
}

function extractionFieldTypeByKey(fieldKey) {
  const normalizedKey = typeof fieldKey === 'string' ? fieldKey.trim() : '';
  if (!normalizedKey) {
    return '';
  }

  const allFieldDefinitions = [
    ...sanitizeExtractionFields(predefinedExtractionFieldsDraft),
    ...sanitizeExtractionFields(extractionFieldsDraft),
    ...sanitizeExtractionFields(systemExtractionFieldsDraft),
  ];

  const match = allFieldDefinitions.find((field) => {
    return field && typeof field === 'object' && String(field.key || '').trim() === normalizedKey;
  }) || null;

  if (!match) {
    return '';
  }

  const firstRuleSet = Array.isArray(match.ruleSets) ? match.ruleSets[0] : null;
  return sanitizeExtractionFieldType(firstRuleSet && typeof firstRuleSet === 'object' ? firstRuleSet.type : '', match);
}

function normalizeFilenameIdentifierDigits(value) {
  return digitsOnly(value);
}

function buildFilenameIdentifierValueList(extractionFields, key) {
  const field = extractionFields && typeof extractionFields === 'object' ? extractionFields[key] : null;
  const values = Array.isArray(field)
    ? field
    : (field && typeof field === 'object'
      ? (Array.isArray(field.values)
        ? field.values
        : (Object.prototype.hasOwnProperty.call(field, 'value') ? [field.value] : []))
      : (field !== undefined && field !== null ? [field] : []));
  return values
    .map((value) => String(value ?? '').trim())
    .filter((value) => value !== '');
}

function senderPaymentNameForFilenameValues(sender, extractionFields, type) {
  if (!sender || typeof sender !== 'object') {
    return '';
  }
  const typeKey = type === 'plusgiro' ? 'plusgiro' : 'bankgiro';
  const fieldValues = buildFilenameIdentifierValueList(extractionFields, typeKey);
  if (fieldValues.length === 0) {
    return '';
  }
  const normalizedValues = new Set(fieldValues.map(normalizeFilenameIdentifierDigits).filter((value) => value !== ''));
  if (normalizedValues.size === 0) {
    return '';
  }
  const paymentNumbers = Array.isArray(sender.paymentNumbers) ? sender.paymentNumbers : [];
  const match = paymentNumbers.find((payment) => {
    if (!payment || typeof payment !== 'object') {
      return false;
    }
    const paymentType = String(payment.type || '').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
    if (paymentType !== typeKey) {
      return false;
    }
    const normalized = normalizeFilenameIdentifierDigits(payment.number);
    return normalized !== '' && normalizedValues.has(normalized);
  });
  return match && typeof match.payeeName === 'string' ? match.payeeName.trim() : '';
}

function senderOrganizationNameForFilenameValues(sender, extractionFields) {
  if (!sender || typeof sender !== 'object') {
    return '';
  }
  const fieldValues = buildFilenameIdentifierValueList(extractionFields, 'organisationsnummer');
  if (fieldValues.length === 0) {
    return '';
  }
  const normalizedValues = new Set(fieldValues.map(normalizeFilenameIdentifierDigits).filter((value) => value !== ''));
  if (normalizedValues.size === 0) {
    return '';
  }
  const organizationNumbers = Array.isArray(sender.organizationNumbers) ? sender.organizationNumbers : [];
  const match = organizationNumbers.find((organization) => {
    if (!organization || typeof organization !== 'object') {
      return false;
    }
    const normalized = normalizeFilenameIdentifierDigits(organization.organizationNumber);
    return normalized !== '' && normalizedValues.has(normalized);
  });
  return match && typeof match.organizationName === 'string' ? match.organizationName.trim() : '';
}

function buildFilenameFieldValues(job, options = {}) {
  if (!job) {
    return new Map();
  }

  const overrideFolderId = typeof options.folderId === 'string' ? options.folderId.trim() : '';
  const overrideLabelIds = Array.isArray(options.labelIds) ? normalizeSelectedLabelIds(options.labelIds) : null;
  const overrideSelection = options && typeof options.selectedExtractionFieldValues === 'object' && !Array.isArray(options.selectedExtractionFieldValues)
    ? normalizeSelectedExtractionFieldValues(options.selectedExtractionFieldValues)
    : null;
  const extractionFields = job.analysis && typeof job.analysis === 'object' && job.analysis.extractionFields && typeof job.analysis.extractionFields === 'object'
    ? job.analysis.extractionFields
    : {};
  const extractionFieldMeta = job.analysis && typeof job.analysis === 'object' && job.analysis.extractionFieldMeta && typeof job.analysis.extractionFieldMeta === 'object'
    ? job.analysis.extractionFieldMeta
    : {};
  const clientDirName = effectiveClientDirName(job);
  const sender = findSenderById(effectiveSenderId(job));
  const folder = findArchiveFolderById(overrideFolderId || effectiveFolderId(job));
  const labelIds = overrideLabelIds || effectiveSelectedLabelIds(job);
  const selectedFieldValues = overrideSelection || effectiveSelectedExtractionFieldValues(job);

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

  setValue('folder', archiveFolderDisplayName(folder));
  setValue('client', clientDirName);
  setValue('main_client', clientDirName);
  setValue('sender', sender && sender.name);
  setValue('bankgiro_name', senderPaymentNameForFilenameValues(sender, extractionFields, 'bankgiro'));
  setValue('plusgiro_name', senderPaymentNameForFilenameValues(sender, extractionFields, 'plusgiro'));
  setValue('organization_number_name', senderOrganizationNameForFilenameValues(sender, extractionFields));

  const firstFieldValue = (value) => {
    if (Array.isArray(value)) {
      return value.length > 0 ? value[0] : null;
    }
    return value;
  };

  const extractionFieldValue = (key) => {
    const normalizedKey = typeof key === 'string' ? key.trim() : '';
    if (normalizedKey === '') {
      return '';
    }
    const primarySelectionValue = primaryExtractionFieldValueForJob(job, normalizedKey);
    if (primarySelectionValue !== '') {
      return primarySelectionValue;
    }

    const field = extractionFields && typeof extractionFields === 'object' ? extractionFields[normalizedKey] : null;
    if (Array.isArray(field)) {
      return firstFieldValue(field);
    }
    if (field && typeof field === 'object' && Object.prototype.hasOwnProperty.call(field, 'value')) {
      return field.value;
    }
    const meta = extractionFieldMeta && typeof extractionFieldMeta === 'object' ? extractionFieldMeta[normalizedKey] : null;
    if (meta && typeof meta === 'object') {
      if (Array.isArray(meta.values) && meta.values.length > 0) {
        return meta.values[0];
      }
      if (typeof meta.value === 'string' && meta.value.trim() !== '') {
        return meta.value.trim();
      }
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
    const rawValue = entry ? entry.value : firstFieldValue(field);
    let value = typeof rawValue === 'string'
      ? rawValue.trim()
      : (rawValue === null || rawValue === undefined ? '' : String(rawValue).trim());
    if (key && extractionFieldTypeByKey(key) === 'amount') {
      value = formatFilenameAmount(value) || value;
    }
    if (key && value) {
      setValue(key, value);
    }
  });

  Object.entries(selectedFieldValues).forEach(([fieldKey]) => {
    const key = typeof fieldKey === 'string' ? fieldKey.trim() : '';
    if (key === '') {
      return;
    }
    const value = extractionFieldValue(key);
    if (key && value && !values.has(key)) {
      setValue(key, value);
    }
  });

  const labelNames = labelIds
    .map((labelId) => filenameTemplateLabelNameById(labelId))
    .filter((name) => typeof name === 'string' && name.trim() !== '');
  if (labelNames.length > 0) {
    values.set('__labels', labelNames);
  }
  if (labelIds.length > 0) {
    values.set('__labelIds', Array.from(new Set(labelIds)));
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

    if (part.type === 'dataField' || part.type === 'systemField') {
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

    if (part.type === 'folder') {
      const value = String(fieldValues.get('folder') || '').trim();
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

    if (part.type === 'ifLabels') {
      const selectedLabelIds = Array.isArray(fieldValues.get('__labelIds'))
        ? fieldValues.get('__labelIds').map((item) => String(item || '').trim()).filter((item) => item !== '')
        : [];
      const conditionLabelIds = normalizeLabelIdList(part.labelIds);
      const mode = sanitizeIfLabelsMode(part.mode);
      const isTrue = conditionLabelIds.length < 1
        ? false
        : (mode === 'all'
          ? conditionLabelIds.every((labelId) => selectedLabelIds.includes(labelId))
          : conditionLabelIds.some((labelId) => selectedLabelIds.includes(labelId)));
      const branchParts = isTrue ? (part.thenParts || []) : (part.elseParts || []);
      const resolved = evaluateFilenameTemplateParts(branchParts, fieldValues);
      if (resolved === '') {
        return;
      }
      result += evaluateFilenameTemplateParts(part.prefixParts || [], fieldValues);
      result += resolved;
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

function generateFilenameForJob(job, options = {}) {
  if (!job) {
    return '';
  }

  const overrideFolderId = typeof options.folderId === 'string' ? options.folderId.trim() : '';
  const overrideLabelIds = Array.isArray(options.labelIds) ? normalizeSelectedLabelIds(options.labelIds) : null;
  const overrideTemplateId = typeof options.filenameTemplateId === 'string' ? options.filenameTemplateId.trim() : '';
  const folder = findArchiveFolderById(overrideFolderId || effectiveFolderId(job));
  let filenameTemplate = null;
  if (overrideTemplateId !== '') {
    filenameTemplate = findFilenameTemplateById(overrideTemplateId);
  }
  if (!filenameTemplate && folder) {
    filenameTemplate = selectArchiveFolderFilenameTemplateByLabelIds(folder, overrideLabelIds || effectiveSelectedLabelIds(job));
  }
  if (!filenameTemplate) {
    filenameTemplate = findFilenameTemplateById(effectiveFilenameTemplateId(job));
  }
  const template = filenameTemplate && filenameTemplate.template && typeof filenameTemplate.template === 'object'
    ? sanitizeFilenameTemplate(filenameTemplate.template)
    : { parts: [] };
  const fieldValues = buildFilenameFieldValues(job, {
    folderId: overrideFolderId,
    labelIds: overrideLabelIds,
  });
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
  if (filenameByJobId.has(job.id)) {
    return String(filenameByJobId.get(job.id) || '').trim();
  }
  const filenameTemplate = findFilenameTemplateById(effectiveFilenameTemplateId(job));
  const template = filenameTemplate && filenameTemplate.template && typeof filenameTemplate.template === 'object'
    ? sanitizeFilenameTemplate(filenameTemplate.template)
    : { parts: [] };
  const hasMeaningfulTemplate = Array.isArray(template.parts) && template.parts.some((part) => {
    if (!part || typeof part !== 'object') {
      return false;
    }
    if (part.type === 'text') {
      return typeof part.value === 'string' && part.value.trim() !== '';
    }
    return true;
  });
  const generatedFromTemplate = hasMeaningfulTemplate ? generateFilenameForJob(job) : '';
  const savedFilename = typeof job.filename === 'string' ? job.filename.trim() : '';
  if (hasMeaningfulTemplate) {
    const autoResult = autoArchivingResultForJob(job);
    const autoFilename = autoResult && typeof autoResult.filename === 'string'
      ? autoResult.filename.trim()
      : '';
    const originalFilename = typeof job.originalFilename === 'string'
      ? job.originalFilename.trim()
      : '';
    if (!savedFilename) {
      return generatedFromTemplate;
    }
    if (savedFilename === generatedFromTemplate) {
      return generatedFromTemplate;
    }
    if (
      (autoFilename && savedFilename === autoFilename)
      || (originalFilename && savedFilename === originalFilename)
    ) {
      return generatedFromTemplate;
    }
    return savedFilename;
  }
  if (savedFilename !== '') {
    return savedFilename;
  }
  const autoResult = autoArchivingResultForJob(job);
  if (autoResult && typeof autoResult.filename === 'string' && autoResult.filename.trim() !== '') {
    return autoResult.filename.trim();
  }
  return generateFilenameForJob(job);
}

function proposedFilenameForJob(job, proposed = proposedArchivingResultForJob(job)) {
  if (!job || !proposed || typeof proposed !== 'object') {
    return '';
  }
  const directValue = typeof proposed.filename === 'string' ? proposed.filename.trim() : '';
  if (directValue !== '') {
    return directValue;
  }
  return generateFilenameForJob(job, {
    folderId: typeof proposed.folderId === 'string' ? proposed.folderId.trim() : '',
    labelIds: effectiveSelectedLabelIds(job),
    filenameTemplateId: typeof proposed.filenameTemplateId === 'string' ? proposed.filenameTemplateId.trim() : '',
  });
}

function syncCurrentActionValuesFromProposalChange(jobId, previousCurrentFolder, previousCurrentFilename, previousProposed, nextProposed, options = {}) {
  if (typeof jobId !== 'string' || jobId === '') {
    return;
  }
  if (!previousProposed || typeof previousProposed !== 'object' || !nextProposed || typeof nextProposed !== 'object') {
    return;
  }

  const syncFolder = options.syncFolder === true;
  const previousProposedFolder = typeof previousProposed.folderId === 'string' ? previousProposed.folderId.trim() : '';
  const nextProposedFolder = typeof nextProposed.folderId === 'string' ? nextProposed.folderId.trim() : '';
  if (syncFolder && previousCurrentFolder === previousProposedFolder) {
    if (nextProposedFolder !== '') {
      selectedFolderByJobId.set(jobId, nextProposedFolder);
    } else {
      selectedFolderByJobId.delete(jobId);
    }
  }

  const previousProposedFilename = typeof previousProposed.filename === 'string' ? previousProposed.filename.trim() : '';
  const nextProposedFilename = typeof nextProposed.filename === 'string' ? nextProposed.filename.trim() : '';
  if (previousCurrentFilename === previousProposedFilename) {
    if (nextProposedFilename !== '') {
      filenameByJobId.set(jobId, nextProposedFilename);
    } else {
      filenameByJobId.delete(jobId);
    }
  }
}

function renderArchiveFolderPathForJob(job) {
  const folder = findArchiveFolderById(effectiveFolderId(job));
  const template = folder && folder.pathTemplate && typeof folder.pathTemplate === 'object'
    ? sanitizeFilenameTemplate(folder.pathTemplate)
    : { parts: [] };
  const fieldValues = buildFilenameFieldValues(job);
  return evaluateFilenameTemplateParts(template.parts || [], fieldValues).replace(/\s+/g, ' ').trim();
}

function filenameTooltipForJob(job, explicitFilename = null) {
  if (!job) {
    return '';
  }

  if (job.archived === true) {
    const archivedPdfPath = typeof job.archivedPdfPath === 'string' ? job.archivedPdfPath.trim() : '';
    if (archivedPdfPath !== '') {
      return archivedPdfPath;
    }
  }

  const filename = typeof explicitFilename === 'string'
    ? explicitFilename.trim()
    : displayedFilenameForJob(job).trim();
  if (filename === '') {
    return '';
  }

  const basePath = normalizedPathValue(outputBasePathEl ? outputBasePathEl.value : pathsBaselineValue);
  const clientDirName = effectiveClientDirName(job);
  const archiveFolderPath = renderArchiveFolderPathForJob(job);
  const parts = [basePath, clientDirName, archiveFolderPath, filename]
    .filter((part) => typeof part === 'string' && part.trim() !== '')
    .map((part, index) => index === 0 ? part.replace(/[\\/]+$/, '') : part.replace(/^[\\/]+|[\\/]+$/g, ''));
  return parts.join('/');
}

function syncFilenameField(job) {
  if (!filenameInputEl) {
    return;
  }

  const disabled = !selectedJobArchivingEditable(job);
  filenameInputEl.disabled = disabled;
  filenameInputEl.value = job ? displayedFilenameForJob(job) : '';
  filenameInputEl.title = job ? filenameTooltipForJob(job, filenameInputEl.value) : '';
  if (disabled || !job) {
    setFilenameFieldExpanded(false);
  }
  syncFilenameExpandedWidth(job);
}

function currentJobValuesDifferFromProposed(job) {
  const proposed = proposedArchivingResultForJob(job);
  if (!job || !proposed || typeof proposed !== 'object') {
    return {
      client: false,
      sender: false,
      folder: false,
      filename: false,
      labels: false,
      dataFields: false,
      labelsAndDataFields: false,
    };
  }

  const proposedClientId = typeof proposed.clientId === 'string' ? proposed.clientId.trim() : '';
  const proposedSenderId = proposed.senderId ? String(proposed.senderId).trim() : '';
  const proposedFolderId = typeof proposed.folderId === 'string' ? proposed.folderId.trim() : '';
  const proposedFilename = proposedFilenameForJob(job, proposed);
  const proposedLabels = normalizeComparableLabelIds(proposed.labels);
  const labelsDiffer = !arrayValuesEqual(normalizeComparableLabelIds(effectiveSelectedLabelIds(job)), proposedLabels);
  const dataFieldsDiffer = extractionFieldSelectionsDifferFromAuto(job);

  return {
    client: effectiveClientDirName(job) !== proposedClientId,
    sender: effectiveSenderId(job) !== proposedSenderId,
    folder: effectiveFolderId(job) !== proposedFolderId,
    filename: String(displayedFilenameForJob(job) || '').trim() !== proposedFilename,
    labels: labelsDiffer,
    dataFields: dataFieldsDiffer,
    labelsAndDataFields: labelsDiffer || dataFieldsDiffer,
  };
}

function proposedResetTooltip(fieldKey, job) {
  const proposed = proposedArchivingResultForJob(job);
  if (!proposed || typeof proposed !== 'object') {
    return '';
  }
  if (fieldKey === 'client') {
    const value = clientDisplayNameByDirName(typeof proposed.clientId === 'string' ? proposed.clientId.trim() : '');
    return value ? `Återställ till föreslagen huvudman:\n${value}` : '';
  }
  if (fieldKey === 'sender') {
    const sender = findSenderById(proposed.senderId);
    const value = sender && typeof sender.name === 'string' && sender.name.trim() !== ''
      ? sender.name.trim()
      : (proposed.senderId ? String(proposed.senderId).trim() : '');
    return value ? `Återställ till föreslagen avsändare:\n${value}` : '';
  }
  if (fieldKey === 'folder') {
    const folder = findArchiveFolderById(typeof proposed.folderId === 'string' ? proposed.folderId.trim() : '');
    const value = archiveFolderDisplayName(folder) || (typeof proposed.folderId === 'string' ? proposed.folderId.trim() : '');
    return value ? `Återställ till föreslagen mapp:\n${value}` : '';
  }
  if (fieldKey === 'filename') {
    const value = proposedFilenameForJob(job, proposed);
    return value ? `Återställ till föreslaget filnamn:\n${value}` : '';
  }
  if (fieldKey === 'labels') {
    return 'Återställ etiketter och datafält till automatiskt föreslagna värden.\nManuella ändringar tas bort.';
  }
  return '';
}

function setFieldResetButtonVisibility(buttonEl, visible, tooltipValue = '') {
  if (!(buttonEl instanceof HTMLButtonElement)) {
    return;
  }
  buttonEl.hidden = !visible;
  if (visible) {
    buttonEl.title = tooltipValue;
  } else {
    buttonEl.title = '';
  }
}

function updateSelectedJobResetActions(job) {
  const editable = !!job && resetActionsModeActive() && selectedJobArchivingEditable(job) && !!proposedArchivingResultForJob(job);
  const diffs = editable ? currentJobValuesDifferFromProposed(job) : {
    client: false,
    sender: false,
    folder: false,
    filename: false,
    labels: false,
    dataFields: false,
    labelsAndDataFields: false,
  };

  setFieldResetButtonVisibility(resetClientActionEl, editable && diffs.client, proposedResetTooltip('client', job));
  setFieldResetButtonVisibility(resetSenderActionEl, editable && diffs.sender, proposedResetTooltip('sender', job));
  setFieldResetButtonVisibility(resetFolderActionEl, editable && diffs.folder, proposedResetTooltip('folder', job));
  setFieldResetButtonVisibility(resetFilenameActionEl, editable && diffs.filename, proposedResetTooltip('filename', job));
  setFieldResetButtonVisibility(resetLabelsActionEl, editable && diffs.labelsAndDataFields, proposedResetTooltip('labels', job));
  syncFilenameExpandedWidth(job);
  syncJobLabelsExpandedWidth(job);
}

function selectedJobAnalysisOutdated(job) {
  return !!(job && job.status === 'ready' && job.archived !== true && job.analysisOutdated === true);
}

function syncSelectedJobActionsWarning(job) {
  if (!(selectedJobActionsWarningEl instanceof HTMLElement)) {
    return;
  }

  const textEl = selectedJobActionsWarningEl.querySelector('.selected-job-actions-warning-text');
  const reprocessButtonEl = selectedJobActionsWarningReprocessEl instanceof HTMLButtonElement
    ? bindSelectedJobReprocessButton(selectedJobActionsWarningReprocessEl)
    : null;
  if (!(textEl instanceof HTMLElement) || !selectedJobAnalysisOutdated(job)) {
    selectedJobActionsWarningEl.classList.add('hidden');
    selectedJobActionsWarningEl.classList.remove('is-marquee');
    if (reprocessButtonEl instanceof HTMLButtonElement) {
      reprocessButtonEl.disabled = true;
    }
    return;
  }

  selectedJobActionsWarningEl.classList.remove('hidden');
  if (reprocessButtonEl instanceof HTMLButtonElement) {
    const reprocessDisabled = !job
      || job.status === 'processing'
      || job.archived === true
      || (!job.hasReviewPdf && !job.hasSourcePdf);
    reprocessButtonEl.disabled = reprocessDisabled;
    reprocessButtonEl.title = reprocessDisabled
      ? 'Jobbet kan inte analyseras om just nu.'
      : 'Analysera om dokumentet';
  }
  requestAnimationFrame(() => {
    if (!(selectedJobActionsWarningEl instanceof HTMLElement) || !(textEl instanceof HTMLElement)) {
      return;
    }
    const visibleWidth = selectedJobActionsWarningEl.clientWidth
      - (reprocessButtonEl instanceof HTMLButtonElement ? reprocessButtonEl.offsetWidth + 8 : 0);
    selectedJobActionsWarningEl.style.setProperty('--warning-visible-width', `${Math.max(0, visibleWidth)}px`);
    const shouldMarquee = textEl.scrollWidth > Math.max(0, visibleWidth) + 2;
    selectedJobActionsWarningEl.classList.toggle('is-marquee', shouldMarquee);
  });
}

function updateArchiveAction(job) {
  if (!archiveActionEl) {
    return;
  }

  if (dismissArchivedUpdateActionEl instanceof HTMLButtonElement) {
    const archivedReviewMode = currentJobListMode === 'archived-review';
    dismissArchivedUpdateActionEl.hidden = !archivedReviewMode;
    dismissArchivedUpdateActionEl.style.display = archivedReviewMode ? '' : 'none';
    dismissArchivedUpdateActionEl.textContent = 'Avfärda';
    dismissArchivedUpdateActionEl.disabled = true;
    dismissArchivedUpdateActionEl.title = archivedReviewMode
	? 'Ignorerar de föreslagna ändringarna för det här dokumentet.\nVisas inte längre bland arkiverade att granska, men kan visas igen om reglerna ändras eller om analysen körs om.'
	: '';
  }

  if (!job) {
    archiveActionEl.dataset.jobAction = currentJobListMode === 'archived-review' ? 'update' : 'archive';
    archiveActionEl.disabled = true;
    archiveActionEl.textContent = currentJobListMode === 'archived-review' ? 'Uppdatera' : 'Arkivera';
    archiveActionEl.title = 'Markera ett jobb först.';
    return;
  }

  const isArchived = job.archived === true;
  if (archivedReviewModeActiveForJob(job)) {
    const payload = selectedArchivedReviewPayload(job);
    archiveActionEl.dataset.jobAction = 'update';
    archiveActionEl.textContent = 'Uppdatera';
    archiveActionEl.disabled = !(payload && payload.isActionable === true);
    archiveActionEl.title = archiveActionEl.disabled
      ? 'Analysen för det arkiverade dokumentet laddas eller saknar förändringar.'
      : 'Sparar aktuellt förslag eller manuella ändringar för det arkiverade dokumentet.';
    if (dismissArchivedUpdateActionEl instanceof HTMLButtonElement) {
      dismissArchivedUpdateActionEl.disabled = !(payload && payload.isActionable === true);
    }
    return;
  }

  archiveActionEl.dataset.jobAction = isArchived ? 'restore' : 'archive';
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
  if (!effectiveFolderId(job)) {
    missingFields.push('Mapp');
  }
  if (!String(filenameInputEl ? filenameInputEl.value : displayedFilenameForJob(job)).trim()) {
    missingFields.push('Filnamn');
  }
  Array.from(requiredExtractionFieldKeysForJob(job)).forEach((fieldKey) => {
    if (primaryExtractionFieldValueForJob(job, fieldKey) === '') {
      const fieldName = extractionFieldDisplayNameByKey(fieldKey);
      if (fieldName && !missingFields.includes(`${fieldName}`)) {
        missingFields.push(`${fieldName}`);
      }
    }
  });

  archiveActionEl.disabled = job.status !== 'ready' || missingFields.length > 0;
  archiveActionEl.title = archiveActionEl.disabled
    ? (job.status !== 'ready'
      ? 'Jobbet måste vara klart innan det kan arkiveras.'
      : `Fyll i ${missingFields.join(', ')} innan jobbet kan arkiveras.`)
    : 'Flyttar review.pdf till vald huvudmans arkivmapp med angivet filnamn.';
}

function showAnalysisOutdatedArchiveDialog() {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'analysis-warning-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'analysis-warning-dialog';

    const title = document.createElement('h3');
    title.textContent = 'Analys inaktuell';

    const message = document.createElement('p');
    message.textContent = 'Nya relevanta avsändaruppgifter har tillkommit sedan senaste analysen. Dokumentet kan få ett annat analysresultat om analysen körs igen.';

    const actions = document.createElement('div');
    actions.className = 'analysis-warning-dialog-actions';

    const archiveAnywayButton = document.createElement('button');
    archiveAnywayButton.type = 'button';
    archiveAnywayButton.textContent = 'Arkivera ändå';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.textContent = 'Avbryt';

    const reprocessButton = document.createElement('button');
    reprocessButton.type = 'button';
    reprocessButton.className = 'analysis-warning-dialog-reprocess';
    reprocessButton.innerHTML = '<span>Analysera om</span><span aria-hidden="true">↻</span>';
    reprocessButton.title = 'Analysera om dokumentet';

    actions.append(reprocessButton, archiveAnywayButton, cancelButton);
    dialog.append(title, message, actions);
    overlay.appendChild(dialog);

    const finish = (choice) => {
      document.removeEventListener('keydown', onKeyDown);
      overlay.remove();
      resolve(choice);
    };

    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        finish('cancel');
      }
    };

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        finish('cancel');
      }
    });
    reprocessButton.addEventListener('click', () => finish('reprocess'));
    archiveAnywayButton.addEventListener('click', () => finish('archive'));
    cancelButton.addEventListener('click', () => finish('cancel'));

    document.addEventListener('keydown', onKeyDown);
    document.body.appendChild(overlay);
    reprocessButton.focus();
  });
}

function showDeleteJobDialog(job) {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'analysis-warning-dialog-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'analysis-warning-dialog';

    const title = document.createElement('h3');
    title.textContent = 'Ta bort dokument?';

    const message = document.createElement('p');
    message.textContent = 'Detta tar bort dokumentet från Docflow.';

    let deleteArchivedFileCheckbox = null;
    if (job && job.archived === true) {
      const option = document.createElement('label');
      option.className = 'analysis-warning-dialog-option';

      deleteArchivedFileCheckbox = document.createElement('input');
      deleteArchivedFileCheckbox.type = 'checkbox';
      deleteArchivedFileCheckbox.checked = true;

      const optionText = document.createElement('span');
      optionText.textContent = 'Ta bort även arkiverad fil';

      option.append(deleteArchivedFileCheckbox, optionText);
      dialog.append(title, message, option);
    } else {
      dialog.append(title, message);
    }

    const actions = document.createElement('div');
    actions.className = 'analysis-warning-dialog-actions';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.textContent = 'Avbryt';

    const deleteButton = document.createElement('button');
    deleteButton.type = 'button';
    deleteButton.className = 'is-danger';
    deleteButton.textContent = 'Ta bort';

    actions.append(cancelButton, deleteButton);
    dialog.appendChild(actions);
    overlay.appendChild(dialog);

    const finish = (confirmed) => {
      document.removeEventListener('keydown', onKeyDown, true);
      overlay.remove();
      resolve({
        confirmed,
        deleteArchivedFile: confirmed && deleteArchivedFileCheckbox instanceof HTMLInputElement
          ? deleteArchivedFileCheckbox.checked
          : false,
      });
    };

    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        finish(false);
      }
    };

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        finish(false);
      }
    });
    cancelButton.addEventListener('click', () => finish(false));
    deleteButton.addEventListener('click', () => finish(true));

    document.addEventListener('keydown', onKeyDown, true);
    document.body.appendChild(overlay);
    cancelButton.focus();
  });
}

function showLabelImportDialog() {
  return new Promise((resolve) => {
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';

    const dialog = document.createElement('div');
    dialog.className = 'settings-dialog label-import-dialog';

    const title = document.createElement('h3');
    title.textContent = 'Importera etikett';

    const body = document.createElement('div');
    body.className = 'label-import-dialog-body';

    const description = document.createElement('p');
    description.textContent = 'Klistra in en etikett i samma JSON-format som kopiera-knappen exporterar.';

    const textarea = document.createElement('textarea');
    textarea.placeholder = '{\n  "name": "Överförmyndarnämnd",\n  "description": "…",\n  "minScore": 3,\n  "rules": [ ... ]\n}';
    textarea.spellcheck = false;

    const error = document.createElement('div');
    error.className = 'label-import-error';

    const actions = document.createElement('div');
    actions.className = 'panel-actions';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'button-danger';
    cancelButton.textContent = 'Avbryt';

    const importButton = document.createElement('button');
    importButton.type = 'button';
    importButton.className = 'button-success';
    importButton.textContent = 'Importera';

    actions.append(cancelButton, importButton);
    body.append(description, textarea, error);
    dialog.append(title, body, actions);
    overlay.appendChild(dialog);

    const finish = (value = null) => {
      document.removeEventListener('keydown', onKeyDown, true);
      overlay.remove();
      resolve(value);
    };

    const submit = () => {
      const result = parseImportedLabelJson(textarea.value);
      if (!result || !result.label) {
        error.textContent = result && typeof result.error === 'string'
          ? result.error
          : 'Importen misslyckades.';
        textarea.focus();
        return;
      }
      finish(result.label);
    };

    const onKeyDown = (event) => {
      if (event.key === 'Escape') {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        finish(null);
        return;
      }
      if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
        event.preventDefault();
        event.stopPropagation();
        event.stopImmediatePropagation();
        submit();
      }
    };

    overlay.addEventListener('click', (event) => {
      if (event.target === overlay) {
        finish(null);
      }
    });
    cancelButton.addEventListener('click', () => finish(null));
    importButton.addEventListener('click', submit);

    document.addEventListener('keydown', onKeyDown, true);
    document.body.appendChild(overlay);
    textarea.focus();
  });
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
  setFolderForJob(currentJob);
  setLabelsForJob(currentJob);
  syncFilenameField(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
}

async function deleteSelectedJob() {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    return;
  }

  closeSelectedJobActionsMenu();
  const confirmation = await showDeleteJobDialog(selectedJob);
  if (!confirmation || confirmation.confirmed !== true) {
    return;
  }

  const nextSelectedJobId = nextVisibleJobIdAfterRemoval(selectedJob.id);
  const deleteArchivedFile = selectedJob.archived === true && confirmation.deleteArchivedFile === true;

  if (selectedJobDeleteActionEl instanceof HTMLButtonElement) {
    selectedJobDeleteActionEl.disabled = true;
  }
  if (selectedJobActionsMenuButtonEl instanceof HTMLButtonElement) {
    selectedJobActionsMenuButtonEl.disabled = true;
  }

  try {
    const response = await fetch('/api/delete-job.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        jobId: selectedJob.id,
        deleteArchivedFile,
      }),
    });
    const payload = await response.json().catch(() => null);
    if (!response.ok || !payload || payload.ok !== true) {
      const message = payload && typeof payload.error === 'string'
        ? payload.error
        : 'Kunde inte ta bort dokumentet.';
      throw new Error(message);
    }

    if (selectedJob.archived === true) {
      removeJobFromArchivingReviewLocalState(selectedJob);
    } else {
      clearLocalArchivedReviewStateForJob(selectedJob.id);
    }
    preferredJobIdFromHash = nextSelectedJobId;
    applyJobEvents([{
      type: 'job.remove',
      jobId: selectedJob.id,
    }]);
  } catch (error) {
    alert(error.message || 'Kunde inte ta bort dokumentet.');
  } finally {
    updateSelectedJobActionsMenu(findJobById(selectedJobId));
  }
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
  if (!response.ok || !data || data.ok !== true) {
    const message = data && typeof data.error === 'string' ? data.error : 'Kunde inte spara jobbdata';
    throw new Error(message);
  }

  if (requestSeq !== saveSelectedJobFieldsSeq) {
    return;
  }
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

function applySelectedFilenameValue(value) {
  if (!selectedJobId) {
    return;
  }
  const currentJob = findJobById(selectedJobId);
  if (!currentJob || (!selectedJobArchivingEditable(currentJob) && !archivedReviewModeActiveForJob(currentJob))) {
    syncFilenameField(currentJob);
    return;
  }

  if (!value.trim()) {
    filenameByJobId.delete(selectedJobId);
  } else {
    filenameByJobId.set(selectedJobId, value);
  }
  if (filenameInputEl instanceof HTMLInputElement) {
    filenameInputEl.value = value;
  }
  filenameInputEl.title = filenameTooltipForJob(currentJob, value);
  updateArchivedReviewDraftFromSidebar(currentJob);
  updateArchiveAction(currentJob);
  updateSelectedJobResetActions(currentJob);
  syncFilenameExpandedWidth(currentJob);
  if (archivedReviewModeActiveForJob(currentJob)) {
    return;
  }
  saveSelectedJobFields(selectedJobId, { filename: value }).catch((error) => {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte spara filnamn.');
  });
}

function renderSelectedJobPanel() {
  const reprocessButtonEl = syncSelectedJobReprocessButton();
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    syncReviewViewModeAvailability(null, { allowFallback: false });
    selectedJobPanelEl.classList.add('is-empty');
    selectedJobNameEl.textContent = 'Inget jobb markerat';
    renderSelectedJobStatus(null);
    selectedJobMetaEl.textContent = 'Markera ett jobb i listan för att visa åtgärder.';
    renderSelectedJobClientSection(null);
    renderSelectedJobSenderSection(null);
    syncSelectedJobActionsWarning(null);
    setLabelsForJob(null);
    if (reprocessButtonEl instanceof HTMLButtonElement) {
      reprocessButtonEl.disabled = true;
      reprocessButtonEl.title = 'Markera ett jobb först.';
    }
    syncFilenameField(null);
    syncOcrMenuState(null);
    updateArchiveAction(null);
    updateSelectedJobResetActions(null);
    updateSelectedJobActionsMenu(null);
    return;
  }

  syncReviewViewModeAvailability(selectedJob, { allowFallback: false });
  selectedJobPanelEl.classList.remove('is-empty');
  selectedJobNameEl.textContent = selectedJob.originalFilename || selectedJob.id;
  renderSelectedJobStatus(selectedJob);

  const metaLines = [];
  const appendLine = (text, extraClass = '') => {
    const line = document.createElement('div');
    line.className = extraClass ? `selected-job-meta-line ${extraClass}` : 'selected-job-meta-line';
    line.textContent = text;
    metaLines.push(line);
  };

  if (selectedJob && selectedJob.ocr && selectedJob.ocr.usedExistingText === true) {
    appendLine('Dokumentet hade redan OCR-text. OCR-steget hoppades över.');
  }

  if (selectedJob.status === 'failed' && selectedJob.error) {
    appendLine('Fel: ' + selectedJob.error, 'is-error');
  }
  if (selectedJob.archived === true && typeof selectedJob.archivedAt === 'string' && selectedJob.archivedAt) {
    appendLine('Arkiverat: ' + selectedJob.archivedAt.replace('T', ' ').replace(/([+-]\d{2}:\d{2}|Z)$/, '').trim());
  }
  if (selectedJob.archived === true && jobHasArchivingUpdateChange(selectedJob.id)) {
    appendLine('Arkiveringen kan uppdateras enligt aktuella regler.', 'is-warning');
  }

  selectedJobMetaEl.replaceChildren(...metaLines);
  renderSelectedJobClientSection(selectedJob);
  renderSelectedJobSenderSection(selectedJob);
  syncSelectedJobActionsWarning(selectedJob);
  if (reprocessButtonEl instanceof HTMLButtonElement) {
    reprocessButtonEl.disabled = selectedJob.status === 'processing'
      || selectedJob.archived === true
      || (!selectedJob.hasReviewPdf && !selectedJob.hasSourcePdf);
    reprocessButtonEl.title = reprocessButtonEl.disabled
      ? (selectedJob.status === 'processing'
        ? 'Jobbet bearbetas redan.'
        : (selectedJob.archived === true
          ? 'Arkiverade jobb kan inte köras om här.'
          : 'Det finns varken review.pdf eller source.pdf att köra om från.'))
      : 'Klicka för att analysera igen från review.pdf. Håll Ctrl nedtryckt för att tvinga en helomkörning från source.pdf.';
  }
  syncFilenameField(selectedJob);
  syncOcrMenuState(selectedJob);
  updateArchiveAction(selectedJob);
  updateSelectedJobResetActions(selectedJob);
  updateSelectedJobActionsMenu(selectedJob);
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
  if (ocrMenuWrapEl instanceof HTMLElement) {
    ocrMenuWrapEl.classList.toggle('hidden', !visible);
  }
  if (!visible) {
    closeOcrMenu();
  }
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

function syncOcrMenuState(job = findJobById(selectedJobId)) {
  if (!(ocrMenuButtonEl instanceof HTMLButtonElement) || !(ocrDownloadActionEl instanceof HTMLButtonElement)) {
    return;
  }
  const disabled = !job || job.status === 'processing';
  ocrMenuButtonEl.disabled = disabled;
  ocrMenuButtonEl.title = disabled ? 'Markera ett färdigbearbetat dokument först.' : 'Fler alternativ';
  ocrDownloadActionEl.disabled = disabled;
}

function ocrDownloadBaseName(job) {
  const base = job && typeof job.originalFilename === 'string' && job.originalFilename.trim() !== ''
    ? job.originalFilename.trim()
    : (job && typeof job.id === 'string' && job.id.trim() !== '' ? job.id.trim() : 'dokument');
  return base.replace(/\.pdf$/i, '').replace(/[^\p{L}\p{N}._ -]+/gu, '_').trim() || 'dokument';
}

async function loadOcrPayload(jobId, source) {
  const response = await fetch(
    '/api/get-job-ocr.php?id='
      + encodeURIComponent(jobId)
      + '&source='
      + encodeURIComponent(source),
    { cache: 'no-store' }
  );
  if (response.status === 404) {
    throw new Error('Ingen OCR-data finns för aktuell källa.');
  }
  if (!response.ok) {
    throw new Error('Kunde inte hämta OCR-data för nedladdning.');
  }
  const payload = await response.json().catch(() => null);
  if (!payload || typeof payload !== 'object') {
    throw new Error('Ogiltigt OCR-svar för nedladdning.');
  }
  return payload;
}

function triggerDownloadBlob(filename, blob) {
  const url = URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  document.body.appendChild(link);
  link.click();
  link.remove();
  window.setTimeout(() => URL.revokeObjectURL(url), 0);
}

async function downloadCurrentOcrRepresentation() {
  const job = findJobById(selectedJobId);
  if (!job || job.status === 'processing') {
    return;
  }

  const source = normalizeOcrSource(currentOcrSource);
  const payload = await loadOcrPayload(job.id, source);
  const mode = typeof payload.mode === 'string' ? payload.mode : 'text';
  const baseName = ocrDownloadBaseName(job);
  const sourceSuffix = source === 'merged' ? 'sammanfogad-text'
    : source === 'merged-objects' ? 'sammanfogade-objekt'
    : source;

  if (mode === 'objects') {
    const filename = `${baseName}.${sourceSuffix}.json`;
    const json = JSON.stringify({
      jobId: job.id,
      source,
      mode,
      text: typeof payload.text === 'string' ? payload.text : '',
      pages: Array.isArray(payload.pages) ? payload.pages : [],
    }, null, 2);
    triggerDownloadBlob(filename, new Blob([json], { type: 'application/json;charset=utf-8' }));
    return;
  }

  const filename = `${baseName}.${sourceSuffix}.txt`;
  const text = typeof payload.text === 'string' ? payload.text : '';
  triggerDownloadBlob(filename, new Blob([text], { type: 'text/plain;charset=utf-8' }));
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

function escapeRegexPattern(value) {
  return String(value || '').replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

function whitespaceFlexibleRegexPattern(value) {
  return String(value || '').replace(/\s+/gu, '\\s+');
}

function buildOcrSearchRegex(query, useRegex) {
  const source = useRegex ? String(query || '') : escapeRegexPattern(query);
  return new RegExp(whitespaceFlexibleRegexPattern(source), 'gimu');
}

function buildOcrSearchMatches(text, query, useRegex) {
  if (!query) {
    return [];
  }

  const regex = buildOcrSearchRegex(query, useRegex);
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
  if (jobId !== selectedJobId) {
    closeJobLabelsOverlay();
    closeSelectedJobActionsMenu();
  }
  const selectedJob = findJobById(jobId);
  selectedJobId = selectedJob ? selectedJob.id : '';
  syncReviewViewModeAvailability(selectedJob);
  syncPrimaryViewModeAvailability(selectedJob);
  renderJobList(state.processingJobs, state.readyJobs, state.failedJobs);
  setViewerJob(selectedJobId);
  selectedJobStateSig = jobStateSignature(selectedJob);
  setClientForJob(selectedJob);
  setSenderForJob(selectedJob);
  setFolderForJob(selectedJob);
  setLabelsForJob(selectedJob);
  if (archivedReviewModeActiveForJob(selectedJob)) {
    loadArchivedReview(selectedJob.id).catch(() => {
      renderArchivedReviewPanel();
    });
  }
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
    const shouldRefreshViewer = nextJobStateSig !== selectedJobStateSig;
    selectedJobId = currentSelection.id;
    syncReviewViewModeAvailability(currentSelection);
    syncPrimaryViewModeAvailability(currentSelection);
    if (shouldRefreshViewer) {
      setViewerJob(currentSelection.id);
    }
    selectedJobStateSig = nextJobStateSig;
    setClientForJob(currentSelection);
    setSenderForJob(currentSelection);
    setFolderForJob(currentSelection);
    setLabelsForJob(currentSelection);
    syncFilenameField(currentSelection);
    updateArchiveAction(currentSelection);
    updateSelectedJobResetActions(currentSelection);
    if (archivedReviewModeActiveForJob(currentSelection)) {
      loadArchivedReview(currentSelection.id, shouldRefreshViewer).catch(() => {
        renderArchivedReviewPanel();
      });
    }
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
  const shouldUpdateArchiveFolders = Array.isArray(nextState.archiveFolders);
  const shouldUpdateSenderOrganizationLookupQueue = nextState.senderOrganizationLookupQueue && typeof nextState.senderOrganizationLookupQueue === 'object';
  const shouldUpdateSenderPayeeLookupQueue = nextState.senderPayeeLookupQueue && typeof nextState.senderPayeeLookupQueue === 'object';
  const shouldUpdateArchivingRules = nextState.archivingRules && typeof nextState.archivingRules === 'object';
  const nextSenderOrganizationLookupQueue = shouldUpdateSenderOrganizationLookupQueue
    ? normalizeSenderOrganizationLookupQueue(nextState.senderOrganizationLookupQueue)
    : state.senderOrganizationLookupQueue;
  const nextSenderPayeeLookupQueue = shouldUpdateSenderPayeeLookupQueue
    ? normalizeSenderPayeeLookupQueue(nextState.senderPayeeLookupQueue)
    : state.senderPayeeLookupQueue;
  const nextArchivingRules = shouldUpdateArchivingRules
    ? normalizeArchivingRulesStatePayload(nextState.archivingRules, state.archivingRules)
    : state.archivingRules;

  state = {
    processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : state.processingJobs,
    readyJobs: Array.isArray(nextState.readyJobs) ? nextState.readyJobs : state.readyJobs,
    archivedJobs: Array.isArray(nextState.archivedJobs) ? nextState.archivedJobs : state.archivedJobs,
    failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : state.failedJobs,
    senderOrganizationLookupQueue: nextSenderOrganizationLookupQueue,
    senderPayeeLookupQueue: nextSenderPayeeLookupQueue,
    clients: shouldUpdateClients ? nextState.clients : state.clients,
    senders: shouldUpdateSenders ? nextState.senders : state.senders,
    archiveFolders: shouldUpdateArchiveFolders ? nextState.archiveFolders : state.archiveFolders,
    archivingRules: nextArchivingRules
  };
  console.info('[Docflow] applyState senderOrganizationLookupQueue', state.senderOrganizationLookupQueue);
  console.info('[Docflow] applyState senderPayeeLookupQueue', state.senderPayeeLookupQueue);

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
  Array.from(selectedFolderByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedFolderByJobId.delete(jobId);
    }
  });
  Array.from(selectedLabelIdsByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedLabelIdsByJobId.delete(jobId);
    }
  });
  Array.from(selectedExtractionFieldValuesByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedExtractionFieldValuesByJobId.delete(jobId);
    }
  });
  Array.from(filenameByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      filenameByJobId.delete(jobId);
    }
  });
  Array.from(archivedReviewPayloadByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      archivedReviewPayloadByJobId.delete(jobId);
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
  pruneReprocessWatchJobs();
  updateBulkResetWatchFromState();
  syncChromeExtensionOrganizationQueueRuntimeFromState();
  syncChromeExtensionPayeeQueueRuntimeFromState();

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
  if (shouldUpdateArchiveFolders) {
    renderFolderSelect(state.archiveFolders);
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
    archiveFolders: state.archiveFolders,
    archivingRules: state.archivingRules,
  };
  let mutated = false;
  let archivingRulesMutated = false;

  events.forEach((eventPayload) => {
    if (!eventPayload || typeof eventPayload !== 'object') {
      return;
    }

    const eventId = Number.parseInt(String(eventPayload.id || ''), 10);
    if (Number.isInteger(eventId) && eventId > 0 && eventId <= stateEventCursor) {
      return;
    }
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
      const archivedJob = Array.isArray(nextState.archivedJobs)
        ? nextState.archivedJobs.find((entry) => entry && entry.id === jobId) || null
        : null;
      pinnedProcessingJobIds.delete(jobId);
      pinnedProcessingOrderById.delete(jobId);
      if (archivedJob) {
        clearLocalArchivedReviewStateForJob(jobId);
        nextState.archivingRules = archivingRulesStateWithoutJob(nextState.archivingRules, archivedJob);
        archivingRulesMutated = true;
      }
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

    syncUnarchivedJobAutoProposalChange(findJobById(jobId), job);
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

function applyOptimisticReprocess(jobId, mode = 'post-ocr', options = {}) {
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
    error: null,
    reprocessMode: mode === 'full' ? 'full' : 'post-ocr',
    forceOcr: options.forceOcr === true,
    analysisOutdated: false,
    analysisAutoReprocessQueued: false,
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

function settingsDialogViewportBounds() {
  const margin = SETTINGS_DIALOG_VIEWPORT_MARGIN_PX;
  return {
    margin,
    width: Math.max(320, window.innerWidth - (margin * 2)),
    height: Math.max(320, window.innerHeight - (margin * 2)),
  };
}

function defaultSettingsDialogLayout() {
  const bounds = settingsDialogViewportBounds();
  const width = Math.min(980, bounds.width);
  const height = Math.min(920, bounds.height);
  return {
    width,
    height,
    left: Math.round((window.innerWidth - width) / 2),
    top: Math.round((window.innerHeight - height) / 2),
  };
}

function clampSettingsDialogLayout(layout) {
  const source = layout && typeof layout === 'object' ? layout : {};
  const bounds = settingsDialogViewportBounds();
  const width = Math.round(Math.min(
    bounds.width,
    Math.max(Math.min(SETTINGS_DIALOG_MIN_WIDTH_PX, bounds.width), Number(source.width) || defaultSettingsDialogLayout().width)
  ));
  const height = Math.round(Math.min(
    bounds.height,
    Math.max(Math.min(SETTINGS_DIALOG_MIN_HEIGHT_PX, bounds.height), Number(source.height) || defaultSettingsDialogLayout().height)
  ));
  const left = Math.round(Math.min(
    window.innerWidth - bounds.margin - width,
    Math.max(bounds.margin, Number(source.left))
  ));
  const top = Math.round(Math.min(
    window.innerHeight - bounds.margin - height,
    Math.max(bounds.margin, Number(source.top))
  ));
  return {
    width,
    height,
    left: Number.isFinite(left) ? left : bounds.margin,
    top: Number.isFinite(top) ? top : bounds.margin,
  };
}

function applySettingsDialogLayout(layout) {
  if (!(settingsDialogEl instanceof HTMLElement)) {
    return;
  }
  const nextLayout = clampSettingsDialogLayout(layout || settingsDialogLayout || defaultSettingsDialogLayout());
  settingsDialogLayout = nextLayout;
  settingsDialogEl.style.width = `${nextLayout.width}px`;
  settingsDialogEl.style.height = `${nextLayout.height}px`;
  settingsDialogEl.style.left = `${nextLayout.left}px`;
  settingsDialogEl.style.top = `${nextLayout.top}px`;
}

function restoreSettingsDialogLayout() {
  applySettingsDialogLayout(settingsDialogLayout || defaultSettingsDialogLayout());
}

function stopSettingsDialogInteractions() {
  settingsDialogDragState = null;
  settingsDialogResizeState = null;
  document.body.classList.remove('is-dragging-settings-dialog', 'is-resizing-settings-dialog');
}

function openSettingsModal() {
  if (!settingsDialogLayout) {
    restoreSettingsDialogLayout();
  } else {
    applySettingsDialogLayout(settingsDialogLayout);
  }
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
    sendersUnlinkedListEl = document.getElementById('senders-unlinked-list');
    sendersAddRowEl = document.getElementById('senders-add-row');
    sendersCancelEl = document.getElementById('senders-cancel');
    sendersApplyEl = document.getElementById('senders-apply');
    sendersSortOrderEl = document.getElementById('senders-sort-order');
    sendersExpandAllEl = document.getElementById('senders-expand-all');
    sendersCollapseAllEl = document.getElementById('senders-collapse-all');
    sendersViewSendersEl = document.getElementById('senders-view-senders');
    sendersSelectedCountEl = document.getElementById('senders-selected-count');
    sendersClearSelectionEl = document.getElementById('senders-clear-selection');
    sendersMergeSelectedEl = document.getElementById('senders-merge-selected');
    senderMergeOverlayEl = document.getElementById('sender-merge-overlay');
    senderMergeEditorEl = document.getElementById('sender-merge-editor');
    senderMergeCancelEl = document.getElementById('sender-merge-cancel');
    senderMergeApplyEl = document.getElementById('sender-merge-apply');
    sendersSortOrderEl.value = sendersSortOrder;
    if (String(sendersSortOrderEl.value || '') !== sendersSortOrder) {
      sendersSortOrder = String(sendersSortOrderEl.value || 'name');
    }
    setSendersPanelTab();
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
    matchingNoisePenaltyEl = document.getElementById('matching-noise-penalty');
    matchingTrailingDelimiterPenaltyEl = document.getElementById('matching-trailing-delimiter-penalty');
    matchingOtherMatchKeyPenaltyEl = document.getElementById('matching-other-match-key-penalty');
    matchingRightYOffsetPenaltyEl = document.getElementById('matching-right-y-offset-penalty');
    matchingDownXOffsetPenaltyEl = document.getElementById('matching-down-x-offset-penalty');
    matchingDataFieldAcceptanceThresholdEl = document.getElementById('matching-data-field-acceptance-threshold');
    const bindMatchingPenaltyInput = (inputEl, key) => {
      if (!(inputEl instanceof HTMLInputElement)) {
        return;
      }
      inputEl.addEventListener('input', () => {
        const maxDecimal = key === 'noisePenaltyPerCharacter' ? 1 : null;
        matchingPositionAdjustmentDraft[key] = sanitizeMatchingPercentInput(
          inputEl.value,
          matchingPositionAdjustmentDraft[key],
          maxDecimal
        );
        updateSettingsActionButtons();
      });
    };
    bindMatchingPenaltyInput(matchingNoisePenaltyEl, 'noisePenaltyPerCharacter');
    bindMatchingPenaltyInput(matchingTrailingDelimiterPenaltyEl, 'trailingDelimiterPenalty');
    bindMatchingPenaltyInput(matchingOtherMatchKeyPenaltyEl, 'otherMatchKeyPenalty');
    bindMatchingPenaltyInput(matchingRightYOffsetPenaltyEl, 'rightYOffsetPenalty');
    bindMatchingPenaltyInput(matchingDownXOffsetPenaltyEl, 'downXOffsetPenalty');
    if (matchingDataFieldAcceptanceThresholdEl instanceof HTMLInputElement) {
      matchingDataFieldAcceptanceThresholdEl.addEventListener('input', () => {
        matchingDataFieldAcceptanceThresholdDraft = sanitizeMatchingPercentInput(
          matchingDataFieldAcceptanceThresholdEl.value,
          matchingDataFieldAcceptanceThresholdDraft,
          null
        );
        updateSettingsActionButtons();
      });
    }
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
      matchingPositionAdjustmentDraft = sanitizeMatchingPositionAdjustmentSettings(parsed.positionAdjustment);
      matchingDataFieldAcceptanceThresholdDraft = parsed.dataFieldAcceptanceThreshold ?? 0.5;
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
  } else if (tabId === 'archive-structure') {
    archiveStructureListEl = document.getElementById('archive-structure-list');
    archiveStructureAddFolderEl = document.getElementById('archive-structure-add-folder');
    archiveStructureFolderSortEl = document.getElementById('archive-structure-folder-sort');
    archiveStructureCancelEl = document.getElementById('archive-structure-cancel');
    archiveStructureApplyEl = document.getElementById('archive-structure-apply');
    if (archiveStructureFolderSortEl instanceof HTMLSelectElement) {
      archiveStructureFolderSortEl.value = archiveStructureFolderSortMode;
      archiveStructureFolderSortEl.addEventListener('change', () => {
        archiveStructureFolderSortMode = archiveStructureFolderSortEl.value || 'path';
        renderArchiveStructureEditor();
      });
    }
    archiveStructureAddFolderEl.addEventListener('click', () => {
      archiveFoldersDraft.push(defaultArchiveFolder());
      renderArchiveStructureEditor();
      updateSettingsActionButtons();
    });
    archiveStructureCancelEl.addEventListener('click', () => {
      let parsed = {};
      try {
        parsed = JSON.parse(archiveStructureBaselineJson);
      } catch (error) {
        parsed = {};
      }
      archiveFoldersDraft = Array.isArray(parsed.archiveFolders) ? parsed.archiveFolders.map((folder, index) => sanitizeArchiveFolder(folder, index)) : [];
      renderArchiveStructureEditor();
      updateSettingsActionButtons();
    });
    archiveStructureApplyEl.addEventListener('click', async () => {
      try {
        await saveArchiveStructure();
      } catch (error) {
        alert(error.message || 'Kunde inte spara arkivstruktur.');
      }
    });
  } else if (tabId === 'labels') {
    labelsListEl = document.getElementById('labels-list');
    systemLabelEditorEl = document.getElementById('system-label-editor');
    labelsAddRowEl = document.getElementById('labels-add-row');
    labelsImportRowEl = document.getElementById('labels-import-row');
    labelsCancelEl = document.getElementById('labels-cancel');
    labelsApplyEl = document.getElementById('labels-apply');
    labelsAddRowEl.addEventListener('click', () => {
      labelsDraft.push(defaultLabel());
      renderLabelsEditor();
      updateSettingsActionButtons();
    });
    labelsImportRowEl.addEventListener('click', async () => {
      const importedLabel = await showLabelImportDialog();
      if (!importedLabel) {
        return;
      }
      labelsDraft.push(importedLabel);
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
    archivingReviewActionsEl = document.getElementById('archiving-review-actions');
    archivingReviewSummaryEl = document.getElementById('archiving-review-summary');
    archivingReviewTemplateChangesEl = document.getElementById('archiving-review-template-changes');
    archivingReviewJobsEl = document.getElementById('archiving-review-jobs');
  } else if (tabId === 'paths') {
    inputInboxPathEl = document.getElementById('input-inbox-path');
    outputBasePathEl = document.getElementById('output-base-path');
    pathsCancelEl = document.getElementById('paths-cancel');
    pathsApplyEl = document.getElementById('paths-apply');
    inputInboxPathEl.addEventListener('input', () => {
      updateSettingsActionButtons();
    });
    outputBasePathEl.addEventListener('input', () => {
      updateSettingsActionButtons();
    });
    pathsCancelEl.addEventListener('click', () => {
      inputInboxPathEl.value = inboxPathBaselineValue;
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
    settingsResetJobsEl = document.getElementById('settings-reset-jobs');
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
    settingsResetJobsEl.addEventListener('click', async () => {
      const confirmed = window.confirm(
        'Detta återställer alla oarkiverade jobb, flyttar tillbaka deras source.pdf till inbox och lämnar arkiverade dokument orörda. Fortsätta?'
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
  } else if (tabId === 'extensions') {
    systemChromeExtensionStatusEl = document.getElementById('system-chrome-extension-status');
    systemChromeExtensionTestEl = document.getElementById('system-chrome-extension-test');
    systemChromeExtensionSuppressMissingEl = document.getElementById('system-chrome-extension-suppress-missing');
    systemChromeExtensionDebugEl = document.getElementById('system-chrome-extension-debug');
    systemChromeExtensionPageEl = document.getElementById('system-chrome-extension-page');
    systemChromeExtensionDirectoryEl = document.getElementById('system-chrome-extension-directory');
    systemChromeExtensionCopyPageEl = document.getElementById('system-chrome-extension-copy-page');
    systemChromeExtensionCopyDirectoryEl = document.getElementById('system-chrome-extension-copy-directory');
    if (systemChromeExtensionSuppressMissingEl instanceof HTMLInputElement) {
      systemChromeExtensionSuppressMissingEl.checked = chromeExtensionSuppressMissingNotice === true;
      systemChromeExtensionSuppressMissingEl.addEventListener('change', async () => {
        const previousValue = chromeExtensionSuppressMissingNotice === true;
        const nextValue = systemChromeExtensionSuppressMissingEl.checked === true;
        if (nextValue === previousValue) {
          return;
        }
        systemChromeExtensionSuppressMissingEl.disabled = true;
        try {
          await saveChromeExtensionSuppressMissingSetting(nextValue);
          updateSystemChromeExtensionDebug(nextValue ? 'Notisen för saknat tillägg är dold.' : 'Notisen för saknat tillägg visas igen.');
        } catch (error) {
          systemChromeExtensionSuppressMissingEl.checked = previousValue;
          alert(error.message || 'Kunde inte spara inställningen för Chrome-tillägget.');
        } finally {
          systemChromeExtensionSuppressMissingEl.disabled = false;
        }
      });
    }
    if (systemChromeExtensionCopyPageEl) {
      systemChromeExtensionCopyPageEl.addEventListener('click', async () => {
        try {
          const copied = await copyTextToClipboard('chrome://extensions');
          if (!copied) {
            throw new Error('Kunde inte kopiera adressen.');
          }
          updateSystemChromeExtensionDebug('Adressen kopierades.');
        } catch (error) {
          alert(error.message || 'Kunde inte kopiera adressen.');
        }
      });
    }
    if (systemChromeExtensionCopyDirectoryEl) {
      systemChromeExtensionCopyDirectoryEl.addEventListener('click', async () => {
        try {
          const copied = await copyTextToClipboard(chromeExtensionDirectory);
          if (!copied) {
            throw new Error('Kunde inte kopiera sökvägen.');
          }
          updateSystemChromeExtensionDebug('Sökvägen kopierades.');
        } catch (error) {
          alert(error.message || 'Kunde inte kopiera sökvägen.');
        }
      });
    }
    if (systemChromeExtensionTestEl) {
      systemChromeExtensionTestEl.addEventListener('click', async () => {
        systemChromeExtensionTestEl.disabled = true;
        updateSystemChromeExtensionDebug('Testar kommunikationen med Chrome-tillägget...');
        try {
          await extensionTest();
        } catch (error) {
          alert(error instanceof Error ? error.message : 'Testet mot Chrome-tillägget misslyckades.');
        } finally {
          systemChromeExtensionTestEl.disabled = false;
        }
      });
    }
    renderSystemChromeExtensionStatus();
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

  loadingSettingsPanels.add(tabId);
  updateSettingsActionButtons();

  try {
    if (tabId === 'clients') {
      await loadClientsSettings();
    } else if (tabId === 'senders') {
      await loadSendersSettings();
    } else if (tabId === 'matching') {
      await loadMatchingSettings();
    } else if (tabId === 'ocr-processing') {
      await loadOcrProcessingSettings(options);
    } else if (tabId === 'archive-structure') {
      await Promise.all([loadArchiveStructure(), loadExtractionFields(), loadLabels()]);
      renderArchiveStructureEditor();
    } else if (tabId === 'labels') {
      await loadLabels();
    } else if (tabId === 'data-fields') {
      await loadExtractionFields();
    } else if (tabId === 'archiving-review') {
      await loadArchivingRuleReview();
    } else if (tabId === 'paths') {
      await loadPathSettings();
    } else if (tabId === 'system') {
      // System-fliken använder lokalt state för transportval och reset-knapp.
    } else if (tabId === 'extensions') {
      await loadSystemSettings();
    }

    loadedSettingsPanels.add(tabId);
    return true;
  } finally {
    loadingSettingsPanels.delete(tabId);
    updateSettingsActionButtons();
  }
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
    sendersUnlinkedIdentifiers = [];
    sendersBaselineJson = normalizedSendersJson(sendersDraft);
    renderSendersEditor();
    setSendersPanelTab();
    updateSettingsActionButtons();
    return false;
  }

  if (sendersAddRowEl) {
    sendersAddRowEl.focus();
  }
  updateSettingsActionButtons();
  return true;
}

async function openSenderInRegister(senderId) {
  if (!Number.isInteger(senderId) || senderId < 1) {
    return;
  }

  const opened = await openSendersSettingsDirect();
  if (opened === false) {
    return;
  }

  setSendersPanelTab();
  renderSendersEditor();
  const senderRow = sendersDraft.find((row) => Number.isInteger(row && row.id) && row.id === senderId) || null;
  if (!senderRow) {
    return;
  }
  focusSenderDraftRow(senderUiKey(senderRow));
}

async function openClientInRegister(clientDirName) {
  const normalizedDirName = typeof clientDirName === 'string' ? clientDirName.trim() : '';
  if (!normalizedDirName) {
    return;
  }

  const opened = await openClientsSettingsDirect();
  if (opened === false) {
    return;
  }

  renderClientsEditor();
  const clientRow = clientsDraft.find((row) => {
    return row && typeof row.folderName === 'string' && row.folderName.trim() === normalizedDirName;
  }) || null;
  if (!clientRow) {
    return;
  }

  focusClientDraftRow(clientUiKey(clientRow));
}

async function openArchiveStructureSettingsDirect() {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return false;
  }

  openSettingsModal();
  setSettingsTab('archive-structure');

  try {
    await ensureSettingsPanelReady('archive-structure');
  } catch (error) {
    alert('Kunde inte ladda arkivstruktur.');
    archiveFoldersDraft = [];
    archiveStructureBaselineJson = normalizedArchiveStructureJson();
    renderArchiveStructureEditor();
    updateSettingsActionButtons();
    return false;
  }

  if (archiveStructureAddFolderEl) {
    archiveStructureAddFolderEl.focus();
  }
  updateSettingsActionButtons();
  return true;
}

function closeSettingsModal(force = false) {
  if (!force && !canLeaveCurrentSettingsView()) {
    return false;
  }

  stopSettingsDialogInteractions();
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

  const panelIds = ['clients', 'senders', 'matching', 'ocr-processing', 'archive-structure', 'labels', 'data-fields', 'archiving-review', 'paths', 'system', 'extensions'];
  panelIds.forEach((id) => {
    const panel = document.getElementById('settings-panel-' + id);
    if (!panel) {
      return;
    }
    panel.classList.toggle('hidden', id !== tabId);
    panel.classList.toggle('active', id === tabId);
  });

  syncSettingsFooterActions(tabId);
  updateSettingsActionButtons();
}

function isEditableSettingsTab(tabId) {
  return tabId === 'clients'
    || tabId === 'senders'
    || tabId === 'matching'
    || tabId === 'ocr-processing'
    || tabId === 'archive-structure'
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

function normalizedMatchingJson(replacements, positionAdjustment = matchingPositionAdjustmentDraft, dataFieldAcceptanceThreshold = matchingDataFieldAcceptanceThresholdDraft) {
  return JSON.stringify({
    replacements: replacements.map(sanitizeReplacement),
    positionAdjustment: sanitizeMatchingPositionAdjustmentSettings(positionAdjustment),
    dataFieldAcceptanceThreshold
  });
}

function normalizedOcrPdfSubstitutionsJson(replacements) {
  return JSON.stringify(replacements.map(sanitizeReplacement));
}

function normalizedSendersJson(senders) {
  return JSON.stringify(senders.map(sanitizeSenderDraft));
}

function normalizedArchiveStructureJson() {
  return JSON.stringify({
    archiveFolders: archiveFoldersDraft.map((folder, index) => sanitizeArchiveFolder(folder, index)),
  });
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
  return normalizedMatchingJson(matchingDraft, matchingPositionAdjustmentDraft, matchingDataFieldAcceptanceThresholdDraft) !== matchingBaselineJson;
}

function isSendersDirty() {
  return normalizedSendersJson(sendersDraft) !== sendersBaselineJson;
}

function isArchiveStructureDirty() {
  return normalizedArchiveStructureJson() !== archiveStructureBaselineJson;
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
  if (!outputBasePathEl || !inputInboxPathEl) {
    return false;
  }
  return normalizedPathValue(outputBasePathEl.value) !== pathsBaselineValue
    || normalizedPathValue(inputInboxPathEl.value) !== inboxPathBaselineValue;
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
  if (tabId === 'archive-structure') {
    return isArchiveStructureDirty();
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
  return isClientsDirty() || isSendersDirty() || isMatchingDirty() || isOcrProcessingDirty() || isArchiveStructureDirty() || isLabelsDirty() || isExtractionFieldsDirty() || isPathsDirty();
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
  if (tabId === 'archive-structure') {
    return [archiveStructureCancelEl, archiveStructureApplyEl];
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
  const reviewPayload = normalizeArchivingReviewPayload(archivingRulesReviewPayload || state.archivingRules?.updateReview);
  const clientsLoading = loadingSettingsPanels.has('clients');
  const sendersLoading = loadingSettingsPanels.has('senders');
  const matchingLoading = loadingSettingsPanels.has('matching');
  const ocrProcessingLoading = loadingSettingsPanels.has('ocr-processing');
  const archiveStructureLoading = loadingSettingsPanels.has('archive-structure');
  const labelsLoading = loadingSettingsPanels.has('labels');
  const extractionFieldsLoading = loadingSettingsPanels.has('data-fields');
  const archivingReviewLoading = loadingSettingsPanels.has('archiving-review');
  const pathsLoading = loadingSettingsPanels.has('paths');
  const clientsDirty = isClientsDirty();
  const sendersDirty = isSendersDirty();
  const matchingDirty = isMatchingDirty();
  const ocrProcessingDirty = isOcrProcessingDirty();
  const archiveStructureDirty = isArchiveStructureDirty();
  const archiveStructureError = archiveStructureValidationError();
  const labelsDirty = isLabelsDirty();
  const labelsError = labelsValidationError();
  const extractionFieldsDirty = isExtractionFieldsDirty();
  const pathsDirty = isPathsDirty();
  if (clientsCancelEl && clientsApplyEl) {
    clientsCancelEl.disabled = clientsLoading || !clientsDirty;
    clientsApplyEl.disabled = clientsLoading || !clientsDirty;
  }

  if (sendersCancelEl && sendersApplyEl) {
    sendersCancelEl.disabled = sendersLoading || !sendersDirty;
    sendersApplyEl.disabled = sendersLoading || !sendersDirty;
  }

  if (matchingCancelEl && matchingApplyEl) {
    matchingCancelEl.disabled = matchingLoading || !matchingDirty;
    matchingApplyEl.disabled = matchingLoading || !matchingDirty;
  }

  if (ocrProcessingCancelEl && ocrProcessingApplyEl) {
    ocrProcessingCancelEl.disabled = ocrProcessingLoading || !ocrProcessingDirty;
    ocrProcessingApplyEl.disabled = ocrProcessingLoading || !ocrProcessingDirty;
  }

  if (archiveStructureCancelEl && archiveStructureApplyEl) {
    archiveStructureCancelEl.disabled = archiveStructureLoading || !archiveStructureDirty;
    archiveStructureApplyEl.disabled = archiveStructureLoading || !archiveStructureDirty || archiveStructureError !== '';
    archiveStructureApplyEl.title = archiveStructureError || '';
  }

  if (labelsCancelEl && labelsApplyEl) {
    labelsCancelEl.disabled = labelsLoading || !labelsDirty;
    labelsApplyEl.disabled = labelsLoading || !labelsDirty || labelsError !== '';
    labelsApplyEl.title = labelsError || '';
  }

  if (extractionFieldsCancelEl && extractionFieldsApplyEl) {
    extractionFieldsCancelEl.disabled = extractionFieldsLoading || !extractionFieldsDirty;
    extractionFieldsApplyEl.disabled = extractionFieldsLoading || !extractionFieldsDirty;
  }

  if (pathsCancelEl && pathsApplyEl) {
    pathsCancelEl.disabled = pathsLoading || !pathsDirty;
    pathsApplyEl.disabled = pathsLoading || !pathsDirty;
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
    senderId: null,
    field: '',
    score: 1
  };
}

function defaultLabel() {
  return {
    id: '',
    name: '',
    description: '',
    minScore: 1,
    rules: [defaultRule()],
  };
}

function defaultArchiveFolder() {
  return {
    id: '',
    name: '',
    priority: 1,
    pathTemplate: {
      parts: [defaultFilenameTemplatePart('text')]
    },
    filenameTemplates: [defaultFilenameTemplateDraft()],
  };
}

function defaultFilenameTemplateDraft() {
  return {
    id: '',
    template: {
      parts: [defaultFilenameTemplatePart('text')]
    },
    labelIds: [],
  };
}

function moveArrayItem(items, fromIndex, toIndex) {
  if (!Array.isArray(items)) {
    return [];
  }
  const next = [...items];
  if (
    !Number.isInteger(fromIndex)
    || !Number.isInteger(toIndex)
    || fromIndex < 0
    || fromIndex >= next.length
    || toIndex < 0
    || toIndex >= next.length
    || fromIndex === toIndex
  ) {
    return next;
  }
  const [item] = next.splice(fromIndex, 1);
  next.splice(toIndex, 0, item);
  return next;
}

function sanitizePositiveInt(value, fallback = 1) {
  const parsed = parseInt(String(value), 10);
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  return parsed < 1 ? 1 : parsed;
}

function sanitizeInt(value, fallback = 0) {
  const parsed = parseInt(String(value), 10);
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  return parsed;
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
  applyChromeExtensionConfigPayload(payload);
  return stateUpdateTransport;
}

async function saveChromeExtensionSuppressMissingSetting(nextValue) {
  const response = await fetch('/api/save-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      chromeExtensionSuppressMissingNotice: nextValue === true,
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara inställningen för Chrome-tillägget';
    throw new Error(message);
  }

  applyChromeExtensionConfigPayload(payload);
  return chromeExtensionSuppressMissingNotice;
}

function clientUiKey(row) {
  if (row && typeof row.uiKey === 'string' && row.uiKey.trim() !== '') {
    return row.uiKey.trim();
  }
  return `tmp-client-${clientDraftUiKeySeq++}`;
}

function splitClientFirstNames(value) {
  const normalized = typeof value === 'string'
    ? value.trim().replace(/\s+/gu, ' ')
    : '';
  return normalized === '' ? [] : normalized.split(' ');
}

function normalizePreferredFirstNameIndex(value, firstName) {
  if (value === null || value === undefined || value === '') {
    return null;
  }
  const parts = Array.isArray(firstName) ? firstName : splitClientFirstNames(firstName);
  const numeric = Number.parseInt(String(value), 10);
  if (!Number.isInteger(numeric) || numeric < 0 || numeric >= parts.length) {
    return null;
  }
  return numeric;
}

function preferredFirstNameForClientRow(row) {
  const client = row && typeof row === 'object' ? row : {};
  const parts = splitClientFirstNames(typeof client.firstName === 'string' ? client.firstName : '');
  const index = normalizePreferredFirstNameIndex(client.preferredFirstNameIndex, parts);
  return index === null ? '' : parts[index];
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
  const preferredFirstNameIndex = normalizePreferredFirstNameIndex(
    input.preferredFirstNameIndex,
    firstName
  );

  return {
    uiKey,
    firstName,
    lastName,
    folderName,
    personalIdentityNumber,
    preferredFirstNameIndex
  };
}

function serializeClientDraft(row) {
  const client = sanitizeClientDraft(row);
  return {
    firstName: client.firstName.trim(),
    lastName: client.lastName.trim(),
    folderName: client.folderName.trim(),
    personalIdentityNumber: client.personalIdentityNumber.trim(),
    preferredFirstNameIndex: client.preferredFirstNameIndex
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
    personalIdentityNumber: '',
    preferredFirstNameIndex: null
  });
}

function defaultReplacement() {
  return {
    from: '',
    to: ''
  };
}

function defaultMatchingPositionAdjustmentSettings() {
  return {
    noisePenaltyPerCharacter: 0.01,
    trailingDelimiterPenalty: 0.25,
    otherMatchKeyPenalty: 0.5,
    rightYOffsetPenalty: 0.25,
    downXOffsetPenalty: 0.25
  };
}

function clampMatchingDecimal(value, fallback = 0, max = 1) {
  const parsed = Number.parseFloat(String(value).replace(',', '.'));
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  if (parsed < 0) {
    return 0;
  }
  if (typeof max === 'number' && Number.isFinite(max) && parsed > max) {
    return max;
  }
  return parsed;
}

function sanitizeMatchingPercentInput(value, fallbackDecimal, maxDecimal = 1) {
  const parsed = Number.parseFloat(String(value).replace(',', '.'));
  if (!Number.isFinite(parsed)) {
    return fallbackDecimal;
  }
  return clampMatchingDecimal(parsed / 100, fallbackDecimal, maxDecimal);
}

function formatMatchingPercentInput(value, maxDecimal = 1) {
  const decimal = clampMatchingDecimal(value, 0, maxDecimal);
  const percent = decimal * 100;
  if (Number.isInteger(percent)) {
    return String(percent);
  }
  return percent.toFixed(2).replace(/0+$/u, '').replace(/\.$/u, '');
}

function sanitizeMatchingPositionAdjustmentSettings(value) {
  const input = value && typeof value === 'object' ? value : {};
  const defaults = defaultMatchingPositionAdjustmentSettings();
  return {
    noisePenaltyPerCharacter: clampMatchingDecimal(input.noisePenaltyPerCharacter, defaults.noisePenaltyPerCharacter, 1),
    trailingDelimiterPenalty: clampMatchingDecimal(input.trailingDelimiterPenalty, defaults.trailingDelimiterPenalty, null),
    otherMatchKeyPenalty: clampMatchingDecimal(input.otherMatchKeyPenalty, defaults.otherMatchKeyPenalty, null),
    rightYOffsetPenalty: clampMatchingDecimal(
      input.rightYOffsetPenalty ?? input.downRightPenalty,
      defaults.rightYOffsetPenalty,
      null
    ),
    downXOffsetPenalty: clampMatchingDecimal(
      input.downXOffsetPenalty ?? input.downRightPenalty,
      defaults.downXOffsetPenalty,
      null
    )
  };
}

function defaultSenderDraft() {
  return {
    uiKey: `tmp-${senderDraftUiKeySeq++}`,
    id: null,
    name: '',
    domain: '',
    kind: '',
    notes: '',
    organizationNumbers: [],
    paymentNumbers: [],
    mergedSourceSenderIds: []
  };
}

function defaultSenderOrganizationDraft() {
  return {
    id: null,
    organizationNumber: '',
    organizationName: '',
  };
}

function defaultSenderPaymentDraft() {
  return {
    id: null,
    type: 'bankgiro',
    number: '',
    payeeName: ''
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
  const rawOrganizationNumbers = Array.isArray(input.organizationNumbers) ? input.organizationNumbers : [];
  const rawPaymentNumbers = Array.isArray(input.paymentNumbers) ? input.paymentNumbers : [];
  const fallbackOrgNumber = typeof input.orgNumber === 'string' ? input.orgNumber : '';
  const organizationNumbers = rawOrganizationNumbers.map(sanitizeSenderOrganizationDraft);
  if (organizationNumbers.length === 0 && fallbackOrgNumber.trim() !== '') {
    organizationNumbers.push(sanitizeSenderOrganizationDraft({
      organizationNumber: fallbackOrgNumber,
      organizationName: typeof input.name === 'string' ? input.name : '',
    }));
  }
  return {
    uiKey,
    id,
    name: typeof input.name === 'string' ? input.name : '',
    domain: typeof input.domain === 'string' ? input.domain : '',
    kind: typeof input.kind === 'string' ? input.kind : '',
    notes: typeof input.notes === 'string' ? input.notes : '',
    organizationNumbers,
    paymentNumbers: rawPaymentNumbers.map(sanitizeSenderPaymentDraft),
    mergedSourceSenderIds: Array.isArray(input.mergedSourceSenderIds)
      ? Array.from(new Set(input.mergedSourceSenderIds
        .map((value) => Number.parseInt(String(value || ''), 10))
        .filter((value) => Number.isInteger(value) && value > 0)))
      : []
  };
}

function sanitizeSenderOrganizationDraft(row) {
  const input = row && typeof row === 'object' ? row : {};
  const idValue = input.id;
  const id = Number.isInteger(idValue) && idValue > 0
    ? idValue
    : null;
  return {
    id,
    organizationNumber: typeof input.organizationNumber === 'string'
      ? input.organizationNumber
      : (typeof input.orgNumber === 'string' ? input.orgNumber : ''),
    organizationName: typeof input.organizationName === 'string'
      ? input.organizationName
      : (typeof input.name === 'string' ? input.name : ''),
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
    number: typeof input.number === 'string' ? input.number : '',
    payeeName: typeof input.payeeName === 'string' ? input.payeeName : '',
  };
}

function sanitizeUnlinkedSenderIdentifier(row) {
  const input = row && typeof row === 'object' ? row : {};
  const kind = String(input.kind || '').trim().toLowerCase() === 'organization' ? 'organization' : 'payment';
  const idValue = input.id;
  const id = Number.isInteger(idValue) && idValue > 0
    ? idValue
    : null;
  const normalizedNumber = typeof input.normalizedNumber === 'string'
    ? digitsOnly(input.normalizedNumber)
    : '';
  const paymentType = kind === 'payment' && String(input.paymentType || '').trim().toLowerCase() === 'plusgiro'
    ? 'plusgiro'
    : 'bankgiro';
  const typeLabel = kind === 'organization'
    ? 'ORG.NR'
    : (paymentType === 'plusgiro' ? 'PG' : 'BG');
  const key = kind === 'organization'
    ? `organization:${normalizedNumber}`
    : `payment:${paymentType}:${normalizedNumber}`;
  return {
    key,
    kind,
    id,
    typeLabel,
    paymentType: kind === 'payment' ? paymentType : '',
    number: typeof input.number === 'string' ? input.number : '',
    normalizedNumber,
    name: typeof input.name === 'string' ? input.name : '',
  };
}

function sortSenderLinkOptions(rows) {
  return [...rows].sort((left, right) => {
    const leftName = String(left && left.name || '').trim().toLowerCase();
    const rightName = String(right && right.name || '').trim().toLowerCase();
    if (leftName !== rightName) {
      if (leftName === '') {
        return 1;
      }
      if (rightName === '') {
        return -1;
      }
      return leftName.localeCompare(rightName, 'sv');
    }
    return 0;
  });
}

function senderLinkOptions() {
  return sortSenderLinkOptions(
    sendersDraft
      .map((row) => sanitizeSenderDraft(row))
      .filter((row) => String(row.name || '').trim() !== '')
      .map((row) => ({
        value: senderUiKey(row),
        label: row.name,
      }))
  );
}

function senderLinkOptionsForIdentifier(identifier) {
  const options = senderLinkOptions();
  const identifierName = String(identifier && identifier.name || '').trim();
  if (identifierName === '') {
    return options;
  }

  return [...options].sort((left, right) => {
    const leftScore = senderNameSimilarity(identifierName, left.label);
    const rightScore = senderNameSimilarity(identifierName, right.label);
    if (leftScore !== rightScore) {
      return rightScore - leftScore;
    }
    return left.label.localeCompare(right.label, 'sv');
  });
}

function claimedSenderIdentifierKeys() {
  const keys = new Set();

  sendersDraft.forEach((sender) => {
    const organizations = Array.isArray(sender && sender.organizationNumbers) ? sender.organizationNumbers : [];
    organizations.forEach((organization) => {
      const normalizedNumber = digitsOnly(organization && organization.organizationNumber);
      if (normalizedNumber !== '') {
        keys.add(`organization:${normalizedNumber}`);
      }
    });

    const payments = Array.isArray(sender && sender.paymentNumbers) ? sender.paymentNumbers : [];
    payments.forEach((payment) => {
      const type = String(payment && payment.type || '').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
      const normalizedNumber = digitsOnly(payment && payment.number);
      if (normalizedNumber !== '') {
        keys.add(`payment:${type}:${normalizedNumber}`);
      }
    });
  });

  return keys;
}

function visibleUnlinkedSenderIdentifiers() {
  const claimedKeys = claimedSenderIdentifierKeys();
  return sendersUnlinkedIdentifiers.filter((row) => !claimedKeys.has(row.key));
}

function setSendersPanelTab() {
  if (sendersViewSendersEl) {
    sendersViewSendersEl.classList.remove('hidden');
  }
}

function applyUnlinkedIdentifierToSenderDraft(senderDraft, identifier) {
  if (!senderDraft || typeof senderDraft !== 'object' || !identifier || typeof identifier !== 'object') {
    return;
  }

  if (identifier.kind === 'organization') {
    const existingRow = Array.isArray(senderDraft.organizationNumbers)
      ? senderDraft.organizationNumbers.find((row) => digitsOnly(row && row.organizationNumber) === identifier.normalizedNumber)
      : null;
    if (existingRow) {
      if (!Number.isInteger(existingRow.id) && Number.isInteger(identifier.id)) {
        existingRow.id = identifier.id;
      }
      if (String(existingRow.organizationNumber || '').trim() === '') {
        existingRow.organizationNumber = identifier.number;
      }
      if (String(existingRow.organizationName || '').trim() === '' && String(identifier.name || '').trim() !== '') {
        existingRow.organizationName = identifier.name;
      }
      return;
    }
    senderDraft.organizationNumbers.push(sanitizeSenderOrganizationDraft({
      id: identifier.id,
      organizationNumber: identifier.number,
      organizationName: identifier.name,
    }));
    return;
  }

  const existingPayment = Array.isArray(senderDraft.paymentNumbers)
    ? senderDraft.paymentNumbers.find((row) => {
      const rowType = String(row && row.type || '').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
      return rowType === identifier.paymentType && digitsOnly(row && row.number) === identifier.normalizedNumber;
    })
    : null;
  if (existingPayment) {
    if (!Number.isInteger(existingPayment.id) && Number.isInteger(identifier.id)) {
      existingPayment.id = identifier.id;
    }
    if (String(existingPayment.number || '').trim() === '') {
      existingPayment.number = identifier.number;
    }
    if (String(existingPayment.payeeName || '').trim() === '' && String(identifier.name || '').trim() !== '') {
      existingPayment.payeeName = identifier.name;
    }
    return;
  }

  senderDraft.paymentNumbers.push(sanitizeSenderPaymentDraft({
    id: identifier.id,
    type: identifier.paymentType,
    number: identifier.number,
    payeeName: identifier.name,
  }));
}

function focusSenderDraftRow(senderUiKeyValue) {
  if (!sendersListEl || typeof senderUiKeyValue !== 'string' || senderUiKeyValue.trim() === '') {
    return;
  }

  requestAnimationFrame(() => {
    const senderNode = sendersListEl.querySelector(`[data-sender-ui-key="${CSS.escape(senderUiKeyValue)}"]`);
    if (!(senderNode instanceof HTMLElement)) {
      return;
    }
    const nameInput = senderNode.querySelector('.sender-summary-fields input[type="text"]');
    if (nameInput instanceof HTMLElement) {
      nameInput.focus();
      if ('select' in nameInput && typeof nameInput.select === 'function') {
        nameInput.select();
      }
      return;
    }
    senderNode.scrollIntoView({ block: 'nearest' });
  });
}

function createSenderDraftFromUnlinkedIdentifier(identifier) {
  const draft = defaultSenderDraft();
  if (String(identifier && identifier.name || '').trim() !== '') {
    draft.name = String(identifier.name);
  }
  applyUnlinkedIdentifierToSenderDraft(draft, identifier);
  return draft;
}

function linkUnlinkedIdentifierToSender(identifierKey, senderUiKeyValue) {
  const identifier = visibleUnlinkedSenderIdentifiers().find((row) => row.key === identifierKey);
  if (!identifier) {
    return;
  }
  const senderIndex = sendersDraft.findIndex((row) => senderUiKey(row) === senderUiKeyValue);
  if (senderIndex < 0) {
    return;
  }

  applyUnlinkedIdentifierToSenderDraft(sendersDraft[senderIndex], identifier);
  renderSendersEditor();
  renderUnlinkedSenderIdentifiers();
  updateSettingsActionButtons();
}

function createSenderFromUnlinkedIdentifier(identifierKey) {
  const identifier = visibleUnlinkedSenderIdentifiers().find((row) => row.key === identifierKey);
  if (!identifier) {
    return;
  }

  const draft = createSenderDraftFromUnlinkedIdentifier(identifier);
  sendersDraft.unshift(draft);
  setSendersPanelTab('senders');
  renderSendersEditor();
  renderUnlinkedSenderIdentifiers();
  updateSettingsActionButtons();
  focusSenderDraftRow(senderUiKey(draft));
}

function senderSortFieldValue(row, field) {
  if (!row || typeof row !== 'object') {
    return '';
  }

  if (field === 'orgNumber') {
    const firstOrganization = Array.isArray(row.organizationNumbers)
      ? row.organizationNumbers.find((organization) => organization && typeof organization.organizationNumber === 'string' && organization.organizationNumber.trim() !== '')
      : null;
    return String(firstOrganization && firstOrganization.organizationNumber || '').trim().toLowerCase();
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

function mergedSenderOrganizationNumbers(rows) {
  const itemsByKey = new Map();
  rows.forEach((row) => {
    const organizationRows = Array.isArray(row && row.organizationNumbers) ? row.organizationNumbers : [];
    organizationRows.forEach((organization) => {
      const normalized = sanitizeSenderOrganizationDraft(organization);
      const key = digitsOnly(normalized.organizationNumber);
      if (key === '') {
        return;
      }
      if (!itemsByKey.has(key)) {
        itemsByKey.set(key, normalized);
        return;
      }
      const existing = itemsByKey.get(key);
      if (existing && (!existing.organizationName || String(existing.organizationName).trim() === '') && normalized.organizationName.trim() !== '') {
        itemsByKey.set(key, {
          ...existing,
          organizationName: normalized.organizationName,
        });
      }
    });
  });
  return Array.from(itemsByKey.entries())
    .sort((a, b) => a[0].localeCompare(b[0], 'sv'))
    .map(([, value]) => value);
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
      domain: domainOptions[0] || '',
      notes: mergedSenderNotes(rows),
      organizationNumbers: mergedSenderOrganizationNumbers(rows),
      paymentNumbers: mergedSenderPaymentNumbers(rows)
    }),
    fieldOptions: {
      name: nameOptions,
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
  const type = ['text', 'sender_is', 'sender_name_contains', 'field_exists'].includes(rawType)
    ? rawType
    : 'text';
  const senderId = Number.parseInt(String(input.senderId || ''), 10);
  return {
    type,
    text: (type === 'text' || type === 'sender_name_contains') && typeof input.text === 'string' ? input.text : '',
    isRegex: type === 'text' && input.isRegex === true,
    senderId: type === 'sender_is' && Number.isInteger(senderId) && senderId > 0 ? senderId : null,
    field: type === 'field_exists' && typeof input.field === 'string' ? input.field : '',
    score: sanitizeInt(input.score, 1)
  };
}

function sanitizeSystemLabelRule(rule) {
  return sanitizeLabelRule(rule);
}

function sanitizeLabelRule(rule) {
  return sanitizeRule(rule);
}

function sanitizeRuleLabelIds(labelIds) {
  const normalized = [];
  const seen = new Set();

  const appendLabelId = (value) => {
    const labelId = typeof value === 'string' ? value.trim() : '';
    if (!labelId || seen.has(labelId)) {
      return;
    }
    seen.add(labelId);
    normalized.push(labelId);
  };

  if (Array.isArray(labelIds)) {
    labelIds.forEach(appendLabelId);
  }

  return normalized;
}

function sanitizeArchiveFolder(archiveFolder, fallbackIndex = 0) {
  const input = archiveFolder && typeof archiveFolder === 'object' ? archiveFolder : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const normalizedId = typeof input.id === 'string' && input.id.trim() !== ''
    ? input.id.trim()
    : slugifyText(name || `folder-${fallbackIndex + 1}`, '-', `folder-${fallbackIndex + 1}`);
  const rawTemplates = Array.isArray(input.filenameTemplates) ? input.filenameTemplates : [];
  return {
    id: normalizedId,
    name,
    priority: sanitizePositiveInt(input.priority, 1),
    pathTemplate: sanitizeFilenameTemplate(input.pathTemplate ?? input.path),
    filenameTemplates: rawTemplates.map((template, index) => sanitizeFilenameTemplateDraft(template, index, normalizedId)),
  };
}

function sanitizeFilenameTemplateDraft(template, fallbackIndex = 0, folderId = '') {
  const input = template && typeof template === 'object' ? template : {};
  const normalizedId = typeof input.id === 'string' && input.id.trim() !== ''
    ? input.id.trim()
    : slugifyText(`${folderId || 'folder'}-filename-template-${fallbackIndex + 1}`, '-', `filename-template-${fallbackIndex + 1}`);
  return {
    id: normalizedId,
    template: sanitizeFilenameTemplate(input.template ?? input.filenameTemplate),
    labelIds: sanitizeRuleLabelIds(input.labelIds ?? (input.conditions && input.conditions.labelIds)),
  };
}

function sanitizeLabel(label) {
  const input = label && typeof label === 'object' ? label : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const description = typeof input.description === 'string' ? input.description : '';
  const rawRules = Array.isArray(input.rules) ? input.rules : [];
  const rules = rawRules.map(sanitizeLabelRule);
  return {
    id: slugifyText(name, '-', ''),
    name,
    description,
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
  const description = typeof input.description === 'string'
    ? input.description
    : (typeof defaults.description === 'string' ? defaults.description : '');
  return {
    id: slugifyText(name, '-', 'label'),
    systemLabelKey: key,
    isSystemLabel: true,
    name,
    description,
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
      description: typeof label.description === 'string' ? label.description.trim() : '',
    }))
    .filter((label) => label.value !== '' && label.label !== '');
}

function archiveRuleLabelOptionsList() {
  const options = [...systemLabelOptions(), ...sanitizeLabels(labelsDraft)
    .map((label) => ({
      value: typeof label.id === 'string' ? label.id.trim() : '',
      label: typeof label.name === 'string' ? label.name.trim() : '',
      description: typeof label.description === 'string' ? label.description.trim() : '',
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

function archiveRuleLabelOptions() {
  return archiveRuleLabelOptionsList();
}

function archiveRuleLabelOptionsForRule(ruleIndex, currentLabelId = '') {
  return archiveRuleLabelOptions();
}

function labelRuleSenderOptions() {
  const sourceRows = Array.isArray(sendersDraft) && sendersDraft.length > 0
    ? sendersDraft.map(sanitizeSenderDraft)
    : (Array.isArray(state.senders) ? state.senders.map(sanitizeSenderDraft) : []);
  const seen = new Set();
  return sourceRows
    .map((sender) => ({
      value: Number.isInteger(sender && sender.id) && sender.id > 0 ? sender.id : null,
      label: typeof sender?.name === 'string' ? sender.name.trim() : '',
    }))
    .filter((sender) => sender.value !== null && sender.label !== '' && !seen.has(sender.value) && seen.add(sender.value))
    .sort((left, right) => left.label.localeCompare(right.label, 'sv', { sensitivity: 'base', numeric: true }));
}

function labelRuleFieldOptions() {
  const options = [];
  const seen = new Set();
  [...systemExtractionFieldsDraft, ...predefinedExtractionFieldsDraft, ...extractionFieldsDraft].forEach((field, index) => {
    const normalized = sanitizeExtractionField(field, index);
    const value = typeof normalized.key === 'string' ? normalized.key.trim() : '';
    const label = typeof normalized.name === 'string' ? normalized.name.trim() : '';
    if (!value || !label || seen.has(value)) {
      return;
    }
    seen.add(value);
    options.push({ value, label });
  });
  return options.sort((left, right) => left.label.localeCompare(right.label, 'sv', { sensitivity: 'base', numeric: true }));
}

function archiveStructureValidationError() {
  const duplicateIds = new Set();
  const allIds = new Map();
  const rememberId = (id, label) => {
    if (!id) {
      return;
    }
    if (allIds.has(id)) {
      duplicateIds.add(`${id} (${label})`);
      return;
    }
    allIds.set(id, label);
  };

  archiveFoldersDraft.map((folder, index) => sanitizeArchiveFolder(folder, index)).forEach((folder) => {
    rememberId(folder.id, 'mapp');
    if (!folder.name.trim()) {
      duplicateIds.add('__blank_folder_name__');
    }
    folder.filenameTemplates.forEach((template) => {
      rememberId(template.id, 'filnamnsregel');
    });
  });

  if (duplicateIds.has('__blank_folder_name__')) {
    return 'Alla mappar måste ha ett namn.';
  }
  const duplicateRealIds = Array.from(duplicateIds).filter((value) => !value.startsWith('__'));
  if (duplicateRealIds.length > 0) {
    return `Id krockar i arkivstrukturen: ${duplicateRealIds.join(', ')}`;
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

function labelsValidationErrorFor(labels, systemLabels = systemLabelsDraft) {
  const duplicates = duplicateLabelIds(labels, systemLabels);
  if (duplicates.size > 0) {
    return `Etikett-id krockar: ${Array.from(duplicates).join(', ')}`;
  }
  const blankLabel = labels.map(sanitizeLabel).find((label) => !label.name.trim() || !label.id.trim());
  if (blankLabel) {
    return 'Alla etiketter måste ha ett namn.';
  }
  for (const label of labels.map(sanitizeLabel)) {
    const labelName = typeof label.name === 'string' && label.name.trim() !== '' ? label.name.trim() : 'Namnlös etikett';
    for (const rule of Array.isArray(label.rules) ? label.rules.map(sanitizeLabelRule) : []) {
      if (rule.type === 'sender_is') {
        if (!Number.isInteger(rule.senderId) || rule.senderId < 1) {
          return `Etiketten "${labelName}" har en avsändarregel utan vald avsändare.`;
        }
      } else if (rule.type === 'field_exists') {
        if (typeof rule.field !== 'string' || rule.field.trim() === '') {
          return `Etiketten "${labelName}" har en fältregel utan valt datafält.`;
        }
      } else if (typeof rule.text !== 'string' || rule.text.trim() === '') {
        return `Etiketten "${labelName}" har en textregel utan text.`;
      }
    }
  }
  return '';
}

function labelsValidationError() {
  return labelsValidationErrorFor(labelsDraft, systemLabelsDraft);
}

function singleLabelValidationError(label, existingLabels = labelsDraft, systemLabels = systemLabelsDraft) {
  const sanitizedLabel = sanitizeLabel(label);
  if (!sanitizedLabel.name.trim() || !sanitizedLabel.id.trim()) {
    return 'Importerad etikett måste ha ett namn.';
  }

  const duplicateIds = duplicateLabelIds([sanitizedLabel, ...sanitizeLabels(existingLabels)], systemLabels);
  if (duplicateIds.has(sanitizedLabel.id)) {
    return `Etikett-id krockar: ${sanitizedLabel.id}`;
  }

  const labelName = sanitizedLabel.name.trim();
  for (const rule of Array.isArray(sanitizedLabel.rules) ? sanitizedLabel.rules.map(sanitizeLabelRule) : []) {
    if (rule.type === 'sender_is') {
      if (!Number.isInteger(rule.senderId) || rule.senderId < 1) {
        return `Etiketten "${labelName}" har en avsändarregel utan vald avsändare.`;
      }
    } else if (rule.type === 'field_exists') {
      if (typeof rule.field !== 'string' || rule.field.trim() === '') {
        return `Etiketten "${labelName}" har en fältregel utan valt datafält.`;
      }
    } else if (typeof rule.text !== 'string' || rule.text.trim() === '') {
      return `Etiketten "${labelName}" har en textregel utan text.`;
    }
  }

  return '';
}

function parseImportedLabelJson(text) {
  const source = typeof text === 'string' ? text.trim() : '';
  if (source === '') {
    return {
      error: 'Klistra in en etikett i JSON-format först.'
    };
  }

  let parsed = null;
  try {
    parsed = JSON.parse(source);
  } catch (error) {
    return {
      error: 'JSON kunde inte tolkas.'
    };
  }

  if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) {
    return {
      error: 'Importerad etikett måste vara ett JSON-objekt.'
    };
  }

  if (!Array.isArray(parsed.rules) || parsed.rules.length === 0) {
    return {
      error: 'Importerad etikett måste innehålla minst en regel.'
    };
  }

  const importedLabel = sanitizeLabel(parsed);
  const validationError = singleLabelValidationError(importedLabel, labelsDraft, systemLabelsDraft);
  if (validationError) {
    return {
      error: validationError
    };
  }

  return {
    label: importedLabel
  };
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

function normalizeLabelIdList(values) {
  if (!Array.isArray(values)) {
    return [];
  }

  const seen = new Set();
  const normalized = [];
  values.forEach((value) => {
    const labelId = typeof value === 'string' ? value.trim() : '';
    if (!labelId || seen.has(labelId)) {
      return;
    }
    seen.add(labelId);
    normalized.push(labelId);
  });
  return normalized;
}

function sanitizeIfLabelsMode(value) {
  return value === 'all' ? 'all' : 'any';
}

function sanitizeFilenameTemplatePart(part, depth = 0) {
  const input = part && typeof part === 'object' ? part : null;
  if (!input || depth > 6) {
    return null;
  }

  const type = typeof input.type === 'string' ? input.type.trim() : 'text';
  const prefixParts = sanitizeFilenameTemplateParts(input.prefixParts, depth + 1);
  const suffixParts = sanitizeFilenameTemplateParts(input.suffixParts, depth + 1);
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
  if (type === 'folder') {
    return {
      type: 'folder',
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
  if (type === 'ifLabels') {
    return {
      type: 'ifLabels',
      mode: sanitizeIfLabelsMode(input.mode),
      labelIds: normalizeLabelIdList(input.labelIds),
      thenParts: sanitizeFilenameTemplateParts(input.thenParts, depth + 1),
      elseParts: sanitizeFilenameTemplateParts(input.elseParts, depth + 1),
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
  if (type === 'folder') {
    return {
      type: 'folder',
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
  if (type === 'ifLabels') {
    return {
      type: 'ifLabels',
      mode: 'any',
      labelIds: [],
      thenParts: [],
      elseParts: [],
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
    ruleSets: [defaultExtractionFieldRuleSet()],
  };
}

function defaultExtractionFieldRuleSet() {
  return {
    type: 'regex',
    useSearchText: true,
    requiresSearchTerms: true,
    searchTerms: [{ text: '', isRegex: false }],
    isRegex: false,
    valuePattern: '',
    normalizationType: 'none',
    normalizationChars: '',
    normalizationReplacements: [],
    datePosition: 'first',
    amountPosition: 'first',
  };
}

function sanitizeExtractionFieldAliasesInput(aliases, legacyFallback = '') {
  const values = Array.isArray(aliases)
    ? aliases
    : ((typeof aliases === 'string' || typeof aliases === 'number') ? [String(aliases)] : []);
  const normalized = [];
  const seen = new Set();

  values.forEach((value) => {
    const alias = String(value || '').trim();
    if (!alias) {
      return;
    }
    const dedupeKey = alias.toLowerCase();
    if (seen.has(dedupeKey)) {
      return;
    }
    seen.add(dedupeKey);
    normalized.push(alias);
  });

  const fallback = String(legacyFallback || '').trim();
  if (normalized.length === 0 && fallback) {
    normalized.push(fallback);
  }

  return normalized;
}

function sanitizeExtractionFieldNormalizationType(value) {
  const normalized = String(value || '').trim().toLowerCase();
  return normalized === 'whitelist' || normalized === 'blacklist' || normalized === 'replacements' ? normalized : 'none';
}

function defaultExtractionFieldNormalizationReplacement() {
  return {
    find: '',
    replace: '',
    isRegex: false,
  };
}

function sanitizeExtractionFieldNormalizationReplacement(row) {
  const input = row && typeof row === 'object' ? row : {};
  const find = typeof input.find === 'string'
    ? input.find.trim()
    : (typeof input.from === 'string' ? input.from.trim() : '');
  if (!find) {
    return null;
  }
  return {
    find,
    replace: typeof input.replace === 'string'
      ? input.replace
      : (typeof input.to === 'string' ? input.to : ''),
    isRegex: input.isRegex === true,
  };
}

function sanitizeExtractionFieldNormalizationReplacementsInput(replacements) {
  const values = Array.isArray(replacements) ? replacements : [];
  const normalized = [];
  values.forEach((row) => {
    const replacement = sanitizeExtractionFieldNormalizationReplacement(row);
    if (replacement) {
      normalized.push(replacement);
    }
  });
  return normalized;
}

function sanitizeExtractionFieldType(value, legacyField = null) {
  const normalized = String(value || '').trim().toLowerCase();
  if (normalized === 'regex' || normalized === 'date' || normalized === 'amount') {
    return normalized;
  }

  const legacy = legacyField && typeof legacyField === 'object' ? legacyField : {};
  const legacyKey = String(legacy.predefinedFieldKey || legacy.systemFieldKey || legacy.key || '').trim();
  const legacyExtractor = String(legacy.extractor || '').trim().toLowerCase();
  if (legacyKey === 'amount' || legacyExtractor === 'amount') {
    return 'amount';
  }
  if (legacyKey === 'due_date' || legacyKey === 'document_date' || legacyExtractor === 'due_date' || legacyExtractor === 'document_date') {
    return 'date';
  }
  return 'regex';
}

function sanitizeExtractionFieldPosition(value) {
  const normalized = String(value || '').trim().toLowerCase();
  return normalized === 'second' || normalized === 'last' ? normalized : 'first';
}

function sanitizeExtractionFieldSearchTerm(term, legacyIsRegex = false) {
  if (term && typeof term === 'object' && !Array.isArray(term)) {
    const text = String(term.text || term.value || '').trim();
    if (!text) {
      return null;
    }
    return {
      text,
      isRegex: term.isRegex === true,
    };
  }

  if (typeof term === 'string' || typeof term === 'number') {
    const text = String(term || '').trim();
    if (!text) {
      return null;
    }
    return {
      text,
      isRegex: legacyIsRegex === true,
    };
  }

  return null;
}

function sanitizeExtractionFieldSearchTermsInput(searchTerms, legacyFallback = '', legacyIsRegex = false) {
  const values = Array.isArray(searchTerms)
    ? searchTerms
    : ((typeof searchTerms === 'string' || typeof searchTerms === 'number') ? [searchTerms] : []);
  const normalized = [];
  const seen = new Set();

  values.forEach((value) => {
    const searchTerm = sanitizeExtractionFieldSearchTerm(value, legacyIsRegex);
    if (!searchTerm) {
      return;
    }
    const dedupeKey = `${searchTerm.text.toLowerCase()}|${searchTerm.isRegex ? '1' : '0'}`;
    if (seen.has(dedupeKey)) {
      return;
    }
    seen.add(dedupeKey);
    normalized.push(searchTerm);
  });

  const fallback = sanitizeExtractionFieldSearchTerm(legacyFallback, legacyIsRegex);
  if (normalized.length === 0 && fallback) {
    normalized.push(fallback);
  }

  return normalized;
}

function sanitizeExtractionFieldRuleSet(ruleSet, legacyField = null) {
  const input = ruleSet && typeof ruleSet === 'object' ? ruleSet : {};
  const legacy = legacyField && typeof legacyField === 'object' ? legacyField : {};
  const hasExplicitRuleSet = ruleSet && typeof ruleSet === 'object';
  const legacySearchString = typeof legacy.searchString === 'string'
    ? legacy.searchString
    : (typeof legacy.query === 'string' ? legacy.query : '');
  const legacyAliasesFallback = !Array.isArray(legacy.aliases) && String(legacySearchString || '').trim() !== ''
    ? legacySearchString
    : '';
  const legacyIsRegex = hasExplicitRuleSet ? input.isRegex === true : legacy.isRegex === true;
  const searchTerms = sanitizeExtractionFieldSearchTermsInput(
    hasExplicitRuleSet ? input.searchTerms : legacy.aliases,
    hasExplicitRuleSet ? '' : legacyAliasesFallback,
    legacyIsRegex
  );
  const type = sanitizeExtractionFieldType(hasExplicitRuleSet ? input.type : legacy.type, legacy);
  const useSearchText = hasExplicitRuleSet
    ? input.useSearchText !== false && input.requiresSearchTerms !== false
    : searchTerms.length > 0;
  const isDateType = type === 'date';
  const valuePattern = isDateType
    ? undefined
    : (
      typeof input.valuePattern === 'string'
        ? input.valuePattern
        : (typeof input.searchString === 'string' ? input.searchString : '')
    );
  const normalizationType = isDateType
    ? undefined
    : sanitizeExtractionFieldNormalizationType(
      hasExplicitRuleSet ? input.normalizationType : legacy.normalizationType
    );
  const normalizationReplacements = isDateType
    ? undefined
    : sanitizeExtractionFieldNormalizationReplacementsInput(
      hasExplicitRuleSet ? input.normalizationReplacements : legacy.normalizationReplacements
    );

  return {
    type,
    useSearchText,
    requiresSearchTerms: useSearchText,
    searchTerms,
    isRegex: false,
    valuePattern,
    normalizationType,
    normalizationChars: isDateType
      ? undefined
      : (typeof (hasExplicitRuleSet ? input.normalizationChars : legacy.normalizationChars) === 'string'
        ? (hasExplicitRuleSet ? input.normalizationChars : legacy.normalizationChars)
        : ''),
    normalizationReplacements,
    datePosition: sanitizeExtractionFieldPosition(hasExplicitRuleSet ? input.datePosition : legacy.datePosition),
    amountPosition: isDateType ? undefined : sanitizeExtractionFieldPosition(hasExplicitRuleSet ? input.amountPosition : legacy.amountPosition),
  };
}

function sanitizeExtractionFieldRuleSets(ruleSets, legacyField = null) {
  const normalized = Array.isArray(ruleSets)
    ? ruleSets
      .map((ruleSet) => sanitizeExtractionFieldRuleSet(ruleSet, legacyField))
      .filter((ruleSet) => ruleSet && typeof ruleSet === 'object')
    : [];
  if (normalized.length > 0) {
    return normalized;
  }
  return [sanitizeExtractionFieldRuleSet(null, legacyField)];
}

function extractionFieldSearchTermsForEditor(ruleSet) {
  const sanitized = sanitizeExtractionFieldRuleSet(ruleSet);
  const searchTerms = Array.isArray(sanitized.searchTerms) ? [...sanitized.searchTerms] : [];
  const lastSearchTerm = searchTerms[searchTerms.length - 1] || null;
  if (!lastSearchTerm || String(lastSearchTerm.text || '').trim() !== '') {
    searchTerms.push({ text: '', isRegex: false });
  }
  return searchTerms;
}

function sanitizeExtractionField(field, fallbackIndex = 0) {
  const input = field && typeof field === 'object' ? field : {};
  const name = typeof input.name === 'string' ? input.name : '';
  const extractor = typeof input.extractor === 'string' && input.extractor.trim() !== ''
    ? input.extractor.trim()
    : 'generic_label';
  const isPredefinedField = input.isPredefinedField === true;
  const isSystemField = input.isSystemField === true
    || (typeof input.systemFieldKey === 'string' && input.systemFieldKey.trim() !== '');
  const derivedKey = normalizeConfigKey(name || `field_${fallbackIndex + 1}`);
  const storedKey = typeof input.key === 'string' && input.key.trim() !== ''
    ? input.key.trim()
    : '';
  const normalizedKey = storedKey !== ''
    ? ((!isSystemField && !isPredefinedField && storedKey.length <= 1 && derivedKey.length > 1 && storedKey !== derivedKey) ? derivedKey : storedKey)
    : derivedKey;

  if (isSystemField) {
    let searchString = typeof input.searchString === 'string'
      ? input.searchString
      : (typeof input.query === 'string' ? input.query : '');
    const legacyAliasFallback = extractor === 'generic_label'
      && !Array.isArray(input.aliases)
      && String(searchString || '').trim() !== ''
      ? searchString
      : '';
    const aliases = sanitizeExtractionFieldAliasesInput(input.aliases, legacyAliasFallback);
    if (legacyAliasFallback) {
      searchString = '';
    }
    return {
      key: normalizedKey,
      name,
      aliases,
      searchString,
      isRegex: input.isRegex === true,
      normalizationType: sanitizeExtractionFieldNormalizationType(input.normalizationType),
      normalizationChars: typeof input.normalizationChars === 'string' ? input.normalizationChars : '',
      extractor,
      predefinedFieldKey: typeof input.predefinedFieldKey === 'string' ? input.predefinedFieldKey : '',
      isPredefinedField,
      systemFieldKey: typeof input.systemFieldKey === 'string' ? input.systemFieldKey : '',
      isSystemField: input.isSystemField === true,
    };
  }

  return {
    key: normalizedKey,
    name,
    type: sanitizeExtractionFieldType(
      typeof input.type === 'string'
        ? input.type
        : (Array.isArray(input.ruleSets) && input.ruleSets[0] && typeof input.ruleSets[0].type === 'string'
          ? input.ruleSets[0].type
          : ''),
      input
    ),
    ruleSets: sanitizeExtractionFieldRuleSets(input.ruleSets, input),
    extractor,
    predefinedFieldKey: typeof input.predefinedFieldKey === 'string' ? input.predefinedFieldKey : '',
    isPredefinedField,
    systemFieldKey: typeof input.systemFieldKey === 'string' ? input.systemFieldKey : '',
    isSystemField: input.isSystemField === true,
  };
}

function syncExtractionFieldAliasInputSize(input, accessoryCount = 0) {
  if (!(input instanceof HTMLInputElement)) {
    return;
  }
  const sample = String(input.value || input.placeholder || '');
  const contentLength = Array.from(sample).length;
  const accessoryChars = Math.max(0, Number(accessoryCount) || 0);
  input.size = Math.max(12, Math.min(40, contentLength + 1 + accessoryChars));
}

function sanitizeExtractionFields(fields) {
  return Array.isArray(fields) ? fields.map((field, index) => sanitizeExtractionField(field, index)) : [];
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
  const options = sanitizeExtractionFields(systemExtractionFieldsDraft)
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

  [
    {
      key: 'bankgiro_name',
      label: 'Bankgiro-namn',
      tone: 'system',
      title: 'Lägger till namnet som är kopplat till dokumentets bankgiro.',
    },
    {
      key: 'plusgiro_name',
      label: 'Plusgiro-namn',
      tone: 'system',
      title: 'Lägger till namnet som är kopplat till dokumentets plusgiro.',
    },
    {
      key: 'organization_number_name',
      label: 'Org.nr.-namn',
      tone: 'system',
      title: 'Lägger till namnet som är kopplat till dokumentets organisationsnummer.',
    },
  ].forEach((option) => {
    if (seenKeys.has(option.key)) {
      return;
    }
    seenKeys.add(option.key);
    options.push(option);
  });

  return options;
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

function extractionFieldAddOptions() {
  return filenameTemplateDataFieldOptions()
    .slice()
    .sort((left, right) => {
      const leftLabel = typeof left.label === 'string' ? left.label : '';
      const rightLabel = typeof right.label === 'string' ? right.label : '';
      if (leftLabel !== rightLabel) {
        return leftLabel.localeCompare(rightLabel, 'sv');
      }
      return left.key.localeCompare(right.key, 'sv');
    });
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

let filenameTemplateLabelPickerCounter = 0;

function createFilenameTemplateLabelPicker(selectedLabelIds, onChange, options = {}) {
  filenameTemplateLabelPickerCounter += 1;
  const optionIdPrefix = `filename-template-label-picker-${filenameTemplateLabelPickerCounter}`;
  const state = {
    filterText: '',
    dropdownOpen: false,
    activeOptionIndex: -1,
    selectedIds: normalizeLabelIdList(selectedLabelIds),
  };
  const placeholder = typeof options.placeholder === 'string' && options.placeholder.trim() !== ''
    ? options.placeholder.trim()
    : 'Lägg till etikett...';

  const wrapper = document.createElement('div');
  wrapper.className = 'filename-template-label-picker';

  const flow = document.createElement('div');
  flow.className = 'filename-template-label-picker-flow';

  const combobox = document.createElement('div');
  combobox.className = 'job-labels-combobox filename-template-label-picker-combobox';

  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'job-labels-combobox-input filename-template-label-picker-input';
  input.placeholder = placeholder;
  input.autocomplete = 'off';
  input.setAttribute('aria-label', placeholder);

  const list = document.createElement('div');
  list.className = 'job-labels-combobox-list filename-template-label-picker-list';
  list.setAttribute('role', 'listbox');

  const selected = document.createElement('div');
  selected.className = 'filename-template-label-picker-selected';
  let listPortalMounted = false;
  let detachFloatingListeners = null;

  const positionFloatingList = () => {
    if (!(document.body instanceof HTMLElement) || !listPortalMounted) {
      return;
    }
    const rect = combobox.getBoundingClientRect();
    list.style.position = 'fixed';
    list.style.top = `${Math.round(rect.bottom + 6)}px`;
    list.style.left = `${Math.round(rect.left)}px`;
    list.style.right = 'auto';
    list.style.bottom = 'auto';
    list.style.minWidth = `${Math.max(Math.round(rect.width), 260)}px`;
    list.style.maxWidth = `min(420px, calc(100vw - 48px))`;
    list.style.zIndex = '1000';
  };

  const mountFloatingList = () => {
    if (!(document.body instanceof HTMLElement)) {
      return;
    }
    if (!listPortalMounted) {
      document.body.appendChild(list);
      listPortalMounted = true;
    }
    positionFloatingList();
    if (!detachFloatingListeners) {
      const reposition = () => {
        if (state.dropdownOpen) {
          positionFloatingList();
        }
      };
      window.addEventListener('resize', reposition);
      window.addEventListener('scroll', reposition, true);
      detachFloatingListeners = () => {
        window.removeEventListener('resize', reposition);
        window.removeEventListener('scroll', reposition, true);
      };
    }
  };

  const unmountFloatingList = () => {
    if (detachFloatingListeners) {
      detachFloatingListeners();
      detachFloatingListeners = null;
    }
    if (listPortalMounted && list.parentElement) {
      list.parentElement.removeChild(list);
    }
    listPortalMounted = false;
  };

  const filteredOptions = () => {
    const normalizedFilter = state.filterText.trim().toLocaleLowerCase('sv');
    const selectedSet = new Set(state.selectedIds);
    return filenameTemplateLabelDefinitions().filter((option) => {
      if (selectedSet.has(option.id)) {
        return false;
      }
      if (normalizedFilter === '') {
        return true;
      }
      return option.name.toLocaleLowerCase('sv').includes(normalizedFilter)
        || option.id.toLocaleLowerCase('sv').includes(normalizedFilter);
    });
  };

  const syncActiveDescendant = (optionsList) => {
    if (state.activeOptionIndex >= 0 && state.activeOptionIndex < optionsList.length) {
      input.setAttribute('aria-activedescendant', `${optionIdPrefix}-option-${state.activeOptionIndex}`);
      return;
    }
    input.removeAttribute('aria-activedescendant');
  };

  const commitSelection = (labelId) => {
    const nextIds = normalizeLabelIdList([...state.selectedIds, labelId]);
    state.selectedIds = nextIds;
    state.filterText = '';
    input.value = '';
    state.dropdownOpen = false;
    state.activeOptionIndex = -1;
    if (typeof onChange === 'function') {
      onChange(nextIds);
    }
    render();
  };

  const render = () => {
    const optionsList = filteredOptions();
    if (optionsList.length < 1) {
      state.activeOptionIndex = -1;
    } else if (state.activeOptionIndex < 0 || state.activeOptionIndex >= optionsList.length) {
      state.activeOptionIndex = 0;
    }

    selected.replaceChildren();
    selected.classList.toggle('is-empty', state.selectedIds.length < 1);
    state.selectedIds.forEach((labelId) => {
      const chipEl = document.createElement('span');
      chipEl.className = 'job-labels-selected-chip';

      const textEl = document.createElement('span');
      textEl.className = 'job-labels-selected-chip-text';
      textEl.textContent = filenameTemplateLabelNameById(labelId);

      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'job-labels-selected-chip-remove';
      removeButton.setAttribute('aria-label', `Ta bort etiketten ${filenameTemplateLabelNameById(labelId)}`);
      removeButton.textContent = '✕';
      removeButton.addEventListener('mousedown', (event) => {
        event.preventDefault();
        event.stopPropagation();
      });
      removeButton.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        state.selectedIds = state.selectedIds.filter((candidate) => candidate !== labelId);
        if (typeof onChange === 'function') {
          onChange(state.selectedIds);
        }
        render();
      });

      chipEl.append(textEl, removeButton);
      selected.appendChild(chipEl);
    });

    list.replaceChildren();
    list.classList.toggle('is-open', state.dropdownOpen);
    if (state.dropdownOpen) {
      mountFloatingList();
      if (optionsList.length < 1) {
        const emptyEl = document.createElement('div');
        emptyEl.className = 'job-labels-combobox-empty';
        emptyEl.textContent = state.filterText.trim() === '' ? 'Inga fler etiketter.' : 'Ingen träff.';
        list.appendChild(emptyEl);
      } else {
        optionsList.forEach((option, index) => {
          const optionButton = document.createElement('button');
          optionButton.type = 'button';
          optionButton.className = 'job-labels-combobox-option';
          optionButton.id = `${optionIdPrefix}-option-${index}`;
          optionButton.textContent = option.name;
          optionButton.setAttribute('role', 'option');
          optionButton.setAttribute('aria-selected', index === state.activeOptionIndex ? 'true' : 'false');
          optionButton.classList.toggle('is-active', index === state.activeOptionIndex);
          optionButton.tabIndex = -1;
          optionButton.addEventListener('mousedown', (event) => {
            event.preventDefault();
          });
          optionButton.addEventListener('mouseenter', () => {
            state.activeOptionIndex = index;
            syncActiveDescendant(optionsList);
            Array.from(list.querySelectorAll('.job-labels-combobox-option')).forEach((node, nodeIndex) => {
              const isActive = nodeIndex === state.activeOptionIndex;
              node.classList.toggle('is-active', isActive);
              node.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });
          });
          optionButton.addEventListener('click', () => {
            commitSelection(option.id);
          });
          list.appendChild(optionButton);
        });
      }
      positionFloatingList();
    } else {
      unmountFloatingList();
    }

    syncActiveDescendant(optionsList);
  };

  input.addEventListener('focus', () => {
    state.dropdownOpen = true;
    render();
  });
  input.addEventListener('click', () => {
    state.dropdownOpen = true;
    render();
  });
  input.addEventListener('input', () => {
    state.filterText = input.value;
    state.dropdownOpen = true;
    render();
  });
  input.addEventListener('keydown', (event) => {
    const optionsList = filteredOptions();
    if (event.key === 'ArrowDown') {
      if (!state.dropdownOpen) {
        state.dropdownOpen = true;
      } else if (optionsList.length > 0) {
        state.activeOptionIndex = state.activeOptionIndex >= optionsList.length - 1 ? 0 : state.activeOptionIndex + 1;
      }
      event.preventDefault();
      render();
      return;
    }
    if (event.key === 'ArrowUp') {
      if (!state.dropdownOpen) {
        state.dropdownOpen = true;
      } else if (optionsList.length > 0) {
        state.activeOptionIndex = state.activeOptionIndex <= 0 ? optionsList.length - 1 : state.activeOptionIndex - 1;
      }
      event.preventDefault();
      render();
      return;
    }
    if (event.key === 'Enter') {
      if (state.dropdownOpen && optionsList.length > 0 && state.activeOptionIndex >= 0) {
        event.preventDefault();
        commitSelection(optionsList[state.activeOptionIndex].id);
      }
      return;
    }
    if (event.key === 'Escape') {
      state.dropdownOpen = false;
      state.activeOptionIndex = -1;
      render();
      event.stopPropagation();
    }
  });
  wrapper.addEventListener('focusout', () => {
    window.requestAnimationFrame(() => {
      if (!wrapper.contains(document.activeElement)) {
        state.dropdownOpen = false;
        state.activeOptionIndex = -1;
        render();
      }
    });
  });
  flow.addEventListener('click', (event) => {
    if (event.target instanceof HTMLElement && event.target.closest('button')) {
      return;
    }
    input.focus();
  });

  combobox.appendChild(input);
  flow.append(selected, combobox);
  wrapper.appendChild(flow);
  render();
  return wrapper;
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
    {
      type: 'ifLabels',
      label: 'Om etikett',
      tone: 'special',
      title: 'Renderar olika innehåll beroende på om någon av de valda etiketterna finns.',
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

function setRegexToggleButtonState(buttonEl, isActive) {
  if (!(buttonEl instanceof HTMLButtonElement)) {
    return;
  }
  buttonEl.classList.toggle('is-active', isActive);
  buttonEl.setAttribute('aria-pressed', isActive ? 'true' : 'false');
}

function createRegexToggleButton(options = {}) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'inline-regex-toggle';
  button.textContent = '.*';
  button.title = typeof options.title === 'string' && options.title.trim() !== '' ? options.title.trim() : 'Regex';
  button.setAttribute('aria-label', button.title);

  const getActive = typeof options.getActive === 'function'
    ? options.getActive
    : () => options.isActive === true;
  const setActive = typeof options.setActive === 'function' ? options.setActive : null;

  const sync = () => {
    setRegexToggleButtonState(button, getActive() === true);
  };

  button.addEventListener('click', (event) => {
    event.preventDefault();
    const next = !(getActive() === true);
    if (setActive) {
      setActive(next);
    }
    sync();
  });

  sync();
  button.syncState = sync;
  return button;
}

function createInlineInputWithAccessories(inputEl, accessories = [], extraClass = '') {
  const wrapper = document.createElement('div');
  wrapper.className = 'inline-input-wrap' + (extraClass ? ' ' + extraClass : '');
  inputEl.classList.add('inline-input-control');

  const accessoryWrap = document.createElement('div');
  accessoryWrap.className = 'inline-input-accessories';

  accessories.forEach((accessory) => {
    if (!(accessory instanceof HTMLElement)) {
      return;
    }
    accessoryWrap.appendChild(accessory);
  });

  const accessoryCount = accessoryWrap.childElementCount;
  const accessoriesWidth = accessoryCount > 0
    ? 8 + (accessoryCount * 24) + ((accessoryCount - 1) * 4)
    : 0;
  wrapper.style.setProperty('--inline-input-accessories-width', `${accessoriesWidth}px`);

  wrapper.appendChild(inputEl);
  wrapper.appendChild(accessoryWrap);
  return wrapper;
}

function createRegexToggleInput(inputEl, options = {}, extraClass = '') {
  const regexButton = createRegexToggleButton(options);
  const wrapper = createInlineInputWithAccessories(inputEl, [regexButton], extraClass);
  return {
    wrapper,
    button: regexButton,
  };
}

if (ocrSearchBarEl instanceof HTMLElement && ocrSearchInputEl instanceof HTMLInputElement && ocrSearchRegexEl instanceof HTMLInputElement) {
  const { wrapper: ocrSearchInputWrap, button: ocrSearchRegexButton } = createRegexToggleInput(ocrSearchInputEl, {
    getActive: () => ocrSearchRegexEl.checked === true,
    setActive: (next) => {
      ocrSearchRegexEl.checked = next === true;
      refreshOcrSearch();
    },
  }, 'ocr-search-input-wrap');
  ocrSearchBarEl.insertBefore(ocrSearchInputWrap, ocrSearchPrevEl);
  ocrSearchRegexEl.addEventListener('change', () => {
    if (typeof ocrSearchRegexButton.syncState === 'function') {
      ocrSearchRegexButton.syncState();
    }
  });
}

function createTreeRow(options = {}) {
  const row = document.createElement('div');
  row.className = 'tree-row';
  if (options.markerless) {
    row.classList.add('tree-row-no-marker');
  }
  return row;
}

function syncTreeChildrenConnector(children) {
  if (!(children instanceof HTMLElement)) {
    return;
  }

  const childrenRect = children.getBoundingClientRect();
  const childNodes = Array.from(children.children).filter((child) => (
    child instanceof HTMLElement
    && child.classList.contains('tree-node')
    && child.classList.contains('has-parent')
  ));
  const childHeading = Array.from(children.children).find((child) => (
    child instanceof HTMLElement
    && child.classList.contains('archive-level-label')
  )) || null;
  const siblingHeading = children.previousElementSibling instanceof HTMLElement
    && children.previousElementSibling.classList.contains('archive-level-label')
    ? children.previousElementSibling
    : null;
  const heading = childHeading || siblingHeading;
  const lastNode = childNodes[childNodes.length - 1] || null;
  const lastRow = lastNode
    ? Array.from(lastNode.children).find((child) => child instanceof HTMLElement && child.classList.contains('tree-row')) || null
    : null;
  if (
    !(heading instanceof HTMLElement)
    || !(lastNode instanceof HTMLElement)
    || !(lastRow instanceof HTMLElement)
  ) {
    children.style.setProperty('--tree-connector-top', '0px');
    children.style.setProperty('--tree-connector-height', '0px');
    return;
  }

  const headingRect = heading.getBoundingClientRect();
  const lastRowRect = lastRow.getBoundingClientRect();
  const headingConnectorY = headingRect.bottom - childrenRect.top;
  const lastConnectorY = lastRowRect.top - childrenRect.top + 17;
  children.style.setProperty('--tree-connector-top', `${Math.round(headingConnectorY)}px`);
  children.style.setProperty('--tree-connector-height', `${Math.max(0, Math.round(lastConnectorY - headingConnectorY))}px`);
}

function scheduleTreeChildrenConnectorSync(children) {
  if (!(children instanceof HTMLElement)) {
    return;
  }
  if (Number.isInteger(children._treeConnectorFrame)) {
    window.cancelAnimationFrame(children._treeConnectorFrame);
  }
  children._treeConnectorFrame = window.requestAnimationFrame(() => {
    children._treeConnectorFrame = null;
    syncTreeChildrenConnector(children);
  });
}

function createTreeChildren(options = {}) {
  const children = document.createElement('div');
  children.className = 'tree-children';
  if (options.markerless) {
    children.classList.add('tree-children-markerless');
  }
  const observer = new MutationObserver(() => {
    scheduleTreeChildrenConnectorSync(children);
  });
  observer.observe(children, { childList: true, subtree: true });
  children._treeConnectorObserver = observer;
  if (typeof ResizeObserver === 'function') {
    const resizeObserver = new ResizeObserver(() => {
      scheduleTreeChildrenConnectorSync(children);
    });
    resizeObserver.observe(children);
    children._treeConnectorResizeObserver = resizeObserver;
  }
  scheduleTreeChildrenConnectorSync(children);
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
    firstNameInput.placeholder = 'Ex: Johan Petter';
    firstNameInput.value = row.firstName || '';
    firstNameInput.addEventListener('input', () => {
      clientsDraft[rowIndex].firstName = firstNameInput.value;
      updateSettingsActionButtons();
    });

    const preferredFirstNameSelect = document.createElement('select');
    const syncPreferredFirstNameOptions = (preferredName = null) => {
      const parts = splitClientFirstNames(firstNameInput.value);
      const currentDraft = clientsDraft[rowIndex];
      let nextIndex = null;
      if (typeof preferredName === 'string' && preferredName.trim() !== '') {
        const matchIndex = parts.findIndex((part) => part === preferredName.trim());
        nextIndex = matchIndex >= 0 ? matchIndex : null;
      } else {
        nextIndex = normalizePreferredFirstNameIndex(currentDraft.preferredFirstNameIndex, parts);
      }
      currentDraft.preferredFirstNameIndex = nextIndex;

      preferredFirstNameSelect.innerHTML = '';
      const placeholderOption = document.createElement('option');
      placeholderOption.value = '';
      placeholderOption.textContent = 'Välj tilltalsnamn';
      preferredFirstNameSelect.appendChild(placeholderOption);
      parts.forEach((part, partIndex) => {
        const option = document.createElement('option');
        option.value = String(partIndex);
        option.textContent = part;
        preferredFirstNameSelect.appendChild(option);
      });
      preferredFirstNameSelect.disabled = parts.length < 1;
      preferredFirstNameSelect.value = nextIndex === null ? '' : String(nextIndex);
    };
    preferredFirstNameSelect.addEventListener('change', () => {
      clientsDraft[rowIndex].preferredFirstNameIndex = normalizePreferredFirstNameIndex(
        preferredFirstNameSelect.value,
        firstNameInput.value
      );
      updateSettingsActionButtons();
    });
    firstNameInput.addEventListener('blur', () => {
      const selectedOption = preferredFirstNameSelect.selectedOptions[0] || null;
      const previousPreferredName = selectedOption && preferredFirstNameSelect.value !== ''
        ? selectedOption.textContent
        : preferredFirstNameForClientRow(clientsDraft[rowIndex]);
      syncPreferredFirstNameOptions(previousPreferredName || null);
      updateSettingsActionButtons();
    });
    syncPreferredFirstNameOptions(preferredFirstNameForClientRow(row) || null);

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
    fields.appendChild(createFloatingField('Tilltalsnamn', preferredFirstNameSelect));
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

function focusClientDraftRow(clientUiKeyValue) {
  if (!clientsListEl || typeof clientUiKeyValue !== 'string' || clientUiKeyValue.trim() === '') {
    return;
  }

  requestAnimationFrame(() => {
    const clientNode = clientsListEl.querySelector(`[data-client-ui-key="${CSS.escape(clientUiKeyValue)}"]`);
    if (!(clientNode instanceof HTMLElement)) {
      return;
    }
    const firstInput = clientNode.querySelector('input[type="text"], select');
    if (firstInput instanceof HTMLElement) {
      firstInput.focus();
      if ('select' in firstInput && typeof firstInput.select === 'function') {
        firstInput.select();
      }
      return;
    }
    clientNode.scrollIntoView({ block: 'nearest' });
  });
}

function renderMatchingEditor() {
  matchingListEl.innerHTML = '';
  syncMatchingPositionAdjustmentInputs();

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

function syncMatchingPositionAdjustmentInputs() {
  if (matchingNoisePenaltyEl) {
    matchingNoisePenaltyEl.value = formatMatchingPercentInput(matchingPositionAdjustmentDraft.noisePenaltyPerCharacter, 1);
  }
  if (matchingTrailingDelimiterPenaltyEl) {
    matchingTrailingDelimiterPenaltyEl.value = formatMatchingPercentInput(matchingPositionAdjustmentDraft.trailingDelimiterPenalty, null);
  }
  if (matchingOtherMatchKeyPenaltyEl) {
    matchingOtherMatchKeyPenaltyEl.value = formatMatchingPercentInput(matchingPositionAdjustmentDraft.otherMatchKeyPenalty, null);
  }
  if (matchingRightYOffsetPenaltyEl) {
    matchingRightYOffsetPenaltyEl.value = formatMatchingPercentInput(matchingPositionAdjustmentDraft.rightYOffsetPenalty, null);
  }
  if (matchingDownXOffsetPenaltyEl) {
    matchingDownXOffsetPenaltyEl.value = formatMatchingPercentInput(matchingPositionAdjustmentDraft.downXOffsetPenalty, null);
  }
  if (matchingDataFieldAcceptanceThresholdEl) {
    matchingDataFieldAcceptanceThresholdEl.value = formatMatchingPercentInput(matchingDataFieldAcceptanceThresholdDraft, null);
  }
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

  const organizationList = createTreeChildren({ markerless: true });

  const organizationsLabel = document.createElement('div');
  organizationsLabel.className = 'archive-level-label';
  organizationsLabel.textContent = 'Organisationsnummer';
  organizationList.appendChild(organizationsLabel);

  row.organizationNumbers.forEach((organization, organizationIndex) => {
    const organizationNode = document.createElement('div');
    organizationNode.className = 'tree-node tree-category has-parent';

    const organizationRowEl = createTreeRow({ markerless: true });

    const organizationBody = document.createElement('div');
    organizationBody.className = 'tree-body category-body';
    appendTreeBodyIcon(organizationBody, 'tree-body-icon sender-organization-icon');

    const organizationFields = document.createElement('div');
    organizationFields.className = 'sender-organization-fields';

    const organizationNumberInput = document.createElement('input');
    organizationNumberInput.type = 'text';
    organizationNumberInput.placeholder = 'Ex: 212000-1850';
    organizationNumberInput.value = organization.organizationNumber;
    organizationNumberInput.addEventListener('input', () => {
      sendersDraft[rowIndex].organizationNumbers[organizationIndex].organizationNumber = organizationNumberInput.value;
      updateSettingsActionButtons();
    });

    const organizationNameInput = document.createElement('input');
    organizationNameInput.type = 'text';
    organizationNameInput.placeholder = 'Observerat namn';
    organizationNameInput.value = organization.organizationName;
    organizationNameInput.addEventListener('input', () => {
      sendersDraft[rowIndex].organizationNumbers[organizationIndex].organizationName = organizationNameInput.value;
      updateSettingsActionButtons();
    });

    const removeOrganizationButton = document.createElement('button');
    removeOrganizationButton.type = 'button';
    removeOrganizationButton.className = 'category-remove';
    removeOrganizationButton.textContent = 'Ta bort';
    removeOrganizationButton.addEventListener('click', () => {
      sendersDraft[rowIndex].organizationNumbers.splice(organizationIndex, 1);
      renderSendersEditor();
      updateSettingsActionButtons();
    });

    organizationFields.appendChild(createFloatingField('Org.nr', organizationNumberInput));
    organizationFields.appendChild(createFloatingField('Namn', organizationNameInput));
    organizationFields.appendChild(removeOrganizationButton);
    organizationBody.appendChild(organizationFields);
    organizationRowEl.appendChild(organizationBody);
    organizationNode.appendChild(organizationRowEl);
    organizationList.appendChild(organizationNode);
  });

  senderDetails.appendChild(organizationList);

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
  const addOrganizationButton = document.createElement('button');
  addOrganizationButton.type = 'button';
  addOrganizationButton.textContent = 'Lägg till organisationsnummer';
  addOrganizationButton.addEventListener('click', () => {
    sendersDraft[rowIndex].organizationNumbers.push(defaultSenderOrganizationDraft());
    renderSendersEditor();
    updateSettingsActionButtons();
  });
  const addPaymentButton = document.createElement('button');
  addPaymentButton.type = 'button';
  addPaymentButton.textContent = 'Lägg till betalnummer';
  addPaymentButton.addEventListener('click', () => {
    sendersDraft[rowIndex].paymentNumbers.push(defaultSenderPaymentDraft());
    renderSendersEditor();
    updateSettingsActionButtons();
  });
  senderActions.appendChild(addOrganizationButton);
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

function renderUnlinkedSenderIdentifiers() {
  if (!sendersUnlinkedListEl) {
    return;
  }

  const visibleRows = visibleUnlinkedSenderIdentifiers();
  const fragment = document.createDocumentFragment();

  if (visibleRows.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Det finns inga okopplade uppgifter.';
    fragment.appendChild(empty);
    sendersUnlinkedListEl.replaceChildren(fragment);
    return;
  }

  const table = document.createElement('table');
  table.className = 'senders-unlinked-table';

  const thead = document.createElement('thead');
  const headRow = document.createElement('tr');
  ['Typ', 'Nummer', 'Namn', 'Åtgärd'].forEach((label) => {
    const th = document.createElement('th');
    th.scope = 'col';
    th.textContent = label;
    headRow.appendChild(th);
  });
  thead.appendChild(headRow);
  table.appendChild(thead);

  const tbody = document.createElement('tbody');

  visibleRows.forEach((row) => {
    const tr = document.createElement('tr');

    const typeCell = document.createElement('td');
    typeCell.className = 'senders-unlinked-type-cell';
    typeCell.textContent = row.typeLabel;

    const numberCell = document.createElement('td');
    numberCell.className = 'senders-unlinked-number-cell';
    numberCell.textContent = row.number;

    const nameCell = document.createElement('td');
    nameCell.className = 'senders-unlinked-name-cell';
    nameCell.textContent = String(row.name || '').trim() !== '' ? row.name : '—';

    const actionCell = document.createElement('td');
    actionCell.className = 'senders-unlinked-action-cell';

    const actionWrap = document.createElement('div');
    actionWrap.className = 'senders-unlinked-actions';

    const select = document.createElement('select');
    select.className = 'settings-select senders-unlinked-link-select';
    select.setAttribute('aria-label', `Koppla ${row.typeLabel} ${row.number}`);

    const placeholderOption = document.createElement('option');
    placeholderOption.value = '';
    placeholderOption.hidden = true;
    placeholderOption.textContent = 'Koppla';
    select.appendChild(placeholderOption);

    const createOption = document.createElement('option');
    createOption.value = '__create_sender__';
    createOption.textContent = '+ Skapa ny avsändare';
    select.appendChild(createOption);

    const separatorOption = document.createElement('option');
    separatorOption.value = '';
    separatorOption.disabled = true;
    separatorOption.textContent = '────────';
    select.appendChild(separatorOption);

    senderLinkOptionsForIdentifier(row).forEach((optionData) => {
      const option = document.createElement('option');
      option.value = optionData.value;
      option.textContent = optionData.label;
      select.appendChild(option);
    });

    select.value = '';
    select.addEventListener('change', () => {
      const value = String(select.value || '').trim();
      select.value = '';
      if (value === '') {
        return;
      }
      if (value === '__create_sender__') {
        createSenderFromUnlinkedIdentifier(row.key);
        return;
      }
      linkUnlinkedIdentifierToSender(row.key, value);
    });

    actionWrap.appendChild(select);
    actionCell.appendChild(actionWrap);

    tr.appendChild(typeCell);
    tr.appendChild(numberCell);
    tr.appendChild(nameCell);
    tr.appendChild(actionCell);
    tbody.appendChild(tr);
  });

  table.appendChild(tbody);
  fragment.appendChild(table);
  sendersUnlinkedListEl.replaceChildren(fragment);
}

function renderSendersEditor() {
  if (!sendersListEl) {
    return;
  }

  const fragment = document.createDocumentFragment();

  if (sendersDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga avsändare ännu.';
    fragment.appendChild(empty);
    sendersListEl.replaceChildren(fragment);
    updateSendersSelectionSummary();
    renderUnlinkedSenderIdentifiers();
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
  renderUnlinkedSenderIdentifiers();
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

  const organizationList = createTreeChildren({ markerless: true });

  const organizationsLabel = document.createElement('div');
  organizationsLabel.className = 'archive-level-label';
  organizationsLabel.textContent = 'Organisationsnummer';
  organizationList.appendChild(organizationsLabel);

  draft.organizationNumbers.forEach((organization, organizationIndex) => {
    const organizationNode = document.createElement('div');
    organizationNode.className = 'tree-node tree-category has-parent';

    const organizationRowEl = createTreeRow({ markerless: true });

    const organizationBody = document.createElement('div');
    organizationBody.className = 'tree-body category-body';
    appendTreeBodyIcon(organizationBody, 'tree-body-icon sender-organization-icon');

    const organizationFields = document.createElement('div');
    organizationFields.className = 'sender-organization-fields';

    const organizationNumberInput = document.createElement('input');
    organizationNumberInput.type = 'text';
    organizationNumberInput.value = organization.organizationNumber;
    organizationNumberInput.addEventListener('input', () => {
      senderMergeState.draft.organizationNumbers[organizationIndex].organizationNumber = organizationNumberInput.value;
    });

    const organizationNameInput = document.createElement('input');
    organizationNameInput.type = 'text';
    organizationNameInput.value = organization.organizationName;
    organizationNameInput.addEventListener('input', () => {
      senderMergeState.draft.organizationNumbers[organizationIndex].organizationName = organizationNameInput.value;
    });

    const removeOrganizationButton = document.createElement('button');
    removeOrganizationButton.type = 'button';
    removeOrganizationButton.className = 'category-remove';
    removeOrganizationButton.textContent = 'Ta bort';
    removeOrganizationButton.addEventListener('click', () => {
      senderMergeState.draft.organizationNumbers.splice(organizationIndex, 1);
      renderSenderMergeEditor();
    });

    organizationFields.appendChild(createFloatingField('Org.nr', organizationNumberInput));
    organizationFields.appendChild(createFloatingField('Namn', organizationNameInput));
    organizationFields.appendChild(removeOrganizationButton);
    organizationBody.appendChild(organizationFields);
    organizationRowEl.appendChild(organizationBody);
    organizationNode.appendChild(organizationRowEl);
    organizationList.appendChild(organizationNode);
  });

  rootBody.appendChild(organizationList);

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
  const addOrganizationButton = document.createElement('button');
  addOrganizationButton.type = 'button';
  addOrganizationButton.textContent = 'Lägg till organisationsnummer';
  addOrganizationButton.addEventListener('click', () => {
    senderMergeState.draft.organizationNumbers.push(defaultSenderOrganizationDraft());
    renderSenderMergeEditor();
  });
  const addPaymentButton = document.createElement('button');
  addPaymentButton.type = 'button';
  addPaymentButton.textContent = 'Lägg till betalnummer';
  addPaymentButton.addEventListener('click', () => {
    senderMergeState.draft.paymentNumbers.push(defaultSenderPaymentDraft());
    renderSenderMergeEditor();
  });
  mergeActions.appendChild(addOrganizationButton);
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
  const mergedSourceSenderIds = new Set(Array.isArray(mergedDraft.mergedSourceSenderIds) ? mergedDraft.mergedSourceSenderIds : []);
  sendersDraft.forEach((row) => {
    const rowUiKey = senderUiKey(row);
    if (!sourceUiKeys.has(rowUiKey)) {
      return;
    }
    if (Number.isInteger(row.id) && row.id > 0 && row.id !== mergedDraft.id) {
      mergedSourceSenderIds.add(row.id);
    }
    (Array.isArray(row.mergedSourceSenderIds) ? row.mergedSourceSenderIds : []).forEach((senderId) => {
      if (Number.isInteger(senderId) && senderId > 0 && senderId !== mergedDraft.id) {
        mergedSourceSenderIds.add(senderId);
      }
    });
  });
  const nextSendersDraft = [];
  let insertedMergedRow = false;

  sendersDraft.forEach((row) => {
    const rowUiKey = senderUiKey(row);
    if (!sourceUiKeys.has(rowUiKey)) {
      nextSendersDraft.push(row);
      return;
    }
    if (!insertedMergedRow && rowUiKey === senderMergeState.baseUiKey) {
      nextSendersDraft.push({
        ...mergedDraft,
        mergedSourceSenderIds: Array.from(mergedSourceSenderIds),
      });
      insertedMergedRow = true;
    }
  });

  if (!insertedMergedRow) {
    nextSendersDraft.push({
      ...mergedDraft,
      mergedSourceSenderIds: Array.from(mergedSourceSenderIds),
    });
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
  fieldBody.className = 'tree-body category-body extraction-field-editor-body';
  appendTreeBodyIcon(fieldBody, 'tree-body-icon tree-body-icon-category');
  if (showLock) {
    appendTreeBodyLock(fieldBody, 'Låst datafält');
  }

  const fieldActions = document.createElement('div');
  fieldActions.className = 'extraction-field-editor-actions';

  const copyButton = document.createElement('button');
  copyButton.type = 'button';
  copyButton.className = 'extraction-field-copy-button';
  copyButton.textContent = '⧉';
  copyButton.title = 'Kopiera datafält som JSON';
  copyButton.setAttribute('aria-label', 'Kopiera datafält som JSON');
  copyButton.addEventListener('click', async () => {
    const fieldConfig = sanitizeExtractionField(collection[index], index);
    const json = JSON.stringify(fieldConfig, null, 2);
    const defaultTitle = 'Kopiera datafält som JSON';
    try {
      const copied = await copyTextToClipboard(json);
      if (!copied) {
        throw new Error('copy_failed');
      }
      copyButton.classList.add('is-copied');
      copyButton.title = 'Kopierad';
      copyButton.setAttribute('aria-label', 'Kopierad');
      window.setTimeout(() => {
        copyButton.classList.remove('is-copied');
        copyButton.title = defaultTitle;
        copyButton.setAttribute('aria-label', defaultTitle);
      }, 1200);
    } catch (error) {
      copyButton.classList.add('is-copy-failed');
      copyButton.title = 'Kunde inte kopiera';
      copyButton.setAttribute('aria-label', 'Kunde inte kopiera');
      window.setTimeout(() => {
        copyButton.classList.remove('is-copy-failed');
        copyButton.title = defaultTitle;
        copyButton.setAttribute('aria-label', defaultTitle);
      }, 1200);
    }
  });
  fieldActions.appendChild(copyButton);
  fieldBody.appendChild(fieldActions);

  const fields = document.createElement('div');
  fields.className = 'extraction-field-header-fields';

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

  fields.appendChild(createFloatingField('Namn', nameInput));

  if (allowRemove) {
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'category-remove';
    removeButton.textContent = 'Ta bort datafält';
    removeButton.addEventListener('click', async () => {
      const fieldDraft = sanitizeExtractionField(collection[index], index);
      const fieldKey = typeof fieldDraft.key === 'string' ? fieldDraft.key.trim() : '';
      if (fieldKey !== '') {
        try {
          const response = await fetch('/api/check-extraction-field-removal.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              fieldKey,
            })
          });
          const payload = await response.json().catch(() => null);
          if (!response.ok || !payload || payload.ok !== true) {
            throw new Error(payload && typeof payload.error === 'string'
              ? payload.error
              : 'Kunde inte kontrollera om datafältet kan tas bort.');
          }
        } catch (error) {
          alert(error.message || 'Kunde inte ta bort datafältet.');
          return;
        }
      }
      collection.splice(index, 1);
      renderExtractionFieldsEditor();
      renderSystemExtractionFieldsEditor();
      updateSettingsActionButtons();
    });
    fields.appendChild(removeButton);
  }

  fieldBody.appendChild(fields);

  if (!readOnly) {
    const ruleSetList = createTreeChildren({ markerless: true });
    const ruleSetsLabel = document.createElement('div');
    ruleSetsLabel.className = 'archive-level-label';
    ruleSetsLabel.textContent = 'Regeluppsättningar';
    ruleSetList.appendChild(ruleSetsLabel);

    const ruleSets = sanitizeExtractionFieldRuleSets(collection[index]?.ruleSets, collection[index]);
    collection[index].ruleSets = ruleSets;

    ruleSets.forEach((ruleSet, ruleSetIndex) => {
      const ruleNode = document.createElement('div');
      ruleNode.className = 'tree-node tree-rule has-parent';

      const ruleRow = createTreeRow({ markerless: true });
      const ruleBody = document.createElement('div');
      ruleBody.className = 'tree-body rule-body';
      appendTreeBodyIcon(ruleBody, 'tree-body-icon tree-body-icon-rule');

      const ruleFields = document.createElement('div');
      ruleFields.className = 'extraction-field-rule-set-fields';
      let removeRuleSetButton = null;

      const typeSelect = document.createElement('select');
      typeSelect.className = 'extraction-field-type-select';
      [
        ['regex', 'Regex'],
        ['date', 'Datum'],
        ['amount', 'Belopp'],
      ].forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        typeSelect.appendChild(option);
      });
      typeSelect.value = sanitizeExtractionFieldType(ruleSet.type, collection[index]);
      ruleFields.appendChild(createFloatingField('Typ', typeSelect));

      const requiresSearchTermsLabel = document.createElement('label');
      requiresSearchTermsLabel.className = 'extraction-field-rule-set-toggle';
      const requiresSearchTermsCheckbox = document.createElement('input');
      requiresSearchTermsCheckbox.type = 'checkbox';
      requiresSearchTermsCheckbox.checked = ruleSet.useSearchText !== false && ruleSet.requiresSearchTerms !== false;
      const requiresSearchTermsText = document.createElement('span');
      requiresSearchTermsText.textContent = 'Använd söktext';
      requiresSearchTermsLabel.appendChild(requiresSearchTermsCheckbox);
      requiresSearchTermsLabel.appendChild(requiresSearchTermsText);

      const toggleField = document.createElement('div');
      toggleField.className = 'floating-input-group extraction-field-rule-set-toggle-field';
      toggleField.appendChild(requiresSearchTermsLabel);
      ruleFields.appendChild(toggleField);

      const searchTermsField = document.createElement('div');
      searchTermsField.className = 'floating-input-group extraction-field-aliases-field';
      const searchTermsLabel = document.createElement('label');
      searchTermsLabel.className = 'floating-input-label';
      searchTermsLabel.textContent = 'Söktext';
      const searchTermsList = document.createElement('div');
      searchTermsList.className = 'extraction-field-aliases';
      const searchTermValues = extractionFieldSearchTermsForEditor(ruleSet);

      const syncSearchTermsToDraft = () => {
        collection[index].ruleSets[ruleSetIndex].searchTerms = sanitizeExtractionFieldSearchTermsInput(searchTermValues);
        updateSettingsActionButtons();
      };

      const renderSearchTermRows = (focusIndex = null, selectionStart = null, selectionEnd = null) => {
        searchTermsList.innerHTML = '';
        const nonEmptyCount = searchTermValues.filter((value) => String(value && value.text ? value.text : '').trim() !== '').length;
        const currentType = sanitizeExtractionFieldType(typeSelect.value, collection[index]);

        searchTermValues.forEach((searchTermValue, searchTermIndex) => {
          const searchTermRow = document.createElement('div');
          searchTermRow.className = 'extraction-field-alias-row';
          const supportsRegexSearchText = currentType === 'regex';
          const accessoryCount = searchTermIndex > 0 ? (supportsRegexSearchText ? 2 : 1) : (supportsRegexSearchText ? 1 : 0);

          const searchTermInput = document.createElement('input');
          searchTermInput.type = 'text';
          searchTermInput.placeholder = 'Ex: "org.nr"';
          searchTermInput.value = searchTermValue && typeof searchTermValue === 'object'
            ? String(searchTermValue.text || '')
            : String(searchTermValue || '');
          searchTermInput.dataset.searchTermIndex = String(searchTermIndex);
          syncExtractionFieldAliasInputSize(searchTermInput, accessoryCount);
          searchTermInput.addEventListener('input', () => {
            const current = searchTermValues[searchTermIndex] && typeof searchTermValues[searchTermIndex] === 'object'
              ? searchTermValues[searchTermIndex]
              : { text: '', isRegex: false };
            current.text = searchTermInput.value;
            searchTermValues[searchTermIndex] = current;
            syncExtractionFieldAliasInputSize(searchTermInput, accessoryCount);
            syncSearchTermsToDraft();
            if (searchTermIndex === searchTermValues.length - 1 && searchTermInput.value.trim() !== '') {
              const nextSelectionStart = searchTermInput.selectionStart;
              const nextSelectionEnd = searchTermInput.selectionEnd;
              searchTermValues.push({ text: '', isRegex: false });
              renderSearchTermRows(searchTermIndex, nextSelectionStart, nextSelectionEnd);
            }
          });

          const accessories = [];
          if (supportsRegexSearchText) {
            const regexButton = createRegexToggleButton({
              getActive: () => {
                const current = searchTermValues[searchTermIndex];
                return !!(current && typeof current === 'object' && current.isRegex === true);
              },
              setActive: (next) => {
                const current = searchTermValues[searchTermIndex] && typeof searchTermValues[searchTermIndex] === 'object'
                  ? searchTermValues[searchTermIndex]
                  : { text: '', isRegex: false };
                current.isRegex = next === true;
                searchTermValues[searchTermIndex] = current;
                syncSearchTermsToDraft();
                updateSettingsActionButtons();
                const nextSelectionStart = searchTermInput.selectionStart;
                const nextSelectionEnd = searchTermInput.selectionEnd;
                renderSearchTermRows(searchTermIndex, nextSelectionStart, nextSelectionEnd);
              },
            });
            accessories.push(regexButton);
          }
          if (searchTermIndex > 0) {
            const removeSearchTermButton = document.createElement('button');
            removeSearchTermButton.type = 'button';
            removeSearchTermButton.className = 'extraction-field-alias-remove';
            removeSearchTermButton.setAttribute('aria-label', 'Ta bort söktext');
            removeSearchTermButton.title = 'Ta bort söktext';
            removeSearchTermButton.textContent = '×';
            const removingLastSearchTerm = nonEmptyCount <= 1 && searchTermInput.value.trim() !== '';
            removeSearchTermButton.disabled = removingLastSearchTerm;
            removeSearchTermButton.addEventListener('click', () => {
              if (removingLastSearchTerm) {
                return;
              }
              searchTermValues.splice(searchTermIndex, 1);
              const lastTerm = searchTermValues[searchTermValues.length - 1] || null;
              if (searchTermValues.length === 0 || String(lastTerm && lastTerm.text ? lastTerm.text : '').trim() !== '') {
                searchTermValues.push({ text: '', isRegex: false });
              }
              syncSearchTermsToDraft();
              renderSearchTermRows();
            });
            accessories.push(removeSearchTermButton);
          }

          const searchTermInputWrap = createInlineInputWithAccessories(searchTermInput, accessories, 'extraction-field-alias-input-wrap');
          searchTermRow.appendChild(searchTermInputWrap);
          searchTermsList.appendChild(searchTermRow);
        });

        if (focusIndex !== null) {
          const inputs = Array.from(searchTermsList.querySelectorAll('input'));
          const targetInput = inputs[focusIndex] || null;
          if (targetInput) {
            targetInput.focus();
            if (selectionStart !== null && selectionEnd !== null) {
              targetInput.setSelectionRange(selectionStart, selectionEnd);
            }
          }
        }
      };

      renderSearchTermRows();
      searchTermsField.appendChild(searchTermsLabel);
      searchTermsField.appendChild(searchTermsList);
      ruleFields.appendChild(searchTermsField);

      const valuePatternInput = document.createElement('input');
      valuePatternInput.type = 'text';
      valuePatternInput.placeholder = 'Ex: "\\d{6}[- ]?\\d{4}"';
      valuePatternInput.value = ruleSet.valuePattern || '';
      valuePatternInput.addEventListener('input', () => {
        collection[index].ruleSets[ruleSetIndex].valuePattern = valuePatternInput.value;
        updateSettingsActionButtons();
      });
      const valuePatternField = createFloatingField('Värdemönster (regex)', valuePatternInput);
      ruleFields.appendChild(valuePatternField);

      const datePositionSelect = document.createElement('select');
      [
        ['first', 'Första datumet'],
        ['second', 'Andra datumet'],
        ['last', 'Sista datumet'],
      ].forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        datePositionSelect.appendChild(option);
      });
      datePositionSelect.value = sanitizeExtractionFieldPosition(ruleSet.datePosition);
      const datePositionField = createFloatingField('Datumposition', datePositionSelect);
      ruleFields.appendChild(datePositionField);

      const amountPositionSelect = document.createElement('select');
      [
        ['first', 'Första beloppet'],
        ['second', 'Andra beloppet'],
        ['last', 'Sista beloppet'],
      ].forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        amountPositionSelect.appendChild(option);
      });
      amountPositionSelect.value = sanitizeExtractionFieldPosition(ruleSet.amountPosition);
      const amountPositionField = createFloatingField('Beloppsposition', amountPositionSelect);
      ruleFields.appendChild(amountPositionField);

      const normalizationTypeSelect = document.createElement('select');
      normalizationTypeSelect.className = 'extraction-field-normalization-select';
      [
        ['none', 'Ingen'],
        ['whitelist', 'Whitelist'],
        ['blacklist', 'Blacklist'],
        ['replacements', 'Ersättningar'],
      ].forEach(([value, label]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        normalizationTypeSelect.appendChild(option);
      });
      normalizationTypeSelect.value = sanitizeExtractionFieldNormalizationType(ruleSet.normalizationType);

      const normalizationCharsInput = document.createElement('input');
      normalizationCharsInput.type = 'text';
      normalizationCharsInput.placeholder = 'Ex: 0123456789';
      normalizationCharsInput.value = ruleSet.normalizationChars || '';

      const normalizationCharsField = createFloatingField('Normaliseringstecken', normalizationCharsInput);
      normalizationCharsField.classList.add('extraction-field-normalization-field');

      let normalizationReplacementsValues = sanitizeExtractionFieldNormalizationReplacementsInput(ruleSet.normalizationReplacements);
      const normalizationReplacementsList = document.createElement('div');
      normalizationReplacementsList.className = 'extraction-field-normalization-replacements-list';
      const normalizationReplacementsAddButton = document.createElement('button');
      normalizationReplacementsAddButton.type = 'button';
      normalizationReplacementsAddButton.className = 'extraction-field-normalization-replacements-add';
      normalizationReplacementsAddButton.textContent = 'Lägg till ersättning';
      const normalizationReplacementsField = document.createElement('div');
      normalizationReplacementsField.className = 'extraction-field-normalization-replacements-field';
      normalizationReplacementsField.appendChild(normalizationReplacementsList);
      normalizationReplacementsField.appendChild(normalizationReplacementsAddButton);

      const syncNormalizationReplacementsToDraft = () => {
        collection[index].ruleSets[ruleSetIndex].normalizationReplacements = normalizationReplacementsValues.map((replacement) => ({
          find: typeof replacement.find === 'string' ? replacement.find : '',
          replace: typeof replacement.replace === 'string' ? replacement.replace : '',
          isRegex: replacement.isRegex === true,
        }));
      };

      const renderNormalizationReplacementRows = (focusIndex = null) => {
        normalizationReplacementsList.innerHTML = '';
        if (normalizationReplacementsValues.length === 0) {
          const empty = document.createElement('div');
          empty.className = 'categories-empty extraction-field-normalization-replacements-empty';
          empty.textContent = 'Inga ersättningar ännu.';
          normalizationReplacementsList.appendChild(empty);
          return;
        }

        normalizationReplacementsValues.forEach((replacementValue, replacementIndex) => {
          const row = document.createElement('div');
          row.className = 'extraction-field-normalization-replacement-row';

          const findInput = document.createElement('input');
          findInput.type = 'text';
          findInput.placeholder = 'Ex: ^(\\d{4})\\s*[-./ ]\\s*(\\d{2})\\s*[-./ ]\\s*(\\d{2})$';
          findInput.value = typeof replacementValue.find === 'string' ? replacementValue.find : '';
          findInput.addEventListener('input', () => {
            normalizationReplacementsValues[replacementIndex].find = findInput.value;
            syncNormalizationReplacementsToDraft();
            updateSettingsActionButtons();
          });

          const replaceInput = document.createElement('input');
          replaceInput.type = 'text';
          replaceInput.placeholder = 'Ex: $1-$2-$3';
          replaceInput.value = typeof replacementValue.replace === 'string' ? replacementValue.replace : '';
          replaceInput.addEventListener('input', () => {
            normalizationReplacementsValues[replacementIndex].replace = replaceInput.value;
            syncNormalizationReplacementsToDraft();
            updateSettingsActionButtons();
          });

          const regexToggleButton = createRegexToggleButton({
            getActive: () => replacementValue.isRegex === true,
            setActive: (next) => {
              normalizationReplacementsValues[replacementIndex].isRegex = next === true;
              syncNormalizationReplacementsToDraft();
              updateSettingsActionButtons();
            },
          });
          regexToggleButton.title = 'Regex';
          regexToggleButton.setAttribute('aria-label', 'Regex');

          const removeReplacementButton = document.createElement('button');
          removeReplacementButton.type = 'button';
          removeReplacementButton.className = 'category-remove extraction-field-normalization-replacement-remove';
          removeReplacementButton.textContent = '';
          removeReplacementButton.setAttribute('aria-label', 'Ta bort ersättning');
          removeReplacementButton.title = 'Ta bort ersättning';
          removeReplacementButton.addEventListener('click', () => {
            normalizationReplacementsValues.splice(replacementIndex, 1);
            syncNormalizationReplacementsToDraft();
            renderNormalizationReplacementRows();
            updateSettingsActionButtons();
          });

          const findInputWrap = createInlineInputWithAccessories(findInput, [regexToggleButton], 'extraction-field-alias-input-wrap');
          row.appendChild(createFloatingField('Hitta', findInputWrap));
          row.appendChild(createFloatingField('Ersätt med', replaceInput));
          row.appendChild(removeReplacementButton);
          normalizationReplacementsList.appendChild(row);

          if (focusIndex === replacementIndex) {
            findInput.focus();
          }
        });
      };

      normalizationReplacementsAddButton.addEventListener('click', () => {
        normalizationReplacementsValues.push(defaultExtractionFieldNormalizationReplacement());
        syncNormalizationReplacementsToDraft();
        renderNormalizationReplacementRows(normalizationReplacementsValues.length - 1);
        updateSettingsActionButtons();
      });

      const normalizationGroup = document.createElement('div');
      normalizationGroup.className = 'extraction-field-normalization-group';
      normalizationGroup.appendChild(createFloatingField('Normalisering', normalizationTypeSelect));
      normalizationGroup.appendChild(normalizationCharsField);

      const syncRuleSetUi = () => {
        const type = sanitizeExtractionFieldType(typeSelect.value, collection[index]);
        const normalizationType = sanitizeExtractionFieldNormalizationType(normalizationTypeSelect.value);
        collection[index].ruleSets[ruleSetIndex].type = type;
        collection[index].type = type;
        collection[index].ruleSets[ruleSetIndex].useSearchText = requiresSearchTermsCheckbox.checked;
        collection[index].ruleSets[ruleSetIndex].requiresSearchTerms = requiresSearchTermsCheckbox.checked;
        collection[index].ruleSets[ruleSetIndex].normalizationType = normalizationType;
        collection[index].ruleSets[ruleSetIndex].normalizationChars = normalizationCharsInput.value;
        collection[index].ruleSets[ruleSetIndex].normalizationReplacements = normalizationReplacementsValues.map((replacement) => ({
          find: typeof replacement.find === 'string' ? replacement.find : '',
          replace: typeof replacement.replace === 'string' ? replacement.replace : '',
          isRegex: replacement.isRegex === true,
        }));
        collection[index].ruleSets[ruleSetIndex].datePosition = sanitizeExtractionFieldPosition(datePositionSelect.value);
        collection[index].ruleSets[ruleSetIndex].amountPosition = sanitizeExtractionFieldPosition(amountPositionSelect.value);
        searchTermsField.hidden = !requiresSearchTermsCheckbox.checked;
        valuePatternField.hidden = type !== 'regex';
        datePositionField.hidden = type !== 'date';
        amountPositionField.hidden = type !== 'amount';
        if (type === 'date') {
          if (normalizationGroup.parentNode) {
            normalizationGroup.remove();
          }
        } else {
          if (!normalizationGroup.parentNode) {
            if (removeRuleSetButton && removeRuleSetButton.parentNode === ruleFields) {
              ruleFields.insertBefore(normalizationGroup, removeRuleSetButton);
            } else {
              ruleFields.appendChild(normalizationGroup);
            }
          }
          normalizationCharsField.hidden = normalizationType !== 'whitelist' && normalizationType !== 'blacklist';
          if (normalizationType === 'replacements') {
            if (!normalizationReplacementsField.parentNode) {
              normalizationGroup.appendChild(normalizationReplacementsField);
            }
          } else if (normalizationReplacementsField.parentNode) {
            normalizationReplacementsField.remove();
          }
          if (normalizationType === 'replacements' && normalizationReplacementsValues.length === 0) {
            normalizationReplacementsValues.push(defaultExtractionFieldNormalizationReplacement());
            syncNormalizationReplacementsToDraft();
            renderNormalizationReplacementRows(normalizationReplacementsValues.length - 1);
          } else {
            syncNormalizationReplacementsToDraft();
          }
          if (normalizationType === 'replacements') {
            renderNormalizationReplacementRows();
          }
        }
        if (type === 'date') {
          syncNormalizationReplacementsToDraft();
        }
        renderSearchTermRows();
        updateSettingsActionButtons();
      };

      typeSelect.addEventListener('change', syncRuleSetUi);
      requiresSearchTermsCheckbox.addEventListener('change', syncRuleSetUi);
      normalizationTypeSelect.addEventListener('change', syncRuleSetUi);
      normalizationCharsInput.addEventListener('input', syncRuleSetUi);
      datePositionSelect.addEventListener('change', syncRuleSetUi);
      amountPositionSelect.addEventListener('change', syncRuleSetUi);

      if (ruleSetIndex > 0) {
        removeRuleSetButton = document.createElement('button');
        removeRuleSetButton.type = 'button';
        removeRuleSetButton.className = 'rule-remove';
        removeRuleSetButton.textContent = 'Ta bort regeluppsättning';
        removeRuleSetButton.addEventListener('click', () => {
          collection[index].ruleSets.splice(ruleSetIndex, 1);
          if (collection[index].ruleSets.length === 0) {
            collection[index].ruleSets.push(defaultExtractionFieldRuleSet());
          }
          renderExtractionFieldsEditor();
          updateSettingsActionButtons();
        });
        ruleFields.appendChild(removeRuleSetButton);
      }

      syncRuleSetUi();
      ruleBody.appendChild(ruleFields);
      ruleRow.appendChild(ruleBody);
      ruleNode.appendChild(ruleRow);
      ruleSetList.appendChild(ruleNode);
    });

    const ruleActions = document.createElement('div');
    ruleActions.className = 'category-rule-actions';
    const addRuleSetButton = document.createElement('button');
    addRuleSetButton.type = 'button';
    addRuleSetButton.textContent = 'Lägg till regeluppsättning';
    addRuleSetButton.addEventListener('click', () => {
      const nextRuleSets = sanitizeExtractionFieldRuleSets(collection[index]?.ruleSets, collection[index]);
      const baseRuleSet = sanitizeExtractionFieldRuleSet(nextRuleSets[0] || null, collection[index]);
      nextRuleSets.push({
        ...defaultExtractionFieldRuleSet(),
        type: baseRuleSet.type,
        useSearchText: baseRuleSet.useSearchText,
        requiresSearchTerms: baseRuleSet.requiresSearchTerms,
        datePosition: baseRuleSet.datePosition,
        amountPosition: baseRuleSet.amountPosition,
      });
      collection[index].ruleSets = nextRuleSets;
      renderExtractionFieldsEditor();
      updateSettingsActionButtons();
    });
    ruleActions.appendChild(addRuleSetButton);
    ruleSetList.appendChild(ruleActions);
    fieldBody.appendChild(ruleSetList);
  } else {
    const queryInput = document.createElement('input');
    queryInput.type = 'text';
    queryInput.placeholder = isDocumentDateField ? 'Särskild intern heuristik' : 'Ex: "\\d{6}[- ]?\\d{4}"';
    queryInput.value = isDocumentDateField
      ? 'Ort + datum / brevhuvud / fristående datum'
      : field.searchString;
    queryInput.disabled = true;
    fields.appendChild(createFloatingField(isDocumentDateField ? 'Extraktion' : 'Söksträng', queryInput));
  }

  fieldRow.appendChild(fieldBody);
  fieldNode.appendChild(fieldRow);
  container.appendChild(fieldNode);
}

function renderExtractionFieldsEditor() {
  if (!extractionFieldsEditorEl) {
    return;
  }

  extractionFieldsEditorEl.innerHTML = '';

  const builtInGroup = createEditorGroup('Fördefinierade', extractionFieldsBuiltInCollapsed, () => {
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
    emptyBuiltIn.textContent = 'Inga fördefinierade datafält ännu.';
    builtInGroup.content.appendChild(emptyBuiltIn);
  } else {
    predefinedExtractionFieldsDraft.forEach((field, index) => {
      renderSingleExtractionFieldEditor(builtInGroup.content, predefinedExtractionFieldsDraft, index, {
        showLock: false,
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
  return archiveRuleLabelOptionsList().filter((option) => option.value !== normalizedCurrentId);
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

function serializeLabelRuleForClipboard(rule) {
  const sanitized = sanitizeLabelRule(rule);
  if (sanitized.type === 'sender_is') {
    return {
      type: 'sender_is',
      senderId: sanitized.senderId,
      score: sanitized.score
    };
  }
  if (sanitized.type === 'sender_name_contains') {
    return {
      type: 'sender_name_contains',
      text: sanitized.text,
      score: sanitized.score
    };
  }
  if (sanitized.type === 'field_exists') {
    return {
      type: 'field_exists',
      field: sanitized.field,
      score: sanitized.score
    };
  }
  return {
    type: 'text',
    text: sanitized.text,
    isRegex: sanitized.isRegex === true,
    score: sanitized.score
  };
}

function serializeLabelDraftForClipboard(options = {}) {
  const label = sanitizedLabelDraftForEditor(options);
  if (!label) {
    return null;
  }
  const serialized = {
    id: label.id,
    name: label.name,
    minScore: label.minScore,
    rules: Array.isArray(label.rules) ? label.rules.map(serializeLabelRuleForClipboard) : []
  };
  if (typeof label.description === 'string' && label.description.trim() !== '') {
    serialized.description = label.description;
  }
  return serialized;
}

async function ensureLabelRemovalAllowed(labelId) {
  const normalizedLabelId = typeof labelId === 'string' ? labelId.trim() : '';
  if (normalizedLabelId === '') {
    return;
  }

  const response = await fetch('/api/check-label-removal.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      labelId: normalizedLabelId,
    })
  });
  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    throw new Error(payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte kontrollera om etiketten kan tas bort.');
  }
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
  labelBody.className = 'tree-body category-body extraction-field-editor-body';
  appendTreeBodyIcon(labelBody, 'tree-body-icon tree-body-icon-category');
  if (builtIn) {
    appendTreeBodyLock(labelBody);
  }

  const labelActions = document.createElement('div');
  labelActions.className = 'extraction-field-editor-actions';

  const copyButton = document.createElement('button');
  copyButton.type = 'button';
  copyButton.className = 'extraction-field-copy-button';
  copyButton.textContent = '⧉';
  copyButton.title = 'Kopiera etikett som JSON';
  copyButton.setAttribute('aria-label', 'Kopiera etikett som JSON');
  copyButton.addEventListener('click', async () => {
    const labelConfig = serializeLabelDraftForClipboard(options);
    if (!labelConfig) {
      return;
    }

    const json = JSON.stringify(labelConfig, null, 2);
    const defaultTitle = 'Kopiera etikett som JSON';
    try {
      const copied = await copyTextToClipboard(json);
      if (!copied) {
        throw new Error('copy_failed');
      }
      copyButton.classList.add('is-copied');
      copyButton.title = 'Kopierad';
      copyButton.setAttribute('aria-label', 'Kopierad');
      window.setTimeout(() => {
        copyButton.classList.remove('is-copied');
        copyButton.title = defaultTitle;
        copyButton.setAttribute('aria-label', defaultTitle);
      }, 1200);
    } catch (error) {
      copyButton.classList.add('is-copy-failed');
      copyButton.title = 'Kunde inte kopiera';
      copyButton.setAttribute('aria-label', 'Kunde inte kopiera');
      window.setTimeout(() => {
        copyButton.classList.remove('is-copy-failed');
        copyButton.title = defaultTitle;
        copyButton.setAttribute('aria-label', defaultTitle);
      }, 1200);
    }
  });
  labelActions.appendChild(copyButton);
  labelBody.appendChild(labelActions);

  const fields = document.createElement('div');
  fields.className = 'label-fields';

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

  const descriptionInput = document.createElement('textarea');
  descriptionInput.rows = 2;
  descriptionInput.placeholder = 'Beskriv vad etiketten betyder semantiskt';
  descriptionInput.value = typeof currentLabel.description === 'string' ? currentLabel.description : '';
  descriptionInput.addEventListener('input', () => {
    const draft = currentLabelDraftForEditor(options);
    if (!draft) {
      return;
    }
    draft.description = descriptionInput.value;
    updateSettingsActionButtons();
  });
  fields.appendChild(createFloatingField('Beskrivning', descriptionInput, 'label-description-field'));

  if (!builtIn) {
    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'category-remove';
    removeButton.textContent = 'Ta bort etikett';
    removeButton.addEventListener('click', async () => {
      const labelIndex = Number.isInteger(options.labelIndex) ? options.labelIndex : -1;
      if (labelIndex < 0) {
        return;
      }
      const labelDraft = sanitizeLabel(labelsDraft[labelIndex]);
      try {
        await ensureLabelRemovalAllowed(labelDraft.id);
      } catch (error) {
        alert(error.message || 'Kunde inte ta bort etiketten.');
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
      ['sender_is', 'Avsändare är...'],
      ['sender_name_contains', 'Avsändarnamn innehåller...'],
      ['field_exists', 'Fält finns'],
    ].forEach(([value, optionLabel]) => {
      const option = document.createElement('option');
      option.value = value;
      option.textContent = optionLabel;
      typeSelect.appendChild(option);
    });
    typeSelect.value = ['text', 'sender_is', 'sender_name_contains', 'field_exists'].includes(rule.type) ? rule.type : 'text';
    typeSelect.addEventListener('change', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      const nextType = String(typeSelect.value || 'text').trim();
      draft.rules[ruleIndex].type = nextType;
      if (nextType === 'sender_is') {
        const senderOptions = labelRuleSenderOptions();
        const fallbackSenderId = senderOptions[0] && Number.isInteger(senderOptions[0].value)
          ? senderOptions[0].value
          : null;
        draft.rules[ruleIndex].senderId = senderOptions.some((option) => option.value === draft.rules[ruleIndex].senderId)
          ? draft.rules[ruleIndex].senderId
          : fallbackSenderId;
        draft.rules[ruleIndex].text = '';
        draft.rules[ruleIndex].field = '';
      } else if (nextType === 'field_exists') {
        const fieldOptions = labelRuleFieldOptions();
        const fallbackField = fieldOptions[0] && typeof fieldOptions[0].value === 'string'
          ? fieldOptions[0].value
          : '';
        draft.rules[ruleIndex].field = fieldOptions.some((option) => option.value === draft.rules[ruleIndex].field)
          ? draft.rules[ruleIndex].field
          : fallbackField;
        draft.rules[ruleIndex].text = '';
        draft.rules[ruleIndex].senderId = null;
      } else {
        draft.rules[ruleIndex].senderId = null;
        draft.rules[ruleIndex].field = '';
      }
      renderLabelsEditor();
      updateSettingsActionButtons();
    });

    const textInput = document.createElement('input');
    textInput.type = 'text';
    textInput.placeholder = rule.type === 'sender_name_contains'
      ? 'Ex: "kommun"'
      : 'Ex: "förfallodatum"';
    textInput.value = rule.text;
    textInput.addEventListener('input', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].text = textInput.value;
      updateSettingsActionButtons();
    });

    const senderSelect = document.createElement('select');
    const senderOptions = labelRuleSenderOptions();
    if (senderOptions.length === 0) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Inga avsändare';
      senderSelect.appendChild(option);
      senderSelect.disabled = true;
    } else {
      senderOptions.forEach((optionData) => {
        const option = document.createElement('option');
        option.value = String(optionData.value);
        option.textContent = optionData.label;
        senderSelect.appendChild(option);
      });
      const currentSenderId = Number.isInteger(rule.senderId) ? rule.senderId : null;
      const resolvedSenderId = senderOptions.some((option) => option.value === currentSenderId)
        ? currentSenderId
        : senderOptions[0].value;
      senderSelect.value = String(resolvedSenderId);
      const draft = currentLabelDraftForEditor(options);
      if (draft && Array.isArray(draft.rules) && draft.rules[ruleIndex]) {
        draft.rules[ruleIndex].senderId = resolvedSenderId;
      }
    }
    senderSelect.addEventListener('change', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      const nextSenderId = Number.parseInt(String(senderSelect.value || ''), 10);
      draft.rules[ruleIndex].senderId = Number.isInteger(nextSenderId) && nextSenderId > 0 ? nextSenderId : null;
      updateSettingsActionButtons();
    });

    const fieldSelect = document.createElement('select');
    const fieldOptions = labelRuleFieldOptions();
    if (fieldOptions.length === 0) {
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'Inga fält';
      fieldSelect.appendChild(option);
      fieldSelect.disabled = true;
    } else {
      fieldOptions.forEach((optionData) => {
        const option = document.createElement('option');
        option.value = optionData.value;
        option.textContent = optionData.label;
        fieldSelect.appendChild(option);
      });
      const currentField = typeof rule.field === 'string' ? rule.field.trim() : '';
      const resolvedField = fieldOptions.some((option) => option.value === currentField)
        ? currentField
        : fieldOptions[0].value;
      fieldSelect.value = resolvedField;
      const draft = currentLabelDraftForEditor(options);
      if (draft && Array.isArray(draft.rules) && draft.rules[ruleIndex]) {
        draft.rules[ruleIndex].field = resolvedField;
      }
    }
    fieldSelect.addEventListener('change', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].field = fieldSelect.value;
      updateSettingsActionButtons();
    });

    const scoreInput = document.createElement('input');
    scoreInput.type = 'number';
    scoreInput.step = '1';
    scoreInput.value = String(rule.score);
    scoreInput.addEventListener('input', () => {
      const draft = currentLabelDraftForEditor(options);
      if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
        return;
      }
      draft.rules[ruleIndex].score = sanitizeInt(scoreInput.value, 1);
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
    if (rule.type === 'sender_is') {
      ruleFields.appendChild(createFloatingField('Avsändare', senderSelect));
    } else if (rule.type === 'field_exists') {
      ruleFields.appendChild(createFloatingField('Fält', fieldSelect));
    } else {
      if (rule.type === 'text') {
        const { wrapper: textInputWithRegex } = createRegexToggleInput(textInput, {
          getActive: () => {
            const draft = currentLabelDraftForEditor(options);
            return !!(draft && Array.isArray(draft.rules) && draft.rules[ruleIndex] && draft.rules[ruleIndex].isRegex === true);
          },
          setActive: (next) => {
            const draft = currentLabelDraftForEditor(options);
            if (!draft || !Array.isArray(draft.rules) || !draft.rules[ruleIndex]) {
              return;
            }
            draft.rules[ruleIndex].isRegex = next === true;
            updateSettingsActionButtons();
          },
        });
        ruleFields.appendChild(createFloatingField('Regeltext', textInputWithRegex));
      } else {
        ruleFields.appendChild(createFloatingField('Text', textInput));
      }
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
      systemLabelsDraft[labelKey].description = typeof defaults.description === 'string' ? defaults.description : '';
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
  const chipsOnlyEditor = options && options.chipsOnly === true;
  parts.splice(
    0,
    parts.length,
    ...(chipsOnlyEditor
      ? sanitizeFilenameTemplateCandidateParts(parts)
      : normalizeEditableFilenameTemplateParts(parts))
  );

  const isSlotEditor = options && options.variant === 'slot';
  const focusToolbar = options && options.focusToolbar === true;
  const autoFocus = !(options && options.autoFocus === false);
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
        tone: 'data',
        title: '',
      };
    }

    if (part.type === 'folder') {
      return {
        label: 'Mapp (legacy)',
        tone: 'folder',
        title: 'Legacy-chip från äldre mallar. Använd inte detta i nya mallar.',
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
    if (part.type === 'ifLabels') {
      const conditionMode = sanitizeIfLabelsMode(part.mode);
      return {
        label: 'Om etikett',
        tone: 'special',
        title: conditionMode === 'all'
          ? 'Renderar innehåll när alla valda etiketterna finns, annars valfritt alternativt innehåll.'
          : 'Renderar innehåll när någon av de valda etiketterna finns, annars valfritt alternativt innehåll.',
      };
    }
    return {
      label: part.key || 'Fält',
      tone: 'data',
      title: 'Lägger till värdet från valt datafält i filnamnet.',
    };
  };

  if (!context && !isSlotEditor) {
    const toolbar = createFilenameTemplateToolbar(sharedContext);
    if (focusToolbar) {
      toolbar.hidden = true;
      wrapper.addEventListener('focusin', () => {
        toolbar.hidden = false;
      });
      wrapper.addEventListener('focusout', () => {
        window.requestAnimationFrame(() => {
          if (!wrapper.contains(document.activeElement)) {
            toolbar.hidden = true;
          }
        });
      });
    }
    wrapper.appendChild(toolbar);
  }

  const isRootEditor = depth === 0 && !isSlotEditor;
  const sequence = document.createElement(isRootEditor ? 'div' : 'span');
  sequence.className = 'filename-template-inline-flow';
  if (isRootEditor) {
    sequence.classList.add('is-root');
  }
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

  const replaceParts = (targetParts, nextParts, chipsOnly = false) => {
    targetParts.splice(
      0,
      targetParts.length,
      ...(chipsOnly
        ? sanitizeFilenameTemplateCandidateParts(nextParts)
        : normalizeEditableFilenameTemplateParts(collapseTextParts(nextParts)))
    );
  };

  const editableTextToModelText = (value) =>
    String(value || '').replace(/\u00a0/g, ' ');

  const modelTextToEditableText = (value) =>
    String(value || '').replace(/(^ +| +$)/g, (match) => '\u00a0'.repeat(match.length));

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

  const isRootTextSlot = (node) =>
    node instanceof HTMLElement
    && node.classList.contains('filename-template-root-slot');

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

    if (isRootTextSlot(editable) && isCaretAtEditableBoundary(editable, direction)) {
      const adjacentToken = rootSlotAdjacentToken(editable, direction);
      if (adjacentToken) {
        adjacentToken.remove();
        syncRootSequenceFromDom(editable._filenameTemplateRootOwner || null);
        return true;
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

  if (isRootTextSlot(editable) && isCaretAtEditableBoundary(editable, direction)) {
    const siblingToken = rootSlotAdjacentToken(editable, direction);
    if (siblingToken) {
      return siblingToken;
    }
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

	const ownEditable = (selector) =>
		Array.from(token.querySelectorAll(selector)).find(
			(editable) => editable instanceof HTMLElement
				&& editable.closest('.filename-template-dom-token') === token
		) || null;

	return [
		ownEditable('.filename-template-inline-token-slot--prefix.filename-template-editable'),
		ownEditable('.filename-template-inline-token-slot--candidates.filename-template-editable'),
		ownEditable('.filename-template-inline-token-slot--suffix.filename-template-editable'),
	].filter((editable) => editable instanceof HTMLElement);
	};

  const focusSiblingEditable = (editable, direction) => {
    if (!(editable instanceof HTMLElement)) {
      return false;
    }
    const currentToken = editable.closest('.filename-template-dom-token');
    if (!(currentToken instanceof HTMLElement)) {
      return false;
    }
    const ownerEditable = currentToken.parentElement instanceof HTMLElement
      ? currentToken.parentElement.closest('.filename-template-editable')
      : null;
    if (!(ownerEditable instanceof HTMLElement) || ownerEditable === editable || ownerEditable._filenameTemplateChipsOnly !== true) {
      return false;
    }
    const adjacentCandidateToken = direction === 'back'
      ? currentToken.previousSibling
      : currentToken.nextSibling;
    if (isTokenNode(adjacentCandidateToken)) {
      setActiveEditable(ownerEditable);
      return setCaretAdjacentToNode(
        ownerEditable,
        adjacentCandidateToken,
        direction === 'back' ? 'fwd' : 'back'
      );
    }
    setActiveEditable(ownerEditable);
    return setCaretAdjacentToNode(ownerEditable, currentToken, direction);
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

	if (editable._filenameTemplateChipsOnly !== true) {
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
		if (focusSiblingEditable(editable, direction)) {
			debugFilenameTemplateNav('owner-sibling-editable', {
				direction,
				from: describeEditable(editable),
			});
			return true;
		}
	}

	const ownerEditable = currentToken.parentElement instanceof HTMLElement
		? currentToken.parentElement.closest('.filename-template-editable')
		: null;
  if (!(ownerEditable instanceof HTMLElement) && focusRootSlotAdjacentToToken(currentToken, direction)) {
    debugFilenameTemplateNav('root-slot-adjacent', {
      direction,
      editable: describeEditable(editable),
    });
    return true;
  }
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

  const writeTokenPartState = (token, part) => {
    if (!(token instanceof HTMLElement)) {
      return;
    }
    const normalized = sanitizeFilenameTemplatePart(part) || defaultFilenameTemplatePart('text');
    token._filenameTemplateLivePart = part && typeof part === 'object'
      ? part
      : normalized;
    token._filenameTemplatePart = normalized;
    try {
      token.dataset.filenameTemplatePart = JSON.stringify(normalized);
    } catch (error) {
      token.dataset.filenameTemplatePart = '';
    }
  };

  const readTokenPartState = (token) => {
    if (!(token instanceof HTMLElement)) {
      return null;
    }
    const live = sanitizeFilenameTemplatePart(token._filenameTemplateLivePart);
    if (live) {
      return live;
    }
    const direct = sanitizeFilenameTemplatePart(token._filenameTemplatePart);
    if (direct) {
      return direct;
    }
    const serialized = typeof token.dataset.filenameTemplatePart === 'string'
      ? token.dataset.filenameTemplatePart
      : '';
    if (!serialized) {
      return null;
    }
    try {
      return sanitizeFilenameTemplatePart(JSON.parse(serialized));
    } catch (error) {
      return null;
    }
  };

  const tokenLivePartState = (token) => {
    if (!(token instanceof HTMLElement)) {
      return null;
    }
    const live = token._filenameTemplateLivePart;
    return live && typeof live === 'object' ? live : null;
  };

  const syncOwnerTokenLivePart = (ownerToken, editable) => {
    if (!(ownerToken instanceof HTMLElement) || !(editable instanceof HTMLElement)) {
      return;
    }
    const liveOwnerPart = tokenLivePartState(ownerToken);
    if (!liveOwnerPart) {
      return;
    }
    const slot = editable.closest('.filename-template-inline-token-slot');
    if (!(slot instanceof HTMLElement)) {
      return;
    }
    if (slot.classList.contains('filename-template-inline-token-slot--prefix')) {
      liveOwnerPart.prefixParts = editable._filenameTemplateTargetParts;
      return;
    }
    if (slot.classList.contains('filename-template-inline-token-slot--suffix')) {
      liveOwnerPart.suffixParts = editable._filenameTemplateTargetParts;
      return;
    }
    if (slot.classList.contains('filename-template-inline-token-slot--candidates')) {
      liveOwnerPart.parts = editable._filenameTemplateTargetParts;
    }
  };

let activeEditable = null;

  const setActiveToken = (editable) => {
    wrapper.querySelectorAll('.filename-template-dom-token.is-active').forEach((node) => node.classList.remove('is-active'));
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    const activeToken = editable.closest('.filename-template-dom-token');
    if (activeToken instanceof HTMLElement) {
      activeToken.classList.add('is-active');
    }
  };

  const clearActiveEditable = () => {
    wrapper.querySelectorAll('.filename-template-editable.is-active').forEach((node) => node.classList.remove('is-active'));
    wrapper.querySelectorAll('.filename-template-dom-token.is-active').forEach((node) => node.classList.remove('is-active'));
    activeEditable = null;
  };

  const setActiveEditable = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      clearActiveEditable();
      return;
    }
    setActiveToken(editable);
    if (activeEditable === editable) {
      sharedContext.insertPart = (part) => insertPartIntoEditable(editable, part);
      return;
    }
    wrapper.querySelectorAll('.filename-template-editable.is-active').forEach((node) => node.classList.remove('is-active'));
    editable.classList.add('is-active');
    activeEditable = editable;
    sharedContext.insertPart = (part) => insertPartIntoEditable(editable, part);
  };

  const rootSequenceSlotTextPart = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      return null;
    }
    if (!Array.isArray(editable._filenameTemplateTargetParts)) {
      return null;
    }
    return editable._filenameTemplateTargetParts[0] || null;
  };

  const normalizeEditableTextNodeChildren = (editable, chipsOnly = false) => {
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    Array.from(editable.childNodes).forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE || isTokenNode(child)) {
        return;
      }
      const fallbackText = child.textContent || '';
      if (!chipsOnly && fallbackText !== '') {
        editable.insertBefore(document.createTextNode(modelTextToEditableText(fallbackText)), child);
      }
      child.remove();
    });
    editable.normalize();
  };

  const readTextValueFromEditable = (editable) => {
    if (!(editable instanceof HTMLElement)) {
      return '';
    }
    normalizeEditableTextNodeChildren(editable, false);
    let value = '';
    Array.from(editable.childNodes).forEach((child) => {
      if (child.nodeType === Node.TEXT_NODE) {
        value += editableTextToModelText(child.nodeValue || '');
      }
    });
    return value;
  };

  const buildRootSequencePartsFromDom = (rootSequence, slotOverride = null) => {
    const nextParts = [];
    const appendText = (value) => {
      const textValue = typeof value === 'string' ? value : '';
      const previous = nextParts[nextParts.length - 1] || null;
      if (previous && previous.type === 'text') {
        previous.value = String(previous.value || '') + textValue;
        return;
      }
      nextParts.push({ type: 'text', value: textValue });
    };

    Array.from(rootSequence.childNodes).forEach((child) => {
      if (isRootTextSlot(child)) {
        if (slotOverride && slotOverride.editable === child) {
          appendText(slotOverride.value);
          return;
        }
        const textPart = rootSequenceSlotTextPart(child);
        if (textPart && textPart.type === 'text') {
          appendText(String(textPart.value || ''));
          return;
        }
        appendText(readTextValueFromEditable(child));
        return;
      }
      if (isTokenNode(child)) {
        const normalized = readTokenPartState(child);
        if (normalized) {
          nextParts.push(normalized);
        }
      }
    });

    return nextParts;
  };

  const syncRootSequenceFromDom = (rootSequence, slotOverride = null) => {
    if (!(rootSequence instanceof HTMLElement) || !Array.isArray(rootSequence._filenameTemplateTargetParts)) {
      return;
    }
    const nextParts = buildRootSequencePartsFromDom(rootSequence, slotOverride);
    replaceParts(rootSequence._filenameTemplateTargetParts, nextParts, false);
    onChange();
  };

  const rootSlotAdjacentToken = (editable, direction) => {
    if (!isRootTextSlot(editable)) {
      return null;
    }
    const sibling = direction === 'back'
      ? editable.previousSibling
      : editable.nextSibling;
    return isTokenNode(sibling) ? sibling : null;
  };

  const focusRootSlotAdjacentToToken = (token, direction) => {
    if (!(token instanceof HTMLElement)) {
      return false;
    }
    const sibling = direction === 'back'
      ? token.previousSibling
      : token.nextSibling;
    if (!isRootTextSlot(sibling)) {
      return false;
    }
    setActiveEditable(sibling);
    return setCaretAtEditableBoundary(sibling, direction === 'back' ? 'fwd' : 'back');
  };

  const syncEditableFromDom = (editable) => {
    if (!(editable instanceof HTMLElement) || !Array.isArray(editable._filenameTemplateTargetParts)) {
      return;
    }
    if (editable.classList.contains('is-root')) {
      syncRootSequenceFromDom(editable);
      return;
    }
    if (isRootTextSlot(editable)) {
      const textValue = readTextValueFromEditable(editable);
      replaceParts(editable._filenameTemplateTargetParts, [{ type: 'text', value: textValue }], false);
      syncRootSequenceFromDom(editable._filenameTemplateRootOwner || null, {
        editable,
        value: textValue,
      });
      return;
    }
    const chipsOnly = editable._filenameTemplateChipsOnly === true;
    normalizeEditableTextNodeChildren(editable, chipsOnly);
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
          appendText(editableTextToModelText(child.nodeValue || ''));
        }
        return;
      }
      if (!isTokenNode(child)) {
        return;
      }
      const normalized = readTokenPartState(child);
      if (normalized) {
        nextParts.push(normalized);
      }
    });

    replaceParts(editable._filenameTemplateTargetParts, nextParts, chipsOnly);
    const ownerToken = editable.closest('.filename-template-dom-token');
    if (ownerToken instanceof HTMLElement) {
      syncOwnerTokenLivePart(ownerToken, editable);
      const ownerPart = tokenLivePartState(ownerToken) || readTokenPartState(ownerToken);
      if (ownerPart) {
        writeTokenPartState(ownerToken, ownerPart);
      }
      const ownerEditable = ownerToken.parentElement instanceof HTMLElement
        ? ownerToken.parentElement.closest('.filename-template-editable')
        : null;
      if (ownerEditable instanceof HTMLElement && ownerEditable !== editable) {
        syncEditableFromDom(ownerEditable);
        return;
      }
      const rootOwner = ownerToken.parentElement instanceof HTMLElement
        && ownerToken.parentElement.classList.contains('filename-template-inline-flow')
        && ownerToken.parentElement.classList.contains('is-root')
          ? ownerToken.parentElement
          : null;
      if (rootOwner instanceof HTMLElement) {
        syncRootSequenceFromDom(rootOwner);
        return;
      }
    }
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
    editable.addEventListener('blur', () => {
      queueMicrotask(() => {
        if (!wrapper.contains(document.activeElement)) {
          clearActiveEditable();
        }
      });
    });
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
        editable.appendChild(document.createTextNode(modelTextToEditableText(part.value || '')));
        return;
      }
      editable.appendChild(createTokenNode(part, editable));
    });
  };

  const buildSlotEditor = (targetParts, placeholder, slotClassName = '') => {
    const chipsOnly = slotClassName.includes('filename-template-inline-token-slot--candidates');
    const wrappedEdgeSlot = slotClassName.includes('filename-template-inline-token-slot--prefix')
      || slotClassName.includes('filename-template-inline-token-slot--suffix');
    targetParts.splice(
      0,
      targetParts.length,
      ...(chipsOnly
        ? sanitizeFilenameTemplateCandidateParts(targetParts)
        : normalizeEditableFilenameTemplateParts(targetParts))
    );
    const slotEditable = document.createElement('span');
    slotEditable.className = `filename-template-inline-token-slot ${slotClassName} filename-template-inline-flow is-slot`.trim();
    if (placeholder) {
      slotEditable.dataset.placeholder = placeholder;
    }
    slotEditable._filenameTemplateTargetParts = targetParts;
    slotEditable._filenameTemplateChipsOnly = chipsOnly;
    attachEditableHandlers(slotEditable);
    renderEditorParts(slotEditable, targetParts);
    if (!wrappedEdgeSlot) {
      return slotEditable;
    }
    const slotWrap = document.createElement('span');
    const wrapRoleClass = slotClassName.includes('filename-template-inline-token-slot--prefix')
      ? 'filename-template-inline-token-slot-wrap--prefix'
      : 'filename-template-inline-token-slot-wrap--suffix';
    slotWrap.className = `filename-template-inline-token-slot-wrap ${wrapRoleClass}`;
    slotWrap.appendChild(slotEditable);
    return slotWrap;
  };

  const buildRootTextSlot = (textValue, rootSequence) => {
    const slotEditable = document.createElement('span');
    slotEditable.className = 'filename-template-root-slot filename-template-inline-flow is-slot';
    slotEditable._filenameTemplateTargetParts = [{ type: 'text', value: String(textValue || '') }];
    slotEditable._filenameTemplateChipsOnly = false;
    slotEditable._filenameTemplateRootOwner = rootSequence;
    attachEditableHandlers(slotEditable);
    renderEditorParts(slotEditable, slotEditable._filenameTemplateTargetParts);
    return slotEditable;
  };

  const createTokenNode = (part, ownerEditable) => {
    const normalizedPart = createPartObject(part);
    const token = document.createElement('span');
    token.className = 'filename-template-dom-token';
    token.setAttribute('contenteditable', 'false');
    writeTokenPartState(token, normalizedPart);
    const tokenPart = sanitizeFilenameTemplatePart(token._filenameTemplatePart) || normalizedPart;
    writeTokenPartState(token, tokenPart);
    const meta = partDisplayMeta(tokenPart);
    if (meta.title) {
      token.title = meta.title;
    }

    const shell = document.createElement('span');
    shell.className = 'filename-template-inline-token-shell';
    shell.appendChild(buildSlotEditor(tokenPart.prefixParts, '', 'filename-template-inline-token-slot--prefix'));

    const center = document.createElement('span');
    center.className = `filename-template-inline-token-center filename-template-inline-token-center--${meta.tone || 'data'}`;
    if (tokenPart.type === 'ifLabels') {
      center.classList.add('filename-template-inline-token-center--stacked');
    }

    const label = document.createElement('span');
    label.className = `filename-template-inline-token filename-template-inline-token--${meta.tone || 'data'}`;
    if (meta.title) {
      label.title = meta.title;
    }
    const labelText = document.createElement('span');
    labelText.className = 'filename-template-inline-token-label';
    labelText.dataset.label = meta.label;
    const labelTextInner = document.createElement('span');
    labelTextInner.className = 'filename-template-inline-token-label-text';
    labelTextInner.textContent = meta.label;
    labelText.appendChild(labelTextInner);
    label.appendChild(labelText);

    const syncPartControlChange = () => {
      writeTokenPartState(token, tokenPart);
      if (ownerEditable instanceof HTMLElement) {
        syncEditableFromDom(ownerEditable);
      } else {
        onChange();
      }
    };

    if (tokenPart.type === 'dataField') {
      center.appendChild(label);
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
        select.value = options.some((option) => option.key === tokenPart.key)
          ? tokenPart.key
          : fallbackKey;
        tokenPart.key = select.value;
      }
      select.title = meta.title || 'Välj vilket datafält som ska skrivas in i filnamnet.';
      select.addEventListener('change', () => {
        tokenPart.key = select.value;
        syncPartControlChange();
      });
      center.appendChild(select);
    } else if (tokenPart.type === 'labels') {
      center.appendChild(label);
      const separatorInput = document.createElement('input');
      separatorInput.type = 'text';
      separatorInput.className = 'filename-template-inline-token-input';
      separatorInput.value = typeof tokenPart.separator === 'string'
        ? tokenPart.separator
        : DEFAULT_FILENAME_TEMPLATE_LABEL_SEPARATOR;
      separatorInput.placeholder = ', ';
      separatorInput.title = 'Separator mellan etiketter i filnamnet.';
      separatorInput.setAttribute('aria-label', 'Separator mellan etiketter');
      separatorInput.addEventListener('input', () => {
        tokenPart.separator = separatorInput.value;
        syncPartControlChange();
      });
      center.appendChild(separatorInput);
    } else if (tokenPart.type === 'ifLabels') {
      const headerRow = document.createElement('div');
      headerRow.className = 'filename-template-inline-token-header-row';
      headerRow.appendChild(label);

      const modeSelect = document.createElement('select');
      modeSelect.className = 'filename-template-inline-token-select filename-template-inline-token-select--mode';
      [
        ['any', 'Någon'],
        ['all', 'Alla'],
      ].forEach(([value, labelText]) => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = labelText;
        modeSelect.appendChild(option);
      });
      modeSelect.value = sanitizeIfLabelsMode(tokenPart.mode);
      tokenPart.mode = modeSelect.value;
      modeSelect.title = 'Välj om någon eller alla valda etiketter måste finnas.';
      modeSelect.addEventListener('change', () => {
        tokenPart.mode = sanitizeIfLabelsMode(modeSelect.value);
        syncPartControlChange();
      });
      headerRow.appendChild(createFloatingField('Matcha', modeSelect, 'filename-template-inline-token-floating-field filename-template-inline-token-floating-field--header'));
      center.appendChild(headerRow);

      const labelPicker = createFilenameTemplateLabelPicker(tokenPart.labelIds, (nextLabelIds) => {
        tokenPart.labelIds = normalizeLabelIdList(nextLabelIds);
        syncPartControlChange();
      }, {
        placeholder: 'Lägg till etikett...',
      });
      center.appendChild(createFloatingField('Etiketter', labelPicker, 'filename-template-inline-token-floating-field'));

      center.appendChild(createFloatingField(
        'Om sant',
        buildSlotEditor(
          tokenPart.thenParts,
          '',
          'filename-template-inline-token-slot--branch'
        ),
        'filename-template-inline-token-floating-field filename-template-inline-token-branch-field'
      ));

      center.appendChild(createFloatingField(
        'Om falskt',
        buildSlotEditor(
          tokenPart.elseParts,
          '',
          'filename-template-inline-token-slot--branch'
        ),
        'filename-template-inline-token-floating-field filename-template-inline-token-branch-field'
      ));
    } else if (tokenPart.type === 'firstAvailable') {
      center.appendChild(label);
      center.appendChild(buildSlotEditor(
        tokenPart.parts,
        'Kandidater',
        'filename-template-inline-token-slot--candidates'
      ));
    } else {
      center.appendChild(label);
    }

    shell.appendChild(center);

    shell.appendChild(buildSlotEditor(tokenPart.suffixParts, '', 'filename-template-inline-token-slot--suffix'));
    token.appendChild(shell);
    return token;
  };

  const renderRootSequence = (rootSequence, targetParts) => {
    if (!(rootSequence instanceof HTMLElement)) {
      return;
    }
    const normalizedParts = normalizeEditableFilenameTemplateParts(collapseTextParts(targetParts));
    const entries = [{ type: 'text', value: '' }];
    normalizedParts.forEach((part) => {
      if (!part || typeof part !== 'object') {
        return;
      }
      if (part.type === 'text') {
        entries[entries.length - 1].value = String(entries[entries.length - 1].value || '') + String(part.value || '');
        return;
      }
      const normalized = sanitizeFilenameTemplatePart(part);
      if (!normalized || normalized.type === 'text') {
        return;
      }
      entries.push(normalized);
      entries.push({ type: 'text', value: '' });
    });

    rootSequence.replaceChildren();
    entries.forEach((entry) => {
      if (entry.type === 'text') {
        rootSequence.appendChild(buildRootTextSlot(entry.value || '', rootSequence));
        return;
      }
      rootSequence.appendChild(createTokenNode(entry, rootSequence));
    });
  };

  const buildInsertedPartsFromEditable = (editable, part) => {
    if (!(editable instanceof HTMLElement) || !Array.isArray(editable._filenameTemplateTargetParts)) {
      return null;
    }

    const chipsOnly = editable._filenameTemplateChipsOnly === true;
    const range = ensureRangeInEditable(editable);
    if (!range) {
      return null;
    }

    const domChildren = Array.from(editable.childNodes);
    const nextParts = [];
    const appendText = (value) => {
      if (chipsOnly) {
        return;
      }
      const textValue = typeof value === 'string' ? value : '';
      if (textValue === '') {
        return;
      }
      const previous = nextParts[nextParts.length - 1] || null;
      if (previous && previous.type === 'text') {
        previous.value = String(previous.value || '') + textValue;
        return;
      }
      nextParts.push({ type: 'text', value: textValue });
    };
    let insertedPartIndex = -1;
    const appendInsertedPart = () => {
      insertedPartIndex = nextParts.length;
      nextParts.push(createPartObject(part));
    };

    let inserted = false;
    const insertAtEditableOffset = (offset) => {
      if (inserted || range.startContainer !== editable) {
        return false;
      }
      const safeOffset = Math.max(0, Math.min(offset, domChildren.length));
      if (range.startOffset !== safeOffset) {
        return false;
      }
      appendInsertedPart();
      inserted = true;
      return true;
    };

    domChildren.forEach((child, index) => {
      insertAtEditableOffset(index);

      if (child.nodeType === Node.TEXT_NODE) {
        const textValue = child.nodeValue || '';
        if (range.startContainer === child) {
          const safeOffset = Math.max(0, Math.min(range.startOffset, textValue.length));
          appendText(editableTextToModelText(textValue.slice(0, safeOffset)));
          if (!inserted) {
            appendInsertedPart();
            inserted = true;
          }
          appendText(editableTextToModelText(textValue.slice(safeOffset)));
        } else {
          appendText(editableTextToModelText(textValue));
        }
        return;
      }

      if (!isTokenNode(child)) {
        if (!chipsOnly) {
          appendText(editableTextToModelText(child.textContent || ''));
        }
        return;
      }

      const normalized = readTokenPartState(child);
      if (normalized) {
        nextParts.push(normalized);
      }
    });

    if (!inserted) {
      insertAtEditableOffset(domChildren.length);
    }
    if (!inserted) {
      appendInsertedPart();
    }

    return {
      parts: nextParts,
      insertedPartIndex,
    };
  };

  const insertPartIntoEditable = (editable, part) => {
    if (!(editable instanceof HTMLElement)) {
      return;
    }
    const rootOwner = editable._filenameTemplateRootOwner instanceof HTMLElement
      ? editable._filenameTemplateRootOwner
      : null;
    if (rootOwner) {
      const insertion = buildInsertedPartsFromEditable(editable, part);
      if (!insertion) {
        return;
      }
      const { parts: insertedParts, insertedPartIndex } = insertion;
      const nextRootParts = [];
      let insertedTokenGlobalIndex = -1;
      let tokenCount = 0;

      Array.from(rootOwner.childNodes).forEach((child) => {
        if (child === editable) {
          insertedParts.forEach((candidate, candidateIndex) => {
            const normalized = sanitizeFilenameTemplatePart(candidate);
            if (!normalized) {
              return;
            }
            nextRootParts.push(normalized);
            if (normalized.type !== 'text') {
              if (candidateIndex === insertedPartIndex) {
                insertedTokenGlobalIndex = tokenCount;
              }
              tokenCount += 1;
            }
          });
          return;
        }
        if (isRootTextSlot(child)) {
          const textPart = rootSequenceSlotTextPart(child);
          nextRootParts.push({ type: 'text', value: String(textPart && textPart.type === 'text' ? textPart.value || '' : '') });
          return;
        }
        if (isTokenNode(child)) {
          const normalized = readTokenPartState(child);
          if (normalized) {
            nextRootParts.push(normalized);
            tokenCount += 1;
          }
        }
      });

      replaceParts(rootOwner._filenameTemplateTargetParts, nextRootParts, false);
      renderRootSequence(rootOwner, rootOwner._filenameTemplateTargetParts);
      onChange();

      if (insertedTokenGlobalIndex >= 0) {
        const tokenNodes = Array.from(rootOwner.querySelectorAll(':scope > .filename-template-dom-token'));
        const insertedTokenNode = tokenNodes[insertedTokenGlobalIndex] || null;
        const followingSlot = insertedTokenNode instanceof HTMLElement && isRootTextSlot(insertedTokenNode.nextSibling)
          ? insertedTokenNode.nextSibling
          : null;
        if (followingSlot instanceof HTMLElement) {
          setActiveEditable(followingSlot);
          setCaretAtEditableBoundary(followingSlot, 'back');
          return;
        }
      }

      const trailingSlot = Array.from(rootOwner.childNodes).reverse().find((child) => isRootTextSlot(child)) || null;
      if (trailingSlot instanceof HTMLElement) {
        setActiveEditable(trailingSlot);
        setCaretToEnd(trailingSlot);
      }
      return;
    }
    const insertion = buildInsertedPartsFromEditable(editable, part);
    if (!insertion) {
      return;
    }
    const { parts: nextParts, insertedPartIndex } = insertion;
    replaceParts(editable._filenameTemplateTargetParts, nextParts, editable._filenameTemplateChipsOnly === true);
    renderEditorParts(editable, editable._filenameTemplateTargetParts);
    onChange();

    if (insertedPartIndex >= 0) {
      const insertedTokenIndex = nextParts
        .slice(0, insertedPartIndex + 1)
        .filter((candidate) => candidate && typeof candidate === 'object' && candidate.type !== 'text')
        .length - 1;
      const tokenNodes = Array.from(editable.querySelectorAll(':scope > .filename-template-dom-token'));
      const insertedTokenNode = tokenNodes[insertedTokenIndex] || null;
      if (insertedTokenNode instanceof HTMLElement) {
        setActiveEditable(editable);
        if (setCaretAdjacentToNode(editable, insertedTokenNode, 'fwd')) {
          return;
        }
      }
    }
    setActiveEditable(editable);
    setCaretToEnd(editable);
  };

  sequence._filenameTemplateTargetParts = parts;
  if (!isRootEditor) {
    attachEditableHandlers(sequence);
    sequence.dataset.placeholder = inlinePlaceholder;
    renderEditorParts(sequence, parts);
  } else {
    renderRootSequence(sequence, parts);
  }
  if (isRootEditor) {
    const scrollShell = document.createElement('div');
    scrollShell.className = 'filename-template-inline-scroll';
    scrollShell.appendChild(sequence);
    wrapper.appendChild(scrollShell);
  } else {
    wrapper.appendChild(sequence);
  }
  if (autoFocus && !context && isRootEditor) {
    const trailingSlot = Array.from(sequence.childNodes).reverse().find((child) => isRootTextSlot(child)) || null;
    if (trailingSlot instanceof HTMLElement) {
      setActiveEditable(trailingSlot);
    }
  } else if (autoFocus && !context && !isSlotEditor) {
    setActiveEditable(sequence);
  }

  return wrapper;
}

function syncCategoriesEditorValidation() {
  if (!(archiveStructureListEl instanceof HTMLElement)) {
    return;
  }
  const message = archiveStructureValidationError();
  archiveStructureListEl.dataset.validationError = message;
}

function renderArchiveStructureEditor() {
  if (!(archiveStructureListEl instanceof HTMLElement)) {
    return;
  }

  archiveStructureListEl.innerHTML = '';
  const folderSortMode = ['name', 'priority', 'path'].includes(archiveStructureFolderSortMode)
    ? archiveStructureFolderSortMode
    : 'path';

  if (archiveStructureFolderSortEl instanceof HTMLSelectElement) {
    archiveStructureFolderSortEl.value = folderSortMode;
  }

  const renderSectionLabel = (text) => {
    const label = document.createElement('div');
    label.className = 'archive-folders-label';
    label.textContent = text;
    archiveStructureListEl.appendChild(label);
  };

  const createMoveButton = (label, title, hidden, onClick) => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'archive-icon-button archive-move-button';
    button.textContent = label;
    button.title = title;
    button.classList.toggle('is-hidden-preserve-space', hidden === true);
    button.setAttribute('aria-hidden', hidden === true ? 'true' : 'false');
    button.tabIndex = hidden === true ? -1 : 0;
    button.addEventListener('click', onClick);
    return button;
  };

  const archiveFolderPathSortText = (folder) => {
    const template = sanitizeFilenameTemplate(folder && folder.pathTemplate && typeof folder.pathTemplate === 'object'
      ? folder.pathTemplate
      : (folder && folder.path));
    const textParts = Array.isArray(template.parts)
      ? template.parts
        .filter((part) => part && typeof part === 'object' && part.type === 'text' && String(part.value || '') !== '')
        .map((part) => String(part.value || ''))
      : [];
    return textParts.join(' ').trim().toLocaleLowerCase('sv');
  };

  const folderEntries = archiveFoldersDraft.map((folder, index) => {
    const folderDraft = sanitizeArchiveFolder(folder, index);
    archiveFoldersDraft[index] = folderDraft;
    return {
      folderDraft,
      folderIndex: index,
    };
  });

  folderEntries.sort((left, right) => {
    if (folderSortMode === 'name') {
      const compare = archiveFolderDisplayName(left.folderDraft).localeCompare(
        archiveFolderDisplayName(right.folderDraft),
        'sv',
        { sensitivity: 'base', numeric: true }
      );
      if (compare !== 0) {
        return compare;
      }
    } else if (folderSortMode === 'priority') {
      const compare = (right.folderDraft.priority || 1) - (left.folderDraft.priority || 1);
      if (compare !== 0) {
        return compare;
      }
    } else if (folderSortMode === 'path') {
      const compare = archiveFolderPathSortText(left.folderDraft).localeCompare(
        archiveFolderPathSortText(right.folderDraft),
        'sv',
        { sensitivity: 'base', numeric: true }
      );
      if (compare !== 0) {
        return compare;
      }
    }

    return left.folderIndex - right.folderIndex;
  });

  renderSectionLabel('Mappar');
  if (folderEntries.length < 1) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'Inga mappar ännu.';
    archiveStructureListEl.appendChild(empty);
  }
  folderEntries.forEach(({ folderDraft, folderIndex }) => {

    const node = document.createElement('div');
    node.className = 'tree-node tree-folder';
    const row = createTreeRow({ markerless: true });
    const body = document.createElement('div');
    body.className = 'tree-body folder-body';
    appendTreeBodyIcon(body, 'tree-body-icon tree-body-icon-folder');

    const fields = document.createElement('div');
    fields.className = 'folder-fields';

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.placeholder = 'Ex: "Fakturor"';
    nameInput.value = folderDraft.name;
    nameInput.addEventListener('input', () => {
      archiveFoldersDraft[folderIndex].name = nameInput.value;
      updateSettingsActionButtons();
    });
    nameInput.addEventListener('change', () => {
      if (folderSortMode === 'name') {
        renderArchiveStructureEditor();
      }
    });

    const priorityInput = document.createElement('input');
    priorityInput.type = 'number';
    priorityInput.min = '1';
    priorityInput.step = '1';
    priorityInput.inputMode = 'numeric';
    priorityInput.value = String(folderDraft.priority || 1);
    priorityInput.addEventListener('input', () => {
      archiveFoldersDraft[folderIndex].priority = sanitizePositiveInt(priorityInput.value, 1);
      updateSettingsActionButtons();
    });
    priorityInput.addEventListener('change', () => {
      if (folderSortMode === 'priority') {
        renderArchiveStructureEditor();
      }
    });

    const removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'archive-danger-button archive-compact-button';
    removeButton.textContent = 'Ta bort mapp';
    removeButton.addEventListener('click', () => {
      archiveFoldersDraft.splice(folderIndex, 1);
      renderArchiveStructureEditor();
      updateSettingsActionButtons();
    });

    fields.appendChild(createFloatingField('Namn', nameInput));
    fields.appendChild(createFloatingField('Prioritet', priorityInput));
    fields.appendChild(removeButton);
    body.appendChild(fields);

    const templateLabel = document.createElement('div');
    templateLabel.className = 'archive-level-label';
    templateLabel.textContent = 'Sökvägsmall';
    body.appendChild(templateLabel);

    body.appendChild(
      createFilenameTemplatePartsEditor(
        folderDraft.pathTemplate.parts,
        () => {
          updateSettingsActionButtons();
        },
        0,
        null,
        { focusToolbar: true, autoFocus: false }
      )
    );

    const templatesLabel = document.createElement('div');
    templatesLabel.className = 'archive-level-label';
    templatesLabel.textContent = 'Filnamnsregler';
    body.appendChild(templatesLabel);

    const templatesList = createTreeChildren({ markerless: true });
    if (folderDraft.filenameTemplates.length < 1) {
      const empty = document.createElement('div');
      empty.className = 'categories-empty';
      empty.textContent = 'Inga filnamnsregler i mappen ännu.';
      templatesList.appendChild(empty);
    }

    folderDraft.filenameTemplates.forEach((template, templateIndex) => {
      const templateDraft = sanitizeFilenameTemplateDraft(template, templateIndex, folderDraft.id);
      archiveFoldersDraft[folderIndex].filenameTemplates[templateIndex] = templateDraft;

      const templateNode = document.createElement('div');
      templateNode.className = 'tree-node tree-category has-parent';
      const templateRow = createTreeRow({ markerless: true });
      const templateBody = document.createElement('div');
      templateBody.className = 'tree-body category-body';
      appendTreeBodyIcon(templateBody, 'tree-body-icon tree-body-icon-category');

      const templateFields = document.createElement('div');
      templateFields.className = 'category-fields category-fields--wide';

      templateFields.appendChild(createMoveButton('↑', 'Flytta upp', templateIndex < 1, () => {
        archiveFoldersDraft[folderIndex].filenameTemplates = moveArrayItem(
          archiveFoldersDraft[folderIndex].filenameTemplates,
          templateIndex,
          templateIndex - 1
        );
        renderArchiveStructureEditor();
        updateSettingsActionButtons();
      }));
      templateFields.appendChild(createMoveButton('↓', 'Flytta ner', templateIndex >= folderDraft.filenameTemplates.length - 1, () => {
        archiveFoldersDraft[folderIndex].filenameTemplates = moveArrayItem(
          archiveFoldersDraft[folderIndex].filenameTemplates,
          templateIndex,
          templateIndex + 1
        );
        renderArchiveStructureEditor();
        updateSettingsActionButtons();
      }));

      const removeTemplateButton = document.createElement('button');
      removeTemplateButton.type = 'button';
      removeTemplateButton.className = 'archive-danger-button archive-compact-button';
      removeTemplateButton.textContent = 'Ta bort filnamnsregel';
      removeTemplateButton.addEventListener('click', () => {
        archiveFoldersDraft[folderIndex].filenameTemplates.splice(templateIndex, 1);
        renderArchiveStructureEditor();
        updateSettingsActionButtons();
      });
      templateFields.appendChild(removeTemplateButton);
      templateBody.appendChild(templateFields);

      const templateConditionsLabel = document.createElement('div');
      templateConditionsLabel.className = 'archive-level-label';
      templateConditionsLabel.textContent = 'Matchande etiketter';
      templateBody.appendChild(templateConditionsLabel);
      templateBody.appendChild(
        createFilenameTemplateLabelPicker(
        templateDraft.labelIds,
        (nextLabelIds) => {
          archiveFoldersDraft[folderIndex].filenameTemplates[templateIndex].labelIds = nextLabelIds;
          updateSettingsActionButtons();
        },
        {
          placeholder: 'Lägg till etikett...',
        }
      ));

      const templateLabel = document.createElement('div');
      templateLabel.className = 'archive-level-label';
      templateLabel.textContent = 'Filnamnsmall';
      templateBody.appendChild(templateLabel);
      templateBody.appendChild(
        createFilenameTemplatePartsEditor(
          templateDraft.template.parts,
          () => {
            updateSettingsActionButtons();
          },
          0,
          null,
          { focusToolbar: true, autoFocus: false }
        )
      );

      templateRow.appendChild(templateBody);
      templateNode.appendChild(templateRow);
      templatesList.appendChild(templateNode);
    });

    const addTemplateButton = document.createElement('button');
    addTemplateButton.type = 'button';
    addTemplateButton.className = 'archive-add-button';
    addTemplateButton.textContent = 'Lägg till filnamnsregel';
    addTemplateButton.addEventListener('click', () => {
      archiveFoldersDraft[folderIndex].filenameTemplates.push(defaultFilenameTemplateDraft());
      renderArchiveStructureEditor();
      updateSettingsActionButtons();
    });
    templatesList.appendChild(addTemplateButton);
    body.appendChild(templatesList);

    row.appendChild(body);
    node.appendChild(row);
    archiveStructureListEl.appendChild(node);
  });

  syncCategoriesEditorValidation();
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
      ? 'merged_objects.txt byggs från sammanfogade OCR-objekt'
      : 'merged_objects.txt byggs från sammanfogade OCR-objekt';
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
    throw new Error('Huvudmän måste vara en JSON-lista');
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
  if (!payload || !Array.isArray(payload.replacements) || !payload.positionAdjustment || typeof payload.positionAdjustment !== 'object') {
    throw new Error('Ogiltigt svar för matchningsinställningar');
  }

  matchingDraft = payload.replacements.map(sanitizeReplacement);
  matchingPositionAdjustmentDraft = sanitizeMatchingPositionAdjustmentSettings(payload.positionAdjustment);
  matchingDataFieldAcceptanceThresholdDraft = payload.dataFieldAcceptanceThreshold ?? 0.5;
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }
  matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingPositionAdjustmentDraft, matchingDataFieldAcceptanceThresholdDraft);
  renderMatchingEditor();
  updateSettingsActionButtons();
}

async function loadSendersSettings() {
  const response = await fetch('/api/get-senders.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda avsändare');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.senders) || !Array.isArray(payload.unlinkedIdentifiers)) {
    throw new Error('Ogiltigt svar för avsändare');
  }

  sendersDraft = payload.senders.map(sanitizeSenderDraft);
  sendersUnlinkedIdentifiers = payload.unlinkedIdentifiers.map(sanitizeUnlinkedSenderIdentifier);
  sendersBaselineJson = normalizedSendersJson(sendersDraft);
  clearSenderSelections();
  closeSenderMergeOverlay();
  renderSendersEditor();
  setSendersPanelTab();
  updateSettingsActionButtons();
}

async function loadPathSettings() {
  const response = await fetch('/api/get-config.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda konfiguration');
  }

  const payload = await response.json();
  if (!payload || typeof payload.outputBaseDirectory !== 'string' || typeof payload.inboxDirectory !== 'string') {
    throw new Error('Ogiltigt svar för konfiguration');
  }

  inputInboxPathEl.value = payload.inboxDirectory;
  outputBasePathEl.value = payload.outputBaseDirectory;
  inboxPathBaselineValue = normalizedPathValue(payload.inboxDirectory);
  pathsBaselineValue = normalizedPathValue(payload.outputBaseDirectory);
  updateSettingsActionButtons();
}

async function loadSystemSettings() {
  const payload = await loadChromeExtensionConfig();
  stateUpdateTransport = sanitizeStateUpdateTransport(payload.stateUpdateTransport, stateUpdateTransport);
  if (systemStateTransportEl) {
    systemStateTransportEl.value = stateUpdateTransport;
  }
  await pingChromeExtension();
  renderSystemChromeExtensionStatus();
  updateSystemChromeExtensionDebug('');
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

async function loadArchiveStructure() {
  const requestLocalRevision = archivingRulesLocalRevision;
  const response = await fetch('/api/get-categories.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Kunde inte ladda arkivstruktur');
  }

  const payload = await response.json();
  if (
    !payload
    || !Array.isArray(payload.archiveFolders)
  ) {
    throw new Error('Ogiltigt svar för arkivstruktur');
  }

  archiveFoldersDraft = payload.archiveFolders.map((folder, index) => sanitizeArchiveFolder(folder, index));
  archiveStructureBaselineJson = normalizedArchiveStructureJson();
  applyArchivingRulesPayloadFromResponse(payload, {
    forceRender: true,
    expectedLocalRevision: requestLocalRevision
  });
  const selectedJob = findJobById(selectedJobId);
  if (selectedJob) {
    syncFilenameField(selectedJob);
    updateArchiveAction(selectedJob);
  }
  renderArchiveStructureEditor();
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

  const requestLocalRevision = archivingRulesLocalRevision;
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
  applyArchivingRulesPayloadFromResponse(payload, {
    forceRender: true,
    expectedLocalRevision: requestLocalRevision
  });
  const selectedJob = findJobById(selectedJobId);
  if (selectedJob) {
    setLabelsForJob(selectedJob);
    syncFilenameField(selectedJob);
  }
  renderLabelsEditor();
  if (archiveStructureListEl) {
    renderArchiveStructureEditor();
  }
  updateSettingsActionButtons();
}

async function loadExtractionFields() {
  const requestLocalRevision = archivingRulesLocalRevision;
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
  applyArchivingRulesPayloadFromResponse(payload, {
    forceRender: true,
    expectedLocalRevision: requestLocalRevision
  });
  renderExtractionFieldsEditor();
  renderSystemExtractionFieldsEditor();
  if (archiveStructureListEl) {
    renderArchiveStructureEditor();
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

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.clients)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara huvudmän';
    throw new Error(message);
  }

  clientsDraft = payload.clients.map(sanitizeClientDraft);
  clientsBaselineJson = normalizedClientsJson(clientsDraft);
  renderClientsEditor();
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
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.senders) || !Array.isArray(payload.unlinkedIdentifiers)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara avsändare';
    throw new Error(message);
  }

  sendersDraft = payload.senders.map(sanitizeSenderDraft);
  sendersUnlinkedIdentifiers = payload.unlinkedIdentifiers.map(sanitizeUnlinkedSenderIdentifier);
  sendersBaselineJson = normalizedSendersJson(sendersDraft);
  clearSenderSelections();
  closeSenderMergeOverlay();
  renderSendersEditor();
  setSendersPanelTab();
  updateSettingsActionButtons();
  await fetchState({ refreshSenders: true });
}

async function saveMatchingSettings() {
  const normalized = matchingDraft.map(sanitizeReplacement);
  const positionAdjustment = sanitizeMatchingPositionAdjustmentSettings(matchingPositionAdjustmentDraft);
  const response = await fetch('/api/save-matching-settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      replacements: normalized,
      positionAdjustment,
      dataFieldAcceptanceThreshold: matchingDataFieldAcceptanceThresholdDraft
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.replacements) || !payload.positionAdjustment || typeof payload.positionAdjustment !== 'object') {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara matchningsinställningar';
    throw new Error(message);
  }

  matchingDraft = payload.replacements.map(sanitizeReplacement);
  matchingPositionAdjustmentDraft = sanitizeMatchingPositionAdjustmentSettings(payload.positionAdjustment);
  matchingDataFieldAcceptanceThresholdDraft = payload.dataFieldAcceptanceThreshold ?? 0.5;
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }
  matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingPositionAdjustmentDraft, matchingDataFieldAcceptanceThresholdDraft);
  renderMatchingEditor();
  watchReprocessedJobIdsFromPayload(payload);
  updateSettingsActionButtons();
}

async function saveArchiveStructure() {
  const validationError = archiveStructureValidationError();
  if (validationError) {
    throw new Error(validationError);
  }

  const normalizedArchiveFolders = archiveFoldersDraft.map((folder, index) => sanitizeArchiveFolder(folder, index));
  const response = await fetch('/api/save-categories.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      archiveFolders: normalizedArchiveFolders,
    })
  });

  const payload = await response.json().catch(() => null);
  if (
    !response.ok
    || !payload
    || payload.ok !== true
    || !Array.isArray(payload.archiveFolders)
  ) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara arkivstruktur';
    throw new Error(message);
  }

  archiveFoldersDraft = payload.archiveFolders.map((folder, index) => sanitizeArchiveFolder(folder, index));
  archiveStructureBaselineJson = normalizedArchiveStructureJson();
  const selectedJob = findJobById(selectedJobId);
  if (selectedJob) {
    syncFilenameField(selectedJob);
    updateArchiveAction(selectedJob);
  }
  applyArchivingRulesPayloadFromResponse(payload, { bumpLocalRevision: true, forceRender: true });
  renderArchiveStructureEditor();
  watchReprocessedJobIdsFromPayload(payload);
  updateSettingsActionButtons();
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
  const selectedJob = findJobById(selectedJobId);
  if (selectedJob) {
    setLabelsForJob(selectedJob);
    syncFilenameField(selectedJob);
  }
  renderLabelsEditor();
  if (archiveStructureListEl) {
    renderArchiveStructureEditor();
  }
  applyArchivingRulesPayloadFromResponse(payload, { bumpLocalRevision: true, forceRender: true });
  watchReprocessedJobIdsFromPayload(payload);
  updateSettingsActionButtons();
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
  if (archiveStructureListEl) {
    renderArchiveStructureEditor();
  }
  applyArchivingRulesPayloadFromResponse(payload, { bumpLocalRevision: true, forceRender: true });
  watchReprocessedJobIdsFromPayload(payload);
  updateSettingsActionButtons();
}

async function savePathSettings() {
  const response = await fetch('/api/save-config.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      inboxDirectory: inputInboxPathEl.value,
      outputBaseDirectory: outputBasePathEl.value
    })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Kunde inte spara sökvägsinställningar';
    throw new Error(message);
  }

  inboxPathBaselineValue = normalizedPathValue(inputInboxPathEl.value);
  pathsBaselineValue = normalizedPathValue(outputBasePathEl.value);
  inputInboxPathEl.value = inboxPathBaselineValue;
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
  watchReprocessedJobIdsFromPayload(payload);
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

  const resetJobIds = Array.isArray(payload.resetJobIds)
    ? payload.resetJobIds
      .map((jobId) => typeof jobId === 'string' ? jobId.trim() : '')
      .filter((jobId) => jobId !== '')
    : [];

  clearOcrViewCache();
  loadedOcrJobId = '';
  loadedOcrSource = '';
  loadedMatchesJobId = '';
  loadedMetaJobId = '';
  clearPdfFrames();
  closeSettingsModal();
  if (resetJobIds.length > 0) {
    applyOptimisticBulkReset(resetJobIds);
    startBulkResetWatch(resetJobIds.length);
  } else {
    clearBulkResetWatch();
  }
  requestStateRefresh(0);
}

function applyOptimisticBulkReset(resetJobIds) {
  const resetIdSet = new Set(
    Array.isArray(resetJobIds)
      ? resetJobIds.filter((jobId) => typeof jobId === 'string' && jobId !== '')
      : []
  );
  if (resetIdSet.size === 0) {
    return;
  }

  const removableJobs = []
    .concat(Array.isArray(state.processingJobs) ? state.processingJobs : [])
    .concat(Array.isArray(state.readyJobs) ? state.readyJobs : [])
    .concat(Array.isArray(state.failedJobs) ? state.failedJobs : [])
    .filter((job) => job && typeof job.id === 'string' && resetIdSet.has(job.id));
  const placeholderCreatedAt = new Date().toISOString();
  const processingPlaceholders = removableJobs.map((job) => ({
    ...job,
    id: `bulk-reset:${job.id}`,
    status: 'processing',
    error: null,
    archived: false,
    matchedClientDirName: '',
    createdAt: typeof job.createdAt === 'string' && job.createdAt !== '' ? job.createdAt : placeholderCreatedAt,
  }));

  if (selectedJobId && resetIdSet.has(selectedJobId)) {
    selectedJobId = '';
  }

  applyState({
    processingJobs: sortJobsForList('processingJobs', processingPlaceholders),
    readyJobs: Array.isArray(state.readyJobs)
      ? state.readyJobs.filter((job) => job && !resetIdSet.has(job.id))
      : [],
    archivedJobs: state.archivedJobs,
    failedJobs: Array.isArray(state.failedJobs)
      ? state.failedJobs.filter((job) => job && !resetIdSet.has(job.id))
      : [],
  });
}

function cloneCurrentStateForRollback() {
  return {
    processingJobs: Array.isArray(state.processingJobs) ? state.processingJobs.map((job) => ({ ...job })) : [],
    readyJobs: Array.isArray(state.readyJobs) ? state.readyJobs.map((job) => ({ ...job })) : [],
    archivedJobs: Array.isArray(state.archivedJobs) ? state.archivedJobs.map((job) => ({ ...job })) : [],
    failedJobs: Array.isArray(state.failedJobs) ? state.failedJobs.map((job) => ({ ...job })) : [],
    clients: state.clients,
    senders: state.senders,
    archiveFolders: state.archiveFolders,
  };
}

async function reprocessSingleJob(jobId, mode, options = {}) {
  const rollbackState = cloneCurrentStateForRollback();
  applyOptimisticReprocess(jobId, mode, options);
  try {
    const response = await fetch('/api/reset-jobs.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        jobId,
        mode,
        forceOcr: options.forceOcr === true,
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

    reprocessWatchJobIds.add(jobId);
    requestStateRefresh(0);
  } catch (error) {
    reprocessWatchJobIds.delete(jobId);
    pruneReprocessWatchJobs();
    applyState(rollbackState);
    throw error;
  }
}

async function handleSelectedJobReprocess(mode, options = {}) {
  if (!selectedJobId) {
    return;
  }

  try {
    await reprocessSingleJob(selectedJobId, mode, options);
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

if (jobListMenuButtonEl instanceof HTMLButtonElement) {
  jobListMenuButtonEl.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    toggleJobListMenu();
  });
}

if (ocrMenuButtonEl instanceof HTMLButtonElement) {
  ocrMenuButtonEl.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    toggleOcrMenu();
  });
}

if (selectedJobActionsMenuButtonEl instanceof HTMLButtonElement) {
  selectedJobActionsMenuButtonEl.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    toggleSelectedJobActionsMenu();
  });
}

if (jobListReanalyzeAllActionEl instanceof HTMLButtonElement) {
  jobListReanalyzeAllActionEl.addEventListener('click', async () => {
    closeJobListMenu();
    jobListReanalyzeAllActionEl.disabled = true;
    try {
      await reanalyzeAllDocuments();
    } catch (error) {
      alert(error.message || 'Kunde inte köra om analysen för alla dokument.');
    } finally {
      jobListReanalyzeAllActionEl.disabled = false;
    }
  });
}

if (ocrDownloadActionEl instanceof HTMLButtonElement) {
  ocrDownloadActionEl.addEventListener('click', async () => {
    closeOcrMenu();
    ocrDownloadActionEl.disabled = true;
    try {
      await downloadCurrentOcrRepresentation();
    } catch (error) {
      alert(error instanceof Error ? error.message : 'Kunde inte ladda ner OCR-representationen.');
    } finally {
      syncOcrMenuState(findJobById(selectedJobId));
    }
  });
}

if (selectedJobDeleteActionEl instanceof HTMLButtonElement) {
  selectedJobDeleteActionEl.addEventListener('click', async () => {
    await deleteSelectedJob();
  });
}

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
  if (selectedJob && selectedJob.archived === true && !archivedReviewModeActiveForJob(selectedJob)) {
    setClientForJob(selectedJob);
    return;
  }

  applySelectedClientValue(clientSelectEl.value);
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
  if (selectedJob && selectedJob.archived === true && !archivedReviewModeActiveForJob(selectedJob)) {
    setSenderForJob(selectedJob);
    renderSelectedJobSenderSection(selectedJob);
    return;
  }

  const value = senderSelectEl.value;
  applySelectedSenderValue(value);
});

folderSelectEl.addEventListener('change', () => {
  if (folderSelectEl.value === EDIT_ARCHIVE_STRUCTURE_OPTION_VALUE) {
    const selectedJob = state.readyJobs.find((job) => job.id === selectedJobId) || null;
    openArchiveStructureSettingsDirect().finally(() => {
      setFolderForJob(selectedJob);
    });
    return;
  }

  if (!selectedJobId) {
    return;
  }

  const selectedJob = findJobById(selectedJobId);
  if (selectedJob && selectedJob.archived === true && !archivedReviewModeActiveForJob(selectedJob)) {
    setFolderForJob(selectedJob);
    return;
  }

  applySelectedFolderValue(folderSelectEl.value);
});

if (resetClientActionEl instanceof HTMLButtonElement) {
  resetClientActionEl.addEventListener('click', () => {
    resetSelectedJobFieldToProposed('client').catch((error) => {
      alert(error.message || 'Kunde inte återställa huvudman.');
    });
  });
}

if (resetSenderActionEl instanceof HTMLButtonElement) {
  resetSenderActionEl.addEventListener('click', () => {
    resetSelectedJobFieldToProposed('sender').catch((error) => {
      alert(error.message || 'Kunde inte återställa avsändare.');
    });
  });
}

if (resetFolderActionEl instanceof HTMLButtonElement) {
  resetFolderActionEl.addEventListener('click', () => {
    resetSelectedJobFieldToProposed('folder').catch((error) => {
      alert(error.message || 'Kunde inte återställa mapp.');
    });
  });
}

if (jobLabelsFieldEl instanceof HTMLButtonElement) {
  jobLabelsFieldEl.addEventListener('click', (event) => {
    event.preventDefault();
    openJobLabelsOverlay({ focusOverlay: true });
  });
}

if (resetLabelsActionEl instanceof HTMLButtonElement) {
  resetLabelsActionEl.addEventListener('click', () => {
    resetSelectedJobFieldToProposed('labels').catch((error) => {
      alert(error.message || 'Kunde inte återställa etiketter.');
    });
  });
}

if (jobLabelsComboboxEl instanceof HTMLInputElement) {
  jobLabelsComboboxEl.addEventListener('focus', () => {
    if (!jobLabelsOverlayOpen) {
      return;
    }
    jobLabelsDropdownOpen = true;
    renderJobLabelsOverlay(findJobById(selectedJobId));
  });

  jobLabelsComboboxEl.addEventListener('blur', () => {
    window.requestAnimationFrame(() => {
      if (!jobLabelsOverlayOpen || document.activeElement === jobLabelsComboboxEl) {
        return;
      }
      jobLabelsDropdownOpen = false;
      renderJobLabelsOverlay(findJobById(selectedJobId));
    });
  });

  jobLabelsComboboxEl.addEventListener('input', () => {
    jobLabelsFilterText = jobLabelsComboboxEl.value;
    jobLabelsDropdownOpen = true;
    jobLabelsActiveOptionIndex = 0;
    renderJobLabelsOverlay(findJobById(selectedJobId));
  });

  jobLabelsComboboxEl.addEventListener('keydown', async (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeJobLabelsOverlay({ restoreFocus: true });
      return;
    }
    if (event.key === 'ArrowDown') {
      if (jobLabelsRenderedOptions.length < 1) {
        return;
      }
      event.preventDefault();
      jobLabelsDropdownOpen = true;
      jobLabelsActiveOptionIndex = jobLabelsActiveOptionIndex < 0
        ? 0
        : Math.min(jobLabelsActiveOptionIndex + 1, jobLabelsRenderedOptions.length - 1);
      renderJobLabelsOverlay(findJobById(selectedJobId));
      return;
    }
    if (event.key === 'ArrowUp') {
      if (jobLabelsRenderedOptions.length < 1) {
        return;
      }
      event.preventDefault();
      jobLabelsDropdownOpen = true;
      jobLabelsActiveOptionIndex = jobLabelsActiveOptionIndex < 0
        ? jobLabelsRenderedOptions.length - 1
        : Math.max(jobLabelsActiveOptionIndex - 1, 0);
      renderJobLabelsOverlay(findJobById(selectedJobId));
      return;
    }
    if (event.key === 'Enter') {
      event.preventDefault();
      await commitJobLabelsComboboxOption();
    }
  });
}

if (jobLabelsOverlayEl instanceof HTMLElement) {
  jobLabelsOverlayEl.addEventListener('pointerdown', () => {
    jobLabelsOverlayPointerDownInside = true;
    window.requestAnimationFrame(() => {
      jobLabelsOverlayPointerDownInside = false;
    });
  });
  jobLabelsOverlayEl.addEventListener('focusin', () => {
    if (jobLabelsOverlayMutating) {
      jobLabelsOverlayMutating = false;
    }
  });
}

if (jobLabelsOverlayCloseEl instanceof HTMLButtonElement) {
  jobLabelsOverlayCloseEl.addEventListener('click', () => {
    closeJobLabelsOverlay({ restoreFocus: true });
  });
}

if (jobLabelsSelectedEl instanceof HTMLElement) {
  jobLabelsSelectedEl.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      event.preventDefault();
      closeJobLabelsOverlay({ restoreFocus: true });
    }
  });
}

if (jobLabelsFieldGroupEl instanceof HTMLElement) {
  jobLabelsFieldGroupEl.addEventListener('focusout', (event) => {
    if (!jobLabelsOverlayOpen) {
      return;
    }
    if (jobLabelsOverlayMutating) {
      return;
    }
    if (jobLabelsOverlayPointerDownInside) {
      return;
    }
    const nextFocusedElement = event.relatedTarget;
    if (nextFocusedElement instanceof Node && jobLabelsFieldGroupEl.contains(nextFocusedElement)) {
      return;
    }
    if (nextFocusedElement instanceof Node) {
      closeJobLabelsOverlay();
      return;
    }
    window.requestAnimationFrame(() => {
      if (!jobLabelsOverlayOpen || !(jobLabelsFieldGroupEl instanceof HTMLElement)) {
        return;
      }
      const activeElement = document.activeElement;
      if (activeElement instanceof Node && jobLabelsFieldGroupEl.contains(activeElement)) {
        return;
      }
      if (
        activeElement === document.body
        || activeElement === document.documentElement
      ) {
        if (jobLabelsOverlayEl instanceof HTMLElement) {
          jobLabelsOverlayEl.focus({ preventScroll: true });
        }
        return;
      }
      closeJobLabelsOverlay();
    });
  });
}

filenameInputEl.addEventListener('input', () => {
  applySelectedFilenameValue(filenameInputEl.value);
});

filenameInputEl.addEventListener('focus', () => {
  setFilenameFieldExpanded(true);
});

filenameInputEl.addEventListener('click', () => {
  setFilenameFieldExpanded(true);
});

filenameInputEl.addEventListener('blur', () => {
  window.requestAnimationFrame(() => {
    if (document.activeElement === filenameInputEl) {
      return;
    }
    setFilenameFieldExpanded(false);
  });
});

filenameInputEl.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') {
    return;
  }
  event.preventDefault();
  setFilenameFieldExpanded(false);
  filenameInputEl.blur();
});

if (resetFilenameActionEl instanceof HTMLButtonElement) {
  resetFilenameActionEl.addEventListener('click', () => {
    resetSelectedJobFieldToProposed('filename').catch((error) => {
      alert(error.message || 'Kunde inte återställa filnamn.');
    });
  });
}

archiveActionEl.addEventListener('click', async () => {
  const selectedJob = findJobById(selectedJobId);
  if (!selectedJob) {
    return;
  }

  if (archivedReviewModeActiveForJob(selectedJob)) {
    const payload = selectedArchivedReviewPayload(selectedJob);
    if (!payload || payload.isActionable !== true) {
      return;
    }
    updateArchivedReviewDraftFromSidebar(selectedJob);
    const action = archivedReviewDraftMatchesProposal(selectedJob, payload) ? 'use-new' : 'manual';
    archiveActionEl.disabled = true;
    try {
      await saveArchivedReviewAction(action);
    } catch (error) {
      alert(error.message || 'Kunde inte uppdatera den arkiverade posten.');
    } finally {
      updateArchiveAction(findJobById(selectedJobId));
    }
    return;
  }

  const action = selectedJob.archived === true ? 'restore' : 'archive';
  if (action === 'archive' && selectedJobAnalysisOutdated(selectedJob)) {
    const choice = await showAnalysisOutdatedArchiveDialog();
    if (choice === 'cancel') {
      return;
    }
    if (choice === 'reprocess') {
      await handleSelectedJobReprocess('post-ocr');
      return;
    }
  }
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
        selectedFolderId: effectiveFolderId(selectedJob) || null,
        selectedLabelIds: effectiveSelectedLabelIds(selectedJob),
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
    if (action === 'restore') {
      applyOptimisticArchivedReviewUnarchive(selectedJob);
    }
    applyStateEntry(payload.entry);
  } catch (error) {
    restoreSelectedJobEditorState();
    alert(error.message || 'Kunde inte uppdatera arkiveringen.');
  } finally {
    updateArchiveAction(findJobById(selectedJobId));
  }
});

if (dismissArchivedUpdateActionEl instanceof HTMLButtonElement) {
  dismissArchivedUpdateActionEl.addEventListener('click', async () => {
    const selectedJob = findJobById(selectedJobId);
    if (!selectedJob || !archivedReviewModeActiveForJob(selectedJob)) {
      return;
    }
    dismissArchivedUpdateActionEl.disabled = true;
    try {
      await saveArchivedReviewAction('dismiss');
    } catch (error) {
      alert(error.message || 'Kunde inte avfärda dokumentet för aktuell version.');
    } finally {
      updateArchiveAction(findJobById(selectedJobId));
    }
  });
}

settingsButtonEl.addEventListener('click', async () => {
  if (!settingsModalEl.classList.contains('hidden') && !canLeaveCurrentSettingsView()) {
    return;
  }

  const tabId = activeSettingsTabId || 'clients';
  openSettingsModal();
  setSettingsTab(tabId);

  try {
    await ensureSettingsPanelReady(tabId);
  } catch (error) {
    alert('Kunde inte ladda inställningar.');
  }
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
        matchingPositionAdjustmentDraft = defaultMatchingPositionAdjustmentSettings();
        matchingDataFieldAcceptanceThresholdDraft = 0.5;
        matchingBaselineJson = normalizedMatchingJson(matchingDraft, matchingPositionAdjustmentDraft, matchingDataFieldAcceptanceThresholdDraft);
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
      } else if (tabId === 'archive-structure') {
        alert('Kunde inte ladda arkivstruktur.');
        archiveFoldersDraft = [];
            archiveStructureBaselineJson = normalizedArchiveStructureJson();
        renderArchiveStructureEditor();
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
        inboxPathBaselineValue = normalizedPathValue(inputInboxPathEl ? inputInboxPathEl.value : '');
        pathsBaselineValue = normalizedPathValue(outputBasePathEl ? outputBasePathEl.value : '');
      }
      updateSettingsActionButtons();
    }
  });
});

settingsCloseEl.addEventListener('click', () => {
  closeSettingsModal();
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

if (settingsDialogEl instanceof HTMLElement) {
  settingsDialogEl.addEventListener('pointermove', (event) => {
    const target = event.target;
    const isInteractive = target instanceof HTMLElement && target.closest('button, input, select, textarea, a, label');
    const rect = settingsDialogEl.getBoundingClientRect();
    const isTopDragArea = (event.clientY - rect.top) <= SETTINGS_DIALOG_DRAG_HANDLE_HEIGHT_PX;
    settingsDialogEl.style.cursor = !isInteractive && isTopDragArea ? 'move' : '';
  });
  settingsDialogEl.addEventListener('pointerleave', () => {
    settingsDialogEl.style.cursor = '';
  });
  settingsDialogEl.addEventListener('pointerdown', (event) => {
    if (event.button !== 0) {
      return;
    }
    const target = event.target;
    if (target instanceof HTMLElement && target.closest('button, input, select, textarea, a, label')) {
      return;
    }
    const rect = settingsDialogEl.getBoundingClientRect();
    if ((event.clientY - rect.top) > SETTINGS_DIALOG_DRAG_HANDLE_HEIGHT_PX) {
      return;
    }
    settingsDialogDragState = {
      startX: event.clientX,
      startY: event.clientY,
      left: settingsDialogLayout ? settingsDialogLayout.left : rect.left,
      top: settingsDialogLayout ? settingsDialogLayout.top : rect.top,
    };
    settingsDialogResizeState = null;
    document.body.classList.add('is-dragging-settings-dialog');
    event.preventDefault();
  });
}

if (settingsDialogResizeHandleEl instanceof HTMLElement) {
  settingsDialogResizeHandleEl.addEventListener('pointerdown', (event) => {
    if (!(settingsDialogEl instanceof HTMLElement) || event.button !== 0) {
      return;
    }
    const rect = settingsDialogEl.getBoundingClientRect();
    settingsDialogResizeState = {
      startX: event.clientX,
      startY: event.clientY,
      left: settingsDialogLayout ? settingsDialogLayout.left : rect.left,
      top: settingsDialogLayout ? settingsDialogLayout.top : rect.top,
      width: settingsDialogLayout ? settingsDialogLayout.width : rect.width,
      height: settingsDialogLayout ? settingsDialogLayout.height : rect.height,
    };
    settingsDialogDragState = null;
    document.body.classList.add('is-resizing-settings-dialog');
    event.preventDefault();
  });
}

document.addEventListener('pointermove', (event) => {
  if (settingsDialogDragState) {
    applySettingsDialogLayout({
      ...settingsDialogLayout,
      left: settingsDialogDragState.left + (event.clientX - settingsDialogDragState.startX),
      top: settingsDialogDragState.top + (event.clientY - settingsDialogDragState.startY),
    });
    event.preventDefault();
    return;
  }

  if (settingsDialogResizeState) {
    applySettingsDialogLayout({
      left: settingsDialogResizeState.left,
      top: settingsDialogResizeState.top,
      width: settingsDialogResizeState.width + (event.clientX - settingsDialogResizeState.startX),
      height: settingsDialogResizeState.height + (event.clientY - settingsDialogResizeState.startY),
    });
    event.preventDefault();
  }
});

document.addEventListener('pointerup', () => {
  if (!settingsDialogDragState && !settingsDialogResizeState) {
    return;
  }
  stopSettingsDialogInteractions();
});

document.addEventListener('pointercancel', () => {
  if (!settingsDialogDragState && !settingsDialogResizeState) {
    return;
  }
  stopSettingsDialogInteractions();
});

window.addEventListener('resize', () => {
  if (!settingsDialogLayout) {
    return;
  }
  applySettingsDialogLayout(settingsDialogLayout);
});

document.addEventListener('pointerdown', (event) => {
  if (
    jobListMenuWrapEl instanceof HTMLElement
    && jobListMenuButtonEl instanceof HTMLButtonElement
    && jobListMenuEl instanceof HTMLElement
    && !jobListMenuEl.classList.contains('hidden')
    && !jobListMenuWrapEl.contains(event.target)
  ) {
    closeJobListMenu();
  }

  if (
    ocrMenuWrapEl instanceof HTMLElement
    && ocrMenuButtonEl instanceof HTMLButtonElement
    && ocrMenuEl instanceof HTMLElement
    && !ocrMenuEl.classList.contains('hidden')
    && !ocrMenuWrapEl.contains(event.target)
  ) {
    closeOcrMenu();
  }

  if (
    selectedJobActionsMenuWrapEl instanceof HTMLElement
    && selectedJobActionsMenuButtonEl instanceof HTMLButtonElement
    && selectedJobActionsMenuEl instanceof HTMLElement
    && !selectedJobActionsMenuEl.classList.contains('hidden')
    && !selectedJobActionsMenuWrapEl.contains(event.target)
  ) {
    closeSelectedJobActionsMenu();
  }

  if (
    appNoticesEl instanceof HTMLElement
    && appNoticesOverflowOpen
    && !appNoticesEl.contains(event.target)
  ) {
    closeAppNoticesOverflow();
  }

  if (!jobLabelsOverlayOpen || !(jobLabelsFieldGroupEl instanceof HTMLElement)) {
    return;
  }
  if (jobLabelsFieldGroupEl.contains(event.target)) {
    return;
  }
  closeJobLabelsOverlay();
});

if (mainEl instanceof HTMLElement) {
  mainEl.addEventListener('pointerdown', (event) => {
    if (!jobLabelsOverlayOpen || !(jobLabelsFieldGroupEl instanceof HTMLElement)) {
      return;
    }
    if (event.target instanceof Node && jobLabelsFieldGroupEl.contains(event.target)) {
      return;
    }
    closeJobLabelsOverlay();
  }, true);
}

pdfFrameEls.forEach((frameEl) => {
  frameEl.addEventListener('pointerdown', () => {
    if (ocrMenuEl instanceof HTMLElement && !ocrMenuEl.classList.contains('hidden')) {
      closeOcrMenu();
    }
    if (!jobLabelsOverlayOpen) {
      if (appNoticesOverflowOpen) {
        closeAppNoticesOverflow();
      }
      return;
    }
    closeJobLabelsOverlay();
    if (appNoticesOverflowOpen) {
      closeAppNoticesOverflow();
    }
  }, true);
  frameEl.addEventListener('focus', () => {
    if (ocrMenuEl instanceof HTMLElement && !ocrMenuEl.classList.contains('hidden')) {
      closeOcrMenu();
    }
    if (!jobLabelsOverlayOpen) {
      if (appNoticesOverflowOpen) {
        closeAppNoticesOverflow();
      }
      return;
    }
    closeJobLabelsOverlay();
    if (appNoticesOverflowOpen) {
      closeAppNoticesOverflow();
    }
  }, true);
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && appNoticesOverflowOpen) {
    closeAppNoticesOverflow();
    return;
  }

  if (event.key === 'Escape' && jobListMenuEl instanceof HTMLElement && !jobListMenuEl.classList.contains('hidden')) {
    closeJobListMenu();
    return;
  }

  if (event.key === 'Escape' && ocrMenuEl instanceof HTMLElement && !ocrMenuEl.classList.contains('hidden')) {
    closeOcrMenu();
    return;
  }

  if (event.key === 'Escape' && selectedJobActionsMenuEl instanceof HTMLElement && !selectedJobActionsMenuEl.classList.contains('hidden')) {
    closeSelectedJobActionsMenu();
    return;
  }

  if (event.key === 'Escape' && jobLabelsOverlayOpen) {
    event.preventDefault();
    closeJobLabelsOverlay({ restoreFocus: true });
    return;
  }

  if (event.key === 'Escape' && !settingsModalEl.classList.contains('hidden')) {
    closeSettingsModal();
    return;
  }

  if (jobLabelsOverlayOpen) {
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

window.addEventListener('resize', () => {
  scheduleJobLabelsSummaryRender();
  syncFilenameExpandedWidth(findJobById(selectedJobId));
  renderAppNotices();
});

async function fetchState(options = {}) {
  if (pollInFlight) {
    pendingFetchStateOptions = mergeFetchStateOptions(pendingFetchStateOptions, options);
    return;
  }

  const refreshClients = options.refreshClients === true;
  const refreshSenders = options.refreshSenders === true;
  const refreshArchiveStructure = options.refreshArchiveStructure === true;
  const force = options.force === true;
  const includeClients = !hasLoadedClients || refreshClients;
  const includeSenders = !hasLoadedSenders || refreshSenders;
  const includeArchiveStructure = !hasLoadedCategories || refreshArchiveStructure;

  pollInFlight = true;
  const startedArchivingRulesLocalRevision = archivingRulesLocalRevision;
  try {
    const params = new URLSearchParams();
    if (includeClients) {
      params.set('includeClients', '1');
    }
    if (includeSenders) {
      params.set('includeSenders', '1');
    }
    if (includeArchiveStructure) {
      params.set('includeArchiveStructure', '1');
    }
    if (!force && !includeClients && !includeSenders && !includeArchiveStructure && hasLoadedInitialJobsState) {
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
    console.info(
      '[Docflow] fetchState raw senderOrganizationLookupQueue',
      JSON.stringify(nextState && nextState.senderOrganizationLookupQueue ? nextState.senderOrganizationLookupQueue : null)
    );
    console.info(
      '[Docflow] fetchState raw senderPayeeLookupQueue',
      JSON.stringify(nextState && nextState.senderPayeeLookupQueue ? nextState.senderPayeeLookupQueue : null)
    );

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

    const shouldApplyArchivingRules = startedArchivingRulesLocalRevision === archivingRulesLocalRevision;

    applyState({
      processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : [],
      readyJobs: nextState.readyJobs,
      archivedJobs: nextState.archivedJobs,
      failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : [],
      senderOrganizationLookupQueue: nextState.senderOrganizationLookupQueue && typeof nextState.senderOrganizationLookupQueue === 'object'
        ? nextState.senderOrganizationLookupQueue
        : undefined,
      senderPayeeLookupQueue: nextState.senderPayeeLookupQueue && typeof nextState.senderPayeeLookupQueue === 'object'
        ? nextState.senderPayeeLookupQueue
        : undefined,
      archivingRules: shouldApplyArchivingRules && nextState.archivingRules && typeof nextState.archivingRules === 'object'
        ? nextState.archivingRules
        : undefined,
      clients: includeClients && Array.isArray(nextState.clients) ? nextState.clients : undefined,
      senders: includeSenders && Array.isArray(nextState.senders) ? nextState.senders : undefined,
      archiveFolders: includeArchiveStructure && Array.isArray(nextState.archiveFolders) ? nextState.archiveFolders : undefined,
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
    if (includeArchiveStructure) {
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
    if (pendingFetchStateOptions) {
      const nextQueuedOptions = pendingFetchStateOptions;
      pendingFetchStateOptions = null;
      queueMicrotask(() => {
        fetchState(nextQueuedOptions);
      });
    } else if (chromeExtensionIsUsable()) {
      maybeStartChromeExtensionOrganizationLookup();
      maybeStartChromeExtensionPayeeLookup();
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
      const payloadEventId = Number.parseInt(String(jobEvent.id || ''), 10);
      const effectiveEventId = Number.isInteger(payloadEventId) && payloadEventId > 0
        ? payloadEventId
        : eventId;
      if (Number.isInteger(effectiveEventId) && effectiveEventId > 0 && effectiveEventId <= stateEventCursor) {
        return;
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
    // Let EventSource retry on its own while SSE remains the active transport.
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
restoreSidebarListSizePercent();
if (sidebarSplitterEl instanceof HTMLElement) {
  sidebarSplitterEl.addEventListener('pointerdown', startSidebarSplitDrag);
  window.addEventListener('pointermove', handleSidebarSplitPointerMove);
  window.addEventListener('pointerup', stopSidebarSplitDrag);
  window.addEventListener('pointercancel', stopSidebarSplitDrag);
}
applyHashState();
window.addEventListener('hashchange', () => {
  applyHashState();
});
document.addEventListener('visibilitychange', () => {
  if (document.hidden) {
    return;
  }
  if (shouldMonitorChromeExtensionPresence()) {
    scheduleChromeExtensionPresenceCheck(100);
  }
  if (shouldRetrySwedbankLookupAfterLogin()) {
    pingChromeExtension().finally(() => {
      maybeStartChromeExtensionPayeeLookup();
    });
  }
  if (shouldRetryOrganizationLookupAfterOpen()) {
    pingChromeExtension().finally(() => {
      maybeStartChromeExtensionOrganizationLookup();
    });
  }
});
window.addEventListener('focus', () => {
  if (shouldMonitorChromeExtensionPresence()) {
    scheduleChromeExtensionPresenceCheck(100);
  }
  if (shouldRetrySwedbankLookupAfterLogin()) {
    pingChromeExtension().finally(() => {
      maybeStartChromeExtensionPayeeLookup();
    });
  }
  if (shouldRetryOrganizationLookupAfterOpen()) {
    pingChromeExtension().finally(() => {
      maybeStartChromeExtensionOrganizationLookup();
    });
  }
});
Promise.all([
  fetchState({ refreshSenders: true }),
  loadArchiveStructure().catch(() => {
    archiveFoldersDraft = [];
    archiveStructureBaselineJson = normalizedArchiveStructureJson();
    console.error('Kunde inte ladda arkivstruktur vid app-start.');
  }),
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
  pingChromeExtension({ reloadConfig: true }).catch(() => {
    console.error('Kunde inte kontrollera Chrome-tillägget vid app-start.');
  }),
]).finally(() => {
  syncStateUpdateTransport();
  if (chromeExtensionIsUsable()) {
    maybeStartChromeExtensionOrganizationLookup();
    maybeStartChromeExtensionPayeeLookup();
  }
});

window.docflowExtensionDebug = {
  get runtime() {
    return { ...chromeExtensionRuntime };
  },
  get queue() {
    return currentSenderPayeeLookupQueue();
  },
  get organizationQueue() {
    return currentSenderOrganizationLookupQueue();
  },
  get stateQueue() {
    return state && state.senderPayeeLookupQueue ? { ...state.senderPayeeLookupQueue } : null;
  },
  get stateOrganizationQueue() {
    return state && state.senderOrganizationLookupQueue ? { ...state.senderOrganizationLookupQueue } : null;
  },
  async fetchLiveQueue() {
    const response = await fetch('/api/get-state.php?includeSenders=1', { cache: 'no-store' });
    const payload = await response.json();
    console.info('[Docflow] fetchLiveQueue payload', {
      senderPayeeLookupQueue: payload && payload.senderPayeeLookupQueue ? payload.senderPayeeLookupQueue : null,
      senderOrganizationLookupQueue: payload && payload.senderOrganizationLookupQueue ? payload.senderOrganizationLookupQueue : null,
    });
    return {
      senderPayeeLookupQueue: payload && payload.senderPayeeLookupQueue ? payload.senderPayeeLookupQueue : null,
      senderOrganizationLookupQueue: payload && payload.senderOrganizationLookupQueue ? payload.senderOrganizationLookupQueue : null,
    };
  },
};
