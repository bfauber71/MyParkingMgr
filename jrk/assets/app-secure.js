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
let isViewingDuplicates = false;
let appTimezone = 'America/New_York';

// Detect iOS Safari for download compatibility
function isIosSafari() {
    const ua = navigator.userAgent;
    const iOS = /iPad|iPhone|iPod/.test(ua) && !window.MSStream;
    const webkit = /WebKit/.test(ua);
    return iOS && webkit;
}

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
// Real-time clock update function
function updateClock() {
    const navDate = document.getElementById('navDate');
    const navTime = document.getElementById('navTime');
    
    if (!navDate || !navTime) return;
    
    try {
        const now = new Date();
        
        // Format date
        const dateOptions = { 
            weekday: 'short', 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric',
            timeZone: appTimezone 
        };
        const dateStr = now.toLocaleDateString('en-US', dateOptions);
        
        // Format time
        const timeOptions = { 
            hour: '2-digit', 
            minute: '2-digit', 
            second: '2-digit',
            hour12: true,
            timeZone: appTimezone 
        };
        const timeStr = now.toLocaleTimeString('en-US', timeOptions);
        
        navDate.textContent = dateStr;
        navTime.textContent = timeStr;
    } catch (error) {
        console.error('Error updating clock:', error);
        // Fallback to default timezone
        const now = new Date();
        navDate.textContent = now.toLocaleDateString('en-US');
        navTime.textContent = now.toLocaleTimeString('en-US');
    }
}

// Start clock when page loads
let clockInterval = null;

document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    setupEventListeners();
    
    // Clock will be started after printer settings load with correct timezone
});

function setupEventListeners() {
    secureLog('Setting up event listeners...');
    
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    
    // Guest checkbox handler - show/hide guest_of field
    const guestCheckbox = document.getElementById('vehicleGuest');
    const guestOfContainer = document.getElementById('guestOfContainer');
    if (guestCheckbox && guestOfContainer) {
        guestCheckbox.addEventListener('change', function() {
            guestOfContainer.style.display = this.checked ? 'block' : 'none';
        });
    }
    
    // Set up dropdown menu handlers
    const dropdownToggle = document.getElementById('navDropdownToggle');
    const dropdownMenu = document.getElementById('navDropdownMenu');
    const dropdownItems = document.querySelectorAll('.nav-dropdown-item');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownToggle.classList.toggle('active');
            dropdownMenu.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.nav-dropdown-container')) {
                dropdownToggle.classList.remove('active');
                dropdownMenu.classList.remove('show');
            }
        });
    }
    
    secureLog('Found dropdown items:', dropdownItems.length);
    dropdownItems.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = e.target.dataset.tab || e.target.closest('.nav-dropdown-item')?.dataset.tab;
            secureLog('Tab clicked:', tabName);
            if (tabName) {
                switchTab(tabName);
                // Close dropdown after selection
                if (dropdownToggle && dropdownMenu) {
                    dropdownToggle.classList.remove('active');
                    dropdownMenu.classList.remove('show');
                }
            } else {
                console.error('No tab name found for click');
            }
        });
    });
    
    // Set up settings sub-tab click handlers
    const settingsTabButtons = document.querySelectorAll('.settings-tab-btn');
    settingsTabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const subTabName = e.target.dataset.settingsTab || e.target.closest('.settings-tab-btn')?.dataset.settingsTab;
            if (subTabName) {
                switchSettingsTab(subTabName);
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
    
    // Guest Pass event listeners
    const saveGuestPassBtn = document.getElementById('saveGuestPassBtn');
    if (saveGuestPassBtn) {
        saveGuestPassBtn.addEventListener('click', handleGuestPassSubmit);
    }
    
    const clearGuestPassBtn = document.getElementById('clearGuestPassBtn');
    if (clearGuestPassBtn) {
        clearGuestPassBtn.addEventListener('click', clearGuestPassForm);
    }
    
    // Ticket Status event listeners
    const ticketStatusSearchBtn = document.getElementById('ticketStatusSearchBtn');
    if (ticketStatusSearchBtn) {
        ticketStatusSearchBtn.addEventListener('click', loadTicketStatusSection);
    }
    
    const ticketStatusClearBtn = document.getElementById('ticketStatusClearBtn');
    if (ticketStatusClearBtn) {
        ticketStatusClearBtn.addEventListener('click', () => {
            document.getElementById('ticketStatusFilter').value = '';
            document.getElementById('ticketPropertyFilter').value = '';
            loadTicketStatusSection();
        });
    }
    
    secureLog('Event listeners setup complete');
}

