<?php
require_once('../config/db_connect.php');
header('Content-Type: application/json');

try {
    // Get active users count
    $usersQuery = "SELECT COUNT(*) as activeUsers FROM users WHERE status = 'active'";
    $usersResult = $conn->query($usersQuery);
    $activeUsers = $usersResult->fetch_assoc()['activeUsers'];

    // Get total enrollments
    $enrollmentsQuery = "SELECT COUNT(*) as totalEnrollments FROM enrollments";
    $enrollmentsResult = $conn->query($enrollmentsQuery);
    $totalEnrollments = $enrollmentsResult->fetch_assoc()['totalEnrollments'];

    // Get total classes
    $classesQuery = "SELECT COUNT(*) as totalClasses FROM classes";
    $classesResult = $conn->query($classesQuery);
    $totalClasses = $classesResult->fetch_assoc()['totalClasses'];

    // Get total payments this month
    $paymentsQuery = "SELECT SUM(amount) as monthlyRevenue FROM payments WHERE MONTH(payment_date) = MONTH(CURRENT_DATE())";
    $paymentsResult = $conn->query($paymentsQuery);
    $monthlyRevenue = $paymentsResult->fetch_assoc()['monthlyRevenue'] ?: 0;

    // Return stats
    echo json_encode([
        'activeUsers' => $activeUsers,
        'totalEnrollments' => $totalEnrollments,
        'totalClasses' => $totalClasses,
        'monthlyRevenue' => $monthlyRevenue,
        'success' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching statistics: ' . $e->getMessage()
    ]);
}

closeConnection($conn);
?>