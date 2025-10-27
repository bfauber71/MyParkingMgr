// MyParkingManager - Frontend Application (Security Enhanced)

// Get base path from dynamic configuration
const MPM_CONFIG = window.MPM_CONFIG || {
    basePath: (window.APP_CONFIG && window.APP_CONFIG.basePath) 
        ? window.APP_CONFIG.basePath 
        : '',
    apiBase: '/api'
};
const basePath = MPM_CONFIG.basePath;
const API_BASE = MPM_CONFIG.apiBase;
let currentUser = null;
let properties = [];
let currentSection = 'vehicles';
let allUsers = [];

// SECURITY: Remove sensitive debug logging
// Only log errors in production
const isDevelopment = window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1';
const secureLog = isDevelopment ? console.log : () => {};

// SECURITY: CSRF Token Management
let csrfToken = null;

async function getCsrfToken() {
    // Always fetch fresh CSRF token to avoid expiration issues
    try {
        const response = await fetch(`${API_BASE}/csrf-token`, {
            credentials: 'include'
        });
        if (response.ok) {
            const data = await response.json();
            if (data && data.token) {
                csrfToken = data.token;
                console.log('CSRF token fetched successfully, length:', csrfToken.length);
                return csrfToken;
            } else {
                console.error('CSRF token missing in response:', data);
                return null;
            }
        } else {
            console.error('Failed to fetch CSRF token, status:', response.status);
            return null;
        }
    } catch (error) {
        console.error('Failed to get CSRF token:', error);
        return null;
    }
}

// SECURITY: Enhanced HTML escaping function
function escapeHtml(text) {
    if (text == null) return '';
    const str = String(text);
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#x27;',
        '/': '&#x2F;',
        '`': '&#x60;',
        '=': '&#x3D;'
    };
    return str.replace(/[&<>"'`=\/]/g, s => map[s]);
}

// SECURITY: Safe DOM element creation
function createElement(tag, attributes = {}, textContent = '') {
    const element = document.createElement(tag);
    Object.keys(attributes).forEach(key => {
        if (key === 'className') {
            element.className = attributes[key];
        } else if (key.startsWith('data-')) {
            element.setAttribute(key, attributes[key]);
        } else {
            element[key] = attributes[key];
        }
    });
    if (textContent) {
        element.textContent = textContent;
    }
    return element;
}

// SECURITY: Content Security Policy compliant event handling
function safeAddEventListener(element, event, handler) {
    if (element && typeof handler === 'function') {
        element.addEventListener(event, handler);
    }
}

// Toast Notification System (Security Enhanced)
function showToast(message, type = 'info', autoClose = true) {
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = createElement('div', { className: 'toast-container' });
        document.body.appendChild(toastContainer);
    }
    
    const toast = createElement('div', { className: `toast ${type}` });
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    // SECURITY: Use textContent instead of innerHTML
    const iconSpan = createElement('span', { className: 'toast-icon' }, icons[type] || icons.info);
    const messageSpan = createElement('span', { className: 'toast-message' }, message);
    const closeBtn = createElement('button', { className: 'toast-close', 'aria-label': 'Close' }, '×');
    
    safeAddEventListener(closeBtn, 'click', () => removeToast(toast));
    
    toast.appendChild(iconSpan);
    toast.appendChild(messageSpan);
    toast.appendChild(closeBtn);
    
    toastContainer.appendChild(toast);
    
    if (autoClose) {
        setTimeout(() => removeToast(toast), 2000);
    }
    
    return toast;
}

function removeToast(toast) {
    if (!toast || !toast.parentElement) return;
    
    toast.classList.add('removing');
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}

// DOM Elements - Common
const loginPage = document.getElementById('loginPage');
const dashboardPage = document.getElementById('dashboardPage');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');
const logoutBtn = document.getElementById('logoutBtn');
const userInfo = document.getElementById('userInfo');

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    setupEventListeners();
});

function setupEventListeners() {
    secureLog('Setting up event listeners...');
    
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    
    // Set up tab click handlers
    const tabButtons = document.querySelectorAll('.tab-btn');
    secureLog('Found tab buttons:', tabButtons.length);
    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = e.target.dataset.tab || e.target.closest('.tab-btn')?.dataset.tab;
            secureLog('Tab clicked:', tabName);
            if (tabName) {
                switchTab(tabName);
            } else {
                console.error('No tab name found for click');
            }
        });
    });
    
    // Form submissions
    const propertyForm = document.querySelector('#propertyModal form');
    if (propertyForm) propertyForm.addEventListener('submit', handlePropertySubmit);
    
    const userForm = document.querySelector('#userModal form');
    if (userForm) userForm.addEventListener('submit', handleUserSubmit);
    
    const vehicleForm = document.querySelector('#vehicleModal form');
    if (vehicleForm) vehicleForm.addEventListener('submit', handleVehicleSubmit);
    
    const violationTypeForm = document.querySelector('#violationTypeModal form');
    if (violationTypeForm) violationTypeForm.addEventListener('submit', handleViolationTypeSubmit);
    
    document.querySelectorAll('[data-modal]').forEach(btn => {
        btn.addEventListener('click', (e) => closeModalByName(e.target.dataset.modal));
    });
    
    document.querySelectorAll('[data-cancel]').forEach(btn => {
        btn.addEventListener('click', (e) => closeModalByName(e.target.dataset.cancel));
    });
    
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            e.target.classList.remove('show');
        }
    });
    
    secureLog('Event listeners setup complete');
}

// SECURITY: Enhanced authentication with proper headers
async function secureApiCall(url, options = {}) {
    const token = await getCsrfToken();
    
    const defaultHeaders = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    if (token) {
        defaultHeaders['X-CSRF-Token'] = token;
        console.log('Adding CSRF token to request headers, length:', token.length);
    } else {
        console.warn('CSRF token is null - API call may fail for POST/PUT/DELETE requests');
    }
    
    const mergedOptions = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...(options.headers || {})
        },
        credentials: 'include'
    };
    
    console.log('Making API call to:', url, 'with CSRF token:', token ? 'present' : 'MISSING');
    return fetch(url, mergedOptions);
}

// Authentication
async function checkAuth() {
    // SECURITY: Remove demo mode that exposes internal structure
    try {
        const response = await secureApiCall(`${API_BASE}/user`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            currentUser = data.user;
            showDashboard();
        } else {
            showLogin();
        }
    } catch (error) {
        showLogin();
    }
}

let lockoutInterval = null;

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    const submitBtn = document.getElementById('loginSubmitBtn');
    const lockoutDiv = document.getElementById('loginLockout');
    
    try {
        const response = await secureApiCall(`${API_BASE}/login`, {
            method: 'POST',
            body: JSON.stringify({ username, password })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            currentUser = data.user;
            clearLoginLockout();
            showDashboard();
        } else if (response.status === 429 && data.locked) {
            // Account locked - show countdown
            showError('');
            startLockoutCountdown(data.remaining_seconds, data.message);
        } else {
            showError(data.error || 'Login failed');
            clearLoginLockout();
        }
    } catch (error) {
        showError('Network error. Please try again.');
        clearLoginLockout();
    }
}

function startLockoutCountdown(remainingSeconds, message) {
    const submitBtn = document.getElementById('loginSubmitBtn');
    const lockoutDiv = document.getElementById('loginLockout');
    const loginError = document.getElementById('loginError');
    
    // Disable login button
    submitBtn.disabled = true;
    submitBtn.textContent = 'Account Locked';
    
    // Clear any existing interval
    if (lockoutInterval) {
        clearInterval(lockoutInterval);
    }
    
    // Show lockout message
    lockoutDiv.style.display = 'block';
    loginError.classList.remove('show');
    
    function updateCountdown() {
        if (remainingSeconds <= 0) {
            clearLoginLockout();
            showToast('You may now try logging in again', 'info');
            return;
        }
        
        const minutes = Math.floor(remainingSeconds / 60);
        const seconds = remainingSeconds % 60;
        const timeStr = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        
        lockoutDiv.textContent = `Too many failed login attempts. Try again in ${timeStr}`;
        lockoutDiv.classList.add('show');
        
        remainingSeconds--;
    }
    
    // Update immediately
    updateCountdown();
    
    // Update every second
    lockoutInterval = setInterval(updateCountdown, 1000);
}

