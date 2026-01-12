let currentRestaurant = null;
let restaurants = [];

// ============================================
// INITIALIZATION
// ============================================

window.addEventListener('DOMContentLoaded', () => {
    const token = localStorage.getItem('authToken');
    if (token) {
        authToken = token;
        showPage('mainPage');
        initializeApp();
    } else {
        showPage('loginPage');
    }
    
    document.getElementById('loginForm')?.addEventListener('submit', handleLogin);
    document.getElementById('registerForm')?.addEventListener('submit', handleRegister);
    
    // SETUP MODAL LISTENERS
    initModalListeners();
});

async function initializeApp() {
    try {
        const user = await getUserProfile();
        if(document.getElementById('userNameDisplay')) 
            document.getElementById('userNameDisplay').textContent = user.name;
        
        const userData = JSON.parse(localStorage.getItem('currentUser'));
        const role = userData ? userData.role : '';

        // SETUP PANELS
        const ownerPanel = document.getElementById('ownerDashboard');
        const adminPanel = document.getElementById('adminDashboard');
        
        if (ownerPanel) {
            if (role === 'owner' || role === 'admin') ownerPanel.classList.remove('hidden');
            else ownerPanel.classList.add('hidden');
        }

        if (adminPanel) {
            if (role === 'admin') adminPanel.classList.remove('hidden');
            else adminPanel.classList.add('hidden');
        }

        updateCartCount();

    } catch (e) { 
        console.warn("Auth check failed:", e); 
    } finally { 
        loadRestaurants(); 
    }
}

// ============================================
// MODAL HANDLERS (PENGGANTI PROMPT)
// ============================================

function initModalListeners() {
    // 1. Restaurant Form
    document.getElementById('restaurantForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const id = document.getElementById('editRestaurantId').value;
        const name = document.getElementById('restName').value;
        const address = document.getElementById('restAddress').value;
        const open = document.getElementById('restOpenTime').value;
        const close = document.getElementById('restCloseTime').value;
        const hours = `${open} - ${close}`;

        try {
            if (id) {
                await updateRestaurant(id, name, null, address, null, hours);
                showMessage("Restoran berhasil diupdate!", "success");
                if (currentRestaurant && currentRestaurant.id == id) viewRestaurant(id);
            } else {
                await createRestaurant(name, "Desc", address, "08123456", hours);
                showMessage("Restoran berhasil dibuat!", "success");
            }
            closeModal('restaurantModal');
            loadRestaurants();
        } catch (err) { showError(err); }
    });

    // 2. Menu Form
    document.getElementById('menuForm')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const restaurantId = document.getElementById('menuRestaurantId').value;
        const name = document.getElementById('menuName').value;
        const desc = document.getElementById('menuDesc').value;
        const price = document.getElementById('menuPrice').value;

        try {
            await apiCall(`restaurants/${restaurantId}/menu`, 'POST', {
                name, description: desc, price, stock: 100
            });
            showMessage("Menu berhasil ditambahkan!", "success");
            closeModal('menuModal');
            viewRestaurant(restaurantId);
        } catch (err) { showError(err); }
    });
}

function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// ============================================
// ACTIONS (TRIGGERED BY BUTTONS)
// ============================================

function showCreateRestaurantForm() {
    document.getElementById('restaurantModalTitle').textContent = "Buat Restoran Baru";
    document.getElementById('editRestaurantId').value = "";
    document.getElementById('restaurantForm').reset();
    document.getElementById('restOpenTime').value = "09:00";
    document.getElementById('restCloseTime').value = "21:00";
    openModal('restaurantModal');
}

function editRestaurant(id) {
    if (!currentRestaurant || currentRestaurant.id != id) return;
    document.getElementById('restaurantModalTitle').textContent = "Edit Info Restoran";
    document.getElementById('editRestaurantId').value = id;
    document.getElementById('restName').value = currentRestaurant.name;
    document.getElementById('restAddress').value = currentRestaurant.address;
    
    const hours = currentRestaurant.operating_hours || "09:00 - 21:00";
    const parts = hours.split('-').map(s => s.trim());
    document.getElementById('restOpenTime').value = parts[0] || "09:00";
    document.getElementById('restCloseTime').value = parts[1] || "21:00";
    openModal('restaurantModal');
}

