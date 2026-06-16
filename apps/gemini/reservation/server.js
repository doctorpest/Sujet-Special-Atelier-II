const express = require('express');
const session = require('express-session');
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcryptjs');
const multer = require('multer');
const nodemailer = require('nodemailer');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = 3000;

// S'assurer que le dossier des uploads existe
const uploadDir = path.join(__dirname, 'public/uploads');
if (!fs.existsSync(uploadDir)) {
    fs.mkdirSync(uploadDir, { recursive: true });
}

// Configuration de la base de données
const db = new sqlite3.Database('./database.sqlite', (err) => {
    if (err) console.error(err.message);
    console.log('Connecté à la base de données SQLite.');
});

// Exécuter le script init.sql
const initSql = fs.readFileSync(path.join(__dirname, 'init.sql'), 'utf8');
db.exec(initSql, (err) => {
    if (err) console.error("Erreur lors de l'initialisation SQL :", err.message);
});

// Configuration de Multer pour le téléversement des photos de profil
const storage = multer.diskStorage({
    destination: (req, file, cb) => cb(null, 'public/uploads/'),
    filename: (req, file, cb) => cb(null, Date.now() + path.extname(file.originalname))
});
const upload = multer({ storage: storage });

// Configuration de Nodemailer (Simulé via console pour test direct)
const transporter = nodemailer.createTransport({
    streamTransport: true,
    newline: 'unix',
    buffer: true
});

// Middleware
app.use(express.urlencoded({ extended: true }));
app.use(express.static('public'));
app.set('view engine', 'ejs');
app.use(session({
    secret: 'secret_key_salles_reunion',
    resave: false,
    saveUninitialized: false
}));

// Injection de la session globale dans les vues EJS
app.use((req, res, next) => {
    res.locals.user = req.session.user || null;
    next();
});

// Middleware de protection des routes
const redirectLogin = (req, res, next) => {
    if (!req.session.userId) return res.redirect('/login');
    next();
};

// --- ROUTES AUTHENTIFICATION ---

app.get('/register', (req, res) => res.render('register', { error: null }));
app.post('/register', (req, res) => {
    const { username, email, password } = req.body;
    const hashedPassword = bcrypt.hashSync(password, 10);

    db.run(`INSERT INTO users (username, email, password) VALUES (?, ?, ?)`, [username, email, hashedPassword], function(err) {
        if (err) return res.render('register', { error: "L'utilisateur ou l'email existe déjà." });
        res.redirect('/login');
    });
});

app.get('/login', (req, res) => res.render('login', { error: null }));
app.post('/login', (req, res) => {
    const { email, password } = req.body;
    db.get(`SELECT * FROM users WHERE email = ?`, [email], (err, user) => {
        if (err || !user || !bcrypt.compareSync(password, user.password)) {
            return res.render('login', { error: "Identifiants invalides." });
        }
        req.session.userId = user.id;
        req.session.user = user;
        res.redirect('/');
    });
});

app.get('/logout', (req, res) => {
    req.session.destroy(() => res.redirect('/login'));
});

// --- ROUTE PRINCIPALE & RECHERCHE ---

app.get('/', redirectLogin, (req, res) => {
    const { name, min_capacity, date, start_time, end_time } = query = req.query;
    
    let sql = `SELECT * FROM rooms WHERE 1=1`;
    let params = [];

    if (name) {
        sql += ` AND name LIKE ?`;
        params.push(`%${name}%`);
    }
    if (min_capacity) {
        sql += ` AND capacity >= ?`;
        params.push(parseInt(min_capacity));
    }
    // Filtrer par créneau libre si date et heures sont fournies
    if (date && start_time && end_time) {
        sql += ` AND id NOT IN (
            SELECT room_id FROM bookings 
            WHERE booking_date = ? 
            AND NOT (start_time >= ? OR end_time <= ?)
        )`;
        params.push(date, end_time, start_time);
    }

    db.all(sql, params, (err, rooms) => {
        if (err) console.error(err);
        res.render('rooms', { rooms, query });
    });
});

// --- RESERVATIONS ---

