const fileListEl = document.getElementById('file-list');
const viewerStackEl = document.getElementById('viewer-stack');
const viewerFrames = viewerStackEl.querySelectorAll('.pdf-viewer-frame');
const viewModeEl = document.getElementById('view-mode');
const ocrViewEl = document.getElementById('ocr-view');
const ocrTextEl = document.getElementById('ocr-text');
const clientsButtonEl = document.getElementById('clients-button');
const clientsModalEl = document.getElementById('clients-modal');
const clientsTextareaEl = document.getElementById('clients-textarea');
const clientsCancelEl = document.getElementById('clients-cancel');
const clientsSaveEl = document.getElementById('clients-save');
const clientsQueryEl = document.getElementById('clients-query');
const copyQueryButtonEl = document.getElementById('copy-query-button');

let files = [];
let currentIndex = -1;
let frameOrder = [viewerFrames[0], viewerFrames[1], viewerFrames[2]];
let currentViewMode = 'pdf';
let ocrRequestSeq = 0;

function getFileItems() {
  return fileListEl.querySelectorAll('li.file-item');
}

function setSelectedItem(selectedLi) {
  const items = getFileItems();
  items.forEach((item) => item.classList.remove('selected'));
  if (selectedLi) {
    selectedLi.classList.add('selected');
  }
}

function getPdfUrl(index) {
  return '/api/serve-pdf.php?file=' + encodeURIComponent(files[index]);
}

function setFramePdf(frameEl, index) {
  const fileIndexValue = index >= 0 && index < files.length ? String(index) : '';
  if (frameEl.dataset.fileIndex === fileIndexValue) {
    return;
  }

  frameEl.dataset.fileIndex = fileIndexValue;

  if (index < 0 || index >= files.length) {
    frameEl.src = 'about:blank';
    return;
  }

  frameEl.src = getPdfUrl(index);
}

function getCurrentFilename() {
  if (currentIndex < 0 || currentIndex >= files.length) {
    return '';
  }
  return files[currentIndex];
}

function renderFrames() {
  setFramePdf(frameOrder[0], currentIndex - 1);
  setFramePdf(frameOrder[1], currentIndex);
  setFramePdf(frameOrder[2], currentIndex + 1);
}

function applyFrameOrder() {
  updateFrameVisibility();
}

function updateFrameVisibility() {
  frameOrder.forEach((frame, orderIndex) => {
    const isActive = orderIndex === 1;
    frame.style.visibility = 'visible';
    frame.style.opacity = isActive ? '1' : '0';
    frame.style.pointerEvents = isActive ? 'auto' : 'none';
    frame.style.zIndex = isActive ? '2' : '1';
  });
}

function updateSelectedList() {
  const items = getFileItems();
  items.forEach((item, index) => {
    if (index === currentIndex) {
      setSelectedItem(item);
      item.scrollIntoView({ block: 'nearest' });
    }
  });
}

function selectIndex(index) {
  if (index < 0 || index >= files.length || index === currentIndex) {
    return;
  }

  if (index === currentIndex + 1) {
    frameOrder = [frameOrder[1], frameOrder[2], frameOrder[0]];
    applyFrameOrder();
    currentIndex = index;
    setFramePdf(frameOrder[2], currentIndex + 1);
  } else if (index === currentIndex - 1) {
    frameOrder = [frameOrder[2], frameOrder[0], frameOrder[1]];
    applyFrameOrder();
    currentIndex = index;
    setFramePdf(frameOrder[0], currentIndex - 1);
  } else {
    currentIndex = index;
    renderFrames();
  }

  updateSelectedList();
  updateCurrentView();
}

function showMessage(message) {
  fileListEl.innerHTML = '';
  const li = document.createElement('li');
  li.className = 'message';
  li.textContent = message;
  fileListEl.appendChild(li);
}

function renderFileList(files) {
  fileListEl.innerHTML = '';

  files.forEach((filename, index) => {
    const li = document.createElement('li');
    li.className = 'file-item';
    li.textContent = filename;
    li.title = filename;
    li.dataset.filename = filename;

    li.addEventListener('click', () => {
      selectIndex(index);
    });

    fileListEl.appendChild(li);
  });

  currentIndex = 0;
  applyFrameOrder();
  renderFrames();
  updateSelectedList();
}

function moveSelection(step) {
  if (files.length === 0) {
    return;
  }

  const nextIndex = Math.max(0, Math.min(files.length - 1, currentIndex + step));
  selectIndex(nextIndex);
}

