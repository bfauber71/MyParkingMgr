// ManageMyParking - Frontend Application

// Auto-detect API base path (empty for Replit/dev, /jrk for production)
const API_BASE = (window.location.hostname === 'localhost' || window.location.hostname.includes('replit')) 
    ? '/api' 
    : '/jrk/api';
let currentUser = null;
let properties = [];
let currentSection = 'vehicles';

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

async function handleLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
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
            showDashboard();
        } else {
            showError(data.error || 'Login failed');
        }
    } catch (error) {
        showError('Network error. Please try again.');
    }
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
    
    applyRolePermissions();
    
    // Load properties in background, don't wait for it
    loadProperties().catch(err => console.error('Failed to load properties:', err));
    
    // Immediately show vehicles tab
    switchTab('vehicles');
}

function showError(message) {
    loginError.textContent = message;
    loginError.classList.add('show');
}

// Role-Based Permissions
function applyRolePermissions() {
    const propertiesTab = document.getElementById('propertiesTab');
    const usersTab = document.getElementById('usersTab');
    const addVehicleBtn = document.getElementById('addVehicleBtn');
    const importBtn = document.getElementById('importBtn');
    const exportBtn = document.getElementById('exportBtn');
    const addPropertyBtn = document.getElementById('addPropertyBtn');
    const addUserBtn = document.getElementById('addUserBtn');
    
    // Normalize role to lowercase for comparison
    const role = (currentUser.role || '').toLowerCase();
    
    console.log('Applying permissions for role:', role);
    
    if (role === 'admin') {
        propertiesTab.style.display = 'block';
        usersTab.style.display = 'block';
        addVehicleBtn.style.display = 'inline-block';
        importBtn.style.display = 'inline-block';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'inline-block';
        addUserBtn.style.display = 'inline-block';
        console.log('Admin permissions applied - all features visible');
    } else if (role === 'user') {
        propertiesTab.style.display = 'none';
        usersTab.style.display = 'none';
        addVehicleBtn.style.display = 'inline-block';
        importBtn.style.display = 'inline-block';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'none';
        addUserBtn.style.display = 'none';
        console.log('User permissions applied - vehicle features visible');
    } else {
        // Operator role
        propertiesTab.style.display = 'none';
        usersTab.style.display = 'none';
        addVehicleBtn.style.display = 'none';
        importBtn.style.display = 'none';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'none';
        addUserBtn.style.display = 'none';
        console.log('Operator permissions applied - read-only mode');
    }
}

function canEditVehicles() {
    const role = (currentUser.role || '').toLowerCase();
    return role === 'admin' || role === 'user';
}

function canDeleteVehicles() {
    const role = (currentUser.role || '').toLowerCase();
    return role === 'admin' || role === 'user';
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
        } else if (tabName === 'users') {
            loadUsersSection();
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
            alert('Property created successfully!');
            closeModalByName('property');
            await loadProperties();
            loadPropertiesSection();
        } else {
            console.error('Property creation failed:', responseData);
            alert(responseData.error || 'Error saving property');
        }
    } catch (error) {
        console.error('Property save network error:', error);
        alert('Network error. Please try again.');
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
            alert('Property updated successfully!');
            closeModalByName('property');
            await loadProperties();
            loadPropertiesSection();
        } else {
            console.error('Property update failed:', responseData);
            alert(responseData.error || 'Error updating property');
        }
    } catch (error) {
        console.error('Property update network error:', error);
        alert('Network error. Please try again.');
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
            alert('Property deleted successfully!');
            await loadProperties();
            loadPropertiesSection();
        } else {
            alert(responseData.error || 'Error deleting property');
        }
    } catch (error) {
        console.error('Property delete error:', error);
        alert('Network error. Please try again.');
    }
}

