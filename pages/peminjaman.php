<?php
$member_id = '';
$book_id = '';
$borrow_date_tambah = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_add_borrowing'])) {
    $member_id = (int)$_POST['member_id'];
    $book_id = (int)$_POST['book_id'];
    $borrow_date = $_POST['borrow_date'];

    mysqli_begin_transaction($koneksi);
    try {
        $query1 = "INSERT INTO borrowings (member_id, borrow_date) VALUES ($member_id, '$borrow_date')";
        if (!mysqli_query($koneksi, $query1)) throw new Exception("Gagal menyimpan data peminjaman.");
        $borrowing_id = mysqli_insert_id($koneksi);

        $quantity = 1;
        $query2 = "INSERT INTO borrowing_details (borrowing_id, book_id, quantity) VALUES ($borrowing_id, $book_id, $quantity)";
        if (!mysqli_query($koneksi, $query2)) throw new Exception("Gagal menyimpan detail peminjaman.");

        $query3 = "UPDATE books SET stock = stock - $quantity WHERE id = $book_id";
        if (!mysqli_query($koneksi, $query3)) throw new Exception("Gagal mengurangi stok buku.");

        mysqli_commit($koneksi);
        $_SESSION['message'] = 'Peminjaman berhasil dicatat!';
        $_SESSION['message_type'] = 'success';
    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['message'] = 'Terjadi kesalahan: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php?page=peminjaman");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_return'])) {
    $borrowing_id = (int)$_POST['borrowing_id'];
    $return_date = $_POST['return_date'];
    $borrow_date = $_POST['borrow_date'];

    $tarif_denda_per_hari = 1000;
    $batas_hari_pinjam = 7;
    $date1 = date_create($borrow_date);
    $date2 = date_create($return_date);
    $diff = date_diff($date1, $date2);
    $lama_pinjam = $diff->days;
    
    $hari_telat = max(0, $lama_pinjam - $batas_hari_pinjam);
    $late_fee = $hari_telat * $tarif_denda_per_hari;

    mysqli_begin_transaction($koneksi);
    try {
        $query_return = "INSERT INTO returns (borrowing_id, return_date, late_fee) VALUES ($borrowing_id, '$return_date', $late_fee)";
        if (!mysqli_query($koneksi, $query_return)) throw new Exception("Gagal mencatat pengembalian.");

        $detail_q = mysqli_query($koneksi, "SELECT book_id, quantity FROM borrowing_details WHERE borrowing_id = $borrowing_id");
        if ($detail = mysqli_fetch_assoc($detail_q)) {
            $book_id = $detail['book_id'];
            $quantity = $detail['quantity'];
            $update_stock_q = "UPDATE books SET stock = stock + $quantity WHERE id = $book_id";
            if (!mysqli_query($koneksi, $update_stock_q)) throw new Exception("Gagal mengembalikan stok.");
        } else {
            throw new Exception("Detail peminjaman tidak ditemukan.");
        }
        
        mysqli_commit($koneksi);
        $_SESSION['message'] = 'Buku berhasil dikembalikan!';
        $_SESSION['message_type'] = 'success';

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        $_SESSION['message'] = 'Gagal memproses pengembalian: ' . $e->getMessage();
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php?page=peminjaman");
    exit();
}

$is_return_form = false;
if (isset($_GET['action']) && $_GET['action'] == 'return' && isset($_GET['id'])) {
    $is_return_form = true;
    $return_borrowing_id = (int)$_GET['id'];
    $return_q = mysqli_query($koneksi, "SELECT br.id, m.full_name, b.title as book_title, br.borrow_date 
                                        FROM borrowings br
                                        JOIN members m ON br.member_id = m.id
                                        JOIN borrowing_details bd ON br.id = bd.borrowing_id
                                        JOIN books b ON bd.book_id = b.id
                                        WHERE br.id = $return_borrowing_id");
    $return_data = mysqli_fetch_assoc($return_q);
}

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

?>

<?php if ($is_return_form && $return_data) : ?>
<div class="bg-white p-8 rounded-lg shadow-md mb-8 border-l-4 border-purple-500">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Form Pengembalian Buku</h2>
    <form action="index.php?page=peminjaman" method="POST">
        <input type="hidden" name="process_return" value="1">
        <input type="hidden" name="borrowing_id" value="<?php echo $return_data['id']; ?>">
        <input type="hidden" id="borrow_date_hidden" name="borrow_date" value="<?php echo $return_data['borrow_date']; ?>">

        <div class="mb-4">
            <p class="text-sm text-gray-600">Nama Peminjam: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($return_data['full_name']); ?></span></p>
            <p class="text-sm text-gray-600">Buku: <span class="font-semibold text-gray-900">"<?php echo htmlspecialchars($return_data['book_title']); ?>"</span></p>
            <p class="text-sm text-gray-600">Tgl Pinjam: <span class="font-semibold text-gray-900"><?php echo date('d M Y', strtotime($return_data['borrow_date'])); ?></span></p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div>
                <label for="return_date_input" class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                <input type="date" id="return_date_input" name="return_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
            </div>
            <div>
                <p class="text-sm font-medium text-gray-700">Perkiraan Denda</p>
                <p id="perkiraan_denda" class="mt-1 text-2xl font-bold text-red-600">Rp 0</p>
            </div>
        </div>
        
        <div class="mt-6 flex items-center space-x-4">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                Proses Pengembalian
            </button>
            <a href="index.php?page=peminjaman" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Batal
            </a>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const returnDateInput = document.getElementById('return_date_input');
            const borrowDateValue = document.getElementById('borrow_date_hidden').value;
            const dendaDisplay = document.getElementById('perkiraan_denda');

            function calculateFee() {
                const borrowDate = new Date(borrowDateValue);
                const returnDate = new Date(returnDateInput.value);

                if (isNaN(borrowDate.getTime()) || isNaN(returnDate.getTime())) {
                    dendaDisplay.textContent = 'Rp 0';
                    return;
                }

                const timeDiff = returnDate.getTime() - borrowDate.getTime();
                const dayDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));

                const tarifHarian = 1000;
                const batasPinjam = 7;
                
                const hariTelat = Math.max(0, dayDiff - batasPinjam);
                const totalDenda = hariTelat * tarifHarian;
                
                dendaDisplay.textContent = 'Rp ' + totalDenda.toLocaleString('id-ID');
            }
            
            returnDateInput.addEventListener('input', calculateFee);
            calculateFee();
        });
    </script>
