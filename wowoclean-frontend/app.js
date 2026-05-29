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

function checkAuthState() {
    const token = localStorage.getItem('jwt_token');
    if (token) {
        document.getElementById('view-login').classList.add('hidden');
        document.getElementById('view-dashboard').classList.remove('hidden');
        
        const role = localStorage.getItem('user_role');
        document.getElementById('user-role-display').textContent = role.toUpperCase();
        
        if (role === 'admin') {
            document.getElementById('admin-panel').classList.remove('hidden');
        } else {
            document.getElementById('admin-panel').classList.add('hidden');
        }
        
        fetchContainers();
    } else {
        document.getElementById('view-dashboard').classList.add('hidden');
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
        
        let adminButtons = '';
        if (role === 'admin') {
            const archiveBtn = item.status === 'Active' 
                ? `<button onclick="archiveContainer(${item.id})" class="btn btn-warning">Archive</button>` 
                : '';
            adminButtons = `
                <div class="card-actions">
                    ${archiveBtn}
                    <button onclick="deleteContainer(${item.id})" class="btn btn-danger">Hapus</button>
                </div>
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
                ${adminButtons}
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

document.addEventListener('DOMContentLoaded', checkAuthState);