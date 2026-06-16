<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Rediriger si non connecté
if (!isLoggedIn()) {
    redirect('login.php');
}

// Récupérer les informations de l'utilisateur
$user = getUserById(getUserId());

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        setMessage('Token CSRF invalide.', MSG_ERROR);
    } else {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postalCode = trim($_POST['postal_code'] ?? '');
        $country = trim($_POST['country'] ?? '');

        // Validation
        if (empty($firstName) || empty($lastName) || empty($email)) {
            setMessage('Les champs Prénom, Nom et Email sont obligatoires.', MSG_ERROR);
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setMessage('Adresse email invalide.', MSG_ERROR);
        } else {
            // Mettre à jour les informations
            $data = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'city' => $city,
                'postal_code' => $postalCode,
                'country' => $country
            ];

            $result = updateUser(getUserId(), $data);

            if ($result) {
                // Mettre à jour la session
                $_SESSION['first_name'] = $firstName;
                $_SESSION['last_name'] = $lastName;
                $_SESSION['email'] = $email;

                setMessage('Vos informations ont été mises à jour avec succès.', MSG_SUCCESS);
                redirect('account.php');
            } else {
                setMessage('Erreur lors de la mise à jour de vos informations.', MSG_ERROR);
            }
        }
    }
}

$pageTitle = "Mon Compte - Boutique en Ligne";
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width: 800px;">
    <h1>Mon Compte</h1>

    <div style="display: grid; grid-template-columns: 200px 1fr; gap: 2rem;">
        <div>
            <div style="background-color: white; padding: 1rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                <h3>Menu</h3>
                <ul style="list-style: none;">
                    <li><a href="account.php" style="display: block; padding: 0.5rem; <?php echo basename($_SERVER['PHP_SELF']) === 'account.php' ? 'background-color: #f0f0f0;' : ''; ?>">Mes informations</a></li>
                    <li><a href="order_history.php" style="display: block; padding: 0.5rem; <?php echo basename($_SERVER['PHP_SELF']) === 'order_history.php' ? 'background-color: #f0f0f0;' : ''; ?>">Mes commandes</a></li>
                </ul>
            </div>
        </div>

        <div>
            <div style="background-color: white; padding: 1.5rem; border-radius: 4px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
                <h2>Mes informations</h2>

                <form method="post" action="account.php">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="first_name">Prénom</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Nom</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Téléphone</label>
                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="city">Ville</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="postal_code">Code postal</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="country">Pays</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn">Mettre à jour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>