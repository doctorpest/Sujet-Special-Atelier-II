<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
csrf_check();

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['produit_id'] ?? 0);

switch ($action) {
    case 'ajouter':
        $qte = max(1, (int)($_POST['qte'] ?? 1));
        panier_ajouter($id, $qte);
        flash('Produit ajouté au panier.', 'success');
        $ref = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/pages/panier.php';
        header('Location: ' . $ref);
        exit;

    case 'modifier':
        $qte = (int)($_POST['qte'] ?? 0);
        panier_modifier($id, $qte);
        break;

    case 'supprimer':
        panier_supprimer($id);
        flash('Article retiré.', 'success');
        break;

    case 'vider':
        panier_vider();
        flash('Panier vidé.', 'success');
        break;
}

redirect('pages/panier.php');
