// blood-requests.js
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabs = document.querySelectorAll('.blood-request-tab');
    const tabContents = document.querySelectorAll('.blood-request-tab-content');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const tabId = tab.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            tab.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
    
    // Form submission
    const requestForm = document.getElementById('blood-request-form');
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Basic validation
            const patientName = document.getElementById('patient-name').value;
            const contactName = document.getElementById('contact-name').value;
            const contactPhone = document.getElementById('contact-phone').value;
            const bloodGroup = document.getElementById('blood-group').value;
            const hospital = document.getElementById('hospital').value;
            
            if (!patientName || !contactName || !contactPhone || !bloodGroup || !hospital) {
                showNotification('Please fill in all required fields.', 'error');
                return;
            }
            
            // Submit the form
            this.submit();
        });
    }
    
    // Notification function
    function showNotification(message, type) {
        // Create notification element if it doesn't exist
        let notification = document.getElementById('blood-request-notification');
        if (!notification) {
            notification = document.createElement('div');
            notification.id = 'blood-request-notification';
            notification.className = 'blood-request-notification';
            document.body.appendChild(notification);
        }
        
        notification.textContent = message;
        notification.className = 'blood-request-notification ' + type;
        
        setTimeout(() => {
            notification.className = 'blood-request-notification';
        }, 3000);
    }
});