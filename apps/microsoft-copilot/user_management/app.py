import os
import sqlite3
from datetime import datetime

from flask import (
    Flask, render_template, request, redirect,
    url_for, session, flash, g
)
from werkzeug.security import generate_password_hash, check_password_hash
from itsdangerous import URLSafeTimedSerializer, BadSignature, SignatureExpired
import smtplib
from email.message import EmailMessage

BASE_DIR = os.path.abspath(os.path.dirname(__file__))
DB_PATH = os.path.join(BASE_DIR, "users.db")

app = Flask(__name__)
app.config["SECRET_KEY"] = "change-this-secret-key"
app.config["SECURITY_PASSWORD_SALT"] = "change-this-salt"

# Email configuration (adapt to your SMTP server)
app.config["MAIL_SERVER"] = "smtp.example.com"
app.config["MAIL_PORT"] = 587
app.config["MAIL_USE_TLS"] = True
app.config["MAIL_USERNAME"] = "no-reply@example.com"
app.config["MAIL_PASSWORD"] = "your-email-password"
app.config["MAIL_DEFAULT_SENDER"] = "no-reply@example.com"


def get_db():
    if "db" not in g:
        g.db = sqlite3.connect(DB_PATH)
        g.db.row_factory = sqlite3.Row
    return g.db


@app.teardown_appcontext
def close_db(exception):
    db = g.pop("db", None)
    if db is not None:
        db.close()


def init_db():
    if not os.path.exists(DB_PATH):
        with app.app_context():
            db = get_db()
            with open(os.path.join(BASE_DIR, "schema.sql"), "r", encoding="utf-8") as f:
                db.executescript(f.read())
            # Create a default admin user
            password_hash = generate_password_hash("admin123")
            db.execute(
                "INSERT INTO users (email, password_hash, full_name, role, created_at) "
                "VALUES (?, ?, ?, ?, ?)",
                ("admin@example.com", password_hash, "Admin User", "admin", datetime.utcnow()),
            )
            db.commit()


def generate_token(email):
    s = URLSafeTimedSerializer(app.config["SECRET_KEY"])
    return s.dumps(email, salt=app.config["SECURITY_PASSWORD_SALT"])


def confirm_token(token, expiration=3600):
    s = URLSafeTimedSerializer(app.config["SECRET_KEY"])
    try:
        email = s.loads(
            token,
            salt=app.config["SECURITY_PASSWORD_SALT"],
            max_age=expiration,
        )
    except (SignatureExpired, BadSignature):
        return None
    return email


def send_reset_email(to_email, token):
    reset_url = url_for("reset_password_token", token=token, _external=True)
    subject = "Réinitialisation de votre mot de passe"
    body = f"""Bonjour,

Vous avez demandé la réinitialisation de votre mot de passe.

Cliquez sur le lien suivant pour définir un nouveau mot de passe (valide 1 heure) :
{reset_url}

Si vous n'êtes pas à l'origine de cette demande, ignorez simplement cet email.

Cordialement,
L'équipe de l'application
"""

    msg = EmailMessage()
    msg["Subject"] = subject
    msg["From"] = app.config["MAIL_DEFAULT_SENDER"]
    msg["To"] = to_email
    msg.set_content(body)

    with smtplib.SMTP(app.config["MAIL_SERVER"], app.config["MAIL_PORT"]) as server:
        if app.config.get("MAIL_USE_TLS"):
            server.starttls()
        if app.config.get("MAIL_USERNAME"):
            server.login(app.config["MAIL_USERNAME"], app.config["MAIL_PASSWORD"])
        server.send_message(msg)


def current_user():
    if "user_id" not in session:
        return None
    db = get_db()
    user = db.execute(
        "SELECT * FROM users WHERE id = ?", (session["user_id"],)
    ).fetchone()
    return user


def login_required(view):
    from functools import wraps

    @wraps(view)
    def wrapped(*args, **kwargs):
        if current_user() is None:
            flash("Vous devez être connecté.", "warning")
            return redirect(url_for("login"))
        return view(*args, **kwargs)

    return wrapped


def admin_required(view):
    from functools import wraps

    @wraps(view)
    def wrapped(*args, **kwargs):
        user = current_user()
        if user is None or user["role"] != "admin":
            flash("Accès réservé aux administrateurs.", "danger")
            return redirect(url_for("index"))
        return view(*args, **kwargs)

    return wrapped


@app.context_processor
def inject_user():
    return {"current_user": current_user()}


@app.route("/")
def index():
    return render_template("index.html")


