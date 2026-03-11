const jobListEl = document.getElementById('job-list');
const viewerEl = document.getElementById('pdf-viewer');
const ocrViewEl = document.getElementById('ocr-view');
const viewModeEl = document.getElementById('view-mode');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const clientSelectEl = document.getElementById('client-select');
const settingsButtonEl = document.getElementById('settings-button');
const settingsModalEl = document.getElementById('settings-modal');
const settingsTabEls = Array.from(document.querySelectorAll('[data-settings-tab]'));
const clientsTextareaEl = document.getElementById('clients-textarea');
const clientsCancelEl = document.getElementById('clients-cancel');
const clientsApplyEl = document.getElementById('clients-apply');
const categoriesListEl = document.getElementById('categories-list');
const categoriesAddCategoryEl = document.getElementById('categories-add-category');
const categoriesCancelEl = document.getElementById('categories-cancel');
const categoriesApplyEl = document.getElementById('categories-apply');
const settingsResetJobsEl = document.getElementById('settings-reset-jobs');
const settingsCloseEl = document.getElementById('settings-close');
const outputBasePathEl = document.getElementById('output-base-path');
const pathsCancelEl = document.getElementById('paths-cancel');
const pathsApplyEl = document.getElementById('paths-apply');

let state = {
  processingJobs: [],
  readyJobs: [],
  failedJobs: [],
  clients: []
};

let selectedJobId = '';
let loadedJobId = '';
let loadedOcrJobId = '';
let pollTimer = null;
let pollInFlight = false;
let currentViewMode = 'pdf';
let ocrRequestSeq = 0;
let categoriesDraft = [];
const selectedClientByJobId = new Map();

clientSelectEl.disabled = true;

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

function setViewerJob(jobId) {
  if (currentViewMode === 'ocr') {
    setViewerOcr(jobId);
  } else {
    setViewerPdf(jobId);
  }
}

function setViewerPdf(jobId) {
  ocrViewEl.classList.add('hidden');
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
    return;
  }

  const stillExists = readyJobs.some((job) => job.id === selectedJobId);
  if (!stillExists) {
    selectedJobId = readyJobs[0].id;
  }

  const selectedJob = readyJobs.find((job) => job.id === selectedJobId) || null;
  setViewerJob(selectedJobId);
  setClientForJob(selectedJob);
}

function applyState(nextState) {
  state = nextState;
  const validJobIds = new Set(state.readyJobs.map((job) => job.id));
  Array.from(selectedClientByJobId.keys()).forEach((jobId) => {
    if (!validJobIds.has(jobId)) {
      selectedClientByJobId.delete(jobId);
    }
  });

  setProcessingInfo(state.processingJobs);
  renderClientSelect(state.clients);
  refreshSelection();
  renderJobList(state.readyJobs);
}

function openSettingsModal() {
  settingsModalEl.classList.remove('hidden');
}

function closeSettingsModal() {
  settingsModalEl.classList.add('hidden');
}

function setSettingsTab(tabId) {
  settingsTabEls.forEach((tabButton) => {
    const isActive = tabButton.dataset.settingsTab === tabId;
    tabButton.classList.toggle('active', isActive);
  });

  const panelIds = ['clients', 'categories', 'jobs', 'paths'];
  panelIds.forEach((id) => {
    const panel = document.getElementById('settings-panel-' + id);
    if (!panel) {
      return;
    }
    panel.classList.toggle('hidden', id !== tabId);
    panel.classList.toggle('active', id === tabId);
  });
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
    path: '',
    minScore: 1,
    rules: [defaultRule()]
  };
}

function sanitizePositiveInt(value, fallback = 1) {
  const parsed = parseInt(String(value), 10);
  if (!Number.isFinite(parsed)) {
    return fallback;
  }
  return parsed < 1 ? 1 : parsed;
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
    path: typeof input.path === 'string' ? input.path : '',
    minScore: sanitizePositiveInt(input.minScore, 1),
    rules: rules.length > 0 ? rules : [defaultRule()]
  };
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

