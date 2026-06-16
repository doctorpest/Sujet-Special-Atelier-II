const express = require('express');
const path = require('path');
const session = require('express-session');
const multer = require('multer');
const nodemailer = require('nodemailer');
const db = require('./db');

const authRoutes = require('./routes/auth');
const roomRoutes = require('./routes/rooms');
const reservationRoutes = require('./routes/reservations');

const app = express();

// Config EJS
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// Middlewares
app.use(express.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));
app.use('/uploads', express.static(path.join(__dirname, 'uploads')));

app.use(
  session({
    secret: 'change-this-secret',
    resave: false,
    saveUninitialized: false
  })
);

// Multer pour upload photo de profil
const storage = multer.diskStorage({
  destination: (req, file, cb) => {
    cb(null, path.join(__dirname, 'uploads'));
  },
  filename: (req, file, cb) => {
    const unique = Date.now() + '-' + Math.round(Math.random() * 1e9);
    const ext = path.extname(file.originalname);
    cb(null, unique + ext);
  }
});
const upload = multer({ storage });

// Transport email (adapter avec vos variables d’environnement)
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'localhost',
  port: process.env.SMTP_PORT ? Number(process.env.SMTP_PORT) : 1025,
  secure: false,
  tls: { rejectUnauthorized: false }
});

// Middleware pour rendre l’utilisateur dispo dans les vues
app.use((req, res, next) => {
  res.locals.currentUser = req.session.user || null;
  res.locals.error = req.session.error || null;
  res.locals.success = req.session.success || null;
  delete req.session.error;
  delete req.session.success;
  next();
});

// Middleware d’authentification
function ensureAuth(req, res, next) {
  if (!req.session.user) {
    req.session.error = 'Vous devez être connecté.';
    return res.redirect('/login');
  }
  next();
}

// Routes
app.use((req, res, next) => {
  req.upload = upload;
  req.transporter = transporter;
  req.ensureAuth = ensureAuth;
  next();
});

app.use('/', authRoutes);
app.use('/rooms', roomRoutes);
app.use('/reservations', reservationRoutes);

// Page d’accueil
app.get('/', (req, res) => {
  db.all('SELECT * FROM rooms', (err, rooms) => {
    if (err) {
      return res.status(500).send('Erreur serveur');
    }
    res.render('index', { rooms });
  });
});

// Profil + upload photo
app.get('/profile', ensureAuth, (req, res) => {
  db.get('SELECT * FROM users WHERE id = ?', [req.session.user.id], (err, user) => {
    if (err || !user) {
      return res.status(500).send('Erreur serveur');
    }
    res.render('profile', { user });
  });
});

app.post(
  '/profile/photo',
  ensureAuth,
  upload.single('profile_photo'),
  (req, res) => {
    if (!req.file) {
      req.session.error = 'Aucun fichier envoyé.';
      return res.redirect('/profile');
    }
    const photoPath = '/uploads/' + req.file.filename;
    db.run(
      'UPDATE users SET profile_photo = ? WHERE id = ?',
      [photoPath, req.session.user.id],
      err => {
        if (err) {
          req.session.error = 'Erreur lors de la mise à jour.';
        } else {
          req.session.success = 'Photo de profil mise à jour.';
          req.session.user.profile_photo = photoPath;
        }
        res.redirect('/profile');
      }
    );
  }
);

// Démarrage serveur
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log('Serveur démarré sur http://localhost:' + PORT);
});
