<?php

use Syntax\Core\Http\Request;
use Syntax\Core\Http\Response;
use Syntax\Core\View\Component;

/** @var \Syntax\Core\Http\Router $router */

$router->get('/', function (Request $req) {
    return Response::redirect('/students');
});

$router->get('/students', function (Request $req) {
    // Mock domain data for School
    $students = [
        [
            'nis' => '10291',
            'name' => 'Aditya Pratama',
            'class' => 'XII IPA 1',
            'attendance' => '98%',
            'status' => 'Aktif'
        ],
        [
            'nis' => '10292',
            'name' => 'Clarissa Putri',
            'class' => 'XII IPA 1',
            'attendance' => '100%',
            'status' => 'Aktif'
        ],
        [
            'nis' => '10293',
            'name' => 'Dimas Wicaksono',
            'class' => 'XII IPS 2',
            'attendance' => '85%',
            'status' => 'Izin'
        ]
    ];

    // Navbar Component with School branding
    $navbar = Component::render('navbar', [
        'brand' => 'Syntax School Academy',
        'brand_url' => '/students',
        'items' => [
            ['label' => 'Data Siswa', 'url' => '/students', 'active' => true],
            ['label' => 'Jadwal Pelajaran', 'url' => '#', 'active' => false],
            ['label' => 'Presensi & Nilai', 'url' => '#', 'active' => false],
        ],
        'right_html' => '<span class="c-badge c-badge--success">T.A 2026/2027</span>'
    ]);

    // Table Rows
    $tableRows = [];
    foreach ($students as $std) {
        $tableRows[] = [
            '<code>' . e($std['nis']) . '</code>',
            '<strong>' . e($std['name']) . '</strong>',
            e($std['class']),
            '<span class="font-medium">' . e($std['attendance']) . '</span>',
            '<span class="c-badge c-badge--success">' . e($std['status']) . '</span>',
            '<button type="button" class="c-btn c-btn--sm c-btn--secondary" data-toggle="modal" data-target="#studentDetailModal">Rapor</button>'
        ];
    }

    $table = Component::render('table', [
        'headers' => ['NIS', 'Nama Siswa', 'Kelas', 'Kehadiran', 'Status', 'Aksi'],
        'rows' => $tableRows,
        'striped' => true,
        'hover' => true
    ]);

    // Card Component
    $card = Component::render('card', [
        'title' => 'Daftar Siswa Terdaftar',
        'subtitle' => 'Tahun Ajaran Aktif 2026/2027 Semester Ganjil',
        'badge' => ['label' => count($students) . ' Terdata', 'variant' => 'success'],
        'body' => $table,
        'footer' => '<button type="button" class="c-btn c-btn--primary">+ Input Siswa Baru</button>'
    ]);

    // Modal Component
    $modal = Component::render('modal', [
        'id' => 'studentDetailModal',
        'title' => 'Detail Rapor & Akademik Siswa',
        'body' => '<p>Informasi presensi, nilai tugas harian, dan catatan wali kelas tersimpan di database <code>db_school</code>.</p>',
        'cancel_label' => 'Tutup'
    ]);

    $cdnUrl = env('STATIC_CDN_URL', 'http://localhost:8080');

    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Syntax School — Sistem Informasi Akademik</title>
  <!-- 1. Base Shared CDN CSS -->
  <link rel="stylesheet" href="{$cdnUrl}/css/core.min.css">
  <!-- 2. Product Emerald Green Theme Override -->
  <link rel="stylesheet" href="theme.css">
</head>
<body>
  {$navbar}

  <main class="c-container py-6">
    <div class="mb-4">
      <h2>Portal Akademik & Siswa</h2>
      <p class="text-muted">Menggunakan shared component yang sama dengan CMS, namun beridentitas hijau emerald melalui <code>theme.css</code> lokal.</p>
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
