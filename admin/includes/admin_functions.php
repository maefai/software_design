<?php
// admin/includes/admin_functions.php
// Helper functions for admin panel

function getStatusBadge($status) {
    $colors = [
        'pending' => 'badge-pending',
        'active' => 'badge-active',
        'approved' => 'badge-approved',
        'rejected' => 'badge-rejected',
        'open' => 'badge-open',
        'investigating' => 'badge-investigating',
        'resolved' => 'badge-resolved'
    ];
    
    $class = $colors[$status] ?? 'badge-secondary';
    return "<span class='badge-status $class'>$status</span>";
}

function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' minutes ago';
    if ($diff < 86400) return floor($diff/3600) . ' hours ago';
    if ($diff < 604800) return floor($diff/86400) . ' days ago';
    return date('M d, Y', $time);
}
?>