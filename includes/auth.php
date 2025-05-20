<?php
include '../includes/config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Vérification des identifiants
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role']; // Ajout du rôle (assumé que la colonne 'role' existe avec valeurs 'admin' ou 'user')

        // Gestion "Se souvenir de moi"
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = time() + (30 * 24 * 3600); // 30 jours
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, date('Y-m-d H:i:s', $expiry), $user['id']]);
            setcookie('remember_me', $token, $expiry, '/', '', true, true);
        } else {
            setcookie('remember_me', '', time() - 3600, '/', '', true, true);
        }

        // Redirection selon le rôle
        $role = $user['role'];
        echo "<script>Swal.fire('Succès', 'Connexion réussie !', 'success').then(() => window.location.href = 'dashboard.php?role=" . urlencode($role) . "');</script>";
    } else {
        echo "<script>Swal.fire('Erreur', 'Email ou mot de passe incorrect.', 'error');</script>";
    }

    // Gestion "Mot de passe oublié"
    if (isset($_GET['forgot']) && $_GET['forgot'] === 'true') {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $reset_token = bin2hex(random_bytes(32));
            $expiry = time() + (3600); // 1 heure
            $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
            $stmt->execute([$reset_token, date('Y-m-d H:i:s', $expiry), $email]);

            // Simuler envoi email (à remplacer par une vraie fonction d'envoi)
            $reset_link = "http://localhost/reset.php?token=" . $reset_token;
            echo "<script>Swal.fire('Succès', 'Un lien de réinitialisation a été envoyé à votre email.', 'success');</script>";
        } else {
            echo "<script>Swal.fire('Erreur', 'Aucun compte trouvé avec cet email.', 'error');</script>";
        }
    }
}
?>