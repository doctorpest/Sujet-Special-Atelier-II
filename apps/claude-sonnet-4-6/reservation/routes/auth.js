const express = require('express');
const bcrypt = require('bcryptjs');
const router = express.Router();
const { db } = require('./db');

// Home → redirect
router.get('/', (req, res) => {
  if (req.session.user) return res.redirect('/rooms');
  res.redirect('/login');
});

// Register
router.get('/register', (req, res) => {
  if (req.session.user) return res.redirect('/rooms');
  res.render('auth/register');
});

router.post('/register', async (req, res) => {
  const { name, email, password, password2 } = req.body;
  if (!name || !email || !password) {
    req.session.error = 'Tous les champs sont obligatoires.';
    return res.redirect('/register');
  }
  if (password !== password2) {
    req.session.error = 'Les mots de passe ne correspondent pas.';
    return res.redirect('/register');
  }
  if (password.length < 6) {
    req.session.error = 'Le mot de passe doit contenir au moins 6 caractères.';
    return res.redirect('/register');
  }
  const existing = db.prepare('SELECT id FROM users WHERE email = ?').get(email);
  if (existing) {
    req.session.error = 'Cette adresse email est déjà utilisée.';
    return res.redirect('/register');
  }
  const hash = await bcrypt.hash(password, 12);
  const result = db.prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)').run(name, email, hash);
  const user = db.prepare('SELECT id, name, email, avatar FROM users WHERE id = ?').get(result.lastInsertRowid);
  req.session.user = user;
  req.session.success = `Bienvenue, ${user.name} ! Votre compte a été créé.`;
  res.redirect('/rooms');
});

// Login
router.get('/login', (req, res) => {
  if (req.session.user) return res.redirect('/rooms');
  res.render('auth/login');
});

router.post('/login', async (req, res) => {
  const { email, password } = req.body;
  if (!email || !password) {
    req.session.error = 'Email et mot de passe requis.';
    return res.redirect('/login');
  }
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
  if (!user || !(await bcrypt.compare(password, user.password))) {
    req.session.error = 'Identifiants incorrects.';
    return res.redirect('/login');
  }
  req.session.user = { id: user.id, name: user.name, email: user.email, avatar: user.avatar };
  req.session.success = `Bon retour, ${user.name} !`;
  res.redirect('/rooms');
});

// Logout
router.post('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});

module.exports = router;
