<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: logs.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/layouts/header.php';

/** @var list<array{domain: string, date: string, status: string, age_days: int}> $pluginLogs */
$pluginLogs = $pluginLogs ?? [];
/** @var list<array{domain: string, date: string, status: string, age_days: int}> $themeLogs */
$themeLogs = $themeLogs ?? [];
$csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
?>
<div class="page-header d-print-none">
    <div class="page-pretitle">WordPress Update API</div>
    <h1 class="page-title">Update logs</h1>
</div>

<div class="row row-cards">
    <?php foreach ([['title' => 'Plugin updates', 'logs' => $pluginLogs], ['title' => 'Theme updates', 'logs' => $themeLogs]] as $logGroup) : ?>
        <div class="col-12">
            <section class="card">
                <div class="card-header">
                    <h2 class="card-title"><?php echo htmlspecialchars($logGroup['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                    <span class="badge bg-blue-lt ms-auto"><?php echo count($logGroup['logs']); ?> sites</span>
                </div>
                <?php if ($logGroup['logs'] === []) : ?>
                    <div class="empty py-4">
                        <p class="empty-title">No log entries found</p>
                    </div>
                <?php else : ?>
                    <div class="card-body">
                        <div class="log-row">
                            <?php foreach ($logGroup['logs'] as $log) : ?>
                                <?php
                                $status = (string) $log['status'];
                                $statusClass = $status === 'Failed' ? 'bg-red-lt text-red' : ($status === 'Success' ? 'bg-green-lt text-green' : 'bg-azure-lt');
                                $ageClass = (int) $log['age_days'] > 30 ? ' is-stale' : '';
                                ?>
                                <article class="log-sub-box<?php echo $ageClass; ?>">
                                    <h3><?php echo htmlspecialchars($log['domain'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <time class="log-entry" datetime="<?php echo htmlspecialchars($log['date'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($log['date'], ENT_QUOTES, 'UTF-8'); ?></time>
                                        <?php if ((int) $log['age_days'] > 30) : ?>
                                            <span class="badge bg-azure-lt">Older than 30 days</span>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    <?php endforeach; ?>
    <div class="col-12">
        <form method="post" action="/logs" class="d-flex justify-content-end">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <button class="btn btn-danger" type="submit" name="clear_logs">Clear all logs</button>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
