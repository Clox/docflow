const fileListEl = document.getElementById('file-list');
const viewerStackEl = document.getElementById('viewer-stack');
const viewerFrames = viewerStackEl.querySelectorAll('.pdf-viewer-frame');

let files = [];
let currentIndex = -1;
let frameOrder = [viewerFrames[0], viewerFrames[1], viewerFrames[2]];

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
  } catch (error) {
    showMessage('Could not load PDF list.');
    frameOrder.forEach((frame) => {
      frame.src = 'about:blank';
    });
  }
}

init();
