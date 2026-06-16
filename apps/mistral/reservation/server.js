require('dotenv').config();
const express = require('express');
const session = require('express-session');
const path = require('path');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcrypt');
const multer = require('multer');
const nodemailer = require('nodemailer');
const { body, validationResult } = require('express-validator');

const app = express();
const PORT = process.env.PORT || 3000;

// Base de données
const db = new sqlite3.Database('./meeting_rooms.db', (err) => {
  if (err) console.error('Erreur BD:', err.message);
  else {
    console.log('Connecté à SQLite.');
    initializeDatabase();
  }
});

function initializeDatabase() {
  db.serialize(() => {
    db.run(`CREATE TABLE IF NOT EXISTS users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT UNIQUE NOT NULL, password TEXT NOT NULL, name TEXT NOT NULL, profile_picture TEXT, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)`);
    db.run(`CREATE TABLE IF NOT EXISTS rooms (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, capacity INTEGER NOT NULL, equipment TEXT, photo_url TEXT, description TEXT)`);
    db.run(`CREATE TABLE IF NOT EXISTS reservations (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, room_id INTEGER NOT NULL, start_time DATETIME NOT NULL, end_time DATETIME NOT NULL, status TEXT DEFAULT 'confirmed', created_at DATETIME DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id), FOREIGN KEY (room_id) REFERENCES rooms(id))`);

    db.get("SELECT COUNT(*) as count FROM rooms", (err, row) => {
      if (err) return console.error(err.message);
      if (row.count === 0) {
        const rooms = [
          { name: 'Salle de Réunion A', capacity: 10, equipment: 'Projecteur, Tableau blanc, WiFi', photo_url: '/images/room-a.jpg', description: 'Salle moderne' },
          { name: 'Salle de Conférence', capacity: 50, equipment: 'Projecteur, Sonorisation, Tableau interactif, WiFi', photo_url: '/images/conference.jpg', description: 'Grande salle' },
          { name: 'Salle Creative', capacity: 8, equipment: 'Tableau blanc, Paperboard, WiFi', photo_url: '/images/creative.jpg', description: 'Espace créatif' },
          { name: 'Salle VIP', capacity: 4, equipment: 'Vidéoconférence, Imprimante, WiFi', photo_url: '/images/vip.jpg', description: 'Salle privée' }
        ];
        rooms.forEach(room => db.run("INSERT INTO rooms (name, capacity, equipment, photo_url, description) VALUES (?, ?, ?, ?, ?)", [room.name, room.capacity, room.equipment, room.photo_url, room.description]));
        console.log('Salles de test insérées.');
      }
    });

    db.get("SELECT COUNT(*) as count FROM users WHERE email = 'admin@example.com'", (err, row) => {
      if (err) return console.error(err.message);
      if (row.count === 0) {
        const hashedPassword = bcrypt.hashSync('admin123', 10);
        db.run("INSERT INTO users (email, password, name) VALUES (?, ?, ?)", ['admin@example.com', hashedPassword, 'Administrateur']);
        console.log('Utilisateur admin créé.');
      }
    });
  });
}

// Multer pour uploads
const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, 'public/uploads/'),
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9);
    cb(null, uniqueSuffix + path.extname(file.originalname));
  }
});
const upload = multer({ storage: storage });

// Nodemailer
const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST || 'smtp.gmail.com',
  port: process.env.SMTP_PORT || 587,
  secure: false,
  auth: {
    user: process.env.EMAIL_USER || 'votre-email@gmail.com',
    pass: process.env.EMAIL_PASS || 'votre-mot-de-passe'
  }
});

// Middleware
app.use(express.static('public'));
app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(session({
  secret: process.env.SESSION_SECRET || 'votre-secret-super-secure',
  resave: false,
  saveUninitialized: false,
  cookie: { secure: false, maxAge: 24 * 60 * 60 * 1000 }
}));

const requireAuth = (req, res, next) => req.session.userId ? next() : res.redirect('/login');

