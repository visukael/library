<?php
$total_buku_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books");
$total_buku = mysqli_fetch_assoc($total_buku_query)['total'];

$total_anggota_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM members");
$total_anggota = mysqli_fetch_assoc($total_anggota_query)['total'];

$total_pinjam_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM borrowings");
$total_pinjam = mysqli_fetch_assoc($total_pinjam_query)['total'];

$stok_sedikit_query = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM books WHERE stock <= 3 AND stock > 0");
$stok_sedikit = mysqli_fetch_assoc($stok_sedikit_query)['total'];

$peminjaman_terbaru_query = "SELECT m.full_name, b.title, br.borrow_date
                             FROM borrowings br
                             JOIN members m ON br.member_id = m.id
                             JOIN borrowing_details bd ON br.id = bd.borrowing_id
                             JOIN books b ON bd.book_id = b.id
                             ORDER BY br.id DESC
                             LIMIT 5";
$peminjaman_terbaru_result = mysqli_query($koneksi, $peminjaman_terbaru_query);

$buku_stok_sedikit_query = "SELECT title, author, stock FROM books WHERE stock <= 3 ORDER BY stock ASC LIMIT 5";
$buku_stok_sedikit_result = mysqli_query($koneksi, $buku_stok_sedikit_query);

?>

<div class="mb-8">
    <h2 class="text-3xl font-bold text-gray-800">Dashboard Ringkasan 📊</h2>
    <p class="text-gray-600">Selamat datang kembali, Kael! Ini ringkasan sistem perpustakaanmu.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Total Judul Buku</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $total_buku; ?></p>
        </div>
        <div class="bg-purple-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v11.494m-9-5.747h18"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Total Anggota</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $total_anggota; ?></p>
        </div>
        <div class="bg-blue-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Peminjaman Aktif</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $total_pinjam; ?></p>
        </div>
        <div class="bg-green-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h4M8 7a2 2 0 012-2h4a2 2 0 012 2v8a2 2 0 01-2 2H8M8 7l4 4-4 4"></path></svg>
        </div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md flex items-center justify-between">
        <div>
            <p class="text-sm font-medium text-gray-500">Buku Stok Sedikit</p>
            <p class="text-3xl font-bold text-gray-800"><?php echo $stok_sedikit; ?></p>
        </div>
        <div class="bg-yellow-100 p-3 rounded-full">
            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
        </div>
    </div>
</div>

<div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Peminjaman Terbaru</h3>
        <ul class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($peminjaman_terbaru_result) > 0) :
                while ($row = mysqli_fetch_assoc($peminjaman_terbaru_result)) : ?>
                    <li class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['full_name']); ?></p>
                            <p class="text-xs text-gray-500">meminjam <span class="font-semibold text-gray-600">"<?php echo htmlspecialchars($row['title']); ?>"</span></p>
                        </div>
                        <p class="text-sm text-gray-500"><?php echo date('d M Y', strtotime($row['borrow_date'])); ?></p>
                    </li>
            <?php endwhile;
            else : ?>
                <li class="py-3 text-sm text-gray-500">Belum ada aktivitas peminjaman.</li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Buku yang Hampir Habis (Stok ≤ 3)</h3>
        <ul class="divide-y divide-gray-200">
            <?php if (mysqli_num_rows($buku_stok_sedikit_result) > 0) :
                while ($row = mysqli_fetch_assoc($buku_stok_sedikit_result)) : ?>
                    <li class="py-3 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['title']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($row['author']); ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs font-semibold text-red-800 bg-red-100 rounded-full">
                            Sisa <?php echo $row['stock']; ?>
                        </span>
                    </li>
            <?php endwhile;
            else : ?>
                <li class="py-3 text-sm text-gray-500">Stok semua buku aman.</li>
            <?php endif; ?>
        </ul>
    </div>
</div>