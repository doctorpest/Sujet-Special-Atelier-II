<?php
// includes/functions.php

require_once __DIR__ . '/db.php';

// ── Sécurité ───────────────────────────────────────────────
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_check(): void {
    $tok = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $tok)) {
        http_response_code(403);
        die('Requête invalide (CSRF).');
    }
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

// ── Flash messages ─────────────────────────────────────────
function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function render_flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $html .= '<div class="alert alert--' . e($f['type']) . '">' . e($f['msg']) . '</div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

// ── Auth client ────────────────────────────────────────────
function client_logged(): bool { return !empty($_SESSION['client_id']); }
function current_client(): ?array {
    if (!client_logged()) return null;
    return db()->prepare('SELECT * FROM clients WHERE id=?')
               ->execute([$_SESSION['client_id']])
              ? db()->query('SELECT * FROM clients WHERE id=' . (int)$_SESSION['client_id'])->fetch()
              : null;
}
function require_client(): void {
    if (!client_logged()) { flash('Connectez-vous pour continuer.','warning'); redirect('login.php'); }
}

// ── Auth admin ─────────────────────────────────────────────
function admin_logged(): bool { return !empty($_SESSION['admin_id']); }
function require_admin(): void {
    if (!admin_logged()) { header('Location: ' . SITE_URL . '/admin/login.php'); exit; }
}

// ── Redirection ────────────────────────────────────────────
function redirect(string $path): never {
    header('Location: ' . SITE_URL . '/' . ltrim($path, '/'));
    exit;
}

// ── Slugify ────────────────────────────────────────────────
function slugify(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

// ── Panier ─────────────────────────────────────────────────
function panier(): array  { return $_SESSION['panier'] ?? []; }

function panier_ajouter(int $id, int $qte = 1): void {
    $p = db()->prepare('SELECT id, nom, prix, stock FROM produits WHERE id=? AND actif=1');
    $p->execute([$id]);
    $prod = $p->fetch();
    if (!$prod) return;
    $panier = panier();
    if (isset($panier[$id])) {
        $panier[$id]['qte'] = min($panier[$id]['qte'] + $qte, $prod['stock']);
    } else {
        $panier[$id] = ['id'=>$id,'nom'=>$prod['nom'],'prix'=>$prod['prix'],'qte'=>$qte,'stock'=>$prod['stock']];
    }
    $_SESSION['panier'] = $panier;
}

function panier_modifier(int $id, int $qte): void {
    if ($qte <= 0) { panier_supprimer($id); return; }
    if (isset($_SESSION['panier'][$id])) {
        $_SESSION['panier'][$id]['qte'] = min($qte, $_SESSION['panier'][$id]['stock']);
    }
}

function panier_supprimer(int $id): void { unset($_SESSION['panier'][$id]); }

function panier_vider(): void { $_SESSION['panier'] = []; }

function panier_total(): float {
    $total = 0.0;
    foreach (panier() as $item) $total += $item['prix'] * $item['qte'];
    return $total;
}

function panier_count(): int {
    $n = 0;
    foreach (panier() as $item) $n += $item['qte'];
    return $n;
}

// ── Produits ───────────────────────────────────────────────
function get_categories(): array {
    return db()->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
}

function get_produit(int $id): array|false {
    $s = db()->prepare('SELECT p.*, c.nom AS cat_nom FROM produits p JOIN categories c ON c.id=p.categorie_id WHERE p.id=?');
    $s->execute([$id]);
    return $s->fetch();
}

function format_prix(float $p): string {
    return number_format($p, 2, ',', ' ') . ' €';
}

function produit_image_url(?string $image): string {
    if ($image && file_exists(UPLOAD_DIR . $image)) return UPLOAD_URL . $image;
    return SITE_URL . '/assets/images/placeholder.svg';
}

// ── Upload image ───────────────────────────────────────────
function upload_image(array $file, ?string $ancien = null): string|false {
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed, true)) return false;
    if ($file['size'] > 3 * 1024 * 1024) return false;
    $ext  = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = bin2hex(random_bytes(8)) . '.' . strtolower($ext);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $name)) return false;
    // supprimer l'ancienne image
    if ($ancien && file_exists(UPLOAD_DIR . $ancien)) @unlink(UPLOAD_DIR . $ancien);
    return $name;
}
