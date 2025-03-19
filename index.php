<?php
// Database connection
$host = "localhost";
$dbname = "manajemen_pupuk";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Define a constant password
define('PREDEFINED_PASSWORD', '123'); // Set your desired password here

// Function to add a new fertilizer
function addPupuk($nama, $harga, $stok) {
    global $pdo;
    $sql = "INSERT INTO pupuk (nama, harga, stok) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nama, $harga, $stok]);
}

// Function to add a sale transaction
function addPenjualan($pupuk_id, $jumlah, $harga, $total, $supir, $tanggal) {
    global $pdo;
    $sql = "INSERT INTO penjualan (pupuk_id, jumlah, harga, total, supir, tanggal) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$pupuk_id, $jumlah, $harga, $total, $supir, $tanggal]);
    
    // Update stock in the pupuk table
    $sql = "UPDATE pupuk SET stok = stok - ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$jumlah, $pupuk_id]);
}

// Function to get all fertilizers
function getAllPupuk() {
    global $pdo;
    $sql = "SELECT * FROM pupuk";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get all sales transactions
function getAllPenjualan() {
    global $pdo;
    $sql = "SELECT penjualan.*, pupuk.nama FROM penjualan JOIN pupuk ON penjualan.pupuk_id = pupuk.id";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to reset the sales table
function resetPenjualan() {
    global $pdo;
    $sql = "DELETE FROM penjualan";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

// Function to delete fertilizer and related sales
function deletePupuk($id) {
    global $pdo;

    // First, delete related sales
    $sql = "DELETE FROM penjualan WHERE pupuk_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    // Then, delete the fertilizer
    $sql = "DELETE FROM pupuk WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
}

// Handling form submissions for adding fertilizers and sales
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_pupuk'])) {
        $nama = $_POST['nama_pupuk'];
        $harga = $_POST['harga'];
        $stok = $_POST['stok'];
        addPupuk($nama, $harga, $stok);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
        exit();
    } elseif (isset($_POST['add_penjualan'])) {
        $pupuk_id = $_POST['pupuk_id'];
        $jumlah = $_POST['jumlah'];
        
        // Fetch the price of the selected fertilizer
        $sql = "SELECT harga FROM pupuk WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pupuk_id]);
        $pupuk = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check if the fertilizer exists
        if ($pupuk) {
            $harga = $pupuk['harga'];
            $total = $harga * $jumlah; // Calculate total
            
            $supir = $_POST['supir'];
            $tanggal = $_POST['tanggal'];
            addPenjualan($pupuk_id, $jumlah, $harga, $total, $supir, $tanggal); // Pass harga to the function
            header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
            exit();
        } else {
            echo "Pupuk tidak ditemukan.";
        }
    } elseif (isset($_POST['reset_penjualan'])) {
        // Check password
        $password = $_POST['password'];
        if ($password === PREDEFINED_PASSWORD) {
            resetPenjualan();
            echo "Tabel penjualan telah direset.";
            header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
            exit();
        } else {
            echo "Password salah.";
        }
    } elseif (isset($_POST['restok_pupuk'])) {
        $pupuk_id = $_POST['pupuk_id_restock'];
        $stok = $_POST['stok_restock'];
        
        // Update stock in the pupuk table
        $sql = "UPDATE pupuk SET stok = stok + ? WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$stok, $pupuk_id]);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
        exit();
    }
}

// Fetch all fertilizers and sales for display
$pupukList = getAllPupuk();
$penjualanList = getAllPenjualan();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pupuk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
</head>

<body>
    <div class="container py-4">
        <h1 class="text-center">Manajemen Pupuk</h1>

        <!-- Form Input Pupuk -->
        <div class="my-4">
            <h3>Tambah Pupuk</h3>
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="namaPupuk" class="form-label">Nama Pupuk</label>
                        <input type="text" class="form-control" name="nama_pupuk" required placeholder="Nama Pupuk">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="harga" class="form-label">Harga (IDR)</label>
                        <input type="number" class="form-control" name="harga" required placeholder="Harga Pupuk">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="stok" class="form-label">Stok</label>
                        <input type="number" class="form-control" name="stok" required placeholder="Stok Pupuk">
                    </div>
                </div>
                <button class="btn btn-primary" name="add_pupuk" type="submit">Tambah Pupuk</button>
            </form>
        </div>

        <!-- Tabel Barang -->
        <h3>Tabel Barang</h3>
        <table id="tabelBarang" class="table table-striped table-responsive">
            <thead>
                <tr>
                    <th>Nama Pupuk</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Total Harga Stok</th>
                    <th>Hapus</th>
                    <th>Restok</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pupukList as $pupuk): ?>
                <tr>
                    <td><?= htmlspecialchars($pupuk['nama']) ?></td>
                    <td><?= number_format($pupuk['harga'], 0, ',', '.') ?></td>
                    <td><?= $pupuk['stok'] ?></td>
                    <td><?= number_format($pupuk['harga'] * $pupuk['stok'], 0, ',', '.') ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pupuk_id_delete" value="<?= $pupuk['id'] ?>">
                            <button class="btn btn-danger" name="delete_pupuk" type="submit">Hapus</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="pupuk_id_restock" value="<?= $pupuk['id'] ?>">
                            <input type="number" name="stok_restock " required min="1" placeholder="Jumlah" style=" width: 80px;">
                            <button class="btn btn-warning" name="restok_pupuk" type="submit">Restok</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Kasir (Barang yang Diambil) -->
        <div class="my-4">
            <h3>Barang yang Diambil</h3>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="pupukKasir" class="form-label">Nama Pupuk</label>
                        <select class="form-control" name="pupuk_id" required>
                            <option value="">Pilih Pupuk</option>
                            <?php foreach ($pupukList as $pupuk): ?>
                            <option value="<?= $pupuk['id'] ?>"><?= $pupuk['nama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="jumlah" class="form-label">Jumlah</label>
                        <input type="number" class="form-control" name="jumlah" required min="1">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="namaSupir" class="form-label">Nama Supir</label>
                        <input type="text" class="form-control" name="supir" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="tanggalPengambilan" class="form-label">Tanggal Pengambilan</label>
                        <input type="date" class="form-control" name="tanggal" required>
                    </div>
                </div>
                <button class="btn btn-success" name="add_penjualan" type="submit">Proses Penjualan</button>
            </form>
        </div>

        <!-- Tabel Penjualan -->
        <h3>Tabel Penjualan</h3>
        <table id="tabelPenjualan" class="table table-striped table-responsive">
            <thead>
                <tr>
                    <th>Nama Pupuk</th>
                    <th>Harga</th>
                    <th>Jumlah</th>
                    <th>Total</th>
                    <th>Supir</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($penjualanList as $penjualan): ?>
                <tr>
                    <td><?= htmlspecialchars($penjualan['nama']) ?></td>
                    <td><?= number_format($penjualan['harga'], 0, ',', '.') ?></td>
                    <td><?= $penjualan['jumlah'] ?></td>
                    <td><?= number_format($penjualan['total'], 0, ',', '.') ?></td>
                    <td><?= htmlspecialchars($penjualan['supir']) ?></td>
                    <td><?= $penjualan['tanggal'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Total Penjualan -->
        <div class="my-4">
            <h3>Total Penjualan: 
                <?php
                $totalPenjualan = 0;
                foreach ($penjualanList as $penjualan) {
                    $totalPenjualan += $penjualan['total'];
                }
                echo number_format($totalPenjualan, 0, ',', '.');
                ?> IDR
            </h3>
        </div>
       
        <!-- Reset Tabel Penjualan -->
        <div class="my-4">
            <h3>Reset Tabel Penjualan</h3>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#resetModal">Reset Penjualan</button>
        </div>

        <!-- Modal untuk Reset Penjualan -->
        <div class="modal fade" id="resetModal" tabindex="-1" aria-labelledby="resetModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="resetModalLabel">Reset Tabel Penjualan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="password" class="form-label">Masukkan Password</label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <button class="btn btn-danger " name="reset_penjualan" type="submit">Reset Penjualan</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <?php
    // Handle delete fertilizer action
    if (isset($_POST['delete_pupuk'])) {
        $id = $_POST['pupuk_id_delete'];
        deletePupuk($id);
        header("Location: " . $_SERVER['PHP_SELF']); // Refresh the page
        exit();
    }
    ?>

    <script>
        $(document).ready(function() {
            // Initialize DataTable for Tabel Barang
            $('#tabelBarang').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "lengthChange": true,
            });

            // Initialize DataTable for Tabel Penjualan
            $('#tabelPenjualan').DataTable({
                "paging": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "lengthChange": true,
            });
        });
    </script>
</body>
</html>