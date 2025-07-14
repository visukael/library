<?php
require_once 'db.php';
require_once 'vendor/autoload.php';

$faker = Faker\Factory::create('id_ID');

echo "Memulai proses seeding data ke tabel 'books'...\n";

$jumlah_data = 5000;

$stmt = mysqli_prepare($koneksi, "INSERT INTO books (title, author, stock, published_year) VALUES (?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, 'ssii', $title, $author, $stock, $published_year);

for ($i = 0; $i < $jumlah_data; $i++) {
    $title = $faker->sentence(rand(3, 7));
    $author = $faker->name();
    $stock = $faker->numberBetween(5, 50);
    $published_year = $faker->year();

    mysqli_stmt_execute($stmt);

    if (($i + 1) % 100 == 0) {
        echo "-> " . ($i + 1) . " data berhasil ditambahkan...\n";
    }
}

mysqli_stmt_close($stmt);

echo "\n=========================================\n";
echo "Seeding selesai! Total $jumlah_data data buku baru telah ditambahkan.\n";
echo "=========================================\n";

?>