const DOCFLOW_LOGIN_URL = 'https://online.swedbank.se/app/ib/logga-in';
const DOCFLOW_SWEDBANK_APP_PATTERNS = [
  'https://online.swedbank.se/app*'
];
const DOCFLOW_SWEDBANK_FALLBACK_PATTERNS = [
  'https://www.swedbank.se/*'
];

const DOCFLOW_LOG_PREFIX = '[Docflow Chrome Connector]';

function logInfo(message, details) {
  if (details === undefined) {
    console.info(`${DOCFLOW_LOG_PREFIX} ${message}`);
    return;
  }
  console.info(`${DOCFLOW_LOG_PREFIX} ${message}`, details);
}

function logWarn(message, details) {
  if (details === undefined) {
    console.warn(`${DOCFLOW_LOG_PREFIX} ${message}`);
    return;
  }
  console.warn(`${DOCFLOW_LOG_PREFIX} ${message}`, details);
}

function logError(message, details) {
  if (details === undefined) {
    console.error(`${DOCFLOW_LOG_PREFIX} ${message}`);
    return;
  }
  console.error(`${DOCFLOW_LOG_PREFIX} ${message}`, details);
}

function normalizeTabUrl(tab) {
  return typeof tab?.url === 'string' ? tab.url : '';
}

function isSwedbankLoginUrl(url) {
  return typeof url === 'string' && url.includes('/logga-in');
}

function isSwedbankProfileSelectionUrl(url) {
  return typeof url === 'string' && url.includes('/logga-in/valj-profil');
}

