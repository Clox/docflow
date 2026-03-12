const jobListEl = document.getElementById('job-list');
const viewerEl = document.getElementById('pdf-viewer');
const ocrViewEl = document.getElementById('ocr-view');
const matchesViewEl = document.getElementById('matches-view');
const viewModeEl = document.getElementById('view-mode');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const clientSelectEl = document.getElementById('client-select');
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

let state = {
  processingJobs: [],
  readyJobs: [],
  failedJobs: [],
  clients: [],
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
let loadedJobId = '';
let loadedOcrJobId = '';
let loadedMatchesJobId = '';
let pollTimer = null;
let pollInFlight = false;
let currentViewMode = 'pdf';
let ocrRequestSeq = 0;
let matchesRequestSeq = 0;
let categoriesDraft = [];
let systemCategoriesDraft = createDefaultSystemCategories();
let matchingDraft = [];
let activeSettingsTabId = 'clients';
let activeArchiveTabId = 'categories';
let clientsBaselineText = '';
let matchingBaselineJson = '[]';
let pathsBaselineValue = '';
let categoriesBaselineJson = JSON.stringify({
  archiveFolders: [],
  systemCategories: systemCategoriesDraft
});
const selectedClientByJobId = new Map();
const selectedCategoryByJobId = new Map();

clientSelectEl.disabled = true;
categorySelectEl.disabled = true;

function setProcessingInfo(processingJobs) {
  if (!Array.isArray(processingJobs) || processingJobs.length === 0) {
    processingIndicatorEl.classList.add('hidden');
    processingTextEl.textContent = '';
    return;
  }

  processingIndicatorEl.classList.remove('hidden');
  processingTextEl.textContent = `Processing ${processingJobs.length} file(s)...`;
}

function renderClientSelect(clients) {
  const currentValue = clientSelectEl.value;
  clientSelectEl.innerHTML = '<option value="" hidden>Choose client</option>';

  clients.forEach((client) => {
    const option = document.createElement('option');
    option.value = client.dirName;
    option.textContent = client.dirName;
    clientSelectEl.appendChild(option);
  });

  if (currentValue && clients.some((client) => client.dirName === currentValue)) {
    clientSelectEl.value = currentValue;
  } else {
    clientSelectEl.value = '';
  }
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
  const currentValue = categorySelectEl.value;
  categorySelectEl.innerHTML = '<option value="" hidden>Choose category</option>';

  categories.forEach((category) => {
    const displayName = categoryDisplayName(category);
    const id = category && typeof category.id === 'string' ? category.id.trim() : '';
    if (id === '') {
      return;
    }
    if (displayName === '') {
      return;
    }

    const option = document.createElement('option');
    option.value = id;
    option.textContent = displayName;
    categorySelectEl.appendChild(option);
  });

  if (currentValue && Array.from(categorySelectEl.options).some((option) => option.value === currentValue)) {
    categorySelectEl.value = currentValue;
  } else {
    categorySelectEl.value = '';
  }
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

function setViewerJob(jobId) {
  if (currentViewMode === 'ocr') {
    setViewerOcr(jobId);
  } else if (currentViewMode === 'matches') {
    setViewerMatches(jobId);
  } else {
    setViewerPdf(jobId);
  }
}

function setViewerPdf(jobId) {
  ocrViewEl.classList.add('hidden');
  matchesViewEl.classList.add('hidden');
  viewerEl.classList.remove('hidden');

  if (!jobId) {
    loadedJobId = '';
    viewerEl.removeAttribute('src');
    return;
  }

  if (loadedJobId === jobId) {
    return;
  }

  loadedJobId = jobId;
  viewerEl.src = '/api/get-job-pdf.php?id=' + encodeURIComponent(jobId);
}

async function setViewerOcr(jobId) {
  matchesViewEl.classList.add('hidden');
  viewerEl.classList.add('hidden');
  ocrViewEl.classList.remove('hidden');

  if (!jobId) {
    loadedOcrJobId = '';
    ocrViewEl.textContent = '';
    return;
  }

  if (loadedOcrJobId === jobId) {
    return;
  }

  loadedOcrJobId = jobId;
  const requestSeq = ++ocrRequestSeq;
  ocrViewEl.textContent = 'Loading OCR data...';

  try {
    const response = await fetch('/api/get-job-ocr.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Failed to fetch OCR');
    }

    const payload = await response.json();
    if (requestSeq !== ocrRequestSeq) {
      return;
    }

    const text = payload && typeof payload.text === 'string' ? payload.text : '';
    ocrViewEl.textContent = text || '(No OCR text found)';
  } catch (error) {
    if (requestSeq !== ocrRequestSeq) {
      return;
    }
    ocrViewEl.textContent = 'Could not load OCR data.';
  }
}

function renderMatchesContent(categories) {
  matchesViewEl.innerHTML = '';

  const header = document.createElement('h3');
  header.className = 'matches-header';
  header.textContent = 'Kategorier';
  matchesViewEl.appendChild(header);

  if (!Array.isArray(categories) || categories.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'matches-empty';
    empty.textContent = 'Inga kategorimatchningar hittades.';
    matchesViewEl.appendChild(empty);
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
      : 'Unnamed category';
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
  matchesViewEl.appendChild(tableWrap);
}

async function setViewerMatches(jobId) {
  viewerEl.classList.add('hidden');
  ocrViewEl.classList.add('hidden');
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
  loading.textContent = 'Loading matches...';
  matchesViewEl.appendChild(loading);

  try {
    const response = await fetch('/api/get-job-matches.php?id=' + encodeURIComponent(jobId), { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Failed to fetch matches');
    }

    const payload = await response.json();
    if (requestSeq !== matchesRequestSeq) {
      return;
    }

    const categories = payload && Array.isArray(payload.categories) ? payload.categories : [];
    renderMatchesContent(categories);
  } catch (error) {
    if (requestSeq !== matchesRequestSeq) {
      return;
    }
    matchesViewEl.innerHTML = '';
    const fail = document.createElement('div');
    fail.className = 'matches-empty';
    fail.textContent = 'Could not load match data.';
    matchesViewEl.appendChild(fail);
  }
}

function renderJobList(readyJobs) {
  jobListEl.innerHTML = '';

  if (readyJobs.length === 0) {
    const li = document.createElement('li');
    li.className = 'job-message';
    li.textContent = 'No ready jobs yet.';
    jobListEl.appendChild(li);
    return;
  }

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
      selectedJobId = job.id;
      renderJobList(state.readyJobs);
      setViewerJob(selectedJobId);
      setClientForJob(job);
      setCategoryForJob(job);
    });

    jobListEl.appendChild(li);
  });
}

