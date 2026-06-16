function requireAuth(req, res, next) {
  if (!req.session.user) {
    req.session.error = 'Veuillez vous connecter pour accéder à cette page.';
    return res.redirect('/login');
  }
  next();
}

module.exports = { requireAuth };