const getUser = (req, res, next) => {
  if (req.session.userId) {
    db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => {
      if (err) console.error(err.message);
      else res.locals.user = user;
      next();
    });
  } else {
    res.locals.user = null;
    next();
  }
};

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use(getUser);

// Routes
app.get('/', (req, res) => res.render('index', { title: 'Accueil - Réservation de Salles' }));

// Auth
app.get('/login', (req, res) => req.session.userId ? res.redirect('/') : res.render('auth/login', { title: 'Connexion', error: null }));
app.post('/login', [body('email').isEmail().normalizeEmail(), body('password').notEmpty()], (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.render('auth/login', { title: 'Connexion', error: 'Email ou mot de passe invalide', oldInput: req.body });

  const { email, password } = req.body;
  db.get("SELECT * FROM users WHERE email = ?", [email], (err, user) => {
    if (err || !user || !bcrypt.compareSync(password, user.password)) {
      return res.render('auth/login', { title: 'Connexion', error: 'Email ou mot de passe invalide', oldInput: req.body });
    }
    req.session.userId = user.id;
    res.redirect('/');
  });
});

app.get('/register', (req, res) => req.session.userId ? res.redirect('/') : res.render('auth/register', { title: 'Inscription', error: null }));
app.post('/register', [body('name').notEmpty().trim().escape(), body('email').isEmail().normalizeEmail(), body('password').isLength({ min: 6 })], (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.render('auth/register', { title: 'Inscription', error: errors.array()[0].msg, oldInput: req.body });

  const { name, email, password } = req.body;
  const hashedPassword = bcrypt.hashSync(password, 10);
  db.run("INSERT INTO users (name, email, password) VALUES (?, ?, ?)", [name, email, hashedPassword], function(err) {
    if (err) {
      if (err.message.includes('UNIQUE constraint failed')) return res.render('auth/register', { title: 'Inscription', error: 'Cet email est déjà utilisé', oldInput: req.body });
      return res.render('auth/register', { title: 'Inscription', error: 'Une erreur est survenue', oldInput: req.body });
    }
    req.session.userId = this.lastID;
    res.redirect('/');
  });
});

app.get('/logout', (req, res) => { req.session.destroy(); res.redirect('/'); });

// Rooms
app.get('/rooms', (req, res) => {
  const { search, minCapacity, date, startTime, endTime } = req.query;
  let query = "SELECT * FROM rooms WHERE 1=1";
  const params = [];
  if (search) { query += " AND name LIKE ?"; params.push(`%${search}%`); }
  if (minCapacity) { query += " AND capacity >= ?"; params.push(parseInt(minCapacity)); }

  db.all(query, params, (err, rooms) => {
    if (err) return res.render('rooms/list', { title: 'Liste des Salles', rooms: [], search, minCapacity, date, startTime, endTime });

    if (date && startTime && endTime) {
      const startDateTime = `${date}T${startTime}:00`;
      const endDateTime = `${date}T${endTime}:00`;
      const checkAllRooms = rooms.map(room => new Promise(resolve => {
        db.get(`SELECT COUNT(*) as count FROM reservations WHERE room_id = ? AND status = 'confirmed' AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))`,
          [room.id, endDateTime, startDateTime, startDateTime, endDateTime, startDateTime, endDateTime],
          (err, row) => resolve({ ...room, available: !err && row.count === 0 }));
      }));
      Promise.all(checkAllRooms).then(roomsWithAvailability => res.render('rooms/list', { title: 'Liste des Salles', rooms: roomsWithAvailability, search, minCapacity, date, startTime, endTime }));
    } else {
      res.render('rooms/list', { title: 'Liste des Salles', rooms: rooms.map(room => ({ ...room, available: true })), search, minCapacity, date, startTime, endTime });
    }
  });
});