function addMenu(restaurantId) {
    document.getElementById('menuRestaurantId').value = restaurantId;
    document.getElementById('menuForm').reset();
    openModal('menuModal');
}

async function showAdminUsers() {
    try {
        const result = await apiCall('admin/users');
        const container = document.getElementById('adminContent');
        
        // Simpan data users di variable global sementara agar bisa difilter
        // (Pastikan variable ini unik untuk scope fungsi ini atau global)
        window.allUsersData = result.data; 

        // 1. Render Struktur Awal (Input Search + Tabel Kosong)
        container.innerHTML = `
            <div style="margin-bottom: 15px;">
                <input type="text" id="userSearchInput" 
                       placeholder="🔍 Cari nama atau email..." 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"
                       onkeyup="filterUsers()">
            </div>
            <div id="usersTableContainer">
                <!-- Tabel akan di-render di sini oleh fungsi renderUserTable -->
            </div>
        `;
        
        // 2. Render Tabel Pertama Kali dengan Semua Data
        renderUserTable(window.allUsersData);
        
        container.classList.remove('hidden');
    } catch(e) { 
        showError(e); 
    }
}

// FUNGSI BARU: Render Tabel (Dipisah agar bisa dipanggil ulang saat filter)
function renderUserTable(users) {
    const tableContainer = document.getElementById('usersTableContainer');
    
    if (users.length === 0) {
        tableContainer.innerHTML = '<p style="text-align:center; padding:20px; color:#666;">Tidak ada user ditemukan.</p>';
        return;
    }

    let html = `
    <table class="user-table" style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background:#f2f2f2;">
                <th style="padding:10px; border:1px solid #ddd;">Nama</th>
                <th style="padding:10px; border:1px solid #ddd;">Email</th>
                <th style="padding:10px; border:1px solid #ddd;">Role</th>
                <th style="padding:10px; border:1px solid #ddd;">Aksi</th>
            </tr>
        </thead>
        <tbody>`;
        
    users.forEach(u => {
        let actionButtons = '';
        
        // Tombol Hapus
        actionButtons += `<button class="btn btn-danger btn-small" onclick="deleteUser(${u.id})">Hapus</button> `;
        
        // Tombol Promote (Hanya jika Customer)
        if (u.role === 'customer') {
            actionButtons += `<button class="btn btn-secondary btn-small" onclick="promoteToOwner(${u.id})">⬆️ Owner</button>`;
        }

        const roleColor = u.role === 'owner' ? '#d35400' : (u.role === 'admin' ? '#2980b9' : '#333');

        html += `
        <tr>
            <td style="padding:10px; border:1px solid #ddd;">${u.name}</td>
            <td style="padding:10px; border:1px solid #ddd;">${u.email}</td>
            <td style="padding:10px; border:1px solid #ddd;">
                <span style="font-weight:bold; color:${roleColor}">${u.role.toUpperCase()}</span>
            </td>
            <td style="padding:10px; border:1px solid #ddd;">
                ${actionButtons}
            </td>
        </tr>`;
    });
    
    html += '</tbody></table>';
    html += `<p style="margin-top:10px; font-size:0.9em; color:#666;">Total: ${users.length} user</p>`;
    
    tableContainer.innerHTML = html;
}

// FUNGSI BARU: Logika Filter
function filterUsers() {
    const input = document.getElementById('userSearchInput');
    const filter = input.value.toLowerCase();
    
    // Ambil data asli (window.allUsersData) dan filter
    const filteredData = window.allUsersData.filter(user => {
        const nameMatch = user.name.toLowerCase().includes(filter);
        const emailMatch = user.email.toLowerCase().includes(filter);
        return nameMatch || emailMatch;
    });
    
    // Render ulang tabel dengan data hasil filter
    renderUserTable(filteredData);
}

// FUNGSI LAMA: Promote & Delete (Tetap Sama, pastikan tetap ada)
async function promoteToOwner(id) {
    if(confirm('Ubah status user ini menjadi Owner Restoran?')) {
        try {
            await apiCall(`admin/users/${id}/promote`, 'PUT');
            showMessage('Berhasil! User sekarang adalah Owner.', 'success');
            showAdminUsers(); // Refresh data & tabel
        } catch (error) { showError(error); }
    }
}

