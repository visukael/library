<?php
session_start();
require_once 'db.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$page_title = ucfirst(str_replace('_', ' ', $page));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | Dashboard Perpustakaan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #8b5cf6;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #7c3aed;
        }
    </style>
</head>
<body class="bg-gray-100 font-sans">
    <div class="flex h-screen">
        <aside class="w-64 bg-white shadow-md">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-purple-600">Perpustakaan</h1>
            </div>
            <nav class="mt-6">
                <a href="index.php?page=dashboard" class="flex items-center px-6 py-3 text-gray-700 hover:bg-purple-100 hover:text-purple-600 <?php echo $page == 'dashboard' ? 'bg-purple-100 text-purple-600' : ''; ?>">
                    <span class="mx-3">Dashboard</span>
                </a>
                <a href="index.php?page=buku" class="flex items-center px-6 py-3 mt-4 text-gray-700 hover:bg-purple-100 hover:text-purple-600 <?php echo $page == 'buku' ? 'bg-purple-100 text-purple-600' : ''; ?>">
                    <span class="mx-3">Manajemen Buku</span>
                </a>
                <a href="index.php?page=anggota" class="flex items-center px-6 py-3 mt-4 text-gray-700 hover:bg-purple-100 hover:text-purple-600 <?php echo $page == 'anggota' ? 'bg-purple-100 text-purple-600' : ''; ?>">
                    <span class="mx-3">Manajemen Anggota</span>
                </a>
                <a href="index.php?page=peminjaman" class="flex items-center px-6 py-3 mt-4 text-gray-700 hover:bg-purple-100 hover:text-purple-600 <?php echo $page == 'peminjaman' ? 'bg-purple-100 text-purple-600' : ''; ?>">
                    <span class="mx-3">Data Peminjaman</span>
                </a>
            </nav>
        </aside>

        <main class="flex-1 overflow-y-auto">
            <div class="p-8">
                <?php
                if (isset($_SESSION['message'])) {
                    $message_type = $_SESSION['message_type'] == 'success' ? 'success' : 'error';
                    echo "<script>
                            Swal.fire({
                                icon: '{$message_type}',
                                title: '{$_SESSION['message']}',
                                showConfirmButton: false,
                                timer: 2000
                            });
                          </script>";
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                }

                $file_path = "pages/{$page}.php";
                if (file_exists($file_path)) {
                    include $file_path;
                } else {
                    echo "<div class='text-center'>";
                    echo "<h1 class='text-4xl font-bold'>404</h1>";
                    echo "<p class='text-gray-600'>Halaman tidak ditemukan.</p>";
                    echo "</div>";
                }
                ?>
            </div>
        </main>
    </div>
</body>
</html>