function refreshSelection() {
  const readyJobs = state.readyJobs;

  if (readyJobs.length === 0) {
    selectedJobId = '';
    setViewerJob('');
    setClientForJob(null);
    setCategoryForJob(null);
    return;
  }

  const stillExists = readyJobs.some((job) => job.id === selectedJobId);
  if (!stillExists) {
    selectedJobId = readyJobs[0].id;
  }

  const selectedJob = readyJobs.find((job) => job.id === selectedJobId) || null;
  setViewerJob(selectedJobId);
  setClientForJob(selectedJob);
  setCategoryForJob(selectedJob);
}

function applyState(nextState) {
  state = nextState;
  const validJobIds = new Set(state.readyJobs.map((job) => job.id));
  Array.from(selectedClientByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedClientByJobId.delete(jobId);
    }
  });
  Array.from(selectedCategoryByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedCategoryByJobId.delete(jobId);
    }
  });

  setProcessingInfo(state.processingJobs);
  renderClientSelect(state.clients);
  renderCategorySelect(state.categories);
  refreshSelection();
  renderJobList(state.readyJobs);
}

function openSettingsModal() {
  settingsModalEl.classList.remove('hidden');
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

  const panelIds = ['clients', 'matching', 'categories', 'jobs', 'paths'];
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
  return tabId === 'clients' || tabId === 'matching' || tabId === 'categories' || tabId === 'paths';
}

function normalizedPathValue(value) {
  return String(value).trim();
}

function normalizedMatchingJson(replacements) {
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
  return normalizedMatchingJson(matchingDraft) !== matchingBaselineJson;
}

