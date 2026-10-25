<?php
/**
 * Campusly Secure Logout Script (logout.php)
 * System: Campusly
 * 
 * Safely clears session variables, wipes session cookie,
 * clears remember-me tokens from DB and client cookies, and destroys session.
 */

require_once __DIR__ . '/auth.php';

logout_user('manual');

header('Location: login.php?msg=logged_out');
exit;
