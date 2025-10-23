<?php
// phpcs:ignoreFile PSR1.Files.SideEffects.FoundWithSymbols

/**
 * Project: UpdateAPI
 * Author:  Vontainment <services@vontainment.com>
 * License: https://opensource.org/licenses/MIT MIT License
 * Link:    https://vontainment.com
 * Version: 4.0.0
 *
 * File: sitelogs.php
 * Description: WordPress Update API - Site Logs Viewer
 */

require_once __DIR__ . '/layouts/header.php';

/** @var array<int, string> $hosts */
$hosts = $hosts ?? [];
?>

<div class="content-box">
  <h2>Site Logs</h2>
  <div class="sitelogs-container">
    <div class="sitelogs-left">
      <h3>Domains</h3>
      <div class="domain-list">
        <?php if (count($hosts) > 0): ?>
          <?php foreach ($hosts as $host): ?>
            <div class="domain-item">
              <span class="domain-name"><?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?></span>
              <button class="green-button view-logs-btn" onclick="viewLogs('<?php echo htmlspecialchars($host, ENT_QUOTES, 'UTF-8'); ?>')">
                View Logs
              </button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No domains found.</p>
        <?php endif; ?>
      </div>
    </div>
    
    <div class="sitelogs-right">
      <h3>Log Viewer</h3>
      <div id="log-status" class="log-status"></div>
      <textarea id="log-viewer" class="log-viewer" readonly placeholder="Select a domain and click 'View Logs' to display logs here..."></textarea>
    </div>
  </div>
</div>

<style>
.sitelogs-container {
  display: flex;
  gap: 20px;
  margin: 20px 10px;
}

.sitelogs-left {
  flex: 1;
  min-width: 250px;
}

.sitelogs-right {
  flex: 2;
}

.domain-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-top: 10px;
}

.domain-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px;
  background-color: #f5f5f5;
  border-radius: 4px;
  border: 1px solid #ddd;
}

.domain-name {
  font-weight: bold;
  flex: 1;
  word-break: break-all;
}

.view-logs-btn {
  margin-left: 10px;
  white-space: nowrap;
}

.log-viewer {
  width: 100%;
  height: 600px;
  padding: 10px;
  font-family: 'Courier New', monospace;
  font-size: 12px;
  border: 2px solid #66cc33;
  border-radius: 4px;
  background-color: #f9f9f9;
  resize: vertical;
}

.log-status {
  margin-bottom: 10px;
  padding: 10px;
  border-radius: 4px;
  display: none;
}

.log-status.loading {
  display: block;
  background-color: #fff3cd;
  border: 1px solid #ffc107;
  color: #856404;
}

.log-status.success {
  display: block;
  background-color: #d4edda;
  border: 1px solid #66cc33;
  color: #155724;
}

.log-status.error {
  display: block;
  background-color: #f8d7da;
  border: 1px solid #f5c6cb;
  color: #721c24;
}

@media (max-width: 767px) {
  .sitelogs-container {
    flex-direction: column;
  }
  
  .domain-item {
    flex-direction: column;
    gap: 10px;
  }
  
  .view-logs-btn {
    width: 100%;
    margin-left: 0;
  }
  
  .log-viewer {
    height: 400px;
  }
}
</style>

<script>
function viewLogs(domain) {
  var statusDiv = document.getElementById('log-status');
  var logViewer = document.getElementById('log-viewer');
  
  // Show loading status
  statusDiv.className = 'log-status loading';
  statusDiv.textContent = 'Loading logs from ' + domain + '...';
  logViewer.value = '';
  
  // Make AJAX request to fetch logs
  $.ajax({
    url: '/sitelogs',
    type: 'POST',
    data: {
      domain: domain,
      lines: 250,
      csrf_token: '<?php echo htmlspecialchars(App\Core\SessionManager::getInstance()->get('csrf_token') ?? '', ENT_QUOTES, 'UTF-8'); ?>'
    },
    success: function(response) {
      var data = typeof response === 'string' ? JSON.parse(response) : response;
      
      if (data.success) {
        statusDiv.className = 'log-status success';
        statusDiv.textContent = 'Logs loaded successfully from ' + domain;
        logViewer.value = data.logs || 'No logs available.';
      } else {
        statusDiv.className = 'log-status error';
        statusDiv.textContent = 'Error: ' + (data.message || 'Failed to load logs');
        logViewer.value = '';
      }
    },
    error: function(xhr, status, error) {
      statusDiv.className = 'log-status error';
      statusDiv.textContent = 'Error: Failed to connect to server';
      logViewer.value = '';
    }
  });
}
</script>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>