function setOcrText(text) {
  ocrTextEl.textContent = text;
}

async function loadOcrForCurrentFile() {
  const requestSeq = ++ocrRequestSeq;
  const filename = getCurrentFilename();
  if (!filename) {
    setOcrText('No file selected.');
    return;
  }

  setOcrText('Loading OCR data...');

  try {
    const response = await fetch('/api/get-ocr.php?file=' + encodeURIComponent(filename));
    if (!response.ok) {
      throw new Error('Failed to load OCR');
    }

    const payload = await response.json();
    if (!payload || typeof payload.text !== 'string') {
      throw new Error('Invalid OCR response');
    }

    if (requestSeq !== ocrRequestSeq) {
      return;
    }

    setOcrText(payload.text || '(No OCR text found)');
  } catch (error) {
    if (requestSeq !== ocrRequestSeq) {
      return;
    }
    setOcrText('Could not load OCR data.');
  }
}

function updateCurrentView() {
  if (currentViewMode === 'ocr') {
    viewerStackEl.classList.add('hidden');
    ocrViewEl.classList.remove('hidden');
    loadOcrForCurrentFile();
    return;
  }

  ocrViewEl.classList.add('hidden');
  viewerStackEl.classList.remove('hidden');
}

function openClientsModal() {
  clientsModalEl.classList.remove('hidden');
}

function closeClientsModal() {
  clientsModalEl.classList.add('hidden');
}

function clientsToText(clients) {
  return clients.join('\n');
}

function textToClients(text) {
  return text
    .split('\n')
    .map((line) => line.trim())
    .filter((line) => line.length > 0);
}

async function loadClients() {
  const response = await fetch('/api/get-clients.php');
  if (!response.ok) {
    throw new Error('Failed to load clients');
  }

  const clients = await response.json();
  if (!Array.isArray(clients)) {
    throw new Error('Invalid clients data');
  }

  clientsTextareaEl.value = clientsToText(clients);
}

async function saveClients() {
  const clients = textToClients(clientsTextareaEl.value);
  const response = await fetch('/api/save-clients.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ clients })
  });

  if (!response.ok) {
    throw new Error('Failed to save clients');
  }

  closeClientsModal();
}

clientsButtonEl.addEventListener('click', async () => {
  try {
    await loadClients();
    openClientsModal();
    clientsTextareaEl.focus();
  } catch (error) {
    alert('Could not load clients.');
  }
});

clientsCancelEl.addEventListener('click', () => {
  closeClientsModal();
});

clientsSaveEl.addEventListener('click', async () => {
  try {
    await saveClients();
  } catch (error) {
    alert('Could not save clients.');
  }
});

clientsModalEl.addEventListener('click', (event) => {
  if (event.target === clientsModalEl) {
    closeClientsModal();
  }
});

copyQueryButtonEl.addEventListener('click', async () => {
  const query = clientsQueryEl.value;

  try {
    await navigator.clipboard.writeText(query);
  } catch (error) {
    clientsQueryEl.focus();
    clientsQueryEl.select();
    document.execCommand('copy');
  }
});

viewModeEl.addEventListener('change', () => {
  currentViewMode = viewModeEl.value === 'ocr' ? 'ocr' : 'pdf';
  updateCurrentView();
});

document.addEventListener('keydown', (event) => {
  const tag = event.target.tagName;
  if (tag === 'INPUT' || tag === 'TEXTAREA' || event.target.isContentEditable) {
    return;
  }

  if (event.key === 'ArrowDown') {
    event.preventDefault();
    moveSelection(1);
  } else if (event.key === 'ArrowUp') {
    event.preventDefault();
    moveSelection(-1);
  } else if (event.key === 'Escape' && !clientsModalEl.classList.contains('hidden')) {
    closeClientsModal();
  }
});

async function init() {
  try {
    const response = await fetch('/api/list-pdfs.php');
    if (!response.ok) {
      throw new Error('Failed to fetch PDF list');
    }

    const fetchedFiles = await response.json();

    if (!Array.isArray(fetchedFiles) || fetchedFiles.length === 0) {
      showMessage('No PDFs found.');
      frameOrder.forEach((frame) => {
        frame.src = 'about:blank';
      });
      return;
    }

    files = fetchedFiles;
    renderFileList(files);
    updateCurrentView();
  } catch (error) {
    showMessage('Could not load PDF list.');
    frameOrder.forEach((frame) => {
      frame.src = 'about:blank';
    });
  }
}

init();