function isCategoriesDirty() {
  return normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft) !== categoriesBaselineJson;
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
  if (tabId === 'paths') {
    return isPathsDirty();
  }
  return false;
}

function hasAnyUnsavedSettingsChanges() {
  return isClientsDirty() || isMatchingDirty() || isCategoriesDirty() || isPathsDirty();
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
  if (tabId === 'paths') {
    return [pathsCancelEl, pathsApplyEl];
  }
  return [];
}

function updateSettingsActionButtons() {
  const clientsDirty = isClientsDirty();
  const matchingDirty = isMatchingDirty();
  const categoriesDirty = isCategoriesDirty();
  const pathsDirty = isPathsDirty();

  clientsCancelEl.disabled = !clientsDirty;
  clientsApplyEl.disabled = !clientsDirty;

  matchingCancelEl.disabled = !matchingDirty;
  matchingApplyEl.disabled = !matchingDirty;

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
    empty.textContent = 'No substitutions yet.';
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

    rowEl.appendChild(createFloatingField('From', fromInput, 'matching-char-field'));
    rowEl.appendChild(createFloatingField('To', toInput, 'matching-char-field'));

    if (rowIndex > 0) {
      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'rule-remove';
      removeButton.textContent = 'Remove';
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

async function loadClientsText() {
  const response = await fetch('/api/get-clients.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Failed to load clients');
  }

  const payload = await response.json();
  if (!payload || typeof payload.text !== 'string') {
    throw new Error('Invalid clients response');
  }

  clientsTextareaEl.value = payload.text;
  clientsBaselineText = payload.text;
  updateSettingsActionButtons();
}

async function loadMatchingSettings() {
  const response = await fetch('/api/get-matching-settings.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Failed to load matching settings');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.replacements)) {
    throw new Error('Invalid matching settings response');
  }

  matchingDraft = payload.replacements.map(sanitizeReplacement);
  if (matchingDraft.length === 0) {
    matchingDraft = [defaultReplacement()];
  }

  matchingBaselineJson = normalizedMatchingJson(matchingDraft);
  renderMatchingEditor();
  updateSettingsActionButtons();
}

async function loadPathSettings() {
  const response = await fetch('/api/get-config.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Failed to load config');
  }

  const payload = await response.json();
  if (!payload || typeof payload.outputBaseDirectory !== 'string') {
    throw new Error('Invalid config response');
  }

  outputBasePathEl.value = payload.outputBaseDirectory;
  pathsBaselineValue = normalizedPathValue(payload.outputBaseDirectory);
  updateSettingsActionButtons();
}

async function loadCategories() {
  const response = await fetch('/api/get-categories.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Failed to load archive structure');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.archiveFolders) || !payload.systemCategories || typeof payload.systemCategories !== 'object') {
    throw new Error('Invalid archive structure response');
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
    throw new Error('Failed to save clients');
  }

  clientsBaselineText = clientsTextareaEl.value;
  updateSettingsActionButtons();
  await fetchState();
}

async function saveMatchingSettings() {
  const normalized = matchingDraft.map(sanitizeReplacement);
  const response = await fetch('/api/save-matching-settings.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ replacements: normalized })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true || !Array.isArray(payload.replacements)) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Failed to save matching settings';
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
      : 'Failed to save archive structure';
    throw new Error(message);
  }

  categoriesDraft = payload.archiveFolders.map(sanitizeArchiveFolder);
  systemCategoriesDraft = sanitizeSystemCategories(payload.systemCategories);
  categoriesBaselineJson = normalizedCategoriesJson(categoriesDraft, systemCategoriesDraft);
  renderCategoriesEditor();
  renderSystemCategoryEditor();
  updateSettingsActionButtons();
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
      : 'Failed to save path settings';
    throw new Error(message);
  }

  pathsBaselineValue = normalizedPathValue(outputBasePathEl.value);
  outputBasePathEl.value = pathsBaselineValue;
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
    throw new Error('Failed to reset jobs');
  }

  const payload = await response.json();
  if (!payload || payload.ok !== true) {
    throw new Error('Reset jobs failed');
  }

  loadedJobId = '';
  loadedOcrJobId = '';
  loadedMatchesJobId = '';
  selectedJobId = '';
  closeSettingsModal();
  await fetchState();
}