// SECURITY: Enhanced authentication with proper headers
async function secureApiCall(url, options = {}) {
    const token = await getCsrfToken();
    
    const defaultHeaders = {
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    // Don't set Content-Type for FormData - browser will set multipart boundary automatically
    // Setting it manually causes "string doesn't match expected parameters" error on iOS Safari
    if (!(options.body instanceof FormData)) {
        defaultHeaders['Content-Type'] = 'application/json';
    }
    
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
    
    // Stop the clock on logout
    if (clockInterval) clearInterval(clockInterval);
    clockInterval = null;
    
    currentUser = null;
    csrfToken = null; // Clear CSRF token on logout
    showLogin();
}

function showLogin() {
    loginPage.style.display = 'block';
    dashboardPage.style.display = 'none';
    document.getElementById('navbar').style.display = 'none';
    loginForm.reset();
    loginError.classList.remove('show');
}

async function showDashboard() {
    console.log('=== showDashboard() called ===');
    console.log('Step 1: Hide login page');
    loginPage.style.display = 'none';
    console.log('Step 2: Show dashboard and navbar');
    dashboardPage.style.display = 'block';
    document.getElementById('navbar').style.display = 'flex';
    
    console.log('Step 3: Set user info');
    userInfo.textContent = `${currentUser.username} (${currentUser.role})`;
    
    console.log('Step 4: Apply permissions');
    applyPermissions();
    
    console.log('Step 5: Load properties');
    // Load properties in background, don't wait for it
    loadProperties().catch(err => console.error('Failed to load properties:', err));
    
    // Load and display license status badge
    console.log('Step 6: About to call loadLicenseStatus()...');
    loadLicenseStatus().catch(err => console.error('Failed to load license status:', err));
    
    // Load printer settings to initialize timezone and start clock
    console.log('Step 7: Loading printer settings for timezone...');
    loadPrinterSettingsForClock().catch(err => console.error('Failed to load printer settings:', err));
    
    console.log('Step 8: Switch to vehicles tab');
    // Immediately show vehicles tab
    switchTab('vehicles');
    console.log('=== showDashboard() completed ===');
}

// Load printer settings to initialize timezone and start clock
async function loadPrinterSettingsForClock() {
    let timezone = 'America/New_York'; // Default fallback
    
    try {
        const response = await secureApiCall(`${API_BASE}/printer-settings`, {
            method: 'GET'
        });

        if (response.ok) {
            const data = await response.json();
            timezone = data.settings?.timezone || 'America/New_York';
            console.log('Timezone loaded from database:', timezone);
        } else {
            console.warn('Printer settings API returned error, using default timezone');
        }
    } catch (error) {
        console.error('Error loading printer settings for clock:', error);
        console.log('Using default timezone:', timezone);
    }
    
    // ALWAYS start the clock, regardless of API success/failure
    appTimezone = timezone;
    if (clockInterval) clearInterval(clockInterval);
    updateClock(); // Update immediately
    clockInterval = setInterval(updateClock, 1000); // Update every second
    console.log('Clock started with timezone:', appTimezone);
}

// Load and display license status badge
async function loadLicenseStatus() {
    console.log('loadLicenseStatus() called');
    try {
        // Use v2 endpoint to bypass OPcache on production
        const response = await secureApiCall(`${API_BASE}/license-status-v2`, {
            method: 'GET'
        });
        
        console.log('License status response:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('License data:', data);
            const badge = document.getElementById('licenseStatusBadge');
            
            console.log('Badge element found:', !!badge);
            
            if (badge && data.license) {
                const status = data.license.status;
                console.log('License status:', status);
                
                if (status === 'trial') {
                    badge.textContent = 'TRIAL';
                    badge.className = 'license-badge trial';
                    console.log('Set TRIAL badge');
                } else if (status === 'expired') {
                    badge.textContent = 'EXPIRED';
                    badge.className = 'license-badge expired';
                    console.log('Set EXPIRED badge');
                } else if (status === 'licensed') {
                    badge.textContent = '';
                    badge.className = 'license-badge';
                    console.log('Cleared badge (licensed)');
                }
            }
        }
    } catch (error) {
        // Silently fail - license status is not critical
    }
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
    try {
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
        
        console.log('Applying permissions for user:', currentUser.username);
        
        // Show tabs based on view permission
        if (propertiesTab) propertiesTab.style.display = hasPermission('properties', 'view') ? 'block' : 'none';
        if (databaseTab) databaseTab.style.display = hasPermission('database', 'view') ? 'block' : 'none';
        if (violationsTab) violationsTab.style.display = hasPermission('violations', 'view') ? 'block' : 'none';
        
        // Show buttons based on permissions
        if (addVehicleBtn) addVehicleBtn.style.display = hasPermission('vehicles', 'create_delete') ? 'inline-block' : 'none';
        if (addPropertyBtn) addPropertyBtn.style.display = hasPermission('properties', 'create_delete') ? 'inline-block' : 'none';
        
        // Database tab buttons
        if (addUserBtn) addUserBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
        if (importBtn) importBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
        if (exportBtn) exportBtn.style.display = hasPermission('database', 'view') ? 'inline-block' : 'none';
        if (bulkDeleteBtn) bulkDeleteBtn.style.display = hasPermission('database', 'create_delete') ? 'inline-block' : 'none';
        if (findDuplicatesBtn) findDuplicatesBtn.style.display = hasPermission('database', 'view') ? 'inline-block' : 'none';
        
        console.log('Permissions applied successfully');
    } catch (error) {
        console.error('Error applying permissions:', error);
    }
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
    
    // Remove active class from all dropdown items and content
    document.querySelectorAll('.nav-dropdown-item').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to selected dropdown item and content
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    const activeContent = document.getElementById(`${tabName}Section`);
    
    if (activeBtn) {
        activeBtn.classList.add('active');
        // Update dropdown toggle text to show current section
        const currentSectionName = document.getElementById('currentSectionName');
        if (currentSectionName) {
            currentSectionName.textContent = activeBtn.textContent;
        }
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
        } else if (tabName === 'guest-pass') {
            loadGuestPassSection();
        } else if (tabName === 'ticket-status') {
            loadTicketStatusSection();
        } else if (tabName === 'database') {
            // Redirect to settings > users
            secureLog('Database tab accessed - redirecting to settings > users');
            switchTab('settings');
            switchSettingsTab('users');
        } else if (tabName === 'violations') {
            // Redirect to settings > violations
            secureLog('Violations tab accessed - redirecting to settings > violations');
            switchTab('settings');
            switchSettingsTab('violations');
        } else if (tabName === 'settings') {
            loadSettingsSection();
        }
    } catch (error) {
        console.error('Error loading section:', tabName, error);
    }
}