async function deleteUser(id) {
    if(confirm('Yakin hapus user ini?')) {
        await apiCall(`admin/users/${id}`, 'DELETE');
        showAdminUsers();
    }
}

// FUNGSI BARU: Promote User
async function promoteToOwner(id) {
    if(confirm('Ubah status user ini menjadi Owner Restoran?')) {
        try {
            await apiCall(`admin/users/${id}/promote`, 'PUT');
            showMessage('Berhasil! User sekarang adalah Owner.', 'success');
            showAdminUsers(); // Refresh tabel
        } catch (error) {
            showError(error);
        }
    }
}

async function deleteUser(id) {
    if(confirm('Yakin hapus user ini?')) {
        await apiCall(`admin/users/${id}`, 'DELETE');
        showAdminUsers();
    }
}

async function showOwnerOrders() {
    try {
        // 1. Ambil daftar restoran milik Owner ini
        const result = await apiCall('owner/restaurants');
        const myRestaurants = result.data;

        if (myRestaurants.length === 0) {
            alert("Anda belum memiliki restoran. Silakan buat restoran terlebih dahulu.");
            return;
        }

        let selectedRestaurantId = null;

        // 2. Logika Pemilihan Restoran
        if (myRestaurants.length === 1) {
            // Jika cuma punya 1, langsung pilih otomatis
            selectedRestaurantId = myRestaurants[0].id;
        } else {
            // Jika punya banyak, kita harus tanya user mau lihat yang mana
            // Karena prompt() tidak bisa dropdown, kita buat string list sederhana
            // (Cara paling cepat tanpa membuat Modal HTML baru yang kompleks)
            
            let listText = "Pilih Restoran (Ketik Angka Nomor):\n";
            myRestaurants.forEach((resto, index) => {
                listText += `${index + 1}. ${resto.name}\n`;
            });

            const choice = prompt(listText);
            const index = parseInt(choice) - 1;

            if (!isNaN(index) && myRestaurants[index]) {
                selectedRestaurantId = myRestaurants[index].id;
            } else {
                return; // Batal atau salah pilih
            }
        }

        // 3. Ambil Orderan dari Restoran Terpilih
        if (selectedRestaurantId) {
            // Cari nama restoran untuk judul
            const restoName = myRestaurants.find(r => r.id == selectedRestaurantId).name;
            
            const ordersResult = await getRestaurantOrders(selectedRestaurantId);
            const container = document.getElementById('ordersList');
            
            // ... (Bagian atas showOwnerOrders tetap sama)

            if (ordersResult.data.length === 0) {
                container.innerHTML = `<h3>📦 Pesanan Masuk: ${restoName}</h3><p>Belum ada pesanan.</p>`;
            } else {
                container.innerHTML = `<h3>📦 Pesanan Masuk: ${restoName}</h3>` + 
                    ordersResult.data.map(order => {
                        // LOGIKA TOMBOL BERTAHAP
                        let actionButtons = '';
                        
                        if (order.status === 'pending') {
                            // Jika Pending: Muncul Terima & Tolak
                            actionButtons = `
                                <button class="btn btn-small btn-primary" onclick="updateStatus(this, ${order.id}, 'preparing')">👨‍🍳 Terima & Masak</button>
                                <button class="btn btn-small btn-danger" onclick="updateStatus(this, ${order.id}, 'cancelled')">❌ Tolak</button>
                            `;
                        } else if (order.status === 'preparing') {
                            // Jika Sedang Dimasak: Muncul Kirim & Selesai
                            actionButtons = `
                                <button class="btn btn-small btn-secondary" onclick="updateStatus(this, ${order.id}, 'delivered')">🛵 Kirim & Selesai</button>
                            `;
                        } else {
                            // Jika Selesai/Batal: Tidak ada tombol aksi
                            actionButtons = '<span style="color:#888; font-size:0.9em;">Pesanan Selesai</span>';
                        }

                        return `
                        <div class="order-card" id="order-card-${order.id}">
                            <div class="order-card-header">
                                <h4>Order #${order.id} - Rp ${formatCurrency(order.total_price)}</h4>
                                <span class="order-status ${order.status}" id="status-badge-${order.id}">${order.status}</span>
                            </div>
                            <p><strong>Status:</strong> <span id="status-text-${order.id}">${order.status}</span></p>
                            <p><strong>Alamat:</strong> ${order.delivery_address}</p>
                            <p><strong>Catatan:</strong> ${order.notes || '-'}</p>
                            <p><strong>Tanggal:</strong> ${formatDate(order.order_date)}</p>
                            <div class="order-actions" id="actions-${order.id}" style="margin-top: 10px;">
                                ${actionButtons}
                            </div>
                        </div>
                        `;
                    }).join('');
            }
            showPage('ordersPage');
        }

    } catch (error) {
        showError(error);
    }
}

