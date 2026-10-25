/**
 * Campusly Fetch API Application Engine (script.js)
 * System: Campusly (CHARUSAT CSPIT Academic Portal)
 * 
 * Features:
 * - 100% Asynchronous CRUD operations using native browser Fetch API
 * - GET: Fetch student records with department and status filtering
 * - POST: Asynchronously insert new student record with input validation
 * - PUT: Asynchronously update existing student record
 * - DELETE: Asynchronously delete student record with modal confirmation
 * - Intermediate Extension: Live AJAX search with 300ms debounce
 * - Advanced Extension: Response status code inspection (200, 201, 400, 409) & centralized error handling
 * - Built-in DevTools Network Inspector logging request headers, payloads, status codes, and JSON responses
 */

// Application State
const state = {
  students: [],
  networkLogs: [],
  selectedLogIndex: 0,
  activeDetailTab: 'headers',
  searchQuery: '',
  selectedCourse: 'all',
  selectedStatus: 'all',
  debounceTimer: null,
  deleteTarget: null
};

// DOM Element References
const elements = {
  tbody: document.getElementById('students-tbody'),
  loadingState: document.getElementById('loading-state'),
  emptyState: document.getElementById('empty-state'),
  tableWrapper: document.getElementById('table-wrapper'),
  searchInput: document.getElementById('search-input'),
  courseFilter: document.getElementById('course-filter'),
  statusFilter: document.getElementById('status-filter'),
  btnRefresh: document.getElementById('btn-refresh'),
  btnOpenAddModal: document.getElementById('btn-open-add-modal'),
  btnToggleDevtools: document.getElementById('btn-toggle-devtools'),
  feedbackBanner: document.getElementById('feedback-banner'),
  feedbackText: document.getElementById('feedback-text'),
  statTotal: document.getElementById('stat-total-students'),
  statActive: document.getElementById('stat-active-students'),
  statLatency: document.getElementById('stat-latency'),
  counterBadge: document.getElementById('records-counter-badge'),
  // Add/Edit Modal
  modal: document.getElementById('student-modal'),
  modalHeading: document.getElementById('modal-heading'),
  modalForm: document.getElementById('student-form'),
  modalAlert: document.getElementById('modal-alert'),
  modalAlertMsg: document.getElementById('modal-alert-msg'),
  btnCloseModal: document.getElementById('btn-close-modal'),
  btnCancelModal: document.getElementById('btn-cancel-modal'),
  btnSaveStudent: document.getElementById('btn-save-student'),
  btnSaveText: document.getElementById('btn-save-text'),
  inputStudentId: document.getElementById('student-id'),
  inputName: document.getElementById('form-name'),
  inputEmail: document.getElementById('form-email'),
  inputCourse: document.getElementById('form-course'),
  inputStatus: document.getElementById('form-status'),
  errName: document.getElementById('err-name'),
  errEmail: document.getElementById('err-email'),
  // Delete Modal
  deleteModal: document.getElementById('delete-modal'),
  deleteStudentName: document.getElementById('delete-student-name'),
  deleteStudentId: document.getElementById('delete-student-id'),
  btnCloseDeleteModal: document.getElementById('btn-close-delete-modal'),
  btnCancelDelete: document.getElementById('btn-cancel-delete'),
  btnConfirmDelete: document.getElementById('btn-confirm-delete'),
  // DevTools Inspector
  devtoolsSection: document.getElementById('devtools-section'),
  dtRequestsTbody: document.getElementById('dt-requests-tbody'),
  dtDetailBody: document.getElementById('dt-detail-body'),
  dtReqCount: document.getElementById('dt-req-count')
};

