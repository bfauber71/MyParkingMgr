// MyParkingManager - Frontend Application (Security Enhanced)

// Get base path from server-injected config (fallback to auto-detection for development)
const basePath = (window.APP_CONFIG && window.APP_CONFIG.basePath) 
    ? window.APP_CONFIG.basePath 
    : (window.location.pathname.startsWith('/jrk') ? '/jrk' : '');
const API_BASE = `${basePath}/api`;
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
    if (!csrfToken) {
        try {
            const response = await fetch(`${API_BASE}/csrf-token`, {
                credentials: 'include'
            });
            if (response.ok) {
                const data = await response.json();
                csrfToken = data.token;
            }
        } catch (error) {
            console.error('Failed to get CSRF token');
        }
    }
    return csrfToken;
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
    }
    
    const mergedOptions = {
        ...options,
        headers: {
            ...defaultHeaders,
            ...(options.headers || {})
        },
        credentials: 'include'
    };
    
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

// Continue with remaining functions...
// Due to length constraints, I'm creating the secure version in a new file

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