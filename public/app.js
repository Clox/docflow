const fileListEl = document.getElementById('file-list');
const viewerEl = document.getElementById('pdf-viewer');

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

function loadPdf(filename) {
  const url = '/api/serve-pdf.php?file=' + encodeURIComponent(filename);
  viewerEl.src = url;
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
      setSelectedItem(li);
      loadPdf(filename);
    });

    fileListEl.appendChild(li);

    if (index === 0) {
      setSelectedItem(li);
      loadPdf(filename);
    }
  });
}

function moveSelection(step) {
  const items = getFileItems();
  if (items.length === 0) {
    return;
  }

  let currentIndex = Array.from(items).findIndex((item) =>
    item.classList.contains('selected')
  );

  if (currentIndex === -1) {
    currentIndex = 0;
  }

  const nextIndex = Math.max(0, Math.min(items.length - 1, currentIndex + step));
  const nextItem = items[nextIndex];
  if (!nextItem) {
    return;
  }

  setSelectedItem(nextItem);
  loadPdf(nextItem.dataset.filename);
  nextItem.scrollIntoView({ block: 'nearest' });
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

    const files = await response.json();

    if (!Array.isArray(files) || files.length === 0) {
      showMessage('No PDFs found.');
      viewerEl.removeAttribute('src');
      return;
    }

    renderFileList(files);
  } catch (error) {
    showMessage('Could not load PDF list.');
    viewerEl.removeAttribute('src');
  }
}

init();