// Users
async function loadUsersSection() {
    document.getElementById('addUserBtn').onclick = () => openUserModal();
    
    // DEMO MODE: Show sample users without database
    const isDemo = window.location.hostname === 'localhost' || window.location.hostname.includes('replit');
    if (isDemo) {
        const demoUsers = [
            { id: 1, username: 'admin', email: 'admin@example.com', role: 'Admin', created_at: '2024-01-01' },
            { id: 2, username: 'manager', email: 'manager@example.com', role: 'User', created_at: '2024-02-15' },
            { id: 3, username: 'viewer', email: 'viewer@example.com', role: 'Operator', created_at: '2024-03-20' }
        ];
        displayUsersTable(demoUsers);
        return;
    }
    
    // PRODUCTION MODE: Fetch from API
    try {
        const response = await fetch(`${API_BASE}/users-list`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayUsersTable(data.users);
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function displayUsersTable(users) {
    const container = document.getElementById('usersResults');
    
    if (users.length === 0) {
        container.innerHTML = '<div class="no-results">No users found</div>';
        return;
    }
    
    const table = `
        <div class="data-table">
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
                                    ${user.id !== currentUser.id ? `<button class="btn btn-small btn-danger" onclick="deleteUser(${user.id}, '${escapeHtml(user.username)}')">Delete</button>` : ''}
                                </div>
                            </td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = table;
}

function openUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userModal').classList.add('show');
    
    document.getElementById('userForm').onsubmit = handleSaveUser;
}

async function handleSaveUser(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        username: formData.get('username'),
        email: formData.get('email'),
        password: formData.get('password'),
        role: formData.get('role')
    };
    
    console.log('Saving user:', {username: data.username, email: data.email, role: data.role});
    console.log('POST to:', `${API_BASE}/users-create`);
    
    try {
        const response = await fetch(`${API_BASE}/users-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        console.log('User save response status:', response.status);
        const responseData = await response.json();
        console.log('User save response:', responseData);
        
        if (response.ok) {
            alert('User created successfully!');
            closeModalByName('user');
            loadUsersSection();
        } else {
            console.error('User creation failed:', responseData);
            alert(responseData.error || 'Error saving user');
        }
    } catch (error) {
        console.error('User save network error:', error);
        alert('Network error. Please try again.');
    }
}

async function deleteUser(id, username) {
    if (!confirm(`Delete user "${username}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/users-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            loadUsersSection();
        } else {
            const error = await response.json();
            alert(error.error || 'Error deleting user');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

// Vehicles
function loadVehiclesSection() {
    document.getElementById('searchBtn').onclick = searchVehicles;
    document.getElementById('searchInput').onkeypress = (e) => {
        if (e.key === 'Enter') searchVehicles();
    };
    document.getElementById('propertyFilter').onchange = searchVehicles;
    document.getElementById('addVehicleBtn').onclick = () => openVehicleModal();
    document.getElementById('importBtn').onclick = importVehicles;
    document.getElementById('exportBtn').onclick = exportVehicles;
    
    searchVehicles();
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
                owner_email: 'john@example.com'
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
                owner_email: 'jane@example.com'
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
                owner_email: 'bob@example.com'
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
    
    const actionButtons = canEditVehicles() ? `
        <div class="vehicle-actions">
            <button class="btn btn-small btn-primary" onclick='editVehicle(${JSON.stringify(vehicle)})'>Edit</button>
            ${canDeleteVehicles() ? `<button class="btn btn-small btn-danger" onclick="deleteVehicle(${vehicle.id}, '${escapeHtml(title)}')">Delete</button>` : ''}
        </div>
    ` : '';
    
    card.innerHTML = `
        <div class="vehicle-header">
            <div class="vehicle-title">${escapeHtml(title)}</div>
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
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModalByName('vehicle');
            searchVehicles();
        } else {
            const error = await response.json();
            alert(error.error || 'Error saving vehicle');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

async function deleteVehicle(id, title) {
    if (!confirm(`Delete vehicle "${title}"?`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/vehicles-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            searchVehicles();
        } else {
            const error = await response.json();
            alert(error.error || 'Error deleting vehicle');
        }
    } catch (error) {
        alert('Network error. Please try again.');
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
                alert(data.message + (data.errors.length > 0 ? '\n\nErrors:\n' + data.errors.join('\n') : ''));
                searchVehicles(); // Refresh the list
            } else {
                alert('Import failed: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Import error:', error);
            alert('Import failed. Please try again.');
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
