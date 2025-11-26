/* ===================================
   Academia Vanessa - JavaScript
   =================================== */

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all functions
    initNavigation();
    initScrollAnimations();
    initGallery();
    initContactForm();
    loadClasses();
    loadInstructors();
    initSmoothScrolling();
});

// Navigation Functions
function initNavigation() {
    const navbar = document.getElementById('mainNav');
    
    // Navbar scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Active navigation highlighting
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    window.addEventListener('scroll', function() {
        let current = '';
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            
            if (scrollY >= (sectionTop - 200)) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    });
}

// Smooth Scrolling for Navigation Links
function initSmoothScrolling() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                const offsetTop = target.offsetTop - 80; // Account for fixed navbar
                
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
}

// Scroll Animations
function initScrollAnimations() {
    const animateElements = document.querySelectorAll('.card, .feature-item, .gallery-item');
    
    animateElements.forEach(element => {
        element.classList.add('animate-on-scroll');
    });
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    animateElements.forEach(element => {
        observer.observe(element);
    });
}

// Gallery Functions
function initGallery() {
    const galleryItems = document.querySelectorAll('.gallery-item .placeholder-gallery');
    
    galleryItems.forEach(item => {
        item.addEventListener('click', function() {
            // Show placeholder message in modal
            const modalBody = document.querySelector('#galleryModal .modal-body');
            modalBody.innerHTML = '<div class="placeholder-modal-image">Agrega tu imagen aquí</div>';
        });
    });
}

// Contact Form Handling
function initContactForm() {
    const form = document.getElementById('contactForm');
    
    if (!form) {
        return;
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitForm();
        }
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('is-invalid')) {
                validateField(this);
            }
        });
    });
}

function validateForm() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    let errorMessage = '';
    
    // Remove previous validation classes
    field.classList.remove('is-valid', 'is-invalid');
    
    // Check if required field is empty
    if (field.hasAttribute('required') && !value) {
        isValid = false;
        errorMessage = 'Este campo es obligatorio';
    }
    
    // Email validation
    if (field.type === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Ingrese un email válido';
        }
    }
    
    // Phone validation
    if (field.type === 'tel' && value) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Ingrese un teléfono válido';
        }
    }
    
    // Add validation classes and feedback
    if (isValid) {
        field.classList.add('is-valid');
        removeErrorMessage(field);
    } else {
        field.classList.add('is-invalid');
        showErrorMessage(field, errorMessage);
    }
    
    return isValid;
}

