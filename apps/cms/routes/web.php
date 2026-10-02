<?php

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\View\Component;

/** @var \Syntax\Core\Http\Router $router */

$router->get('/', function (Request $req) {
    return Response::redirect('/articles');
});

$router->get('/articles', function (Request $req) {
    // Mock domain data for CMS
    $articles = [
        [
            'id' => 1,
            'title' => 'Panduan Memulai Arsitektur Multi-Aplikasi',
            'author' => 'Ahmad Admin',
            'category' => 'Engineering',
            'status' => 'Published',
            'date' => '2026-10-02'
        ],
        [
            'id' => 2,
            'title' => 'Cara Integrasi Vanilla JS dengan Central CDN',
            'author' => 'Siti Editor',
            'category' => 'Frontend',
            'status' => 'Draft',
            'date' => '2026-10-01'
        ],
        [
            'id' => 3,
            'title' => 'Tips Optimasi Database MySQL Multi-Tenant',
            'author' => 'Budi Backend',
            'category' => 'Database',
            'status' => 'Published',
            'date' => '2026-09-28'
        ]
    ];

    // Navbar Component
    $navbar = Component::render('navbar', [
        'brand' => 'Syntax CMS',
        'brand_url' => '/articles',
        'items' => [
            ['label' => 'Artikel', 'url' => '/articles', 'active' => true],
            ['label' => 'Kategori', 'url' => '#', 'active' => false],
            ['label' => 'Media', 'url' => '#', 'active' => false],
        ],
        'right_html' => '<span class="c-badge c-badge--primary">Admin Mode</span>'
    ]);

    // Table Rows
    $tableRows = [];
    foreach ($articles as $art) {
        $badgeClass = $art['status'] === 'Published' ? 'success' : 'warning';
        $tableRows[] = [
            '#' . $art['id'],
            '<strong>' . e($art['title']) . '</strong>',
            e($art['author']),
            '<span class="c-badge c-badge--' . $badgeClass . '">' . e($art['status']) . '</span>',
            e($art['date']),
            '<button type="button" class="c-btn c-btn--sm c-btn--danger" data-toggle="modal" data-target="#deleteArticleModal" onclick="document.getElementById(\'deleteArticleForm\').action=\'/articles/delete/' . $art['id'] . '\';">Hapus</button>'
        ];
    }

    $table = Component::render('table', [
        'headers' => ['ID', 'Judul Artikel', 'Penulis', 'Status', 'Tanggal', 'Aksi'],
        'rows' => $tableRows,
        'striped' => true,
        'hover' => true
    ]);

    // Card Component
    $card = Component::render('card', [
        'title' => 'Koleksi Artikel Terbit',
        'subtitle' => 'Kelola seluruh artikel CMS melalui tabel terpadu',
        'badge' => ['label' => count($articles) . ' Artikel', 'variant' => 'primary'],
        'body' => $table,
        'footer' => '<button type="button" class="c-btn c-btn--primary" onclick="alert(\'Form tambah artikel siap dibuka\');">+ Tulis Artikel Baru</button>'
    ]);

    // Modal Component
    $modal = Component::render('modal', [
        'id' => 'deleteArticleModal',
        'title' => 'Konfirmasi Penghapusan Artikel',
        'body' => '<p>Apakah Anda yakin ingin menghapus artikel ini? Tindakan ini tidak dapat dibatalkan.</p>',
        'cancel_label' => 'Batal',
        'confirm_label' => 'Ya, Hapus Artikel',
        'confirm_action' => '/articles/delete/1',
        'variant' => 'danger'
    ]);

    $cdnUrl = env('STATIC_CDN_URL', 'http://localhost:8080');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Syntax CMS — Panel Manajemen Konten</title>
  <link rel="stylesheet" href="{$cdnUrl}/css/core.min.css">
</head>
<body>
  {$navbar}

  <main class="c-container py-6">
    <div class="mb-4">
      <h2>Dashboard Artikel CMS</h2>
      <p class="text-muted">Aplikasi ini mengonsumsi <code>syntax/core-php</code> dan menyematkan UI dari Central CDN.</p>
    </div>

    {$card}
    {$modal}
  </main>

  <script src="{$cdnUrl}/js/core.min.js"></script>
</body>
</html>
HTML;

    return Response::html($html);
});

// JSON API endpoint demonstration (Unified API Contract)
$router->get('/api/articles', function (Request $req) {
    return json_response([
        [
            'id' => 1,
            'title' => 'Panduan Memulai Arsitektur Multi-Aplikasi',
            'status' => 'published'
        ],
        [
            'id' => 2,
            'title' => 'Cara Integrasi Vanilla JS dengan Central CDN',
            'status' => 'draft'
        ]
    ], 200, 'Daftar artikel berhasil diambil.');
});
