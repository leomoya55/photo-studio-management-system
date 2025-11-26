<?php
/**
 * Academia Vanessa - Configuration File
 * Central configuration for the website
 */

if (!function_exists('config_env')) {
    function config_env($key, $default = '') {
        $candidates = [getenv($key), $_ENV[$key] ?? null, $_SERVER[$key] ?? null];
        foreach ($candidates as $value) {
            if ($value !== false && $value !== null && $value !== '') {
                return $value;
            }
        }
        return $default;
    }
}

return [
    // Studio Information
    'academy' => [
        'name' => 'Vale V Photography',
        'tagline' => 'Luz cálida, historias reales desde 2010',
        'description' => 'Vale V Photography es un estudio creativo dedicado a producir imágenes emotivas y profesionales para personas, marcas y eventos.',
        'founded_year' => 2010,
        'total_students' => 500
    ],
    
    // Contact Information
    'contact' => [
        'address' => '75 metros norte de correos, Zapote, San José, Costa Rica',
        'phone' => '+506 8676-4740',
        'email' => 'info@valevphotography.com',
        'hours' => [
            'monday_friday' => '09:00 - 21:00',
            'saturday' => '09:00 - 18:00',
            'sunday' => '10:00 - 16:00'
        ]
    ],
    
    // Social Media
    'social' => [
        'facebook' => 'https://www.facebook.com/share/1Czy4E7doQ/?mibextid=wwXIfr',
        'instagram' => 'https://www.instagram.com/valevphotography?igsh=MXZobjc0NWtod2gyMA%3D%3D&utm_source=qr',
        'youtube' => 'https://youtube.com/@valevphotography',
        'tiktok' => 'https://tiktok.com/@valevstudio'
    ],
    
    // Email Configuration
    'email' => [
        'smtp_host' => config_env('SMTP_HOST', 'smtp.gmail.com'),
        'smtp_port' => (int)config_env('SMTP_PORT', 587),
        'smtp_encryption' => config_env('SMTP_SECURE', 'tls'),
        'smtp_username' => config_env('SMTP_USER', 'vegamurillovaleria@gmail.com'),
        'smtp_password' => config_env('SMTP_PASS', ''),
        'from_email' => config_env('SENDER_EMAIL', 'info@valevphotography.com'),
        'from_name' => config_env('SENDER_NAME', 'Vale V Photography'),
        'admin_email' => config_env('ADMIN_EMAIL', 'info@valevphotography.com'),
        'reply_to' => config_env('REPLY_TO_EMAIL', config_env('SENDER_EMAIL', 'info@valevphotography.com'))
    ],
    
    // Website Settings
    'website' => [
        'url' => 'https://www.valevphotography.com',
        'timezone' => 'America/Mexico_City',
        'language' => 'es',
        'charset' => 'UTF-8'
    ],
    
    // Admin Settings
    'admin' => [
        'username' => 'admin',
        'password' => password_hash('vanessa2025', PASSWORD_DEFAULT), // Change this!
        'session_timeout' => 3600, // 1 hour
        'max_login_attempts' => 3
    ],
    
    // File Paths
    'paths' => [
        'classes_data' => 'data/classes.json',
        'instructors_data' => 'data/instructors.json',
        'enrollments_data' => 'data/enrollments.json',
        'contact_logs' => 'logs/contact_submissions.log',
        'upload_directory' => 'uploads/',
        'backup_directory' => 'backups/'
    ],
    
    // Security Settings
    'security' => [
        'csrf_protection' => true,
        'rate_limiting' => true,
        'max_requests_per_minute' => 30,
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf'],
        'max_file_size' => 5242880, // 5MB
        'encryption_key' => 'your_encryption_key_here'
    ],
    
    // Business Settings
    'business' => [
        'currency' => 'USD',
        'tax_rate' => 0.16, // 16%
        'enrollment_deadline_days' => 3,
        'cancellation_policy_hours' => 24,
        'trial_class_enabled' => true,
        'trial_class_price' => 25
    ],
    
    // Class Settings
    'classes' => [
        'default_duration' => 60, // minutes
        'max_capacity' => 20,
        'min_age' => 3,
        'max_age' => 99,
        'skill_levels' => ['Principiante', 'Intermedio', 'Avanzado', 'Profesional'],
        'payment_methods' => ['Efectivo', 'Tarjeta', 'Transferencia', 'PayPal']
    ],
    
    // Notification Settings
    'notifications' => [
        'send_enrollment_confirmation' => true,
        'send_class_reminders' => true,
        'send_payment_reminders' => true,
        'send_newsletter' => true,
        'reminder_hours_before_class' => 24
    ],
    
    // Feature Flags
    'features' => [
        'online_payments' => false,
        'video_lessons' => false,
        'mobile_app' => false,
        'booking_system' => true,
        'instructor_profiles' => true,
        'student_portal' => false,
        'performance_tracking' => false
    ],
    
    // API Settings (for future integrations)
    'api' => [
        'google_maps_key' => 'your_google_maps_api_key',
        'google_analytics_id' => 'GA-XXXXXXXXX',
        'facebook_pixel_id' => '',
        'mailchimp_api_key' => '',
        'stripe_public_key' => '',
        'stripe_secret_key' => ''
    ],
    
    // SEO Settings
    'seo' => [
        'meta_title' => 'Vale V Photography - Estudio Fotográfico en Costa Rica',
        'meta_description' => 'Sesiones fotográficas profesionales para retratos, marcas y eventos. Reserva tu experiencia con Vale V Photography y captura tus momentos más auténticos.',
        'meta_keywords' => 'fotografía profesional, estudio fotográfico, retratos, fotografía comercial, sesiones creativas',
        'og_image' => 'assets/images/og-image.jpg',
        'twitter_card' => 'summary_large_image'
    ]
];
?>