const express = require('express');
const router = express.Router();
const { db } = require('./db');
const { requireAuth } = require('./middleware');
const { sendConfirmationEmail } = require('./mailer');

// My reservations
router.get('/', requireAuth, (req, res) => {
  const reservations = db.prepare(`
    SELECT res.*, r.name as room_name, r.photo as room_photo, r.capacity as room_capacity
    FROM reservations res
    JOIN rooms r ON res.room_id = r.id
    WHERE res.user_id = ?
    ORDER BY res.date DESC, res.start_time DESC
  `).all(req.session.user.id);

  const now = new Date();
  const upcoming = reservations.filter(r => {
    const dt = new Date(`${r.date}T${r.end_time}`);
    return dt > now && r.status !== 'cancelled';
  });
  const past = reservations.filter(r => {
    const dt = new Date(`${r.date}T${r.end_time}`);
    return dt <= now || r.status === 'cancelled';
  });

  res.render('reservations/index', { upcoming, past });
});

// New reservation form
router.get('/new', requireAuth, (req, res) => {
  const { room_id, date, start } = req.query;
  const rooms = db.prepare('SELECT * FROM rooms ORDER BY name').all();
  const room = room_id ? db.prepare('SELECT * FROM rooms WHERE id = ?').get(room_id) : null;
  res.render('reservations/new', { rooms, preRoom: room, preDate: date || '', preStart: start || '' });
});

// Create reservation
router.post('/', requireAuth, async (req, res) => {
  const { room_id, title, date, start_time, end_time, attendees, notes } = req.body;

  if (!room_id || !title || !date || !start_time || !end_time) {
    req.session.error = 'Tous les champs obligatoires doivent être remplis.';
    return res.redirect('/reservations/new');
  }

  if (start_time >= end_time) {
    req.session.error = 'L\'heure de fin doit être après l\'heure de début.';
    return res.redirect('/reservations/new');
  }

  // Check date is not in the past
  const now = new Date();
  const resDate = new Date(`${date}T${start_time}`);
  if (resDate < now) {
    req.session.error = 'Impossible de réserver dans le passé.';
    return res.redirect('/reservations/new');
  }

  // Check conflicts
  const conflict = db.prepare(`
    SELECT id FROM reservations
    WHERE room_id = ? AND date = ? AND status != 'cancelled'
    AND start_time < ? AND end_time > ?
  `).get(room_id, date, end_time, start_time);

  if (conflict) {
    req.session.error = 'Ce créneau est déjà réservé pour cette salle.';
    return res.redirect(`/reservations/new?room_id=${room_id}&date=${date}`);
  }

  // Check capacity
  const room = db.prepare('SELECT * FROM rooms WHERE id = ?').get(room_id);
  if (!room) {
    req.session.error = 'Salle introuvable.';
    return res.redirect('/reservations/new');
  }
  if (attendees && parseInt(attendees) > room.capacity) {
    req.session.error = `Cette salle n'accepte que ${room.capacity} personnes maximum.`;
    return res.redirect(`/reservations/new?room_id=${room_id}`);
  }

  const result = db.prepare(`
    INSERT INTO reservations (user_id, room_id, title, date, start_time, end_time, attendees, notes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(req.session.user.id, room_id, title, date, start_time, end_time, attendees || 1, notes || '');

  // Send confirmation email (non-blocking)
  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(req.session.user.id);
  sendConfirmationEmail({
    to: user.email,
    userName: user.name,
    roomName: room.name,
    date,
    startTime: start_time,
    endTime: end_time,
    title,
  });

  req.session.success = `Réservation confirmée ! Un email de confirmation a été envoyé à ${user.email}.`;
  res.redirect('/reservations');
});

// Edit form
router.get('/:id/edit', requireAuth, (req, res) => {
  const reservation = db.prepare(`
    SELECT res.*, r.name as room_name, r.capacity as room_capacity
    FROM reservations res JOIN rooms r ON res.room_id = r.id
    WHERE res.id = ? AND res.user_id = ?
  `).get(req.params.id, req.session.user.id);

  if (!reservation) {
    req.session.error = 'Réservation introuvable.';
    return res.redirect('/reservations');
  }

  const rooms = db.prepare('SELECT * FROM rooms ORDER BY name').all();
  res.render('reservations/edit', { reservation, rooms });
});

// Update reservation
router.put('/:id', requireAuth, (req, res) => {
  const { title, date, start_time, end_time, attendees, notes } = req.body;
  const reservation = db.prepare('SELECT * FROM reservations WHERE id = ? AND user_id = ?').get(req.params.id, req.session.user.id);

  if (!reservation) {
    req.session.error = 'Réservation introuvable.';
    return res.redirect('/reservations');
  }

  if (start_time >= end_time) {
    req.session.error = 'L\'heure de fin doit être après l\'heure de début.';
    return res.redirect(`/reservations/${req.params.id}/edit`);
  }

  // Check conflicts (excluding current reservation)
  const conflict = db.prepare(`
    SELECT id FROM reservations
    WHERE room_id = ? AND date = ? AND status != 'cancelled'
    AND id != ? AND start_time < ? AND end_time > ?
  `).get(reservation.room_id, date, reservation.id, end_time, start_time);

  if (conflict) {
    req.session.error = 'Ce créneau est déjà réservé pour cette salle.';
    return res.redirect(`/reservations/${req.params.id}/edit`);
  }

  db.prepare(`
    UPDATE reservations SET title=?, date=?, start_time=?, end_time=?, attendees=?, notes=?
    WHERE id = ? AND user_id = ?
  `).run(title, date, start_time, end_time, attendees || 1, notes || '', req.params.id, req.session.user.id);

  req.session.success = 'Réservation modifiée avec succès.';
  res.redirect('/reservations');
});

// Cancel reservation
router.delete('/:id', requireAuth, (req, res) => {
  const reservation = db.prepare('SELECT * FROM reservations WHERE id = ? AND user_id = ?').get(req.params.id, req.session.user.id);
  if (!reservation) {
    req.session.error = 'Réservation introuvable.';
    return res.redirect('/reservations');
  }
  db.prepare('UPDATE reservations SET status = ? WHERE id = ?').run('cancelled', req.params.id);
  req.session.success = 'Réservation annulée.';
  res.redirect('/reservations');
});

module.exports = router;