// FUNGSI UPDATE STATUS (REVISI: Realtime UI Update)
async function updateStatus(btnElement, orderId, newStatus) {
    // Tambahkan efek loading di tombol
    const originalText = btnElement.innerText;
    btnElement.innerText = "Memproses...";
    btnElement.disabled = true;

    try {
        // 1. Kirim Request ke Server
        await updateOrderStatus(orderId, newStatus);
        
        // 2. Jika Sukses, Update UI Langsung (Tanpa Refresh)
        
        // a. Ubah Badge Status & Teks
        const statusBadge = document.getElementById(`status-badge-${orderId}`);
        const statusText = document.getElementById(`status-text-${orderId}`);
        
        if (statusBadge) {
            statusBadge.className = `order-status ${newStatus}`; // Ganti warna class
            statusBadge.textContent = newStatus;
        }
        if (statusText) statusText.textContent = newStatus;

        // b. Ganti Tombol Aksi Sesuai Status Baru
        const actionContainer = document.getElementById(`actions-${orderId}`);
        
        if (newStatus === 'preparing') {
            // Jika baru diterima, ganti tombol jadi "Kirim & Selesai"
            actionContainer.innerHTML = `
                <button class="btn btn-small btn-secondary" onclick="updateStatus(this, ${orderId}, 'delivered')">🛵 Kirim & Selesai</button>
            `;
            showMessage(`Order #${orderId} diterima. Status: Preparing`, "success");
            
        } else if (newStatus === 'delivered') {
            // Jika sudah dikirim, hilangkan tombol
            actionContainer.innerHTML = '<span style="color:#27ae60; font-weight:bold;">✅ Pesanan Terkirim</span>';
            showMessage(`Order #${orderId} selesai!`, "success");
            
        } else if (newStatus === 'cancelled') {
            actionContainer.innerHTML = '<span style="color:#c0392b; font-weight:bold;">❌ Pesanan Ditolak</span>';
            showMessage(`Order #${orderId} ditolak.`, "info");
        }

    } catch (error) {
        // Jika Gagal, kembalikan tombol seperti semula
        showError(error);
        btnElement.innerText = originalText;
        btnElement.disabled = false;
    }
}

// ============================================
// RESTAURANT DISPLAY (UPDATED)
// ============================================

async function loadRestaurants(search = '') {
    try {
        const result = await getRestaurants(1, 20, search);
        restaurants = result.data;
        displayRestaurants(restaurants);
    } catch (error) { showError(error); }
}

// GANTI FUNGSI displayRestaurants DENGAN INI:

