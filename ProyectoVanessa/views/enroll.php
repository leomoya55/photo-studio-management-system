<?php
/**
 * Academia Vanessa - Class Enrollment Handler
 * Handles class enrollment requests
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Configuration
$config = [
    'data_file' => 'data/enrollments.json',
    'admin_email' => 'admin@academialegend.com'
];

// Load classes data
function loadClasses() {
    $classes_file = 'data/classes.json';
    if (file_exists($classes_file)) {
        return json_decode(file_get_contents($classes_file), true);
    }
    return [];
}

// Load existing enrollments
function loadEnrollments() {
    if (file_exists($GLOBALS['config']['data_file'])) {
        return json_decode(file_get_contents($GLOBALS['config']['data_file']), true);
    }
    return [];
}

// Save enrollments
function saveEnrollments($enrollments) {
    $dir = dirname($GLOBALS['config']['data_file']);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    
    return file_put_contents(
        $GLOBALS['config']['data_file'], 
        json_encode($enrollments, JSON_PRETTY_PRINT)
    );
}

// Validate enrollment data
function validateEnrollment($data) {
    $errors = [];
    
    $required_fields = ['nombre', 'email', 'telefono', 'class_id', 'fecha_nacimiento'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo '$field' es obligatorio";
        }
    }
    
    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    // Phone validation
    if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $data['telefono'])) {
        $errors[] = 'El teléfono no es válido';
    }
    
    // Date validation
    if (!empty($data['fecha_nacimiento'])) {
        $date = DateTime::createFromFormat('Y-m-d', $data['fecha_nacimiento']);
        if (!$date || $date->format('Y-m-d') !== $data['fecha_nacimiento']) {
            $errors[] = 'La fecha de nacimiento no es válida';
        }
    }
    
    return $errors;
}

// Check class availability
function checkClassAvailability($class_id) {
    $classes = loadClasses();
    $enrollments = loadEnrollments();
    
    $class_info = null;
    foreach ($classes as $class) {
        if ($class['id'] === $class_id) {
            $class_info = $class;
            break;
        }
    }
    
    if (!$class_info) {
        return ['available' => false, 'message' => 'Clase no encontrada'];
    }
    
    // Count current enrollments for this class
    $current_enrollments = 0;
    foreach ($enrollments as $enrollment) {
        if ($enrollment['class_id'] === $class_id && $enrollment['status'] === 'active') {
            $current_enrollments++;
        }
    }
    
    $available_spots = $class_info['capacity'] - $current_enrollments;
    
    return [
        'available' => $available_spots > 0,
        'spots_remaining' => $available_spots,
        'class_info' => $class_info
    ];
}

// Generate enrollment confirmation email
function generateEnrollmentEmail($data, $class_info) {
    $template = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%); color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .class-info { background: white; padding: 15px; border-left: 4px solid #4ecdc4; margin: 15px 0; }
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Bienvenido/a a Academia Legend!</h1>
                <p>Confirmación de Inscripción</p>
            </div>
            <div class='content'>
                <p>Hola <strong>{$data['nombre']}</strong>,</p>
                <p>¡Felicitaciones! Tu inscripción ha sido procesada exitosamente.</p>
                
                <div class='class-info'>
                    <h3>Detalles de tu clase:</h3>
                    <ul>
                        <li><strong>Clase:</strong> {$class_info['name']}</li>
                        <li><strong>Nivel:</strong> {$class_info['level']}</li>
                        <li><strong>Horario:</strong> {$class_info['schedule']}</li>
                        <li><strong>Duración:</strong> {$class_info['duration']}</li>
                        <li><strong>Instructor:</strong> {$class_info['instructor']}</li>
                        <li><strong>Precio mensual:</strong> \${$class_info['price']}</li>
                    </ul>
                </div>
                
                <h3>Próximos pasos:</h3>
                <ol>
                    <li>Recibirás una llamada de confirmación en las próximas 24 horas</li>
                    <li>Te proporcionaremos detalles sobre el pago y el primer día de clase</li>
                    <li>Te enviaremos información sobre el vestuario requerido</li>
                </ol>
                
                <p><strong>Información de contacto para dudas:</strong><br>
                Teléfono: +1 (555) 123-4567<br>
                Email: info@academialegend.com</p>
                
                <p>¡Esperamos verte pronto en nuestro estudio!</p>
            </div>
            <div class='footer'>
                <p>Academia de Danza Legend<br>
                Transformando vidas a través de la danza desde 2008</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $template;
}

// Main processing
try {
    $input_data = $_POST;
    $errors = validateEnrollment($input_data);
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Errores de validación: ' . implode(', ', $errors)
        ]);
        exit;
    }
    
    // Check class availability
    $availability = checkClassAvailability($input_data['class_id']);
    
    if (!$availability['available']) {
        echo json_encode([
            'success' => false,
            'message' => 'Lo sentimos, esta clase está llena. ' . ($availability['message'] ?? '')
        ]);
        exit;
    }
    
    // Create enrollment record
    $enrollment = [
        'id' => uniqid('enroll_'),
        'timestamp' => date('Y-m-d H:i:s'),
        'nombre' => htmlspecialchars(trim($input_data['nombre'])),
        'email' => htmlspecialchars(trim($input_data['email'])),
        'telefono' => htmlspecialchars(trim($input_data['telefono'])),
        'fecha_nacimiento' => $input_data['fecha_nacimiento'],
        'class_id' => $input_data['class_id'],
        'experiencia' => htmlspecialchars(trim($input_data['experiencia'] ?? '')),
        'condiciones_medicas' => htmlspecialchars(trim($input_data['condiciones_medicas'] ?? '')),
        'contacto_emergencia' => htmlspecialchars(trim($input_data['contacto_emergencia'] ?? '')),
        'status' => 'pending', // pending, active, inactive
        'payment_status' => 'pending' // pending, paid, overdue
    ];
    
    // Load existing enrollments and add new one
    $enrollments = loadEnrollments();
    $enrollments[] = $enrollment;
    
    // Save enrollments
    if (saveEnrollments($enrollments)) {
        // Send confirmation email
        $confirmation_email = generateEnrollmentEmail($enrollment, $availability['class_info']);
        
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Academia Legend <info@academialegend.com>',
            'Reply-To: info@academialegend.com'
        ];
        
        $email_sent = mail(
            $enrollment['email'],
            "Confirmación de Inscripción - Academia Legend",
            $confirmation_email,
            implode("\r\n", $headers)
        );
        
        echo json_encode([
            'success' => true,
            'message' => '¡Inscripción exitosa! Revisa tu email para más detalles.',
            'enrollment_id' => $enrollment['id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar la inscripción. Inténtalo nuevamente.'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo más tarde.'
    ]);
}
?>