function clearLoginLockout() {
    const submitBtn = document.getElementById('loginSubmitBtn');
    const lockoutDiv = document.getElementById('loginLockout');
    
    if (lockoutInterval) {
        clearInterval(lockoutInterval);
        lockoutInterval = null;
    }
    
    submitBtn.disabled = false;
    submitBtn.textContent = 'Sign In';
    lockoutDiv.style.display = 'none';
    lockoutDiv.classList.remove('show');
}

async function handleLogout() {
    try {
        await secureApiCall(`${API_BASE}/logout`, {
            method: 'POST'
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    currentUser = null;
    csrfToken = null; // Clear CSRF token on logout
    showLogin();
}

function showLogin() {
    loginPage.style.display = 'block';
    dashboardPage.style.display = 'none';
    loginForm.reset();
    loginError.classList.remove('show');
}

async function showDashboard() {
    loginPage.style.display = 'none';
    dashboardPage.style.display = 'block';
    
    userInfo.textContent = `${currentUser.username} (${currentUser.role})`;
    
    applyPermissions();
    
    // Load properties in background, don't wait for it
    loadProperties().catch(err => console.error('Failed to load properties:', err));
    
    // Immediately show vehicles tab
    switchTab('vehicles');
}

function showError(message) {
    loginError.textContent = message;
    loginError.classList.add('show');
}

// Permission System
function hasPermission(module, action) {
    if (!currentUser) return false;
    
    // Fallback to role-based permissions if permissions not loaded
    if (!currentUser.permissions) {
        const role = (currentUser.role || '').toLowerCase();
        if (role === 'admin') return true;
        if (role === 'operator') return module === 'vehicles' && action === 'view';
        if (role === 'user') return module === 'vehicles';
        return false;
    }
    
    const perms = currentUser.permissions[module];
    if (!perms) return false;
    
    // Action hierarchy: create_delete implies edit and view, edit implies view
    switch (action) {
        case 'view':
            return perms.can_view || perms.can_edit || perms.can_create_delete;
        case 'edit':
            return perms.can_edit || perms.can_create_delete;
        case 'create_delete':
            return perms.can_create_delete;
        default:
            return false;
    }
}

function applyPermissions() {
    const propertiesTab = document.getElementById('propertiesTab');
    const databaseTab = document.getElementById('databaseTab');
    const violationsTab = document.getElementById('violationsTab');
    const addVehicleBtn = document.getElementById('addVehicleBtn');
    const importBtn = document.getElementById('importBtn');
    const exportBtn = document.getElementById('exportBtn');
    const addPropertyBtn = document.getElementById('addPropertyBtn');
    const addUserBtn = document.getElementById('addUserBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const findDuplicatesBtn = document.getElementById('findDuplicatesBtn');
    
    secureLog('Applying permissions for user:', currentUser.username);
    
    // Show tabs based on view permission
    propertiesTab.style.display = hasPermission('properties', 'view') ? 'block' : 'none';
    databaseTab.style.display = hasPermission('database', 'view') ? 'block' : 'none';
    violationsTab.style.display = hasPermission('violations', 'view') ? 'block' : 'none';
    
    // Show buttons based on permissions
    addVehicleBtn.style.display = hasPermission('vehicles', 'create_delete') ? 'inline-block' : 'none';
    addPropertyBtn.style.display = hasPermission('properties', 'create_delete') ? 'inline-block' : 'none';
    
    // Database tab buttons
    if (addUserBtn) addUserBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
    if (importBtn) importBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
    if (exportBtn) exportBtn.style.display = hasPermission('database', 'view') ? 'inline-block' : 'none';
    if (bulkDeleteBtn) bulkDeleteBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
    if (findDuplicatesBtn) findDuplicatesBtn.style.display = hasPermission('database', 'view') ? 'inline-block' : 'none';
    
    secureLog('Permissions applied successfully');
}

function canEditVehicles() {
    return hasPermission('vehicles', 'edit');
}

function canDeleteVehicles() {
    return hasPermission('vehicles', 'create_delete');
}

// Tab Navigation
function switchTab(tabName) {
    secureLog('Switching to tab:', tabName);
    currentSection = tabName;
    
    // Remove active class from all tabs and content
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to selected tab and content
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    const activeContent = document.getElementById(`${tabName}Section`);
    
    if (activeBtn) {
        activeBtn.classList.add('active');
        secureLog('Activated tab button:', tabName);
    } else {
        console.error('Tab button not found:', tabName);
    }
    
    if (activeContent) {
        activeContent.classList.add('active');
        secureLog('Activated tab content:', tabName);
    } else {
        console.error('Tab content not found:', tabName);
    }
    
    // Load section-specific data
    try {
        if (tabName === 'vehicles') {
            loadVehiclesSection();
        } else if (tabName === 'properties') {
            loadPropertiesSection();
        } else if (tabName === 'database') {
            secureLog('Loading database tab - initializing user management');
            loadUsersSection();
        } else if (tabName === 'violations') {
            loadViolationsManagementSection();
        } else if (tabName === 'settings') {
            loadSettingsSection();
        }
    } catch (error) {
        console.error('Error loading section:', tabName, error);
    }
}

// Properties
async function loadProperties() {
    secureLog('loadProperties() called, fetching from:', `${API_BASE}/properties`);
    try {
        const response = await secureApiCall(`${API_BASE}/properties`, {
            method: 'GET'
        });
        
        secureLog('Properties API response status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            secureLog('Properties loaded:', data);
            properties = data.properties || [];
            secureLog('Properties array now has', properties.length, 'items');
            updatePropertyFilters();
        } else {
            const errorText = await response.text();
            console.error('Properties API error:', response.status, errorText);
            // Initialize empty array so dropdowns still work
            properties = [];
            updatePropertyFilters();
        }
    } catch (error) {
        console.error('Error loading properties (network/parse):', error);
        // Initialize empty array so dropdowns still work
        properties = [];
        updatePropertyFilters();
    }
}

function updatePropertyFilters() {
    const propertyFilter = document.getElementById('propertyFilter');
    const vehicleProperty = document.getElementById('vehicleProperty');
    
    // SECURITY: Use safe DOM manipulation
    if (propertyFilter) {
        while (propertyFilter.firstChild) {
            propertyFilter.removeChild(propertyFilter.firstChild);
        }
        
        const defaultOption = createElement('option', { value: '' }, 'All Properties');
        propertyFilter.appendChild(defaultOption);
        
        properties.forEach(prop => {
            const option = createElement('option', { value: prop.name }, prop.name);
            propertyFilter.appendChild(option);
        });
    }
    
    if (vehicleProperty) {
        while (vehicleProperty.firstChild) {
            vehicleProperty.removeChild(vehicleProperty.firstChild);
        }
        
        const defaultOption = createElement('option', { value: '' }, 'Select Property');
        vehicleProperty.appendChild(defaultOption);
        
        properties.forEach(prop => {
            const option = createElement('option', { value: prop.name }, prop.name);
            vehicleProperty.appendChild(option);
        });
    }
}

async function loadPropertiesSection() {
    document.getElementById('addPropertyBtn').onclick = () => openPropertyModal();
    
    // PRODUCTION MODE: Fetch from API
    try {
        const response = await secureApiCall(`${API_BASE}/properties-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayPropertiesTable(data.properties);
        }
    } catch (error) {
        console.error('Error loading properties:', error);
    }
}

function displayPropertiesTable(properties) {
    const container = document.getElementById('propertiesResults');
    
    if (!container) return;
    
    // Clear existing content
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }
    
    if (properties.length === 0) {
        const noResults = createElement('div', { className: 'no-results' }, 'No properties found');
        container.appendChild(noResults);
        return;
    }
    
    // SECURITY: Build table using safe DOM methods
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    ['Name', 'Address', 'Primary Contact', 'Contact Phone', 'Contact Email', 'Created', 'Actions'].forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    
    properties.forEach(prop => {
        const row = createElement('tr');
        const primaryContact = prop.contacts && prop.contacts.length > 0 ? prop.contacts[0] : null;
        
        // Add cells with text content (safe from XSS)
        const cells = [
            prop.name,
            prop.address || 'N/A',
            primaryContact ? primaryContact.name : 'N/A',
            primaryContact && primaryContact.phone ? primaryContact.phone : 'N/A',
            primaryContact && primaryContact.email ? primaryContact.email : 'N/A',
            formatDate(prop.created_at)
        ];
        
        cells.forEach(cellText => {
            const td = createElement('td', {}, cellText);
            row.appendChild(td);
        });
        
        // Actions cell
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'table-actions' });
        
        const editBtn = createElement('button', {
            className: 'btn btn-small property-edit-btn',
            'data-property-id': prop.id
        }, 'Edit');
        
        const deleteBtn = createElement('button', {
            className: 'btn btn-small btn-danger property-delete-btn',
            'data-property-id': prop.id,
            'data-property-name': prop.name
        }, 'Delete');
        
        safeAddEventListener(editBtn, 'click', async () => {
            const property = properties.find(p => p.id === prop.id);
            if (property) {
                editProperty(property);
            }
        });
        
        safeAddEventListener(deleteBtn, 'click', () => {
            deleteProperty(prop.id, prop.name);
        });
        
        actionsDiv.appendChild(editBtn);
        actionsDiv.appendChild(deleteBtn);
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    dataTable.appendChild(table);
    container.appendChild(dataTable);
}

// Vehicles Section
async function loadVehiclesSection() {
    const searchBtn = document.getElementById('searchBtn');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const searchInput = document.getElementById('searchInput');
    const propertyFilter = document.getElementById('propertyFilter');
    const addVehicleBtn = document.getElementById('addVehicleBtn');
    
    if (addVehicleBtn) {
        addVehicleBtn.onclick = () => openVehicleModal();
    }
    
    if (searchBtn) {
        searchBtn.onclick = async () => {
            const query = searchInput.value;
            const property = propertyFilter.value;
            await searchVehicles(query, property);
        };
    }
    
    if (clearSearchBtn) {
        clearSearchBtn.onclick = () => {
            searchInput.value = '';
            propertyFilter.value = '';
            searchVehicles('', '');
        };
    }
    
    await searchVehicles('', '');
}

async function searchVehicles(query = '', property = '') {
    try {
        const params = new URLSearchParams();
        if (query) params.append('q', query);
        if (property) params.append('property', property);
        
        const response = await secureApiCall(`${API_BASE}/vehicles-search?${params}`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayVehicles(data.vehicles);
        } else {
            console.error('Vehicle search failed:', response.status);
            displayVehicles([]);
        }
    } catch (error) {
        console.error('Error searching vehicles:', error);
        displayVehicles([]);
    }
}

function displayVehicles(vehicles) {
    const container = document.getElementById('vehiclesResults');
    if (!container) return;
    
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }
    
    if (vehicles.length === 0) {
        const noResults = createElement('div', { className: 'no-results' }, 'No vehicles found. Try adjusting your search or add a new vehicle.');
        container.appendChild(noResults);
        return;
    }
    
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    ['Tag', 'Plate', 'Owner', 'Apt', 'Make/Model', 'Color', 'Year', 'Property', 'Violations', 'Actions'].forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    vehicles.forEach(vehicle => {
        const row = createElement('tr');
        
        [
            vehicle.tag_number || '-',
            vehicle.plate_number || '-',
            vehicle.owner_name || '-',
            vehicle.apt_number || '-',
            `${vehicle.make || ''} ${vehicle.model || ''}`.trim() || '-',
            vehicle.color || '-',
            vehicle.year || '-',
            vehicle.property || '-',
            vehicle.violation_count || '0'
        ].forEach(text => {
            const td = createElement('td', {}, text);
            row.appendChild(td);
        });
        
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'actions' });
        const createTicketBtn = createElement('button', { className: 'btn btn-sm btn-primary' }, 'Create Ticket');
        const editBtn = createElement('button', { className: 'btn btn-sm btn-secondary' }, 'Edit');
        const deleteBtn = createElement('button', { className: 'btn btn-sm btn-danger' }, 'Delete');
        
        safeAddEventListener(createTicketBtn, 'click', () => {
            openCreateTicketModal(vehicle);
        });
        
        safeAddEventListener(editBtn, 'click', () => {
            openVehicleModal(vehicle);
        });
        
        safeAddEventListener(deleteBtn, 'click', () => {
            deleteVehicle(vehicle.id, vehicle.tag_number);
        });
        
        actionsDiv.appendChild(createTicketBtn);
        if (canEditVehicles()) actionsDiv.appendChild(editBtn);
        if (canDeleteVehicles()) actionsDiv.appendChild(deleteBtn);
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    dataTable.appendChild(table);
    container.appendChild(dataTable);
}

// Users Section
async function loadUsersSection() {
    const addUserBtn = document.getElementById('addUserBtn');
    
    if (addUserBtn) {
        addUserBtn.onclick = () => openUserModal();
    }
    
    // Database page button handlers
    setupDatabasePageHandlers();
    
    try {
        const response = await secureApiCall(`${API_BASE}/users-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayUsers(data.users || []);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

async function setupDatabasePageHandlers() {
    // Import/Export buttons
    const importBtn = document.getElementById('importBtn');
    const exportBtn = document.getElementById('exportBtn');
    const importFileInput = document.getElementById('importFileInput');
    
    if (importBtn) {
        importBtn.onclick = () => {
            if (importFileInput) importFileInput.click();
        };
    }
    
    if (importFileInput) {
        importFileInput.onchange = handleImportFile;
    }
    
    if (exportBtn) {
        exportBtn.onclick = handleExportVehicles;
    }
    
    // Bulk operations
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const findDuplicatesBtn = document.getElementById('findDuplicatesBtn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.onclick = handleBulkDelete;
    }
    
    if (findDuplicatesBtn) {
        findDuplicatesBtn.onclick = handleFindDuplicates;
    }
    
    // Violation search
    const violationSearchBtn = document.getElementById('violationSearchBtn');
    const violationPrintBtn = document.getElementById('violationPrintBtn');
    const violationExportBtn = document.getElementById('violationExportBtn');
    const clearViolationSearchBtn = document.getElementById('clearViolationSearchBtn');
    
    if (violationSearchBtn) {
        violationSearchBtn.onclick = handleViolationSearch;
    }
    
    if (violationPrintBtn) {
        violationPrintBtn.onclick = handleViolationPrint;
    }
    
    if (violationExportBtn) {
        violationExportBtn.onclick = handleViolationExport;
    }
    
    if (clearViolationSearchBtn) {
        clearViolationSearchBtn.onclick = handleClearViolationSearch;
    }
    
    // Clear duplicates button
    const clearDuplicatesBtn = document.getElementById('clearDuplicatesBtn');
    if (clearDuplicatesBtn) {
        clearDuplicatesBtn.onclick = handleClearDuplicates;
    }
    
    // Populate dropdowns with properties and violation types
    await populateDatabaseDropdowns();
}

async function populateDatabaseDropdowns() {
    // Fetch properties
    try {
        const response = await secureApiCall(`${API_BASE}/properties-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            const properties = data.properties || [];
            
            // Populate bulk delete property dropdown
            const bulkDeleteProperty = document.getElementById('bulkDeleteProperty');
            if (bulkDeleteProperty) {
                bulkDeleteProperty.innerHTML = '<option value="">Select Property</option>';
                properties.forEach(property => {
                    const option = document.createElement('option');
                    option.value = property.id;
                    option.textContent = property.name;
                    bulkDeleteProperty.appendChild(option);
                });
            }
            
            // Populate violation search property filter
            const violationPropertyFilter = document.getElementById('violationPropertyFilter');
            if (violationPropertyFilter) {
                violationPropertyFilter.innerHTML = '<option value="">All Properties</option>';
                properties.forEach(property => {
                    const option = document.createElement('option');
                    option.value = property.id;
                    option.textContent = property.name;
                    violationPropertyFilter.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading properties for dropdowns:', error);
    }
    
    // Fetch violation types
    try {
        const response = await secureApiCall(`${API_BASE}/violations-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            const violations = data.violations || [];
            
            // Populate violation type filter
            const violationTypeFilter = document.getElementById('violationTypeFilter');
            if (violationTypeFilter) {
                violationTypeFilter.innerHTML = '<option value="">All Violations</option>';
                violations.forEach(violation => {
                    const option = document.createElement('option');
                    option.value = violation.id;
                    option.textContent = violation.name;
                    violationTypeFilter.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading violation types for dropdowns:', error);
    }
}

async function handleImportFile(e) {
    const file = e.target.files[0];
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-import`, {
            method: 'POST',
            body: formData,
            credentials: 'include'
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(`Successfully imported ${data.count || 0} vehicles`, 'success');
            searchVehicles('', '');
        } else {
            showToast(data.error || 'Failed to import vehicles', 'error');
        }
    } catch (error) {
        console.error('Error importing vehicles:', error);
        showToast('Error importing vehicles', 'error');
    }
    
    e.target.value = '';
}

async function handleExportVehicles() {
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-export`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `vehicles_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast('Vehicles exported successfully', 'success');
        } else {
            showToast('Failed to export vehicles', 'error');
        }
    } catch (error) {
        console.error('Error exporting vehicles:', error);
        showToast('Error exporting vehicles', 'error');
    }
}

async function handleBulkDelete() {
    const propertySelect = document.getElementById('bulkDeleteProperty');
    if (!propertySelect || !propertySelect.value) {
        showToast('Please select a property', 'error');
        return;
    }
    
    const propertyName = propertySelect.options[propertySelect.selectedIndex].text;
    
    if (!confirm(`Are you sure you want to delete ALL vehicles for property "${propertyName}"? This cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-bulk-delete`, {
            method: 'POST',
            body: JSON.stringify({ property: propertySelect.value })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(`Deleted ${data.count || 0} vehicles`, 'success');
            searchVehicles('', '');
        } else {
            showToast(data.error || 'Failed to delete vehicles', 'error');
        }
    } catch (error) {
        console.error('Error deleting vehicles:', error);
        showToast('Error deleting vehicles', 'error');
    }
}

async function handleFindDuplicates() {
    const criteriaSelect = document.getElementById('duplicateCriteria');
    const resultsDiv = document.getElementById('duplicatesResults');
    
    if (!criteriaSelect || !resultsDiv) return;
    
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-duplicates`, {
            method: 'POST',
            body: JSON.stringify({
                action: 'find',
                criteria: criteriaSelect.value
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            if (data.duplicates && data.duplicates.length > 0) {
                resultsDiv.innerHTML = `<p class="alert alert-warning">Found ${data.total_groups} duplicate group(s)</p>`;
                showToast(`Found ${data.total_groups} duplicate group(s)`, 'info');
            } else {
                resultsDiv.innerHTML = '<p class="alert alert-success">No duplicates found</p>';
                showToast('No duplicates found', 'success');
            }
        } else {
            showToast(data.error || 'Failed to find duplicates', 'error');
        }
    } catch (error) {
        console.error('Error finding duplicates:', error);
        showToast('Error finding duplicates', 'error');
    }
}

async function handleViolationSearch() {
    const startDate = document.getElementById('violationStartDate')?.value || '';
    const endDate = document.getElementById('violationEndDate')?.value || '';
    const property = document.getElementById('violationPropertyFilter')?.value || '';
    const violationType = document.getElementById('violationTypeFilter')?.value || '';
    const query = document.getElementById('violationSearchQuery')?.value || '';
    
    const searchData = {};
    if (startDate) searchData.start_date = startDate;
    if (endDate) searchData.end_date = endDate;
    if (property) searchData.property = property;
    if (violationType) searchData.violation_type = violationType;
    if (query) searchData.query = query;
    
    try {
        const response = await secureApiCall(`${API_BASE}/violations-search`, {
            method: 'POST',
            body: JSON.stringify(searchData)
        });
        
        const data = await response.json();
        
        if (response.ok) {
            displayViolationSearchResults(data.violations || []);
            showToast(`Found ${data.total || 0} violation(s)`, 'success');
        } else {
            showToast(data.error || 'Failed to search violations', 'error');
            displayViolationSearchResults([]);
        }
    } catch (error) {
        console.error('Error searching violations:', error);
        showToast('Error searching violations', 'error');
        displayViolationSearchResults([]);
    }
}

function displayViolationSearchResults(violations) {
    const resultsDiv = document.getElementById('violationSearchResults');
    const countSpan = document.getElementById('violationResultsCount');
    const actionsDiv = document.querySelector('.search-actions');
    
    if (!resultsDiv) return;
    
    if (countSpan) {
        countSpan.textContent = `${violations.length} result(s) found`;
    }
    
    if (actionsDiv) {
        actionsDiv.style.display = violations.length > 0 ? 'block' : 'none';
    }
    
    if (violations.length === 0) {
        resultsDiv.innerHTML = '<p class="empty-state">No violations found matching your criteria.</p>';
        return;
    }
    
    const table = createElement('table', { className: 'data-table' });
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    ['Date', 'Vehicle', 'Violation', 'Property', 'Status', 'Actions'].forEach(text => {
        const th = createElement('th', {}, text);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    violations.forEach(violation => {
        const row = createElement('tr');
        
        // Build vehicle description
        let vehicleDesc = '';
        if (violation.plate_number) {
            vehicleDesc = violation.plate_number;
        } else if (violation.tag_number) {
            vehicleDesc = violation.tag_number;
        } else {
            vehicleDesc = 'Unknown';
        }
        
        // Add vehicle details if available
        const vehicleParts = [];
        if (violation.year) vehicleParts.push(violation.year);
        if (violation.color) vehicleParts.push(violation.color);
        if (violation.make) vehicleParts.push(violation.make);
        if (violation.model) vehicleParts.push(violation.model);
        
        if (vehicleParts.length > 0) {
            vehicleDesc += ` (${vehicleParts.join(' ')})`;
        }
        
        [
            formatDate(violation.created_at),
            vehicleDesc,
            violation.violation_list || 'N/A',
            violation.property || 'N/A',
            violation.status || 'Active'
        ].forEach(text => {
            const td = createElement('td', {}, text);
            row.appendChild(td);
        });
        
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'actions' });
        
        // Add reprint button for all violations (using the violation ticket id)
        const reprintBtn = createElement('button', { className: 'btn btn-sm btn-primary' }, 'Reprint Ticket');
        safeAddEventListener(reprintBtn, 'click', () => {
            window.open(`violations-print.html?id=${violation.id}`, '_blank');
            showToast('Opening ticket for printing', 'info');
        });
        actionsDiv.appendChild(reprintBtn);
        
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        tbody.appendChild(row);
    });
    table.appendChild(tbody);
    
    resultsDiv.innerHTML = '';
    resultsDiv.appendChild(table);
}

async function handleViolationPrint() {
    const resultsDiv = document.getElementById('violationSearchResults');
    if (!resultsDiv || !resultsDiv.querySelector('table')) {
        showToast('No results to print', 'warning');
        return;
    }
    
    // Create print window
    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        showToast('Please allow popups to print', 'error');
        return;
    }
    
    // Get the results table
    const table = resultsDiv.querySelector('table').cloneNode(true);
    
    // Build print HTML
    const printHtml = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Violation Search Results</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                
                body {
                    font-family: Arial, sans-serif;
                    padding: 0.5in;
                    background: white;
                }
                
                h1 {
                    font-size: 18px;
                    margin-bottom: 10px;
                    text-align: center;
                }
                
                .print-info {
                    font-size: 10px;
                    text-align: center;
                    margin-bottom: 20px;
                    color: #666;
                }
                
                table {
                    width: 100%;
                    border-collapse: collapse;
                    font-size: 10px;
                }
                
                th {
                    background: #333;
                    color: white;
                    padding: 8px;
                    text-align: left;
                    font-weight: bold;
                    border: 1px solid #000;
                }
                
                td {
                    padding: 6px 8px;
                    border: 1px solid #ccc;
                }
                
                tr:nth-child(even) {
                    background: #f9f9f9;
                }
                
                @media print {
                    body {
                        padding: 0.25in;
                    }
                    
                    @page {
                        size: letter;
                        margin: 0.5in;
                    }
                    
                    table {
                        page-break-inside: auto;
                    }
                    
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                    
                    thead {
                        display: table-header-group;
                    }
                }
                
                .no-print {
                    text-align: center;
                    margin: 20px 0;
                }
                
                .no-print button {
                    background: #4a90e2;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    font-size: 14px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 0 5px;
                }
                
                @media print {
                    .no-print {
                        display: none;
                    }
                }
            </style>
        </head>
        <body>
            <h1>Violation Search Results</h1>
            <div class="print-info">
                Generated: ${new Date().toLocaleString()}<br>
                Total Results: ${table.querySelectorAll('tbody tr').length}
            </div>
            
            <div class="no-print">
                <button onclick="window.print()">Print</button>
                <button onclick="window.close()">Close</button>
            </div>
            
            ${table.outerHTML}
        </body>
        </html>
    `;
    
    printWindow.document.write(printHtml);
    printWindow.document.close();
    showToast('Print preview opened', 'success');
}

async function handleViolationExport() {
    try {
        const response = await secureApiCall(`${API_BASE}/violations-export`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `violations_export_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
            showToast('Violations exported successfully', 'success');
        } else {
            showToast('Failed to export violations', 'error');
        }
    } catch (error) {
        console.error('Error exporting violations:', error);
        showToast('Error exporting violations', 'error');
    }
}

function handleClearViolationSearch() {
    // Clear all search form fields
    const startDate = document.getElementById('violationStartDate');
    const endDate = document.getElementById('violationEndDate');
    const propertyFilter = document.getElementById('violationPropertyFilter');
    const typeFilter = document.getElementById('violationTypeFilter');
    const searchQuery = document.getElementById('violationSearchQuery');
    const resultsDiv = document.getElementById('violationSearchResults');
    const countSpan = document.getElementById('violationResultsCount');
    const actionsDiv = document.querySelector('.search-actions');
    
    if (startDate) startDate.value = '';
    if (endDate) endDate.value = '';
    if (propertyFilter) propertyFilter.value = '';
    if (typeFilter) typeFilter.value = '';
    if (searchQuery) searchQuery.value = '';
    if (resultsDiv) resultsDiv.innerHTML = '';
    if (countSpan) countSpan.textContent = '';
    if (actionsDiv) actionsDiv.style.display = 'none';
    
    showToast('Search cleared', 'info');
}

function handleClearDuplicates() {
    const resultsDiv = document.getElementById('duplicatesResults');
    if (resultsDiv) {
        resultsDiv.innerHTML = '';
    }
    showToast('Results cleared', 'info');
}

function displayUsers(users) {
    const container = document.getElementById('usersResults');
    if (!container) return;
    
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }
    
    if (users.length === 0) {
        const noResults = createElement('div', { className: 'no-results' }, 'No users found');
        container.appendChild(noResults);
        return;
    }
    
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    ['Username', 'Email', 'Role', 'Created', 'Actions'].forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    users.forEach(user => {
        const row = createElement('tr');
        
        [
            user.username || '-',
            user.email || '-',
            user.role || '-',
            user.created_at ? new Date(user.created_at).toLocaleDateString() : '-'
        ].forEach(text => {
            const td = createElement('td', {}, text);
            row.appendChild(td);
        });
        
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'actions' });
        const editBtn = createElement('button', { className: 'btn btn-sm btn-secondary' }, 'Edit');
        const deleteBtn = createElement('button', { className: 'btn btn-sm btn-danger' }, 'Delete');
        
        safeAddEventListener(editBtn, 'click', () => {
            editUser(user);
        });
        
        safeAddEventListener(deleteBtn, 'click', () => {
            deleteUser(user.id, user.username);
        });
        
        actionsDiv.appendChild(editBtn);
        actionsDiv.appendChild(deleteBtn);
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    dataTable.appendChild(table);
    container.appendChild(dataTable);
}

// Violations Section
async function loadViolationsManagementSection() {
    const addViolationBtn = document.getElementById('addViolationBtn');
    
    if (addViolationBtn) {
        addViolationBtn.onclick = () => openViolationTypeModal();
    }
    
    await loadViolations();
}

// Settings Section
async function loadSettingsSection() {
    let printerSettings = {
        ticket_width: '2.5',
        ticket_height: '6',
        ticket_unit: 'in',
        logo_top: null,
        logo_bottom: null,
        logo_top_enabled: 'false',
        logo_bottom_enabled: 'false'
    };

    // Load current settings from API
    async function loadPrinterSettings() {
        try {
            const response = await secureApiCall(`${API_BASE}/printer-settings`, {
                method: 'GET'
            });

            if (response.ok) {
                const data = await response.json();
                printerSettings = data.settings;
                applySettingsToForm();
            }
        } catch (error) {
            console.error('Error loading printer settings:', error);
        }
    }

    // Apply settings to form fields
    function applySettingsToForm() {
        document.getElementById('ticketWidth').value = printerSettings.ticket_width;
        document.getElementById('ticketHeight').value = printerSettings.ticket_height;
        document.getElementById('ticketUnit').value = printerSettings.ticket_unit;
        document.getElementById('logoTopEnabled').checked = printerSettings.logo_top_enabled === 'true';
        document.getElementById('logoBottomEnabled').checked = printerSettings.logo_bottom_enabled === 'true';

        // Show/hide logo upload sections
        document.getElementById('logoTopUpload').style.display = 
            printerSettings.logo_top_enabled === 'true' ? 'block' : 'none';
        document.getElementById('logoBottomUpload').style.display = 
            printerSettings.logo_bottom_enabled === 'true' ? 'block' : 'none';

        // Show logo previews
        if (printerSettings.logo_top) {
            document.getElementById('logoTopPreview').innerHTML = 
                `<img src="${printerSettings.logo_top}" style="max-width: 200px; max-height: 100px;">`;
        }
        if (printerSettings.logo_bottom) {
            document.getElementById('logoBottomPreview').innerHTML = 
                `<img src="${printerSettings.logo_bottom}" style="max-width: 200px; max-height: 100px;">`;
        }
    }

    // Handle checkbox toggles
    document.getElementById('logoTopEnabled').addEventListener('change', function() {
        document.getElementById('logoTopUpload').style.display = this.checked ? 'block' : 'none';
    });

    document.getElementById('logoBottomEnabled').addEventListener('change', function() {
        document.getElementById('logoBottomUpload').style.display = this.checked ? 'block' : 'none';
    });

    // Handle logo uploads
    document.getElementById('logoTopFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                printerSettings.logo_top = e.target.result;
                document.getElementById('logoTopPreview').innerHTML = 
                    `<img src="${e.target.result}" style="max-width: 200px; max-height: 100px;">`;
            };
            reader.readAsDataURL(file);
        }
    });

    document.getElementById('logoBottomFile').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                printerSettings.logo_bottom = e.target.result;
                document.getElementById('logoBottomPreview').innerHTML = 
                    `<img src="${e.target.result}" style="max-width: 200px; max-height: 100px;">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Save settings
    document.getElementById('savePrinterSettingsBtn').addEventListener('click', async function() {
        try {
            printerSettings.ticket_width = document.getElementById('ticketWidth').value;
            printerSettings.ticket_height = document.getElementById('ticketHeight').value;
            printerSettings.ticket_unit = document.getElementById('ticketUnit').value;
            printerSettings.logo_top_enabled = document.getElementById('logoTopEnabled').checked ? 'true' : 'false';
            printerSettings.logo_bottom_enabled = document.getElementById('logoBottomEnabled').checked ? 'true' : 'false';

            const response = await secureApiCall(`${API_BASE}/printer-settings`, {
                method: 'POST',
                body: JSON.stringify({
                    settings: printerSettings
                })
            });

            if (response.ok) {
                showToast('Printer settings saved successfully', 'success');
            } else {
                const errorData = await response.json();
                console.error('Printer settings error:', errorData);
                showToast(errorData.error || 'Error saving printer settings', 'error');
            }
        } catch (error) {
            console.error('Error saving printer settings:', error);
            showToast('Error saving printer settings', 'error');
        }
    });

    // Reset to defaults
    document.getElementById('resetPrinterSettingsBtn').addEventListener('click', function() {
        printerSettings = {
            ticket_width: '2.5',
            ticket_height: '6',
            ticket_unit: 'in',
            logo_top: null,
            logo_bottom: null,
            logo_top_enabled: 'false',
            logo_bottom_enabled: 'false'
        };
        applySettingsToForm();
        document.getElementById('logoTopPreview').innerHTML = '';
        document.getElementById('logoBottomPreview').innerHTML = '';
        showToast('Settings reset to defaults', 'info');
    });

    // Load settings on page load
    await loadPrinterSettings();
}

async function loadViolations() {
    try {
        const response = await secureApiCall(`${API_BASE}/violations-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayViolations(data.violations || []);
        } else {
            showToast('Failed to load violations', 'error');
        }
    } catch (error) {
        console.error('Error loading violations:', error);
        showToast('Error loading violations', 'error');
    }
}

function displayViolations(violations) {
    const container = document.getElementById('violationsResults');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (violations.length === 0) {
        container.innerHTML = '<p class="empty-state">No violations found. Click "Add Violation" to create a violation type.</p>';
        return;
    }
    
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    ['Violation Type', 'Fine Amount', 'Tow Deadline', 'Display Order', 'Status', 'Actions'].forEach(text => {
        const th = createElement('th', {}, text);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    violations.forEach(violation => {
        const row = createElement('tr');
        
        [
            violation.name || 'N/A',
            violation.fine_amount ? `$${parseFloat(violation.fine_amount).toFixed(2)}` : 'N/A',
            violation.tow_deadline_hours ? `${violation.tow_deadline_hours} hours` : 'N/A',
            violation.display_order || '0',
            violation.is_active ? 'Active' : 'Inactive'
        ].forEach(text => {
            const td = createElement('td', {}, text);
            row.appendChild(td);
        });
        
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'actions' });
        const editBtn = createElement('button', { className: 'btn btn-sm btn-secondary' }, 'Edit');
        const deleteBtn = createElement('button', { className: 'btn btn-sm btn-danger' }, 'Delete');
        
        safeAddEventListener(editBtn, 'click', () => {
            editViolationType(violation);
        });
        
        safeAddEventListener(deleteBtn, 'click', () => {
            deleteViolationType(violation.id, violation.name);
        });
        
        actionsDiv.appendChild(editBtn);
        actionsDiv.appendChild(deleteBtn);
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    dataTable.appendChild(table);
    container.appendChild(dataTable);
}

function openViolationTypeModal(violation = null) {
    const modal = document.getElementById('violationTypeModal');
    const form = document.getElementById('violationTypeForm');
    const title = document.getElementById('violationTypeModalTitle');
    
    if (!modal || !form) return;
    
    if (violation) {
        title.textContent = 'Edit Violation Type';
        document.getElementById('violationTypeId').value = violation.id;
        document.getElementById('violationTypeName').value = violation.name || '';
        document.getElementById('violationTypeFineAmount').value = violation.fine_amount || '';
        document.getElementById('violationTypeTowDeadline').value = violation.tow_deadline_hours || '';
        document.getElementById('violationTypeDisplayOrder').value = violation.display_order || 0;
        document.getElementById('violationTypeIsActive').checked = violation.is_active == 1;
    } else {
        title.textContent = 'Add Violation Type';
        form.reset();
        document.getElementById('violationTypeId').value = '';
        document.getElementById('violationTypeIsActive').checked = true;
    }
    
    modal.classList.add('show');
}

function editViolationType(violation) {
    openViolationTypeModal(violation);
}

async function deleteViolationType(id, name) {
    if (!confirm(`Are you sure you want to delete violation type "${name}"?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/violations-delete`, {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            showToast('Violation type deleted successfully', 'success');
            loadViolations();
        } else {
            const data = await response.json();
            showToast(data.error || 'Failed to delete violation type', 'error');
        }
    } catch (error) {
        console.error('Error deleting violation type:', error);
        showToast('Error deleting violation type', 'error');
    }
}

async function handleViolationTypeSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const violationId = form.querySelector('[name="id"]')?.value;
    const isUpdate = violationId && violationId !== '';
    
    const formData = {
        name: form.querySelector('[name="name"]').value,
        fine_amount: parseFloat(form.querySelector('[name="fine_amount"]').value) || null,
        tow_deadline_hours: parseInt(form.querySelector('[name="tow_deadline_hours"]').value) || null,
        display_order: parseInt(form.querySelector('[name="display_order"]').value) || 0,
        is_active: form.querySelector('[name="is_active"]').checked ? 1 : 0
    };
    
    if (isUpdate) {
        formData.id = violationId;
    }
    
    try {
        const endpoint = isUpdate ? `${API_BASE}/violations-update` : `${API_BASE}/violations-add`;
        const response = await secureApiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(isUpdate ? 'Violation type updated successfully' : 'Violation type created successfully', 'success');
            closeModalByName('violationType');
            form.reset();
            loadViolations();
        } else {
            showToast(data.error || 'Failed to save violation type', 'error');
        }
    } catch (error) {
        console.error('Error saving violation type:', error);
        showToast('Error saving violation type', 'error');
    }
}

// Create Ticket Modal Functions
async function openCreateTicketModal(vehicle) {
    const modal = document.getElementById('violationModal');
    const form = document.getElementById('violationForm');
    const vehicleInfo = document.getElementById('violationVehicleInfo');
    const vehicleIdInput = document.getElementById('violationVehicleId');
    const checkboxesContainer = document.getElementById('violationCheckboxes');
    
    if (!modal || !form) return;
    
    vehicleIdInput.value = vehicle.id;
    vehicleInfo.textContent = `${vehicle.make || ''} ${vehicle.model || ''} - ${vehicle.plate_number || vehicle.tag_number || 'N/A'}`.trim();
    
    checkboxesContainer.innerHTML = '<div class="loading">Loading violations...</div>';
    
    modal.classList.add('show');
    
    try {
        const response = await secureApiCall(`${API_BASE}/violations-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            const violations = data.violations || [];
            
            checkboxesContainer.innerHTML = '';
            
            const activeViolations = violations.filter(v => v.is_active == 1);
            
            if (activeViolations.length === 0) {
                checkboxesContainer.innerHTML = '<p class="empty-state">No active violations available. Please add violation types first.</p>';
                return;
            }
            
            activeViolations.forEach(violation => {
                const checkboxDiv = createElement('div', { className: 'checkbox-item' });
                
                let labelText = violation.name;
                if (violation.fine_amount) {
                    labelText += ` - $${parseFloat(violation.fine_amount).toFixed(2)}`;
                }
                if (violation.tow_deadline_hours) {
                    labelText += ` (Tow: ${violation.tow_deadline_hours}hrs)`;
                }
                
                const label = document.createElement('label');
                
                const checkbox = createElement('input', { 
                    type: 'checkbox',
                    name: 'violations[]',
                    value: violation.id
                });
                checkbox.setAttribute('data-violation-name', violation.name);
                
                if (violation.id === 'other' || violation.name.toLowerCase().includes('other')) {
                    checkbox.addEventListener('change', () => {
                        const customNoteContainer = document.getElementById('customNoteContainer');
                        if (customNoteContainer) {
                            customNoteContainer.style.display = checkbox.checked ? 'block' : 'none';
                        }
                    });
                }
                
                label.appendChild(checkbox);
                label.appendChild(document.createTextNode(labelText));
                checkboxDiv.appendChild(label);
                checkboxesContainer.appendChild(checkboxDiv);
            });
        } else {
            checkboxesContainer.innerHTML = '<p class="error-message">Failed to load violations.</p>';
        }
    } catch (error) {
        console.error('Error loading violations:', error);
        checkboxesContainer.innerHTML = '<p class="error-message">Error loading violations.</p>';
    }
}

async function handleCreateViolation(event) {
    event.preventDefault();
    
    const form = event.target;
    const vehicleId = form.querySelector('[name="vehicleId"]').value;
    const checkboxes = form.querySelectorAll('input[name="violations[]"]:checked');
    const customNote = form.querySelector('[name="customNote"]')?.value || '';
    
    const violationIds = Array.from(checkboxes).map(cb => cb.value);
    
    if (violationIds.length === 0 && !customNote.trim()) {
        showToast('Please select at least one violation or enter a custom note', 'warning');
        return;
    }
    
    // Build list of selected violations for confirmation
    const violationNames = Array.from(checkboxes).map(cb => {
        // Get the violation name from the data attribute or adjacent text
        const violationName = cb.getAttribute('data-violation-name') || cb.value;
        return violationName;
    });
    
    let confirmMessage = 'Create violation ticket with the following violations?\n\n';
    violationNames.forEach(name => {
        confirmMessage += '• ' + name + '\n';
    });
    if (customNote.trim()) {
        confirmMessage += '\nCustom Note: ' + customNote.trim();
    }
    
    // Ask for confirmation before creating
    if (!confirm(confirmMessage)) {
        return;
    }
    
    const requestData = {
        vehicleId: vehicleId,
        violations: violationIds,
        customNote: customNote.trim()
    };
    
    try {
        const response = await secureApiCall(`${API_BASE}/violations-create`, {
            method: 'POST',
            body: JSON.stringify(requestData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast('Violation ticket created successfully', 'success');
            closeModalByName('violation');
            form.reset();
            
            if (data.ticketId) {
                const shouldPrint = confirm('Ticket created! Would you like to print it now?');
                if (shouldPrint) {
                    window.open(`violations-print.html?id=${data.ticketId}`, '_blank');
                }
            }
            
            searchVehicles('', '');
        } else {
            showToast(data.error || 'Failed to create violation ticket', 'error');
        }
    } catch (error) {
        console.error('Error creating violation ticket:', error);
        showToast('Error creating violation ticket', 'error');
    }
}

// Modal Management Functions
function openPropertyModal(property = null) {
    const modal = document.getElementById('propertyModal');
    const title = document.getElementById('propertyModalTitle');
    const form = modal.querySelector('form');
    
    if (property) {
        title.textContent = 'Edit Property';
        document.getElementById('propertyId').value = property.id;
        document.getElementById('propertyName').value = property.name;
        document.getElementById('propertyAddress').value = property.address || '';
        document.getElementById('propertyTicketText').value = property.custom_ticket_text || '';
        
        if (property.contacts && property.contacts.length > 0) {
            property.contacts.forEach((contact, index) => {
                if (index < 3) {
                    document.getElementById(`contact${index + 1}Name`).value = contact.name || '';
                    document.getElementById(`contact${index + 1}Phone`).value = contact.phone || '';
                    document.getElementById(`contact${index + 1}Email`).value = contact.email || '';
                }
            });
        }
    } else {
        title.textContent = 'Add Property';
        form.reset();
        document.getElementById('propertyId').value = '';
    }
    
    modal.classList.add('show');
}

function editProperty(property) {
    openPropertyModal(property);
}

async function deleteProperty(id, name) {
    if (!confirm(`Are you sure you want to delete property "${name}"?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/properties-delete`, {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            showToast('Property deleted successfully', 'success');
            loadPropertiesSection();
        } else {
            const data = await response.json();
            showToast(data.error || 'Failed to delete property', 'error');
        }
    } catch (error) {
        showToast('Error deleting property', 'error');
    }
}

async function openUserModal(user = null) {
    const modal = document.getElementById('userModal');
    const title = document.getElementById('userModalTitle');
    const form = modal.querySelector('form');
    
    if (user) {
        title.textContent = 'Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('userUsername').value = user.username;
        document.getElementById('userEmail').value = user.email || '';
        document.getElementById('userRole').value = user.role || 'user';
        document.getElementById('userPassword').required = false;
        
        // Load permissions
        await loadUserPermissions(user.id);
        
        // Load assigned properties
        await loadUserProperties(user.id);
    } else {
        title.textContent = 'Add User';
        form.reset();
        document.getElementById('userId').value = '';
        document.getElementById('userPassword').required = true;
        
        // Clear permissions
        document.querySelectorAll('.perm-check').forEach(checkbox => checkbox.checked = false);
        
        // Load properties list for new user
        await loadUserProperties(null);
    }
    
    modal.classList.add('show');
}

// Load and display properties for user assignment
async function loadUserProperties(userId) {
    try {
        // Fetch all properties
        const response = await secureApiCall(`${API_BASE}/properties-list`, {
            method: 'GET'
        });
        
        if (!response.ok) {
            console.error('Failed to load properties');
            return;
        }
        
        const data = await response.json();
        const properties = data.properties || [];
        
        // Fetch user's assigned properties if editing
        let assignedPropertyIds = [];
        if (userId) {
            const assignedResponse = await secureApiCall(`${API_BASE}/users-assigned-properties?user_id=${userId}`, {
                method: 'GET'
            });
            
            if (assignedResponse.ok) {
                const assignedData = await assignedResponse.json();
                assignedPropertyIds = assignedData.property_ids || [];
            }
        }
        
        // Render property checkboxes
        const container = document.getElementById('userPropertiesCheckboxes');
        if (properties.length === 0) {
            container.innerHTML = '<div style="color: #94a3b8; padding: 10px;">No properties available. Create properties first.</div>';
            return;
        }
        
        container.innerHTML = properties.map(property => `
            <div style="margin-bottom: 8px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input 
                        type="checkbox" 
                        class="property-check" 
                        data-property-id="${property.id}"
                        ${assignedPropertyIds.includes(property.id) ? 'checked' : ''}
                        style="margin-right: 8px;"
                    >
                    <span>${escapeHtml(property.name)}</span>
                </label>
            </div>
        `).join('');
        
    } catch (error) {
        console.error('Error loading properties:', error);
    }
}

// Load user permissions (existing permission checkboxes)
async function loadUserPermissions(userId) {
    try {
        const response = await secureApiCall(`${API_BASE}/users-permissions?user_id=${userId}`, {
            method: 'GET'
        });
        
        if (!response.ok) {
            return;
        }
        
        const data = await response.json();
        const permissions = data.permissions || [];
        
        // Clear all checkboxes first
        document.querySelectorAll('.perm-check').forEach(checkbox => checkbox.checked = false);
        
        // Check boxes based on permissions
        permissions.forEach(perm => {
            const checkbox = document.querySelector(`.perm-check[data-module="${perm.module}"][data-action="${perm.action}"]`);
            if (checkbox) {
                checkbox.checked = true;
            }
        });
    } catch (error) {
        console.error('Error loading permissions:', error);
    }
}

function editUser(user) {
    openUserModal(user);
}

async function deleteUser(id, username) {
    if (!confirm(`Are you sure you want to delete user "${username}"?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/users-delete`, {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            showToast('User deleted successfully', 'success');
            loadUsersSection();
        } else {
            const data = await response.json();
            showToast(data.error || 'Failed to delete user', 'error');
        }
    } catch (error) {
        showToast('Error deleting user', 'error');
    }
}

function openVehicleModal(vehicle = null) {
    const modal = document.getElementById('vehicleModal');
    const title = document.getElementById('vehicleModalTitle');
    const form = modal.querySelector('form');
    
    if (vehicle) {
        title.textContent = 'Edit Vehicle';
        document.getElementById('vehicleId').value = vehicle.id;
        document.getElementById('vehicleTag').value = vehicle.tag_number || '';
        document.getElementById('vehiclePlate').value = vehicle.plate_number || '';
        document.getElementById('vehicleOwner').value = vehicle.owner_name || '';
        document.getElementById('vehicleApt').value = vehicle.apt_number || '';
        document.getElementById('vehicleMake').value = vehicle.make || '';
        document.getElementById('vehicleModel').value = vehicle.model || '';
        document.getElementById('vehicleColor').value = vehicle.color || '';
        document.getElementById('vehicleYear').value = vehicle.year || '';
        document.getElementById('vehicleProperty').value = vehicle.property || '';
    } else {
        title.textContent = 'Add Vehicle';
        form.reset();
        document.getElementById('vehicleId').value = '';
    }
    
    modal.classList.add('show');
}

async function deleteVehicle(id, tag) {
    if (!confirm(`Are you sure you want to delete vehicle ${tag}?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-delete`, {
            method: 'POST',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            showToast('Vehicle deleted successfully', 'success');
            searchVehicles('', '');
        } else {
            const data = await response.json();
            showToast(data.error || 'Failed to delete vehicle', 'error');
        }
    } catch (error) {
        showToast('Error deleting vehicle', 'error');
    }
}

// Form Submission Handlers
async function handlePropertySubmit(e) {
    e.preventDefault();
    const form = e.target;
    const propertyId = document.getElementById('propertyId').value;
    const isUpdate = propertyId && propertyId !== '';
    
    const contacts = [];
    for (let i = 1; i <= 3; i++) {
        const name = document.getElementById(`contact${i}Name`).value.trim();
        const phone = document.getElementById(`contact${i}Phone`).value.trim();
        const email = document.getElementById(`contact${i}Email`).value.trim();
        
        if (name) {
            contacts.push({
                name: name,
                phone: phone || '',
                email: email || ''
            });
        }
    }
    
    const formData = {
        name: document.getElementById('propertyName').value,
        address: document.getElementById('propertyAddress').value,
        custom_ticket_text: document.getElementById('propertyTicketText').value,
        contacts: contacts
    };
    
    if (isUpdate) {
        formData.id = propertyId;
    }
    
    try {
        const endpoint = isUpdate ? `${API_BASE}/properties-update` : `${API_BASE}/properties-create`;
        const response = await secureApiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(isUpdate ? 'Property updated successfully' : 'Property created successfully', 'success');
            closeModalByName('property');
            form.reset();
            loadPropertiesSection();
        } else {
            showToast(data.error || 'Failed to save property', 'error');
        }
    } catch (error) {
        console.error('Error saving property:', error);
        showToast('Error saving property', 'error');
    }
}

// Set permission preset for user form
function setPermissionPreset(preset) {
    const checkboxes = document.querySelectorAll('#userModal .perm-check');
    
    checkboxes.forEach(checkbox => {
        if (preset === 'admin') {
            checkbox.checked = true;
        } else if (preset === 'view') {
            // View only: only check "view" checkboxes
            checkbox.checked = checkbox.dataset.action === 'view';
        } else if (preset === 'custom') {
            // Custom: uncheck all
            checkbox.checked = false;
        }
    });
}

async function handleUserSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const userId = document.getElementById('userId').value;
    const isUpdate = userId && userId !== '';
    
    // Collect basic user data
    const formData = {
        username: document.getElementById('userUsername').value,
        email: document.getElementById('userEmail').value,
        role: document.getElementById('userRole').value
    };
    
    // Add password if provided
    const passwordField = document.getElementById('userPassword');
    if (passwordField && passwordField.value) {
        formData.password = passwordField.value;
    }
    
    if (isUpdate) {
        formData.id = userId;
    }
    
    // Collect permissions
    const permissions = [];
    document.querySelectorAll('.perm-check').forEach(checkbox => {
        if (checkbox.checked) {
            permissions.push({
                module: checkbox.dataset.module,
                action: checkbox.dataset.action
            });
        }
    });
    formData.permissions = permissions;
    
    // Collect assigned properties
    const assignedProperties = [];
    document.querySelectorAll('.property-check:checked').forEach(checkbox => {
        assignedProperties.push(checkbox.dataset.propertyId);
    });
    formData.assigned_properties = assignedProperties;
    
    try {
        const endpoint = isUpdate ? `${API_BASE}/users-update` : `${API_BASE}/users-create`;
        const response = await secureApiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(isUpdate ? 'User updated successfully' : 'User created successfully', 'success');
            closeModalByName('user');
            form.reset();
            loadUsersSection();
        } else {
            showToast(data.error || 'Failed to save user', 'error');
        }
    } catch (error) {
        console.error('Error saving user:', error);
        showToast('Error saving user', 'error');
    }
}

async function handleVehicleSubmit(e) {
    e.preventDefault();
    const form = e.target;
    const vehicleId = form.querySelector('[name="vehicle_id"]')?.value;
    const isUpdate = vehicleId && vehicleId !== '';
    
    const formData = {
        property: form.querySelector('[name="property"]')?.value || '',
        tagNumber: form.querySelector('[name="tag_number"]')?.value || '',
        plateNumber: form.querySelector('[name="plate_number"]')?.value || '',
        state: form.querySelector('[name="state"]')?.value || '',
        make: form.querySelector('[name="make"]')?.value || '',
        model: form.querySelector('[name="model"]')?.value || '',
        color: form.querySelector('[name="color"]')?.value || '',
        year: form.querySelector('[name="year"]')?.value || '',
        aptNumber: form.querySelector('[name="apt_number"]')?.value || '',
        ownerName: form.querySelector('[name="owner_name"]')?.value || '',
        ownerPhone: form.querySelector('[name="owner_phone"]')?.value || '',
        ownerEmail: form.querySelector('[name="owner_email"]')?.value || '',
        reservedSpace: form.querySelector('[name="reserved_space"]')?.value || ''
    };
    
    if (isUpdate) {
        formData.id = vehicleId;
    }
    
    try {
        const endpoint = isUpdate ? `${API_BASE}/vehicles-update` : `${API_BASE}/vehicles-create`;
        const response = await secureApiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(isUpdate ? 'Vehicle updated successfully' : 'Vehicle created successfully', 'success');
            closeModalByName('vehicle');
            form.reset();
            searchVehicles('', '');
        } else {
            showToast(data.error || 'Failed to save vehicle', 'error');
        }
    } catch (error) {
        console.error('Error saving vehicle:', error);
        showToast('Error saving vehicle', 'error');
    }
}

// Helper Functions
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

function closeModalByName(modalName) {
    const modal = document.getElementById(`${modalName}Modal`);
    if (modal) {
        modal.classList.remove('show');
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// SECURITY: Input validation helper
function validateInput(input, type) {
    if (!input) return '';
    
    switch (type) {
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(input) ? input : '';
        case 'phone':
            const phoneRegex = /^[\d\s\-\+\(\)]+$/;
            return phoneRegex.test(input) ? input : '';
        case 'alphanumeric':
            const alphanumericRegex = /^[a-zA-Z0-9\s\-]+$/;
            return alphanumericRegex.test(input) ? input : '';
        default:
            return escapeHtml(input);
    }
}

// SECURITY: Rate limiting for API calls
const rateLimiter = {
    calls: {},
    isAllowed(endpoint, maxCalls = 10, windowMs = 60000) {
        const now = Date.now();
        const key = endpoint;
        
        if (!this.calls[key]) {
            this.calls[key] = [];
        }
        
        // Remove old calls outside the window
        this.calls[key] = this.calls[key].filter(timestamp => now - timestamp < windowMs);
        
        if (this.calls[key].length >= maxCalls) {
            return false;
        }
        
        this.calls[key].push(now);
        return true;
    }
};

// Export functions for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        escapeHtml,
        createElement,
        validateInput,
        rateLimiter
    };
}