app.get('/rooms/:id', (req, res) => {
  const roomId = req.params.id;
  db.get("SELECT * FROM rooms WHERE id = ?", [roomId], (err, room) => {
    if (err || !room) return res.status(err ? 500 : 404).send(err ? 'Erreur serveur' : 'Salle non trouvée');
    db.all(`SELECT r.*, u.name as user_name, u.email as user_email FROM reservations r JOIN users u ON r.user_id = u.id WHERE r.room_id = ? AND r.status = 'confirmed' ORDER BY r.start_time`, [roomId], (err, reservations) => {
      res.render('rooms/detail', { title: room.name, room, reservations: err ? [] : reservations });
    });
  });
});

// Reservations
app.get('/reservations', requireAuth, (req, res) => {
  db.all(`SELECT r.*, room.name as room_name, room.capacity, room.equipment, room.photo_url FROM reservations r JOIN rooms room ON r.room_id = room.id WHERE r.user_id = ? ORDER BY r.start_time DESC`, [req.session.userId], (err, reservations) => {
    res.render('reservations/my-reservations', { title: 'Mes Réservations', reservations: err ? [] : reservations });
  });
});

app.get('/reservations/new', requireAuth, (req, res) => {
  db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/new', { title: 'Nouvelle Réservation', rooms: err ? [] : rooms, error: null }));
});

app.post('/reservations', requireAuth, [body('roomId').isInt(), body('date').notEmpty(), body('startTime').notEmpty(), body('endTime').notEmpty()], (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/new', { title: 'Nouvelle Réservation', rooms: err ? [] : rooms, error: errors.array()[0].msg, oldInput: req.body }));

  const { roomId, date, startTime, endTime } = req.body;
  const startDateTime = `${date}T${startTime}:00`;
  const endDateTime = `${date}T${endTime}:00`;

  db.get(`SELECT COUNT(*) as count FROM reservations WHERE room_id = ? AND status = 'confirmed' AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))`,
    [roomId, endDateTime, startDateTime, startDateTime, endDateTime, startDateTime, endDateTime], (err, row) => {
    if (err || row.count > 0) {
      return db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/new', {
        title: 'Nouvelle Réservation', rooms: err ? [] : rooms,
        error: err ? 'Une erreur est survenue' : 'La salle n\'est pas disponible à ce créneau', oldInput: req.body
      }));
    }

    db.run("INSERT INTO reservations (user_id, room_id, start_time, end_time, status) VALUES (?, ?, ?, ?, ?)", [req.session.userId, roomId, startDateTime, endDateTime, 'confirmed'], function(err) {
      if (err) return db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/new', { title: 'Nouvelle Réservation', rooms: err ? [] : rooms, error: 'Une erreur est survenue', oldInput: req.body }));

      db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => {
        if (!err && user) db.get("SELECT * FROM rooms WHERE id = ?", [roomId], (err, room) => {
          if (!err && room) transporter.sendMail({
            from: process.env.EMAIL_USER || 'votre-email@gmail.com',
            to: user.email,
            subject: `Confirmation de réservation - ${room.name}`,
            html: `<h2>Confirmation de Réservation</h2><p>Bonjour ${user.name},</p><p>Votre réservation a été confirmée.</p><p><strong>Détails:</strong></p><ul><li><strong>Salle:</strong> ${room.name}</li><li><strong>Date:</strong> ${date}</li><li><strong>Heure:</strong> ${startTime} - ${endTime}</li></ul><p>Merci!</p>`
          });
        });
      });
      res.redirect('/reservations');
    });
  });
});

app.get('/reservations/:id/edit', requireAuth, (req, res) => {
  const reservationId = req.params.id;
  db.get(`SELECT r.*, room.name as room_name FROM reservations r JOIN rooms room ON r.room_id = room.id WHERE r.id = ? AND r.user_id = ?`, [reservationId, req.session.userId], (err, reservation) => {
    if (err || !reservation) return res.status(err ? 500 : 404).send(err ? 'Erreur serveur' : 'Réservation non trouvée');
    const startDate = reservation.start_time.split('T')[0];
    const startTime = reservation.start_time.split('T')[1].substring(0, 5);
    const endDate = reservation.end_time.split('T')[0];
    const endTime = reservation.end_time.split('T')[1].substring(0, 5);
    db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/edit', {
      title: 'Modifier Réservation',
      reservation: { ...reservation, startDate, startTime, endDate, endTime },
      rooms: err ? [] : rooms,
      error: null
    }));
  });
});