function displayRestaurants(restaurantList) {
    const container = document.getElementById('restaurantsList');
    
    if (!restaurantList || restaurantList.length === 0) {
        container.innerHTML = '<p class="loading">Tidak ada restoran ditemukan</p>'; 
        return;
    }

    // Ambil info user yang sedang login
    const user = JSON.parse(localStorage.getItem('currentUser'));
    const currentUserId = user ? user.user_id : 0;

    container.innerHTML = restaurantList.map(restaurant => {
        const rating = restaurant.rating || 0;
        
        // Cek apakah ini restoran milik user yang sedang login?
        const isMyRestaurant = (user && (restaurant.owner_id == currentUserId));
        
        // Buat Badge Khusus & Tombol Edit jika milik sendiri
        let ownerBadge = '';
        let editButton = '';
        let cardClass = 'restaurant-card'; // Default class

        if (isMyRestaurant) {
            // Tambahkan border warna oranye/emas agar mencolok
            cardClass += ' my-restaurant-highlight'; 
            
            ownerBadge = `
                <div style="
                    background: #ff6b35; 
                    color: white; 
                    padding: 5px 10px; 
                    font-size: 12px; 
                    font-weight: bold; 
                    position: absolute; 
                    top: 10px; 
                    right: 10px; 
                    border-radius: 4px;
                    z-index: 2;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                ">
                    👑 Restoran Anda
                </div>
            `;

            editButton = `
                <button class="btn btn-secondary btn-small" 
                    style="margin-top:5px; width:100%;"
                    onclick="event.stopPropagation(); currentRestaurant = {id: ${restaurant.id}, name: '${restaurant.name.replace(/'/g, "\\'")}', address: '${restaurant.address.replace(/'/g, "\\'")}', operating_hours: '${restaurant.operating_hours}'}; editRestaurant(${restaurant.id});">
                    ✏️ Edit Restoran
                </button>
            `;
        }

        return `
        <div class="${cardClass}" onclick="viewRestaurant(${restaurant.id})" style="position: relative;">
            ${ownerBadge}
            <div class="restaurant-card-img">🍽️</div>
            <div class="restaurant-card-body">
                <h3>${restaurant.name}</h3>
                <p>${restaurant.address}</p>
                <div class="restaurant-rating">⭐ ${rating} (${restaurant.total_reviews} ulasan)</div>
                
                <div style="margin-top: 10px;">
                    <button class="btn btn-primary btn-small" style="width:100%;" onclick="event.stopPropagation(); viewRestaurant(${restaurant.id});">
                        Lihat Menu
                    </button>
                    ${editButton}
                </div>
            </div>
        </div>
        `;
    }).join('');
}

async function viewRestaurant(id) {
    try {
        const restaurant = await getRestaurantDetail(id);
        currentRestaurant = restaurant;
        document.getElementById('restaurantName').textContent = restaurant.name;

        // OWNER CONTROLS
        const user = JSON.parse(localStorage.getItem('currentUser'));
        let ownerControls = '';
        if (user && (user.role === 'admin' || (user.role === 'owner' && restaurant.owner_id == user.user_id))) {
            ownerControls = `
                <div style="margin-top: 15px; border-top: 1px solid #ccc; padding-top: 10px;">
                    <button class="btn btn-secondary btn-small" onclick="editRestaurant(${restaurant.id})">✏️ Edit Info</button>
                    <button class="btn btn-primary btn-small" onclick="addMenu(${restaurant.id})">➕ Tambah Menu</button>
                </div>
            `;
        }

        const rating = restaurant.rating || 0;
        document.getElementById('restaurantInfo').innerHTML = `
            <h2>${restaurant.name}</h2>
            <p>${restaurant.address}</p>
            <p>Jam Buka: ${restaurant.operating_hours || '-'}</p>
            <p class="review-rating">⭐ ${rating} (${restaurant.total_reviews} ulasan)</p>
            ${ownerControls}
        `;

        displayMenuItems(restaurant.menu_items || []);

        // REVIEWS
        const reviewsResult = await apiCall(`restaurants/${id}/reviews`);
        const reviewsContainer = document.getElementById('restaurantReviews');
        if (reviewsResult.data.length > 0) {
            reviewsContainer.innerHTML = '<h3>Ulasan Pelanggan</h3>' + reviewsResult.data.map(r => `
                <div class="review-card">
                    <div class="review-header">
                        <span>${r.customer_name}</span><span class="review-rating">⭐ ${r.rating}</span>
                    </div>
                    <p class="review-text">"${r.comment}"</p>
                </div>
            `).join('');
            reviewsContainer.classList.remove('hidden');
        } else {
            reviewsContainer.innerHTML = '<p>Belum ada ulasan.</p>';
            reviewsContainer.classList.remove('hidden');
        }

        showPage('restaurantPage');
    } catch (e) { showError(e); }
}

function displayMenuItems(items) {
    const container = document.getElementById('menuItems');
    if (!items || items.length === 0) { container.innerHTML = '<p>Menu tidak tersedia</p>'; return; }
    container.innerHTML = items.map(item => `
        <div class="menu-item">
            <div class="menu-item-img">🍜</div>
            <div class="menu-item-body">
                <h4>${item.name}</h4>
                <p>${item.description || 'Enak & Lezat'}</p>
                <div class="menu-item-price">${formatCurrency(item.price)}</div>
                <div class="menu-item-actions">
                    <input type="number" id="qty-${item.id}" value="1" min="1" max="10">
                    <button class="btn btn-primary" onclick="addMenuToCart(${currentRestaurant.id}, ${item.id}, '${item.name}', ${item.price})">Tambah</button>
                </div>
            </div>
        </div>
    `).join('');
}

