<?php

/*
 * Project: Update API
 * Author: Vontainment
 * URL: https://vontainment.com
 * File: ThHelper.php
 * Description: WordPress Update API Helper for theme updates
 */

namespace UpdateApi\helpers;

class ThHelper
{
    public static function generateThemeTableRow($theme, $theme_name)
    {
        return '<tr>
             <td>' . htmlspecialchars($theme_name, ENT_QUOTES, 'UTF-8') . '</td>
             <td>
                 <form name="delete_theme_form" action="/thupdate" method="POST">
                     <input type="hidden" name="theme_name" value="' .
                         htmlspecialchars($theme, ENT_QUOTES, 'UTF-8') .
                     '">
                     <input class="th-submit" type="submit" name="delete_theme" value="Delete">
                 </form>
             </td>
         </tr>';
    }

    /**
     * Generates the HTML for the themes table.
     *
     * @return string
     */
    public static function getThemesTableHtml()
    {
        $themes = glob(THEMES_DIR . "/*.zip");
        $themes = array_reverse($themes);
        if (count($themes) > 0) {
            $half_count = ceil(count($themes) / 2);
            $themes_column1 = array_slice($themes, 0, $half_count);
            $themes_column2 = array_slice($themes, $half_count);
            $themesTableHtml = '<div class="row"><div class="column">
                 <table>
                     <thead>
                         <tr>
                             <th>Theme Name</th>
                             <th>Delete</th>
                         </tr>
                     </thead>
                     <tbody>';
            foreach ($themes_column1 as $theme) {
                $theme_name = basename($theme);
                $themesTableHtml .= self::generateThemeTableRow($theme, $theme_name);
            }
            $themesTableHtml .= '</tbody></table></div><div class="column"><table>
                 <thead>
                     <tr>
                         <th>Theme Name</th>
                         <th>Delete</th>
                     </tr>
                 </thead>
                 <tbody>';
            foreach ($themes_column2 as $theme) {
                $theme_name = basename($theme);
                $themesTableHtml .= self::generateThemeTableRow($theme, $theme_name);
            }
            $themesTableHtml .= '</tbody></table></div></div>';
        } else {
            $themesTableHtml = "No themes found.";
        }
        return $themesTableHtml;
    }
}
