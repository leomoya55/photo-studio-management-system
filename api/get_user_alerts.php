<?php
// api/get_user_alerts.php - API endpoint to get user payment alerts
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['alerts' => []]);
    exit;
}

require_once '../config/db_connect.php';

$user_id = $_SESSION['user_id'];
$alerts = [];

try {
    // Check for inactive enrollments (payment required)
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.class_name,
            e.selected_schedule,
            e.enrollment_date,
            e.status,
            c.price,
            DATEDIFF(NOW(), e.enrollment_date) as days_since_enrollment
        FROM enrollments e
        LEFT JOIN classes c ON e.class_name = c.name
        WHERE e.user_id = ? AND e.status = 'inactive'
        ORDER BY e.enrollment_date DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $inactive_classes = [];
    $total_amount = 0;
    
    while ($row = $result->fetch_assoc()) {
        $inactive_classes[] = [
            'class_name' => $row['class_name'],
            'schedule' => $row['selected_schedule'] ?: 'Horario no especificado',
            'price' => $row['price'] ?: 0,
            'days_since' => $row['days_since_enrollment']
        ];
        $total_amount += ($row['price'] ?: 0);
    }
    
    if (!empty($inactive_classes)) {
        $alerts[] = [
            'type' => 'payment_required',
            'priority' => 1,
            'title' => '⚠️ No Estás Asistiendo a Clases',
            'message' => count($inactive_classes) === 1 
                ? "Tu inscripción en <strong>{$inactive_classes[0]['class_name']}</strong> está marcada como <span style='color: #c2410c; font-weight: bold;'>NO ASISTIENDO</span>. Contacta con la academia para resolver cualquier problema y volver a las clases."
                : "Tienes <strong>" . count($inactive_classes) . " clases</strong> marcadas como <span style='color: #c2410c; font-weight: bold;'>NO ASISTIENDO</span>. Contacta con la academia para resolver cualquier problema.",
            'details' => $inactive_classes,
            'total_amount' => $total_amount
        ];
    }
    
    // Check for rejected enrollments
    $stmt = $conn->prepare("
        SELECT 
            e.id,
            e.class_name,
            e.selected_schedule,
            e.enrollment_date,
            e.progress_notes,
            DATEDIFF(NOW(), e.enrollment_date) as days_since_enrollment
        FROM enrollments e
        WHERE e.user_id = ? AND e.status = 'rejected'
        ORDER BY e.enrollment_date DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rejected_classes = [];
    while ($row = $result->fetch_assoc()) {
        $rejected_classes[] = [
            'class_name' => $row['class_name'],
            'schedule' => $row['selected_schedule'] ?: 'Horario no especificado',
            'days_since' => $row['days_since_enrollment'],
            'reason' => $row['progress_notes'] ?: 'No se especificó motivo'
        ];
    }
    
    // Only show rejected alert if no payment alerts (payment has higher priority)
    if (!empty($rejected_classes) && empty($alerts)) {
        $alerts[] = [
            'type' => 'enrollment_rejected',
            'priority' => 2,
            'title' => '❌ Inscripción No Aprobada',
            'message' => count($rejected_classes) === 1 
                ? "Lamentablemente, tu solicitud para <strong>{$rejected_classes[0]['class_name']}</strong> no pudo ser aprobada en este momento."
                : "Algunas de tus solicitudes de inscripción no pudieron ser aprobadas.",
            'details' => $rejected_classes
        ];
    }
    
    // Check for pending enrollments (waiting approval) - lowest priority
    $stmt = $conn->prepare("
        SELECT 
            e.class_name,
            e.selected_schedule,
            e.enrollment_date,
            DATEDIFF(NOW(), e.enrollment_date) as days_since_enrollment
        FROM enrollments e
        WHERE e.user_id = ? AND e.status = 'pending'
        ORDER BY e.enrollment_date DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pending_classes = [];
    while ($row = $result->fetch_assoc()) {
        $pending_classes[] = [
            'class_name' => $row['class_name'],
            'schedule' => $row['selected_schedule'] ?: 'Horario no especificado',
            'days_since' => $row['days_since_enrollment']
        ];
    }
    
    // Only show pending alert if no payment or rejection alerts
    if (!empty($pending_classes) && empty($alerts)) {
        $alerts[] = [
            'type' => 'pending_approval',
            'priority' => 3,
            'title' => '⏳ Inscripción Pendiente',
            'message' => count($pending_classes) === 1 
                ? "Tu inscripción a <strong>{$pending_classes[0]['class_name']}</strong> está siendo revisada por la administración."
                : "Tienes " . count($pending_classes) . " inscripciones esperando aprobación.",
            'details' => $pending_classes
        ];
    }
    
} catch (Exception $e) {
    error_log("Error getting user alerts: " . $e->getMessage());
}

echo json_encode(['alerts' => $alerts], JSON_UNESCAPED_UNICODE);
?>