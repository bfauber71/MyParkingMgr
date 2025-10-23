// ManageMyParking - Frontend Application

const API_BASE = '/jrk/api';
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
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => switchTab(e.target.dataset.tab));
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
}

// Authentication
async function checkAuth() {
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
    
    await loadProperties();
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
    const exportBtn = document.getElementById('exportBtn');
    const addPropertyBtn = document.getElementById('addPropertyBtn');
    const addUserBtn = document.getElementById('addUserBtn');
    
    if (currentUser.role === 'Admin') {
        propertiesTab.style.display = 'block';
        usersTab.style.display = 'block';
        addVehicleBtn.style.display = 'inline-block';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'inline-block';
        addUserBtn.style.display = 'inline-block';
    } else if (currentUser.role === 'User') {
        propertiesTab.style.display = 'none';
        usersTab.style.display = 'none';
        addVehicleBtn.style.display = 'inline-block';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'none';
        addUserBtn.style.display = 'none';
    } else {
        propertiesTab.style.display = 'none';
        usersTab.style.display = 'none';
        addVehicleBtn.style.display = 'none';
        exportBtn.style.display = 'inline-block';
        addPropertyBtn.style.display = 'none';
        addUserBtn.style.display = 'none';
    }
}

function canEditVehicles() {
    return currentUser.role === 'Admin' || currentUser.role === 'User';
}

function canDeleteVehicles() {
    return currentUser.role === 'Admin' || currentUser.role === 'User';
}

// Tab Navigation
function switchTab(tabName) {
    currentSection = tabName;
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    const activeBtn = document.querySelector(`[data-tab="${tabName}"]`);
    const activeContent = document.getElementById(`${tabName}Section`);
    
    if (activeBtn) activeBtn.classList.add('active');
    if (activeContent) activeContent.classList.add('active');
    
    if (tabName === 'vehicles') {
        loadVehiclesSection();
    } else if (tabName === 'properties') {
        loadPropertiesSection();
    } else if (tabName === 'users') {
        loadUsersSection();
    }
}

// Properties
async function loadProperties() {
    try {
        const response = await fetch(`${API_BASE}/properties`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            properties = data.properties;
            updatePropertyFilters();
        }
    } catch (error) {
        console.error('Error loading properties:', error);
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
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    ${properties.map(prop => `
                        <tr>
                            <td>${escapeHtml(prop.name)}</td>
                            <td>${escapeHtml(prop.address || 'N/A')}</td>
                            <td>${formatDate(prop.created_at)}</td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-small btn-danger" onclick="deleteProperty(${prop.id}, '${escapeHtml(prop.name)}')">Delete</button>
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

function openPropertyModal() {
    document.getElementById('propertyModalTitle').textContent = 'Add Property';
    document.getElementById('propertyForm').reset();
    document.getElementById('propertyId').value = '';
    document.getElementById('propertyModal').classList.add('show');
    
    document.getElementById('propertyForm').onsubmit = handleSaveProperty;
}

async function handleSaveProperty(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = {
        name: formData.get('name'),
        address: formData.get('address')
    };
    
    try {
        const response = await fetch(`${API_BASE}/properties-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModalByName('property');
            await loadProperties();
            loadPropertiesSection();
        } else {
            const error = await response.json();
            alert(error.error || 'Error saving property');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

async function deleteProperty(id, name) {
    if (!confirm(`Delete property "${name}"?\n\nThis will fail if any vehicles are assigned to this property.`)) {
        return;
    }
    
    try {
        const response = await fetch(`${API_BASE}/properties-delete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify({ id })
        });
        
        if (response.ok) {
            await loadProperties();
            loadPropertiesSection();
        } else {
            const error = await response.json();
            alert(error.error || 'Error deleting property');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

// Users
async function loadUsersSection() {
    document.getElementById('addUserBtn').onclick = () => openUserModal();
    
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
    
    try {
        const response = await fetch(`${API_BASE}/users-create`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModalByName('user');
            loadUsersSection();
        } else {
            const error = await response.json();
            alert(error.error || 'Error saving user');
        }
    } catch (error) {
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
    document.getElementById('exportBtn').onclick = exportVehicles;
    
    searchVehicles();
}

async function searchVehicles() {
    const query = document.getElementById('searchInput').value;
    const property = document.getElementById('propertyFilter').value;
    
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

function exportVehicles() {
    window.location.href = `${API_BASE}/vehicles-export`;
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