// =============================================================================
// CENTRALIZED FETCH WRAPPER WITH DEVTOOLS NETWORK LOGGER
// =============================================================================
async function apiFetch(url, options = {}) {
  const method = (options.method || 'GET').toUpperCase();
  const startTime = performance.now();

  // Prepare standard JSON headers
  const defaultHeaders = {
    'Accept': 'application/json'
  };
  if (options.body && typeof options.body === 'string') {
    defaultHeaders['Content-Type'] = 'application/json';
  }
  options.headers = { ...defaultHeaders, ...(options.headers || {}) };

  let response;
  let responseData = null;
  let errorObj = null;

  try {
    response = await window.fetch(url, options);
    const duration = Math.round(performance.now() - startTime);

    // Try parsing JSON response
    try {
      responseData = await response.json();
    } catch (parseErr) {
      responseData = { status: 'error', message: 'Failed to parse JSON response body.' };
    }

    // Log to DevTools Network Inspector
    logNetworkRequest({
      url,
      method,
      status: response.status,
      statusText: response.statusText || getStatusText(response.status),
      duration,
      requestHeaders: options.headers,
      requestPayload: options.body ? JSON.parse(options.body) : null,
      responseHeaders: {
        'content-type': response.headers.get('content-type') || 'application/json; charset=UTF-8',
        'cache-control': response.headers.get('cache-control') || 'no-store, no-cache',
        'server': 'PHP/8.5 Built-in Server'
      },
      responseBody: responseData
    });

    // Update latency stat
    if (elements.statLatency) {
      elements.statLatency.textContent = `${duration} ms`;
    }

    return { ok: response.ok, status: response.status, data: responseData };

  } catch (netErr) {
    const duration = Math.round(performance.now() - startTime);
    logNetworkRequest({
      url,
      method,
      status: 0,
      statusText: 'Failed (Network Error)',
      duration,
      requestHeaders: options.headers,
      requestPayload: options.body ? JSON.parse(options.body) : null,
      responseHeaders: {},
      responseBody: { status: 'error', message: netErr.message }
    });
    throw netErr;
  }
}

function getStatusText(status) {
  switch (status) {
    case 200: return 'OK';
    case 201: return 'Created';
    case 400: return 'Bad Request';
    case 404: return 'Not Found';
    case 409: return 'Conflict';
    case 405: return 'Method Not Allowed';
    case 500: return 'Internal Server Error';
    default: return '';
  }
}

// =============================================================================
// CRUD OPERATIONS (FETCH API)
// =============================================================================

/**
 * 1. GET: Fetch students list from api.php
 */
async function loadStudents() {
  showLoading(true);
  try {
    const params = new URLSearchParams();
    if (state.searchQuery.trim() !== '') params.append('search', state.searchQuery.trim());
    if (state.selectedCourse !== 'all') params.append('course', state.selectedCourse);
    if (state.selectedStatus !== 'all') params.append('status', state.selectedStatus);

    const queryString = params.toString() ? `?${params.toString()}` : '';
    const endpoint = `api.php${queryString}`;

    const res = await apiFetch(endpoint, { method: 'GET' });

    if (res.ok && res.data && res.data.status === 'success') {
      state.students = res.data.data.students || [];
      renderStudentsTable(state.students);
      updateStatistics();
    } else {
      showBanner(res.data.message || 'Failed to retrieve student records from API.', 'error');
    }
  } catch (err) {
    showBanner(`Network Error: ${err.message}`, 'error');
  } finally {
    showLoading(false);
  }
}

/**
 * Render students into table rows without full page reload
 */
