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
			<!-- Global Flash Messages (Shows RBAC Access Denied & System Alerts on Dashboard & All Pages) -->
				<?php if (session()->getFlashdata('error')): ?>
					<div class="alert-banner alert-error" style="margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
						<span>⚠️ <strong>Access Denied / Error:</strong> <?= esc(session()->getFlashdata('error')) ?></span>
					</div>
				<?php endif; ?>

				<?php if (session()->getFlashdata('success')): ?>
					<div class="alert-banner alert-success" style="margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: space-between;">
						<span>✅ <?= esc(session()->getFlashdata('success')) ?></span>
					</div>
				<?php endif; ?>

				
                <?= $this->renderSection('content') ?>
            </main>

            <?= $this->include('layouts/partials/footer') ?>
        </div>
    </div>

 <?php
        $coreJsFile = FCPATH . 'assets/js/app.js';
        $coreJsVer  = is_file($coreJsFile) ? (string) @filemtime($coreJsFile) : '1.0';
    ?>
    <script src="<?= base_url('assets/js/app.js') ?>?v=<?= esc($coreJsVer) ?>" defer></script>
    <?php if (! empty($settings['footer_scripts'])): ?>
        <?= $settings['footer_scripts'] ?>
    <?php endif; ?>
</body>
</html>