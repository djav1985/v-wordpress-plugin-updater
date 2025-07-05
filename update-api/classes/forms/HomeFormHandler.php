<?php
// @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: HomeFormHandler.php
 * Description: WordPress Update API
 */



class HomeFormHandler
{
    public function handleRequest(): void
    {
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST'
            && isset($_POST['csrf_token'], $_SESSION['csrf_token'])
            && $_POST['csrf_token'] === $_SESSION['csrf_token']
        ) {
            // Validate all POST inputs
            $domain = isset($_POST['domain']) ? SecurityHandler::validateDomain($_POST['domain']) : null;
            $key = isset($_POST['key']) ? SecurityHandler::validateKey($_POST['key']) : null;
            $id = isset($_POST['id']) ? filter_var($_POST['id'], FILTER_VALIDATE_INT) : null;
            if (isset($_POST['add_entry'])) {
                $this->addEntry($domain, $key);
            } elseif (isset($_POST['update_entry'])) {
                $this->updateEntry($id, $domain, $key);
            } elseif (isset($_POST['delete_entry'])) {
                $this->deleteEntry($id, $domain);
            } else {
                die('Invalid form action.');
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
            die('Invalid CSRF token.');
        }
    }

    private function addEntry(?string $domain, ?string $key): void
    {
        $hosts_file = HOSTS_ACL . '/HOSTS';
        // Escape output for safety
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $new_entry = $safe_domain . ' ' . $safe_key;
        file_put_contents($hosts_file, $new_entry . "\n", FILE_APPEND | LOCK_EX);
        header('Location: /home');
        exit();
    }

    private function updateEntry(?int $line_number, ?string $domain, ?string $key): void
    {
        $hosts_file = HOSTS_ACL . '/HOSTS';
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        // Escape output for safety
        $safe_domain = htmlspecialchars($domain, ENT_QUOTES, 'UTF-8');
        $safe_key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
        $entries[$line_number] = $safe_domain . ' ' . $safe_key;
        file_put_contents($hosts_file, implode("\n", $entries) . "\n");
        header('Location: /home');
        exit();
    }

    private function deleteEntry(?int $line_number, ?string $domain_to_delete): void
    {
        $hosts_file = HOSTS_ACL . '/HOSTS';
        $entries = file($hosts_file, FILE_IGNORE_NEW_LINES);
        unset($entries[$line_number]);
        file_put_contents($hosts_file, implode("\n", $entries) . "\n");

        // Log files to be updated
        $log_files = [
                      'plugin.log',
                      'theme.log',
                     ];
        // Escape domain for safety
        $safe_domain_to_delete = htmlspecialchars($domain_to_delete, ENT_QUOTES, 'UTF-8');
        foreach ($log_files as $log_file) {
            $log_file_path = LOG_DIR . "/$log_file";
            if (file_exists($log_file_path)) {
                $log_entries = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                $filtered_entries = array_filter($log_entries, function ($entry) use ($safe_domain_to_delete) {
                    return strpos($entry, $safe_domain_to_delete) !== 0;
                });
                file_put_contents($log_file_path, implode("\n", $filtered_entries) . "\n");
            }
        }
        header('Location: /home');
        exit();
    }
}
