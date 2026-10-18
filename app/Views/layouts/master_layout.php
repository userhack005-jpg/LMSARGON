<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?= $this->include('layouts/partials/head_meta') ?>
</head>
<body class="theme-obsidian" data-base-url="<?= base_url() ?>">

    <?php if (! empty($settings['body_scripts'])): ?>
        <?= $settings['body_scripts'] ?>
    <?php endif; ?>

    <div class="ambient-grid"></div>

    <div class="app-shell">
        <?= $this->include('layouts/partials/sidebar') ?>

        <div class="app-main">
            <?= $this->include('layouts/partials/header') ?>

            <main class="app-content">
                <?= $this->renderSection('content') ?>
            </main>

            <?= $this->include('layouts/partials/footer') ?>
        </div>
    </div>


    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>