<?php
// ====================================================================
// Helper Functions
// Industrial Equipment Maintenance & Tracking System
// ====================================================================

require_once __DIR__ . '/../config/database.php';

/**
 * Generate a complete, absolute or base-relative application URL
 * 
 * @param string $path
 * @return string
 */
function url($path = '') {
    $path = ltrim($path, '/');
    $base = defined('BASE_URL') ? BASE_URL : '';
    return ($base ? $base : '') . ($path !== '' ? '/' . $path : '/');
}

/**
 * Redirect safely to an internal URL
 * 
 * @param string $path
 * @return void
 */
function redirect($path) {
    header("Location: " . url($path));
    exit;
}

/**
 * Sanitize user input for safe output / processing
 * 
 * @param mixed $data
 * @return string
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Set or retrieve and clear flash alert message
 * 
 * @param string $key Identifier for the message (e.g. 'success', 'error', 'warning', 'info')
 * @param string|null $message Message text to store. If null, displays and clears message.
 * @param string $type Bootstrap / Tailwind compatible alert type ('success', 'error', 'warning', 'info')
 * @return string|void
 */
function flash_message($key = 'flash', $message = null, $type = 'success') {
    if ($message !== null) {
        $_SESSION['flash'][$key] = [
            'message' => $message,
            'type'    => $type
        ];
        return;
    }

    if (!empty($_SESSION['flash'][$key])) {
        $alert = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);

        $typeClass = $alert['type'] === 'error' ? 'alert-danger' : 'alert-' . $alert['type'];
        $icon = 'fa-circle-check';
        if ($alert['type'] === 'error') $icon = 'fa-circle-exclamation';
        if ($alert['type'] === 'warning') $icon = 'fa-triangle-exclamation';
        if ($alert['type'] === 'info') $icon = 'fa-circle-info';

        return "
        <div class='alert {$typeClass} alert-dismissible fade show' role='alert'>
            <div class='alert-content'>
                <i class='fa-solid {$icon} alert-icon'></i>
                <span>" . htmlspecialchars($alert['message']) . "</span>
            </div>
            <button type='button' class='btn-close' onclick='this.parentElement.remove();' aria-label='Close'>&times;</button>
        </div>";
    }
    return '';
}

/**
 * Format a standard date string (Y-m-d) into human-readable date
 * 
 * @param string|null $date
 * @param string $default
 * @return string
 */
function format_date($date, $default = 'N/A') {
    if (empty($date) || $date === '0000-00-00') {
        return "<span class='text-muted'>{$default}</span>";
    }
    return date('M d, Y', strtotime($date));
}

/**
 * Format a datetime string (Y-m-d H:i:s) into human-readable date and time
 * 
 * @param string|null $datetime
 * @param string $default
 * @return string
 */
function format_datetime($datetime, $default = 'N/A') {
    if (empty($datetime) || $datetime === '0000-00-00 00:00:00') {
        return "<span class='text-muted'>{$default}</span>";
    }
    return date('M d, Y h:i A', strtotime($datetime));
}

/**
 * Format numeric currency amount
 * 
 * @param float|int|string $amount
 * @return string
 */
function format_currency($amount) {
    return '$' . number_format((float)$amount, 2);
}

/**
 * Generate stylized HTML badge for machine status
 * 
 * @param string $status
 * @return string
 */
function get_status_badge($status) {
    switch (trim($status)) {
        case 'Working':
            return "<span class='badge badge-success'><i class='fa-solid fa-circle-check'></i> Working</span>";
        case 'Under Maintenance':
            return "<span class='badge badge-warning'><i class='fa-solid fa-wrench'></i> Under Maintenance</span>";
        case 'Breakdown':
            return "<span class='badge badge-danger'><i class='fa-solid fa-triangle-exclamation'></i> Breakdown</span>";
        case 'Inactive':
            return "<span class='badge badge-secondary'><i class='fa-solid fa-ban'></i> Inactive</span>";
        // Maintenance Statuses
        case 'Scheduled':
            return "<span class='badge badge-info'><i class='fa-solid fa-calendar-day'></i> Scheduled</span>";
        case 'In Progress':
            return "<span class='badge badge-primary'><i class='fa-solid fa-gear fa-spin'></i> In Progress</span>";
        case 'Completed':
            return "<span class='badge badge-success'><i class='fa-solid fa-check-double'></i> Completed</span>";
        case 'Cancelled':
            return "<span class='badge badge-secondary'><i class='fa-solid fa-xmark'></i> Cancelled</span>";
        // Breakdown Statuses
        case 'Reported':
            return "<span class='badge badge-danger'><i class='fa-solid fa-bullhorn'></i> Reported</span>";
        case 'Resolved':
            return "<span class='badge badge-success'><i class='fa-solid fa-check'></i> Resolved</span>";
        // Technician Statuses
        case 'Active':
            return "<span class='badge badge-success'><i class='fa-solid fa-user-check'></i> Active</span>";
        default:
            return "<span class='badge badge-secondary'>" . htmlspecialchars($status) . "</span>";
    }
}

/**
 * Generate stylized HTML badge for breakdown severity
 * 
 * @param string $severity
 * @return string
 */
function get_severity_badge($severity) {
    switch (trim($severity)) {
        case 'Critical':
            return "<span class='badge badge-danger badge-pulse'><i class='fa-solid fa-skull-crossbones'></i> Critical</span>";
        case 'High':
            return "<span class='badge badge-orange'><i class='fa-solid fa-arrow-trend-up'></i> High</span>";
        case 'Medium':
            return "<span class='badge badge-warning'><i class='fa-solid fa-circle-exclamation'></i> Medium</span>";
        case 'Low':
            return "<span class='badge badge-info'><i class='fa-solid fa-circle-info'></i> Low</span>";
        default:
            return "<span class='badge badge-secondary'>" . htmlspecialchars($severity) . "</span>";
    }
}

/**
 * Generate stylized HTML badge for maintenance type
 * 
 * @param string $type
 * @return string
 */
function get_maintenance_type_badge($type) {
    switch (trim($type)) {
        case 'Preventive':
            return "<span class='badge badge-primary'><i class='fa-solid fa-shield-halved'></i> Preventive</span>";
        case 'Corrective':
            return "<span class='badge badge-warning'><i class='fa-solid fa-screwdriver-wrench'></i> Corrective</span>";
        case 'Emergency':
            return "<span class='badge badge-danger'><i class='fa-solid fa-fire-extinguisher'></i> Emergency</span>";
        default:
            return "<span class='badge badge-secondary'>" . htmlspecialchars($type) . "</span>";
    }
}

/**
 * Retrieve system alert counts for sidebar badges
 * 
 * @param PDO $pdo
 * @return array
 */
function get_sidebar_counters($pdo) {
    try {
        // Active breakdowns count (Reported or In Progress)
        $stmt1 = $pdo->query("SELECT COUNT(*) FROM breakdowns WHERE status != 'Resolved'");
        $activeBreakdowns = (int)$stmt1->fetchColumn();

        // Overdue maintenance count (Scheduled and date < TODAY)
        $stmt2 = $pdo->query("SELECT COUNT(*) FROM maintenance_records WHERE status = 'Scheduled' AND scheduled_date < CURDATE()");
        $overdueMaintenance = (int)$stmt2->fetchColumn();

        return [
            'breakdowns' => $activeBreakdowns,
            'overdue'    => $overdueMaintenance
        ];
    } catch (Exception $e) {
        return ['breakdowns' => 0, 'overdue' => 0];
    }
}
