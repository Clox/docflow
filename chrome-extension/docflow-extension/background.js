const DOCFLOW_LOGIN_URL = 'https://online.swedbank.se/app/ib/logga-in';
const DOCFLOW_SWEDBANK_APP_PATTERNS = [
  'https://online.swedbank.se/app*'
];
const DOCFLOW_SWEDBANK_FALLBACK_PATTERNS = [
  'https://www.swedbank.se/*'
];

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
  const validAppTab = appTabs.find((tab) => typeof tab.id === 'number' && tab.id > 0);
  if (validAppTab) {
    return { tab: validAppTab, sessionAvailable: true };
  }

  const fallbackTabs = await queryTabs(DOCFLOW_SWEDBANK_FALLBACK_PATTERNS);
  const fallbackTab = fallbackTabs.find((tab) => typeof tab.id === 'number' && tab.id > 0) || null;
  return { tab: fallbackTab, sessionAvailable: false };
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
            'x-client': ''
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

  const loginRequired =
    status === 401
    || status === 403
    || responseUrl.includes('/logga-in')
    || /logga\s*in/i.test(rawText);

  if (!result || result.ok !== true) {
    return {
      ok: false,
      errorCode: loginRequired ? 'NO_SESSION' : 'LOOKUP_FAILED',
      message: loginRequired
        ? 'Ingen användbar Swedbank-session hittades.'
        : (result?.scriptError || `Swedbank lookup failed with status ${status || 0}.`),
      status,
      loginRequired,
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
  if (!swedbank.tab || swedbank.sessionAvailable !== true || typeof swedbank.tab.id !== 'number') {
    return {
      ok: false,
      errorCode: 'NO_SESSION',
      message: 'Ingen användbar Swedbank-session hittades.',
      loginRequired: true,
    };
  }

  const result = await executeLookupInTab(swedbank.tab.id, { type, number });
  return normalizeLookupResponse(type, number, result);
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