// Guest Pass Section
async function loadGuestPassSection() {
    // Populate property dropdown
    try {
        const response = await secureApiCall(`${API_BASE}/properties-list`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            const properties = data.properties || [];
            
            const gpProperty = document.getElementById('gpProperty');
            const ticketPropertyFilter = document.getElementById('ticketPropertyFilter');
            
            if (gpProperty) {
                gpProperty.innerHTML = '<option value="">Select Property</option>';
                properties.forEach(property => {
                    const option = document.createElement('option');
                    option.value = property.name;
                    option.textContent = property.name;
                    gpProperty.appendChild(option);
                });
            }
            
            if (ticketPropertyFilter) {
                ticketPropertyFilter.innerHTML = '<option value="">All Properties</option>';
                properties.forEach(property => {
                    const option = document.createElement('option');
                    option.value = property.name;
                    option.textContent = property.name;
                    ticketPropertyFilter.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading properties for guest pass:', error);
    }
}

// Settings Sub-tab Navigation
function switchSettingsTab(subTabName) {
    secureLog('Switching to settings sub-tab:', subTabName);
    
    // Remove active class from all settings sub-tabs
    document.querySelectorAll('.settings-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.settings-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to selected sub-tab
    const activeBtn = document.querySelector(`[data-settings-tab="${subTabName}"]`);
    const activeContent = document.getElementById(`settings${subTabName.replace(/-/g, '').replace(/^./, str => str.toUpperCase())}Tab`);
    
    // Fix ID mapping for camelCase
    const contentMap = {
        'users': 'settingsUsersTab',
        'violations': 'settingsViolationsTab',
        'database-ops': 'settingsDatabaseOpsTab',
        'printer': 'settingsPrinterTab'
    };
    
    const actualContent = document.getElementById(contentMap[subTabName]);
    
    if (activeBtn) {
        activeBtn.classList.add('active');
        secureLog('Activated settings sub-tab button:', subTabName);
    }
    
    if (actualContent) {
        actualContent.classList.add('active');
        secureLog('Activated settings sub-tab content:', subTabName);
    }
    
    // Load sub-tab specific data
    if (subTabName === 'users') {
        loadUsersSection();
    } else if (subTabName === 'violations') {
        loadViolationsManagementSection();
        setupViolationSearchHandlers();
    } else if (subTabName === 'database-ops') {
        setupDatabaseOpsHandlers();
    }
}

async function setupDatabaseOpsHandlers() {
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
    const clearDuplicatesBtn = document.getElementById('clearDuplicatesBtn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.onclick = handleBulkDelete;
    }
    
    if (findDuplicatesBtn) {
        findDuplicatesBtn.onclick = handleFindDuplicates;
    }
    
    if (clearDuplicatesBtn) {
        clearDuplicatesBtn.onclick = handleClearDuplicates;
    }
    
    // Populate dropdowns
    await populateDatabaseDropdowns();
}

// Set up violation search event handlers
function setupViolationSearchHandlers() {
    const violationSearchBtn = document.getElementById('violationSearchBtn');
    const violationPrintBtn = document.getElementById('violationPrintBtn');
    const violationExportBtn = document.getElementById('violationExportBtn');
    const clearViolationSearchBtn = document.getElementById('clearViolationSearchBtn');
    
    if (violationSearchBtn) {
        violationSearchBtn.onclick = handleViolationSearch;
        secureLog('Violation search button handler attached');
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
}

// Validate violation date range (global function for inline HTML)
window.validateViolationDateRange = function() {
    const startDateInput = document.getElementById('violationStartDate');
    const endDateInput = document.getElementById('violationEndDate');
    
    if (!startDateInput || !endDateInput) return;
    
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (start > end) {
            endDateInput.setCustomValidity('End date must be after start date');
            endDateInput.reportValidity();
        } else {
            endDateInput.setCustomValidity('');
        }
    }
};

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
            // Hide search results
            const container = document.getElementById('vehiclesResults');
            if (container) {
                container.innerHTML = '';
            }
        };
    }
    
    // Start with blank results - removed initial search call
}

async function searchVehicles(query = '', property = '') {
    try {
        const params = new URLSearchParams();
        if (query) params.append('q', query);
        if (property) params.append('property', property);
        
        // Add timestamp to prevent browser caching
        params.append('_t', Date.now());
        
        // Use v2 endpoint to bypass OPcache on production
        const response = await secureApiCall(`${API_BASE}/vehicles-search-v2?${params}`, {
            method: 'GET',
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            displayVehicles(data.vehicles, query);
        } else {
            console.error('Vehicle search failed:', response.status);
            displayVehicles([], query);
        }
    } catch (error) {
        console.error('Error searching vehicles:', error);
        displayVehicles([], query);
    }
}

function displayVehicles(vehicles, searchQuery = '') {
    const container = document.getElementById('vehiclesResults');
    if (!container) return;
    
    while (container.firstChild) {
        container.removeChild(container.firstChild);
    }
    
    if (vehicles.length === 0) {
        const noResultsContainer = createElement('div', { className: 'no-results' });
        const message = createElement('p', {}, 'No vehicles found. Try adjusting your search or add a new vehicle.');
        noResultsContainer.appendChild(message);
        
        // If search query looks like a plate/tag number, offer to create ticket
        if (searchQuery && searchQuery.trim().length > 0) {
            const createTicketBtn = createElement('button', { 
                className: 'btn btn-primary',
                style: 'margin-top: 15px;'
            }, `Create Ticket for "${searchQuery.trim()}"`);
            
            safeAddEventListener(createTicketBtn, 'click', () => {
                createTicketForUnknownPlate(searchQuery.trim());
            });
            
            noResultsContainer.appendChild(createTicketBtn);
        }
        
        container.appendChild(noResultsContainer);
        return;
    }
    
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    ['Tag', 'State', 'Plate', 'Owner', 'Apt', 'Make/Model', 'Color', 'Year', 'Guest Pass', 'Property', 'Violations', 'Actions'].forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    vehicles.forEach(vehicle => {
        const row = createElement('tr');
        
        // Check guest pass expiration
        let guestPassStatus = '-';
        if (vehicle.expiration_date) {
            const expirationDate = new Date(vehicle.expiration_date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            expirationDate.setHours(0, 0, 0, 0);
            
            if (expirationDate < today) {
                guestPassStatus = 'EXPIRED';
            } else {
                const expiryString = vehicle.expiration_date;
                guestPassStatus = `Expires ${expiryString}`;
            }
        }
        
        [
            vehicle.tag_number || '-',
            vehicle.state || '-',
            vehicle.plate_number || '-',
            vehicle.owner_name || '-',
            vehicle.apt_number || '-',
            `${vehicle.make || ''} ${vehicle.model || ''}`.trim() || '-',
            vehicle.color || '-',
            vehicle.year || '-',
            guestPassStatus,
            vehicle.property || '-',
            vehicle.violation_count || '0'
        ].forEach((text, index) => {
            const td = createElement('td', {}, text);
            // Add red styling for EXPIRED status
            if (index === 8 && text === 'EXPIRED') {
                td.style.color = '#d9534f';
                td.style.fontWeight = 'bold';
            }
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
                    option.value = property.name;
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
    
    if (!file.name.endsWith('.csv')) {
        showToast('Please select a CSV file', 'error');
        e.target.value = '';
        return;
    }
    
    const formData = new FormData();
    formData.append('csv', file);
    
    try {
        // Get CSRF token for file upload
        const token = await getCsrfToken();
        if (!token) {
            showToast('Security token unavailable. Please refresh and try again.', 'error');
            e.target.value = '';
            return;
        }
        
        const response = await fetch(`${API_BASE}/vehicles-import`, {
            method: 'POST',
            body: formData,
            credentials: 'include',
            headers: {
                'X-CSRF-Token': token
            }
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            let message = `Successfully imported ${data.imported || 0} vehicles`;
            if (data.errors && data.errors.length > 0) {
                message += `\n\nWarnings:\n${data.errors.slice(0, 5).join('\n')}`;
                if (data.errors.length > 5) {
                    message += `\n... and ${data.errors.length - 5} more`;
                }
            }
            showToast(message, 'success');
            searchVehicles('', '');
        } else {
            let errorMessage = data.error || 'Failed to import vehicles';
            if (data.errors && data.errors.length > 0) {
                errorMessage += `\n\nErrors:\n${data.errors.slice(0, 5).join('\n')}`;
                if (data.errors.length > 5) {
                    errorMessage += `\n... and ${data.errors.length - 5} more`;
                }
            }
            showToast(errorMessage, 'error');
        }
    } catch (error) {
        console.error('Error importing vehicles:', error);
        showToast('Error importing vehicles: ' + error.message, 'error');
    }
    
    e.target.value = '';
}

async function handleExportVehicles() {
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-export`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const csvContent = await response.text();
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const filename = `vehicles_export_${new Date().toISOString().split('T')[0]}.csv`;
            
            // Use traditional download for CSV (iOS Share API causes import issues)
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            
            setTimeout(() => {
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }, 100);
            
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
                isViewingDuplicates = true;
                displayDuplicatesResults(data.duplicates, criteriaSelect.value);
                showToast(`Found ${data.total_groups} duplicate group(s)`, 'info');
            } else {
                isViewingDuplicates = false;
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

function displayDuplicatesResults(duplicates, criteria) {
    const resultsDiv = document.getElementById('duplicatesResults');
    if (!resultsDiv) return;
    
    resultsDiv.innerHTML = '';
    
    // Add summary header
    const summary = createElement('div', { className: 'alert alert-warning' });
    summary.textContent = `Found ${duplicates.length} duplicate ${criteria === 'plate' ? 'plate' : 'tag'} number(s)`;
    resultsDiv.appendChild(summary);
    
    // Display each duplicate group
    duplicates.forEach((dupGroup, index) => {
        const groupDiv = createElement('div', { className: 'duplicate-group', style: 'margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;' });
        
        // Group header
        const groupHeader = createElement('h4', { style: 'margin: 0 0 10px 0; color: #d32f2f;' });
        groupHeader.textContent = `${criteria === 'plate' ? 'Plate' : 'Tag'}: ${dupGroup.value} (${dupGroup.count} vehicles)`;
        groupDiv.appendChild(groupHeader);
        
        // Create table for this group
        const table = createElement('table', { className: 'data-table', style: 'margin-top: 10px;' });
        const thead = createElement('thead');
        const headerRow = createElement('tr');
        ['Vehicle', 'Property', 'Actions'].forEach(text => {
            const th = createElement('th', {}, text);
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        table.appendChild(thead);
        
        const tbody = createElement('tbody');
        dupGroup.items.forEach(item => {
            const row = createElement('tr');
            
            // Vehicle column
            const vehicleCell = createElement('td');
            vehicleCell.textContent = item.vehicle || 'Unknown Vehicle';
            row.appendChild(vehicleCell);
            
            // Property column
            const propertyCell = createElement('td');
            propertyCell.textContent = item.property || 'Unknown Property';
            row.appendChild(propertyCell);
            
            // Actions column
            const actionsCell = createElement('td');
            const actionsDiv = createElement('div', { style: 'display: flex; gap: 5px;' });
            
            // Edit button
            if (canEditVehicles()) {
                const editBtn = createElement('button', { 
                    className: 'btn btn-sm btn-secondary',
                    title: 'Edit Vehicle'
                });
                editBtn.textContent = 'Edit';
                editBtn.onclick = () => editDuplicateVehicle(item.id);
                actionsDiv.appendChild(editBtn);
            }
            
            // Delete button
            if (canDeleteVehicles()) {
                const deleteBtn = createElement('button', { 
                    className: 'btn btn-sm btn-danger',
                    title: 'Delete Vehicle'
                });
                deleteBtn.textContent = 'Delete';
                const identifier = item.tag_number || item.plate_number || 'Unknown';
                deleteBtn.onclick = () => deleteDuplicateVehicle(item.id, identifier);
                actionsDiv.appendChild(deleteBtn);
            }
            
            actionsCell.appendChild(actionsDiv);
            row.appendChild(actionsCell);
            
            tbody.appendChild(row);
        });
        
        table.appendChild(tbody);
        groupDiv.appendChild(table);
        resultsDiv.appendChild(groupDiv);
    });
}

async function editDuplicateVehicle(vehicleId) {
    try {
        // Fetch full vehicle details
        const response = await secureApiCall(`${API_BASE}/vehicles-get?id=${vehicleId}`);
        const data = await response.json();
        
        if (response.ok && data.vehicle) {
            // Open the vehicle modal with full vehicle data
            openVehicleModal(data.vehicle);
        } else {
            showToast(data.error || 'Failed to load vehicle details', 'error');
        }
    } catch (error) {
        console.error('Error loading vehicle:', error);
        showToast('Error loading vehicle details', 'error');
    }
}

async function deleteDuplicateVehicle(vehicleId, identifier) {
    if (!confirm(`Are you sure you want to delete vehicle ${identifier}?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/vehicles-delete`, {
            method: 'POST',
            body: JSON.stringify({ id: vehicleId })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast('Vehicle deleted successfully', 'success');
            // Refresh duplicates search to show updated results
            await handleFindDuplicates();
        } else {
            showToast(data.error || 'Failed to delete vehicle', 'error');
        }
    } catch (error) {
        console.error('Error deleting vehicle:', error);
        showToast('Error deleting vehicle', 'error');
    }
}

async function handleViolationSearch() {
    const startDate = document.getElementById('violationStartDate')?.value || '';
    const endDate = document.getElementById('violationEndDate')?.value || '';
    const property = document.getElementById('violationPropertyFilter')?.value || '';
    const violationType = document.getElementById('violationTypeFilter')?.value || '';
    const query = document.getElementById('violationSearchQuery')?.value || '';
    
    // Validate date range
    if (startDate && endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        
        if (start > end) {
            showToast('Start date must be before end date', 'error');
            return;
        }
    }
    
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
    ['Date', 'Vehicle', 'Ticket Type', 'Violation', 'Fines', 'Property', 'Status', 'Actions'].forEach(text => {
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
        
        // Format fine amount
        const fineAmount = violation.total_fine && parseFloat(violation.total_fine) > 0
            ? `$${parseFloat(violation.total_fine).toFixed(2)}`
            : '-';
        
        // Format ticket type with styling
        const ticketType = violation.ticket_type || 'VIOLATION';
        const ticketTypeText = ticketType === 'WARNING' ? '⚠️ WARNING' : '🚫 VIOLATION';
        
        [
            formatDate(violation.created_at),
            vehicleDesc,
            ticketTypeText,
            violation.violation_list || 'N/A',
            fineAmount,
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
            const csvContent = await response.text();
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const filename = `violations_export_${new Date().toISOString().split('T')[0]}.csv`;
            
            // Use traditional download for CSV (iOS Share API causes import issues)
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            
            setTimeout(() => {
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
            }, 100);
            
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
    isViewingDuplicates = false;
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
        document.getElementById('timezone').value = printerSettings.timezone || 'America/New_York';
        document.getElementById('logoTopEnabled').checked = printerSettings.logo_top_enabled === 'true';
        document.getElementById('logoBottomEnabled').checked = printerSettings.logo_bottom_enabled === 'true';
        
        // Make form fields read-only for non-admin users
        const isAdmin = currentUser && currentUser.role === 'admin';
        if (!isAdmin) {
            // Disable save buttons for non-admins
            const saveBtn = document.getElementById('savePrinterSettingsBtn');
            const resetBtn = document.getElementById('resetPrinterSettingsBtn');
            const permNote = document.getElementById('printerSettingsPermissionNote');
            if (saveBtn) saveBtn.style.display = 'none';
            if (resetBtn) resetBtn.style.display = 'none';
            if (permNote) permNote.style.display = 'block';
            
            // Make all inputs disabled (view-only) for non-admins
            document.getElementById('ticketWidth').disabled = true;
            document.getElementById('ticketHeight').disabled = true;
            document.getElementById('ticketUnit').disabled = true;
            document.getElementById('timezone').disabled = true;
            document.getElementById('logoTopEnabled').disabled = true;
            document.getElementById('logoBottomEnabled').disabled = true;
        }
        
        // Update global timezone and start/restart clock
        appTimezone = printerSettings.timezone || 'America/New_York';
        console.log('Timezone loaded from settings:', appTimezone);
        
        // Start or restart the clock with the correct timezone
        if (clockInterval) clearInterval(clockInterval);
        updateClock(); // Update immediately
        clockInterval = setInterval(updateClock, 1000); // Update every second

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
            printerSettings.timezone = document.getElementById('timezone').value;
            printerSettings.logo_top_enabled = document.getElementById('logoTopEnabled').checked ? 'true' : 'false';
            printerSettings.logo_bottom_enabled = document.getElementById('logoBottomEnabled').checked ? 'true' : 'false';
            
            // Update global timezone immediately
            appTimezone = printerSettings.timezone;

            console.log('Saving printer settings:', {
                timezone: printerSettings.timezone,
                logo_top_length: printerSettings.logo_top ? printerSettings.logo_top.length : 'null',
                logo_bottom_length: printerSettings.logo_bottom ? printerSettings.logo_bottom.length : 'null',
                logo_top_enabled: printerSettings.logo_top_enabled,
                logo_bottom_enabled: printerSettings.logo_bottom_enabled
            });

            const response = await secureApiCall(`${API_BASE}/printer-settings`, {
                method: 'POST',
                body: JSON.stringify({
                    settings: printerSettings
                })
            });

            if (response.ok) {
                const data = await response.json();
                console.log('Printer settings saved:', data);
                console.log('New timezone applied:', appTimezone);
                updateClock();
                showToast('Printer settings saved successfully', 'success');
            } else {
                const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
                console.error('Printer settings error:', errorData);
                showToast(errorData.error || 'Error saving printer settings', 'error');
            }
        } catch (error) {
            console.error('Error saving printer settings:', error);
            showToast('Network error: Could not save printer settings', 'error');
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
            logo_bottom_enabled: 'false',
            timezone: 'America/New_York'
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
        console.log('loadViolations: Starting to load violations...');
        const response = await secureApiCall(`${API_BASE}/violations-list`, {
            method: 'GET'
        });
        
        console.log('loadViolations: Response status:', response.status, 'OK:', response.ok);
        
        if (response.ok) {
            const data = await response.json();
            console.log('loadViolations: Received data:', data);
            console.log('loadViolations: Violations count:', (data.violations || []).length);
            displayViolations(data.violations || []);
        } else {
            console.error('loadViolations: Failed with status', response.status);
            const errorText = await response.text();
            console.error('loadViolations: Error response:', errorText);
            showToast('Failed to load violations', 'error');
        }
    } catch (error) {
        console.error('loadViolations: Exception caught:', error);
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
    
    const ticketType = form.querySelector('[name="ticketType"]')?.value || 'VIOLATION';
    
    const requestData = {
        vehicleId: vehicleId,
        violations: violationIds,
        customNote: customNote.trim(),
        ticketType: ticketType
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
                window.open(`violations-print.html?id=${data.ticketId}`, '_blank');
                showToast('Opening ticket for printing', 'info');
            }
            
            // Hide search results after ticket creation
            const container = document.getElementById('vehiclesResults');
            if (container) {
                container.innerHTML = '';
            }
            // Also clear search inputs
            const searchInput = document.getElementById('searchInput');
            const propertyFilter = document.getElementById('propertyFilter');
            if (searchInput) searchInput.value = '';
            if (propertyFilter) propertyFilter.value = '';
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

// Global variable to store callback after vehicle creation
let vehicleCreationCallback = null;

async function openVehicleModal(vehicle = null, prefill = {}) {
    const modal = document.getElementById('vehicleModal');
    const title = document.getElementById('vehicleModalTitle');
    const form = modal.querySelector('form');
    
    if (vehicle) {
        title.textContent = 'Edit Vehicle';
        
        // CRITICAL FIX: Fetch fresh data from database instead of using cached object
        try {
            const response = await secureApiCall(`${API_BASE}/vehicles-get?id=${vehicle.id}`, {
                method: 'GET'
            });
            
            if (response.ok) {
                const data = await response.json();
                const freshVehicle = data.vehicle;
                
                document.getElementById('vehicleId').value = freshVehicle.id;
                document.getElementById('vehicleTag').value = freshVehicle.tag_number || '';
                document.getElementById('vehiclePlate').value = freshVehicle.plate_number || '';
                document.getElementById('vehicleState').value = freshVehicle.state || '';
                document.getElementById('vehicleOwner').value = freshVehicle.owner_name || '';
                document.getElementById('vehicleApt').value = freshVehicle.apt_number || '';
                document.getElementById('vehicleMake').value = freshVehicle.make || '';
                document.getElementById('vehicleModel').value = freshVehicle.model || '';
                document.getElementById('vehicleColor').value = freshVehicle.color || '';
                document.getElementById('vehicleYear').value = freshVehicle.year || '';
                document.getElementById('vehicleSpace').value = freshVehicle.reserved_space || '';
                document.getElementById('vehicleProperty').value = freshVehicle.property || '';
                document.getElementById('vehiclePhone').value = freshVehicle.owner_phone || '';
                document.getElementById('vehicleEmail').value = freshVehicle.owner_email || '';
                
                // Set resident, guest, and guestOf fields
                const residentCheckbox = document.getElementById('vehicleResident');
                const guestCheckbox = document.getElementById('vehicleGuest');
                const guestOfField = document.getElementById('vehicleGuestOf');
                const guestOfContainer = document.getElementById('guestOfContainer');
                
                if (residentCheckbox) residentCheckbox.checked = freshVehicle.resident !== undefined ? Boolean(freshVehicle.resident) : true;
                if (guestCheckbox) guestCheckbox.checked = freshVehicle.guest !== undefined ? Boolean(freshVehicle.guest) : false;
                if (guestOfField) guestOfField.value = freshVehicle.guest_of || '';
                
                // Show/hide guest_of field based on guest checkbox
                if (guestOfContainer && guestCheckbox) {
                    guestOfContainer.style.display = guestCheckbox.checked ? 'block' : 'none';
                }
            } else {
                showToast('Error loading vehicle data', 'error');
                return;
            }
        } catch (error) {
            console.error('Error fetching vehicle:', error);
            showToast('Error loading vehicle data', 'error');
            return;
        }
    } else {
        title.textContent = 'Add Vehicle';
        form.reset();
        document.getElementById('vehicleId').value = '';
        
        // Reset new fields to defaults
        const residentCheckbox = document.getElementById('vehicleResident');
        const guestCheckbox = document.getElementById('vehicleGuest');
        const guestOfField = document.getElementById('vehicleGuestOf');
        const guestOfContainer = document.getElementById('guestOfContainer');
        
        if (residentCheckbox) residentCheckbox.checked = true;
        if (guestCheckbox) guestCheckbox.checked = false;
        if (guestOfField) guestOfField.value = '';
        if (guestOfContainer) guestOfContainer.style.display = 'none';
        
        // Apply prefill data if provided
        if (prefill.plate) {
            document.getElementById('vehiclePlate').value = prefill.plate;
            document.getElementById('vehicleTag').value = prefill.plate; // Also set tag to same value
        }
        if (prefill.tag) {
            document.getElementById('vehicleTag').value = prefill.tag;
        }
    }
    
    modal.classList.add('show');
}

// Function to create a ticket for an unknown plate
function createTicketForUnknownPlate(plateNumber) {
    // Set callback to open create ticket modal after vehicle is created
    vehicleCreationCallback = async (newVehicle) => {
        // Close the vehicle modal first
        closeModalByName('vehicle');
        
        // Wait a moment for modal to close
        await new Promise(resolve => setTimeout(resolve, 200));
        
        // Open create ticket modal with the new vehicle
        openCreateTicketModal(newVehicle);
        
        // Clear the callback
        vehicleCreationCallback = null;
    };
    
    // Open vehicle modal with plate pre-filled
    openVehicleModal(null, { plate: plateNumber });
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
    
    console.log('=== Vehicle Submit ===');
    console.log('isUpdate:', isUpdate);
    console.log('vehicleId:', vehicleId);
    
    const formData = {
        property: form.querySelector('[name="property"]')?.value || '',
        tag_number: form.querySelector('[name="tag_number"]')?.value || '',
        plate_number: form.querySelector('[name="plate_number"]')?.value || '',
        state: form.querySelector('[name="state"]')?.value || '',
        make: form.querySelector('[name="make"]')?.value || '',
        model: form.querySelector('[name="model"]')?.value || '',
        color: form.querySelector('[name="color"]')?.value || '',
        year: form.querySelector('[name="year"]')?.value || '',
        apt_number: form.querySelector('[name="apt_number"]')?.value || '',
        owner_name: form.querySelector('[name="owner_name"]')?.value || '',
        owner_phone: form.querySelector('[name="owner_phone"]')?.value || '',
        owner_email: form.querySelector('[name="owner_email"]')?.value || '',
        reserved_space: form.querySelector('[name="reserved_space"]')?.value || '',
        resident: form.querySelector('[name="resident"]')?.checked || false,
        guest: form.querySelector('[name="guest"]')?.checked || false,
        guest_of: form.querySelector('[name="guest_of"]')?.value || ''
    };
    
    if (isUpdate) {
        formData.id = vehicleId;
    }
    
    console.log('Form data:', formData);
    
    try {
        // Use v2 endpoint to bypass OPcache on production
        const endpoint = isUpdate ? `${API_BASE}/vehicles-update-v2` : `${API_BASE}/vehicles-create`;
        console.log('Calling endpoint:', endpoint);
        
        const response = await secureApiCall(endpoint, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        console.log('Response status:', response.status);
        const data = await response.json();
        console.log('Response data:', data);
        
        if (response.ok && data.success) {
            showToast(isUpdate ? 'Vehicle updated successfully' : 'Vehicle created successfully', 'success');
            
            // If this was a create operation and there's a callback, execute it
            if (!isUpdate && vehicleCreationCallback && data.vehicle) {
                // Call the callback with the newly created vehicle
                vehicleCreationCallback(data.vehicle);
            } else {
                // Normal flow: close modal and refresh
                closeModalByName('vehicle');
                form.reset();
                
                // Refresh appropriate list based on current view
                if (isViewingDuplicates) {
                    handleFindDuplicates();
                } else {
                    searchVehicles('', '');
                }
            }
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

// Guest Pass Functions
async function clearGuestPassForm() {
    document.getElementById('gpProperty').value = '';
    document.getElementById('gpPlateNumber').value = '';
    document.getElementById('gpState').value = '';
    document.getElementById('gpMake').value = '';
    document.getElementById('gpModel').value = '';
    document.getElementById('gpColor').value = '';
    document.getElementById('gpYear').value = '';
    document.getElementById('gpOwnerName').value = '';
    document.getElementById('gpGuestOf').value = '';
}

async function handleGuestPassSubmit() {
    const property = document.getElementById('gpProperty').value;
    const plateNumber = document.getElementById('gpPlateNumber').value;
    
    if (!property || !plateNumber) {
        showToast('Property and Plate Number are required', 'error');
        return;
    }
    
    // Calculate expiration date (7 days from now)
    const expirationDate = new Date();
    expirationDate.setDate(expirationDate.getDate() + 7);
    const expirationString = expirationDate.toISOString().split('T')[0];
    
    const formData = {
        property: property,
        plateNumber: document.getElementById('gpPlateNumber').value || '',
        state: document.getElementById('gpState').value || '',
        make: document.getElementById('gpMake').value || '',
        model: document.getElementById('gpModel').value || '',
        color: document.getElementById('gpColor').value || '',
        year: document.getElementById('gpYear').value || '',
        ownerName: document.getElementById('gpOwnerName').value || '',
        tagNumber: document.getElementById('gpPlateNumber').value || '',
        guest: true,
        guestOf: document.getElementById('gpGuestOf').value || '',
        expirationDate: expirationString
    };
    
    try {
        const response = await secureApiCall(`${API_BASE}/guest-pass-create`, {
            method: 'POST',
            body: JSON.stringify(formData)
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast('Guest pass created successfully', 'success');
            
            // Open print window with guest pass
            printGuestPass(data.vehicle, data.property);
            
            // Clear form
            clearGuestPassForm();
        } else {
            showToast(data.error || 'Failed to create guest pass', 'error');
        }
    } catch (error) {
        console.error('Error creating guest pass:', error);
        showToast('Error creating guest pass', 'error');
    }
}

function printGuestPass(vehicle, property) {
    // Format expiration date
    const expDate = new Date(vehicle.expiration_date);
    const formattedExpDate = expDate.toLocaleDateString('en-US', { 
        month: 'long', 
        day: 'numeric', 
        year: 'numeric' 
    });
    
    // Build URL with parameters
    const params = new URLSearchParams({
        propertyName: property.name || '',
        propertyAddress: property.address || '',
        propertyContact: property.contact1_name ? `Contact: ${property.contact1_name} ${property.contact1_phone || ''}` : '',
        plateNumber: vehicle.plate_number || '',
        state: vehicle.state || '',
        make: vehicle.make || '',
        model: vehicle.model || '',
        color: vehicle.color || '',
        year: vehicle.year || '',
        guestName: vehicle.owner_name || '',
        guestOf: vehicle.guest_of ? `Apt/Unit ${vehicle.guest_of}` : '',
        expirationDate: formattedExpDate,
        logoUrl: property.logo_url || '../assets/logo.png'
    });
    
    // Open print window
    window.open(`guest-pass-print.html?${params.toString()}`, '_blank');
}

// Ticket Status Functions
async function loadTicketStatusSection() {
    const status = document.getElementById('ticketStatusFilter').value;
    const property = document.getElementById('ticketPropertyFilter').value;
    
    try {
        const params = new URLSearchParams();
        if (status) params.append('status', status);
        if (property) params.append('property', property);
        
        const response = await secureApiCall(`${API_BASE}/tickets-list?${params}`, {
            method: 'GET'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayTicketStatus(data.tickets || []);
        } else {
            showToast('Failed to load tickets', 'error');
        }
    } catch (error) {
        console.error('Error loading tickets:', error);
        showToast('Error loading tickets', 'error');
    }
}

function displayTicketStatus(tickets) {
    const container = document.getElementById('ticketStatusResults');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (tickets.length === 0) {
        container.innerHTML = '<div class="no-results"><p>No tickets found.</p></div>';
        return;
    }
    
    const dataTable = createElement('div', { className: 'data-table' });
    const table = createElement('table');
    const thead = createElement('thead');
    const headerRow = createElement('tr');
    
    ['Ticket ID', 'Vehicle', 'Property', 'Issued Date', 'Status', 'Disposition', 'Actions'].forEach(header => {
        const th = createElement('th', {}, header);
        headerRow.appendChild(th);
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    const tbody = createElement('tbody');
    tickets.forEach(ticket => {
        const row = createElement('tr');
        
        const vehicleInfo = `${ticket.plate_number || ticket.tag_number || 'N/A'}`;
        const issuedDate = new Date(ticket.issued_at).toLocaleDateString();
        const statusBadge = ticket.status === 'active' ? 
            '<span style="color: #28a745; font-weight: bold;">ACTIVE</span>' :
            '<span style="color: #6c757d;">CLOSED</span>';
        const disposition = ticket.fine_disposition || '-';
        
        [
            ticket.id.substring(0, 8),
            vehicleInfo,
            ticket.property || '-',
            issuedDate,
            statusBadge,
            disposition
        ].forEach((text, index) => {
            const td = createElement('td');
            if (index === 4) {
                td.innerHTML = text;
            } else {
                td.textContent = text;
            }
            row.appendChild(td);
        });
        
        const actionsTd = createElement('td');
        const actionsDiv = createElement('div', { className: 'actions' });
        
        // Add reprint button for all tickets
        const reprintBtn = createElement('button', { className: 'btn btn-sm btn-primary' }, 'Reprint');
        safeAddEventListener(reprintBtn, 'click', () => {
            window.open(`violations-print.html?id=${ticket.id}`, '_blank');
            showToast('Opening ticket for printing', 'info');
        });
        actionsDiv.appendChild(reprintBtn);
        
        if (ticket.status === 'active') {
            const collectedBtn = createElement('button', { className: 'btn btn-sm btn-success' }, 'Collected');
            const dismissedBtn = createElement('button', { className: 'btn btn-sm btn-secondary' }, 'Dismissed');
            
            safeAddEventListener(collectedBtn, 'click', () => {
                closeTicket(ticket.id, 'collected');
            });
            
            safeAddEventListener(dismissedBtn, 'click', () => {
                closeTicket(ticket.id, 'dismissed');
            });
            
            actionsDiv.appendChild(collectedBtn);
            actionsDiv.appendChild(dismissedBtn);
        }
        
        actionsTd.appendChild(actionsDiv);
        row.appendChild(actionsTd);
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    dataTable.appendChild(table);
    container.appendChild(dataTable);
}

async function closeTicket(ticketId, disposition) {
    if (!confirm(`Mark this ticket as ${disposition}?`)) {
        return;
    }
    
    try {
        const response = await secureApiCall(`${API_BASE}/ticket-close`, {
            method: 'POST',
            body: JSON.stringify({
                ticketId: ticketId,
                disposition: disposition
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.success) {
            showToast(`Ticket marked as ${disposition}`, 'success');
            loadTicketStatusSection();
        } else {
            showToast(data.error || 'Failed to close ticket', 'error');
        }
    } catch (error) {
        console.error('Error closing ticket:', error);
        showToast('Error closing ticket', 'error');
    }
}

// Export functions for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        escapeHtml,
        createElement,
        validateInput,
        rateLimiter
    };
}