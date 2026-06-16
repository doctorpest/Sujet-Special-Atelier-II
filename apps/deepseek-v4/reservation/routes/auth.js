const express = require('express');
const bcrypt = require('bcryptjs');
const db = require('../db');
const router = express.Router();

// Inscription
router.get('/register', (req, res) => {
  res.render('auth/register', { error: null });
});

router.post('/register', (req, res) => {
  const { username, email, password } = req.body;
  if (!username || !email || !password) {
    return res.render('auth/register', { error: 'Tous les champs sont obligatoires.' });
  }
  const existing = db.prepare('SELECT id FROM users WHERE email = ? OR username = ?').get(email, username);
  if (existing) {
    return res.render('auth/register', { error: 'Nom d’utilisateur ou email déjà utilisé.' });
  }
  const hash = bcrypt.hashSync(password, 10);
  db.prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)').run(username, email, hash);
  res.redirect('/login');
});

// Connexion
router.get('/login', (req, res) => {
  res.render('auth/login', { error: null });
});

router.post('/login', (req, res) => {
  const { email, password } = req.body;
  const user = db.prepare('SELECT * FROM users WHERE email = ?').get(email);
  if (!user || !bcrypt.compareSync(password, user.password)) {
    return res.render('auth/login', { error: 'Email ou mot de passe incorrect.' });
  }
  req.session.user = { id: user.id, username: user.username, email: user.email };
  res.redirect('/');
});

// Déconnexion
router.get('/logout', (req, res) => {
  req.session.destroy();
  res.redirect('/');
});

module.exports = router;