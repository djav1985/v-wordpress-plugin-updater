<?php

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.5.0
 *
 * File: plupdate.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/layouts/header.php';

/** @var list<array{slug: string, version: string}> $plugins */
$plugins = $plugins ?? [];
$csrfToken = \App\Core\SessionManager::getInstance()->get('csrf_token') ?? '';
?>
<div class="page-header d-print-none">
    <div class="page-pretitle">WordPress Update API</div>
    <h1 class="page-title">Manage plugins</h1>
</div>

<div class="row row-cards">
    <div class="col-12">
        <section class="card">
            <div class="card-header">
                <h2 class="card-title">Available plugins</h2>
                <span class="badge bg-blue-lt ms-auto"><?php echo count($plugins); ?> packages</span>
            </div>
            <?php if ($plugins === []) : ?>
                <div class="empty py-5">
                    <p class="empty-title">No plugins available</p>
                    <p class="empty-subtitle text-secondary">Upload a plugin package below.</p>
                </div>
            <?php else : ?>
                <div class="table-responsive" id="plugins_table">
                    <table class="table table-vcenter table-hover admin-table">
                        <thead>
                            <tr>
                                <th scope="col">Name</th>
                                <th scope="col">Version</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($plugins as $plugin) : ?>
                                <?php
                                $slug = (string) $plugin['slug'];
                                $version = (string) $plugin['version'];
                                $displayName = str_replace(['-', '_'], ' ', $slug);
                                $packageFile = $slug . '_' . $version . '.zip';
                                ?>
                                <tr>
                                    <td data-label="Name" class="package-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td data-label="Version"><span class="badge bg-azure-lt"><?php echo htmlspecialchars($version, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td data-label="Action">
                                        <form method="post" action="/plupdate" class="inline-action-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="plugin_name" value="<?php echo htmlspecialchars($packageFile, ENT_QUOTES, 'UTF-8'); ?>">
                                            <button class="btn btn-sm btn-danger" type="submit" name="delete_plugin">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
    <div class="col-12 col-lg-7">
        <section class="card upload-card">
            <div class="card-header">
                <h2 class="card-title">Upload plugin</h2>
            </div>
            <div class="card-body">
                <form action="/plupdate" method="post" enctype="multipart/form-data" class="dropzone" id="upload_plugin_form" data-dropzone data-param-name="plugin_file[]">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="fallback">
                        <label class="form-label" for="plugin-files">Choose plugin ZIP files</label>
                        <input class="form-control" id="plugin-files" name="plugin_file[]" type="file" accept=".zip" multiple>
                    </div>
                    <div class="dz-message">Drop plugin ZIP files here or click to browse.</div>
                </form>
                <button class="btn btn-outline-primary mt-3" type="button" onclick="window.location.reload();">Reload list</button>
            </div>
        </section>
    </div>
    <div class="col-12 col-lg-5">
        <section class="card upload-card">
            <div class="card-header">
                <h2 class="card-title">Upload status</h2>
            </div>
            <div class="card-body upload-status" id="message-container" aria-live="polite">
                <p class="text-secondary mb-0">Upload results will appear here.</p>
            </div>
        </section>
    </div>
</div>
<script src="/assets/js/uploads.js"></script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
