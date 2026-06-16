// Ajouter au panier depuis la page produit
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du panier
    const addToCartForms = document.querySelectorAll('.add-to-cart-form');
    addToCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const productId = formData.get('product_id');
            const quantity = formData.get('quantity');

            fetch('cart.php?action=add', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Mettre à jour le compteur du panier
                    updateCartCount();
                    // Afficher un message
                    showMessage(data.message, 'success');
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Une erreur est survenue.', 'error');
            });
        });
    });

    // Mettre à jour la quantité dans le panier
    const updateQuantityForms = document.querySelectorAll('.update-quantity-form');
    updateQuantityForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const productId = formData.get('product_id');
            const quantity = formData.get('quantity');

            fetch('cart.php?action=update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recharger la page pour mettre à jour le panier
                    location.reload();
                } else {
                    showMessage(data.message, 'error');
                }
            })
            .catch(error => {
                showMessage('Une erreur est survenue.', 'error');
            });
        });
    });

    // Supprimer du panier
    const removeFromCartForms = document.querySelectorAll('.remove-from-cart-form');
    removeFromCartForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const productId = formData.get('product_id');

            if (confirm('Voulez-vous vraiment retirer ce produit du panier ?')) {
                fetch('cart.php?action=remove', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Recharger la page pour mettre à jour le panier
                        location.reload();
                    } else {
                        showMessage(data.message, 'error');
                    }
                })
                .catch(error => {
                    showMessage('Une erreur est survenue.', 'error');
                });
            }
        });
    });

    // Mettre à jour le compteur du panier
    function updateCartCount() {
        fetch('cart.php?action=count')
            .then(response => response.json())
            .then(data => {
                const cartCountElements = document.querySelectorAll('.cart-count');
                cartCountElements.forEach(el => {
                    el.textContent = data.count;
                });
            });
    }

    // Afficher un message
    function showMessage(message, type) {
        const messageContainer = document.createElement('div');
        messageContainer.className = `message ${type}`;
        messageContainer.textContent = message;

        // Supprimer les anciens messages
        const oldMessages = document.querySelectorAll('.message');
        oldMessages.forEach(msg => msg.remove());

        // Ajouter le nouveau message
        const container = document.querySelector('.container') || document.body;
        container.prepend(messageContainer);

        // Supprimer le message après 5 secondes
        setTimeout(() => {
            messageContainer.remove();
        }, 5000);
    }

    // Initialiser le compteur du panier
    updateCartCount();

    // Gestion des onglets admin
    const adminTabLinks = document.querySelectorAll('.admin-sidebar a');
    adminTabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Retirer la classe active de tous les liens
            adminTabLinks.forEach(l => l.classList.remove('active'));
            // Ajouter la classe active au lien cliqué
            this.classList.add('active');
        });
    });

    // Validation des formulaires
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            let isValid = true;

            // Vérifier les champs requis
            const requiredFields = this.querySelectorAll('[required]');
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#e74c3c';
                } else {
                    field.style.borderColor = '#bdc3c7';
                }
            });

            if (!isValid) {
                e.preventDefault();
                showMessage('Veuillez remplir tous les champs obligatoires.', 'error');
            }
        });
    });

    // Aperçu de l'image avant téléchargement
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            const preview = this.parentNode.querySelector('.image-preview');
            if (preview && this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
});