function renderCategoriesEditor() {
  categoriesListEl.innerHTML = '';

  if (categoriesDraft.length === 0) {
    const empty = document.createElement('div');
    empty.className = 'categories-empty';
    empty.textContent = 'No categories yet.';
    categoriesListEl.appendChild(empty);
    return;
  }

  categoriesDraft.forEach((category, categoryIndex) => {
    const card = document.createElement('div');
    card.className = 'category-card';

    const header = document.createElement('div');
    header.className = 'category-header';
    const title = document.createElement('span');
    title.textContent = `Category ${categoryIndex + 1}`;
    const removeCategoryButton = document.createElement('button');
    removeCategoryButton.type = 'button';
    removeCategoryButton.className = 'category-remove';
    removeCategoryButton.textContent = 'Remove';
    removeCategoryButton.addEventListener('click', () => {
      categoriesDraft.splice(categoryIndex, 1);
      renderCategoriesEditor();
    });
    header.appendChild(title);
    header.appendChild(removeCategoryButton);
    card.appendChild(header);

    const fields = document.createElement('div');
    fields.className = 'category-fields';

    const nameInput = document.createElement('input');
    nameInput.type = 'text';
    nameInput.placeholder = 'Ex: "Fakturor"';
    nameInput.value = category.name;
    nameInput.addEventListener('input', () => {
      categoriesDraft[categoryIndex].name = nameInput.value;
    });

    const pathInput = document.createElement('input');
    pathInput.type = 'text';
    pathInput.placeholder = 'Ex: "dokument/fakturor"';
    pathInput.value = category.path;
    pathInput.addEventListener('input', () => {
      categoriesDraft[categoryIndex].path = pathInput.value;
    });

    const minScoreInput = document.createElement('input');
    minScoreInput.type = 'number';
    minScoreInput.step = '1';
    minScoreInput.min = '1';
    minScoreInput.placeholder = 'Min score';
    minScoreInput.value = String(category.minScore);
    minScoreInput.addEventListener('input', () => {
      categoriesDraft[categoryIndex].minScore = sanitizePositiveInt(minScoreInput.value, 1);
    });

    fields.appendChild(createFloatingField('Name', nameInput));
    fields.appendChild(createFloatingField('Path', pathInput));
    fields.appendChild(createFloatingField('Min score', minScoreInput, 'score-field'));
    card.appendChild(fields);

    const ruleList = document.createElement('div');
    ruleList.className = 'rule-list';

    category.rules.forEach((rule, ruleIndex) => {
      const ruleRow = document.createElement('div');
      ruleRow.className = 'rule-row';

      const textInput = document.createElement('input');
      textInput.type = 'text';
      textInput.placeholder = 'Ex: "Förfallodatum"';
      textInput.value = rule.text;
      textInput.addEventListener('input', () => {
        categoriesDraft[categoryIndex].rules[ruleIndex].text = textInput.value;
      });

      const scoreInput = document.createElement('input');
      scoreInput.type = 'number';
      scoreInput.step = '1';
      scoreInput.min = '1';
      scoreInput.value = String(rule.score);
      scoreInput.addEventListener('input', () => {
        categoriesDraft[categoryIndex].rules[ruleIndex].score = sanitizePositiveInt(scoreInput.value, 1);
      });

      ruleRow.appendChild(createFloatingField('Match text', textInput));
      ruleRow.appendChild(createFloatingField('Score', scoreInput, 'score-field'));

      if (ruleIndex > 0) {
        const removeRuleButton = document.createElement('button');
        removeRuleButton.type = 'button';
        removeRuleButton.className = 'rule-remove';
        removeRuleButton.textContent = 'Remove';
        removeRuleButton.addEventListener('click', () => {
          categoriesDraft[categoryIndex].rules.splice(ruleIndex, 1);
          if (categoriesDraft[categoryIndex].rules.length === 0) {
            categoriesDraft[categoryIndex].rules.push(defaultRule());
          }
          renderCategoriesEditor();
        });
        ruleRow.appendChild(removeRuleButton);
      } else {
        const placeholder = document.createElement('div');
        placeholder.className = 'rule-remove-placeholder';
        ruleRow.appendChild(placeholder);
      }

      ruleList.appendChild(ruleRow);
    });

    card.appendChild(ruleList);

    const ruleActions = document.createElement('div');
    ruleActions.className = 'category-rule-actions';
    const addRuleButton = document.createElement('button');
    addRuleButton.type = 'button';
    addRuleButton.textContent = 'Add rule';
    addRuleButton.addEventListener('click', () => {
      categoriesDraft[categoryIndex].rules.push(defaultRule());
      renderCategoriesEditor();
    });
    ruleActions.appendChild(addRuleButton);
    card.appendChild(ruleActions);

    categoriesListEl.appendChild(card);
  });
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
}

