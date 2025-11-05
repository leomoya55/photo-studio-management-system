<?php
/**
 * Academia Vanessa - Configuration File
 * Central configuration for the website
 */

return [
    // Academy Information
    'academy' => [
        'name' => 'Academia de Danza Legend',
        'tagline' => 'Transformando vidas a través de la danza desde 2008',
        'description' => 'Con más de 15 años de experiencia, Academia Legend se ha convertido en el referente de la danza en nuestra ciudad.',
        'founded_year' => 2008,
        'total_students' => 500
    ],
    
    // Contact Information
    'contact' => [
        'address' => 'Calle Principal 123, Ciudad, País',
        'phone' => '+1 (555) 123-4567',
        'email' => 'info@academialegend.com',
        'hours' => [
            'monday_friday' => '09:00 - 21:00',
            'saturday' => '09:00 - 18:00',
            'sunday' => '10:00 - 16:00'
        ]
    ],
    
    // Social Media
    'social' => [
        'facebook' => 'https://facebook.com/academialegend',
        'instagram' => 'https://instagram.com/academialegend',
        'youtube' => 'https://youtube.com/academialegend',
        'tiktok' => 'https://tiktok.com/@academialegend'
    ],
    
    // Email Configuration
    'email' => [
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_encryption' => 'tls',
        'smtp_username' => 'info@academialegend.com',
        'smtp_password' => 'your_app_password_here',
        'from_email' => 'info@academialegend.com',
        'from_name' => 'Academia Legend',
        'admin_email' => 'admin@academialegend.com'
    ],
    
    // Website Settings
    'website' => [
        'url' => 'https://www.academialegend.com',
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
        'meta_title' => 'Academia de Danza Legend - Clases de Danza Profesionales',
        'meta_description' => 'Aprende danza con los mejores instructores. Ballet, jazz, hip hop, salsa y más. ¡Únete a nuestra familia de danza!',
        'meta_keywords' => 'academia danza, clases ballet, hip hop, jazz, salsa, instructores profesionales',
        'og_image' => 'assets/images/og-image.jpg',
        'twitter_card' => 'summary_large_image'
    ]
];
?>