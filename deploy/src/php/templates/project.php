<?php [$name, $tld] = site_name($site); $repo = project_repo($project); $date = format_date($project["date"]); $pushed = format_date($project["pushed_at"]); ?>
    <header class="topbar">
        <div class="page-width topbar-content">
            <nav class="breadcrumb" aria-label="Breadcrumb">
                <a class="logo" href="/"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg><?= e($name) ?><span><?= e($tld) ?></span></a>
                <span aria-hidden="true">/</span>
                <a href="/#projects">projects</a>
                <span aria-hidden="true">/</span>
                <span class="breadcrumb-current" aria-current="page"><?= e($project["id"]) ?></span>
            </nav>
            <div class="topbar-actions">
<?php if ($repo !== ""): ?>
                <a class="button button-dark" href="https://github.com/<?= e($repo) ?>">View on GitHub<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17L17 7"></path><polyline points="8 7 17 7 17 16"></polyline></svg></a>
<?php endif; ?>
<?= render_template("theme_toggle.php", []) ?>
            </div>
        </div>
    </header>

    <main>
        <div class="page-width project-head">
<?php
    $meta = [];

    if ($date !== "") array_push($meta, "started " . $date);
    if ($repo !== "") array_push($meta, e($project["commit_count"]) . " commits");
    if ($pushed !== "") array_push($meta, "last push " . $pushed);
?>
<?php if (count($meta) > 0): ?>
            <div class="project-meta mono muted"><?= implode("<span aria-hidden=\"true\">·</span>", array_map(function($part) { return "<span>" . $part . "</span>"; }, $meta)) ?></div>
<?php endif; ?>
            <h1><?= e($project["name"]) ?></h1>
<?php if ($project["description"] !== ""): ?>
            <p class="project-description"><?= e($project["description"]) ?></p>
<?php endif; ?>
<?php if (count($project["topics"]) > 0): ?>
            <div class="tags">
<?php foreach ($project["topics"] as $topic): ?>
                <span class="tag" style="--hue: <?= topic_hue($topic) ?>"><?= e($topic) ?></span>
<?php endforeach; ?>
            </div>
<?php endif; ?>
        </div>

        <div class="page-width project-layout">
            <article class="panel markdown-body project-article">
<?= $html ?>
            </article>

            <aside class="project-aside">
<?php if (count($toc) > 0): ?>
                <nav class="toc" aria-label="On this page">
                    <span class="label">On this page</span>
<?php foreach ($toc as $i => $entry): ?>
                    <a href="#<?= e($entry["id"]) ?>"<?php if ($i === 0): ?> aria-current="true"<?php endif; ?>><?= e($entry["text"]) ?></a>
<?php endforeach; ?>
                </nav>
<?php endif; ?>
<?php if ($repo !== "" or !empty($project["homepage"])): ?>
                <div class="aside-links">
                    <span class="label">Links</span>
<?php if ($repo !== ""): ?>
                    <a href="https://github.com/<?= e($repo) ?>">Repository</a>
<?php endif; ?>
<?php if (!empty($project["homepage"])): ?>
                    <a href="<?= e($project["homepage"]) ?>">Homepage</a>
<?php endif; ?>
                </div>
<?php endif; ?>
            </aside>
        </div>
    </main>
