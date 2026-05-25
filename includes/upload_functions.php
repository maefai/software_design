<?php
// includes/upload_functions.php
if (defined('UPLOAD_FUNCTIONS_LOADED')) {
    return;
}
define('UPLOAD_FUNCTIONS_LOADED', true);

require_once 'config.php';

function uploadFile($file, $destination_folder, $allowed_types = null) {
    if ($allowed_types === null) {
        $allowed_types = array_merge(ALLOWED_IMAGES, ALLOWED_DOCUMENTS);
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'File too large (max ' . (MAX_FILE_SIZE / 1048576) . 'MB)'];
    }
    
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_types)) {
        return ['success' => false, 'error' => 'File type not allowed'];
    }
    
    $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
    $destination = UPLOAD_PATH . $destination_folder . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return [
            'success' => true,
            'filename' => $new_filename,
            'filepath' => 'uploads/' . $destination_folder . $new_filename,
            'size' => $file['size']
        ];
    }
    
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}
?>