<!DOCTYPE html>
<html lang="<?= e($site["language"]) ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title) ?></title>

<?php if ($description !== ""): ?>
    <meta name="description" content="<?= e($description) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
<?php endif; ?>
    <meta property="og:site_name" content="<?= e($site["title"]) ?>">
    <meta property="og:title" content="<?= e($og_title) ?>">
    <meta property="og:type" content="<?= $is_project ? "article" : "website" ?>">
<?php if ($site["url"] !== ""): ?>
    <meta property="og:url" content="<?= e($url) ?>">
    <link rel="canonical" href="<?= e($url) ?>">
<?php endif; ?>
<?php if ($image !== null and $site["url"] !== ""): ?>
    <meta property="og:image" content="<?= e($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php else: ?>
    <meta name="twitter:card" content="summary">
<?php endif; ?>

    <link rel="icon" type="image/x-icon" href="/src/img/favicon.ico">
    <link rel="preload" href="/src/fonts/jetbrains-mono-latin-wght-normal.woff2" as="font" type="font/woff2" crossorigin>

    <link rel="stylesheet" href="/src/css/github-markdown.css">
    <link rel="stylesheet" href="/src/css/custom-main.css">

    <style>
        :root {
            --page-width: <?= e($site["max_width"]) ?>;
        }
    </style>

    <script>
        // the theme chosen with the toggle button is applied before the page is shown, otherwise the system setting is used
        try {
            const theme = localStorage.getItem("theme");

            if (theme === "light" || theme === "dark") {
                document.documentElement.dataset.theme = theme;
            }
        } catch (e) {}
    </script>
    <script type="module" src="/src/js/core.js"></script>
</head>

<body>
<?= $content ?>

    <footer class="page-width site-footer">
        <nav aria-label="Footer">
            <a href="/legal">Legal Notice</a>
            <a href="mailto:<?= e($site["contact"]) ?>">Contact</a>
            <a href="https://github.com/<?= e($core["owner"]) ?>/<?= e($core["repository"]) ?>">Source</a>
        </nav>
        <span>built from GitHub · <?= e($build_date) ?> · © <?= e($site["copyright"]["start_year"]) ?>–<?= substr($build_date, 0, 4) ?> <?= e($site["copyright"]["name"]) ?></span>
    </footer>
</body>

</html>