function addMenuToCart(restaurantId, menuItemId, name, price) {
    const qty = parseInt(document.getElementById(`qty-${menuItemId}`).value) || 1;
    addToCart(restaurantId, menuItemId, name, price, qty);
}

// ============================================
// AUTH HANDLERS
// ============================================

async function handleLogin(e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value;
    const password = document.getElementById('loginPassword').value;
    try {
        const result = await login(email, password);
        document.getElementById('userNameDisplay').textContent = result.name;
        showPage('mainPage');
        initializeApp();
    } catch (error) { showError(error); }
}

async function handleRegister(e) {
    e.preventDefault();
    
    const name = document.getElementById('regName').value;
    const email = document.getElementById('regEmail').value;
    const password = document.getElementById('regPassword').value;
    const phone = document.getElementById('regPhone').value;
    const address = document.getElementById('regAddress').value;
    
    try {
        const result = await register(name, email, password, phone, address, 'customer');
        
        if (result && result.token) {
            localStorage.setItem('authToken', result.token);
            localStorage.setItem('currentUser', JSON.stringify(result));
            authToken = result.token;
            showMessage(`Selamat datang, ${result.name}!`, 'success');
            showPage('mainPage');
            initializeApp();
        } else {
            showMessage('Registrasi berhasil! Silakan login.', 'success');
            showLogin();
        }
        
    } catch (error) {
        showError(error);
    }
}

function showLogin() { showPage('loginPage'); document.getElementById('loginForm').reset(); }
function showRegister() { showPage('registerPage'); document.getElementById('registerForm').reset(); }
function logout() { if (confirm('Yakin ingin keluar?')) { localStorage.removeItem('authToken'); localStorage.removeItem('currentUser'); clearCart(); showLogin(); } }

// ============================================
// CART & ORDER FUNCTIONS (UPDATED)
// ============================================

