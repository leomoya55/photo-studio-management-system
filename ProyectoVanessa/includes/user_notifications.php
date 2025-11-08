<?php
// includes/user_notifications.php - Check user enrollment status and show notifications

function getUserNotifications($user_id, $conn) {
    $notifications = [];
    
    try {
        // Check for inactive enrollments (not paid)
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.class_name,
                e.selected_schedule,
                e.enrollment_date,
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
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => 'payment_required',
                'level' => 'warning',
                'title' => 'Pago Pendiente',
                'message' => "Tu inscripción para la clase <strong>{$row['class_name']}</strong> está pendiente de pago.",
                'details' => "Horario: {$row['selected_schedule']} | Monto: ₡" . number_format($row['price']) . " | Inscrito hace {$row['days_since_enrollment']} días",
                'action' => 'Contacta con la academia para realizar tu pago',
                'enrollment_id' => $row['id']
            ];
        }
        
        // Check for pending enrollments (waiting approval)
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.class_name,
                e.selected_schedule,
                e.enrollment_date,
                c.price,
                DATEDIFF(NOW(), e.enrollment_date) as days_since_enrollment
            FROM enrollments e
            LEFT JOIN classes c ON e.class_name = c.name
            WHERE e.user_id = ? AND e.status = 'pending'
            ORDER BY e.enrollment_date DESC
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'type' => 'pending_approval',
                'level' => 'info',
                'title' => 'Inscripción en Revisión',
                'message' => "Tu solicitud para la clase <strong>{$row['class_name']}</strong> está siendo revisada.",
                'details' => "Horario: {$row['selected_schedule']} | Monto: ₡" . number_format($row['price']) . " | Solicitado hace {$row['days_since_enrollment']} días",
                'action' => 'Te contactaremos pronto para confirmar tu inscripción',
                'enrollment_id' => $row['id']
            ];
        }
        
        // Check for active enrollments (success message)
        $stmt = $conn->prepare("
            SELECT 
                e.id,
                e.class_name,
                e.selected_schedule,
                c.price
            FROM enrollments e
            LEFT JOIN classes c ON e.class_name = c.name
            WHERE e.user_id = ? AND e.status = 'active'
            ORDER BY e.enrollment_date DESC
            LIMIT 3
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $active_classes = [];
        while ($row = $result->fetch_assoc()) {
            $active_classes[] = $row['class_name'] . " ({$row['selected_schedule']})";
        }
        
        if (!empty($active_classes)) {
            $notifications[] = [
                'type' => 'active_enrollments',
                'level' => 'success',
                'title' => '¡Clases Activas!',
                'message' => "Estás inscrito(a) en: " . implode(', ', $active_classes),
                'details' => 'Tus pagos están al día. ¡Disfruta tus clases!',
                'action' => '',
                'enrollment_id' => null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error getting user notifications: " . $e->getMessage());
    }
    
    return $notifications;
}

function displayNotifications($notifications) {
    if (empty($notifications)) {
        return '';
    }
    
    $html = '<div class="user-notifications mb-4">';
    
    foreach ($notifications as $notification) {
        $alertClass = 'alert-' . ($notification['level'] === 'warning' ? 'warning' : 
                                 ($notification['level'] === 'success' ? 'success' : 'info'));
        
        $icon = $notification['level'] === 'warning' ? '⚠️' : 
               ($notification['level'] === 'success' ? '✅' : 'ℹ️');
        
        $html .= "
        <div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
            <div class='d-flex align-items-start'>
                <span class='me-2' style='font-size: 1.2em;'>{$icon}</span>
                <div class='flex-grow-1'>
                    <h6 class='alert-heading mb-1'>{$notification['title']}</h6>
                    <p class='mb-1'>{$notification['message']}</p>";
        
        if (!empty($notification['details'])) {
            $html .= "<small class='text-muted d-block'>{$notification['details']}</small>";
        }
        
        if (!empty($notification['action'])) {
            $html .= "<small class='fw-bold d-block mt-1'>{$notification['action']}</small>";
        }
        
        $html .= "
                </div>
            </div>
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
    
    $html .= '</div>';
    
    return $html;
}

// Function to get payment reminder specifically for inactive users
function getPaymentReminderMessage($user_id, $conn) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as inactive_count,
                   GROUP_CONCAT(class_name SEPARATOR ', ') as class_names
            FROM enrollments 
            WHERE user_id = ? AND status = 'inactive'
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['inactive_count'] > 0) {
            return [
                'has_pending_payment' => true,
                'message' => "Tienes {$row['inactive_count']} inscripción(es) pendiente(s) de pago: {$row['class_names']}",
                'count' => $row['inactive_count']
            ];
        }
    } catch (Exception $e) {
        error_log("Error getting payment reminder: " . $e->getMessage());
    }
    
    return ['has_pending_payment' => false];
}
?>