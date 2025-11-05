/**
 * User Alert System - JOptionPane style popup for payment notifications
 * To be included in index.php after user login
 */

class UserAlertSystem {
    constructor() {
        this.alertShown = false;
        this.init();
    }

    init() {
        // Check for alerts when page loads
        document.addEventListener('DOMContentLoaded', () => {
            this.checkForAlerts();
        });
    }

    async checkForAlerts() {
        try {
            const response = await fetch('api/get_user_alerts.php');
            const data = await response.json();
            
            if (data.alerts && data.alerts.length > 0) {
                // Show the highest priority alert
                const alert = data.alerts[0];
                this.showAlert(alert);
            }
        } catch (error) {
            console.error('Error checking user alerts:', error);
        }
    }

    showAlert(alert) {
        if (this.alertShown) return;
        this.alertShown = true;

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'user-alert-overlay';
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 10000;
            display: flex;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease-in-out;
        `;

        // Create alert dialog
        const dialog = document.createElement('div');
        dialog.className = 'user-alert-dialog';
        dialog.style.cssText = `
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: slideIn 0.3s ease-out;
            border: ${alert.type === 'payment_required' ? '3px solid #dc3545' : 
                      alert.type === 'enrollment_rejected' ? '3px solid #dc3545' : 
                      '3px solid #0d6efd'};
        `;

        // Create content
        const content = this.createAlertContent(alert);
        dialog.appendChild(content);
        overlay.appendChild(dialog);

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeIn {
                from { opacity: 0; }
                to { opacity: 1; }
            }
            @keyframes slideIn {
                from { 
                    opacity: 0; 
                    transform: translateY(-50px) scale(0.9); 
                }
                to { 
                    opacity: 1; 
                    transform: translateY(0) scale(1); 
                }
            }
            .user-alert-overlay {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
        `;
        document.head.appendChild(style);

        // Add to page
        document.body.appendChild(overlay);

        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.closeAlert(overlay);
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAlert(overlay);
            }
        }, { once: true });
    }

    createAlertContent(alert) {
        const container = document.createElement('div');
        container.style.cssText = 'padding: 30px; text-align: center;';

        // Title
        const title = document.createElement('h3');
        title.innerHTML = alert.title;
        title.style.cssText = `
            margin: 0 0 20px 0;
            color: ${alert.type === 'payment_required' ? '#dc3545' : 
                     alert.type === 'enrollment_rejected' ? '#dc3545' : 
                     '#0d6efd'};
            font-size: 24px;
            font-weight: bold;
        `;

        // Message
        const message = document.createElement('p');
        message.innerHTML = alert.message;
        message.style.cssText = `
            margin: 0 0 20px 0;
            font-size: 16px;
            line-height: 1.5;
            color: #333;
        `;

        // Details for payment required
        if (alert.type === 'payment_required' && alert.details) {
            const detailsContainer = document.createElement('div');
            detailsContainer.style.cssText = `
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            `;

            const detailsTitle = document.createElement('h5');
            detailsTitle.textContent = 'Detalles de pago:';
            detailsTitle.style.cssText = 'margin: 0 0 15px 0; color: #495057; font-size: 16px;';
            detailsContainer.appendChild(detailsTitle);

            alert.details.forEach(detail => {
                const item = document.createElement('div');
                item.style.cssText = `
                    margin-bottom: 12px;
                    padding: 12px;
                    background: white;
                    border-radius: 6px;
                    border-left: 4px solid #dc3545;
                `;
                item.innerHTML = `
                    <strong>${detail.class_name}</strong><br>
                    <small style="color: #6c757d;">
                        📅 ${detail.schedule} • 
                        💰 ₡${detail.price.toLocaleString()} • 
                        ⏰ ${detail.days_since} días pendiente
                    </small>
                `;
                detailsContainer.appendChild(item);
            });

            if (alert.total_amount) {
                const total = document.createElement('div');
                total.style.cssText = `
                    margin-top: 15px;
                    padding: 15px;
                    background: #dc3545;
                    color: white;
                    border-radius: 6px;
                    font-weight: bold;
                    font-size: 18px;
                `;
                total.innerHTML = `Total a pagar: ₡${alert.total_amount.toLocaleString()}`;
                detailsContainer.appendChild(total);
            }

            container.appendChild(title);
            container.appendChild(message);
            container.appendChild(detailsContainer);
        } 
        // Details for rejected enrollments
        else if (alert.type === 'enrollment_rejected' && alert.details) {
            const detailsContainer = document.createElement('div');
            detailsContainer.style.cssText = `
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            `;

            const detailsTitle = document.createElement('h5');
            detailsTitle.textContent = 'Detalles de la solicitud:';
            detailsTitle.style.cssText = 'margin: 0 0 15px 0; color: #495057; font-size: 16px;';
            detailsContainer.appendChild(detailsTitle);

            alert.details.forEach(detail => {
                const item = document.createElement('div');
                item.style.cssText = `
                    margin-bottom: 12px;
                    padding: 12px;
                    background: white;
                    border-radius: 6px;
                    border-left: 4px solid #dc3545;
                `;
                item.innerHTML = `
                    <strong>${detail.class_name}</strong><br>
                    <small style="color: #6c757d;">
                        📅 ${detail.schedule} • 
                        📝 ${detail.reason} • 
                        ⏰ Hace ${detail.days_since} días
                    </small>
                `;
                detailsContainer.appendChild(item);
            });

            const infoBox = document.createElement('div');
            infoBox.style.cssText = `
                margin-top: 15px;
                padding: 15px;
                background: #ffc107;
                color: #212529;
                border-radius: 6px;
                font-weight: bold;
                font-size: 14px;
            `;
            infoBox.innerHTML = `💡 No te preocupes, puedes intentar inscribirte en otras clases disponibles.`;
            detailsContainer.appendChild(infoBox);

            container.appendChild(title);
            container.appendChild(message);
            container.appendChild(detailsContainer);
        }
        // Details for pending enrollments
        else if (alert.type === 'pending_approval' && alert.details) {
            const detailsContainer = document.createElement('div');
            detailsContainer.style.cssText = `
                background: #f8f9fa;
                border-radius: 8px;
                padding: 20px;
                margin: 20px 0;
                text-align: left;
            `;

            const detailsTitle = document.createElement('h5');
            detailsTitle.textContent = 'Clases en revisión:';
            detailsTitle.style.cssText = 'margin: 0 0 15px 0; color: #495057; font-size: 16px;';
            detailsContainer.appendChild(detailsTitle);

            alert.details.forEach(detail => {
                const item = document.createElement('div');
                item.style.cssText = `
                    margin-bottom: 12px;
                    padding: 12px;
                    background: white;
                    border-radius: 6px;
                    border-left: 4px solid #0d6efd;
                `;
                item.innerHTML = `
                    <strong>${detail.class_name}</strong><br>
                    <small style="color: #6c757d;">
                        📅 ${detail.schedule} • 
                        ⏰ Solicitada hace ${detail.days_since} días
                    </small>
                `;
                detailsContainer.appendChild(item);
            });

            container.appendChild(title);
            container.appendChild(message);
            container.appendChild(detailsContainer);
        } else {
            container.appendChild(title);
            container.appendChild(message);
        }

        // Action text
        const actionText = document.createElement('p');
        actionText.innerHTML = alert.type === 'payment_required' 
            ? '💳 <strong>Contacta con la academia para realizar tu pago y activar tu cuenta.</strong>'
            : alert.type === 'enrollment_rejected'
            ? '📞 <strong>Puedes contactar con la academia para más información sobre otras clases disponibles.</strong>'
            : '⏳ <strong>Te contactaremos pronto para confirmar tu inscripción.</strong>';
        actionText.style.cssText = `
            margin: 20px 0;
            padding: 15px;
            background: ${alert.type === 'payment_required' ? '#fff3cd' : 
                         alert.type === 'enrollment_rejected' ? '#f8d7da' : 
                         '#d1ecf1'};
            border: 1px solid ${alert.type === 'payment_required' ? '#ffeaa7' : 
                               alert.type === 'enrollment_rejected' ? '#f5c6cb' : 
                               '#bee5eb'};
            border-radius: 6px;
            color: ${alert.type === 'payment_required' ? '#856404' : 
                     alert.type === 'enrollment_rejected' ? '#721c24' : 
                     '#0c5460'};
            font-size: 14px;
        `;

        // Buttons
        const buttonContainer = document.createElement('div');
        buttonContainer.style.cssText = 'margin-top: 30px; display: flex; gap: 10px; justify-content: center;';

        const closeButton = document.createElement('button');
        closeButton.textContent = 'Entendido';
        closeButton.style.cssText = `
            padding: 12px 30px;
            background: #6c757d;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.2s;
        `;
        closeButton.onmouseover = () => closeButton.style.background = '#5a6268';
        closeButton.onmouseout = () => closeButton.style.background = '#6c757d';
        closeButton.onclick = () => this.closeAlert(document.querySelector('.user-alert-overlay'));

        if (alert.type === 'payment_required') {
            const contactButton = document.createElement('button');
            contactButton.textContent = 'Contactar Academia';
            contactButton.style.cssText = `
                padding: 12px 30px;
                background: #28a745;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.2s;
            `;
            contactButton.onmouseover = () => contactButton.style.background = '#218838';
            contactButton.onmouseout = () => contactButton.style.background = '#28a745';
            contactButton.onclick = () => {
                window.location.href = 'contact.php';
            };
            buttonContainer.appendChild(contactButton);
        } else if (alert.type === 'enrollment_rejected') {
            const exploreButton = document.createElement('button');
            exploreButton.textContent = 'Ver Otras Clases';
            exploreButton.style.cssText = `
                padding: 12px 30px;
                background: #17a2b8;
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 16px;
                cursor: pointer;
                transition: all 0.2s;
            `;
            exploreButton.onmouseover = () => exploreButton.style.background = '#138496';
            exploreButton.onmouseout = () => exploreButton.style.background = '#17a2b8';
            exploreButton.onclick = () => {
                window.location.href = 'clases.php';
            };
            buttonContainer.appendChild(exploreButton);
        }

        buttonContainer.appendChild(closeButton);

        container.appendChild(actionText);
        container.appendChild(buttonContainer);

        return container;
    }

    closeAlert(overlay) {
        overlay.style.animation = 'fadeOut 0.2s ease-in-out';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
        }, 200);

        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
}

// Initialize the alert system
new UserAlertSystem();