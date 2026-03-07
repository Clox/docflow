Simple local PHP PDF browser/viewer.

What it does:
- Lists PDF files from one hardcoded directory.
- Lets you click a file to view it in an iframe.

Requirements:
- PHP installed.

Run:
chmod +x start.sh
./start.sh

Then open:
http://127.0.0.1:4321

Notes:
- The PDF source directory is hardcoded in:
  - public/api/list-pdfs.php
  - public/api/serve-pdf.php
- Change the PDF_DIR constant in both files to your folder.
