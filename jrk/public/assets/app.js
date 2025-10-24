// MyParkingManager - Frontend Application

// Auto-detect API base path from current pathname
const basePath = window.location.pathname.startsWith('/jrk') ? '/jrk' : '';
const API_BASE = `${basePath}/api`;
let currentUser = null;
let properties = [];
let currentSection = 'vehicles';
let allUsers = [];

// Toast Notification System
function showToast(message, type = 'info', autoClose = true) {
    let toastContainer = document.querySelector('.toast-container');
    
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    const icons = {
        success: '✓',
        error: '✕',
        warning: '⚠',
        info: 'ℹ'
    };
    
    toast.innerHTML = `
        <span class="toast-icon">${icons[type] || icons.info}</span>
        <span class="toast-message">${message}</span>
        <button class="toast-close" aria-label="Close">×</button>
    `;
    
    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', () => removeToast(toast));
    
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
    console.log('Setting up event listeners...');
    
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    
    // Set up tab click handlers
    const tabButtons = document.querySelectorAll('.tab-btn');
    console.log('Found tab buttons:', tabButtons.length);
    tabButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = e.target.dataset.tab || e.target.closest('.tab-btn')?.dataset.tab;
            console.log('Tab clicked:', tabName);
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
    
    console.log('Event listeners setup complete');
}

