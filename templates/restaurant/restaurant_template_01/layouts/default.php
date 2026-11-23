<!doctype html>
<html lang="en">
<head>
<?= $view->head() ?>
<?= $view->libStyles() ?>
<link rel="stylesheet" href="<?= $view->asset('css/style.css') ?>">
</head>
<body class="sfr sfr-header-<?= $view->e(($view->theme['header_style'] ?? 'overlay') === 'solid' ? 'solid' : 'overlay') ?> <?= $view->bodyClass() ?>">
<a class="sfr-skip" href="#main">Skip to content</a>
<?= $view->partial('header') ?>
<main id="main">
<?= $content ?>
</main>
<?= $view->partial('footer') ?>
<?= $view->libScripts() ?>
<script src="<?= $view->asset('js/main.js') ?>"></script>
</body>
</html>
