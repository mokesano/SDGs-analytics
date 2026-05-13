<?php
/**
 * SDG Frontend — Application Entry Point
 * 
 * File ini sangat sederhana dan hanya bertugas mengaktifkan aplikasi.
 * Semua logika routing dan AJAX proxy didelegasikan ke routers.php
 * 
 * @version 2.0.0 (Refactored for modularity)
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Define project root
define('PROJECT_ROOT', dirname(__DIR__));

// Load the router which handles all application logic
require_once PROJECT_ROOT . '/includes/routers.php';

// Run the application
runApplication();
