document.getElementById('toggle-password').addEventListener('click', function (e) {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.setAttribute('name', type === 'password' ? 'eye-outline' : 'eye-off-outline');
});

document.querySelector('a[href="#"]').addEventListener('click', function (e) {
    e.preventDefault();
    const email = document.getElementById('email').value;
    if (email) {
        Swal.fire({
            title: 'Mot de passe oublié ?',
            text: 'Un lien de réinitialisation vous sera envoyé si l\'email existe.',
            input: 'text',
            inputValue: email,
            showCancelButton: true,
            confirmButtonText: 'Envoyer',
            cancelButtonText: 'Annuler',
            preConfirm: (value) => {
                if (!value) {
                    Swal.showValidationMessage('Veuillez entrer un email.');
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `login.php?forgot=true&email=${encodeURIComponent(result.value)}`;
            }
        });
    } else {
        Swal.fire('Erreur', 'Veuillez entrer un email d\'abord.', 'error');
    }
});

document.getElementById('login-form').addEventListener('submit', function (e) {
    // Ajouter une validation côté client si nécessaire
});