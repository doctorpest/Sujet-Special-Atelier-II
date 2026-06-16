const express = require('express');
const db = require('../db');
const { sendReservationConfirmation } = require('../email');
const router = express.Router();

// Middleware d'authentification
function requireAuth(req, res, next) {
  if (!req.session.user) return res.redirect('/login');
  next();
}

// Créer une réservation
router.post('/', requireAuth, (req, res) => {
  const { room_id, date, start_time, end_time } = req.body;
  const userId = req.session.user.id;

  if (!room_id || !date || !start_time || !end_time) {
    const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(room_id);
    return res.render('rooms/detail', { room, error: 'Tous les champs sont obligatoires.' });
  }

  const start = `${date} ${start_time}:00`;
  const end = `${date} ${end_time}:00`;

  // Vérifier le chevauchement
  const conflict = db.prepare(`
    SELECT id FROM reservations 
    WHERE room_id = ? AND start_time < ? AND end_time > ?
  `).get(room_id, end, start);

  if (conflict) {
    const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(room_id);
    return res.render('rooms/detail', { room, error: 'Ce créneau est déjà réservé.' });
  }

  const stmt = db.prepare('INSERT INTO reservations (user_id, room_id, start_time, end_time) VALUES (?, ?, ?, ?)');
  const result = stmt.run(userId, room_id, start, end);
  const reservation = db.prepare(`
    SELECT r.*, rooms.name AS roomName 
    FROM reservations r 
    JOIN rooms ON r.room_id = rooms.id 
    WHERE r.id = ?
  `).get(result.lastInsertRowid);

  // Envoi email de confirmation
  sendReservationConfirmation(req.session.user.email, reservation);

  res.redirect('/reservations');
});

// Liste des réservations de l'utilisateur
router.get('/', requireAuth, (req, res) => {
  const reservations = db.prepare(`
    SELECT r.*, rooms.name AS roomName, rooms.capacity 
    FROM reservations r 
    JOIN rooms ON r.room_id = rooms.id 
    WHERE r.user_id = ? 
    ORDER BY r.start_time DESC
  `).all(req.session.user.id);
  res.render('reservations/my', { reservations });
});

// Formulaire de modification
router.get('/:id/edit', requireAuth, (req, res) => {
  const reservation = db.prepare(`
    SELECT r.*, rooms.name AS roomName 
    FROM reservations r 
    JOIN rooms ON r.room_id = rooms.id 
    WHERE r.id = ? AND r.user_id = ?
  `).get(req.params.id, req.session.user.id);
  if (!reservation) return res.status(404).send('Réservation introuvable');
  res.render('reservations/edit', { reservation, error: null });
});

// Mise à jour de la réservation
router.post('/:id', requireAuth, (req, res) => {
  const { date, start_time, end_time } = req.body;
  const reservationId = req.params.id;
  const reservation = db.prepare('SELECT * FROM reservations WHERE id = ? AND user_id = ?').get(reservationId, req.session.user.id);
  if (!reservation) return res.status(404).send('Réservation introuvable');

  const start = `${date} ${start_time}:00`;
  const end = `${date} ${end_time}:00`;

  // Vérifier chevauchement (en excluant la réservation elle-même)
  const conflict = db.prepare(`
    SELECT id FROM reservations 
    WHERE room_id = ? AND id != ? AND start_time < ? AND end_time > ?
  `).get(reservation.room_id, reservationId, end, start);

  if (conflict) {
    return res.render('reservations/edit', { reservation, error: 'Créneau déjà occupé.' });
  }

  db.prepare('UPDATE reservations SET start_time = ?, end_time = ? WHERE id = ?').run(start, end, reservationId);
  res.redirect('/reservations');
});

// Annulation
router.post('/:id/cancel', requireAuth, (req, res) => {
  db.prepare('DELETE FROM reservations WHERE id = ? AND user_id = ?').run(req.params.id, req.session.user.id);
  res.redirect('/reservations');
});

module.exports = router;