function renderStudentsTable(students) {
  elements.tbody.innerHTML = '';

  if (!students || students.length === 0) {
    elements.tableWrapper.style.display = 'none';
    elements.emptyState.style.display = 'block';
    elements.counterBadge.textContent = '0 Records Found';
    return;
  }

  elements.tableWrapper.style.display = 'block';
  elements.emptyState.style.display = 'none';
  elements.counterBadge.textContent = `${students.length} Records Loaded`;

  students.forEach(student => {
    const tr = document.createElement('tr');
    tr.id = `student-row-${student.student_id}`;

    // Initials for avatar
    const initials = student.name
      .split(' ')
      .map(part => part[0])
      .slice(0, 2)
      .join('')
      .toUpperCase();

    // Dept class
    const deptKey = (student.course || 'CSE').toLowerCase();
    const deptClass = `dept-${deptKey}`;

    // Status class
    const statusClass = student.status === 'Active' ? 'status-active' : 'status-inactive';

    tr.innerHTML = `
      <td><span class="student-id-pill">#${escapeHtml(student.student_id.toString())}</span></td>
      <td>
        <div class="student-name-cell">
          <div class="avatar-mini">${escapeHtml(initials)}</div>
          <div>
            <div>${escapeHtml(student.name)}</div>
          </div>
        </div>
      </td>
      <td>
        <span style="font-family: var(--font-mono); font-size: 0.86rem; color: #334155;">
          ${escapeHtml(student.email)}
        </span>
      </td>
      <td>
        <span class="dept-badge ${deptClass}">${escapeHtml(student.course)}</span>
      </td>
      <td>
        <span class="status-badge ${statusClass}">${escapeHtml(student.status)}</span>
      </td>
      <td style="text-align: center;">
        <div class="action-btn-group" style="justify-content: center;">
          <button type="button" class="btn-edit" onclick="openEditModal(${student.student_id})">
            ✏️ Edit
          </button>
          <button type="button" class="btn-delete" onclick="openDeleteModal(${student.student_id}, '${escapeQuote(student.name)}')">
            🗑️ Delete
          </button>
        </div>
      </td>
    `;
    elements.tbody.appendChild(tr);
  });
}

/**
 * 2. POST / PUT: Save student (Create or Update)
 */
async function handleFormSubmit(e) {
  e.preventDefault();
  clearValidationErrors();

  const id = elements.inputStudentId.value.trim();
  const name = elements.inputName.value.trim();
  const email = elements.inputEmail.value.trim();
  const course = elements.inputCourse.value;
  const status = elements.inputStatus.value;

  // Client-side quick check
  let hasError = false;
  if (!name) {
    showInputError(elements.inputName, elements.errName, 'Full Name is required.');
    hasError = true;
  }
  if (!email) {
    showInputError(elements.inputEmail, elements.errEmail, 'University email is required.');
    hasError = true;
  } else if (!validateEmail(email)) {
    showInputError(elements.inputEmail, elements.errEmail, 'Invalid email format (e.g., student@charusat.edu.in).');
    hasError = true;
  }

  if (hasError) return;

  const isEdit = Boolean(id);
  const method = isEdit ? 'PUT' : 'POST';
  const payload = {
    name,
    email,
    course,
    status
  };
  if (isEdit) {
    payload.student_id = parseInt(id, 10);
  }

  // Set button saving state
  elements.btnSaveStudent.disabled = true;
  elements.btnSaveText.textContent = isEdit ? 'Updating via Fetch...' : 'Creating via Fetch...';

  try {
    const res = await apiFetch('api.php', {
      method,
      body: JSON.stringify(payload)
    });

    if (res.ok && res.data && res.data.status === 'success') {
      closeModal();
      showBanner(res.data.message, 'success');
      // Reload student list asynchronously without page reload
      await loadStudents();
    } else {
      // Server returned validation error (400) or conflict (409)
      const errorMsg = res.data.message || 'Operation failed. Please review errors.';
      showModalAlert(errorMsg);

      if (res.data.errors) {
        if (res.data.errors.name) {
          showInputError(elements.inputName, elements.errName, res.data.errors.name);
        }
        if (res.data.errors.email) {
          showInputError(elements.inputEmail, elements.errEmail, res.data.errors.email);
        }
      }
    }
  } catch (err) {
    showModalAlert(`Network Error: ${err.message}`);
  } finally {
    elements.btnSaveStudent.disabled = false;
    elements.btnSaveText.textContent = isEdit ? 'Update Student Record' : 'Save Student Record';
  }
}

/**
 * 3. DELETE: Confirm and execute asynchronous deletion
 */