function isUsableSwedbankAppUrl(url) {
  if (typeof url !== 'string' || url === '') {
    return false;
  }
  if (!url.startsWith('https://online.swedbank.se/app')) {
    return false;
  }
  if (isSwedbankProfileSelectionUrl(url)) {
    return true;
  }
  return !isSwedbankLoginUrl(url);
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
  const usableSessionTabs = candidateAppTabs.filter((tab) => isUsableSwedbankAppUrl(normalizeTabUrl(tab)));
  const loginTabs = candidateAppTabs.filter((tab) => !isUsableSwedbankAppUrl(normalizeTabUrl(tab)));
  const usableAppTab = usableSessionTabs[0] || null;
  if (usableAppTab) {
    logInfo('found usable Swedbank app tabs', {
      sessionTabIds: usableSessionTabs.map((tab) => tab.id).filter((id) => typeof id === 'number'),
      loginTabIds: loginTabs.map((tab) => tab.id).filter((id) => typeof id === 'number'),
      urls: usableSessionTabs.map((tab) => normalizeTabUrl(tab)),
    });
    return {
      tab: usableAppTab,
      sessionAvailable: true,
      sessionTabs: usableSessionTabs,
      loginTabs,
    };
  }

  const loginTab = loginTabs[0] || null;
  if (loginTab) {
    logWarn('only non-usable Swedbank login tabs found', {
      loginTabIds: loginTabs.map((tab) => tab.id).filter((id) => typeof id === 'number'),
      urls: loginTabs.map((tab) => normalizeTabUrl(tab)),
    });
    return {
      tab: loginTab,
      sessionAvailable: false,
      sessionTabs: [],
      loginTabs,
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
      const prefix = '[Docflow Chrome Connector]';
      const normalizedType = String(type || '').trim().toLowerCase() === 'plusgiro' ? 'PGACCOUNT' : 'BGACCOUNT';
      const normalizedNumber = String(number || '').trim();
      const url = `https://online.swedbank.se/TDE_DAP_Portal_REST_WEB/api/v5/payment/payee/${normalizedType}/${encodeURIComponent(normalizedNumber)}`;
      try {
        console.info(`${prefix} injected lookup start`, {
          type,
          normalizedType,
          number: normalizedNumber,
          url,
          href: window.location.href,
        });
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

        console.info(`${prefix} injected lookup response`, {
          ok: response.ok,
          status: response.status,
          responseUrl: response.url,
          hasJson: !!data,
        });

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

  const profileSelectionRequired =
    swedbankError.code === 'AUTHORIZATION_FAILED'
    && /no target selected/i.test(swedbankError.message);

  const loginRequired =
    swedbankError.code === 'STRONGER_AUTHENTICATION_NEEDED'
    || (swedbankError.code === 'AUTHORIZATION_FAILED' && !profileSelectionRequired)
    || responseUrl.includes('/logga-in')
    || /logga\s*in/i.test(rawText)
    || ((status === 401 || status === 403) && swedbankError.code === '');

  if (!result || result.ok !== true) {
    return {
      ok: false,
      errorCode: notFound
        ? 'PAYEE_NOT_FOUND'
        : (
          profileSelectionRequired
            ? 'PROFILE_SELECTION_REQUIRED'
            : (
          loginRequired
            ? 'NO_SESSION'
            : (swedbankError.code || 'LOOKUP_FAILED')
            )
        ),
      message: notFound
        ? (swedbankError.fieldMessage || 'Det finns ingen mottagare med det här numret.')
        : (
          profileSelectionRequired
            ? 'Swedbank kräver att du väljer profil för att hämta namn för betalnummer.'
            : (
          loginRequired
            ? 'Swedbank kräver att du loggar in igen för att hämta namn för betalnummer.'
            : (
              swedbankError.message
              || swedbankError.fieldMessage
              || result?.scriptError
              || `Swedbank lookup failed with status ${status || 0}.`
            )
            )
        ),
      status,
      loginRequired: notFound || profileSelectionRequired ? false : loginRequired,
      profileSelectionRequired: notFound ? false : profileSelectionRequired,
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
    loginRequired: false,
    profileSelectionRequired: false,
    raw: payload,
  };
}

async function handlePing() {
  const manifest = chrome.runtime.getManifest();
  const swedbank = await findSwedbankSessionTab();
  const payload = {
    ok: true,
    type: 'docflow.pong',
    version: String(manifest.version || ''),
    extensionId: chrome.runtime.id,
    swedbankSessionAvailable: swedbank.sessionAvailable === true,
    hasAnySwedbankTab: !!swedbank.tab,
  };
  logInfo('ping', payload);
  return payload;
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
  logInfo('lookup requested', {
    type,
    number,
    sessionAvailable: swedbank.sessionAvailable === true,
    hasAnySwedbankTab: !!swedbank.tab,
    sessionTabIds: sessionTabs.map((tab) => tab.id).filter((id) => typeof id === 'number'),
  });
  if (sessionTabs.length < 1) {
    const payload = {
      ok: false,
      errorCode: 'NO_SESSION',
      message: 'Ingen användbar Swedbank-session hittades.',
      loginRequired: true,
    };
    logWarn('lookup aborted: no usable Swedbank session', payload);
    return payload;
  }

  let firstNonSessionError = null;
  for (const tab of sessionTabs) {
    if (typeof tab?.id !== 'number' || tab.id < 1) {
      continue;
    }

    const result = await executeLookupInTab(tab.id, { type, number });
    const normalized = normalizeLookupResponse(type, number, result);
    logInfo('lookup tab result', {
      tabId: tab.id,
      ok: normalized.ok === true,
      status: normalized.status || 0,
      errorCode: normalized.errorCode || '',
      loginRequired: normalized.loginRequired === true,
      message: normalized.message || '',
      payeeName: normalized.payeeName || '',
    });
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
    logWarn('lookup finished with non-session error', firstNonSessionError);
    return firstNonSessionError;
  }

  const payload = {
    ok: false,
    errorCode: 'NO_SESSION',
    message: 'Ingen användbar Swedbank-session hittades.',
    loginRequired: true,
  };
  logWarn('lookup finished without usable session across candidate tabs', payload);
  return payload;
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
    logInfo('received external message', { type });
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
      logError('unexpected external message failure', error instanceof Error ? error.message : String(error || 'Unknown error'));
      sendResponse({
        ok: false,
        errorCode: 'UNEXPECTED_ERROR',
        message: error instanceof Error ? error.message : String(error || 'Unknown error'),
      });
    });

  return true;
});

(() => {
  const manifest = chrome.runtime.getManifest();
  logInfo('service worker loaded', {
    extensionId: chrome.runtime.id,
    version: String(manifest.version || ''),
  });
})();
