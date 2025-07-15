<?php
$id = '';
$title = '';
$author = '';
$stock = '';
$published_year = '';
$is_edit = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $title = mysqli_real_escape_string($koneksi, $_POST['title']);
    $author = mysqli_real_escape_string($koneksi, $_POST['author']);
    $stock = (int)$_POST['stock'];
    $published_year = (int)$_POST['published_year'];

    $current_search = isset($_POST['current_search']) ? '&search=' . urlencode($_POST['current_search']) : '';
    $current_page = isset($_POST['current_page']) ? '&p=' . $_POST['current_page'] : '';
    $redirect_url = "index.php?page=buku" . $current_page . $current_search;


    if (empty($id)) {
        $query = "INSERT INTO books (title, author, stock, published_year) VALUES ('$title', '$author', $stock, $published_year)";
        $_SESSION['message'] = 'Buku berhasil ditambahkan!';
    } else {
        $query = "UPDATE books SET title='$title', author='$author', stock=$stock, published_year=$published_year WHERE id=$id";
        $_SESSION['message'] = 'Data buku berhasil diperbarui!';
    }

    if (mysqli_query($koneksi, $query)) {
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Terjadi kesalahan: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'error';
    }
    header("Location: " . $redirect_url);
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "DELETE FROM books WHERE id=$id";
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['message'] = 'Buku berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus buku: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php?page=buku");
    exit();
}

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 1000;
$offset = ($page_num - 1) * $limit;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $is_edit = true;
    $id = (int)$_GET['id'];
    $result_edit = mysqli_query($koneksi, "SELECT * FROM books WHERE id=$id");
    if ($data = mysqli_fetch_assoc($result_edit)) {
        $title = $data['title'];
        $author = $data['author'];
        $stock = $data['stock'];
        $published_year = $data['published_year'];
    }
}

$where_clause = "";
if (!empty($search_keyword)) {
    $where_clause = "WHERE title LIKE '$search_keyword%' OR author LIKE '$search_keyword%'";
}
$total_rows_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books $where_clause");
$total_rows = mysqli_fetch_assoc($total_rows_query)['total'];
$total_pages = ceil($total_rows / $limit);

$data_query = "SELECT * FROM books $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($koneksi, $data_query);

?>

<div class="bg-white p-8 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $is_edit ? 'Edit Buku' : 'Tambah Buku Baru'; ?></h2>
    <form action="index.php?page=buku" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($search_keyword); ?>">
        <input type="hidden" name="current_page" value="<?php echo $page_num; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700">Judul Buku</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="author" class="block text-sm font-medium text-gray-700">Penulis</label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="stock" class="block text-sm font-medium text-gray-700">Stok</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($stock); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="published_year" class="block text-sm font-medium text-gray-700">Tahun Terbit</label>
                <input type="number" id="published_year" name="published_year" value="<?php echo htmlspecialchars($published_year); ?>" class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm" required>
            </div>
        </div>
        <div class="mt-6 flex items-center space-x-4">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                <?php echo $is_edit ? 'Update Buku' : 'Simpan Buku'; ?>
            </button>
            <?php if ($is_edit) : ?>
                <a href="index.php?page=buku" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Daftar Buku</h2>
        <form action="index.php" method="GET" class="mt-4 md:mt-0">
            <input type="hidden" name="page" value="buku">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari judul atau penulis..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="block w-full md:w-80 px-4 py-2 pr-10 text-sm text-gray-900 border border-gray-300 rounded-md">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penulis</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stok</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tahun</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                if (mysqli_num_rows($result) > 0) :
                    $nomor = $offset + 1;
                    while ($row = mysqli_fetch_assoc($result)) :
                ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $nomor++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['author']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['stock']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['published_year']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="?page=buku&action=edit&id=<?php echo $row['id']; ?>&p=<?php echo $page_num; ?>&search=<?php echo htmlspecialchars($search_keyword); ?>" class="text-purple-600 hover:text-purple-900 mr-4">Edit</a>
                                <a href="?page=buku&action=delete&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Yakin mau hapus buku ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php
                    endwhile;
                else :
                    ?>
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                            <?php echo empty($search_keyword) ? 'Belum ada data buku.' : 'Buku tidak ditemukan.'; ?>
                        </td>
                    </tr>
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
            <a href="?page=buku&p=<?php echo max(1, $page_num - 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </a>
            
            <?php
            $start_page = max(1, $page_num - 2);
            $end_page = min($total_pages, $page_num + 2);

            if ($start_page > 1) {
                echo '<a href="?page=buku&p=1&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">1</a>';
                if ($start_page > 2) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <a href="?page=buku&p=<?php echo $i; ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo ($i == $page_num) ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; 
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
                echo '<a href="?page=buku&p='.$total_pages.'&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
            }
            ?>

            <a href="?page=buku&p=<?php echo min($total_pages, $page_num + 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </a>
        </nav>
    </div>
    <?php endif; ?>
</div>