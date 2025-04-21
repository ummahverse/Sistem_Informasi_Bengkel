<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header text-center py-4">
        <!-- Logo and Brand -->
        <div class="d-flex justify-content-center align-items-center mb-3">
            <div class="logo-circle">
                <i class="fas fa-tools"></i>
            </div>
        </div>
        <h4 class="sidebar-title">Kasir Bengkel</h4>
        
        <!-- Profile Section -->
        <div class="profile-section mt-4 mb-3">
            <div class="profile-image-wrapper">
                <img src="<?= isset($_SESSION['admin']['photo']) ? $_SESSION['admin']['photo'] : 'uploads/default.jpg' ?>" alt="Foto Profil" class="profile-image">
            </div>
            <h5 class="mt-3 profile-name"><?= htmlspecialchars($_SESSION['admin']['nama']) ?></h5>
            <span class="badge bg-primary px-3 py-2 role-badge">Admin</span>
        </div>
    </div>
    
    <div class="sidebar-divider"></div>
    
    <div class="sidebar-content px-4 py-3">
        <h6 class="sidebar-heading text-uppercase">Menu Utama</h6>
        
        <div class="nav-menu">
            <a href="dashboard.php" class="nav-link active">
                <div class="nav-link-icon">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <span>Dashboard</span>
            </a>
            
            <a href="pengguna.php" class="nav-link">
                <div class="nav-link-icon">
                    <i class="fas fa-users"></i>
                </div>
                <span>Pengguna</span>
            </a>
            
            <a href="laporan.php" class="nav-link">
                <div class="nav-link-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span>Laporan Penjualan</span>
            </a>

            <a href="laba_bersih.php" class="nav-link">
                <div class="nav-link-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <span>Laba Bersih</span>
            </a>
        </div>
        
        <div class="sidebar-divider mt-4"></div>
        
        <h6 class="sidebar-heading text-uppercase mt-4">Pengaturan</h6>
        
        <div class="nav-menu">
            <a href="profile.php" class="nav-link">
                <div class="nav-link-icon">
                    <i class="fas fa-user-cog"></i>
                </div>
                <span>Profil Saya</span>
            </a>
            
            <a href="logout.php" class="nav-link">
                <div class="nav-link-icon">
                    <i class="fas fa-sign-out-alt"></i>
                </div>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Mobile navbar toggle button -->
<div class="mobile-nav-toggle">
    <button id="sidebarToggle" class="btn btn-primary">
        <i class="fas fa-bars"></i>
    </button>
</div>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
    /* Sidebar styling */
    .sidebar {
        background-color: #ffffff;
        height: 100vh;
        position: fixed;
        top: 0;
        left: 0;
        box-shadow: 4px 0 12px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        overflow-y: auto;
        width: 280px;
        z-index: 1040;
    }
    
    /* Logo styling - Updated to purple theme */
    .logo-circle {
        background: linear-gradient(135deg, #5E35B1, #7E57C2);
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(126, 87, 194, 0.3);
    }
    
    .logo-circle i {
        color: #ffffff;
        font-size: 1.8rem;
    }
    
    .sidebar-title {
        color: #5E35B1;
        font-weight: 700;
        font-size: 1.4rem;
        margin-top: 0.5rem;
    }
    
    /* Profile section */
    .profile-section {
        padding: 0.5rem 1rem;
    }
    
    .profile-image-wrapper {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        margin: 0 auto;
        border: 3px solid #EDE7F6;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
    
    .profile-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .profile-name {
        color: #2C3E50;
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.3rem;
    }
    
    .role-badge {
        background: linear-gradient(135deg, #5E35B1, #7E57C2);
        font-size: 0.75rem;
        font-weight: 500;
        letter-spacing: 0.5px;
    }
    
    /* Divider - Updated to purple theme */
    .sidebar-divider {
        height: 1px;
        background-color: #EDE7F6;
        margin: 0.5rem 1.5rem;
    }
    
    /* Sidebar headings */
    .sidebar-heading {
        color: #6c757d;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.8px;
        margin-bottom: 1rem;
    }
    
    /* Navigation links - Updated to purple theme */
    .nav-menu {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .nav-link {
        display: flex;
        align-items: center;
        padding: 0.8rem 1rem;
        border-radius: 8px;
        color: #2C3E50;
        text-decoration: none;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    
    .nav-link:hover {
        background-color: #EDE7F6;
        color: #5E35B1;
        transform: translateX(5px);
    }
    
    .nav-link.active {
        background: linear-gradient(135deg, #5E35B1, #7E57C2);
        color: #ffffff;
        box-shadow: 0 4px 8px rgba(126, 87, 194, 0.2);
    }
    
    .nav-link-icon {
        width: 35px;
        height: 35px;
        background-color: rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.8rem;
    }
    
    .nav-link.active .nav-link-icon {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .nav-link:not(.active) .nav-link-icon {
        background-color: rgba(126, 87, 194, 0.1);
    }
    
    .nav-link i {
        font-size: 1rem;
    }
    
    /* Mobile toggle button - Updated to purple theme */
    .mobile-nav-toggle {
        display: none;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1050;
    }
    
    .mobile-nav-toggle .btn {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #5E35B1, #7E57C2);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(126, 87, 194, 0.3);
    }
    
    /* Overlay for mobile */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1030;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    /* Responsive styles */
    @media (max-width: 992px) {
        body {
            padding-left: 0 !important; /* Remove any padding for the sidebar */
        }
        
        .sidebar {
            transform: translateX(-100%);
            width: 260px;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        .mobile-nav-toggle {
            display: block;
        }
        
        .sidebar-overlay.show {
            display: block;
            opacity: 1;
        }
    }
    
    /* For smaller mobile devices */
    @media (max-width: 576px) {
        .sidebar {
            width: 240px;
        }
        
        .sidebar-content {
            padding-left: 12px;
            padding-right: 12px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    
    // Toggle sidebar on button click
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
    });
    
    // Close sidebar when clicking the overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.remove('show');
        sidebarOverlay.classList.remove('show');
    });
    
    // Close sidebar on window resize if screen becomes larger than 992px
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
});
</script>