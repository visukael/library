<?php
session_start();
require_once 'db.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if ($page == 'dashboard') {
    $page_title = 'Dashboard';
} else {
    $page_title = 'Manajemen ' . ucfirst(str_replace('_', ' ', $page));
    if ($page == 'peminjaman') {
      $page_title = 'Data Peminjaman';
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($page == 'dashboard' ? '' : $page_title . ' | '); ?>Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c4b5fd; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #a78bfa; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">
    <div class="flex h-screen">
        <aside class="w-64 flex-shrink-0 bg-white border-r border-gray-200 flex flex-col">
            <div class="h-20 flex items-center justify-center border-b border-gray-200">
                <a href="index.php" class="text-2xl font-bold text-purple-600">
                    Perpustakaan
                </a>
            </div>
            
            <nav class="flex-1 px-4 py-6 space-y-2">
                <?php
                    $menuItems = [
                        'dashboard' => ['icon' => '<i data-lucide="layout-dashboard" class="w-5 h-5"></i>', 'label' => 'Dashboard'],
                        'buku' => ['icon' => '<i data-lucide="book-open" class="w-5 h-5"></i>', 'label' => 'Manajemen Buku'],
                        'anggota' => ['icon' => '<i data-lucide="users-2" class="w-5 h-5"></i>', 'label' => 'Manajemen Anggota'],
                        'peminjaman' => ['icon' => '<i data-lucide="arrow-left-right" class="w-5 h-5"></i>', 'label' => 'Data Peminjaman']
                    ];

                    foreach ($menuItems as $key => $item) {
                        $isActive = ($page == $key);
                        $linkClass = $isActive 
                            ? 'bg-purple-100 text-purple-700' 
                            : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';
                        echo "<a href='index.php?page={$key}' class='flex items-center px-4 py-2.5 text-sm font-medium rounded-lg transition-colors duration-200 {$linkClass}'>";
                        echo "<span class='mr-3'>{$item['icon']}</span>";
                        echo $item['label'];
                        echo "</a>";
                    }
                ?>
            </nav>

            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-purple-200 flex items-center justify-center">
                        <span class="text-lg font-semibold text-purple-700">K</span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{user}</p>
                        <p class="text-xs text-gray-500">Admin</p>
                    </div>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col overflow-hidden">
            <header class="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-8">
                <h1 class="text-xl font-bold text-gray-800"><?php echo $page_title; ?></h1>
            </header>
            
            <main class="flex-1 overflow-y-auto bg-gray-100">
                <div class="p-8">
                    <?php
                    if (isset($_SESSION['message'])) {
                        $message_type = $_SESSION['message_type'] == 'success' ? 'success' : 'error';
                        echo "<script>
                                Swal.fire({
                                    icon: '{$message_type}',
                                    title: '{$_SESSION['message']}',
                                    showConfirmButton: false,
                                    timer: 2000,
                                    toast: true,
                                    position: 'top-end',
                                    timerProgressBar: true
                                });
                              </script>";
                        unset($_SESSION['message']);
                        unset($_SESSION['message_type']);
                    }

                    $file_path = "pages/{$page}.php";
                    if (file_exists($file_path)) {
                        include $file_path;
                    } else {
                        echo "<div class='text-center p-16'><h1 class='text-4xl font-bold text-gray-400'>404 - Halaman Tidak Ditemukan</h1></div>";
                    }
                    ?>
                </div>
            </main>
        </div>
    </div>

    <script>
      lucide.createIcons();
    </script>
</body>
</html>