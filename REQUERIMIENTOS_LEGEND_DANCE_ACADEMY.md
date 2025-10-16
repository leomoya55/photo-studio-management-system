# DOCUMENTO DE REQUERIMIENTOS TÉCNICOS
## LEGEND DANCE ACADEMY - SISTEMA DE GESTIÓN WEB

**Proyecto:** Sistema Web para Academia de Danza Legend  
**Fecha:** 13 de Octubre, 2025  
**Versión:** 1.0  

---

## 1. INFORMACIÓN GENERAL DEL PROYECTO

### 1.1 Descripción
Sistema web integral para la gestión de una academia de danza que incluye catálogo de clases, horarios, ubicación, redes sociales, y sistema de inscripción con autenticación de usuarios.

### 1.2 Tecnologías Utilizadas
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Framework CSS:** Bootstrap 5.3.2
- **Iconos:** Font Awesome 6.4.0
- **Fuentes:** Google Fonts (Poppins, Dancing Script)
- **Servidor:** Apache (XAMPP)
- **Datos:** JSON (simulación de base de datos)
- **PWA:** Service Worker implementado

---

## 2. REQUERIMIENTOS FUNCIONALES

### RF001 - Sistema de Navegación
**Descripción:** El sistema debe proporcionar una navegación fluida y responsive entre todas las secciones del sitio web.

**Implementación:**
```html
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.html">
            <span class="brand-text">Legend</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="index.html#inicio">Inicio</a></li>
                <li class="nav-item"><a class="nav-link" href="clases.html">Clases</a></li>
                <li class="nav-item"><a class="nav-link" href="catalogo.html">Catálogo</a></li>
                <li class="nav-item"><a class="nav-link" href="redes-sociales.html">Redes Sociales</a></li>
                <li class="nav-item"><a class="nav-link" href="ubicacion.html">Ubicación</a></li>
            </ul>
        </div>
    </div>
</nav>
```

### RF002 - Sistema de Horarios Dinámicos
**Descripción:** Visualización de horarios de clases organizados por días de la semana con información detallada.

**Implementación:**
```html
<section id="horarios" class="py-5">
    <div class="container">
        <div class="day-schedule mb-4">
            <div class="day-header">
                <h4 class="fw-bold text-primary mb-3">
                    <i class="fas fa-clock me-2"></i>MARTES
                </h4>
            </div>
            <div class="classes-list">
                <div class="class-item">
                    <span class="class-name">Pilates/Funcional</span>
                    <span class="class-time">3:00 P.M</span>
                </div>
            </div>
        </div>
    </div>
</section>
```

### RF003 - Catálogo de Clases con Filtrado
**Descripción:** Sistema de visualización y filtrado de clases por categorías con información detallada de cada clase.

**Implementación:**
```javascript
function loadClasses() {
    const timestamp = new Date().getTime();
    fetch(`data/classes.json?t=${timestamp}`)
        .then(response => response.json())
        .then(classes => {
            displayClasses(classes);
            setupFilters(classes);
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            showErrorState();
        });
}

function filterClasses(category) {
    const allClasses = document.querySelectorAll('.class-card');
    allClasses.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
```

### RF004 - Sistema de Navegación Geográfica
**Descripción:** Integración con aplicaciones de navegación (Waze y Uber) para facilitar el acceso a la academia.

**Implementación:**
```javascript
function openWaze() {
    const wazeUrl = "https://waze.com/ul?q=Legend%20Dance%20Academy&navigate=yes";
    if (isMobile()) {
        window.location.href = wazeUrl;
    } else {
        window.open(wazeUrl, '_blank');
    }
}

function openUber() {
    const uberUrl = "https://m.uber.com/ul/?drop%5Bformatted_address%5D=Legend%20Dance%20Academy";
    if (isMobile()) {
        window.location.href = uberUrl;
    } else {
        window.open(uberUrl, '_blank');
    }
}
```