function showErrorMessage(field, message) {
    removeErrorMessage(field);
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function removeErrorMessage(field) {
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

function submitForm() {
    const form = document.getElementById('contactForm');
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    const endpoint = form.dataset.endpoint || 'api/contact_submit.php';
    
    // Show loading state
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Enviando...';
    submitBtn.disabled = true;
    
    // Create FormData object
    const formData = new FormData(form);
    
    // Submit form via fetch
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccessMessage('¡Mensaje enviado exitosamente! Te contactaremos pronto.');
            form.reset();
            form.querySelectorAll('.is-valid').forEach(el => el.classList.remove('is-valid'));
        } else {
            showErrorMessage(form, data.message || 'Error al enviar el mensaje. Inténtalo nuevamente.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showErrorMessage(form, 'Error al enviar el mensaje. Verifica tu conexión e inténtalo nuevamente.');
    })
    .finally(() => {
        // Restore button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const form = document.getElementById('contactForm');
    form.parentNode.insertBefore(alertDiv, form);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Load Classes from JSON
async function loadClasses() {
    try {
        const response = await fetch('data/classes.json');
        const classes = await response.json();
        
        const container = document.getElementById('clasesContainer');
        container.innerHTML = '';
        
        classes.forEach(classItem => {
            const classCard = createClassCard(classItem);
            container.appendChild(classCard);
        });
    } catch (error) {
        console.error('Error loading classes:', error);
        document.getElementById('clasesContainer').innerHTML = 
            '<div class="col-12"><p class="text-center">Error al cargar las clases. Inténtalo más tarde.</p></div>';
    }
}

function createClassCard(classItem) {
    const col = document.createElement('div');
    col.className = 'col-lg-4 col-md-6 mb-4';
    
    col.innerHTML = `
        <div class="card h-100">
            <div class="placeholder-class card-img-top"></div>
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">${classItem.name}</h5>
                <p class="card-text">${classItem.description}</p>
                <div class="mt-auto">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary">${classItem.level}</span>
                        <span class="text-primary fw-bold">$${classItem.price}/mes</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> ${classItem.duration}
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-calendar"></i> ${classItem.schedule}
                        </small>
                    </div>
                    <button class="btn btn-primary w-100" onclick="enrollClass('${classItem.id}')">
                        Inscribirse
                    </button>
                </div>
            </div>
        </div>
    `;
    
    return col;
}

// Load Instructors from JSON
async function loadInstructors() {
    try {
        const response = await fetch('data/instructors.json');
        const instructors = await response.json();
        
        const container = document.getElementById('instructoresContainer');
        container.innerHTML = '';
        
        instructors.forEach(instructor => {
            const instructorCard = createInstructorCard(instructor);
            container.appendChild(instructorCard);
        });
    } catch (error) {
        console.error('Error loading instructors:', error);
        document.getElementById('instructoresContainer').innerHTML = 
            '<div class="col-12"><p class="text-center">Error al cargar los instructores. Inténtalo más tarde.</p></div>';
    }
}

function createInstructorCard(instructor) {
    const col = document.createElement('div');
    col.className = 'col-lg-3 col-md-6 mb-4';
    
    col.innerHTML = `
        <div class="card h-100 text-center">
            <div class="placeholder-instructor mt-3"></div>
            <div class="card-body">
                <h5 class="card-title">${instructor.name}</h5>
                <p class="text-primary">${instructor.specialty}</p>
                <p class="card-text">${instructor.bio}</p>
                <div class="d-flex justify-content-center">
                    ${instructor.social.instagram ? `<a href="${instructor.social.instagram}" class="text-primary me-2"><i class="fab fa-instagram"></i></a>` : ''}
                    ${instructor.social.facebook ? `<a href="${instructor.social.facebook}" class="text-primary me-2"><i class="fab fa-facebook"></i></a>` : ''}
                    ${instructor.social.youtube ? `<a href="${instructor.social.youtube}" class="text-primary"><i class="fab fa-youtube"></i></a>` : ''}
                </div>
            </div>
        </div>
    `;
    
    return col;
}

// Class Enrollment Function
function enrollClass(classId) {
    // Create enrollment modal or redirect to enrollment form
    const modal = new bootstrap.Modal(document.createElement('div'));
    
    // For now, show an alert with class enrollment info
    alert(`¡Gracias por tu interés! Para inscribirte en esta clase, por favor contáctanos al teléfono o completa el formulario de contacto especificando la clase de interés.`);
    
    // Scroll to contact section
    document.querySelector('#contacto').scrollIntoView({ behavior: 'smooth' });
}

// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Performance optimization for scroll events
const debouncedScrollHandler = debounce(() => {
    // Handle scroll events here if needed
}, 10);

window.addEventListener('scroll', debouncedScrollHandler);

// Handle page visibility change
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        // Page is hidden, pause animations or videos if any
        console.log('Page hidden');
    } else {
        // Page is visible, resume animations or videos
        console.log('Page visible');
    }
});

// Service Worker Registration (for PWA capabilities)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        navigator.serviceWorker.register('/sw.js')
        .then(function(registration) {
            console.log('ServiceWorker registration successful');
        })
        .catch(function(err) {
            console.log('ServiceWorker registration failed');
        });
    });
}

// Error Handling
window.addEventListener('error', function(e) {
    console.error('JavaScript Error:', e.error);
    // You could send this to an error reporting service
});

// Unhandled Promise Rejections
window.addEventListener('unhandledrejection', function(e) {
    console.error('Unhandled Promise Rejection:', e.reason);
    // Prevent the default behavior
    e.preventDefault();
});