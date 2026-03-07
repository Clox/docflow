const fileListEl = document.getElementById('file-list');
const viewerEl = document.getElementById('pdf-viewer');

function setSelectedItem(selectedLi) {
  const items = fileListEl.querySelectorAll('li');
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
    li.textContent = filename;
    li.title = filename;

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