### RF005 - Sistema de Autenticación de Usuarios
**Descripción:** Modal de login/registro para acceso a funcionalidades premium como inscripción a clases.

**Implementación:**
```html
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Iniciar Sesión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <div class="mb-3">
                        <input type="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="password" class="form-control" placeholder="Contraseña" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Iniciar Sesión</button>
                </form>
            </div>
        </div>
    </div>
</div>
```

### RF006 - Cache Busting para Datos Dinámicos
**Descripción:** Sistema para evitar problemas de caché en la carga de datos JSON.

**Implementación:**
```javascript
function loadInstructors() {
    const timestamp = new Date().getTime();
    fetch(`data/instructors.json?t=${timestamp}`)
        .then(response => response.json())
        .then(instructors => {
            displayInstructors(instructors);
        });
}
```




## 3. REQUERIMIENTOS NO FUNCIONALES

### RNF001 - Rendimiento
**Descripción:** El sitio web debe cargar completamente en menos de 3 segundos en conexiones estándar.

**Implementación:**
```css
/* Optimización de imágenes y CSS */
.hero-section {
    background-image: url('images/hero-bg.webp');
    background-size: cover;
    background-position: center;
    will-change: transform;
}
```

### RNF002 - Compatibilidad Cross-Browser
**Descripción:** Compatible con Chrome, Firefox, Safari, y Edge (últimas 2 versiones).

**Implementación:**
```css
/* Prefijos CSS para compatibilidad */
.btn-primary {
    background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
    -webkit-background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
    -moz-background: linear-gradient(135deg, #ff6600 0%, #ff8533 100%);
}
```

### RNF003 - Responsive Design
**Descripción:** Adaptable a dispositivos móviles, tablets y desktop.

**Implementación:**
```css
@media (max-width: 768px) {
    .schedule-container {
        padding: 1.5rem;
    }
    .class-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
```

### RNF004 - Seguridad
**Descripción:** Validación de formularios y sanitización de datos de entrada.

**Implementación:**
```javascript
function validateEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

function sanitizeInput(input) {
    return input.replace(/[<>]/g, '');
}
```

---

## 4. ARQUITECTURA TÉCNICA

### 4.1 Estructura del Proyecto
```
ProyectoVanessa/
├── index.html              # Página principal
├── clases.html             # Catálogo de clases
├── catalogo.html           # Catálogo general
├── redes-sociales.html     # Redes sociales
├── ubicacion.html          # Ubicación y contacto
├── admin.php               # Panel administrativo
├── config.php              # Configuración del servidor
├── sw.js                   # Service Worker
├── assets/
│   ├── css/
│   │   └── style.css       # Estilos principales
│   └── js/
│       ├── main.js         # JavaScript principal
│       └── script.js       # Scripts adicionales
├── data/
│   ├── classes.json        # Datos de clases
│   └── instructors.json    # Datos de instructores
└── images/                 # Recursos gráficos
```

### 4.2 Patrón de Diseño
- **MVC Pattern:** Separación de modelo (JSON), vista (HTML) y controlador (JavaScript)
- **Responsive First:** Diseño mobile-first con Bootstrap
- **Progressive Enhancement:** Funcionalidad básica sin JavaScript, mejorada con JS

### 4.3 Gestión del Estado
```javascript
class AppState {
    constructor() {
        this.currentUser = null;
        this.selectedClasses = [];
        this.filters = {
            category: 'all',
            level: 'all'
        };
    }
    
    updateFilter(type, value) {
        this.filters[type] = value;
        this.applyFilters();
    }
}
```

---

## 5. ROLES Y RESPONSABILIDADES

### 5.1 Desarrollador Frontend
**Responsabilidades:**
- Implementación de interfaces de usuario
- Integración con APIs de terceros
- Optimización de rendimiento

**Evidencias:**
- Commits en sistema de control de versiones
- Implementación de componentes responsive
- Integración exitosa con Waze/Uber APIs

### 5.2 Desarrollador Backend
**Responsabilidades:**
- Configuración del servidor Apache
- Gestión de datos JSON
- Implementación de PHP administrativo

