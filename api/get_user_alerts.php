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

    // Check for recently approved enrollments (last 7 days)
    // Support legacy schema where 'active' represents an approved enrollment
    $stmt = $conn->prepare("
        SELECT 
            e.class_name,
            e.selected_schedule,
            e.updated_at,
            e.status
        FROM enrollments e
        WHERE e.user_id = ? 
          AND e.status IN ('approved','active')
          AND e.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY e.updated_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $approved_classes = [];
    while ($row = $result->fetch_assoc()) {
        $approved_classes[] = [
            'class_name' => $row['class_name'],
            'schedule' => $row['selected_schedule'] ?: 'Horario no especificado',
            'approved_at' => $row['updated_at']
        ];
    }
    if (!empty($approved_classes)) {
        $alerts[] = [
            'type' => 'enrollment_approved',
            'priority' => 1,
            'title' => '✅ ¡Inscripción Aprobada!',
            'message' => count($approved_classes) === 1
                ? "Tu inscripción a <strong>{$approved_classes[0]['class_name']}</strong> fue aprobada."
                : "Tienes " . count($approved_classes) . " inscripciones aprobadas recientemente.",
            'details' => $approved_classes
        ];
    }

    // Check for recent order status changes (last 7 days)
    $stmt = $conn->prepare("
        SELECT 
            o.order_number,
            o.status,
            o.updated_at,
            o.total_amount,
            o.delivery_cost,
            COALESCE(SUM(oi.product_price * oi.quantity), 0) AS items_subtotal
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        WHERE o.user_id = ? 
          AND o.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
          AND o.status IN ('approved','completed','canceled','cancelled','paid','processing','delivered','shipped')
        GROUP BY o.id
        ORDER BY o.updated_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order_updates = [];
    while ($row = $result->fetch_assoc()) {
        $st = strtolower($row['status']);
        if ($st === 'cancelled') { $st = 'canceled'; }
        if (in_array($st, ['paid','processing'])) { $st = 'approved'; }
        if (in_array($st, ['delivered','shipped'])) { $st = 'completed'; }
        $baseTotal = (float)($row['total_amount'] ?? 0);
        $deliveryCost = (float)($row['delivery_cost'] ?? 0);
        $itemsSubtotal = (float)($row['items_subtotal'] ?? 0);
        $grandTotal = $itemsSubtotal > 0
            ? max($baseTotal, $itemsSubtotal + $deliveryCost)
            : ($baseTotal > 0 ? $baseTotal : $deliveryCost);
        $order_updates[] = [
            'order_number' => $row['order_number'],
            'status' => $st,
            'updated_at' => $row['updated_at'],
            'total' => $grandTotal
        ];
    }
    if (!empty($order_updates)) {
        $alerts[] = [
            'type' => 'order_update',
            'priority' => 2,
            'title' => '🛒 Actualización de Orden',
            'message' => count($order_updates) === 1
                ? "Tu orden <strong>#" . $order_updates[0]['order_number'] . "</strong> fue <strong>" . $order_updates[0]['status'] . "</strong>."
                : "Tienes " . count($order_updates) . " actualizaciones recientes de órdenes.",
            'details' => $order_updates
        ];
    }
    
} catch (Exception $e) {
    error_log("Error getting user alerts: " . $e->getMessage());
}

echo json_encode(['alerts' => $alerts], JSON_UNESCAPED_UNICODE);
?>