require('dotenv').config();
const express = require('express');
const session = require('express-session');
const SQLiteStore = require('connect-sqlite3')(session);
const path = require('path');
const methodOverride = require('method-override');
const fs = require('fs');
const { db, initializeDatabase } = require('./routes/db');

const app = express();
const PORT = process.env.PORT || 3000;

initializeDatabase();

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

app.use(express.urlencoded({ extended: true }));
app.use(express.json());
app.use(methodOverride('_method'));
app.use(express.static(path.join(__dirname, 'public')));

app.use(session({
  store: new SQLiteStore({ db: 'sessions.sqlite', dir: path.join(__dirname, 'db') }),
  secret: process.env.SESSION_SECRET || 'dev-secret-change-me',
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: true, maxAge: 1000 * 60 * 60 * 24 }
}));

app.use((req, res, next) => {
  res.locals.currentUser = req.session.user || null;
  res.locals.flash = req.session.flash || null;
  delete req.session.flash;
  next();
});

app.use('/', require('./routes/auth'));
app.use('/rooms', require('./routes/rooms'));
app.use('/reservations', require('./routes/reservations'));
app.use('/profile', require('./routes/profile'));

app.get('/', (req, res) => res.redirect('/rooms'));

app.use((req, res) => {
  res.status(404).render('error', { message: 'Page introuvable.' });
});

app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).render('error', { message: err.message || 'Erreur serveur.' });
});

app.listen(PORT, () => {
  console.log(`Application disponible sur http://localhost:${PORT}`);
});
