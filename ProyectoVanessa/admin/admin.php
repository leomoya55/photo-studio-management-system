<?php
// Simple redirect so admin tools can return to dashboard consistently
session_start();
require_once '../config/session_manager.php';
if (!$isLoggedIn || ($userRole !== 'admin')) {
  header('Location: ../views/login.php');
  exit();
}
header('Location: ../views/admin.php');
exit;