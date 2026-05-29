const API_URL = 'http://127.0.0.1:8000/api/v1';

axios.interceptors.request.use(config => {
    const token = localStorage.getItem('jwt_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    config.headers['Accept'] = 'application/json';
    return config;
});

axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            handleLogout();
        }
        return Promise.reject(error);
    }
);

function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s forwards';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function switchAuthView(target) {
    if (target === 'login') {
        document.getElementById('view-register').classList.add('hidden');
        document.getElementById('view-login').classList.remove('hidden');
    } else {
        document.getElementById('view-login').classList.add('hidden');
        document.getElementById('view-register').classList.remove('hidden');
    }
}

document.getElementById('link-to-register').addEventListener('click', (e) => {
    e.preventDefault();
    switchAuthView('register');
});

document.getElementById('link-to-login').addEventListener('click', (e) => {
    e.preventDefault();
    switchAuthView('login');
});

function checkAuthState() {
    const token = localStorage.getItem('jwt_token');
    if (token) {
        document.getElementById('view-login').classList.add('hidden');
        document.getElementById('view-register').classList.add('hidden');
        document.getElementById('view-dashboard').classList.remove('hidden');
        
        const role = localStorage.getItem('user_role');
        document.getElementById('user-role-display').textContent = role.toUpperCase();
        
        if (role === 'admin') {
            document.getElementById('create-panel').classList.remove('hidden');
        } else {
            document.getElementById('create-panel').classList.add('hidden');
        }
        
        fetchContainers();
    } else {
        document.getElementById('view-dashboard').classList.add('hidden');
        document.getElementById('view-register').classList.add('hidden');
        document.getElementById('view-login').classList.remove('hidden');
    }
}

document.getElementById('form-login').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('login-email').value;
    const password = document.getElementById('login-password').value;

    try {
        const response = await axios.post(`${API_URL}/login`, { email, password });
        localStorage.setItem('jwt_token', response.data.data.access_token);
        localStorage.setItem('user_role', response.data.data.user.role);
        
        document.getElementById('form-login').reset();
        showToast('Autentikasi berhasil, memuat sistem...');
        checkAuthState();
    } catch (error) {
        showToast('Gagal masuk: Kredensial tidak valid', 'error');
    }
});

document.getElementById('form-register').addEventListener('submit', async (e) => {
    e.preventDefault();
    document.getElementById('err-reg-email').textContent = '';
    
    const payload = {
        name: document.getElementById('register-name').value,
        email: document.getElementById('register-email').value,
        password: document.getElementById('register-password').value
    };

    try {
        await axios.post(`${API_URL}/register`, payload);
        document.getElementById('form-register').reset();
        showToast('Registrasi berhasil, silakan masuk dengan akun baru Anda');
        switchAuthView('login');
    } catch (error) {
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            if (errors.email) document.getElementById('err-reg-email').textContent = errors.email[0];
            showToast('Validasi gagal, periksa input Anda', 'error');
        } else {
            showToast('Terjadi kesalahan pada sistem registrasi', 'error');
        }
    }
});

function handleLogout() {
    localStorage.removeItem('jwt_token');
    localStorage.removeItem('user_role');
    checkAuthState();
}

document.getElementById('btn-logout').addEventListener('click', async () => {
    try {
        await axios.post(`${API_URL}/logout`);
    } catch (error) {} 
    handleLogout();
});

async function fetchContainers() {
    try {
        const type = document.getElementById('filter-type').value;
        const weight = document.getElementById('filter-weight').value;
        
        let url = `${API_URL}/gateway/containers/search?`;
        if (type) url += `type=${type}&`;
        if (weight) url += `min_weight=${weight}`;

        const response = await axios.get(url);
        renderContainers(response.data.data);
    } catch (error) {
        showToast('Gagal memuat data kontainer', 'error');
    }
}

document.getElementById('btn-search').addEventListener('click', fetchContainers);

