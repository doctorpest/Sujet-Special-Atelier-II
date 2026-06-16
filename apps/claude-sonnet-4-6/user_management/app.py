import os
import sqlite3
import secrets
import hashlib
from datetime import datetime, timedelta
from functools import wraps
from flask import (
    Flask, render_template, request, redirect, url_for,
    session, flash, g, abort
)

app = Flask(__name__)
app.secret_key = os.environ.get('SECRET_KEY', secrets.token_hex(32))

DATABASE = 'users.db'

# ─── Database helpers ──────────────────────────────────────────────────────────

def get_db():
    db = getattr(g, '_database', None)
    if db is None:
        db = g._database = sqlite3.connect(DATABASE)
        db.row_factory = sqlite3.Row
    return db

@app.teardown_appcontext
def close_connection(exception):
    db = getattr(g, '_database', None)
    if db is not None:
        db.close()

def init_db():
    with app.app_context():
        db = get_db()
        db.executescript(open('schema.sql').read())
        db.commit()
        # Create default admin if not exists
        existing = db.execute("SELECT id FROM users WHERE email = 'admin@example.com'").fetchone()
        if not existing:
            db.execute(
                "INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)",
                ('admin@example.com', hash_password('Admin1234!'), 'Administrateur', 'admin')
            )
            db.commit()

def hash_password(password: str) -> str:
    return hashlib.sha256(password.encode()).hexdigest()

# ─── Auth decorators ───────────────────────────────────────────────────────────

def login_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'user_id' not in session:
            flash('Vous devez être connecté pour accéder à cette page.', 'warning')
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated

def admin_required(f):
    @wraps(f)
    def decorated(*args, **kwargs):
        if 'user_id' not in session:
            flash('Vous devez être connecté.', 'warning')
            return redirect(url_for('login'))
        db = get_db()
        user = db.execute("SELECT role FROM users WHERE id = ?", (session['user_id'],)).fetchone()
        if not user or user['role'] != 'admin':
            abort(403)
        return f(*args, **kwargs)
    return decorated

# ─── Context processor ─────────────────────────────────────────────────────────

@app.context_processor
def inject_user():
    current_user = None
    if 'user_id' in session:
        current_user = get_db().execute(
            "SELECT * FROM users WHERE id = ?", (session['user_id'],)
        ).fetchone()
    return dict(current_user=current_user)

# ─── Routes ────────────────────────────────────────────────────────────────────

@app.route('/')
def index():
    if 'user_id' in session:
        return redirect(url_for('profile'))
    return redirect(url_for('login'))

# --- Auth ---

@app.route('/register', methods=['GET', 'POST'])
def register():
    if 'user_id' in session:
        return redirect(url_for('profile'))
    if request.method == 'POST':
        email     = request.form['email'].strip().lower()
        full_name = request.form['full_name'].strip()
        password  = request.form['password']
        confirm   = request.form['confirm_password']
        role      = request.form.get('role', 'user')

        errors = []
        if not email or '@' not in email:
            errors.append('Adresse email invalide.')
        if not full_name:
            errors.append('Le nom complet est requis.')
        if len(password) < 8:
            errors.append('Le mot de passe doit contenir au moins 8 caractères.')
        if password != confirm:
            errors.append('Les mots de passe ne correspondent pas.')
        if role not in ('user', 'admin', 'moderator'):
            role = 'user'

        db = get_db()
        existing = db.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()
        if existing:
            errors.append('Cette adresse email est déjà utilisée.')

        if errors:
            for e in errors:
                flash(e, 'danger')
            return render_template('register.html', email=email, full_name=full_name)

        db.execute(
            "INSERT INTO users (email, password_hash, full_name, role) VALUES (?, ?, ?, ?)",
            (email, hash_password(password), full_name, role)
        )
        db.commit()
        flash('Compte créé avec succès ! Vous pouvez vous connecter.', 'success')
        return redirect(url_for('login'))

    return render_template('register.html')


@app.route('/login', methods=['GET', 'POST'])
def login():
    if 'user_id' in session:
        return redirect(url_for('profile'))
    if request.method == 'POST':
        email    = request.form['email'].strip().lower()
        password = request.form['password']
        db = get_db()
        user = db.execute(
            "SELECT * FROM users WHERE email = ? AND password_hash = ?",
            (email, hash_password(password))
        ).fetchone()
        if user:
            session.permanent = True
            app.permanent_session_lifetime = timedelta(hours=8)
            session['user_id'] = user['id']
            flash(f'Bienvenue, {user["full_name"]} !', 'success')
            return redirect(url_for('profile'))
        flash('Email ou mot de passe incorrect.', 'danger')
    return render_template('login.html')


@app.route('/logout')
@login_required
def logout():
    session.clear()
    flash('Vous avez été déconnecté.', 'info')
    return redirect(url_for('login'))


# --- Profile ---

@app.route('/profile')
@login_required
def profile():
    db = get_db()
    user = db.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],)).fetchone()
    return render_template('profile.html', user=user)


