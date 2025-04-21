<?php
include '../config.php';
session_start();

if (!isset($_GET['id'])) {
    echo "<script>alert('ID Transaksi tidak ditemukan.'); window.location='data_transaksi.php';</script>";
    exit;
}

$id = intval($_GET['id']);

// Ambil data transaksi utama
$transaksiQuery = $conn->query("SELECT * FROM transaksi WHERE id = $id");
if ($transaksiQuery->num_rows == 0) {
    echo "<script>alert('Transaksi tidak ditemukan.'); window.location='data_transaksi.php';</script>";
    exit;
}
$transaksi = $transaksiQuery->fetch_assoc();

// Ambil detail produk dalam transaksi
$detailQuery = $conn->query("
    SELECT td.*, p.nama AS nama_produk 
    FROM transaksi_detail td
    JOIN produk p ON td.produk_id = p.id
    WHERE td.transaksi_id = $id
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Transaksi #<?= $id ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-light: #e8f0fe;
            --primary-dark: #0d47a1;
            --secondary-color: #f5f5f5;
            --text-color: #333;
            --border-color: #ddd;
            --hover-color: #f5f9ff;
            --success-color: #388e3c;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            color: var(--text-color);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 16px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .btn-secondary {
            background-color: #757575;
        }
        
        .btn-secondary:hover {
            background-color: #616161;
        }
        
        .btn-success {
            background-color: var(--success-color);
        }
        
        .btn-success:hover {
            background-color: #2e7d32;
        }
        
        .back-btn {
            margin-bottom: 20px;
        }
        
        .invoice-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .invoice-header {
            background-color: var(--primary-color);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .invoice-header h2 {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
        }
        
        .invoice-number {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 5px 15px;
            font-size: 14px;
            margin-top: 8px;
            display: inline-block;
        }
        
        .invoice-body {
            padding: 30px;
        }
        
        .invoice-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 15px;
            border-left: 4px solid var(--primary-color);
            padding-left: 10px;
        }
        
        .customer-info {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .info-item {
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 500;
            color: #666;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-weight: 500;
            font-size: 16px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .products-table th {
            background-color: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            font-size: 14px;
        }
        
        .products-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }
        
        .products-table tr:last-child td {
            border-bottom: none;
        }
        
        .products-table tr:hover {
            background-color: var(--hover-color);
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-section {
            background-color: var(--primary-light);
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }
        
        .total-row {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-dark);
            padding-top: 10px;
            margin-top: 10px;
            border-top: 1px dashed var(--border-color);
        }
        
        .invoice-footer {
            display: flex;
            justify-content: space-between;
            padding: 20px 30px;
            background-color: #f9f9f9;
            border-top: 1px solid var(--border-color);
        }
        
        .payment-badge {
            display: inline-flex;
            align-items: center;
            background-color: var(--primary-light);
            color: var(--primary-dark);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .payment-badge i {
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .customer-info {
                grid-template-columns: 1fr;
            }
            
            .invoice-footer {
                flex-direction: column;
                gap: 15px;
            }
            
            .products-table {
                display: block;
                overflow-x: auto;
                white-space: nowrap;
            }
        }
        
        @media print {
            .back-btn, .print-btn {
                display: none;
            }
            
            body {
                background-color: white;
                padding: 0;
                margin: 0;
            }
            
            .container {
                width: 100%;
                max-width: none;
            }
            
            .invoice-container {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="laporan.php" class="btn btn-secondary back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
        
        <div class="invoice-container">
            <div class="invoice-header">
                <h2>Detail Transaksi</h2>
                <div class="invoice-number">Invoice #<?= $id ?></div>
            </div>
            
            <div class="invoice-body">
                <div class="invoice-section">
                    <h3 class="section-title">Informasi Transaksi</h3>
                    <div class="customer-info">
                        <div class="info-item">
                            <span class="info-label">Tanggal Transaksi</span>
                            <span class="info-value">
                                <i class="far fa-calendar-alt"></i> 
                                <?= date('d F Y', strtotime($transaksi['tanggal'])) ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Kasir</span>
                            <span class="info-value">
                                <i class="fas fa-user"></i> 
                                <?= $transaksi['kasir'] ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Nama Customer</span>
                            <span class="info-value">
                                <i class="fas fa-user-circle"></i> 
                                <?= htmlspecialchars($transaksi['nama_customer']) ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">No. WhatsApp</span>
                            <span class="info-value">
                                <i class="fab fa-whatsapp" style="color: #25D366;"></i> 
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $transaksi['no_whatsapp']) ?>" target="_blank" style="color: inherit; text-decoration: none;">
                                    <?= $transaksi['no_whatsapp'] ?>
                                </a>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Alamat</span>
                            <span class="info-value">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?= $transaksi['alamat'] ?>
                            </span>
                        </div>
                        
                        <div class="info-item">
                            <span class="info-label">Plat Nomor</span>
                            <span class="info-value">
                                <i class="fas fa-motorcycle"></i> 
                                <?= strtoupper($transaksi['plat_nomor_motor']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="invoice-section">
                    <h3 class="section-title">Detail Produk</h3>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th width="40%">Nama Produk</th>
                                <th width="15%">Jumlah</th>
                                <th width="20%">Harga</th>
                                <th width="20%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $no = 1;
                            $total = 0;
                            if ($detailQuery->num_rows > 0):
                                while ($row = $detailQuery->fetch_assoc()):
                                    $total += $row['subtotal'];
                            ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td><?= $row['nama_produk'] ?></td>
                                <td class="text-center"><?= $row['jumlah'] ?></td>
                                <td class="text-right">Rp <?= number_format($row['harga_satuan'], 0, ',', '.') ?></td>
                                <td class="text-right">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                            </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data produk</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    
                    <div class="summary-section">
                        <div class="summary-row total-row">
                            <span>Total Pembayaran</span>
                            <span>Rp <?= number_format($transaksi['total'], 0, ',', '.') ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="invoice-footer">
                <div class="payment-info">
                    <div class="payment-badge">
                        <i class="fas fa-money-bill-wave"></i>
                        Metode Pembayaran: <?= $transaksi['metode_pembayaran'] ?? 'Cash' ?>
                    </div>
                </div>
                
            
            </div>
        </div>
    </div>
    
    <script>
        // Add current year to footer
        document.addEventListener('DOMContentLoaded', function() {
            const year = new Date().getFullYear();
            if (document.querySelector('.year')) {
                document.querySelector('.year').textContent = year;
            }
        });
    </script>
</body>
</html>