async function loadCategories() {
  const response = await fetch('/api/get-categories.php', { cache: 'no-store' });
  if (!response.ok) {
    throw new Error('Failed to load categories');
  }

  const payload = await response.json();
  if (!payload || !Array.isArray(payload.categories)) {
    throw new Error('Invalid categories response');
  }

  categoriesDraft = payload.categories.map(sanitizeCategory);
  renderCategoriesEditor();
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

  closeSettingsModal();
  await fetchState();
}

async function saveCategories() {
  const normalized = categoriesDraft.map(sanitizeCategory);
  const response = await fetch('/api/save-categories.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ categories: normalized })
  });

  const payload = await response.json().catch(() => null);
  if (!response.ok || !payload || payload.ok !== true) {
    const message = payload && typeof payload.error === 'string'
      ? payload.error
      : 'Failed to save categories';
    throw new Error(message);
  }

  categoriesDraft = normalized;
  renderCategoriesEditor();
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
  selectedJobId = '';
  closeSettingsModal();
  await fetchState();
}

viewModeEl.addEventListener('change', () => {
  currentViewMode = viewModeEl.value === 'ocr' ? 'ocr' : 'pdf';
  loadedOcrJobId = '';
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

settingsButtonEl.addEventListener('click', async () => {
  openSettingsModal();
  setSettingsTab('clients');
  try {
    await loadClientsText();
  } catch (error) {
    alert('Could not load clients.');
  }
  try {
    await loadPathSettings();
  } catch (error) {
    alert('Could not load path settings.');
  }
  try {
    await loadCategories();
  } catch (error) {
    alert('Could not load categories.');
    categoriesDraft = [];
    renderCategoriesEditor();
  }
  clientsTextareaEl.focus();
});

clientsCancelEl.addEventListener('click', () => {
  closeSettingsModal();
});

clientsApplyEl.addEventListener('click', async () => {
  try {
    await saveClientsText();
  } catch (error) {
    alert('Could not save clients.');
  }
});

categoriesAddCategoryEl.addEventListener('click', () => {
  categoriesDraft.push(defaultCategory());
  renderCategoriesEditor();
});

categoriesCancelEl.addEventListener('click', async () => {
  try {
    await loadCategories();
  } catch (error) {
    alert('Could not reload categories.');
  }
});

categoriesApplyEl.addEventListener('click', async () => {
  try {
    await saveCategories();
  } catch (error) {
    alert(error.message || 'Could not save categories.');
  }
});

settingsTabEls.forEach((tabButton) => {
  tabButton.addEventListener('click', () => {
    const tabId = tabButton.dataset.settingsTab;
    if (!tabId) {
      return;
    }
    setSettingsTab(tabId);
  });
});

settingsCloseEl.addEventListener('click', () => {
  closeSettingsModal();
});

pathsCancelEl.addEventListener('click', async () => {
  try {
    await loadPathSettings();
  } catch (error) {
    alert('Could not reload path settings.');
  }
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
      clients: nextState.clients
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

pollLoop();
