const express = require('express');
const multer = require('multer');
const path = require('path');
const db = require('../db');
const router = express.Router();

// Configuration multer pour photo de profil
const storage = multer.diskStorage({
  destination: path.join(__dirname, '..', 'public', 'uploads', 'profiles'),
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + path.extname(file.originalname));
  }
});
const upload = multer({ storage });

// Middleware d'authentification
router.use((req, res, next) => {
  if (!req.session.user) return res.redirect('/login');
  next();
});

// Affichage du profil
router.get('/', (req, res) => {
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.session.user.id);
  res.render('profile', { user });
});

// Upload photo
router.post('/photo', upload.single('photo'), (req, res) => {
  if (!req.file) return res.redirect('/profile');
  const filename = '/uploads/profiles/' + req.file.filename;
  db.prepare('UPDATE users SET profile_photo = ? WHERE id = ?').run(filename, req.session.user.id);
  res.redirect('/profile');
});

module.exports = router;