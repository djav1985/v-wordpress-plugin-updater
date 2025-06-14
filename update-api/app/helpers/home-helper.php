<?php
/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: home-helper.php
 * Description: WordPress Update API
 */

function generateHostsTableRow($lineNumber, $domain, $key)
{
    return '<tr>
        <form method="post" action="/home">
            <input type="hidden" name="id" value="' . $lineNumber . '">
            <td><input class="hosts-domain" type="text" name="domain" value="' . $domain . '"></td>
            <td><input class="hosts-key" type="text" name="key" value="' . $key . '"></td>
            <td>
                <input class="hosts-submit" type="submit" name="update_entry" value="Update">
                <input class="hosts-submit" type="submit" name="delete_entry" value="Delete">
            </td>
        </form>
    </tr>';
}

$hostsFile = HOSTS_ACL . '/HOSTS';
$entries = file($hostsFile, FILE_IGNORE_NEW_LINES);
$hostsTableHtml = '';

if (count($entries) > 0) {
    $halfCount = ceil(count($entries) / 2);
    $entriesColumn1 = array_slice($entries, 0, $halfCount);
    $entriesColumn2 = array_slice($entries, $halfCount);

    $hostsTableHtml .= '<div class="row">';

    // Column 1
    $hostsTableHtml .= '<div class="column">
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Key</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($entriesColumn1 as $index => $entry) {
        $lineNumber = $index; // Correct line number for column 1
        $fields = explode(' ', $entry);
        $domain = isset($fields[0]) ? $fields[0] : '';
        $key = isset($fields[1]) ? $fields[1] : '';

        $hostsTableHtml .= generateHostsTableRow($lineNumber, $domain, $key);
    }

    $hostsTableHtml .= '</tbody></table></div>';

    // Column 2
    $hostsTableHtml .= '<div class="column">
        <table>
            <thead>
                <tr>
                    <th>Domain</th>
                    <th>Key</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($entriesColumn2 as $index => $entry) {
        $lineNumber = $index + $halfCount; // Correct line number for column 2
        $fields = explode(' ', $entry);
        $domain = isset($fields[0]) ? $fields[0] : '';
        $key = isset($fields[1]) ? $fields[1] : '';

        $hostsTableHtml .= generateHostsTableRow($lineNumber, $domain, $key);
    }

    $hostsTableHtml .= '</tbody></table></div></div>';
} else {
    $hostsTableHtml = "No entries found.";
}
