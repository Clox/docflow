const jobListEl = document.getElementById('job-list');
const viewerEl = document.getElementById('pdf-viewer');
const ocrViewEl = document.getElementById('ocr-view');
const viewModeEl = document.getElementById('view-mode');
const processingIndicatorEl = document.getElementById('processing-indicator');
const processingTextEl = document.getElementById('processing-text');
const clientSelectEl = document.getElementById('client-select');
const settingsButtonEl = document.getElementById('settings-button');
const settingsModalEl = document.getElementById('settings-modal');
const clientsTextareaEl = document.getElementById('clients-textarea');
const settingsCancelEl = document.getElementById('settings-cancel');
const settingsSaveEl = document.getElementById('settings-save');
const settingsResetJobsEl = document.getElementById('settings-reset-jobs');

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
    option.textContent = client.name;
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

  if (!job || !job.matchedClientDirName) {
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

settingsButtonEl.addEventListener('click', async () => {
  try {
    await loadClientsText();
    openSettingsModal();
    clientsTextareaEl.focus();
  } catch (error) {
    alert('Could not load clients.');
  }
});

settingsCancelEl.addEventListener('click', () => {
  closeSettingsModal();
});

settingsSaveEl.addEventListener('click', async () => {
  try {
    await saveClientsText();
  } catch (error) {
    alert('Could not save clients.');
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