app.post('/reservations/:id', requireAuth, [body('roomId').isInt(), body('date').notEmpty(), body('startTime').notEmpty(), body('endTime').notEmpty()], (req, res) => {
  const reservationId = req.params.id;
  const errors = validationResult(req);
  if (!errors.isEmpty()) return db.get(`SELECT r.*, room.name as room_name FROM reservations r JOIN rooms room ON r.room_id = room.id WHERE r.id = ? AND r.user_id = ?`, [reservationId, req.session.userId], (err, reservation) => {
    if (reservation) {
      const startDate = reservation.start_time.split('T')[0];
      const startTime = reservation.start_time.split('T')[1].substring(0, 5);
      const endDate = reservation.end_time.split('T')[0];
      const endTime = reservation.end_time.split('T')[1].substring(0, 5);
      db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/edit', {
        title: 'Modifier Réservation',
        reservation: { ...reservation, startDate, startTime, endDate, endTime },
        rooms: err ? [] : rooms,
        error: errors.array()[0].msg,
        oldInput: req.body
      }));
    }
  });

  const { roomId, date, startTime, endTime } = req.body;
  const startDateTime = `${date}T${startTime}:00`;
  const endDateTime = `${date}T${endTime}:00`;

  db.get(`SELECT COUNT(*) as count FROM reservations WHERE room_id = ? AND id != ? AND status = 'confirmed' AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?) OR (start_time >= ? AND end_time <= ?))`,
    [roomId, reservationId, endDateTime, startDateTime, startDateTime, endDateTime, startDateTime, endDateTime], (err, row) => {
    if (err || row.count > 0) return db.get(`SELECT r.*, room.name as room_name FROM reservations r JOIN rooms room ON r.room_id = room.id WHERE r.id = ? AND r.user_id = ?`, [reservationId, req.session.userId], (err, reservation) => {
      if (reservation) {
        const startDate = reservation.start_time.split('T')[0];
        const startTime = reservation.start_time.split('T')[1].substring(0, 5);
        const endDate = reservation.end_time.split('T')[0];
        const endTime = reservation.end_time.split('T')[1].substring(0, 5);
        db.all("SELECT * FROM rooms", (err, rooms) => res.render('reservations/edit', {
          title: 'Modifier Réservation',
          reservation: { ...reservation, startDate, startTime, endDate, endTime },
          rooms: err ? [] : rooms,
          error: 'La salle n\'est pas disponible à ce créneau',
          oldInput: req.body
        }));
      }
    });

    db.run("UPDATE reservations SET room_id = ?, start_time = ?, end_time = ? WHERE id = ? AND user_id = ?", [roomId, startDateTime, endDateTime, reservationId, req.session.userId], function(err) {
      if (err) return res.redirect('/reservations');
      db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => {
        if (!err && user) db.get("SELECT * FROM rooms WHERE id = ?", [roomId], (err, room) => {
          if (!err && room) transporter.sendMail({
            from: process.env.EMAIL_USER || 'votre-email@gmail.com',
            to: user.email,
            subject: `Modification de réservation - ${room.name}`,
            html: `<h2>Modification de Réservation</h2><p>Bonjour ${user.name},</p><p>Votre réservation a été modifiée.</p><p><strong>Nouveaux détails:</strong></p><ul><li><strong>Salle:</strong> ${room.name}</li><li><strong>Date:</strong> ${date}</li><li><strong>Heure:</strong> ${startTime} - ${endTime}</li></ul>`
          });
        });
      });
      res.redirect('/reservations');
    });
  });
});

