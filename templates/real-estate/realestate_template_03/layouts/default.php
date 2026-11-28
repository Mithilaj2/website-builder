<!doctype html>
<html lang="en">
<head>
<?= $view->head() ?>
<?= $view->libStyles() ?>
<link rel="stylesheet" href="<?= $view->asset('css/style.css') ?>">
</head>
<body class="nvk nvk-mood-light <?= $view->bodyClass() ?>">
<a class="nvk-skip" href="#main">Skip to content</a>
<?= $view->partial('announce') ?>
<?= $view->partial('header') ?>
<main id="main">
<?= $content ?>
</main>
<?= $view->partial('footer') ?>
<?= $view->libScripts() ?>
<script src="<?= $view->asset('js/main.js') ?>"></script>
</body>
</html>
