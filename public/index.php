<?php

/**
 * SDG Frontend — Application Entry Point
 * 
 * File ini sangat sederhana dan hanya bertugas mengaktifkan aplikasi.
 * Semua logika routing dan AJAX proxy didelegasikan ke Application class.
 * 
 * @version 3.0.0 (PSR-4 Autoloader & OOP Architecture)
 * @author Rochmady and Wizdam Team
 * @license MIT
 */

// Define project root
define('PROJECT_ROOT', dirname(__DIR__));

// Register PSR-4 autoloader
spl_autoload_register(function ($class) {
    // Project namespace prefix
    $prefix = 'Wizdam\\';
    
    // Base directory for the namespace prefix
    $base_dir = PROJECT_ROOT . '/src/';
    
    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    // Get the relative class name
    $relative_class = substr($class, $len);
    
    // Replace namespace separators with directory separators
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Run the application using the Application class
Wizdam\Core\Application::get()->execute();
