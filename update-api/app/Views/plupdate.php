<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: plupdate.php
 * Description: WordPress Update API
 */

require_once __DIR__ . '/layouts/header.php';
?>

<div class="content-box">
  <h2>Plugins</h2>
  <div id="plugins_table">
    <?php
    /** @var string $pluginsTableHtml */
    $pluginsTableHtml = $pluginsTableHtml ?? '';
    echo $pluginsTableHtml;
    ?>
  </div>
  <div class="plupload section">
    <div id="upload-container">
      <h2>Upload Plugin</h2>
      <form action="/plupdate" method="post" enctype="multipart/form-data" class="dropzone" id="upload_plugin_form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>">
        <div class="fallback">
          <input name="plugin_file[]" type="file" multiple />
        </div>
      </form>
      <button class="reload-btn" onclick="window.location.reload();">Reload Page</button>
    </div>

    <div id="message-container">
      <h2>Upload Status</h2>
    </div>
  </div>
</div>


<script>
  Dropzone.autoDiscover = false;

  $(document).ready(function() {
    var myDropzone = new Dropzone("#upload_plugin_form", {
      paramName: "plugin_file[]",
      maxFilesize: 200,
      acceptedFiles: "application/zip,application/x-zip-compressed,multipart/x-zip",
      autoProcessQueue: true,
      parallelUploads: 6,
      init: function() {
        var dz = this;

        this.on("success", function(file, response) {
          console.log(response);
          var successMsg = $('<div class="success-message"></div>');
          successMsg.text(response);

          // Insert the success message below the form
          $('#message-container').append(successMsg);
        });

        this.on("error", function(file, errorMessage) {
          console.log(errorMessage);
          var errorMsg = $('<div class="error-message"></div>');
          errorMsg.text(errorMessage);

          // Insert the error message below the form
          $('#message-container').append(errorMsg);
        });
      }
    });
  });
</script>
<?php require_once __DIR__ . '/layouts/footer.php'; ?>
