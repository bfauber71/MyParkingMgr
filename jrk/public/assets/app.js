// ManageMyParking - Frontend Application

const API_BASE = '/jrk/api';
let currentUser = null;
let properties = [];
let currentVehicleId = null;

// DOM Elements
const loginPage = document.getElementById('loginPage');
const dashboardPage = document.getElementById('dashboardPage');
const loginForm = document.getElementById('loginForm');
const loginError = document.getElementById('loginError');
const logoutBtn = document.getElementById('logoutBtn');
const userInfo = document.getElementById('userInfo');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const propertyFilter = document.getElementById('propertyFilter');
const results = document.getElementById('results');
const addVehicleBtn = document.getElementById('addVehicleBtn');
const exportBtn = document.getElementById('exportBtn');
const vehicleModal = document.getElementById('vehicleModal');
const vehicleForm = document.getElementById('vehicleForm');
const modalTitle = document.getElementById('modalTitle');
const vehicleProperty = document.getElementById('vehicleProperty');
const closeModal = document.querySelector('.close');
const cancelBtn = document.getElementById('cancelBtn');

// Initialize app
document.addEventListener('DOMContentLoaded', () => {
    checkAuth();
    setupEventListeners();
});

function setupEventListeners() {
    loginForm.addEventListener('submit', handleLogin);
    logoutBtn.addEventListener('click', handleLogout);
    searchBtn.addEventListener('click', searchVehicles);
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') searchVehicles();
    });
    propertyFilter.addEventListener('change', searchVehicles);
    addVehicleBtn.addEventListener('click', () => openVehicleModal());
    exportBtn.addEventListener('click', exportVehicles);
    vehicleForm.addEventListener('submit', handleSaveVehicle);
    closeModal.addEventListener('click', closeVehicleModal);
    cancelBtn.addEventListener('click', closeVehicleModal);
    
    window.addEventListener('click', (e) => {
        if (e.target === vehicleModal) {
            closeVehicleModal();
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
    
    await loadProperties();
    searchVehicles();
}

function showError(message) {
    loginError.textContent = message;
    loginError.classList.add('show');
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
    // Update search filter
    propertyFilter.innerHTML = '<option value="">All Properties</option>';
    properties.forEach(prop => {
        const option = document.createElement('option');
        option.value = prop.name;
        option.textContent = prop.name;
        propertyFilter.appendChild(option);
    });
    
    // Update form filter
    vehicleProperty.innerHTML = '<option value="">Select Property</option>';
    properties.forEach(prop => {
        const option = document.createElement('option');
        option.value = prop.name;
        option.textContent = prop.name;
        vehicleProperty.appendChild(option);
    });
}

// Vehicles
async function searchVehicles() {
    const query = searchInput.value;
    const property = propertyFilter.value;
    
    const params = new URLSearchParams();
    if (query) params.append('q', query);
    if (property) params.append('property', property);
    
    try {
        const response = await fetch(`${API_BASE}/vehicles/search?${params}`, {
            credentials: 'include'
        });
        
        if (response.ok) {
            const data = await response.json();
            displayVehicles(data.vehicles);
        } else {
            results.innerHTML = '<div class="no-results">Error loading vehicles</div>';
        }
    } catch (error) {
        results.innerHTML = '<div class="no-results">Network error</div>';
    }
}

function displayVehicles(vehicles) {
    if (vehicles.length === 0) {
        results.innerHTML = '<div class="no-results">No vehicles found</div>';
        return;
    }
    
    const grid = document.createElement('div');
    grid.className = 'vehicle-grid';
    
    vehicles.forEach(vehicle => {
        const card = createVehicleCard(vehicle);
        grid.appendChild(card);
    });
    
    results.innerHTML = '';
    results.appendChild(grid);
}

function createVehicleCard(vehicle) {
    const card = document.createElement('div');
    card.className = 'vehicle-card';
    
    const title = vehicle.plate_number || vehicle.tag_number || 'No Plate/Tag';
    
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

// Vehicle Modal
function openVehicleModal(vehicle = null) {
    currentVehicleId = vehicle ? vehicle.id : null;
    modalTitle.textContent = vehicle ? 'Edit Vehicle' : 'Add Vehicle';
    
    if (vehicle) {
        // Populate form with vehicle data
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
        vehicleForm.reset();
    }
    
    vehicleModal.classList.add('show');
}

function closeVehicleModal() {
    vehicleModal.classList.remove('show');
    vehicleForm.reset();
    currentVehicleId = null;
}

async function handleSaveVehicle(e) {
    e.preventDefault();
    
    const formData = new FormData(vehicleForm);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(`${API_BASE}/vehicles`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'include',
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeVehicleModal();
            searchVehicles();
        } else {
            const error = await response.json();
            alert(error.error || 'Error saving vehicle');
        }
    } catch (error) {
        alert('Network error. Please try again.');
    }
}

// Export
async function exportVehicles() {
    window.location.href = `${API_BASE}/vehicles/export`;
}

// Utilities
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
