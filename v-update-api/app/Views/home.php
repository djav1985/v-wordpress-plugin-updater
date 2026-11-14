<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: home.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/layouts/header.php';

/**
 * @var list<array{
 *     domain: string,
 *     display_key: string,
 *     masked_key: string,
 *     expires_at: int,
 *     key_input_id: string,
 *     revealed: bool
 * }> $hosts
 */
$hosts = $hosts ?? [];
$csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
?>
<div class="page-header d-print-none">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">WordPress Update API</div>
            <h1 class="page-title">Allowed hosts</h1>
        </div>
    </div>
</div>

<div class="row row-cards">
    <div class="col-12">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Allowed hosts</h2>
                <p class="text-secondary mb-0 ms-auto">Keys are masked by default and revealed for 30 seconds.</p>
            </div>
            <div class="table-card" id="hosts_table">
                <?php if ($hosts === []) : ?>
                    <div class="empty py-5">
                        <p class="empty-title">No hosts yet</p>
                        <p class="empty-subtitle text-secondary">Add a domain below to authorize update requests.</p>
                    </div>
                <?php else : ?>
                    <div class="table-responsive">
                        <table class="table table-vcenter table-hover admin-table">
                            <thead>
                                <tr>
                                    <th scope="col">Domain</th>
                                    <th scope="col">Key</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hosts as $host) : ?>
                                    <?php
                                    $domain = htmlspecialchars($host['domain'], ENT_QUOTES, 'UTF-8');
                                    $escapedToken = htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8');
                                    $keyInputId = htmlspecialchars($host['key_input_id'], ENT_QUOTES, 'UTF-8');
                                    $displayKey = htmlspecialchars($host['display_key'], ENT_QUOTES, 'UTF-8');
                                    $maskedKey = htmlspecialchars($host['masked_key'], ENT_QUOTES, 'UTF-8');
                                    ?>
                                    <tr>
                                        <td data-label="Domain">
                                            <span class="host-domain-text"><?php echo $domain; ?></span>
                                        </td>
                                        <td data-label="Key">
                                            <input
                                                id="<?php echo $keyInputId; ?>"
                                                class="hosts-key"
                                                type="text"
                                                value="<?php echo $displayKey; ?>"
                                                data-masked-value="<?php echo $maskedKey; ?>"
                                                data-expires-at="<?php echo (int) $host['expires_at']; ?>"
                                                readonly
                                                aria-label="API key for <?php echo $domain; ?>"
                                            >
                                        </td>
                                        <td data-label="Actions" class="host-actions-cell">
                                            <div class="host-actions">
                                                <form method="post" action="/home" class="m-0">
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo $escapedToken; ?>"
                                                    >
                                                    <input type="hidden" name="domain" value="<?php echo $domain; ?>">
                                                    <button
                                                        class="btn btn-sm btn-secondary copy-key-btn"
                                                        type="<?php echo $host['revealed'] ? 'button' : 'submit'; ?>"
                                                        name="reveal_entry"
                                                        data-key-action="<?php
                                                            echo $host['revealed'] ? 'copy' : 'reveal';
                                                        ?>"
                                                        data-key-target="<?php echo $keyInputId; ?>"
                                                    ><?php echo $host['revealed'] ? 'Copy' : 'Reveal'; ?></button>
                                                </form>
                                                <form method="post" action="/home" class="m-0">
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo $escapedToken; ?>"
                                                    >
                                                    <input type="hidden" name="domain" value="<?php echo $domain; ?>">
                                                    <button
                                                        class="btn btn-sm btn-outline-primary"
                                                        type="submit"
                                                        name="regen_entry"
                                                    >Regenerate</button>
                                                </form>
                                                <form method="post" action="/home" class="m-0">
                                                    <input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?php echo $escapedToken; ?>"
                                                    >
                                                    <input type="hidden" name="domain" value="<?php echo $domain; ?>">
                                                    <button
                                                        class="btn btn-sm btn-danger"
                                                        type="submit"
                                                        name="delete_entry"
                                                    >Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    <div class="col-12">
        <section class="card section-card">
            <div class="card-header">
                <h2 class="card-title">Add host</h2>
            </div>
            <div class="card-body">
                <form class="entry-form" method="post" action="/home">
                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                    <div class="form-group">
                        <label class="form-label" for="domain">Domain</label>
                        <input class="form-control" type="text" name="domain" id="domain" autocomplete="url" required>
                    </div>
                    <button class="btn btn-primary" type="submit" name="add_entry">Add host</button>
                </form>
            </div>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
