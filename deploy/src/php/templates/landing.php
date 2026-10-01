<?php [$name, $tld] = site_name($site); ?>
    <header class="page-width site-header">
        <a class="logo" href="/"><?= e($name) ?><span><?= e($tld) ?></span></a>
        <nav class="site-nav" aria-label="Main">
            <a href="#projects">Projects</a>
            <a href="mailto:<?= e($site["contact"]) ?>">Contact</a>
            <a href="<?= e($github_url) ?>">GitHub</a>
<?= render_template("theme_toggle.php", []) ?>
        </nav>
    </header>

    <main>
        <div class="page-width">
        <section class="panel intro">
            <div class="intro-top">
<?php if ($bio["avatar"] !== null): ?>
                <img class="avatar" src="<?= e($bio["avatar"]["url"]) ?>" width="88" height="88" alt="">
<?php else: ?>
                <div class="avatar" aria-hidden="true"><?= e(project_initials($site["copyright"]["name"])) ?></div>
<?php endif; ?>
                <div class="intro-text">
                    <div class="markdown-body intro-bio">
<?= $bio["html"] ?>
                    </div>
                    <div class="intro-links">
                        <a class="button" href="<?= e($github_url) ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg><?= e(preg_replace("#^https?://#", "", $github_url)) ?></a>
                        <a class="button" href="mailto:<?= e($site["contact"]) ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><polyline points="3 7 12 13 21 7"></polyline></svg><?= e($site["contact"]) ?></a>
                    </div>
                </div>
            </div>
<?php if ($bio["stats"] !== null or count($topic_mix) > 0): $stats = $bio["stats"]; ?>

            <div class="stats">
<?php if ($stats !== null): ?>
                <dl class="stats-numbers">
                    <div><dt>commits</dt><dd><?= format_count($stats["commits"]) ?></dd></div>
                    <div><dt>pull requests</dt><dd><?= format_count($stats["pull_requests"]) ?></dd></div>
                    <div><dt>repos contributed to last year</dt><dd><?= format_count($stats["contributed_to"]) ?></dd></div>
                </dl>
<?php endif; ?>
<?php if (count($topic_mix) > 0): ?>
                <div class="topic-mix">
                    <span class="label">What I build</span>
                    <div class="topic-bar" aria-hidden="true">
<?php foreach ($topic_mix as $topic => $count): ?>
                        <span style="--hue: <?= topic_hue($topic) ?>; flex-grow: <?= $count ?>"></span>
<?php endforeach; ?>
                    </div>
                    <ul class="topic-legend" aria-label="Projects per topic">
<?php foreach ($topic_mix as $topic => $count): ?>
                        <li><span class="dot" style="--hue: <?= topic_hue($topic) ?>"></span><?= e($topic) ?> <span class="mono"><?= $count ?></span></li>
<?php endforeach; ?>
                    </ul>
                </div>
<?php endif; ?>
            </div>
<?php endif; ?>
        </section>
        </div>

        <section id="projects" class="page-width projects">
            <div class="section-head">
                <h2>My Projects</h2>
                <div class="list-controls mono muted">
                    <span class="project-count"><?= count($projects) ?> / <?= count($projects) ?></span>
                    <span class="sort-static">· newest first</span>
                    <div class="sort-controls" hidden>
                        <label class="visually-hidden" for="sort-key">Sort projects by</label>
                        <select id="sort-key" class="sort-key">
                            <option value="date">starting date</option>
                            <option value="name">alphabetical</option>
                            <option value="commits">commit count</option>
                        </select>
                        <button type="button" class="icon-button sort-order" aria-label="Sort descending, switch to ascending">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="5" x2="12" y2="19"></line><polyline points="19 12 12 19 5 12"></polyline></svg>
                        </button>
                    </div>
                </div>
            </div>

            <div class="topic-filter" role="group" aria-label="Filter by topic" hidden>
                <button type="button" class="chip" data-topic="" aria-pressed="true">all</button>
<?php foreach ($topics as $topic): ?>
                <button type="button" class="chip" data-topic="<?= e($topic) ?>" aria-pressed="false" style="--hue: <?= topic_hue($topic) ?>"><?= e($topic) ?></button>
<?php endforeach; ?>
            </div>

            <div class="project-grid">
<?php foreach ($projects as $project): ?>
<?php $date = format_date($project["date"]); $preview = $project["preview"]; ?>
                <a class="card" href="/<?= e($project["id"]) ?>" data-topics="<?= e(json_encode(array_values($project["topics"]))) ?>" data-date="<?= $date ?>" data-name="<?= e($project["name"]) ?>" data-commits="<?= project_repo($project) === "" ? "" : (int) $project["commit_count"] ?>">
                    <div class="card-media">
<?php if ($preview !== null): ?>
                        <img src="<?= e($preview["url"]) ?>"<?php if ($preview["width"] !== null): ?> width="<?= $preview["width"] ?>" height="<?= $preview["height"] ?>"<?php endif; ?> loading="lazy" alt="">
<?php else: ?>
                        <span class="card-initials" aria-hidden="true"><?= e(project_initials($project["name"])) ?></span>
<?php endif; ?>
<?php if ($date !== ""): ?>
                        <span class="badge"><?= $date ?></span>
<?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?= e($project["name"]) ?></h3>
                        <p><?= e($project["description"]) ?></p>
                    </div>
                    <div class="card-foot">
                        <div class="tags">
<?php foreach ($project["topics"] as $topic): ?>
                            <span class="tag" style="--hue: <?= topic_hue($topic) ?>"><?= e($topic) ?></span>
<?php endforeach; ?>
                        </div>
                        <span class="mono muted nowrap"><?= project_repo($project) === "" ? "no repo" : e($project["commit_count"]) . " commits" ?></span>
                    </div>
                </a>
<?php endforeach; ?>
            </div>

            <p class="project-empty muted" hidden>No project has all of the selected topics.</p>
        </section>
    </main>
