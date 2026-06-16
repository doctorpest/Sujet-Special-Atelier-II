-- Script d'initialisation de la base de données MeetRoom

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password TEXT NOT NULL,
  avatar TEXT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS rooms (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  capacity INTEGER NOT NULL,
  equipment TEXT NOT NULL,
  photo TEXT NOT NULL,
  description TEXT,
  floor TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS reservations (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  room_id INTEGER NOT NULL,
  title TEXT NOT NULL,
  date TEXT NOT NULL,
  start_time TEXT NOT NULL,
  end_time TEXT NOT NULL,
  attendees INTEGER DEFAULT 1,
  notes TEXT,
  status TEXT DEFAULT 'confirmed',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE
);

-- Salles de démonstration
INSERT OR IGNORE INTO rooms (id, name, capacity, equipment, photo, description, floor) VALUES
(1, 'Salle Horizon', 10, 'Vidéoprojecteur,Tableau blanc,Visioconférence,WiFi', 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80', 'Grande salle lumineuse avec vue panoramique, idéale pour les réunions d''équipe et présentations clients.', '3ème étage'),
(2, 'Cabine Focus', 4, 'Écran 4K,WiFi,Prises USB', 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=800&q=80', 'Espace compact et intimiste pour les réunions de brainstorming ou entretiens confidentiels.', '1er étage'),
(3, 'Salle Synergie', 20, 'Vidéoprojecteur double,Système audio,Tableau interactif,Visioconférence,WiFi', 'https://images.unsplash.com/photo-1524758631624-e2822e304c36?w=800&q=80', 'Notre plus grande salle, parfaite pour les séminaires, formations et assemblées générales.', '2ème étage'),
(4, 'Espace Créatif', 8, 'Tableaux muraux,Post-its,Imprimante,WiFi', 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80', 'Salle dédiée aux ateliers créatifs et design thinking, équipée pour stimuler la créativité.', 'Rez-de-chaussée'),
(5, 'Bureau Zen', 2, 'Écran,WiFi,Prises multiples', 'https://images.unsplash.com/photo-1551434678-e076c223a692?w=800&q=80', 'Espace calme pour les entretiens individuels ou le travail en binôme nécessitant de la concentration.', '4ème étage'),
(6, 'Salle Innovation', 15, 'Écran interactif,Visioconférence,WiFi,Tableau blanc,Climatisation', 'https://images.unsplash.com/photo-1542744173-8e7e53415bb0?w=800&q=80', 'Salle moderne équipée des dernières technologies pour vos réunions hybrides et projets innovants.', '2ème étage');
