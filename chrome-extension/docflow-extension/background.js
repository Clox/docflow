const DOCFLOW_LOGIN_URL = 'https://online.swedbank.se/app/ib/logga-in';
const DOCFLOW_SWEDBANK_APP_PATTERNS = [
  'https://online.swedbank.se/app*'
];
const DOCFLOW_SWEDBANK_FALLBACK_PATTERNS = [
  'https://www.swedbank.se/*'
];

function normalizeTabUrl(tab) {
  return typeof tab?.url === 'string' ? tab.url : '';
}

function isSwedbankLoginUrl(url) {
  return typeof url === 'string' && url.includes('/logga-in');
}

function firstNonEmptyString(values) {
  for (const value of values) {
    if (typeof value !== 'string') {
      continue;
    }
    const trimmed = value.trim();
    if (trimmed !== '') {
      return trimmed;
    }
  }
  return '';
}

function extractSwedbankError(payload) {
  const generalErrors = Array.isArray(payload?.errorMessages?.general)
    ? payload.errorMessages.general
    : [];
  const fieldErrors = Array.isArray(payload?.errorMessages?.fields)
    ? payload.errorMessages.fields
    : [];
  const firstError = generalErrors.find((item) => item && typeof item === 'object') || null;
  const firstFieldError = fieldErrors.find((item) => item && typeof item === 'object') || null;
  return {
    code: firstNonEmptyString([firstError?.code]),
    message: firstNonEmptyString([firstError?.message]),
    field: firstNonEmptyString([firstFieldError?.field]),
    fieldMessage: firstNonEmptyString([firstFieldError?.message]),
  };
}

async function queryTabs(urlPatterns) {
  try {
    const tabs = await chrome.tabs.query({ url: urlPatterns });
    return Array.isArray(tabs) ? tabs : [];
  } catch (_error) {
    return [];
  }
}

async function findSwedbankSessionTab() {
  const appTabs = await queryTabs(DOCFLOW_SWEDBANK_APP_PATTERNS);
  const candidateAppTabs = appTabs.filter((tab) => typeof tab.id === 'number' && tab.id > 0);
  const usableAppTab = candidateAppTabs.find((tab) => !isSwedbankLoginUrl(normalizeTabUrl(tab))) || null;
  if (usableAppTab) {
    return {
      tab: usableAppTab,
      sessionAvailable: true,
      sessionTabs: candidateAppTabs.filter((tab) => !isSwedbankLoginUrl(normalizeTabUrl(tab))),
      loginTabs: candidateAppTabs.filter((tab) => isSwedbankLoginUrl(normalizeTabUrl(tab))),
    };
  }

  const loginTab = candidateAppTabs.find((tab) => isSwedbankLoginUrl(normalizeTabUrl(tab))) || null;
  if (loginTab) {
    return {
      tab: loginTab,
      sessionAvailable: false,
      sessionTabs: [],
      loginTabs: [loginTab],
    };
  }

  const fallbackTabs = await queryTabs(DOCFLOW_SWEDBANK_FALLBACK_PATTERNS);
  const fallbackTab = fallbackTabs.find((tab) => typeof tab.id === 'number' && tab.id > 0) || null;
  return { tab: fallbackTab, sessionAvailable: false, sessionTabs: [], loginTabs: [] };
}

async function executeLookupInTab(tabId, payload) {
  const results = await chrome.scripting.executeScript({
    target: { tabId },
    world: 'MAIN',
    func: async ({ type, number }) => {
      const normalizedType = String(type || '').trim().toLowerCase() === 'plusgiro' ? 'PGACCOUNT' : 'BGACCOUNT';
      const normalizedNumber = String(number || '').trim();
      const url = `https://online.swedbank.se/TDE_DAP_Portal_REST_WEB/api/v5/payment/payee/${normalizedType}/${encodeURIComponent(normalizedNumber)}`;
      try {
        const response = await fetch(url, {
          method: 'GET',
          credentials: 'include',
          headers: {
            'x-client': 'fdp-internet-bank/232.1.0'
          }
        });
        const rawText = await response.text();
        let data = null;
        try {
          data = JSON.parse(rawText);
        } catch (_error) {
          data = null;
        }

        return {
          ok: response.ok,
          status: response.status,
          responseUrl: response.url,
          rawText,
          data,
        };
      } catch (error) {
        return {
          ok: false,
          status: 0,
          responseUrl: url,
          rawText: '',
          data: null,
          scriptError: error instanceof Error ? error.message : String(error || 'Unknown error'),
        };
      }
    },
    args: [payload],
  });

  if (!Array.isArray(results) || results.length === 0) {
    throw new Error('No injected lookup result returned.');
  }

  return results[0] && typeof results[0] === 'object' ? results[0].result : null;
}