app.post('/bookings', redirectLogin, (req, res) => {
    const { room_id, booking_date, start_time, end_time } = req.body;
    const user_id = req.session.userId;

    // Vérifier les conflits de réservation au dernier moment
    db.get(`SELECT id FROM bookings WHERE room_id = ? AND booking_date = ? AND NOT (start_time >= ? OR end_time <= ?)`, 
    [room_id, booking_date, end_time, start_time], (err, conflict) => {
        if (conflict) {
            return res.send("<script>alert('Ce créneau est déjà occupé !'); window.history.back();</script>");
        }

        db.run(`INSERT INTO bookings (user_id, room_id, booking_date, start_time, end_time) VALUES (?, ?, ?, ?, ?)`,
        [user_id, room_id, booking_date, start_time, end_time], function(err) {
            if (err) return res.send("Erreur lors de la réservation.");

            // Envoi de l'email de confirmation (Simulé dans les logs)
            transporter.sendMail({
                from: '"Plateforme Réservation" <noreply@reservation.com>',
                to: req.session.user.email,
                subject: 'Confirmation de votre réservation',
                text: `Bonjour, votre réservation pour la salle (ID: ${room_id}) le ${booking_date} de ${start_time} à ${end_time} est confirmée.`
            }, (err, info) => {
                console.log("=== EMAIL DE CONFIRMATION ENVOYÉ ===");
                console.log(info.message.toString());
            });

            res.redirect('/bookings');
        });
    });
});

app.get('/bookings', redirectLogin, (req, res) => {
    db.all(`SELECT bookings.*, rooms.name as room_name FROM bookings 
            JOIN rooms ON bookings.room_id = rooms.id 
            WHERE bookings.user_id = ?`, [req.session.userId], (err, bookings) => {
        res.render('bookings', { bookings, error: null });
    });
});

// Modification d'une réservation
app.post('/bookings/edit/:id', redirectLogin, (req, res) => {
    const { booking_date, start_time, end_time } = req.body;
    const bookingId = req.params.id;

    // Récupérer la réservation actuelle pour obtenir le room_id
    db.get(`SELECT room_id FROM bookings WHERE id = ? AND user_id = ?`, [bookingId, req.session.userId], (err, booking) => {
        if (!booking) return res.send("Réservation introuvable.");

        // Vérifier les conflits (en excluant la réservation actuelle)
        db.get(`SELECT id FROM bookings WHERE room_id = ? AND booking_date = ? AND id != ? AND NOT (start_time >= ? OR end_time <= ?)`, 
        [booking.room_id, booking_date, bookingId, end_time, start_time], (err, conflict) => {
            if (conflict) {
                return res.send("<script>alert('Ce nouveau créneau est indisponible.'); window.history.back();</script>");
            }

            db.run(`UPDATE bookings SET booking_date = ?, start_time = ?, end_time = ? WHERE id = ? AND user_id = ?`,
            [booking_date, start_time, end_time, bookingId, req.session.userId], () => {
                res.redirect('/bookings');
            });
        });
    });
});

// Annulation de réservation
app.post('/bookings/delete/:id', redirectLogin, (req, res) => {
    db.run(`DELETE FROM bookings WHERE id = ? AND user_id = ?`, [req.params.id, req.session.userId], () => {
        res.redirect('/bookings');
    });
});

// --- PROFIL UTILISATEUR ---

app.get('/profile', redirectLogin, (req, res) => {
    db.get(`SELECT * FROM users WHERE id = ?`, [req.session.userId], (err, user) => {
        res.render('profile', { user, success: null });
    });
});

app.post('/profile/upload', redirectLogin, upload.single('profile_pic'), (req, res) => {
    if (!req.file) return res.redirect('/profile');
    
    db.run(`UPDATE users SET profile_pic = ? WHERE id = ?`, [req.file.filename, req.session.userId], () => {
        req.session.user.profile_pic = req.file.filename; // Mettre à jour la session
        res.redirect('/profile');
    });
});

app.listen(PORT, () => console.log(`Serveur démarré sur http://localhost:${PORT}`));