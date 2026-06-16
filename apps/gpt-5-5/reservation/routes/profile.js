const express = require('express');
const multer = require('multer');
const path = require('path');
const { run, get } = require('./db');
const { requireAuth, flash } = require('./middleware');
const router = express.Router();

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, path.join(__dirname, '..', 'public', 'uploads')),
  filename: (req, file, cb) => {
    const safeExt = path.extname(file.originalname).toLowerCase();
    cb(null, `profile-${req.session.user.id}-${Date.now()}${safeExt}`);
  }
});

const upload = multer({
  storage,
  limits: { fileSize: 2 * 1024 * 1024 },
  fileFilter: (req, file, cb) => {
    if (!['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'].includes(file.mimetype)) {
      return cb(new Error('Format image non accepté. Utilisez JPG, PNG, WEBP ou SVG.'));
    }
    cb(null, true);
  }
});

router.get('/', requireAuth, async (req, res, next) => {
  try {
    const user = await get('SELECT id, name, email, profile_photo FROM users WHERE id = ?', [req.session.user.id]);
    res.render('profile/show', { user });
  } catch (err) { next(err); }
});

router.post('/photo', requireAuth, upload.single('profile_photo'), async (req, res, next) => {
  try {
    if (!req.file) {
      flash(req, 'danger', 'Veuillez choisir une image.');
      return res.redirect('/profile');
    }
    const photoPath = `/uploads/${req.file.filename}`;
    await run('UPDATE users SET profile_photo = ? WHERE id = ?', [photoPath, req.session.user.id]);
    req.session.user.profile_photo = photoPath;
    flash(req, 'success', 'Photo de profil mise à jour.');
    res.redirect('/profile');
  } catch (err) { next(err); }
});

module.exports = router;
