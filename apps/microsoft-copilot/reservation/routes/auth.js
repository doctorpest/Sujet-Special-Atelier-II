const express = require('express');
const bcrypt = require('bcrypt');
const db = require('../db');

const router = express.Router();

// Inscription
router.get('/register', (req, res) => {
  res.render('register');
});

router.post('/register', async (req, res) => {
  const { name, email, password, password_confirm } = req.body;
  if (!name || !email || !password) {
    req.session.error = 'Tous les champs sont obligatoires.';
    return res.redirect('/register');
  }
  if (password !== password_confirm) {
    req.session.error = 'Les mots de passe ne correspondent pas.';
    return res.redirect('/register');
  }

  try {
    const hash = await bcrypt.hash(password, 10);
    db.run(
      'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
      [name, email, hash],
      function (err) {
        if (err) {
          req.session.error = 'Email déjà utilisé ou erreur.';
          return res.redirect('/register');
        }
        req.session.success = 'Compte créé, vous pouvez vous connecter.';
        res.redirect('/login');
      }
    );
  } catch (e) {
    req.session.error = 'Erreur serveur.';
    res.redirect('/register');
  }
});

// Connexion
router.get('/login', (req, res) => {
  res.render('login');
});

router.post('/login', (req, res) => {
  const { email, password } = req.body;
  db.get('SELECT * FROM users WHERE email = ?', [email], async (err, user) => {
    if (err || !user) {
      req.session.error = 'Identifiants invalides.';
      return res.redirect('/login');
    }
    const match = await bcrypt.compare(password, user.password_hash);
    if (!match) {
      req.session.error = 'Identifiants invalides.';
      return res.redirect('/login');
    }
    req.session.user = {
      id: user.id,
      name: user.name,
      email: user.email,
      profile_photo: user.profile_photo
    };
    res.redirect('/');
  });
});

// Déconnexion
router.post('/logout', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/login');
  });
});

module.exports = router;
