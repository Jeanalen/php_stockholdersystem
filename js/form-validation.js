// Form Validation
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validatePhone(phone) {
    const re = /^[\d\s\-\+\(\)]+$/;
    return re.test(phone) && phone.length >= 7;
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('stakeholderForm');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const shares = parseFloat(document.getElementById('shares').value);

            // Validate name
            if (!name || name.length < 2) {
                e.preventDefault();
                alert('Name must be at least 2 characters long');
                return;
            }

            // Validate email if provided
            if (email && !validateEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }

            // Validate phone if provided
            if (phone && !validatePhone(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number');
                return;
            }

            // Validate shares
            if (isNaN(shares) || shares <= 0) {
                e.preventDefault();
                alert('Shares must be a positive number');
                return;
            }
        });
    }
});

// Search and Filter Functions
function filterTable() {
    const input = document.getElementById('searchInput');
    if (!input) return;
    
    const filter = input.value.toLowerCase();
    const table = document.getElementById('stakeholdersTable');
    
    if (!table) return;
    
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const text = rows[i].textContent.toLowerCase();
        rows[i].style.display = text.includes(filter) ? '' : 'none';
    }
}

// Delete Confirmation
function confirmDelete(id, itemType = 'stakeholder') {
    if (confirm(`Are you sure you want to delete this ${itemType}? This action cannot be undone.`)) {
        window.location.href = window.location.href.split('?')[0] + '?delete=' + id;
    }
}

// Modal Functions
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
});

// Tooltip
function showTooltip(element, message) {
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = message;
    tooltip.style.cssText = `
        position: absolute;
        background-color: #333;
        color: #fff;
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 12px;
        z-index: 1000;
        white-space: nowrap;
    `;
    
    document.body.appendChild(tooltip);
    
    const rect = element.getBoundingClientRect();
    tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
    
    setTimeout(() => tooltip.remove(), 3000);
}

// Format currency
function formatCurrency(value) {
    return '$' + parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Format percentage
function formatPercentage(value, decimals = 2) {
    return (parseFloat(value) * 100).toFixed(decimals) + '%';
}

// Print page
function printPage() {
    window.print();
}

// Export table to CSV
function exportTableToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;

    let csv = [];
    
    // Get headers
    const headers = [];
    table.querySelectorAll('thead th').forEach(th => {
        headers.push(th.textContent);
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach(td => {
            row.push('"' + td.textContent.trim().replace(/"/g, '""') + '"');
        });
        csv.push(row.join(','));
    });
    
    // Create download link
    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', filename);
    link.click();
}

// Debounce function for search
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

// Enhanced search with debounce
const debouncedSearch = debounce(filterTable, 300);

// Add event listener if search input exists
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', debouncedSearch);
    }
});

// Notification system
class Notification {
    static show(message, type = 'info', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            max-width: 400px;
            z-index: 2000;
            animation: slideIn 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
    
    static success(message) {
        this.show(message, 'success');
    }
    
    static error(message) {
        this.show(message, 'danger');
    }
    
    static warning(message) {
        this.show(message, 'warning');
    }
    
    static info(message) {
        this.show(message, 'info');
    }
}

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
});

// Add loading state to buttons
document.addEventListener('DOMContentLoaded', function() {
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (this.form) {
                // Let form submission handle the state
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Alt + H: Go to Home
    if (e.altKey && e.key === 'h') {
        window.location.href = 'index.php';
    }
    // Alt + A: Go to Add Stakeholder
    if (e.altKey && e.key === 'a') {
        window.location.href = 'add-stakeholder.php';
    }
    // Alt + E: Go to Edit Register
    if (e.altKey && e.key === 'e') {
        window.location.href = 'edit-register.php';
    }
    // Alt + R: Go to Report
    if (e.altKey && e.key === 'r') {
        window.location.href = 'report.php';
    }
    // Alt + T: Go to Attendance
    if (e.altKey && e.key === 't') {
        window.location.href = 'attendance.php';
    }
});

// Utility: Get URL parameters
function getUrlParameter(name) {
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
}

// Utility: Check if element is in viewport
function isInViewport(element) {
    const rect = element.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}
