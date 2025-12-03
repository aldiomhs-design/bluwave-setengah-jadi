<?php
require_once "database.php";
session_start();

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>   
    alert('Akses ditolak! Halaman ini hanya untuk admin.');
    window.location.href='index.php';
    </script>";
    exit;
}

// Tampilkan pesan dari session jika ada
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
} else {
    $message = '';
    $message_type = '';
}

// Handle konfirmasi dan tolak
if (isset($_POST['action'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    
    if ($action === 'confirm') {
        $new_status = 'success';
        $_SESSION['message'] = "Pesanan #$order_id berhasil dikonfirmasi!";
        $_SESSION['message_type'] = 'success';
    } elseif ($action === 'reject') {
        $new_status = 'failed';
        $_SESSION['message'] = "Pesanan #$order_id telah ditolak!";
        $_SESSION['message_type'] = 'success';
    }
    
    $stmt = $conn->prepare("UPDATE topup SET status = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_status, $order_id);
        
        if ($stmt->execute()) {
            // Redirect untuk menghindari resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            $_SESSION['message'] = "Gagal mengupdate status!";
            $_SESSION['message_type'] = 'error';
            header("Location: " . $_SERVER['PHP_SELF']);
        }
        exit();
    } else {
        $_SESSION['message'] = "Error preparing statement: " . $conn->error;
        $_SESSION['message_type'] = 'error';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Ambil semua data pesanan dengan error handling
$result = $conn->query("SELECT * FROM topup ORDER BY tanggal DESC");
if ($result === false) {
    
    if ($conn->query($create_table) === TRUE) {
        $result = $conn->query("SELECT * FROM topup ORDER BY tanggal DESC");
    } else {
        die("Error creating topup table: " . $conn->error);
    }
}
$stmt->close();
?>
<!doctype html>
<html lang="id">
    
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Dashboard — BlueWaves STORE</title>
        
        <style>
            :root {
                --primary: #2563eb;
                --primary-dark: #1d4ed8;
                --success: #10b981;
                --warning: #f59e0b;
                --danger: #ef4444;
                --gray: #64748b;
                --gray-light: #e2e8f0;
                --radius: 8px;
            }

        body {
            font-family: 'Inter', system-ui, Arial, sans-serif;
            margin: 0;
            background: #f8fafc;
            color: #1e293b;
        }

        header {
            background: linear-gradient(135deg, #0a4b78 0%, #0f6fb8 100%);
            color: white;
            padding: 1rem 2rem;
            font-size: 1.5rem;
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-info {
            font-size: 1rem;
            font-weight: 500;
            opacity: 0.9;
        }

        .container {
            padding: 2rem;
            max-width: 1400px;
            margin: auto;
        }

        .page-title {
            color: #0a4b78;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title::before {
            content: "⚙";
            font-size: 2rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-left: 4px solid var(--primary);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #0a4b78;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--gray);
            font-weight: 600;
        }

        .table-container {
            background: white;
            border-radius: var(--radius);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            font-size: 0.9rem;
        }

        th {
            background: #f1f5f9;
            color: #475569;
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background: #f8fafc;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
            text-align: center;
            display: inline-block;
            min-width: 100px;
        }

        .status-waiting {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .status-failed {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            min-width: 120px;
        }

        .action-form {
            margin: 0;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: var(--radius);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
            width: 100%;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #0da271;
            transform: translateY(-1px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .status-final {
            padding: 0.5rem;
            border-radius: var(--radius);
            font-weight: 600;
            font-size: 0.8rem;
            text-align: center;
            background: #f1f5f9;
            color: var(--gray);
            display: block;
        }

        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .logout-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .bukti-bayar {
            max-width: 100px;
            max-height: 100px;
            border-radius: var(--radius);
            cursor: pointer;
            transition: transform 0.2s;
        }

        .bukti-bayar:hover {
            transform: scale(1.5);
        }

        /* Modal untuk preview gambar */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            max-width: 90%;
            max-height: 90%;
            border-radius: var(--radius);
        }

        .close {
            position: absolute;
            top: 20px;
            right: 30px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--gray);
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: row;
                gap: 0.25rem;
            }
            
            .action-buttons .btn {
                padding: 0.4rem 0.6rem;
                font-size: 0.7rem;
            }
        }
    </style>
</head>

<body>

    <header>
        <div>Admin Dashboard — BlueWaves</div>
        <div class="admin-info">
            Admin: <?= htmlspecialchars($_SESSION['username'] ?? 'Administrator') ?>
            <a href="logout.php" class="logout-btn" style="margin-left: 1rem; text-decoration: none;">Logout</a>
        </div>
    </header>

    <div class="container">
        <div class="page-title">Manajemen Pesanan</div>

        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="stats-container">
            <?php
            // Hitung statistik dengan error handling
            $total_query = $conn->query("SELECT COUNT(*) as total FROM topup");
            $waiting_query = $conn->query("SELECT COUNT(*) as total FROM topup WHERE status = 'waiting'");
            $success_query = $conn->query("SELECT COUNT(*) as total FROM topup WHERE status = 'success'");
            $failed_query = $conn->query("SELECT COUNT(*) as total FROM topup WHERE status = 'failed'");
            
            $total_orders = $total_query ? $total_query->fetch_assoc()['total'] : 0;
            $waiting_orders = $waiting_query ? $waiting_query->fetch_assoc()['total'] : 0;
            $success_orders = $success_query ? $success_query->fetch_assoc()['total'] : 0;
            $failed_orders = $failed_query ? $failed_query->fetch_assoc()['total'] : 0;
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?= $total_orders ?></div>
                <div class="stat-label">Total Pesanan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $waiting_orders ?></div>
                <div class="stat-label">Menunggu Konfirmasi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $success_orders ?></div>
                <div class="stat-label">Berhasil</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $failed_orders ?></div>
                <div class="stat-label">Gagal</div>
            </div>
        </div>

        <!-- Tabel Pesanan -->
        <div class="table-container">
            <?php if ($result && $result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Game</th>
                            <th>User ID</th>
                            <th>Server</th>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Payment</th>
                            <th>Bukti Bayar</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = 1; ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($row['game']) ?></td>
                                <td><?= htmlspecialchars($row['user_id']) ?></td>
                                <td><?= htmlspecialchars($row['server_id']) ?></td>
                                <td><?= htmlspecialchars($row['produk']) ?></td>
                                <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                                <td><?= htmlspecialchars($row['pembayaran']) ?></td>
                                <td>
                                    <?php if (!empty($row['bukti_bayar'])): ?>
                                        <img src="<?= htmlspecialchars($row['bukti_bayar']) ?>" 
                                             alt="Bukti Bayar" 
                                             class="bukti-bayar"
                                             onclick="openModal('<?= htmlspecialchars($row['bukti_bayar']) ?>')">
                                    <?php else: ?>
                                        <span style="color: var(--gray);">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'status-waiting';
                                    $status_text = 'Menunggu';
                                    
                                    if ($row['status'] === 'success') {
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
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($row['status'] === 'waiting'): ?>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="confirm">
                                                <button type="submit" class="btn btn-success" onclick="return confirm('Konfirmasi pesanan #<?= $row['id'] ?>?')">
                                                    ✓ Konfirmasi
                                                </button>
                                            </form>
                                            <form method="POST" class="action-form">
                                                <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-danger" onclick="return confirm('Tolak pesanan #<?= $row['id'] ?>?')">
                                                    ✗ Tolak
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="status-final">
                                                <?= $row['status'] === 'success' ? 'Terkonfirmasi' : 'Ditolak' ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Belum ada data pesanan</h3>
                    <p>Tabel topup kosong atau belum ada transaksi.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal untuk preview gambar -->
    <div id="imageModal" class="modal">
        <span class="close" onclick="closeModal()">&times;</span>
        <img class="modal-content" id="modalImage">
    </div>

    <script>
        function openModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // Close modal ketika klik di luar gambar
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

</body>

</html>