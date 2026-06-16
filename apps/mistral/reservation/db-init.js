require('dotenv').config();
const sqlite3 = require('sqlite3').verbose();
const bcrypt = require('bcrypt');
const path = require('path');

const dbPath = path.join(__dirname, 'meeting_rooms.db');
const db = new sqlite3.Database(dbPath);

console.log('Initialisation de la base de données...\n');

db.serialize(() => {
  db.run("DROP TABLE IF EXISTS reservations");
  db.run("DROP TABLE IF EXISTS rooms");
  db.run("DROP TABLE IF EXISTS users");

  db.run(`
    CREATE TABLE users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      email TEXT UNIQUE NOT NULL,
      password TEXT NOT NULL,
      name TEXT NOT NULL,
      profile_picture TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `, (err) => err ? console.error('Erreur table users:', err.message) : console.log('✓ Table users créée'));

  db.run(`
    CREATE TABLE rooms (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      capacity INTEGER NOT NULL,
      equipment TEXT,
      photo_url TEXT,
      description TEXT
    )
  `, (err) => err ? console.error('Erreur table rooms:', err.message) : console.log('✓ Table rooms créée'));

  db.run(`
    CREATE TABLE reservations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      room_id INTEGER NOT NULL,
      start_time DATETIME NOT NULL,
      end_time DATETIME NOT NULL,
      status TEXT DEFAULT 'confirmed',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id),
      FOREIGN KEY (room_id) REFERENCES rooms(id)
    )
  `, (err) => err ? console.error('Erreur table reservations:', err.message) : console.log('✓ Table reservations créée'));

  const rooms = [
    { name: 'Salle de Réunion A', capacity: 10, equipment: 'Projecteur, Tableau blanc, WiFi', photo_url: '/images/room-a.jpg', description: 'Salle moderne pour petites réunions' },
    { name: 'Salle de Conférence', capacity: 50, equipment: 'Projecteur, Sonorisation, Tableau interactif, WiFi', photo_url: '/images/conference.jpg', description: 'Grande salle pour conférences' },
    { name: 'Salle Creative', capacity: 8, equipment: 'Tableau blanc, Paperboard, WiFi', photo_url: '/images/creative.jpg', description: 'Espace créatif pour brainstorming' },
    { name: 'Salle VIP', capacity: 4, equipment: 'Vidéoconférence, Imprimante, WiFi', photo_url: '/images/vip.jpg', description: 'Salle privée pour réunions importantes' },
    { name: 'Salle de Formation', capacity: 20, equipment: 'Projecteur, Tableau blanc, WiFi, Ordi portable', photo_url: '/images/formation.jpg', description: 'Salle dédiée aux formations' },
    { name: 'Salle Collaborative', capacity: 12, equipment: 'Écran tactile, Tableau blanc, WiFi, Webcam', photo_url: '/images/collaborative.jpg', description: 'Espace pour travail collaboratif' }
  ];

  console.log('\nInsertion des salles...');
  rooms.forEach(room => {
    db.run(
      "INSERT INTO rooms (name, capacity, equipment, photo_url, description) VALUES (?, ?, ?, ?, ?)",
      [room.name, room.capacity, room.equipment, room.photo_url, room.description],
      function(err) { if (err) console.error('Erreur:', room.name, err.message); else console.log(`✓ Salle "${room.name}" insérée (ID: ${this.lastID})`); }
    );
  });

  console.log('\nInsertion de l\'utilisateur admin...');
  const adminPassword = bcrypt.hashSync('admin123', 10);
  db.run(
    "INSERT INTO users (email, password, name) VALUES (?, ?, ?)",
    ['admin@example.com', adminPassword, 'Administrateur'],
    function(err) { if (err) console.error('Erreur admin:', err.message); else console.log(`✓ Utilisateur admin inséré (ID: ${this.lastID})`); }
  );
});

setTimeout(() => {
  db.close((err) => {
    if (err) console.error('Erreur fermeture BD:', err.message);
    else console.log('\n✅ Base de données initialisée avec succès!\nFichier: meeting_rooms.db\nDonnées de test: admin@example.com / admin123');
  });
}, 1000);