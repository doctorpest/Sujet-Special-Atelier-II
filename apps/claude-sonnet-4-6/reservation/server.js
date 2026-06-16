const express = require('express');
const session = require('express-session');
const FileStore = require('session-file-store')(session);
const methodOverride = require('method-override');
const path = require('path');
const fs = require('fs');
const { initDb } = require('./routes/db');

const app = express();
const PORT = process.env.PORT || 3000;

const uploadsDir = path.join(__dirname, 'public', 'uploads');
if (!fs.existsSync(uploadsDir)) fs.mkdirSync(uploadsDir, { recursive: true });

const sessionsDir = path.join(__dirname, 'sessions');
if (!fs.existsSync(sessionsDir)) fs.mkdirSync(sessionsDir, { recursive: true });

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use(express.static(path.join(__dirname, 'public')));

app.use(session({
  store: new FileStore({ path: sessionsDir, ttl: 7 * 24 * 3600, retries: 0, logFn: () => {} }),
  secret: process.env.SESSION_SECRET || 'meetroom-secret-2024',
  resave: false,
  saveUninitialized: false,
  cookie: { maxAge: 7 * 24 * 60 * 60 * 1000 }
}));

app.use((req, res, next) => {
  res.locals.user = req.session.user || null;
  res.locals.success = req.session.success || null;
  res.locals.error = req.session.error || null;
  delete req.session.success;
  delete req.session.error;
  next();
});

app.use('/', require('./routes/auth'));
app.use('/rooms', require('./routes/rooms'));
app.use('/reservations', require('./routes/reservations'));
app.use('/profile', require('./routes/profile'));

app.use((req, res) => res.status(404).render('error', { message: 'Page introuvable', code: 404 }));
app.use((err, req, res, next) => {
  console.error(err.stack);
  res.status(500).render('error', { message: 'Erreur interne du serveur', code: 500 });
});

// Init DB first, then start server
initDb().then(() => {
  // Run SQL init after DB is ready
  return require('./init-db')();
}).then(() => {
  app.listen(PORT, () => {
    console.log(`\n🚀 MeetRoom démarré sur http://localhost:${PORT}`);
    console.log(`   Inscription : http://localhost:${PORT}/register`);
    console.log(`   Connexion   : http://localhost:${PORT}/login\n`);
  });
}).catch(err => {
  console.error('Erreur démarrage:', err);
  process.exit(1);
});
