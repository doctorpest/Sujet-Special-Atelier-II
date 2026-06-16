const express = require('express');
const bcrypt = require('bcrypt');
const { get, run } = require('./db');
const { flash } = require('./middleware');
const router = express.Router();

router.get('/register', (req, res) => res.render('auth/register'));

router.post('/register', async (req, res, next) => {
  try {
    const { name, email, password } = req.body;
    if (!name || !email || !password || password.length < 6) {
      flash(req, 'danger', 'Tous les champs sont requis, avec un mot de passe de 6 caractères minimum.');
      return res.redirect('/register');
    }
    const existing = await get('SELECT id FROM users WHERE email = ?', [email.toLowerCase()]);
    if (existing) {
      flash(req, 'danger', 'Cet email est déjà utilisé.');
      return res.redirect('/register');
    }
    const passwordHash = await bcrypt.hash(password, 12);
    await run('INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)', [name.trim(), email.toLowerCase().trim(), passwordHash]);
    flash(req, 'success', 'Compte créé. Vous pouvez vous connecter.');
    res.redirect('/login');
  } catch (err) { next(err); }
});

router.get('/login', (req, res) => res.render('auth/login'));

router.post('/login', async (req, res, next) => {
  try {
    const { email, password } = req.body;
    const user = await get('SELECT * FROM users WHERE email = ?', [String(email || '').toLowerCase().trim()]);
    if (!user || !(await bcrypt.compare(password || '', user.password_hash))) {
      flash(req, 'danger', 'Email ou mot de passe incorrect.');
      return res.redirect('/login');
    }
    req.session.user = { id: user.id, name: user.name, email: user.email, profile_photo: user.profile_photo };
    flash(req, 'success', `Bienvenue ${user.name}.`);
    res.redirect('/rooms');
  } catch (err) { next(err); }
});

router.post('/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/login'));
});

module.exports = router;
