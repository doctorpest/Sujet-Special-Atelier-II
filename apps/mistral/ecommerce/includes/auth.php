<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';

// Inscription
function registerUser($username, $email, $password, $firstName, $lastName) {
    global $db;

    // Vérifier si l'utilisateur ou l'email existe déjà
    $checkUser = $db->query("SELECT user_id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    if ($checkUser->num_rows > 0) {
        return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé.'];
    }

    // Hacher le mot de passe
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // Insérer l'utilisateur
    $result = $db->execute(
        "INSERT INTO users (username, email, password_hash, first_name, last_name) VALUES (?, ?, ?, ?, ?)",
        [$username, $email, $passwordHash, $firstName, $lastName]
    );

    if ($result) {
        return ['success' => true, 'message' => 'Inscription réussie. Vous pouvez maintenant vous connecter.'];
    } else {
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
    }
}

// Connexion
function loginUser($username, $password) {
    global $db;

    $user = $db->query("SELECT * FROM users WHERE username = ?", [$username]);

    if ($user->num_rows === 0) {
        return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'];
    }

    $user = $user->fetch_assoc();

    if (password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['is_admin'] = $user['is_admin'];

        return ['success' => true, 'message' => 'Connexion réussie.'];
    } else {
        return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect.'];
    }
}

// Déconnexion
function logoutUser() {
    session_unset();
    session_destroy();
}

// Récupérer les informations de l'utilisateur
function getUserById($userId) {
    global $db;

    $result = $db->query("SELECT * FROM users WHERE user_id = ?", [$userId]);
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;
}

// Mettre à jour les informations de l'utilisateur
function updateUser($userId, $data) {
    global $db;

    $fields = [];
    $params = [];

    foreach ($data as $key => $value) {
        $fields[] = "$key = ?";
        $params[] = $value;
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE user_id = ?";

    return $db->execute($sql, $params);
}
?>