</div>
<?php endif; ?>


<div class="bg-white p-8 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Catat Peminjaman Baru</h2>
    <form action="index.php?page=peminjaman" method="POST">
        <input type="hidden" name="process_add_borrowing" value="1">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label for="member_id" class="block text-sm font-medium text-gray-700">Pilih Anggota</label>
                <select id="member_id" name="member_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md" required>
                    <option value="">-- Pilih Anggota --</option>
                    <?php
                    $members = mysqli_query($koneksi, "SELECT id, full_name FROM members ORDER BY full_name");
                    while ($member = mysqli_fetch_assoc($members)) {
                        echo "<option value='{$member['id']}'>" . htmlspecialchars($member['full_name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="book_id" class="block text-sm font-medium text-gray-700">Pilih Buku</label>
                <select id="book_id" name="book_id" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md" required>
                    <option value="">-- Pilih Buku yang Tersedia --</option>
                    <?php
                    $books = mysqli_query($koneksi, "SELECT id, title FROM books WHERE stock > 0 ORDER BY title");
                    while ($book = mysqli_fetch_assoc($books)) {
                        echo "<option value='{$book['id']}'>" . htmlspecialchars($book['title']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            <div>
                <label for="borrow_date_tambah" class="block text-sm font-medium text-gray-700">Tanggal Pinjam</label>
                <input type="date" id="borrow_date_tambah" name="borrow_date" value="<?php echo htmlspecialchars($borrow_date_tambah); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md" required>
            </div>
        </div>
        <div class="mt-6">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                Simpan Peminjaman
            </button>
        </div>
    </form>
</div>


<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Daftar Riwayat Peminjaman</h2>
        <form action="index.php" method="GET" class="mt-4 md:mt-0">
            <input type="hidden" name="page" value="peminjaman">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari nama peminjam atau judul buku..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="block w-full md:w-80 px-4 py-2 pr-10 text-sm text-gray-900 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
                <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-16">No.</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Peminjam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buku</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Pinjam</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tgl Kembali</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Denda</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                $query = "SELECT br.id, m.full_name, b.title as book_title, br.borrow_date, 
                                 r.return_date, r.late_fee
                          FROM borrowings br
                          JOIN members m ON br.member_id = m.id
                          JOIN borrowing_details bd ON br.id = bd.borrowing_id
                          JOIN books b ON bd.book_id = b.id
                          LEFT JOIN returns r ON br.id = r.borrowing_id";

                if (!empty($search_keyword)) {
                    $query .= " WHERE m.full_name LIKE '$search_keyword%' OR b.title LIKE '$search_keyword%'";
                }

                $query .= " ORDER BY r.return_date IS NULL DESC, br.id DESC";
                
                $result = mysqli_query($koneksi, $query);

                if (mysqli_num_rows($result) > 0) :
                    $nomor = 1;
                    while ($row = mysqli_fetch_assoc($result)) :
                        $sudah_kembali = !is_null($row['return_date']);
                ?>
                        <tr class="<?php echo $sudah_kembali ? 'bg-gray-50' : ''; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $nomor++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['book_title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($row['borrow_date'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $sudah_kembali ? date('d M Y', strtotime($row['return_date'])) : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $row['late_fee'] > 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                <?php echo $sudah_kembali ? 'Rp ' . number_format($row['late_fee'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <?php if ($sudah_kembali) : ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                <?php else : ?>
                                    <a href="index.php?page=peminjaman&action=return&id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700">
                                        Kembalikan Buku
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile;
                else : ?>
                    <tr><td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                        <?php echo empty($search_keyword) ? 'Belum ada data peminjaman.' : 'Data tidak ditemukan.'; ?>
                    </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>