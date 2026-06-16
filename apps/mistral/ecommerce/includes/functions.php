<?php
require_once __DIR__ . '/database.php';

// Redirection
function redirect($url) {
    header("Location: $url");
    exit();
}

// Afficher un message
function setMessage($message, $type = MSG_INFO) {
    $_SESSION['message'] = ['text' => $message, 'type' => $type];
}

function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

// Vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Vérifier si l'utilisateur est admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
}

// Récupérer l'ID de l'utilisateur
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Formater un prix
function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

// Générer un token CSRF
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Vérifier un token CSRF
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Télécharger une image
function uploadImage($file, $targetDir = UPLOADS_PATH) {
    $targetFile = $targetDir . basename($file['name']);
    $imageFileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    // Vérifier si le fichier est une image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return ['success' => false, 'message' => 'Le fichier n\'est pas une image.'];
    }

    // Vérifier la taille (max 2Mo)
    if ($file['size'] > 2097152) {
        return ['success' => false, 'message' => 'L\'image est trop grande (max 2Mo).'];
    }

    // Autoriser certains formats
    $allowedTypes = ['jpg', 'png', 'jpeg', 'gif'];
    if (!in_array($imageFileType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Seuls les fichiers JPG, PNG, JPEG et GIF sont autorisés.'];
    }

    // Générer un nom unique
    $newFilename = uniqid() . '.' . $imageFileType;
    $targetFile = $targetDir . $newFilename;

    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return ['success' => true, 'filename' => $newFilename];
    } else {
        return ['success' => false, 'message' => 'Erreur lors du téléchargement de l\'image.'];
    }
}

// Supprimer une image
function deleteImage($filename) {
    $filePath = UPLOADS_PATH . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
        return true;
    }
    return false;
}
?>