viewModeEl.addEventListener('change', () => {
  if (viewModeEl.value === 'ocr') {
    currentViewMode = 'ocr';
  } else if (viewModeEl.value === 'matches') {
    currentViewMode = 'matches';
  } else {
    currentViewMode = 'pdf';
  }

  loadedOcrJobId = '';
  loadedMatchesJobId = '';
  setViewerJob(selectedJobId);
});

clientSelectEl.addEventListener('change', () => {
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

categorySelectEl.addEventListener('change', () => {
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

outputBasePathEl.addEventListener('input', () => {
  updateSettingsActionButtons();
});

settingsButtonEl.addEventListener('click', async () => {
  openSettingsModal();
  setSettingsTab('clients');
  try {
    await loadClientsText();
  } catch (error) {
    alert('Could not load clients.');
    clientsBaselineText = clientsTextareaEl.value;
    updateSettingsActionButtons();
  }
  try {
    await loadMatchingSettings();
  } catch (error) {
    alert('Could not load matching settings.');
    matchingDraft = [defaultReplacement()];
    matchingBaselineJson = normalizedMatchingJson(matchingDraft);
    renderMatchingEditor();
    updateSettingsActionButtons();
  }
  try {
    await loadPathSettings();
  } catch (error) {
    alert('Could not load path settings.');
    pathsBaselineValue = normalizedPathValue(outputBasePathEl.value);
    updateSettingsActionButtons();
  }
  try {
    await loadCategories();
  } catch (error) {
    alert('Could not load archive structure.');
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
    alert('Could not save clients.');
  }
});

matchingAddRowEl.addEventListener('click', () => {
  matchingDraft.push(defaultReplacement());
  renderMatchingEditor();
  updateSettingsActionButtons();
});

matchingCancelEl.addEventListener('click', () => {
  let parsed = [];
  try {
    parsed = JSON.parse(matchingBaselineJson);
  } catch (error) {
    parsed = [];
  }

  matchingDraft = Array.isArray(parsed) ? parsed.map(sanitizeReplacement) : [];
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
    alert(error.message || 'Could not save matching settings.');
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
    alert(error.message || 'Could not save archive structure.');
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
    alert(error.message || 'Could not save path settings.');
  }
});

settingsResetJobsEl.addEventListener('click', async () => {
  const confirmed = window.confirm(
    'This will move all source.pdf files back to inbox and remove all job folders. Continue?'
  );
  if (!confirmed) {
    return;
  }

  try {
    await resetAllJobs();
  } catch (error) {
    alert('Could not reset jobs.');
  }
});

settingsModalEl.addEventListener('click', (event) => {
  if (event.target === settingsModalEl) {
    closeSettingsModal();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && !settingsModalEl.classList.contains('hidden')) {
    closeSettingsModal();
  }
});

window.addEventListener('beforeunload', (event) => {
  if (!hasAnyUnsavedSettingsChanges()) {
    return;
  }

  event.preventDefault();
  event.returnValue = 'You have unsaved changes.';
});

async function fetchState() {
  if (pollInFlight) {
    return;
  }

  pollInFlight = true;
  try {
    const response = await fetch('/api/get-state.php', { cache: 'no-store' });
    if (!response.ok) {
      throw new Error('Failed to fetch state');
    }

    const nextState = await response.json();
    if (!nextState || !Array.isArray(nextState.readyJobs) || !Array.isArray(nextState.clients)) {
      throw new Error('Invalid state response');
    }

    applyState({
      processingJobs: Array.isArray(nextState.processingJobs) ? nextState.processingJobs : [],
      readyJobs: nextState.readyJobs,
      failedJobs: Array.isArray(nextState.failedJobs) ? nextState.failedJobs : [],
      clients: nextState.clients,
      categories: Array.isArray(nextState.categories) ? nextState.categories : []
    });
  } catch (error) {
    setProcessingInfo([]);
    jobListEl.innerHTML = '';
    const li = document.createElement('li');
    li.className = 'job-message';
    li.textContent = 'Could not load state.';
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
pollLoop();