async function executeDelete() {
  if (!state.deleteTarget) return;

  const { id, name } = state.deleteTarget;
  elements.btnConfirmDelete.disabled = true;
  elements.btnConfirmDelete.textContent = 'Deleting...';

  try {
    const res = await apiFetch(`api.php?id=${id}`, {
      method: 'DELETE'
    });

    if (res.ok && res.data && res.data.status === 'success') {
      closeDeleteModal();
      showBanner(`Student record #${id} ('${name}') deleted successfully.`, 'success');

      // Animate row removal without page reload
      const row = document.getElementById(`student-row-${id}`);
      if (row) {
        row.style.transition = 'all 0.3s ease';
        row.style.opacity = '0';
        row.style.transform = 'translateX(20px)';
        setTimeout(async () => {
          row.remove();
          await loadStudents();
        }, 300);
      } else {
        await loadStudents();
      }
    } else {
      showBanner(res.data.message || 'Failed to delete student record.', 'error');
    }
  } catch (err) {
    showBanner(`Network Error: ${err.message}`, 'error');
  } finally {
    elements.btnConfirmDelete.disabled = false;
    elements.btnConfirmDelete.textContent = 'Confirm Delete';
    state.deleteTarget = null;
  }
}

// =============================================================================
// MODAL CONTROLS
// =============================================================================
function openAddModal() {
  clearValidationErrors();
  elements.inputStudentId.value = '';
  elements.inputName.value = '';
  elements.inputEmail.value = '';
  elements.inputCourse.value = 'CSE';
  elements.inputStatus.value = 'Active';

  elements.modalHeading.textContent = '➕ Add New Student Record';
  elements.btnSaveText.textContent = 'Save Student Record';
  elements.modal.classList.add('active');
  setTimeout(() => elements.inputName.focus(), 50);
}

async function openEditModal(studentId) {
  clearValidationErrors();
  // Fetch specific student via GET /api.php?id=X
  try {
    const res = await apiFetch(`api.php?id=${studentId}`, { method: 'GET' });
    if (res.ok && res.data && res.data.data) {
      const student = res.data.data;
      elements.inputStudentId.value = student.student_id;
      elements.inputName.value = student.name;
      elements.inputEmail.value = student.email;
      elements.inputCourse.value = student.course;
      elements.inputStatus.value = student.status;

      elements.modalHeading.textContent = `✏️ Edit Student Record #${student.student_id}`;
      elements.btnSaveText.textContent = 'Update Student Record';
      elements.modal.classList.add('active');
      setTimeout(() => elements.inputName.focus(), 50);
    } else {
      showBanner('Failed to load student details for editing.', 'error');
    }
  } catch (err) {
    showBanner(`Network Error: ${err.message}`, 'error');
  }
}

function closeModal() {
  elements.modal.classList.remove('active');
  clearValidationErrors();
}

function openDeleteModal(id, name) {
  state.deleteTarget = { id, name };
  elements.deleteStudentId.textContent = `#${id}`;
  elements.deleteStudentName.textContent = name;
  elements.deleteModal.classList.add('active');
}

function closeDeleteModal() {
  elements.deleteModal.classList.remove('active');
  state.deleteTarget = null;
}

// =============================================================================
// VALIDATION & FEEDBACK HELPERS
// =============================================================================
function showInputError(input, feedbackEl, message) {
  input.classList.add('is-invalid');
  feedbackEl.textContent = message;
  feedbackEl.classList.add('visible');
}

function clearValidationErrors() {
  elements.inputName.classList.remove('is-invalid');
  elements.inputEmail.classList.remove('is-invalid');
  elements.errName.classList.remove('visible');
  elements.errEmail.classList.remove('visible');
  elements.modalAlert.classList.remove('visible');
}

function showModalAlert(message) {
  elements.modalAlertMsg.textContent = message;
  elements.modalAlert.classList.add('visible');
}

function showBanner(message, type = 'success') {
  elements.feedbackBanner.className = `feedback-banner ${type}`;
  elements.feedbackText.textContent = message;
  elements.feedbackBanner.style.display = 'flex';
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Auto-dismiss after 6 seconds
  setTimeout(() => {
    if (elements.feedbackBanner.style.display !== 'none') {
      elements.feedbackBanner.style.display = 'none';
    }
  }, 6000);
}