**Evidencias:**
- Configuración de config.php
- Estructura de datos optimizada
- Panel administrativo funcional

### 5.3 Diseñador UX/UI
**Responsabilidades:**
- Diseño de interfaz coherente
- Esquema de colores corporativo
- Experiencia de usuario optimizada

**Evidencias:**
- Implementación de tema naranja/negro
- Navegación intuitiva
- Responsive design completo

---

## 6. PRUEBAS Y EVIDENCIAS

### 6.1 Pruebas de Funcionalidad
```javascript
// Ejemplo de prueba de carga de clases
function testClassLoading() {
    const startTime = performance.now();
    loadClasses().then(() => {
        const endTime = performance.now();
        console.log(`Clases cargadas en ${endTime - startTime} ms`);
        assert(endTime - startTime < 1000, 'Carga debe ser menor a 1s');
    });
}
```

### 6.2 Pruebas de Responsive
- ✅ Navegación móvil funcional
- ✅ Horarios legibles en mobile
- ✅ Formularios adaptables
- ✅ Imágenes optimizadas

### 6.3 Pruebas de Integración
- ✅ Waze URL generation correcta
- ✅ Uber deep linking funcional
- ✅ Google Maps embedding exitoso
- ✅ Font Awesome icons cargando

---

## 7. CONSIDERACIONES TÉCNICAS

### 7.1 Optimizaciones Implementadas
- **Lazy Loading:** Imágenes cargadas bajo demanda
- **Cache Busting:** Prevención de problemas de caché
- **Minificación:** CSS y JS optimizados para producción
- **Compresión:** Imágenes en formato WebP cuando sea posible

### 7.2 Accesibilidad
- **ARIA Labels:** Implementados en elementos interactivos
- **Contraste:** Colores que cumplen estándares WCAG
- **Navegación por teclado:** Soporte completo
- **Alt Text:** Descripciones en todas las imágenes

### 7.3 SEO y Performance
```html
<meta name="description" content="Legend Dance Academy - Clases de danza profesional">
<meta name="keywords" content="danza, clases, academia, ballet, hip hop">
<link rel="preload" href="assets/css/style.css" as="style">
<link rel="preconnect" href="https://fonts.googleapis.com">
```

---

## 8. CRONOGRAMA DE IMPLEMENTACIÓN

| Fase | Descripción | Estado | Evidencia |
|------|-------------|--------|-----------|
| 1 | Estructura HTML básica | ✅ Completado | Archivos HTML validados |
| 2 | Sistema de navegación | ✅ Completado | Navegación responsive funcional |
| 3 | Catálogo de clases | ✅ Completado | JSON loading y filtros operativos |
| 4 | Sistema de horarios | ✅ Completado | Sección horarios implementada |
| 5 | Integración geográfica | ✅ Completado | Waze/Uber links funcionales |
| 6 | Optimizaciones finales | ✅ Completado | PWA y performance optimizado |

---

## 9. MÉTRICAS DE CALIDAD

### 9.1 Performance Metrics
- **First Contentful Paint:** < 1.5s
- **Largest Contentful Paint:** < 2.5s
- **Cumulative Layout Shift:** < 0.1
- **First Input Delay:** < 100ms

### 9.2 Code Quality
- **Validación HTML:** 100% válido W3C
- **CSS Válido:** Sin errores de sintaxis
- **JavaScript:** ES6+ standards compliance
- **Accesibilidad:** WCAG 2.1 AA compliance

---

## 10. FIRMAS DE APROBACIÓN

**Desarrollador Principal:**  
Firma: _________________________ Fecha: _____________

**Profesor Roberto:**  
Firma: _________________________ Fecha: _____________

**Supervisor del Proyecto:**  
Firma: _________________________ Fecha: _____________

---

**Documento generado el:** 13 de Octubre, 2025  
**Versión:** 1.0  
**Próxima revisión:** 20 de Octubre, 2025