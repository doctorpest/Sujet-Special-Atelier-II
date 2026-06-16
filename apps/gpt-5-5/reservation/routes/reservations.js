const express = require('express');
const { all, get, run } = require('./db');
const { requireAuth, flash } = require('./middleware');
const { sendReservationConfirmation } = require('./mailer');
const router = express.Router();

function toSqlDateTime(value) {
  return String(value || '').replace('T', ' ');
}

async function roomIsAvailable(roomId, start, end, ignoredReservationId = null) {
  const params = [roomId, end, start];
  let sql = `SELECT id FROM reservations
             WHERE room_id = ? AND datetime(start_time) < datetime(?) AND datetime(end_time) > datetime(?)`;
  if (ignoredReservationId) {
    sql += ' AND id != ?';
    params.push(ignoredReservationId);
  }
  return !(await get(sql, params));
}

router.get('/', requireAuth, async (req, res, next) => {
  try {
    const reservations = await all(`
      SELECT r.*, rooms.name AS room_name, rooms.photo AS room_photo, rooms.capacity
      FROM reservations r
      JOIN rooms ON rooms.id = r.room_id
      WHERE r.user_id = ?
      ORDER BY datetime(r.start_time) DESC
    `, [req.session.user.id]);
    res.render('reservations/index', { reservations });
  } catch (err) { next(err); }
});

router.get('/new', requireAuth, async (req, res, next) => {
  try {
    const rooms = await all('SELECT * FROM rooms ORDER BY name ASC');
    res.render('reservations/new', { rooms, selectedRoomId: req.query.room_id || '' });
  } catch (err) { next(err); }
});

router.post('/', requireAuth, async (req, res, next) => {
  try {
    const { room_id, title } = req.body;
    const start = toSqlDateTime(req.body.start_time);
    const end = toSqlDateTime(req.body.end_time);
    if (!room_id || !title || !start || !end || new Date(end) <= new Date(start)) {
      flash(req, 'danger', 'Veuillez fournir une salle, un titre, un début et une fin valides.');
      return res.redirect('/reservations/new');
    }
    if (!(await roomIsAvailable(room_id, start, end))) {
      flash(req, 'danger', 'Cette salle est déjà réservée sur ce créneau.');
      return res.redirect('/reservations/new');
    }
    const result = await run(
      'INSERT INTO reservations (user_id, room_id, start_time, end_time, title) VALUES (?, ?, ?, ?, ?)',
      [req.session.user.id, room_id, start, end, title.trim()]
    );
    const reservation = await get('SELECT * FROM reservations WHERE id = ?', [result.lastID]);
    const room = await get('SELECT * FROM rooms WHERE id = ?', [room_id]);
    await sendReservationConfirmation(req.session.user, reservation, room);
    flash(req, 'success', 'Réservation créée. Un email de confirmation a été envoyé ou simulé en console.');
    res.redirect('/reservations');
  } catch (err) { next(err); }
});

router.get('/:id/edit', requireAuth, async (req, res, next) => {
  try {
    const reservation = await get('SELECT * FROM reservations WHERE id = ? AND user_id = ?', [req.params.id, req.session.user.id]);
    if (!reservation) return res.status(404).render('error', { message: 'Réservation introuvable.' });
    const rooms = await all('SELECT * FROM rooms ORDER BY name ASC');
    res.render('reservations/edit', { reservation, rooms });
  } catch (err) { next(err); }
});

router.put('/:id', requireAuth, async (req, res, next) => {
  try {
    const reservation = await get('SELECT * FROM reservations WHERE id = ? AND user_id = ?', [req.params.id, req.session.user.id]);
    if (!reservation) return res.status(404).render('error', { message: 'Réservation introuvable.' });
    const start = toSqlDateTime(req.body.start_time);
    const end = toSqlDateTime(req.body.end_time);
    if (new Date(end) <= new Date(start)) {
      flash(req, 'danger', 'Le créneau est invalide.');
      return res.redirect(`/reservations/${req.params.id}/edit`);
    }
    if (!(await roomIsAvailable(req.body.room_id, start, end, req.params.id))) {
      flash(req, 'danger', 'Cette salle est indisponible sur ce créneau.');
      return res.redirect(`/reservations/${req.params.id}/edit`);
    }
    await run('UPDATE reservations SET room_id = ?, title = ?, start_time = ?, end_time = ? WHERE id = ? AND user_id = ?',
      [req.body.room_id, req.body.title.trim(), start, end, req.params.id, req.session.user.id]);
    flash(req, 'success', 'Réservation modifiée.');
    res.redirect('/reservations');
  } catch (err) { next(err); }
});

router.delete('/:id', requireAuth, async (req, res, next) => {
  try {
    await run('DELETE FROM reservations WHERE id = ? AND user_id = ?', [req.params.id, req.session.user.id]);
    flash(req, 'success', 'Réservation annulée.');
    res.redirect('/reservations');
  } catch (err) { next(err); }
});

module.exports = router;
