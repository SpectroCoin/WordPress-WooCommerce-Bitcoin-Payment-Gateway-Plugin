<?php

namespace SpectroCoin\Includes;

use WC_Logger;

if (!defined('ABSPATH')) {
	die('Access denied.');
}

class SpectroCoinLogger
{

    /**
     * Display error message in admin settings
     * @param string $message Error message
     * @param bool $allow_hyperlink Allow hyperlink in error message
     */
    public static function displayAdminErrorNotice($message, $allow_hyperlink = false) {
        static $displayed_messages = array();

        $allowed_html = $allow_hyperlink ? array(
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
            ),
        ) : array();

        $processed_message = wp_kses($message, $allowed_html);

        $current_page = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';

        if (!empty($processed_message) && !in_array($processed_message, $displayed_messages) && $current_page == "spectrocoin") {
            array_push($displayed_messages, $processed_message);
            ?>
            <div class="notice notice-error">
                <p><?php echo __("SpectroCoin Error: ", 'spectrocoin-accepting-bitcoin') . $processed_message; // Using $processed_message directly ?></p>
            </div>
            <script type="text/javascript">
                document.addEventListener("DOMContentLoaded", function() {
                    var notices = document.querySelectorAll('.notice-error');
                    notices.forEach(function(notice) {
                        notice.style.display = 'block';
                    });
                });
            </script>
            <?php
        }
    }
}