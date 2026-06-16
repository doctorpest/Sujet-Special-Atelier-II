const express = require('express');
const db = require('../db');

const router = express.Router();

// Middleware d’auth local
function ensureAuth(req, res, next) {
  if (!req.session.user) {
    req.session.error = 'Vous devez être connecté.';
    return res.redirect('/login');
  }
  next();
}

// Mes réservations
router.get('/', ensureAuth, (req, res) => {
  const userId = req.session.user.id;
  const query = `
    SELECT r.*, rooms.name AS room_name
    FROM reservations r
    JOIN rooms ON rooms.id = r.room_id
    WHERE r.user_id = ?
    ORDER BY date, start_time
  `;
  db.all(query, [userId], (err, reservations) => {
    if (err) {
      return res.status(500).send('Erreur serveur');
    }
    res.render('reservations', { reservations });
  });
});

// Formulaire nouvelle réservation
router.get('/new', ensureAuth, (req, res) => {
  const roomId = req.query.room_id || null;
  db.all('SELECT * FROM rooms', (err, rooms) => {
    if (err) return res.status(500).send('Erreur serveur');
    res.render('reservation_form', {
      reservation: null,
      rooms,
      selectedRoomId: roomId
    });
  });
});

// Création réservation
router.post('/', ensureAuth, (req, res) => {
  const { room_id, date, start_time, end_time } = req.body;
  const userId = req.session.user.id;

  if (!room_id || !date || !start_time || !end_time) {
    req.session.error = 'Tous les champs sont obligatoires.';
    return res.redirect('/reservations/new');
  }

  // Vérifier chevauchement
  const overlapQuery = `
    SELECT * FROM reservations
    WHERE room_id = ?
      AND date = ?
      AND NOT (end_time <= ? OR start_time >= ?)
  `;
  db.get(
    overlapQuery,
    [room_id, date, start_time, end_time],
    (err, existing) => {
      if (err) {
        req.session.error = 'Erreur serveur.';
        return res.redirect('/reservations/new');
      }
      if (existing) {
        req.session.error = 'Ce créneau est déjà réservé pour cette salle.';
        return res.redirect('/reservations/new?room_id=' + room_id);
      }

      db.run(
        `
        INSERT INTO reservations (user_id, room_id, date, start_time, end_time)
        VALUES (?, ?, ?, ?, ?)
      `,
        [userId, room_id, date, start_time, end_time],
        function (err2) {
          if (err2) {
            req.session.error = 'Erreur lors de la réservation.';
            return res.redirect('/reservations/new');
          }

          // Envoi email (si transport configuré)
          const reservationId = this.lastID;
          db.get('SELECT * FROM rooms WHERE id = ?', [room_id], (e3, room) => {
            const transporter = req.transporter;
            const user = req.session.user;
            const mailOptions = {
              from: process.env.MAIL_FROM || 'no-reply@example.com',
              to: user.email,
              subject: 'Confirmation de réservation de salle',
              text: `Bonjour ${user.name},

Votre réservation est confirmée.

Salle : ${room ? room.name : room_id}
Date : ${date}
Heure : ${start_time} - ${end_time}
Numéro de réservation : ${reservationId}

Merci.`
            };
            transporter.sendMail(mailOptions, errMail => {
              if (errMail) {
                console.log('Erreur envoi email:', errMail.message);
              }
            });
          });

          req.session.success = 'Réservation créée.';
          res.redirect('/reservations');
        }
      );
    }
  );
});

// Édition réservation
router.get('/:id/edit', ensureAuth, (req, res) => {
  const id = req.params.id;
  const userId = req.session.user.id;

  db.get(
    'SELECT * FROM reservations WHERE id = ? AND user_id = ?',
    [id, userId],
    (err, reservation) => {
      if (err || !reservation) {
        return res.status(404).send('Réservation introuvable');
      }
      db.all('SELECT * FROM rooms', (err2, rooms) => {
        if (err2) return res.status(500).send('Erreur serveur');
        res.render('reservation_form', {
          reservation,
          rooms,
          selectedRoomId: reservation.room_id
        });
      });
    }
  );
});

router.post('/:id/edit', ensureAuth, (req, res) => {
  const id = req.params.id;
  const userId = req.session.user.id;
  const { room_id, date, start_time, end_time } = req.body;

  const overlapQuery = `
    SELECT * FROM reservations
    WHERE room_id = ?
      AND date = ?
      AND NOT (end_time <= ? OR start_time >= ?)
      AND id != ?
  `;
  db.get(
    overlapQuery,
    [room_id, date, start_time, end_time, id],
    (err, existing) => {
      if (err) {
        req.session.error = 'Erreur serveur.';
        return res.redirect('/reservations');
      }
      if (existing) {
        req.session.error = 'Ce créneau est déjà réservé pour cette salle.';
        return res.redirect('/reservations/' + id + '/edit');
      }

      db.run(
        `
        UPDATE reservations
        SET room_id = ?, date = ?, start_time = ?, end_time = ?
        WHERE id = ? AND user_id = ?
      `,
        [room_id, date, start_time, end_time, id, userId],
        err2 => {
          if (err2) {
            req.session.error = 'Erreur lors de la mise à jour.';
          } else {
            req.session.success = 'Réservation mise à jour.';
          }
          res.redirect('/reservations');
        }
      );
    }
  );
});

// Annulation
router.post('/:id/delete', ensureAuth, (req, res) => {
  const id = req.params.id;
  const userId = req.session.user.id;

  db.run(
    'DELETE FROM reservations WHERE id = ? AND user_id = ?',
    [id, userId],
    err => {
      if (err) {
        req.session.error = 'Erreur lors de la suppression.';
      } else {
        req.session.success = 'Réservation annulée.';
      }
      res.redirect('/reservations');
    }
  );
});

module.exports = router;
