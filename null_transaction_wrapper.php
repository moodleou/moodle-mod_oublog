<?php
/**
 * Pretends to wrap transactions. In fact does nothing. If you want a
 * real implementation of this, the OU have one, it goes in /local.
 * @copyright &copy; 2007 The Open University
 * @author s.marshall@open.ac.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package ouwiki
 */
class transaction_wrapper {

    function __construct(&$localdb=false) {
    }

    function complete($ok=true) {
        return $ok;
    }

    function commit() {
        return true;
    }

    function rollback() {
    }

    static function is_in_transaction() {
        return false;
    }
}