function dismissBanner() {
  elements.feedbackBanner.style.display = 'none';
}

function showLoading(isLoading) {
  if (isLoading) {
    elements.loadingState.style.display = 'block';
    elements.emptyState.style.display = 'none';
    elements.tableWrapper.style.display = 'none';
  } else {
    elements.loadingState.style.display = 'none';
  }
}

function updateStatistics() {
  const total = state.students.length;
  const active = state.students.filter(s => s.status === 'Active').length;
  if (elements.statTotal) elements.statTotal.textContent = total;
  if (elements.statActive) elements.statActive.textContent = active;
}

function validateEmail(email) {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function escapeQuote(str) {
  return str.replace(/'/g, "\\'");
}

// =============================================================================
// INTERMEDIATE EXTENSION: AJAX SEARCH WITH DEBOUNCE (300ms)
// =============================================================================
function handleSearchInput(e) {
  const query = e.target.value;
  state.searchQuery = query;

  clearTimeout(state.debounceTimer);
  state.debounceTimer = setTimeout(() => {
    loadStudents();
  }, 300);
}

// =============================================================================
// AUTHENTIC DEVTOOLS NETWORK LOGGER & INSPECTOR
// =============================================================================
function logNetworkRequest(entry) {
  state.networkLogs.unshift(entry); // Prepend to show most recent at top
  if (state.networkLogs.length > 50) state.networkLogs.pop();

  if (elements.dtReqCount) {
    elements.dtReqCount.textContent = state.networkLogs.length;
  }

  renderDevToolsList();
  selectNetworkLog(0); // Auto-select most recent
}

function renderDevToolsList() {
  elements.dtRequestsTbody.innerHTML = '';

  state.networkLogs.forEach((log, index) => {
    const tr = document.createElement('tr');
    if (index === state.selectedLogIndex) {
      tr.className = 'selected';
    }

    const shortUrl = log.url.split('/').pop();
    let statusClass = 'badge-status-ok';
    if (log.status >= 200 && log.status < 300) {
      statusClass = log.status === 201 ? 'badge-status-created' : 'badge-status-ok';
    } else if (log.status >= 400 || log.status === 0) {
      statusClass = 'badge-status-err';
    }

    tr.innerHTML = `
      <td title="${escapeHtml(log.url)}">
        <span style="display: inline-block; width: 6px; height: 6px; border-radius: 50%; background: ${log.status >= 400 ? '#cf222e' : '#1a7f37'}; margin-right: 6px;"></span>
        ${escapeHtml(shortUrl)}
      </td>
      <td><span class="${statusClass}">${log.status} ${escapeHtml(log.statusText)}</span></td>
      <td>fetch</td>
      <td><strong>${escapeHtml(log.method)}</strong></td>
      <td>${log.duration} ms</td>
    `;

    tr.addEventListener('click', () => {
      selectNetworkLog(index);
    });

    elements.dtRequestsTbody.appendChild(tr);
  });
}

function selectNetworkLog(index) {
  state.selectedLogIndex = index;

  // Highlight selected row
  const rows = elements.dtRequestsTbody.querySelectorAll('tr');
  rows.forEach((r, i) => {
    if (i === index) r.classList.add('selected');
    else r.classList.remove('selected');
  });

  renderDetailTab();
}

function switchDetailTab(tabName) {
  state.activeDetailTab = tabName;
  const tabs = document.querySelectorAll('.dt-subtab');
  tabs.forEach(tab => {
    if (tab.textContent.toLowerCase().includes(tabName)) {
      tab.classList.add('active');
    } else {
      tab.classList.remove('active');
    }
  });
  renderDetailTab();
}

function renderDetailTab() {
  const log = state.networkLogs[state.selectedLogIndex];
  if (!log) {
    elements.dtDetailBody.innerHTML = 'No network request selected.';
    return;
  }

  if (state.activeDetailTab === 'headers') {
    elements.dtDetailBody.innerHTML = `
<strong style="color: #0550ae;">General</strong>
Request URL: <span class="json-string">${escapeHtml(window.location.origin + '/' + log.url)}</span>
Request Method: <span class="json-boolean">${escapeHtml(log.method)}</span>
Status Code: <span class="${log.status >= 400 ? 'json-boolean' : 'json-number'}">${log.status} ${escapeHtml(log.statusText)}</span>
Referrer Policy: strict-origin-when-cross-origin

<strong style="color: #0550ae;">Response Headers</strong>
${Object.entries(log.responseHeaders).map(([k, v]) => `${escapeHtml(k)}: <span class="json-string">${escapeHtml(v)}</span>`).join('\n')}

<strong style="color: #0550ae;">Request Headers</strong>
${Object.entries(log.requestHeaders).map(([k, v]) => `${escapeHtml(k)}: <span class="json-string">${escapeHtml(v)}</span>`).join('\n')}
    `;
  } else if (state.activeDetailTab === 'payload') {
    if (log.requestPayload) {
      elements.dtDetailBody.innerHTML = syntaxHighlightJson(log.requestPayload);
    } else {
      elements.dtDetailBody.innerHTML = '<span style="color: #64748b; font-style: italic;">No request payload sent with this ' + log.method + ' fetch request.</span>';
    }
  } else if (state.activeDetailTab === 'response') {
    if (log.responseBody) {
      elements.dtDetailBody.innerHTML = syntaxHighlightJson(log.responseBody);
    } else {
      elements.dtDetailBody.innerHTML = '<span style="color: #64748b; font-style: italic;">Empty response body returned.</span>';
    }
  }
}

function syntaxHighlightJson(jsonObj) {
  const json = JSON.stringify(jsonObj, null, 2);
  return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, match => {
    let cls = 'json-number';
    if (/^"/.test(match)) {
      if (/:$/.test(match)) {
        cls = 'json-key';
      } else {
        cls = 'json-string';
      }
    } else if (/true|false/.test(match)) {
      cls = 'json-boolean';
    } else if (/null/.test(match)) {
      cls = 'json-null';
    }
    return `<span class="${cls}">${escapeHtml(match)}</span>`;
  });
}

// =============================================================================
// EVENT LISTENERS INITIALIZATION
// =============================================================================
document.addEventListener('DOMContentLoaded', () => {
  // Search input with debounce
  elements.searchInput.addEventListener('input', handleSearchInput);

  // Filters
  elements.courseFilter.addEventListener('change', e => {
    state.selectedCourse = e.target.value;
    loadStudents();
  });

  elements.statusFilter.addEventListener('change', e => {
    state.selectedStatus = e.target.value;
    loadStudents();
  });

  // Buttons
  elements.btnRefresh.addEventListener('click', () => {
    showBanner('Reloading student catalog asynchronously via Fetch API...', 'info');
    loadStudents();
  });

  elements.btnOpenAddModal.addEventListener('click', openAddModal);
  elements.btnCloseModal.addEventListener('click', closeModal);
  elements.btnCancelModal.addEventListener('click', closeModal);

  // Form submit
  elements.modalForm.addEventListener('submit', handleFormSubmit);

  // Delete modal
  elements.btnCloseDeleteModal.addEventListener('click', closeDeleteModal);
  elements.btnCancelDelete.addEventListener('click', closeDeleteModal);
  elements.btnConfirmDelete.addEventListener('click', executeDelete);

  // DevTools Toggle
  elements.btnToggleDevtools.addEventListener('click', () => {
    const isHidden = elements.devtoolsSection.style.display === 'none';
    elements.devtoolsSection.style.display = isHidden ? 'block' : 'none';
  });

  // Close modals on overlay backdrop click
  window.addEventListener('click', e => {
    if (e.target === elements.modal) closeModal();
    if (e.target === elements.deleteModal) closeDeleteModal();
  });

  // Initial load
  loadStudents();
});
