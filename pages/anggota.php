<?php
$id = '';
$full_name = '';
$email = '';
$phone = '';
$address = '';
$is_edit = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $full_name = mysqli_real_escape_string($koneksi, $_POST['full_name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    $address = mysqli_real_escape_string($koneksi, $_POST['address']);

    $current_search = isset($_POST['current_search']) ? '&search=' . urlencode($_POST['current_search']) : '';
    $current_page = isset($_POST['current_page']) ? '&p=' . $_POST['current_page'] : '';
    $redirect_url = "index.php?page=anggota" . $current_page . $current_search;

    if (empty($id)) {
        $query = "INSERT INTO members (full_name, email, phone, address) VALUES ('$full_name', '$email', '$phone', '$address')";
        $_SESSION['message'] = 'Anggota baru berhasil ditambahkan!';
    } else {
        $query = "UPDATE members SET full_name='$full_name', email='$email', phone='$phone', address='$address' WHERE id=$id";
        $_SESSION['message'] = 'Data anggota berhasil diperbarui!';
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
    $query = "DELETE FROM members WHERE id=$id";
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['message'] = 'Anggota berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus anggota: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php?page=anggota");
    exit();
}

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 100;
$offset = ($page_num - 1) * $limit;

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $is_edit = true;
    $id = (int)$_GET['id'];
    $result_edit = mysqli_query($koneksi, "SELECT * FROM members WHERE id=$id");
    if ($data = mysqli_fetch_assoc($result_edit)) {
        $full_name = $data['full_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $address = $data['address'];
    }
}

$where_clause = "";
if (!empty($search_keyword)) {
    $where_clause = "WHERE full_name LIKE '$search_keyword%' OR email LIKE '$search_keyword%'";
}
$total_rows_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM members $where_clause");
$total_rows = mysqli_fetch_assoc($total_rows_query)['total'];
$total_pages = ceil($total_rows / $limit);

$data_query = "SELECT * FROM members $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset";
$result = mysqli_query($koneksi, $data_query);

?>

<div class="bg-white p-8 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $is_edit ? 'Edit Anggota' : 'Tambah Anggota Baru'; ?></h2>
    <form action="index.php?page=anggota" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <input type="hidden" name="current_search" value="<?php echo htmlspecialchars($search_keyword); ?>">
        <input type="hidden" name="current_page" value="<?php echo $page_num; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required>
            </div>
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                <textarea name="address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm" required><?php echo htmlspecialchars($address); ?></textarea>
            </div>
        </div>
        <div class="mt-6 flex items-center space-x-4">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                <?php echo $is_edit ? 'Update Anggota' : 'Simpan Anggota'; ?>
            </button>
            <?php if ($is_edit) : ?>
                <a href="index.php?page=anggota" class="inline-flex justify-center py-2 px-4 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Batal
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="bg-white p-8 rounded-lg shadow-md">
    <div class="flex flex-col md:flex-row justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Daftar Anggota</h2>
        <form action="index.php" method="GET" class="mt-4 md:mt-0">
            <input type="hidden" name="page" value="anggota">
            <div class="relative">
                <input type="text" name="search" placeholder="Cari nama atau email..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="block w-full md:w-80 px-4 py-2 pr-10 text-sm text-gray-900 border border-gray-300 rounded-md">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
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
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="?page=anggota&action=edit&id=<?php echo $row['id']; ?>&p=<?php echo $page_num; ?>&search=<?php echo htmlspecialchars($search_keyword); ?>" class="text-purple-600 hover:text-purple-900 mr-4">Edit</a>
                                <a href="?page=anggota&action=delete&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Yakin mau hapus anggota ini?');">Hapus</a>
                            </td>
                        </tr>
                    <?php
                    endwhile;
                else :
                    ?>
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            <?php echo empty($search_keyword) ? 'Belum ada data anggota.' : 'Anggota tidak ditemukan.'; ?>
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
            <a href="?page=anggota&p=<?php echo max(1, $page_num - 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-left" class="w-5 h-5"></i>
            </a>
            
            <?php 
            $start_page = max(1, $page_num - 2);
            $end_page = min($total_pages, $page_num + 2);

            if ($start_page > 1) {
                echo '<a href="?page=anggota&p=1&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">1</a>';
                if ($start_page > 2) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) : ?>
                <a href="?page=anggota&p=<?php echo $i; ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium <?php echo ($i == $page_num) ? 'z-10 bg-purple-50 border-purple-500 text-purple-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; 
            
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
                echo '<a href="?page=anggota&p='.$total_pages.'&search='.$search_keyword.'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium bg-white text-gray-700 hover:bg-gray-50">'.$total_pages.'</a>';
            }
            ?>

            <a href="?page=anggota&p=<?php echo min($total_pages, $page_num + 1); ?>&search=<?php echo $search_keyword; ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page_num >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                <i data-lucide="chevron-right" class="w-5 h-5"></i>
            </a>
        </nav>
    </div>
    <?php endif; ?>
</div>