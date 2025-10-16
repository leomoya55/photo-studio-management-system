<?php
/**
 * Academia Vanessa - Contact Form Handler
 * Handles form submissions and sends email notifications
 */

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Configuration
$config = [
    'smtp_host' => 'localhost', // Change to your SMTP server
    'smtp_port' => 587,
    'smtp_username' => 'info@academialegend.com', // Change to your email
    'smtp_password' => 'your_password', // Change to your password
    'from_email' => 'info@academialegend.com',
    'from_name' => 'Academia Legend',
    'to_email' => 'info@academialegend.com', // Where to send form submissions
    'admin_email' => 'admin@academialegend.com' // Admin notifications
];

// Input validation and sanitization
function validateInput($data) {
    $errors = [];
    
    // Required fields
    $required_fields = ['nombre', 'email', 'tipo_clase', 'mensaje'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = "El campo '$field' es obligatorio";
        }
    }
    
    // Email validation
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'El email no es válido';
    }
    
    // Phone validation (optional field)
    if (!empty($data['telefono']) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $data['telefono'])) {
        $errors[] = 'El teléfono no es válido';
    }
    
    // Message length validation
    if (!empty($data['mensaje']) && strlen($data['mensaje']) < 10) {
        $errors[] = 'El mensaje debe tener al menos 10 caracteres';
    }
    
    if (!empty($data['mensaje']) && strlen($data['mensaje']) > 1000) {
        $errors[] = 'El mensaje no puede exceder los 1000 caracteres';
    }
    
    return $errors;
}

// Sanitize input data
function sanitizeInput($data) {
    $sanitized = [];
    foreach ($data as $key => $value) {
        $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    return $sanitized;
}

// Send email function
function sendEmail($to, $subject, $message, $headers) {
    // Use PHP's mail() function or implement SMTP
    return mail($to, $subject, $message, $headers);
}

// Generate email templates
function generateCustomerEmail($data) {
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
            .footer { padding: 20px; text-align: center; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>¡Gracias por contactarnos!</h1>
                <p>Academia de Danza Legend</p>
            </div>
            <div class='content'>
                <p>Hola <strong>{$data['nombre']}</strong>,</p>
                <p>Hemos recibido tu mensaje y nos pondremos en contacto contigo muy pronto.</p>
                
                <h3>Resumen de tu consulta:</h3>
                <ul>
                    <li><strong>Clase de interés:</strong> {$data['tipo_clase']}</li>
                    <li><strong>Email:</strong> {$data['email']}</li>
                    " . (!empty($data['telefono']) ? "<li><strong>Teléfono:</strong> {$data['telefono']}</li>" : "") . "
                </ul>
                
                <p><strong>Tu mensaje:</strong></p>
                <p style='background: white; padding: 15px; border-left: 4px solid #ff6b6b;'>
                    {$data['mensaje']}
                </p>
                
                <p>Nuestro equipo revisará tu consulta y te responderemos en un plazo máximo de 24 horas.</p>
                
                <p>Mientras tanto, te invitamos a seguirnos en nuestras redes sociales para mantenerte al día con nuestras actividades y eventos especiales.</p>
            </div>
            <div class='footer'>
                <p>Academia de Danza Legend<br>
                Calle Principal 123, Ciudad<br>
                Teléfono: +1 (555) 123-4567<br>
                Email: info@academialegend.com</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $template;
}

function generateAdminEmail($data) {
    $timestamp = date('Y-m-d H:i:s');
    
    $template = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .data-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .data-table th, .data-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            .data-table th { background-color: #4ecdc4; color: white; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nueva Consulta - Academia Legend</h1>
                <p>Formulario de contacto del sitio web</p>
            </div>
            <div class='content'>
                <p><strong>Fecha y hora:</strong> {$timestamp}</p>
                
                <table class='data-table'>
                    <tr>
                        <th>Campo</th>
                        <th>Valor</th>
                    </tr>
                    <tr>
                        <td>Nombre</td>
                        <td>{$data['nombre']}</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>{$data['email']}</td>
                    </tr>
                    <tr>
                        <td>Teléfono</td>
                        <td>" . (!empty($data['telefono']) ? $data['telefono'] : 'No proporcionado') . "</td>
                    </tr>
                    <tr>
                        <td>Clase de interés</td>
                        <td>{$data['tipo_clase']}</td>
                    </tr>
                </table>
                
                <h3>Mensaje:</h3>
                <div style='background: white; padding: 15px; border-left: 4px solid #ff6b6b;'>
                    {$data['mensaje']}
                </div>
                
                <p style='margin-top: 20px;'><strong>Acción requerida:</strong> Responder a esta consulta dentro de 24 horas.</p>
            </div>
        </div>
    </body>
    </html>";
    
    return $template;
}

// Log submission function
function logSubmission($data) {
    $logFile = 'logs/contact_submissions.log';
    $logDir = dirname($logFile);
    
    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $logEntry = [
        'timestamp' => $timestamp,
        'ip' => $ip,
        'user_agent' => $userAgent,
        'data' => $data
    ];
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

// Main processing
try {
    // Get and sanitize input data
    $input_data = $_POST;
    $errors = validateInput($input_data);
    
    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Errores de validación: ' . implode(', ', $errors)
        ]);
        exit;
    }
    
    $data = sanitizeInput($input_data);
    
    // Log the submission
    logSubmission($data);
    
    // Prepare emails
    $customer_subject = "Confirmación de consulta - Academia Legend";
    $admin_subject = "Nueva consulta del sitio web - Academia Legend";
    
    $customer_message = generateCustomerEmail($data);
    $admin_message = generateAdminEmail($data);
    
    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . $config['from_name'] . ' <' . $config['from_email'] . '>',
        'Reply-To: ' . $config['from_email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    $headers_string = implode("\r\n", $headers);
    
    // Send confirmation email to customer
    $customer_sent = sendEmail(
        $data['email'],
        $customer_subject,
        $customer_message,
        $headers_string
    );
    
    // Send notification email to admin
    $admin_sent = sendEmail(
        $config['to_email'],
        $admin_subject,
        $admin_message,
        $headers_string
    );
    
    if ($customer_sent && $admin_sent) {
        echo json_encode([
            'success' => true,
            'message' => '¡Mensaje enviado exitosamente! Te contactaremos pronto.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al enviar el mensaje. Por favor, inténtalo nuevamente.'
        ]);
    }
    
} catch (Exception $e) {
    // Log error
    error_log("Contact form error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error interno del servidor. Por favor, inténtalo más tarde.'
    ]);
}
?>