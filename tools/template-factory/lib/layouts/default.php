<!doctype html>
<html lang="en">
<head>
<?= $view->head() ?>
<?= $view->libStyles() ?>
<link rel="stylesheet" href="<?= $view->asset('css/style.css') ?>">
</head>
<body class="{{p}} {{p}}-mood-{{mood}} <?= $view->bodyClass() ?>">
<a class="{{p}}-skip" href="#main">Skip to content</a>
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
