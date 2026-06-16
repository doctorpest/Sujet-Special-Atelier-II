function requireAuth(req, res, next) {
  if (!req.session.user) {
    req.session.flash = { type: 'warning', message: 'Connectez-vous pour continuer.' };
    return res.redirect('/login');
  }
  next();
}

function flash(req, type, message) {
  req.session.flash = { type, message };
}

module.exports = { requireAuth, flash };
