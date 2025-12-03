<?php
session_start();
require_once "database.php";

if (!isset($_SESSION['user_id'])) {
    echo "<script>   
    alert('Anda harus login untuk mengakses halaman ini!');
    window.location.href='index.php';
    </script>";
    exit;
}

$userId = (int)$_SESSION['user_id'];

// Query untuk mengambil data berdasarkan id_akun
$query = "SELECT * FROM topup WHERE id_akun = $userId ORDER BY tanggal DESC, id DESC";
$result = $conn->query($query);

if (!$result) {
    die("Error dalam query: " . $conn->error);
}

$row_count = $result->num_rows;
?>

<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard — BlueWaves STORE</title>
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            margin: 0;
            background: #eef3f8;
            color: #333;
        }

        header {
            background: #0a4b78;
            color: white;
            padding: 16px 22px;
            font-size: 22px;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 3px 10px rgba(0, 0, 0, .2);
        }

        .container {
            padding: 20px;
            max-width: 1200px;
            margin: auto;
        }

        h2 {
            margin-top: 0;
            color: #0a4b78;
            font-size: 26px;
            font-weight: 800;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
            margin-top: 20px;
        }

        th, td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e5e5;
            font-size: 15px;
        }

        th {
            background: #0f6fb8;
            color: white;
            text-align: left;
            font-size: 14px;
            text-transform: uppercase;
        }

        tr:hover {
            background: #f4faff;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            text-align: center;
            display: inline-block;
            min-width: 100px;
        }

        .status-waiting {
            background: #fff3cd;
            color: #856404;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .empty-state p {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .debug-info {
            background: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
            color: #666;
        }

        .success-alert {
            background: #d1fae5;
            color: #065f46;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #10b981;
        }

        .action-buttons {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 8px 12px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header>Riwayat Pesanan</header>
    <div class="container">
        <h2>Data Transaksi</h2>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-alert">
                ✅ Pesanan berhasil dibuat! Status: Menunggu Konfirmasi
            </div>
        <?php endif; ?>
        
        <!-- Debug Info -->
        <div class="debug-info">
            User ID: <?php echo $userId; ?> | Jumlah Data: <?php echo $row_count; ?>
        </div>

        <div class="action-buttons">
            <a href="tokenhok.php" class="btn">➕ Buat Pesanan Baru</a>
            <a href="check_database.php" class="btn btn-secondary">🔍 Cek Database</a>
        </div>

        <table id="transTable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Game</th>
                    <th>User ID</th>
                    <th>Server</th>
                    <th>Produk</th>
                    <th>Harga</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if ($result->num_rows > 0) {
                    $a = 1;
                    while ($row = $result->fetch_assoc()): 
                ?>
                        <tr>
                            <td><?= $a++ ?></td>
                            <td><?= htmlspecialchars($row['game']) ?></td>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['server_id']) ?></td>
                            <td><?= htmlspecialchars($row['produk']) ?></td>
                            <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                            <td><?= htmlspecialchars($row['pembayaran']) ?></td>
                            <td>
                                <?php
                                $status_class = 'status-waiting';
                                $status_text = 'Menunggu Konfirmasi';
                                
                                if ($row['status'] === 'Success') {
                                    $status_class = 'status-success';
                                    $status_text = 'Berhasil';
                                } elseif ($row['status'] === 'failed') {
                                    $status_class = 'status-failed';
                                    $status_text = 'Gagal';
                                }
                                ?>
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td><?= $row['tanggal'] ?></td>
                        </tr>
                    <?php endwhile; 
                } else { ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <p>Belum ada riwayat transaksi</p>
                                <p>Silakan melakukan topup terlebih dahulu</p>
                            </div>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>