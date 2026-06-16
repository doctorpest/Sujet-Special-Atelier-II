CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    email TEXT UNIQUE NOT NULL,
    password TEXT NOT NULL,
    profile_pic TEXT DEFAULT 'default-profile.png'
);

CREATE TABLE IF NOT EXISTS rooms (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    capacity INTEGER NOT NULL,
    equipment TEXT,
    photo TEXT DEFAULT 'default-room.jpg'
);

CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    room_id INTEGER NOT NULL,
    booking_date TEXT NOT NULL,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(room_id) REFERENCES rooms(id)
);

-- Insertion de quelques salles par défaut
INSERT INTO rooms (name, capacity, equipment, photo) VALUES 
('Salle Émeraude', 8, 'Écran 4K, Tableau blanc, Climatisation', 'default-room.jpg'),
('Espace Cosy', 4, 'Téléviseur, Machine à café', 'default-room.jpg'),
('L''Auditorium', 50, 'Vidéoprojecteur, Micros sans fil, Sonorisation', 'default-room.jpg');