/* ===================================
    Vale V Photography - Main JavaScript
   =================================== */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('Vale V Photography website loaded successfully!');
    
    // Initialize all components
    initializeNavbar();
    initializeAnimations();
    initializeModals();
    initializeSmoothScroll();
    
    // Set active navigation
    setActiveNavItem();
});

/* ===================================
   Navigation Functions
   =================================== */

function initializeNavbar() {
    const navbar = document.querySelector('.navbar');
    
    // Handle navbar scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });
    
    // Handle mobile menu collapse
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (navbarCollapse.classList.contains('show')) {
                bootstrap.Collapse.getInstance(navbarCollapse).hide();
            }
        });
    });
}

function setActiveNavItem() {
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link:not(.btn)');
    const currentSection = getCurrentSection();
    
    navLinks.forEach(link => {
        link.classList.remove('active');
        const href = link.getAttribute('href');
        if (href === currentSection) {
            link.classList.add('active');
        }
    });
}

function getCurrentSection() {
    const sections = ['#inicio', '#clases', '#catalogo', '#redes', '#ubicacion'];
    const scrollPos = window.scrollY + 100;
    
    for (let section of sections) {
        const element = document.querySelector(section);
        if (element) {
            const offsetTop = element.offsetTop;
            const offsetBottom = offsetTop + element.offsetHeight;
            
            if (scrollPos >= offsetTop && scrollPos < offsetBottom) {
                return section;
            }
        }
    }
    
    return '#inicio';
}

/* ===================================
   Smooth Scrolling
   =================================== */

function initializeSmoothScroll() {
    const navLinks = document.querySelectorAll('a[href^="#"]');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            
            // Skip if it's a modal trigger
            if (href === '#loginModal' || href === '#registerModal') {
                return;
            }
            
            e.preventDefault();
            
            const targetSection = document.querySelector(href);
            if (targetSection) {
                const navbarHeight = document.querySelector('.navbar').offsetHeight;
                const targetPosition = targetSection.offsetTop - navbarHeight;
                
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
                
                // Update active nav item
                setTimeout(() => setActiveNavItem(), 100);
            }
        });
    });
}

/* ===================================
   Modal Functions
   =================================== */

function initializeModals() {
    // Handle login form submission
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    // Handle register form submission
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', handleRegister);
    }
    
    // Clear forms when modals are closed
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        modal.addEventListener('hidden.bs.modal', function() {
            const forms = this.querySelectorAll('form');
            forms.forEach(form => {
                form.reset();
                clearFormErrors(form);
            });
        });
    });
}

function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const email = formData.get('email');
    const password = formData.get('password');
    
    // Basic validation
    if (!validateEmail(email)) {
        showFormError('email', 'Por favor ingresa un email válido');
        return;
    }
    
    if (password.length < 6) {
        showFormError('password', 'La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    // Clear any previous errors
    clearFormErrors(e.target);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Iniciando sesión...';
    submitBtn.disabled = true;
    
    // Simulate login process (replace with actual authentication)
    setTimeout(() => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Close modal and show success message
        const modal = bootstrap.Modal.getInstance(document.getElementById('loginModal'));
        modal.hide();
        
        showNotification('¡Bienvenido a Vale V Photography!', 'success');
        
        // Here you would typically redirect or update UI for logged-in state
    }, 2000);
}

function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const name = formData.get('name');
    const email = formData.get('email');
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    
    // Validation
    if (name.length < 2) {
        showFormError('name', 'El nombre debe tener al menos 2 caracteres');
        return;
    }
    
    if (!validateEmail(email)) {
        showFormError('email', 'Por favor ingresa un email válido');
        return;
    }
    
    if (password.length < 6) {
        showFormError('password', 'La contraseña debe tener al menos 6 caracteres');
        return;
    }
    
    if (password !== confirmPassword) {
        showFormError('confirmPassword', 'Las contraseñas no coinciden');
        return;
    }
    
    // Clear any previous errors
    clearFormErrors(e.target);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="loading-spinner"></span> Registrando...';
    submitBtn.disabled = true;
    
    // Simulate registration process (replace with actual registration)
    setTimeout(() => {
        // Reset button
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
        
        // Close modal and show success message
        const modal = bootstrap.Modal.getInstance(document.getElementById('registerModal'));
        modal.hide();
        
        showNotification('¡Registro exitoso! Bienvenido a Vale V Photography!', 'success');
        
        // Here you would typically redirect or update UI for logged-in state
    }, 2000);
}

/* ===================================
   Form Validation Functions
   =================================== */

function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function showFormError(fieldName, message) {
    const field = document.querySelector(`[name="${fieldName}"]`);
    if (field) {
        field.classList.add('is-invalid');
        
        // Remove existing error message
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) {
            existingError.remove();
        }
        
        // Add new error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
}

function clearFormErrors(form) {
    const fields = form.querySelectorAll('.is-invalid');
    const errors = form.querySelectorAll('.invalid-feedback');
    
    fields.forEach(field => field.classList.remove('is-invalid'));
    errors.forEach(error => error.remove());
}

/* ===================================
   Animation Functions
   =================================== */

function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, observerOptions);
    
    // Observe all elements with animation classes
    const animatedElements = document.querySelectorAll('.animate-on-scroll');
    animatedElements.forEach(el => observer.observe(el));
    
    // Add scroll listener for navbar active state
    window.addEventListener('scroll', throttle(setActiveNavItem, 100));
}

/* ===================================
   Notification System
   =================================== */

function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : 'info'} notification-toast`;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    
    notification.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
            <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }
    }, 5000);
}

/* ===================================
   Utility Functions
   =================================== */

function throttle(func, wait) {
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

/* ===================================
   Additional Styles for Animations
   =================================== */

// Add CSS for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    .notification-toast {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: none;
        border-radius: 8px;
    }
    
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875em;
        color: #dc3545;
    }
`;

document.head.appendChild(notificationStyles);

/* ===================================
   Development Helper Functions
   =================================== */

// Console log for development
if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
    console.log('%c📸 Vale V Photography', 'color: #7a4a2d; font-size: 20px; font-weight: bold;');
    console.log('%cWebsite loaded successfully!', 'color: #4ecdc4; font-size: 14px;');
}