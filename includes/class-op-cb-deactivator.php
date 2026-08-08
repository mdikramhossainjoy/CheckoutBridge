<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * OP_CB_Deactivator Class
 * Handles plugin deactivation cleanup
 */
class OP_CB_Deactivator {

    /**
     * Main deactivation method
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
