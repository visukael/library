<?php
$id = '';
$full_name = '';
$email = '';
$phone = '';
$address = '';
$is_edit = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $full_name = mysqli_real_escape_string($koneksi, $_POST['full_name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $phone = mysqli_real_escape_string($koneksi, $_POST['phone']);
    $address = mysqli_real_escape_string($koneksi, $_POST['address']);

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
    header("Location: index.php?page=anggota");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $query = "DELETE FROM members WHERE id=$id";
    if (mysqli_query($koneksi, $query)) {
        $_SESSION['message'] = 'Anggota berhasil dihapus!';
        $_SESSION['message_type'] = 'success';
    } else {
        $_SESSION['message'] = 'Gagal menghapus anggota. Mungkin anggota ini masih memiliki data peminjaman. Error: ' . mysqli_error($koneksi);
        $_SESSION['message_type'] = 'error';
    }
    header("Location: index.php?page=anggota");
    exit();
}

if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $is_edit = true;
    $id = (int)$_GET['id'];
    $result = mysqli_query($koneksi, "SELECT * FROM members WHERE id=$id");
    if ($data = mysqli_fetch_assoc($result)) {
        $full_name = $data['full_name'];
        $email = $data['email'];
        $phone = $data['phone'];
        $address = $data['address'];
    }
}

$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';

$where_clause = "";
if (!empty($search_keyword)) {
    $where_clause = "WHERE full_name LIKE '$search_keyword%' OR email LIKE '$search_keyword%'";
}

$data_query = "SELECT * FROM members $where_clause ORDER BY id DESC";
$result = mysqli_query($koneksi, $data_query);

?>

<div class="bg-white p-8 rounded-lg shadow-md mb-8">
    <h2 class="text-2xl font-bold text-gray-800 mb-6"><?php echo $is_edit ? 'Edit Anggota' : 'Tambah Anggota Baru'; ?></h2>
    <form action="index.php?page=anggota" method="POST">
        <input type="hidden" name="id" value="<?php echo $id; ?>">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="full_name" class="block text-sm font-medium text-gray-700">Nama Lengkap</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">No. Telepon</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($phone); ?>" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required>
            </div>
            <div>
                <label for="address" class="block text-sm font-medium text-gray-700">Alamat</label>
                <textarea name="address" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-purple-500 focus:border-purple-500" required><?php echo htmlspecialchars($address); ?></textarea>
            </div>
        </div>
        <div class="mt-6 flex items-center space-x-4">
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500">
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
                <input type="text" name="search" placeholder="Cari nama atau email..." value="<?php echo htmlspecialchars($search_keyword); ?>" class="block w-full md:w-80 px-4 py-2 pr-10 text-sm text-gray-900 border border-gray-300 rounded-md focus:ring-purple-500 focus:border-purple-500">
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telepon</th>
                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php
                if (mysqli_num_rows($result) > 0) :
                    $nomor = 1;
                    while ($row = mysqli_fetch_assoc($result)) :
                ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $nomor++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['email']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($row['phone']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <a href="index.php?page=anggota&action=edit&id=<?php echo $row['id']; ?>" class="text-purple-600 hover:text-purple-900 mr-4">Edit</a>
                                <a href="index.php?page=anggota&action=delete&id=<?php echo $row['id']; ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Yakin mau hapus anggota ini?');">Hapus</a>
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
</div>