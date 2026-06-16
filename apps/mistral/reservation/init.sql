-- Script d'initialisation de la base de données
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  email TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  name TEXT NOT NULL,
  profile_picture TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  capacity INTEGER NOT NULL,
  equipment TEXT,
  photo_url TEXT,
  description TEXT
);

CREATE TABLE IF NOT EXISTS reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  room_id INTEGER NOT NULL,
  start_time DATETIME NOT NULL,
  end_time DATETIME NOT NULL,
  status TEXT DEFAULT 'confirmed',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  FOREIGN KEY (room_id) REFERENCES rooms(id)
);

-- Données de test
INSERT INTO rooms (name, capacity, equipment, photo_url, description) VALUES
  ('Salle de Réunion A', 10, 'Projecteur, Tableau blanc, WiFi', '/images/room-a.jpg', 'Salle moderne pour petites réunions'),
  ('Salle de Conférence', 50, 'Projecteur, Sonorisation, Tableau interactif, WiFi', '/images/conference.jpg', 'Grande salle pour conférences'),
  ('Salle Creative', 8, 'Tableau blanc, Paperboard, WiFi', '/images/creative.jpg', 'Espace créatif pour brainstorming'),
  ('Salle VIP', 4, 'Vidéoconférence, Imprimante, WiFi', '/images/vip.jpg', 'Salle privée pour réunions importantes'),
  ('Salle de Formation', 20, 'Projecteur, Tableau blanc, WiFi, Ordi portable', '/images/formation.jpg', 'Salle dédiée aux formations'),
  ('Salle Collaborative', 12, 'Écran tactile, Tableau blanc, WiFi, Webcam', '/images/collaborative.jpg', 'Espace pour travail collaboratif');

INSERT INTO users (email, password, name) VALUES
  ('admin@example.com', '$2b$10$N9qo8uLOickgx2ZMRZoMy.Mrq4H6JX5Q7J6X5J6X5J6X5J6', 'Administrateur');