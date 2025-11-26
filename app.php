<?php
/**
 * Vale V Photography - Application Bootstrap
 * Main entry point that initializes the application
 */

// Load configuration and paths
require_once 'config/paths.php';

// Simple routing based on URL
$request = $_GET['page'] ?? 'home';

switch($request) {
    case 'home':
    case 'inicio':
        include 'views/index.php';
        break;
        
    case 'clases':
    case 'classes':
        include 'views/clases.php';
        break;
        
    case 'horarios':
    case 'schedule':
        include 'views/horarios.php';
        break;
        
    case 'catalogo':
    case 'catalog':
        include 'views/catalogo.php';
        break;
        
    case 'redes-sociales':
    case 'social':
        include 'views/redes-sociales.php';
        break;
        
    case 'ubicacion':
    case 'location':
        include 'views/ubicacion.php';
        break;
        
    case 'login':
        include 'views/login.php';
        break;
        
    case 'register':
        include 'views/register.php';
        break;
        
    case 'contact':
        include 'views/contact.php';
        break;
        
    case 'dashboard':
        include 'views/dashboard.php';
        break;
        
    case 'admin':
        include 'admin/admin.php';
        break;
        
    default:
        include 'views/index.php';
        break;
}
?>