app.post('/reservations/:id/cancel', requireAuth, (req, res) => {
  const reservationId = req.params.id;
  db.get(`SELECT r.*, room.name as room_name, room.capacity, room.equipment FROM reservations r JOIN rooms room ON r.room_id = room.id WHERE r.id = ? AND r.user_id = ?`, [reservationId, req.session.userId], (err, reservation) => {
    if (err || !reservation) return res.redirect('/reservations');
    db.run("UPDATE reservations SET status = 'cancelled' WHERE id = ? AND user_id = ?", [reservationId, req.session.userId], function(err) {
      if (err) return res.redirect('/reservations');
      db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => {
        if (!err && user) transporter.sendMail({
          from: process.env.EMAIL_USER || 'votre-email@gmail.com',
          to: user.email,
          subject: `Annulation de réservation - ${reservation.room_name}`,
          html: `<h2>Annulation de Réservation</h2><p>Bonjour ${user.name},</p><p>Votre réservation a été annulée.</p><p><strong>Détails:</strong></p><ul><li><strong>Salle:</strong> ${reservation.room_name}</li><li><strong>Date:</strong> ${reservation.start_time.split('T')[0]}</li></ul>`
        });
      });
      res.redirect('/reservations');
    });
  });
});

// Profile
app.get('/profile', requireAuth, (req, res) => res.render('profile/profile', { title: 'Mon Profil', error: null, success: null }));

app.post('/profile', requireAuth, upload.single('profilePicture'), (req, res) => {
  const { name, email } = req.body;
  let profilePicture = res.locals.user.profile_picture;
  if (req.file) profilePicture = `/uploads/${req.file.filename}`;

  db.run("UPDATE users SET name = ?, email = ?, profile_picture = ? WHERE id = ?", [name, email, profilePicture, req.session.userId], function(err) {
    if (err) {
      if (err.message.includes('UNIQUE constraint failed')) return res.render('profile/profile', { title: 'Mon Profil', error: 'Cet email est déjà utilisé', success: null, oldInput: req.body });
      return res.render('profile/profile', { title: 'Mon Profil', error: 'Une erreur est survenue', success: null, oldInput: req.body });
    }
    db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => { if (!err) res.locals.user = user; });
    res.render('profile/profile', { title: 'Mon Profil', error: null, success: 'Votre profil a été mis à jour avec succès', oldInput: req.body });
  });
});

app.post('/profile/password', requireAuth, [body('currentPassword').notEmpty(), body('newPassword').isLength({ min: 6 }), body('confirmPassword').custom((value, { req }) => value === req.body.newPassword || 'Les mots de passe ne correspondent pas')], (req, res) => {
  const errors = validationResult(req);
  if (!errors.isEmpty()) return res.render('profile/profile', { title: 'Mon Profil', error: errors.array()[0].msg, success: null, oldInput: req.body, passwordError: true });

  const { currentPassword, newPassword } = req.body;
  db.get("SELECT * FROM users WHERE id = ?", [req.session.userId], (err, user) => {
    if (err || !bcrypt.compareSync(currentPassword, user.password)) return res.render('profile/profile', { title: 'Mon Profil', error: 'Le mot de passe actuel est incorrect', success: null, oldInput: req.body, passwordError: true });
    const hashedPassword = bcrypt.hashSync(newPassword, 10);
    db.run("UPDATE users SET password = ? WHERE id = ?", [hashedPassword, req.session.userId], function(err) {
      if (err) return res.render('profile/profile', { title: 'Mon Profil', error: 'Une erreur est survenue', success: null, oldInput: req.body, passwordError: true });
      res.render('profile/profile', { title: 'Mon Profil', error: null, success: 'Votre mot de passe a été mis à jour avec succès', oldInput: req.body, passwordSuccess: true });
    });
  });
});

// 404 et démarrage
app.use((req, res) => res.status(404).render('error', { title: 'Page non trouvée', message: 'La page que vous cherchez n\'existe pas.' }));

app.listen(PORT, () => console.log(`Serveur démarré sur http://localhost:${PORT}`));

process.on('SIGINT', () => db.close(() => console.log('BD fermée.')));