function showCart() {
    const cart = getCart();
    const container = document.getElementById('cartItems');
    
    if (Object.keys(cart).length === 0) {
        container.innerHTML = '<p style="text-align:center; padding:20px; color:#666;">Keranjang belanja kosong</p>';
        document.getElementById('cartSummary').style.display = 'none';
        showPage('cartPage');
        return;
    }
    
    let cartHTML = '';
    let subtotal = 0;
    
    // Loop cart items
    for (let restaurantId in cart) {
        for (let item of cart[restaurantId].items) {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            cartHTML += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h4>${item.name}</h4>
                        <p class="text-muted">${formatCurrency(item.price)} x ${item.quantity}</p>
                    </div>
                    <div class="cart-item-actions">
                        <span style="font-weight:bold; margin-right:10px;">${formatCurrency(itemTotal)}</span>
                        <button class="btn btn-danger btn-small" onclick="removeFromCart(${restaurantId}, ${item.id}); showCart();">
                            Hapus
                        </button>
                    </div>
                </div>
            `;
        }
    }
    
    container.innerHTML = cartHTML;
    
    // Set Subtotal awal
    document.getElementById('subtotal').dataset.value = subtotal; // Simpan nilai asli di data-attr
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    
    // Reset input ongkir ke default jika kosong
    const feeInput = document.getElementById('deliveryFee');
    if(!feeInput.value) feeInput.value = 5000;
    
    // Hitung Total Awal
    calculateTotal();
    
    // Tampilkan Summary
    document.getElementById('cartSummary').style.display = 'block';
    showPage('cartPage');

    // Pasang Listener untuk Realtime Update
    initCartListeners();
}

// FUNGSI BARU: Realtime Calculation
function initCartListeners() {
    const feeInput = document.getElementById('deliveryFee');
    
    // Hapus listener lama (agar tidak double) lalu pasang baru
    feeInput.oninput = calculateTotal; 
    feeInput.onkeyup = calculateTotal;
}

function calculateTotal() {
    const subtotal = parseInt(document.getElementById('subtotal').dataset.value) || 0;
    const feeInput = document.getElementById('deliveryFee');
    let fee = parseInt(feeInput.value);

    // Validasi: Jika input kosong atau bukan angka, anggap 0
    if (isNaN(fee) || fee < 0) fee = 0;

    const total = subtotal + fee;
    
    document.getElementById('totalPrice').textContent = formatCurrency(total);
}

async function checkout() {
    const cart = getCart();
    const deliveryAddress = document.getElementById('deliveryAddress').value;
    const feeInput = document.getElementById('deliveryFee');
    const deliveryFee = parseInt(feeInput.value) || 0;
    const notes = document.getElementById('orderNotes').value;
    
    if (!deliveryAddress) {
        showError(new Error('Alamat pengiriman wajib diisi!'));
        return;
    }
    
    const restaurantId = Object.keys(cart)[0];
    const items = cart[restaurantId].items.map(item => ({
        menu_item_id: item.id,
        quantity: item.quantity
    }));
    
    try {
        await createOrder(restaurantId, deliveryAddress, items, deliveryFee, notes);
        clearCart();
        showMessage('Pesanan berhasil dibuat!', 'success');
        showOrders();
    } catch (error) {
        showError(error);
    }
}

async function checkout() {
    const cart = getCart();
    const deliveryAddress = document.getElementById('deliveryAddress').value;
    const deliveryFee = parseInt(document.getElementById('deliveryFee').value) || 0;
    if (!deliveryAddress) { showError(new Error('Alamat harus diisi')); return; }
    const restaurantId = Object.keys(cart)[0];
    const items = cart[restaurantId].items.map(item => ({ menu_item_id: item.id, quantity: item.quantity }));
    try {
        await createOrder(restaurantId, deliveryAddress, items, deliveryFee, document.getElementById('orderNotes').value);
        clearCart(); showMessage('Pesanan berhasil!', 'success'); showOrders();
    } catch (error) { showError(error); }
}

async function showOrders() {
    try {
        const result = await getMyOrders();
        const container = document.getElementById('ordersList');
        if (!result.data.length) { container.innerHTML = '<p>Belum ada pesanan</p>'; return; }
        container.innerHTML = result.data.map(order => `
            <div class="order-card" onclick="viewOrderDetail(${order.id})">
                <div class="order-card-header"><h4>Order #${order.id}</h4><span class="order-status ${order.status}">${order.status}</span></div>
                <div class="order-card-details"><p>Total: ${formatCurrency(order.total_price)}</p><p>${formatDate(order.order_date)}</p></div>
            </div>`).join('');
        showPage('ordersPage');
    } catch (error) { showError(error); }
}

async function viewOrderDetail(orderId) {
    try {
        const order = await getOrderDetail(orderId);
        document.getElementById('orderDetailId').textContent = order.id;
        const itemsHTML = order.items.map(item => `
            <div class="cart-item"><h4>${item.name}</h4><p>${formatCurrency(item.unit_price)} x ${item.quantity}</p></div>`).join('');
        let reviewHTML = '';
        if (order.status === 'delivered') reviewHTML = `<button class="btn btn-secondary" onclick="showReviewForm(${orderId})">Beri Ulasan</button>`;
        document.getElementById('orderDetailContent').innerHTML = `
            <div class="restaurant-detail">
                <div class="restaurant-detail-header"><h2>Detail Order #${order.id}</h2><span class="order-status ${order.status}">${order.status}</span></div>
                <h3>Items</h3>${itemsHTML}
                <div class="cart-summary"><div class="summary-row total"><span>Total:</span><span>${formatCurrency(order.total_price)}</span></div></div>
                ${reviewHTML}
            </div>`;
        showPage('orderDetailPage');
    } catch (error) { showError(error); }
}

function showReviewForm(orderId) {
    const rating = prompt('Rating (1-5):', '5');
    if (!rating) return;
    const comment = prompt('Ulasan Anda:');
    if (!comment) return;
    submitReview(orderId, parseInt(rating), comment);
}

async function submitReview(orderId, rating, comment) {
    try {
        await createReview(orderId, rating, comment);
        showMessage('Terima kasih!', 'success'); showOrders();
    } catch (error) { showError(error); }
}
