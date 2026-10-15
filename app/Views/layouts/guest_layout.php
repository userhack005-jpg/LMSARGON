<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($pageTitle ?? 'System Core') ?></title>
    <?php if (! empty($settings['site_favicon'])): ?>
        <link rel="icon" href="<?= base_url(esc($settings['site_favicon'])) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body class="theme-obsidian" data-base-url="<?= base_url() ?>">

    <div class="ambient-grid"></div>

    <?= $this->renderSection('content') ?>

    <script src="<?= base_url('assets/js/app.js') ?>" defer></script>
</body>
</html>