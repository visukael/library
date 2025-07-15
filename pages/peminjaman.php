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

    $current_search = isset($_POST['current_search']) ? '&search=' . urlencode($_POST['current_search']) : '';
    $current_page = isset($_POST['current_page']) ? '&p=' . $_POST['current_page'] : '';
    $redirect_url = "index.php?page=peminjaman" . $current_page . $current_search;

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
    header("Location: " . $redirect_url);
    exit();
}

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 100;
$offset = ($page_num - 1) * $limit;

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

$count_query_base = "FROM borrowings br
                     JOIN members m ON br.member_id = m.id
                     JOIN borrowing_details bd ON br.id = bd.borrowing_id
                     JOIN books b ON bd.book_id = b.id
                     LEFT JOIN returns r ON br.id = r.borrowing_id";

$where_clause = "";
if (!empty($search_keyword)) {
    $where_clause = " WHERE m.full_name LIKE '$search_keyword%' OR b.title LIKE '$search_keyword%'";
}

$total_rows_query = mysqli_query($koneksi, "SELECT COUNT(br.id) as total " . $count_query_base . $where_clause);
$total_rows = mysqli_fetch_assoc($total_rows_query)['total'];
$total_pages = ceil($total_rows / $limit);

$data_query = "SELECT br.id, m.full_name, b.title as book_title, br.borrow_date, r.return_date, r.late_fee "
              . $count_query_base . $where_clause . 
              " ORDER BY r.return_date IS NULL DESC, br.id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($koneksi, $data_query);

?>

<?php if ($is_return_form && $return_data) : ?>
<div class="bg-white p-8 rounded-lg shadow-md mb-8 border-l-4 border-purple-500">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">Form Pengembalian Buku</h2>
    <form action="index.php?page=peminjaman" method="POST">
        <input type="hidden" name="process_return" value="1">
        <input type="hidden" name="borrowing_id" value="<?php echo $return_data['id']; ?>">
        <input type="hidden" id="borrow_date_hidden" name="borrow_date" value="<?php echo $return_data['borrow_date']; ?>">
        <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($search_keyword); ?>">
        <input type="hidden" name="current_page" value="<?php echo $page_num; ?>">
        <div class="mb-4">
            <p class="text-sm text-gray-600">Nama Peminjam: <span class="font-semibold text-gray-900"><?php echo htmlspecialchars($return_data['full_name']); ?></span></p>
            <p class="text-sm text-gray-600">Buku: <span class="font-semibold text-gray-900">"<?php echo htmlspecialchars($return_data['book_title']); ?>"</span></p>
            <p class="text-sm text-gray-600">Tgl Pinjam: <span class="font-semibold text-gray-900"><?php echo date('d M Y', strtotime($return_data['borrow_date'])); ?></span></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-end">
            <div>
                <label for="return_date_input" class="block text-sm font-medium text-gray-700">Tanggal Kembali</label>
                <input type="date" id="return_date_input" name="return_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
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
            <a href="?page=peminjaman&p=<?php echo $page_num; ?>&search=<?php echo htmlspecialchars($search_keyword); ?>" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                Batal
            </a>
        </div>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const returnDateInput = document.getElementById('return_date_input');
            if(returnDateInput) {
                const borrowDateValue = document.getElementById('borrow_date_hidden').value;
                const dendaDisplay = document.getElementById('perkiraan_denda');
                function calculateFee() {
                    const borrowDate = new Date(borrowDateValue);
                    const returnDate = new Date(returnDateInput.value);
                    if (isNaN(borrowDate.getTime()) || isNaN(returnDate.getTime())) { dendaDisplay.textContent = 'Rp 0'; return; }
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
            }
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
                <input type="text" name="search" placeholder="Cari nama atau judul buku..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="block w-full md:w-80 px-4 py-2 pr-10 text-sm text-gray-900 border border-gray-300 rounded-md">
                <button type="submit" class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <i data-lucide="search" class="w-5 h-5 text-gray-400"></i>
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
                if (mysqli_num_rows($result) > 0) :
                    $nomor = $offset + 1;
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo ($sudah_kembali && $row['late_fee'] > 0) ? 'text-red-600' : 'text-gray-900'; ?>">
                                <?php echo $sudah_kembali ? 'Rp ' . number_format($row['late_fee'], 0, ',', '.') : '-'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <?php if ($sudah_kembali) : ?>
                                    <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Selesai
                                    </span>
                                <?php else : ?>
                                    <a href="?page=peminjaman&action=return&id=<?php echo $row['id']; ?>&p=<?php echo $page_num; ?>&search=<?php echo htmlspecialchars($search_keyword); ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-purple-600 hover:bg-purple-700">
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

    <?php if ($total_pages > 1) : ?>
    <div class="mt-6 flex justify-between items-center">
        <p class="text-sm text-gray-700">
            Halaman <span class="font-medium"><?php echo $page_num; ?></span> dari <span class="font-medium"><?php echo $total_pages; ?></span>
        </p>
        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
            <a href="?page=peminjaman&p=<?php echo max(1, $page_num - 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </a>
            
            <?php 
            $start_page = max(1, $page_num - 2);
            $end_page = min($total_pages, $page_num + 2);

            if ($start_page > 1) {
                echo '<a href="?page=peminjaman&p=1&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">1</a>';
                if ($start_page > 2) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <a href="?page=peminjaman&p=<?php echo $i; ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo ($i == $page_num) ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; 
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
                echo '<a href="?page=peminjaman&p='.$total_pages.'&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
            }
            ?>

            <a href="?page=peminjaman&p=<?php echo min($total_pages, $page_num + 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </a>
        </nav>
    </div>
    <?php endif; ?>
</div>