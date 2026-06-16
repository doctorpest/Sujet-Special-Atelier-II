const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const dbFile = path.join(__dirname, 'database.sqlite');
const db = new sqlite3.Database(dbFile);

// Création des tables si elles n'existent pas
db.serialize(() => {
  db.run(`
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      password_hash TEXT NOT NULL,
      profile_photo TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )
  `);

  db.run(`
    CREATE TABLE IF NOT EXISTS rooms (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      capacity INTEGER NOT NULL,
      equipment TEXT,
      photo TEXT
    )
  `);

  db.run(`
    CREATE TABLE IF NOT EXISTS reservations (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      room_id INTEGER NOT NULL,
      date TEXT NOT NULL,
      start_time TEXT NOT NULL,
      end_time TEXT NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (user_id) REFERENCES users(id),
      FOREIGN KEY (room_id) REFERENCES rooms(id)
    )
  `);

  // Insérer quelques salles si la table est vide
  db.get('SELECT COUNT(*) AS count FROM rooms', (err, row) => {
    if (err) return;
    if (row.count === 0) {
      const stmt = db.prepare(`
        INSERT INTO rooms (name, capacity, equipment, photo)
        VALUES (?, ?, ?, ?)
      `);
      stmt.run('Salle Alpha', 8, 'Écran, Visioconférence, Wi-Fi', '/images/room1.jpg');
      stmt.run('Salle Beta', 12, 'Projecteur, Tableau blanc, Wi-Fi', '/images/room2.jpg');
      stmt.run('Salle Gamma', 4, 'Wi-Fi', '/images/room3.jpg');
      stmt.finalize();
    }
  });
});

module.exports = db;