function renderContainers(containers) {
    const grid = document.getElementById('container-grid');
    grid.innerHTML = '';
    
    let totalWeight = 0;
    const role = localStorage.getItem('user_role');

    containers.forEach(item => {
        totalWeight += parseFloat(item.weight_kg);
        
        const statusClass = item.status === 'Active' ? 'status-active' : 'status-archived';
        
        let actionButtons = `<button onclick="viewLogs(${item.id}, '${item.container_id}')" class="btn btn-primary" style="padding: 0.5rem 1rem; font-size: 0.875rem;">Log</button>`;
        
        if (role === 'admin') {
            const archiveBtn = item.status === 'Active' 
                ? `<button onclick="archiveContainer(${item.id})" class="btn btn-warning">Archive</button>` 
                : '';
            actionButtons += `
                ${archiveBtn}
                <button onclick="deleteContainer(${item.id})" class="btn btn-danger">Hapus</button>
            `;
        }

        grid.innerHTML += `
            <div class="data-card">
                <div class="header">
                    <span class="title">${item.container_id}</span>
                    <span class="status-badge ${statusClass}">${item.status}</span>
                </div>
                <div class="data-row">
                    <span>Tipe Limbah</span>
                    <strong>${item.waste_type}</strong>
                </div>
                <div class="data-row">
                    <span>Berat</span>
                    <strong>${item.weight_kg} kg</strong>
                </div>
                <div class="card-actions">
                    ${actionButtons}
                </div>
            </div>
        `;
    });

    document.getElementById('total-weight-display').textContent = `Total Muatan: ${totalWeight} kg`;
}

document.getElementById('form-container').addEventListener('submit', async (e) => {
    e.preventDefault();
    document.querySelectorAll('.error-msg').forEach(el => el.textContent = '');

    const payload = {
        container_id: document.getElementById('input-id').value,
        waste_type: document.getElementById('input-type').value,
        weight_kg: document.getElementById('input-weight').value
    };

    try {
        await axios.post(`${API_URL}/gateway/containers`, payload);
        showToast('Kontainer berhasil diregistrasi');
        document.getElementById('form-container').reset();
        fetchContainers();
    } catch (error) {
        if (error.response && error.response.status === 422) {
            const errors = error.response.data.errors;
            if (errors.container_id) document.getElementById('err-id').textContent = errors.container_id[0];
            if (errors.waste_type) document.getElementById('err-type').textContent = errors.waste_type[0];
            if (errors.weight_kg) document.getElementById('err-weight').textContent = errors.weight_kg[0];
            showToast('Validasi gagal, periksa input Anda', 'error');
        } else if (error.response && error.response.status === 403) {
            showToast('Akses ditolak: Otorisasi tidak memadai', 'error');
        }
    }
});

window.archiveContainer = async function(id) {
    if (!confirm('Arsip kontainer ini?')) return;
    try {
        await axios.patch(`${API_URL}/gateway/containers/${id}/archive`);
        showToast('Kontainer berhasil diarsipkan');
        fetchContainers();
    } catch (error) {
        showToast('Gagal mengarsipkan kontainer', 'error');
    }
}

window.deleteContainer = async function(id) {
    if (!confirm('Hapus kontainer ini secara permanen?')) return;
    try {
        await axios.delete(`${API_URL}/gateway/containers/${id}`);
        showToast('Kontainer dihapus');
        fetchContainers();
    } catch (error) {
        showToast('Gagal menghapus kontainer', 'error');
    }
}

window.viewLogs = async function(id, container_id) {
    try {
        const response = await axios.get(`${API_URL}/gateway/containers/${id}/logs`);
        const logs = response.data.data;
        const content = document.getElementById('log-content');
        document.getElementById('log-modal-title').textContent = `Log: ${container_id}`;
        
        if (logs.length === 0) {
            content.innerHTML = '<p style="text-align:center; color:var(--text-muted);">Belum ada log perjalanan.</p>';
        } else {
            content.innerHTML = logs.map(log => `
                <div style="border-left: 2px solid var(--primary); padding-left: 1rem;">
                    <div style="font-size: 0.8rem; color: var(--text-muted);">${new Date(log.timestamp).toLocaleString('id-ID')}</div>
                    <div style="font-weight: 500;">${log.location}</div>
                    <div style="font-size: 0.9rem;">${log.description}</div>
                </div>
            `).join('');
        }
        
        document.getElementById('log-modal').classList.remove('hidden');
    } catch (error) {
        showToast('Gagal memuat log perjalanan', 'error');
    }
}

window.closeLogModal = function() {
    document.getElementById('log-modal').classList.add('hidden');
}

document.addEventListener('DOMContentLoaded', checkAuthState);