@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")
        full_name = request.form.get("full_name", "").strip()
        role = request.form.get("role", "user")

        if not email or not password or not full_name:
            flash("Tous les champs sont obligatoires.", "danger")
            return redirect(url_for("register"))

        db = get_db()
        existing = db.execute(
            "SELECT id FROM users WHERE email = ?", (email,)
        ).fetchone()
        if existing:
            flash("Cet email est déjà utilisé.", "danger")
            return redirect(url_for("register"))

        password_hash = generate_password_hash(password)
        db.execute(
            "INSERT INTO users (email, password_hash, full_name, role, created_at) "
            "VALUES (?, ?, ?, ?, ?)",
            (email, password_hash, full_name, role, datetime.utcnow()),
        )
        db.commit()
        flash("Inscription réussie, vous pouvez vous connecter.", "success")
        return redirect(url_for("login"))

    return render_template("register.html")


@app.route("/login", methods=["GET", "POST"])
def login():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        password = request.form.get("password", "")

        db = get_db()
        user = db.execute(
            "SELECT * FROM users WHERE email = ?", (email,)
        ).fetchone()

        if user and check_password_hash(user["password_hash"], password):
            session["user_id"] = user["id"]
            flash("Connexion réussie.", "success")
            return redirect(url_for("profile"))
        else:
            flash("Identifiants invalides.", "danger")
            return redirect(url_for("login"))

    return render_template("login.html")


@app.route("/logout")
def logout():
    session.clear()
    flash("Vous êtes déconnecté.", "info")
    return redirect(url_for("index"))


@app.route("/profile")
@login_required
def profile():
    user = current_user()
    return render_template("profile.html", user=user)


@app.route("/admin")
@admin_required
def admin():
    db = get_db()
    users = db.execute(
        "SELECT id, email, full_name, role, created_at FROM users ORDER BY id"
    ).fetchall()
    return render_template("admin.html", users=users)


@app.route("/admin/user/<int:user_id>/edit", methods=["GET", "POST"])
@admin_required
def edit_user(user_id):
    db = get_db()
    user = db.execute(
        "SELECT id, email, full_name, role FROM users WHERE id = ?", (user_id,)
    ).fetchone()
    if not user:
        flash("Utilisateur introuvable.", "danger")
        return redirect(url_for("admin"))

    if request.method == "POST":
        role = request.form.get("role", "user")
        db.execute(
            "UPDATE users SET role = ? WHERE id = ?", (role, user_id)
        )
        db.commit()
        flash("Rôle mis à jour.", "success")
        return redirect(url_for("admin"))

    return render_template("edit_user.html", user=user)


@app.route("/admin/user/<int:user_id>/delete", methods=["POST"])
@admin_required
def delete_user(user_id):
    db = get_db()
    db.execute("DELETE FROM users WHERE id = ?", (user_id,))
    db.commit()
    flash("Utilisateur supprimé.", "info")
    return redirect(url_for("admin"))


@app.route("/reset_password", methods=["GET", "POST"])
def reset_password_request():
    if request.method == "POST":
        email = request.form.get("email", "").strip().lower()
        if not email:
            flash("Veuillez saisir un email.", "danger")
            return redirect(url_for("reset_password_request"))

        db = get_db()
        user = db.execute(
            "SELECT id FROM users WHERE email = ?", (email,)
        ).fetchone()
        if user:
            token = generate_token(email)
            try:
                send_reset_email(email, token)
                flash("Un email de réinitialisation a été envoyé.", "info")
            except Exception as e:
                flash("Erreur lors de l'envoi de l'email. Vérifiez la configuration SMTP.", "danger")
        else:
            flash("Si un compte existe avec cet email, un lien a été envoyé.", "info")

        return redirect(url_for("login"))

    return render_template("reset_request.html")


@app.route("/reset/<token>", methods=["GET", "POST"])
def reset_password_token(token):
    email = confirm_token(token)
    if not email:
        flash("Lien invalide ou expiré.", "danger")
        return redirect(url_for("reset_password_request"))

    if request.method == "POST":
        password = request.form.get("password", "")
        confirm = request.form.get("confirm", "")
        if not password or password != confirm:
            flash("Les mots de passe ne correspondent pas.", "danger")
            return redirect(url_for("reset_password_token", token=token))

        db = get_db()
        password_hash = generate_password_hash(password)
        db.execute(
            "UPDATE users SET password_hash = ? WHERE email = ?",
            (password_hash, email),
        )
        db.commit()
        flash("Mot de passe mis à jour, vous pouvez vous connecter.", "success")
        return redirect(url_for("login"))

    return render_template("reset_token.html", token=token)


if __name__ == "__main__":
    init_db()
    app.run(debug=True)
