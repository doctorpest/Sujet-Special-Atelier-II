const express = require('express');
const router = express.Router();
const multer = require('multer');
const bcrypt = require('bcryptjs');
const path = require('path');
const fs = require('fs');
const { db } = require('./db');
const { requireAuth } = require('./middleware');

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, path.join(__dirname, '..', 'public', 'uploads')),
  filename: (req, file, cb) => {
    const ext = path.extname(file.originalname).toLowerCase();
    cb(null, `avatar-${req.session.user.id}-${Date.now()}${ext}`);
  },
});

const upload = multer({
  storage,
  limits: { fileSize: 5 * 1024 * 1024 }, // 5MB
  fileFilter: (req, file, cb) => {
    const allowed = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    const ext = path.extname(file.originalname).toLowerCase();
    if (allowed.includes(ext)) cb(null, true);
    else cb(new Error('Format non supporté. Utilisez JPG, PNG, GIF ou WebP.'));
  },
});

// Profile page
router.get('/', requireAuth, (req, res) => {
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.session.user.id);
  const stats = db.prepare(`
    SELECT
      COUNT(*) as total,
      SUM(CASE WHEN status != 'cancelled' THEN 1 ELSE 0 END) as confirmed,
      SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM reservations WHERE user_id = ?
  `).get(req.session.user.id);
  res.render('profile/index', { user, stats });
});

// Upload avatar
router.post('/avatar', requireAuth, (req, res) => {
  upload.single('avatar')(req, res, (err) => {
    if (err) {
      req.session.error = err.message || 'Erreur lors du téléversement.';
      return res.redirect('/profile');
    }
    if (!req.file) {
      req.session.error = 'Aucun fichier sélectionné.';
      return res.redirect('/profile');
    }

    // Delete old avatar if exists
    const oldUser = db.prepare('SELECT avatar FROM users WHERE id = ?').get(req.session.user.id);
    if (oldUser.avatar) {
      const oldPath = path.join(__dirname, '..', 'public', oldUser.avatar);
      if (fs.existsSync(oldPath)) fs.unlinkSync(oldPath);
    }

    const avatarPath = `/uploads/${req.file.filename}`;
    db.prepare('UPDATE users SET avatar = ? WHERE id = ?').run(avatarPath, req.session.user.id);
    req.session.user.avatar = avatarPath;
    req.session.success = 'Photo de profil mise à jour.';
    res.redirect('/profile');
  });
});

// Update profile info
router.post('/update', requireAuth, (req, res) => {
  const { name, email } = req.body;
  if (!name || !email) {
    req.session.error = 'Nom et email sont obligatoires.';
    return res.redirect('/profile');
  }

  const existing = db.prepare('SELECT id FROM users WHERE email = ? AND id != ?').get(email, req.session.user.id);
  if (existing) {
    req.session.error = 'Cette adresse email est déjà utilisée.';
    return res.redirect('/profile');
  }

  db.prepare('UPDATE users SET name = ?, email = ? WHERE id = ?').run(name, email, req.session.user.id);
  req.session.user.name = name;
  req.session.user.email = email;
  req.session.success = 'Profil mis à jour.';
  res.redirect('/profile');
});

// Change password
router.post('/password', requireAuth, async (req, res) => {
  const { current, newpass, newpass2 } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.session.user.id);

  if (!(await bcrypt.compare(current, user.password))) {
    req.session.error = 'Mot de passe actuel incorrect.';
    return res.redirect('/profile');
  }
  if (newpass !== newpass2) {
    req.session.error = 'Les nouveaux mots de passe ne correspondent pas.';
    return res.redirect('/profile');
  }
  if (newpass.length < 6) {
    req.session.error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    return res.redirect('/profile');
  }

  const hash = await bcrypt.hash(newpass, 12);
  db.prepare('UPDATE users SET password = ? WHERE id = ?').run(hash, req.session.user.id);
  req.session.success = 'Mot de passe modifié avec succès.';
  res.redirect('/profile');
});

module.exports = router;
