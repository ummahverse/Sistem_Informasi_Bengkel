<?php
session_start();

// Check if logged in and admin
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../config.php'; // Database connection

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['message'] = "ID Produk tidak ditemukan!";
    $_SESSION['alert_type'] = "danger";
    header("Location: produk.php");
    exit();
}

$id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch product data
$query = "SELECT * FROM produk WHERE id = '$id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['message'] = "Produk tidak ditemukan!";
    $_SESSION['alert_type'] = "danger";
    header("Location: produk.php");
    exit();
}

$produk = mysqli_fetch_assoc($result);

// Fetch categories for dropdown
$kategori_query = "SELECT id, nama_kategori FROM kategori ORDER BY nama_kategori ASC";
$kategori_result = mysqli_query($conn, $kategori_query);

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $kategori_id = mysqli_real_escape_string($conn, $_POST['kategori_id']);
    $harga_beli = mysqli_real_escape_string($conn, str_replace('.', '', $_POST['harga_beli']));
    $harga_jual = mysqli_real_escape_string($conn, str_replace('.', '', $_POST['harga_jual']));
    $stok = mysqli_real_escape_string($conn, $_POST['stok']);
    
    // Default kategori_id to NULL if "Tidak Terkategori" (0) is selected
    if ($kategori_id == "0") {
        $kategori_id = "NULL";
    } else {
        $kategori_id = "'$kategori_id'";
    }
    
    // Update query - removed deskripsi and satuan
    $update_query = "UPDATE produk 
                     SET nama = '$nama', 
                         kategori_id = $kategori_id, 
                         harga_beli = '$harga_beli', 
                         harga_jual = '$harga_jual', 
                         stok = '$stok'
                     WHERE id = '$id'";
    
    if (mysqli_query($conn, $update_query)) {
        $_SESSION['message'] = "Produk berhasil diperbarui!";
        $_SESSION['alert_type'] = "success";
        header("Location: produk.php");
        exit();
    } else {
        $_SESSION['message'] = "Gagal memperbarui produk: " . mysqli_error($conn);
        $_SESSION['alert_type'] = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Produk - Kasir Bengkel</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-blue: #1565C0;
      --secondary-blue: #42A5F5;
      --light-blue: #E3F2FD;
      --accent-blue: #0D47A1;
      --white: #ffffff;
      --light-gray: #f8f9fa;
      --text-dark: #2C3E50;
    }
    
    body {
      background-color: var(--light-gray);
      font-family: 'Poppins', 'Arial', sans-serif;
      color: var(--text-dark);
    }
    
    /* Navbar Styling */
    .navbar {
      background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
      border: none;
      padding: 0.8rem 1.5rem;
    }
    
    .navbar-brand {
      font-weight: 700;
      font-size: 1.5rem;
      color: var(--white);
      letter-spacing: 0.5px;
    }
    
    /* Content area */
    .content {
      margin-left: 280px;
      padding: 20px;
      background-color: var(--light-gray);
      min-height: 100vh;
    }
    
    /* Page header section */
    .page-header {
      background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
      border-radius: 15px;
      padding: 2rem;
      color: var(--white);
      margin-bottom: 2rem;
      box-shadow: 0 6px 18px rgba(0, 123, 255, 0.15);
    }
    
    .page-header h1 {
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    
    /* Data card */
    .data-card {
      border: none;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
      background-color: var(--white);
      padding: 1.5rem;
    }
    
    .card-header-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding-bottom: 1rem;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--light-blue);
    }
    
    .card-title {
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--primary-blue);
      margin-bottom: 0;
    }
    
    /* Button styling */
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-blue), var(--secondary-blue));
      border: none;
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(21, 101, 192, 0.2);
    }
    
    .btn-primary:hover {
      background: linear-gradient(135deg, var(--accent-blue), var(--primary-blue));
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(21, 101, 192, 0.3);
    }
    
    .btn-outline-primary {
      color: var(--primary-blue);
      border-color: var(--primary-blue);
      border-radius: 8px;
      font-weight: 500;
      transition: all 0.3s ease;
    }
    
    .btn-outline-primary:hover {
      background-color: var(--primary-blue);
      color: var(--white);
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(21, 101, 192, 0.2);
    }
    
    .btn-secondary {
      border-radius: 8px;
      transition: all 0.3s ease;
    }
    
    .btn-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(108, 117, 125, 0.2);
    }
    
    /* Form styling */
    .form-label {
      font-weight: 500;
      color: var(--text-dark);
      margin-bottom: 0.5rem;
    }
    
    .form-control, .form-select {
      border-radius: 8px;
      border: 1px solid #e0e6ed;
      padding: 0.75rem 1rem;
      transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
      border-color: var(--secondary-blue);
      box-shadow: 0 0 0 0.25rem rgba(66, 165, 245, 0.15);
    }
    
    .form-text {
      color: #6c757d;
      font-size: 0.875rem;
    }
    
    /* Form section spacing */
    .form-section {
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid #e9ecef;
    }
    
    .form-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
      padding-bottom: 0;
    }
    
    /* Alerts */
    .alert {
      border-radius: 10px;
      border: none;
      padding: 1rem 1.5rem;
      margin-bottom: 2rem;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .alert-success {
      background-color: #E8F5E9;
      color: #1B5E20;
    }
    
    .alert-danger {
      background-color: #FFEBEE;
      color: #B71C1C;
    }
    
    /* Input group styling */
    .input-group-text {
      background-color: var(--light-blue);
      color: var(--primary-blue);
      border: 1px solid #e0e6ed;
      border-radius: 8px 0 0 8px;
    }
    
    /* Required field indicator */
    .required-field::after {
      content: " *";
      color: #dc3545;
    }
    
    /* Responsive media queries */
    @media (max-width: 992px) {
      .content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <?php include 'sidebar.php'; ?>

  <!-- Content -->
  <div class="content">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
      <div class="container-fluid">
        <span class="navbar-brand">
          <i class="fas fa-edit me-2"></i>
          Edit Produk
        </span>
        <div class="d-flex align-items-center">
          <span class="text-white me-3">
            <i class="fas fa-user-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['nama']) ?>
          </span>
          <a href="profile.php" class="btn btn-outline-light btn-sm me-2">
            <i class="fas fa-user-edit me-1"></i> Profil
          </a>
          <a href="logout.php" class="btn btn-outline-light btn-sm">
            <i class="fas fa-sign-out-alt me-1"></i> Logout
          </a>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <!-- Page Header -->
      <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1><i class="fas fa-edit me-2"></i>Edit Produk</h1>
            <p class="lead mb-0">Perbarui informasi untuk produk: <strong><?= htmlspecialchars($produk['nama']) ?></strong></p>
          </div>
          <a href="produk.php" class="btn btn-light">
            <i class="fas fa-arrow-left me-2"></i>
            Kembali ke Daftar Produk
          </a>
        </div>
      </div>

      <!-- Alert Messages -->
      <?php if (isset($_SESSION['message'])): ?>
      <div class="alert alert-<?= $_SESSION['alert_type'] ?> alert-dismissible fade show" role="alert">
        <i class="fas fa-<?= $_SESSION['alert_type'] == 'success' ? 'check-circle' : 'exclamation-circle' ?> me-2"></i>
        <?= $_SESSION['message'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
      <?php
        unset($_SESSION['message']);
        unset($_SESSION['alert_type']);
      endif;
      ?>

      <!-- Edit Product Form Card -->
      <div class="row">
        <div class="col-lg-12">
          <div class="data-card mb-4">
            <div class="card-header-actions">
              <h5 class="card-title">
                <i class="fas fa-clipboard-list me-2"></i>
                Formulir Edit Produk
              </h5>
            </div>
            
            <form method="POST" action="">
              <!-- Basic Information -->
              <div class="form-section">
                <h6 class="text-primary mb-3"><i class="fas fa-info-circle me-2"></i>Informasi Dasar</h6>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="nama" class="form-label required-field">Nama Produk</label>
                    <input type="text" class="form-control" id="nama" name="nama" value="<?= htmlspecialchars($produk['nama']) ?>" required>
                    <div class="form-text">Masukkan nama produk yang jelas dan mudah dikenali.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="kategori_id" class="form-label">Kategori</label>
                    <select class="form-select" id="kategori_id" name="kategori_id">
                      <option value="0" <?= is_null($produk['kategori_id']) ? 'selected' : '' ?>>-- Tidak Terkategori --</option>
                      <?php 
                      mysqli_data_seek($kategori_result, 0);
                      while ($kategori = mysqli_fetch_assoc($kategori_result)): 
                      ?>
                        <option value="<?= $kategori['id'] ?>" <?= $produk['kategori_id'] == $kategori['id'] ? 'selected' : '' ?>>
                          <?= htmlspecialchars($kategori['nama_kategori']) ?>
                        </option>
                      <?php endwhile; ?>
                    </select>
                    <div class="form-text">Pilih kategori atau biarkan tidak terkategori.</div>
                  </div>
                </div>
              </div>
              
              <!-- Price Information -->
              <div class="form-section">
                <h6 class="text-primary mb-3"><i class="fas fa-tag me-2"></i>Informasi Harga</h6>
                <div class="row mb-3">
                  <div class="col-md-6">
                    <label for="harga_beli" class="form-label required-field">Harga Beli</label>
                    <div class="input-group">
                      <span class="input-group-text">Rp</span>
                      <input type="text" class="form-control currency" id="harga_beli" name="harga_beli" 
                             value="<?= number_format($produk['harga_beli'], 0, ',', '.') ?>" required>
                    </div>
                    <div class="form-text">Harga pembelian dari pemasok/distributor.</div>
                  </div>
                  <div class="col-md-6">
                    <label for="harga_jual" class="form-label required-field">Harga Jual</label>
                    <div class="input-group">
                      <span class="input-group-text">Rp</span>
                      <input type="text" class="form-control currency" id="harga_jual" name="harga_jual" 
                             value="<?= number_format($produk['harga_jual'], 0, ',', '.') ?>" required>
                    </div>
                    <div class="form-text">Harga penjualan ke pelanggan.</div>
                  </div>
                </div>
              </div>
              
              <!-- Stock Information -->
              <div class="form-section">
                <h6 class="text-primary mb-3"><i class="fas fa-cubes me-2"></i>Informasi Stok</h6>
                <div class="row">
                  <div class="col-md-6">
                    <label for="stok" class="form-label required-field">Jumlah Stok</label>
                    <input type="number" class="form-control" id="stok" name="stok" min="0" 
                           value="<?= htmlspecialchars($produk['stok']) ?>" required>
                    <div class="form-text">Perbarui jumlah stok yang tersedia.</div>
                  </div>
                </div>
              </div>
              
              <!-- Submit Buttons -->
              <div class="d-flex justify-content-end gap-2 mt-4">
                <a href="produk.php" class="btn btn-secondary">
                  <i class="fas fa-times me-1"></i> Batal
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-1"></i> Simpan Perubahan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
  
  <script>
    $(document).ready(function() {
      // Auto close alerts after 5 seconds
      setTimeout(function() {
        $(".alert").alert('close');
      }, 5000);
      
      // Format currency inputs
      $(".currency").on("input", function() {
        // Remove non-digit characters
        var value = $(this).val().replace(/\D/g, "");
        
        // Add thousand separators
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        
        $(this).val(value);
      });
      
      // Calculate profit margin when price inputs change
      $("#harga_beli, #harga_jual").on("input", function() {
        var harga_beli = $("#harga_beli").val().replace(/\./g, "");
        var harga_jual = $("#harga_jual").val().replace(/\./g, "");
        
        harga_beli = parseInt(harga_beli) || 0;
        harga_jual = parseInt(harga_jual) || 0;
        
        if (harga_beli > 0 && harga_jual > 0) {
          var profit = harga_jual - harga_beli;
          var margin = (profit / harga_beli * 100).toFixed(2);
          
          $("#profit-info").remove();
          
          if (harga_jual >= harga_beli) {
            $("#harga_jual").after('<div id="profit-info" class="form-text text-success mt-2">Margin keuntungan: ' + margin + '%</div>');
          } else {
            $("#harga_jual").after('<div id="profit-info" class="form-text text-danger mt-2">Perhatian: Harga jual lebih rendah dari harga beli!</div>');
          }
        } else {
          $("#profit-info").remove();
        }
      });
      
      // Trigger margin calculation on page load
      $("#harga_jual").trigger("input");
    });
  </script>
</body>
</html>