// Authentication
async function checkAuth() {
    // DEMO MODE: For Replit preview without database
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    
    if (isDemo) {
        // Simulate logged-in admin user for demo purposes
        currentUser = {
            id: 1,
            username: 'admin',
            role: 'Admin',
            email: 'admin@example.com'
        };
        properties = [
            { 
                id: 1, 
                name: 'Sunset Apartments',
                address: '123 Sunset Blvd',
                contacts: [
                    { name: 'Manager Office', phone: '555-0100', email: 'sunset@example.com', position: 0 }
                ]
            },
            { 
                id: 2, 
                name: 'Oak Ridge Condos',
                address: '456 Oak Street',
                contacts: [
                    { name: 'Front Desk', phone: '555-0200', email: 'oak@example.com', position: 0 }
                ]
            },
            { 
                id: 3, 
                name: 'Maple View Townhomes',
                address: '789 Maple Avenue',
                contacts: [
                    { name: 'Admin Office', phone: '555-0300', email: 'maple@example.com', position: 0 }
                ]
            }
        ];
        showDashboard();
        return;
    }
    
    // PRODUCTION MODE: Check real authentication
    try {
        const response = await fetch(`${API_BASE}/user`, {
            credentials: 'include'
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
        const response = await fetch(`${API_BASE}/login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
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
        await fetch(`${API_BASE}/logout`, {
            method: 'POST',
            credentials: 'include'
        });
    } catch (error) {
        console.error('Logout error:', error);
    }
    
    currentUser = null;
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
    // Matches legacy RBAC: Admin=all, User=vehicles only, Operator=vehicles view-only
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
    
    console.log('Applying permissions for user:', currentUser.username);
    
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
    
    console.log('Permissions applied successfully');
}

function canEditVehicles() {
    return hasPermission('vehicles', 'edit');
}

function canDeleteVehicles() {
    return hasPermission('vehicles', 'create_delete');
}

// Tab Navigation
function switchTab(tabName) {
    console.log('Switching to tab:', tabName);
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
        console.log('Activated tab button:', tabName);
    } else {
        console.error('Tab button not found:', tabName);
    }
    
    if (activeContent) {
        activeContent.classList.add('active');
        console.log('Activated tab content:', tabName);
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
            console.log('Loading database tab - initializing user management');
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
    console.log('loadProperties() called, fetching from:', `${API_BASE}/properties`);
    try {
        const response = await fetch(`${API_BASE}/properties`, {
            credentials: 'include'
        });
        
        console.log('Properties API response status:', response.status);
        
        if (response.ok) {
            const data = await response.json();
            console.log('Properties loaded:', data);
            properties = data.properties || [];
            console.log('Properties array now has', properties.length, 'items');
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
    
    propertyFilter.innerHTML = '<option value="">All Properties</option>';
    properties.forEach(prop => {
        const option = document.createElement('option');
        option.value = prop.name;
        option.textContent = prop.name;
        propertyFilter.appendChild(option);
    });
    
    vehicleProperty.innerHTML = '<option value="">Select Property</option>';
    properties.forEach(prop => {
        const option = document.createElement('option');
        option.value = prop.name;
        option.textContent = prop.name;
        vehicleProperty.appendChild(option);
    });
}

async function loadPropertiesSection() {
    document.getElementById('addPropertyBtn').onclick = () => openPropertyModal();
    
    // DEMO MODE: Show sample properties without database
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    if (isDemo) {
        const demoProperties = [
            { 
                id: 1, 
                name: 'Sunset Apartments', 
                address: '123 Sunset Blvd', 
                created_at: '2024-01-15',
                contacts: [
                    { name: 'Manager Office', phone: '555-0100', email: 'sunset@example.com', position: 0 }
                ]
            },
            { 
                id: 2, 
                name: 'Oak Ridge Condos', 
                address: '456 Oak Street', 
                created_at: '2024-02-20',
                contacts: [
                    { name: 'Front Desk', phone: '555-0200', email: 'oak@example.com', position: 0 }
                ]
            },
            { 
                id: 3, 
                name: 'Maple View Townhomes', 
                address: '789 Maple Avenue', 
                created_at: '2024-03-10',
                contacts: [
                    { name: 'Admin Office', phone: '555-0300', email: 'maple@example.com', position: 0 }
                ]
            }
        ];
        displayPropertiesTable(demoProperties);
        return;
    }
    
    // PRODUCTION MODE: Fetch from API
    try {
        const response = await fetch(`${API_BASE}/properties-list`, {
            credentials: 'include'
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
    
    if (properties.length === 0) {
        container.innerHTML = '<div class="no-results">No properties found</div>';
        return;
    }
    
    const table = `
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Primary Contact</th>
                        <th>Contact Phone</th>
                        <th>Contact Email</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${properties.map(prop => {
                        const primaryContact = prop.contacts && prop.contacts.length > 0 ? prop.contacts[0] : null;
                        return `
                        <tr>
                            <td>${escapeHtml(prop.name)}</td>
                            <td>${escapeHtml(prop.address || 'N/A')}</td>
                            <td>${primaryContact ? escapeHtml(primaryContact.name) : 'N/A'}</td>
                            <td>${primaryContact && primaryContact.phone ? escapeHtml(primaryContact.phone) : 'N/A'}</td>
                            <td>${primaryContact && primaryContact.email ? escapeHtml(primaryContact.email) : 'N/A'}</td>
                            <td>${formatDate(prop.created_at)}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-small" onclick='editProperty(${JSON.stringify(prop).replace(/'/g, "&#39;")})'>Edit</button>
                                    <button class="btn btn-small btn-danger" onclick="deleteProperty('${prop.id}', '${escapeHtml(prop.name)}')">Delete</button>
                                </div>
                            </td>
                        </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = table;
}

function openPropertyModal() {
    document.getElementById('propertyModalTitle').textContent = 'Add Property';
    document.getElementById('propertyForm').reset();
    document.getElementById('propertyId').value = '';
    document.getElementById('propertyModal').classList.add('show');
    
    document.getElementById('propertyForm').onsubmit = handleSaveProperty;
}

function editProperty(property) {
    document.getElementById('propertyModalTitle').textContent = 'Edit Property';
    document.getElementById('propertyId').value = property.id;
    document.getElementById('propertyName').value = property.name;
    document.getElementById('propertyAddress').value = property.address || '';
    
    document.getElementById('contact1Name').value = property.contacts[0]?.name || '';
    document.getElementById('contact1Phone').value = property.contacts[0]?.phone || '';
    document.getElementById('contact1Email').value = property.contacts[0]?.email || '';
    
    document.getElementById('contact2Name').value = property.contacts[1]?.name || '';
    document.getElementById('contact2Phone').value = property.contacts[1]?.phone || '';
    document.getElementById('contact2Email').value = property.contacts[1]?.email || '';
    
    document.getElementById('contact3Name').value = property.contacts[2]?.name || '';
    document.getElementById('contact3Phone').value = property.contacts[2]?.phone || '';
    document.getElementById('contact3Email').value = property.contacts[2]?.email || '';
    
    document.getElementById('propertyModal').classList.add('show');
    document.getElementById('propertyForm').onsubmit = handleUpdateProperty;
}

async function handleSaveProperty(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const contacts = [];
    
    if (formData.get('contact1Name')?.trim()) {
        contacts.push({
            name: formData.get('contact1Name').trim(),
            phone: formData.get('contact1Phone')?.trim() || '',
            email: formData.get('contact1Email')?.trim() || ''
        });
    }
    
    if (formData.get('contact2Name')?.trim()) {
        contacts.push({
            name: formData.get('contact2Name').trim(),
            phone: formData.get('contact2Phone')?.trim() || '',
            email: formData.get('contact2Email')?.trim() || ''
        });
    }
    
    if (formData.get('contact3Name')?.trim()) {
        contacts.push({
            name: formData.get('contact3Name').trim(),
            phone: formData.get('contact3Phone')?.trim() || '',
            email: formData.get('contact3Email')?.trim() || ''
        });
    }
    
    const data = {
        name: formData.get('name'),
        address: formData.get('address'),
        contacts: contacts
    };
    
    console.log('Creating property:', data);
    console.log('POST to:', `${API_BASE}/properties-create`);
    
    try {
        const response = await fetch(`${API_BASE}/properties-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        console.log('Property save response status:', response.status);
        const responseData = await response.json();
        console.log('Property save response:', responseData);
        
        if (response.ok) {
            showToast('Property created successfully!', 'success');
            closeModalByName('property');
            await loadProperties();
            loadPropertiesSection();
        } else {
            console.error('Property creation failed:', responseData);
            showToast(responseData.error || 'Error saving property', 'error');
        }
    } catch (error) {
        console.error('Property save network error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function handleUpdateProperty(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const contacts = [];
    
    if (formData.get('contact1Name')?.trim()) {
        contacts.push({
            name: formData.get('contact1Name').trim(),
            phone: formData.get('contact1Phone')?.trim() || '',
            email: formData.get('contact1Email')?.trim() || ''
        });
    }
    
    if (formData.get('contact2Name')?.trim()) {
        contacts.push({
            name: formData.get('contact2Name').trim(),
            phone: formData.get('contact2Phone')?.trim() || '',
            email: formData.get('contact2Email')?.trim() || ''
        });
    }
    
    if (formData.get('contact3Name')?.trim()) {
        contacts.push({
            name: formData.get('contact3Name').trim(),
            phone: formData.get('contact3Phone')?.trim() || '',
            email: formData.get('contact3Email')?.trim() || ''
        });
    }
    
    const data = {
        id: document.getElementById('propertyId').value,
        name: formData.get('name'),
        address: formData.get('address'),
        contacts: contacts
    };
    
    console.log('Updating property:', data);
    console.log('POST to:', `${API_BASE}/properties-update`);
    
    try {
        const response = await fetch(`${API_BASE}/properties-update`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        console.log('Property update response status:', response.status);
        const responseData = await response.json();
        console.log('Property update response:', responseData);
        
        if (response.ok) {
            showToast('Property updated successfully!', 'success');
            closeModalByName('property');
            await loadProperties();
            loadPropertiesSection();
        } else {
            console.error('Property update failed:', responseData);
            showToast(responseData.error || 'Error updating property', 'error');
        }
    } catch (error) {
        console.error('Property update network error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteProperty(id, name) {
    if (!confirm(`Delete property "${name}"?\n\nThis will fail if any vehicles are assigned to this property.`)) {
        return;
    }
    
    console.log('Deleting property:', id);
    
    try {
        const response = await fetch(`${API_BASE}/properties-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        const responseData = await response.json();
        console.log('Delete property response:', responseData);
        
        if (response.ok) {
            showToast('Property deleted successfully!', 'success');
            await loadProperties();
            loadPropertiesSection();
        } else {
            showToast(responseData.error || 'Error deleting property', 'error');
        }
    } catch (error) {
        console.error('Property delete error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Users
async function loadUsersSection() {
    document.getElementById('addUserBtn').onclick = () => openUserModal();
    
    // Setup search functionality
    document.getElementById('userShowAllBtn').onclick = () => showAllUsers();
    document.getElementById('userSearchBtn').onclick = () => filterUsers();
    document.getElementById('userClearSearchBtn').onclick = () => clearUserSearch();
    document.getElementById('userSearchInput').onkeypress = (e) => {
        if (e.key === 'Enter') filterUsers();
    };
    
    // Show initial message
    const container = document.getElementById('usersResults');
    container.innerHTML = '<div class="no-results">Click "Show All" to display users or use the search bar to find specific users.</div>';
}

async function showAllUsers() {
    console.log('=== SHOW ALL USERS DEBUG ===');
    const container = document.getElementById('usersResults');
    container.innerHTML = '<div class="no-results">Loading users...</div>';
    
    // DEMO MODE: Show sample users without database
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    console.log('Demo mode:', isDemo, 'Hostname:', window.location.hostname);
    
    if (isDemo) {
        const demoUsers = [
            { id: 1, username: 'admin', email: 'admin@example.com', role: 'Admin', created_at: '2024-01-01' },
            { id: 2, username: 'manager', email: 'manager@example.com', role: 'User', created_at: '2024-02-15' },
            { id: 3, username: 'viewer', email: 'viewer@example.com', role: 'Operator', created_at: '2024-03-20' }
        ];
        allUsers = demoUsers;
        console.log('Demo users loaded:', allUsers.length, 'users');
        displayUsersTable(demoUsers);
        return;
    }
    
    // PRODUCTION MODE: Fetch from API
    console.log('Production mode: Fetching from API:', `${API_BASE}/users-list`);
    try {
        const response = await fetch(`${API_BASE}/users-list`, {
            credentials: 'include'
        });
        
        console.log('API Response status:', response.status, 'OK:', response.ok);
        
        if (response.ok) {
            const data = await response.json();
            console.log('API Response data:', data);
            allUsers = data.users;
            console.log('Users loaded into allUsers:', allUsers.length, 'users');
            console.log('First user:', allUsers[0]);
            displayUsersTable(allUsers);
        } else {
            const errorText = await response.text();
            console.error('API Error Response:', response.status, errorText);
            container.innerHTML = '<div class="no-results">Failed to load users (Status: ' + response.status + '). Check console for details.</div>';
        }
    } catch (error) {
        console.error('Network error loading users:', error);
        container.innerHTML = '<div class="no-results">Network error: ' + error.message + '</div>';
    }
}

async function filterUsers() {
    console.log('=== FILTER USERS DEBUG ===');
    const searchQuery = document.getElementById('userSearchInput').value.toLowerCase().trim();
    console.log('Search query:', searchQuery);
    console.log('allUsers state - exists:', !!allUsers, 'length:', allUsers ? allUsers.length : 0);
    
    // If no users loaded yet, load them first
    if (!allUsers || allUsers.length === 0) {
        console.log('No users loaded, calling showAllUsers()...');
        await showAllUsers();
        console.log('After showAllUsers - allUsers length:', allUsers ? allUsers.length : 0);
    }
    
    // If no search query after loading, show all users
    if (!searchQuery) {
        console.log('No search query, showing all users');
        if (allUsers && allUsers.length > 0) {
            console.log('Displaying all', allUsers.length, 'users');
            displayUsersTable(allUsers);
        } else {
            console.log('No users to display');
        }
        return;
    }
    
    // Filter users based on search query
    console.log('Filtering users with query:', searchQuery);
    const filtered = allUsers.filter(user => {
        const matchUsername = user.username.toLowerCase().includes(searchQuery);
        const matchEmail = user.email && user.email.toLowerCase().includes(searchQuery);
        const matchRole = user.role.toLowerCase().includes(searchQuery);
        const matchFullName = user.full_name && user.full_name.toLowerCase().includes(searchQuery);
        const matches = matchUsername || matchEmail || matchRole || matchFullName;
        
        if (matches) {
            console.log('Match found:', user.username, '- username:', matchUsername, 'email:', matchEmail, 'role:', matchRole, 'fullname:', matchFullName);
        }
        
        return matches;
    });
    
    console.log('Filtered results:', filtered.length, 'out of', allUsers.length, 'users');
    displayUsersTable(filtered);
}

function clearUserSearch() {
    console.log('=== CLEAR USER SEARCH DEBUG ===');
    document.getElementById('userSearchInput').value = '';
    console.log('Search input cleared');
    console.log('Previous allUsers state:', allUsers ? allUsers.length + ' users' : 'null/undefined');
    
    // Clear the loaded users and hide results
    allUsers = [];
    console.log('allUsers array cleared, results hidden');
    
    const container = document.getElementById('usersResults');
    container.innerHTML = '<div class="no-results">Click "Show All" to display users or use the search bar to find specific users.</div>';
}

function displayUsersTable(users) {
    console.log('=== DISPLAY USERS TABLE DEBUG ===');
    console.log('Displaying', users.length, 'users');
    const container = document.getElementById('usersResults');
    
    if (users.length === 0) {
        console.log('No users to display');
        container.innerHTML = '<div class="no-results">No users found</div>';
        return;
    }
    
    console.log('Sample user data:', users[0]);
    
    const table = `
        <div class="data-table">
            <div style="background: #2a2a2a; padding: 10px; margin-bottom: 10px; font-size: 12px; color: #4a90e2;">
                <strong>DEBUG INFO:</strong> Displaying ${users.length} user(s) | 
                Total loaded: ${allUsers ? allUsers.length : 0} | 
                <span id="debugTimestamp">${new Date().toLocaleTimeString()}</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${users.map(user => `
                        <tr>
                            <td>${escapeHtml(user.username)}</td>
                            <td>${escapeHtml(user.email || 'N/A')}</td>
                            <td><span class="role-badge role-${user.role.toLowerCase()}">${user.role}</span></td>
                            <td>${formatDate(user.created_at)}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-small btn-secondary" onclick="editUser('${user.id}')">Edit</button>
                                    ${user.id !== currentUser.id ? `<button class="btn btn-small btn-danger" onclick="deleteUser('${user.id}', '${escapeHtml(user.username)}')">Delete</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = table;
    console.log('Table HTML rendered successfully');
}

// Permission Matrix Functions
function setPermissionPreset(preset) {
    const modules = ['vehicles', 'users', 'properties', 'violations'];
    const checkboxes = document.querySelectorAll('.perm-check');
    
    checkboxes.forEach(cb => {
        const module = cb.dataset.module;
        const action = cb.dataset.action;
        
        if (preset === 'admin') {
            cb.checked = true;
        } else if (preset === 'view') {
            cb.checked = action === 'view';
        } else {
            cb.checked = false;
        }
    });
}

function setupPermissionDependencies() {
    const checkboxes = document.querySelectorAll('.perm-check');
    checkboxes.forEach(cb => {
        cb.addEventListener('change', handlePermissionChange);
    });
}

function handlePermissionChange(e) {
    const checkbox = e.target;
    const module = checkbox.dataset.module;
    const action = checkbox.dataset.action;
    
    const viewCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="view"]`);
    const editCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="edit"]`);
    const createCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="create_delete"]`);
    
    if (checkbox.checked) {
        if (action === 'create_delete') {
            editCb.checked = true;
            viewCb.checked = true;
        } else if (action === 'edit') {
            viewCb.checked = true;
        }
    } else {
        if (action === 'view') {
            editCb.checked = false;
            createCb.checked = false;
        } else if (action === 'edit') {
            createCb.checked = false;
        }
    }
}

function loadUserPermissions(user) {
    const checkboxes = document.querySelectorAll('.perm-check');
    checkboxes.forEach(cb => cb.checked = false);
    
    if (user && user.permissions) {
        Object.keys(user.permissions).forEach(module => {
            const perms = user.permissions[module];
            const viewCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="view"]`);
            const editCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="edit"]`);
            const createCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="create_delete"]`);
            
            if (viewCb) viewCb.checked = perms.can_view;
            if (editCb) editCb.checked = perms.can_edit;
            if (createCb) createCb.checked = perms.can_create_delete;
        });
    } else if (user) {
        const role = (user.role || '').toLowerCase();
        if (role === 'admin') {
            setPermissionPreset('admin');
        } else {
            setPermissionPreset('view');
        }
    }
}

function getSelectedPermissions() {
    const permissions = {};
    const modules = ['vehicles', 'users', 'properties', 'violations'];
    
    modules.forEach(module => {
        const viewCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="view"]`);
        const editCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="edit"]`);
        const createCb = document.querySelector(`.perm-check[data-module="${module}"][data-action="create_delete"]`);
        
        permissions[module] = {
            can_view: viewCb?.checked || false,
            can_edit: editCb?.checked || false,
            can_create_delete: createCb?.checked || false
        };
    });
    
    return permissions;
}

function openUserModal(user = null) {
    if (user) {
        document.getElementById('userModalTitle').textContent = 'Edit User';
        document.getElementById('userId').value = user.id;
        document.getElementById('userUsername').value = user.username;
        document.getElementById('userEmail').value = user.email || '';
        document.getElementById('userRole').value = user.role.toLowerCase();
        document.getElementById('userPassword').value = '';
        document.getElementById('userPassword').placeholder = 'Leave blank to keep current password';
        document.getElementById('userPassword').required = false;
        loadUserPermissions(user);
    } else {
        document.getElementById('userModalTitle').textContent = 'Add User';
        document.getElementById('userForm').reset();
        document.getElementById('userId').value = '';
        document.getElementById('userPassword').placeholder = '';
        document.getElementById('userPassword').required = true;
        setPermissionPreset('view');
    }
    
    setupPermissionDependencies();
    document.getElementById('userModal').classList.add('show');
    document.getElementById('userForm').onsubmit = handleSaveUser;
}

async function editUser(userId) {
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    
    if (isDemo) {
        const demoUsers = [
            { id: 1, username: 'admin', email: 'admin@example.com', role: 'Admin', created_at: '2024-01-01' },
            { id: 2, username: 'manager', email: 'manager@example.com', role: 'User', created_at: '2024-02-15' },
            { id: 3, username: 'viewer', email: 'viewer@example.com', role: 'Operator', created_at: '2024-03-20' }
        ];
        const user = demoUsers.find(u => u.id == userId);
        if (user) {
            openUserModal(user);
        }
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users-list`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            const user = data.users.find(u => u.id === userId);
            if (user) {
                openUserModal(user);
            } else {
                showToast('User not found', 'error');
            }
        }
    } catch (error) {
        console.error('Error loading user:', error);
        showToast('Error loading user', 'error');
    }
}

async function handleSaveUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userId = formData.get('id');
    const isEdit = !!userId;
    
    const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role'),
        permissions: getSelectedPermissions()
    };
    
    if (isEdit) {
        data.id = userId;
    }
    
    const endpoint = isEdit ? `${API_BASE}/users-update` : `${API_BASE}/users-create`;
    
    console.log(isEdit ? 'Updating user:' : 'Creating user:', {username: data.username, email: data.email, role: data.role, permissions: data.permissions});
    console.log('POST to:', endpoint);
    
    try {
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        console.log('User save response status:', response.status);
        const responseData = await response.json();
        console.log('User save response:', responseData);
        
        if (response.ok) {
            showToast(isEdit ? 'User updated successfully!' : 'User created successfully!', 'success');
            closeModalByName('user');
            loadUsersSection();
        } else {
            console.error('User save failed:', responseData);
            showToast(responseData.error || 'Error saving user', 'error');
        }
    } catch (error) {
        console.error('User save network error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteUser(userId, username) {
    if (!confirm(`Delete user "${username}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id: userId })
        });
        
        if (response.ok) {
            loadUsersSection();
        } else {
            const error = await response.json();
            showToast(error.error || 'Error deleting user', 'error');
        }
    } catch (error) {
        showToast('Network error. Please try again.', 'error');
    }
}

// Vehicles
function loadVehiclesSection() {
    document.getElementById('searchBtn').onclick = searchVehicles;
    document.getElementById('clearSearchBtn').onclick = clearSearch;
    document.getElementById('searchInput').onkeypress = (e) => {
        if (e.key === 'Enter') searchVehicles();
    };
    document.getElementById('propertyFilter').onchange = searchVehicles;
    document.getElementById('addVehicleBtn').onclick = () => openVehicleModal();
    document.getElementById('importBtn').onclick = importVehicles;
    document.getElementById('exportBtn').onclick = exportVehicles;
    
    // Show empty state initially (no auto-load)
    showEmptyVehiclesState();
}

function showEmptyVehiclesState() {
    const resultsDiv = document.getElementById('vehiclesResults');
    resultsDiv.innerHTML = '<p style="text-align: center; padding: 40px; color: #888;">Use the search box above to find vehicles.</p>';
}

function clearSearch() {
    document.getElementById('searchInput').value = '';
    document.getElementById('propertyFilter').value = '';
    showEmptyVehiclesState();
}

async function searchVehicles() {
    const query = document.getElementById('searchInput').value;
    const property = document.getElementById('propertyFilter').value;
    
    // DEMO MODE: Show sample vehicles without database
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    if (isDemo) {
        const demoVehicles = [
            {
                id: 1,
                property: 'Sunset Apartments',
                tag_number: 'P12345',
                plate_number: 'ABC-1234',
                state: 'CA',
                make: 'Toyota',
                model: 'Camry',
                color: 'Silver',
                year: '2020',
                apt_number: '101',
                reserved_space: 'A-15',
                owner_name: 'John Smith',
                owner_phone: '555-1234',
                owner_email: 'john@example.com',
                violation_count: 3
            },
            {
                id: 2,
                property: 'Oak Ridge Condos',
                tag_number: 'P67890',
                plate_number: 'XYZ-5678',
                state: 'CA',
                make: 'Honda',
                model: 'Civic',
                color: 'Blue',
                year: '2019',
                apt_number: '205',
                reserved_space: 'B-22',
                owner_name: 'Jane Doe',
                owner_phone: '555-5678',
                owner_email: 'jane@example.com',
                violation_count: 0
            },
            {
                id: 3,
                property: 'Maple View Townhomes',
                tag_number: 'P11223',
                plate_number: 'DEF-9012',
                state: 'CA',
                make: 'Ford',
                model: 'F-150',
                color: 'Black',
                year: '2021',
                apt_number: '12',
                reserved_space: 'C-5',
                owner_name: 'Bob Wilson',
                owner_phone: '555-9012',
                owner_email: 'bob@example.com',
                violation_count: 1
            }
        ];
        
        // Filter demo vehicles by search query and property
        let filtered = demoVehicles;
        if (property) {
            filtered = filtered.filter(v => v.property === property);
        }
        if (query) {
            const q = query.toLowerCase();
            filtered = filtered.filter(v =>
                v.tag_number?.toLowerCase().includes(q) ||
                v.plate_number?.toLowerCase().includes(q) ||
                v.owner_name?.toLowerCase().includes(q) ||
                v.make?.toLowerCase().includes(q) ||
                v.model?.toLowerCase().includes(q)
            );
        }
        
        displayVehicles(filtered);
        return;
    }
    
    // PRODUCTION MODE: Fetch from API
    const params = new URLSearchParams();
    if (query) params.append('q', query);
    if (property) params.append('property', property);
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-search?${params}`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayVehicles(data.vehicles);
        } else {
            document.getElementById('vehiclesResults').innerHTML = '<div class="no-results">Error loading vehicles</div>';
        }
    } catch (error) {
        document.getElementById('vehiclesResults').innerHTML = '<div class="no-results">Network error</div>';
    }
}

function displayVehicles(vehicles) {
    const container = document.getElementById('vehiclesResults');
    
    if (vehicles.length === 0) {
        container.innerHTML = '<div class="no-results">No vehicles found</div>';
        return;
    }
    
    const grid = document.createElement('div');
    grid.className = 'vehicle-grid';
    
    vehicles.forEach(vehicle => {
        const card = createVehicleCard(vehicle);
        grid.appendChild(card);
    });
    
    container.innerHTML = '';
    container.appendChild(grid);
}

function createVehicleCard(vehicle) {
    const card = document.createElement('div');
    card.className = 'vehicle-card';
    
    const title = vehicle.plate_number || vehicle.tag_number || 'No Plate/Tag';
    
    // Get violation count (default to 0 if not present)
    const violationCount = parseInt(vehicle.violation_count) || 0;
    const violationsIndicator = violationCount > 0 ? `
        <button class="btn btn-small btn-violations" onclick='showViolationHistory("${vehicle.id}", event)'>
            *Violations Exist (${violationCount})
        </button>
    ` : '';
    
    const actionButtons = canEditVehicles() ? `
        <div class="vehicle-actions">
            <button class="btn btn-small btn-warning" onclick='openViolationModal(${JSON.stringify(vehicle)})'>Violation</button>
            <button class="btn btn-small btn-primary" onclick='editVehicle(${JSON.stringify(vehicle)})'>Edit</button>
            ${canDeleteVehicles() ? `<button class="btn btn-small btn-danger" onclick="deleteVehicle('${vehicle.id}', '${escapeHtml(title)}')">Delete</button>` : ''}
        </div>
    ` : '';
    
    card.innerHTML = `
        <div class="vehicle-header">
            <div class="vehicle-title">${escapeHtml(title)}</div>
            ${violationsIndicator}
            <div class="property-badge">${escapeHtml(vehicle.property)}</div>
        </div>
        <div class="vehicle-details">
            ${createDetailRow('Tag Number', vehicle.tag_number)}
            ${createDetailRow('Plate Number', vehicle.plate_number)}
            ${createDetailRow('State', vehicle.state)}
            ${createDetailRow('Make/Model', formatMakeModel(vehicle))}
            ${createDetailRow('Color', vehicle.color)}
            ${createDetailRow('Year', vehicle.year)}
            ${createDetailRow('Apartment', vehicle.apt_number)}
            ${createDetailRow('Reserved Space', vehicle.reserved_space)}
            ${createDetailRow('Owner', vehicle.owner_name)}
            ${createDetailRow('Phone', vehicle.owner_phone)}
            ${createDetailRow('Email', vehicle.owner_email)}
        </div>
        ${actionButtons}
    `;
    
    return card;
}

function createDetailRow(label, value) {
    if (!value) return '';
    return `
        <div class="detail-row">
            <div class="detail-label">${label}:</div>
            <div class="detail-value">${escapeHtml(value)}</div>
        </div>
    `;
}

function formatMakeModel(vehicle) {
    const parts = [];
    if (vehicle.make) parts.push(vehicle.make);
    if (vehicle.model) parts.push(vehicle.model);
    return parts.join(' ') || null;
}

function openVehicleModal(vehicle = null) {
    document.getElementById('vehicleModalTitle').textContent = vehicle ? 'Edit Vehicle' : 'Add Vehicle';
    document.getElementById('vehicleForm').reset();
    
    if (vehicle) {
        document.getElementById('vehicleId').value = vehicle.id || '';
        document.getElementById('vehicleProperty').value = vehicle.property || '';
        document.getElementById('vehicleTag').value = vehicle.tag_number || '';
        document.getElementById('vehiclePlate').value = vehicle.plate_number || '';
        document.getElementById('vehicleState').value = vehicle.state || '';
        document.getElementById('vehicleMake').value = vehicle.make || '';
        document.getElementById('vehicleModel').value = vehicle.model || '';
        document.getElementById('vehicleColor').value = vehicle.color || '';
        document.getElementById('vehicleYear').value = vehicle.year || '';
        document.getElementById('vehicleApt').value = vehicle.apt_number || '';
        document.getElementById('vehicleSpace').value = vehicle.reserved_space || '';
        document.getElementById('vehicleOwner').value = vehicle.owner_name || '';
        document.getElementById('vehiclePhone').value = vehicle.owner_phone || '';
        document.getElementById('vehicleEmail').value = vehicle.owner_email || '';
    } else {
        document.getElementById('vehicleId').value = '';
    }
    
    document.getElementById('vehicleModal').classList.add('show');
    document.getElementById('vehicleForm').onsubmit = handleSaveVehicle;
}

function editVehicle(vehicle) {
    openVehicleModal(vehicle);
}

async function handleSaveVehicle(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    const isEdit = !!data.id;
    console.log(isEdit ? 'Updating vehicle:' : 'Creating vehicle:', data);
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        console.log('Vehicle save response status:', response.status);
        const responseData = await response.json();
        console.log('Vehicle save response:', responseData);
        
        if (response.ok) {
            showToast(isEdit ? 'Vehicle updated successfully!' : 'Vehicle created successfully!', 'success');
            closeModalByName('vehicle');
            searchVehicles();
        } else {
            console.error('Vehicle save failed:', responseData);
            showToast(responseData.error || 'Error saving vehicle', 'error');
        }
    } catch (error) {
        console.error('Vehicle save network error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteVehicle(id, title) {
    if (!confirm(`Delete vehicle "${title}"?`)) {
        return;
    }
    
    console.log('Deleting vehicle:', id);
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        const responseData = await response.json();
        console.log('Delete vehicle response:', responseData);
        
        if (response.ok) {
            showToast('Vehicle deleted successfully!', 'success');
            searchVehicles();
        } else {
            showToast(responseData.error || 'Error deleting vehicle', 'error');
        }
    } catch (error) {
        console.error('Vehicle delete error:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

function importVehicles() {
    const fileInput = document.getElementById('importFileInput');
    fileInput.click();
    
    fileInput.onchange = async (e) => {
        const file = e.target.files[0];
        if (!file) return;
        
        const formData = new FormData();
        formData.append('csv', file);
        
        try {
            const response = await fetch(`${API_BASE}/vehicles-import`, {
                method: 'POST',
                credentials: 'include',
                body: formData
            });
            
            const data = await response.json();
            
            if (response.ok && data.success) {
                showToast(data.message + (data.errors.length > 0 ? '\n\nErrors:\n' + data.errors.join('\n') : ''), 'success');
                searchVehicles(); // Refresh the list
            } else {
                showToast('Import failed: ' + (data.error || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Import error:', error);
            showToast('Import failed. Please try again.', 'error');
        }
        
        // Reset file input
        fileInput.value = '';
    };
}

function exportVehicles() {
    console.log('Export CSV clicked - navigating to:', `${API_BASE}/vehicles-export`);
    // Use window.open with _self to force navigation while keeping session
    window.open(`${API_BASE}/vehicles-export`, '_self');
}

// ============================================
// VIOLATION MANAGEMENT
// ============================================

let currentVehicleForViolation = null;
let availableViolations = [];

async function openViolationModal(vehicle) {
    currentVehicleForViolation = vehicle;
    console.log('Opening violation modal for vehicle:', vehicle);
    
    // Set vehicle info in modal
    const vehicleInfo = `${vehicle.year || ''} ${vehicle.color || ''} ${vehicle.make || ''} ${vehicle.model || ''}`.trim();
    document.getElementById('violationVehicleInfo').textContent = vehicleInfo || 'Unknown Vehicle';
    document.getElementById('violationVehicleId').value = vehicle.id;
    
    // Load violations
    await loadViolations();
    
    // Reset form
    document.getElementById('violationForm').reset();
    document.getElementById('violationVehicleId').value = vehicle.id;
    document.getElementById('customNoteContainer').style.display = 'none';
    
    // Show modal
    document.getElementById('violationModal').classList.add('show');
}

async function loadViolations() {
    try {
        const response = await fetch(`${API_BASE}/violations`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            availableViolations = data.violations;
            renderViolationCheckboxes();
        } else {
            console.error('Failed to load violations');
            showToast('Failed to load violation options', 'error');
        }
    } catch (error) {
        console.error('Error loading violations:', error);
        showToast('Error loading violation options', 'error');
    }
}

function renderViolationCheckboxes() {
    const container = document.getElementById('violationCheckboxes');
    container.innerHTML = '';
    
    availableViolations.forEach(violation => {
        const div = document.createElement('div');
        div.className = 'checkbox-item';
        div.innerHTML = `
            <label>
                <input type="checkbox" name="violations" value="${violation.id}">
                ${escapeHtml(violation.name)}
            </label>
        `;
        container.appendChild(div);
    });
    
    // Add "Other" option
    const otherDiv = document.createElement('div');
    otherDiv.className = 'checkbox-item';
    otherDiv.innerHTML = `
        <label>
            <input type="checkbox" id="otherViolationCheckbox" onchange="toggleCustomNote()">
            Other (specify below)
        </label>
    `;
    container.appendChild(otherDiv);
}

function toggleCustomNote() {
    const checkbox = document.getElementById('otherViolationCheckbox');
    const container = document.getElementById('customNoteContainer');
    container.style.display = checkbox.checked ? 'block' : 'none';
    
    if (!checkbox.checked) {
        document.getElementById('customNoteText').value = '';
    }
}

async function handleCreateViolation(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const selectedViolations = Array.from(formData.getAll('violations'));
    const customNote = formData.get('customNote') || '';
    
    // Validate at least one violation
    if (selectedViolations.length === 0 && !customNote.trim()) {
        showToast('Please select at least one violation or enter a custom note', 'warning');
        return;
    }
    
    // Build violation summary for confirmation
    const violationList = selectedViolations.map(id => {
        const v = availableViolations.find(violation => violation.id === id);
        return v ? v.name : '';
    }).filter(name => name);
    
    if (customNote.trim()) {
        violationList.push(`Other: ${customNote.trim()}`);
    }
    
    const vehicleInfo = `${currentVehicleForViolation.year || ''} ${currentVehicleForViolation.color || ''} ${currentVehicleForViolation.make || ''} ${currentVehicleForViolation.model || ''}`.trim();
    
    const confirmMessage = `Are you sure you want to create a violation ticket for:\n\n${vehicleInfo}\n\nViolations:\n${violationList.map((v, i) => `${i + 1}. ${v}`).join('\n')}`;
    
    if (!confirm(confirmMessage)) {
        return;
    }
    
    console.log('Creating violation ticket:', {
        vehicleId: currentVehicleForViolation.id,
        violations: selectedViolations,
        customNote
    });
    
    try {
        const response = await fetch(`${API_BASE}/violations-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                vehicleId: currentVehicleForViolation.id,
                violations: selectedViolations,
                customNote: customNote.trim()
            })
        });
        
        const data = await response.json();
        console.log('Violation creation response:', data);
        
        if (response.ok && data.success) {
            closeModalByName('violation');
            showToast('Violation ticket created successfully!', 'success', false);
            
            // Open print window
            printViolationTicket(data.ticketId);
        } else {
            showToast(data.error || 'Failed to create violation ticket', 'error');
        }
    } catch (error) {
        console.error('Error creating violation:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

function printViolationTicket(ticketId) {
    // Open ticket in new window for printing
    const printUrl = `violations-print.html?id=${ticketId}`;
    window.open(printUrl, '_blank', 'width=600,height=800');
}

// Violations Management (Admin Only)
function loadViolationsManagementSection() {
    document.getElementById('addViolationBtn').onclick = () => openViolationTypeModal();
    document.getElementById('violationTypeForm').onsubmit = handleViolationTypeSave;
    loadViolationTypesList();
}

async function loadViolationTypesList() {
    try {
        const response = await fetch(`${API_BASE}/violations-list`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to load violations');
        }
        
        const data = await response.json();
        displayViolationTypesList(data.violations || []);
    } catch (error) {
        console.error('Error loading violations:', error);
        document.getElementById('violationsResults').innerHTML = 
            '<p style="color: red;">Error loading violations. Please try again.</p>';
    }
}

function displayViolationTypesList(violations) {
    const resultsDiv = document.getElementById('violationsResults');
    
    if (violations.length === 0) {
        resultsDiv.innerHTML = '<p style="text-align: center; padding: 40px; color: #888;">No violations found. Click "Add Violation" to create one.</p>';
        return;
    }
    
    let html = '<div class="table-container"><table class="data-table">';
    html += '<thead><tr><th>Name</th><th>Display Order</th><th>Status</th><th>Actions</th></tr></thead>';
    html += '<tbody>';
    
    violations.forEach(violation => {
        const statusBadge = violation.is_active 
            ? '<span class="badge badge-success">Active</span>' 
            : '<span class="badge badge-inactive">Inactive</span>';
        
        html += `<tr>
            <td>${escapeHtml(violation.name)}</td>
            <td>${violation.display_order}</td>
            <td>${statusBadge}</td>
            <td class="actions">
                <button class="btn btn-small btn-secondary" onclick="editViolationType('${violation.id}')">Edit</button>
                <button class="btn btn-small btn-danger" onclick="deleteViolationType('${violation.id}', '${escapeHtml(violation.name)}')">Delete</button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    resultsDiv.innerHTML = html;
}

function openViolationTypeModal(violationData = null) {
    const modal = document.getElementById('violationTypeModal');
    const form = document.getElementById('violationTypeForm');
    const title = document.getElementById('violationTypeModalTitle');
    
    form.reset();
    
    if (violationData) {
        title.textContent = 'Edit Violation';
        document.getElementById('violationTypeId').value = violationData.id;
        document.getElementById('violationTypeName').value = violationData.name;
        document.getElementById('violationTypeDisplayOrder').value = violationData.display_order;
        document.getElementById('violationTypeIsActive').checked = violationData.is_active;
    } else {
        title.textContent = 'Add Violation';
        document.getElementById('violationTypeId').value = '';
    }
    
    modal.classList.add('show');
}

async function editViolationType(id) {
    try {
        const response = await fetch(`${API_BASE}/violations-list`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to load violation');
        }
        
        const data = await response.json();
        const violation = data.violations.find(v => v.id === id);
        
        if (violation) {
            openViolationTypeModal(violation);
        } else {
            showToast('Violation not found', 'error');
        }
    } catch (error) {
        console.error('Error loading violation:', error);
        showToast('Error loading violation. Please try again.', 'error');
    }
}

async function handleViolationTypeSave(e) {
    e.preventDefault();
    
    const id = document.getElementById('violationTypeId').value;
    const name = document.getElementById('violationTypeName').value.trim();
    const displayOrder = parseInt(document.getElementById('violationTypeDisplayOrder').value) || 0;
    const isActive = document.getElementById('violationTypeIsActive').checked;
    
    if (!name) {
        showToast('Violation name is required', 'warning');
        return;
    }
    
    try {
        const endpoint = id ? '/violations-update' : '/violations-add';
        const payload = { name, display_order: displayOrder, is_active: isActive };
        if (id) payload.id = id;
        
        const response = await fetch(`${API_BASE}${endpoint}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(payload)
        });
        
        if (response.ok) {
            closeModalByName('violationType');
            loadViolationTypesList();
            showToast(id ? 'Violation updated successfully!' : 'Violation added successfully!', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || 'Error saving violation', 'error');
        }
    } catch (error) {
        console.error('Error saving violation:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

async function deleteViolationType(id, name) {
    if (!confirm(`Are you sure you want to delete "${name}"?\n\nThis will remove it from the violation options list. Existing tickets will not be affected.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/violations-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            loadViolationTypesList();
            showToast('Violation deleted successfully!', 'success');
        } else {
            const error = await response.json();
            showToast(error.error || 'Error deleting violation', 'error');
        }
    } catch (error) {
        console.error('Error deleting violation:', error);
        showToast('Network error. Please try again.', 'error');
    }
}

// Violation History with Pagination
let currentViolationPage = 1;
let allViolationTickets = [];
const VIOLATIONS_PER_PAGE = 5;

async function showViolationHistory(vehicleId, event) {
    if (event) event.stopPropagation();
    
    const modal = document.getElementById('violationHistoryModal');
    const content = document.getElementById('violationHistoryContent');
    const pagination = document.getElementById('violationHistoryPagination');
    
    // Reset pagination
    currentViolationPage = 1;
    allViolationTickets = [];
    
    // Show loading state
    content.innerHTML = '<p style="text-align: center; color: #888;">Loading violation history...</p>';
    pagination.innerHTML = '';
    modal.classList.add('show');
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-violations-history?vehicleId=${vehicleId}`, {
            credentials: 'include'
        });
        
        if (!response.ok) {
            throw new Error('Failed to load violation history');
        }
        
        const data = await response.json();
        allViolationTickets = data.tickets || [];
        displayViolationHistory();
    } catch (error) {
        console.error('Error loading violation history:', error);
        content.innerHTML = '<p style="color: red; text-align: center;">Error loading violation history. Please try again.</p>';
        pagination.innerHTML = '';
    }
}

function displayViolationHistory() {
    const content = document.getElementById('violationHistoryContent');
    const pagination = document.getElementById('violationHistoryPagination');
    
    if (!allViolationTickets || allViolationTickets.length === 0) {
        content.innerHTML = '<p style="text-align: center; color: #888;">No violations found for this vehicle.</p>';
        pagination.innerHTML = '';
        return;
    }
    
    // Calculate pagination
    const totalPages = Math.ceil(allViolationTickets.length / VIOLATIONS_PER_PAGE);
    const startIndex = (currentViolationPage - 1) * VIOLATIONS_PER_PAGE;
    const endIndex = startIndex + VIOLATIONS_PER_PAGE;
    const ticketsToShow = allViolationTickets.slice(startIndex, endIndex);
    
    // Display tickets for current page
    let html = '<div class="violation-history-list">';
    
    ticketsToShow.forEach((ticket, index) => {
        const date = new Date(ticket.issued_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        const vehicleInfo = `${ticket.vehicle_year || ''} ${ticket.vehicle_color || ''} ${ticket.vehicle_make || ''} ${ticket.vehicle_model || ''}`.trim();
        const globalIndex = startIndex + index;
        
        html += `
            <div class="violation-history-item">
                <div class="violation-history-header">
                    <strong>Violation #${allViolationTickets.length - globalIndex}</strong>
                    <span class="violation-date">${formattedDate}</span>
                </div>
                <div class="violation-history-details">
                    <p><strong>Vehicle:</strong> ${escapeHtml(vehicleInfo)}</p>
                    <p><strong>Issued by:</strong> ${escapeHtml(ticket.issued_by_username)}</p>
                    <p><strong>Violations:</strong></p>
                    <ul class="violation-list">
                        ${ticket.violations.map(v => `<li>${escapeHtml(v)}</li>`).join('')}
                    </ul>
                    ${ticket.custom_note ? `<p><em>Note: ${escapeHtml(ticket.custom_note)}</em></p>` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    content.innerHTML = html;
    
    // Display pagination controls
    if (totalPages > 1) {
        let paginationHtml = `
            <button 
                class="btn btn-small" 
                onclick="changeViolationPage(${currentViolationPage - 1})" 
                ${currentViolationPage === 1 ? 'disabled' : ''}
                style="${currentViolationPage === 1 ? 'opacity: 0.5; cursor: not-allowed;' : ''}"
            >
                ← Previous
            </button>
            <span style="color: #888; font-size: 14px;">
                Page ${currentViolationPage} of ${totalPages} (${allViolationTickets.length} total violations)
            </span>
            <button 
                class="btn btn-small" 
                onclick="changeViolationPage(${currentViolationPage + 1})" 
                ${currentViolationPage === totalPages ? 'disabled' : ''}
                style="${currentViolationPage === totalPages ? 'opacity: 0.5; cursor: not-allowed;' : ''}"
            >
                Next →
            </button>
        `;
        pagination.innerHTML = paginationHtml;
    } else {
        pagination.innerHTML = `<span style="color: #888; font-size: 14px;">${allViolationTickets.length} violation${allViolationTickets.length === 1 ? '' : 's'} found</span>`;
    }
}

function changeViolationPage(newPage) {
    const totalPages = Math.ceil(allViolationTickets.length / VIOLATIONS_PER_PAGE);
    if (newPage < 1 || newPage > totalPages) return;
    
    currentViolationPage = newPage;
    displayViolationHistory();
}

// Modal Management
function closeModalByName(name) {
    const modal = document.getElementById(`${name}Modal`);
    if (modal) {
        modal.classList.remove('show');
        const form = modal.querySelector('form');
        if (form) form.reset();
    }
}

// Utilities
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString();
}

// ============================================================================
// BULK OPERATIONS (DATABASE TAB)
// ============================================================================

// Populate bulk delete property dropdown when database tab is shown
document.addEventListener('DOMContentLoaded', () => {
    const bulkDeleteProperty = document.getElementById('bulkDeleteProperty');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const findDuplicatesBtn = document.getElementById('findDuplicatesBtn');
    
    if (bulkDeleteBtn) {
        bulkDeleteBtn.addEventListener('click', handleBulkDelete);
    }
    
    if (findDuplicatesBtn) {
        findDuplicatesBtn.addEventListener('click', handleFindDuplicates);
    }
    
    // Populate property dropdown when database tab is opened
    document.querySelector('[data-tab="database"]')?.addEventListener('click', async () => {
        if (bulkDeleteProperty && properties.length > 0) {
            bulkDeleteProperty.innerHTML = '<option value="">Select Property</option>' +
                properties.map(p => `<option value="${escapeHtml(p.name)}">${escapeHtml(p.name)}</option>`).join('');
        }
    });
});

async function handleBulkDelete() {
    const propertySelect = document.getElementById('bulkDeleteProperty');
    const property = propertySelect?.value;
    
    if (!property) {
        showToast('Please select a property', 'error');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ALL vehicles from "${property}"? This action cannot be undone.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-bulk-delete`, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ property })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            propertySelect.value = '';
        } else {
            showToast(data.error || 'Bulk delete failed', 'error');
        }
    } catch (error) {
        console.error('Bulk delete error:', error);
        showToast('Network error during bulk delete', 'error');
    }
}

async function handleFindDuplicates() {
    const criteriaSelect = document.getElementById('duplicateCriteria');
    const resultsDiv = document.getElementById('duplicatesResults');
    const criteria = criteriaSelect?.value || 'plate';
    
    resultsDiv.innerHTML = '<p style="color: #888;">Searching for duplicates...</p>';
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-duplicates`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ action: 'find', criteria })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            if (data.duplicates.length === 0) {
                resultsDiv.innerHTML = '<p style="color: #4ade80;">No duplicates found!</p>';
                return;
            }
            
            let html = `<p style="color: #60a5fa; margin-bottom: 15px;">Found ${data.total_groups} duplicate group(s):</p>`;
            
            data.duplicates.forEach((group, index) => {
                html += `
                    <div class="duplicate-group">
                        <h5>Duplicate ${criteria === 'plate' ? 'Plate' : 'Tag'}: ${escapeHtml(group.value)} (${group.count} vehicles)</h5>
                        ${group.items.map((item, i) => `
                            <div class="duplicate-item">
                                <div class="duplicate-info">
                                    <strong>${escapeHtml(item.vehicle)}</strong><br>
                                    <small>Property: ${escapeHtml(item.property)}</small>
                                </div>
                                <button class="btn btn-danger btn-sm" onclick="deleteDuplicate('${item.id}', ${index})">Delete</button>
                            </div>
                        `).join('')}
                    </div>
                `;
            });
            
            resultsDiv.innerHTML = html;
        } else {
            resultsDiv.innerHTML = `<p style="color: #ef4444;">${data.error || 'Failed to find duplicates'}</p>`;
        }
    } catch (error) {
        console.error('Find duplicates error:', error);
        resultsDiv.innerHTML = '<p style="color: #ef4444;">Network error while searching for duplicates</p>';
    }
}

async function deleteDuplicate(vehicleId, groupIndex) {
    if (!confirm('Are you sure you want to delete this vehicle?')) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-duplicates`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ 
                action: 'delete', 
                criteria: document.getElementById('duplicateCriteria').value,
                vehicle_ids: [vehicleId]
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            showToast(data.message, 'success');
            // Re-run find duplicates to refresh the list
            handleFindDuplicates();
        } else {
            showToast(data.error || 'Delete failed', 'error');
        }
    } catch (error) {
        console.error('Delete duplicate error:', error);
        showToast('Network error during delete', 'error');
    }
}

// ============================================================================
// VIOLATION SEARCH (DATABASE TAB)
// ============================================================================

let violationSearchData = [];

// Initialize violation search when database tab is opened
document.addEventListener('DOMContentLoaded', () => {
    const violationSearchBtn = document.getElementById('violationSearchBtn');
    const violationPrintBtn = document.getElementById('violationPrintBtn');
    const violationExportBtn = document.getElementById('violationExportBtn');
    
    if (violationSearchBtn) {
        violationSearchBtn.addEventListener('click', handleViolationSearch);
    }
    
    if (violationPrintBtn) {
        violationPrintBtn.addEventListener('click', handleViolationPrint);
    }
    
    if (violationExportBtn) {
        violationExportBtn.addEventListener('click', handleViolationExport);
    }
    
    // Populate dropdowns when database tab is opened
    document.querySelector('[data-tab="database"]')?.addEventListener('click', async () => {
        await populateViolationSearchFilters();
    });
});

async function populateViolationSearchFilters() {
    const propertyFilter = document.getElementById('violationPropertyFilter');
    const violationTypeFilter = document.getElementById('violationTypeFilter');
    
    // Populate property dropdown
    if (propertyFilter && properties.length > 0) {
        const currentValue = propertyFilter.value;
        propertyFilter.innerHTML = '<option value="">All Properties</option>' +
            properties.map(p => `<option value="${escapeHtml(p.name)}">${escapeHtml(p.name)}</option>`).join('');
        if (currentValue) propertyFilter.value = currentValue;
    }
    
    // Populate violation type dropdown
    if (violationTypeFilter) {
        try {
            const response = await fetch(`${API_BASE}/violations-list`, {
                credentials: 'include'
            });
            
            if (response.ok) {
                const data = await response.json();
                const activeViolations = data.violations.filter(v => v.is_active);
                const currentValue = violationTypeFilter.value;
                
                violationTypeFilter.innerHTML = '<option value="">All Violations</option>' +
                    activeViolations.map(v => 
                        `<option value="${escapeHtml(v.violation_name)}">${escapeHtml(v.violation_name)}</option>`
                    ).join('');
                
                if (currentValue) violationTypeFilter.value = currentValue;
            }
        } catch (error) {
            console.error('Error loading violation types:', error);
        }
    }
}

async function handleViolationSearch() {
    const startDate = document.getElementById('violationStartDate').value;
    const endDate = document.getElementById('violationEndDate').value;
    const property = document.getElementById('violationPropertyFilter').value;
    const violationType = document.getElementById('violationTypeFilter').value;
    const query = document.getElementById('violationSearchQuery').value;
    const resultsDiv = document.getElementById('violationSearchResults');
    const actionsDiv = document.querySelector('.search-actions');
    
    resultsDiv.innerHTML = '<p style="color: #888;">Searching violations...</p>';
    actionsDiv.style.display = 'none';
    
    try {
        const response = await fetch(`${API_BASE}/violations-search`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                property: property,
                violation_type: violationType,
                query: query
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            violationSearchData = data.violations;
            displayViolationResults(data.violations, data.total, data.limit_reached);
            
            if (data.violations.length > 0) {
                actionsDiv.style.display = 'flex';
                const countSpan = document.getElementById('violationResultsCount');
                countSpan.textContent = `${data.total} violation${data.total !== 1 ? 's' : ''} found` +
                    (data.limit_reached ? ' (limit reached)' : '');
            }
        } else {
            resultsDiv.innerHTML = `<p style="color: #ef4444;">${data.error || 'Search failed'}</p>`;
            showToast(data.error || 'Search failed', 'error');
        }
    } catch (error) {
        console.error('Violation search error:', error);
        resultsDiv.innerHTML = '<p style="color: #ef4444;">Network error during search</p>';
        showToast('Network error during search', 'error');
    }
}

function displayViolationResults(violations, total, limitReached) {
    const resultsDiv = document.getElementById('violationSearchResults');
    
    if (violations.length === 0) {
        resultsDiv.innerHTML = '<p style="color: #94a3b8;">No violations found matching your search criteria.</p>';
        return;
    }
    
    let html = `
        <table class="violation-results-table">
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Property</th>
                    <th>Vehicle</th>
                    <th>Plate/Tag</th>
                    <th>Violations</th>
                    <th>Notes</th>
                    <th>Issued By</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    violations.forEach(violation => {
        const vehicle = `${violation.year || ''} ${violation.make || ''} ${violation.model || ''} ${violation.color || ''}`.trim();
        const plateTag = [violation.plate_number, violation.tag_number].filter(Boolean).join(' / ') || 'N/A';
        const violationTypes = violation.violation_types_array.join(', ') || 'N/A';
        const date = new Date(violation.created_at);
        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
        
        html += `
            <tr>
                <td>${formattedDate}</td>
                <td>${escapeHtml(violation.property)}</td>
                <td>${escapeHtml(vehicle)}</td>
                <td>${escapeHtml(plateTag)}</td>
                <td class="violation-types-cell">${escapeHtml(violationTypes)}</td>
                <td class="violation-note">${violation.custom_note ? escapeHtml(violation.custom_note) : '-'}</td>
                <td>${escapeHtml(violation.issuing_user || 'Unknown')}</td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    if (limitReached) {
        html += '<p style="color: #f59e0b; margin-top: 15px;"><strong>Note:</strong> Results limited to 500 violations. Please refine your search for more specific results.</p>';
    }
    
    resultsDiv.innerHTML = html;
}

function handleViolationPrint() {
    if (violationSearchData.length === 0) {
        showToast('No results to print', 'warning');
        return;
    }
    
    window.print();
}

async function handleViolationExport() {
    if (violationSearchData.length === 0) {
        showToast('No results to export', 'warning');
        return;
    }
    
    const startDate = document.getElementById('violationStartDate').value;
    const endDate = document.getElementById('violationEndDate').value;
    const property = document.getElementById('violationPropertyFilter').value;
    const violationType = document.getElementById('violationTypeFilter').value;
    const query = document.getElementById('violationSearchQuery').value;
    
    try {
        const response = await fetch(`${API_BASE}/violations-export`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({
                start_date: startDate,
                end_date: endDate,
                property: property,
                violation_type: violationType,
                query: query
            })
        });
        
        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `violations_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
            
            showToast('Violations exported successfully', 'success');
        } else {
            const data = await response.json();
            showToast(data.error || 'Export failed', 'error');
        }
    } catch (error) {
        console.error('Export error:', error);
        showToast('Network error during export', 'error');
    }
}
