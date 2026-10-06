/**
 * Industrial Equipment Maintenance & Tracking System (EquipTrack Pro)
 * Core Client-side Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebar = document.getElementById('appSidebar');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    let backdrop = document.querySelector('.sidebar-backdrop');

    if (!backdrop && sidebar) {
        backdrop = document.createElement('div');
        backdrop.className = 'sidebar-backdrop';
        document.body.appendChild(backdrop);
    }

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
            backdrop.classList.toggle('show');
        });

        backdrop.addEventListener('click', function () {
            sidebar.classList.remove('show');
            backdrop.classList.remove('show');
        });
    }

    // 2. Auto-dismiss flash alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(function () {
                if (alert.parentElement) {
                    alert.remove();
                }
            }, 400);
        }, 5000);
    });

    // 3. Delete Confirmation Modal
    const deleteModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmBtn = document.getElementById('deleteConfirmBtn');
    const deleteItemName = document.getElementById('deleteItemName');
    const deleteCancelBtns = document.querySelectorAll('[data-dismiss-modal]');

    document.querySelectorAll('[data-confirm-delete]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.getAttribute('href') || this.getAttribute('data-delete-url');
            const itemName = this.getAttribute('data-item-name') || 'this record';

            if (deleteModal && deleteConfirmBtn) {
                if (deleteItemName) {
                    deleteItemName.textContent = itemName;
                }
                deleteConfirmBtn.setAttribute('href', url);
                deleteModal.classList.add('active');
            } else {
                // Native fallback
                if (confirm('Are you sure you want to permanently delete: ' + itemName + '? This action cannot be undone.')) {
                    window.location.href = url;
                }
            }
        });
    });

    if (deleteCancelBtns && deleteModal) {
        deleteCancelBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                deleteModal.classList.remove('active');
            });
        });
    }

    // 4. Live Table Search Filter (Client-side fast search helper)
    const tableSearchInputs = document.querySelectorAll('[data-table-search]');
    tableSearchInputs.forEach(function (input) {
        const targetTableSelector = input.getAttribute('data-table-search');
        const targetTable = document.querySelector(targetTableSelector);

        if (targetTable) {
            input.addEventListener('keyup', function () {
                const query = this.value.toLowerCase().trim();
                const rows = targetTable.querySelectorAll('tbody tr');

                rows.forEach(function (row) {
                    const text = row.textContent.toLowerCase();
                    if (text.indexOf(query) !== -1) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
});