function normalizeLookupResponse(type, number, result) {
  const payload = result && typeof result.data === 'object' && result.data ? result.data : {};
  const responseUrl = typeof result?.responseUrl === 'string' ? result.responseUrl : '';
  const rawText = typeof result?.rawText === 'string' ? result.rawText : '';
  const status = Number.isInteger(result?.status) ? result.status : 0;
  const swedbankError = extractSwedbankError(payload);
  const notFound =
    status === 400
    && swedbankError.field === 'accountNumber'
    && /ingen mottagare/i.test(swedbankError.fieldMessage);

  const loginRequired =
    swedbankError.code === 'STRONGER_AUTHENTICATION_NEEDED'
    || responseUrl.includes('/logga-in')
    || /logga\s*in/i.test(rawText)
    || ((status === 401 || status === 403) && swedbankError.code === '');

  if (!result || result.ok !== true) {
    return {
      ok: false,
      errorCode: notFound
        ? 'PAYEE_NOT_FOUND'
        : (
          loginRequired
            ? 'NO_SESSION'
            : (swedbankError.code || 'LOOKUP_FAILED')
        ),
      message: notFound
        ? (swedbankError.fieldMessage || 'Det finns ingen mottagare med det här numret.')
        : (
          loginRequired
            ? 'Swedbank kräver att du loggar in igen för att hämta namn för betalnummer.'
            : (
              swedbankError.message
              || swedbankError.fieldMessage
              || result?.scriptError
              || `Swedbank lookup failed with status ${status || 0}.`
            )
        ),
      status,
      loginRequired: notFound ? false : loginRequired,
      notFound,
    };
  }

  const payeeName = firstNonEmptyString([
    payload.payeeName,
    payload.name,
    payload.accountName,
    payload.beneficiaryName,
    payload.payee?.name,
    payload.payee?.payeeName,
  ]);
  const accountNumber = firstNonEmptyString([
    payload.accountNumber,
    payload.account,
    payload.payee?.accountNumber,
    payload.payee?.number,
    String(number || ''),
  ]);
  const referenceOCR = firstNonEmptyString([
    payload.referenceOCR,
    payload.referenceOcr,
    payload.reference?.ocr,
    payload.reference,
    payload.ocr,
  ]);

  return {
    ok: true,
    type,
    payeeName,
    accountNumber,
    referenceOCR,
    raw: payload,
  };
}

async function handlePing() {
  const manifest = chrome.runtime.getManifest();
  const swedbank = await findSwedbankSessionTab();
  return {
    ok: true,
    type: 'docflow.pong',
    version: String(manifest.version || ''),
    extensionId: chrome.runtime.id,
    swedbankSessionAvailable: swedbank.sessionAvailable === true,
    hasAnySwedbankTab: !!swedbank.tab,
  };
}

async function handleLookupPayee(message) {
  const type = String(message?.lookupType || message?.type || '').trim().toLowerCase() === 'plusgiro' ? 'plusgiro' : 'bankgiro';
  const number = String(message?.number || '').trim();
  if (number === '') {
    return {
      ok: false,
      errorCode: 'INVALID_NUMBER',
      message: 'Betalnummer saknas.',
    };
  }

  const swedbank = await findSwedbankSessionTab();
  const sessionTabs = Array.isArray(swedbank.sessionTabs) ? swedbank.sessionTabs : [];
  if (sessionTabs.length < 1) {
    return {
      ok: false,
      errorCode: 'NO_SESSION',
      message: 'Ingen användbar Swedbank-session hittades.',
      loginRequired: true,
    };
  }

  let firstNonSessionError = null;
  for (const tab of sessionTabs) {
    if (typeof tab?.id !== 'number' || tab.id < 1) {
      continue;
    }

    const result = await executeLookupInTab(tab.id, { type, number });
    const normalized = normalizeLookupResponse(type, number, result);
    if (normalized.ok === true) {
      return normalized;
    }
    if (normalized.loginRequired === true) {
      continue;
    }
    if (firstNonSessionError === null) {
      firstNonSessionError = normalized;
    }
  }

  if (firstNonSessionError) {
    return firstNonSessionError;
  }

  return {
    ok: false,
    errorCode: 'NO_SESSION',
    message: 'Ingen användbar Swedbank-session hittades.',
    loginRequired: true,
  };
}

async function handleOpenLogin() {
  const swedbank = await findSwedbankSessionTab();
  if (swedbank.tab && typeof swedbank.tab.id === 'number') {
    if (typeof swedbank.tab.windowId === 'number') {
      await chrome.windows.update(swedbank.tab.windowId, { focused: true });
    }
    await chrome.tabs.update(swedbank.tab.id, { active: true });
    return { ok: true, action: 'focus', tabId: swedbank.tab.id };
  }

  const createdTab = await chrome.tabs.create({ url: DOCFLOW_LOGIN_URL, active: true });
  return { ok: true, action: 'open', tabId: createdTab?.id || null };
}

chrome.runtime.onMessageExternal.addListener((message, _sender, sendResponse) => {
  (async () => {
    const type = String(message?.type || '').trim();
    if (type === 'docflow.ping') {
      return handlePing();
    }
    if (type === 'docflow.lookupPayee') {
      return handleLookupPayee(message);
    }
    if (type === 'docflow.openSwedbankLogin') {
      return handleOpenLogin();
    }
    return {
      ok: false,
      errorCode: 'UNKNOWN_MESSAGE',
      message: `Unsupported message type: ${type || '(empty)'}`,
    };
  })()
    .then((payload) => sendResponse(payload))
    .catch((error) => {
      sendResponse({
        ok: false,
        errorCode: 'UNEXPECTED_ERROR',
        message: error instanceof Error ? error.message : String(error || 'Unknown error'),
      });
    });

  return true;
});