@app.route('/profile/edit', methods=['GET', 'POST'])
@login_required
def edit_profile():
    db = get_db()
    user = db.execute("SELECT * FROM users WHERE id = ?", (session['user_id'],)).fetchone()
    if request.method == 'POST':
        full_name = request.form['full_name'].strip()
        if not full_name:
            flash('Le nom complet est requis.', 'danger')
            return render_template('edit_profile.html', user=user)
        db.execute("UPDATE users SET full_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                   (full_name, session['user_id']))
        db.commit()
        flash('Profil mis à jour.', 'success')
        return redirect(url_for('profile'))
    return render_template('edit_profile.html', user=user)


# --- Password reset (simulated via token, no real email) ---

@app.route('/forgot-password', methods=['GET', 'POST'])
def forgot_password():
    if request.method == 'POST':
        email = request.form['email'].strip().lower()
        db = get_db()
        user = db.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()
        if user:
            token = secrets.token_urlsafe(32)
            expires = (datetime.utcnow() + timedelta(hours=1)).strftime('%Y-%m-%d %H:%M:%S')
            db.execute("DELETE FROM password_resets WHERE user_id = ?", (user['id'],))
            db.execute(
                "INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)",
                (user['id'], token, expires)
            )
            db.commit()
            # In production you'd send an email; here we show the link directly
            reset_url = url_for('reset_password', token=token, _external=True)
            flash(f'Lien de réinitialisation (simulation – normalement envoyé par email) : '
                  f'<a href="{reset_url}">{reset_url}</a>', 'info')
        else:
            flash('Si cet email existe, un lien vous sera envoyé.', 'info')
    return render_template('forgot_password.html')


@app.route('/reset-password/<token>', methods=['GET', 'POST'])
def reset_password(token):
    db = get_db()
    record = db.execute(
        "SELECT pr.*, u.email FROM password_resets pr JOIN users u ON pr.user_id = u.id "
        "WHERE pr.token = ? AND pr.expires_at > CURRENT_TIMESTAMP",
        (token,)
    ).fetchone()
    if not record:
        flash('Lien invalide ou expiré.', 'danger')
        return redirect(url_for('forgot_password'))

    if request.method == 'POST':
        password = request.form['password']
        confirm  = request.form['confirm_password']
        if len(password) < 8:
            flash('Le mot de passe doit contenir au moins 8 caractères.', 'danger')
            return render_template('reset_password.html', token=token, email=record['email'])
        if password != confirm:
            flash('Les mots de passe ne correspondent pas.', 'danger')
            return render_template('reset_password.html', token=token, email=record['email'])
        db.execute("UPDATE users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                   (hash_password(password), record['user_id']))
        db.execute("DELETE FROM password_resets WHERE token = ?", (token,))
        db.commit()
        flash('Mot de passe mis à jour. Connectez-vous.', 'success')
        return redirect(url_for('login'))

    return render_template('reset_password.html', token=token, email=record['email'])


# --- Admin ---

@app.route('/admin')
@admin_required
def admin_users():
    db = get_db()
    users = db.execute("SELECT * FROM users ORDER BY created_at DESC").fetchall()
    return render_template('admin_users.html', users=users)


@app.route('/admin/user/<int:user_id>/role', methods=['POST'])
@admin_required
def change_role(user_id):
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas modifier votre propre rôle.', 'warning')
        return redirect(url_for('admin_users'))
    new_role = request.form.get('role')
    if new_role not in ('user', 'admin', 'moderator'):
        flash('Rôle invalide.', 'danger')
        return redirect(url_for('admin_users'))
    db = get_db()
    db.execute("UPDATE users SET role = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
               (new_role, user_id))
    db.commit()
    flash('Rôle mis à jour.', 'success')
    return redirect(url_for('admin_users'))


@app.route('/admin/user/<int:user_id>/delete', methods=['POST'])
@admin_required
def delete_user(user_id):
    if user_id == session['user_id']:
        flash('Vous ne pouvez pas supprimer votre propre compte.', 'warning')
        return redirect(url_for('admin_users'))
    db = get_db()
    db.execute("DELETE FROM users WHERE id = ?", (user_id,))
    db.execute("DELETE FROM password_resets WHERE user_id = ?", (user_id,))
    db.commit()
    flash('Utilisateur supprimé.', 'success')
    return redirect(url_for('admin_users'))


# ─── Error handlers ────────────────────────────────────────────────────────────

@app.errorhandler(403)
def forbidden(e):
    return render_template('error.html', code=403,
                           message="Accès interdit. Vous n'avez pas les droits nécessaires."), 403

@app.errorhandler(404)
def not_found(e):
    return render_template('error.html', code=404,
                           message="Page introuvable."), 404


# ─── Entry point ───────────────────────────────────────────────────────────────

if __name__ == '__main__':
    if not os.path.exists(DATABASE):
        init_db()
    else:
        # Ensure schema is up to date
        with app.app_context():
            db = get_db()
            db.executescript(open('schema.sql').read())
            db.commit()
    app.run(debug=True, port=5000)
