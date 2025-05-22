<?php
include '../includes/config.php';

session_start();
$phrase="";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);

    // Vérification des identifiants
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['user_id'];
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

   // Redirection vers dashboard.php avec le rôle dans l'URL
        header("Location: ../pages/dashboard.php" );
        exit();
    } else {
        $phrase = "Email/Mot de passe incorrect !";
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

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Connexion - AGC Archives</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link rel="stylesheet" href="">
    <style>
        .wave {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: #3b82f6;
            border-bottom-left-radius: 50% 100px;
            border-bottom-right-radius: 50% 100px;
            z-index: 0;
            overflow: hidden;
        }
        .input-field:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
        }
        .error-message {
            animation: slideIn 0.3s ease-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .social-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                padding: 1rem;
            }
            .login-card {
                width: 100%;
            }
            .wave { height: 150px; border-bottom-left-radius: 50% 80px; border-bottom-right-radius: 50% 80px; }
        }
        .input-icon {
            position: relative;
        }
        .input-icon ion-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
        }
        .input-icon input {
            padding-left: 40px;
        }
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: #6b7280;
            cursor: pointer;
        }
    </style>
</head>
<body class="relative flex bg-gray-50">
    <!-- Wave Background -->
    <div class="wave"></div>

    <!-- Background Image with Overlay -->
    <div class="absolute inset-0 bg-center bg-cover" style="background-image: url('../image/image.webp'); opacity: 0.1;"></div>

    <!-- Login Container -->
    <div class="flex items-center justify-center w-full h-screen ">
        <div class="relative z-10 flex w-[50%] p-2 bg-white login-container">
            <!-- Image Container -->
            <div class="hidden object-cover w-1/2 bg-center bg-cover rounded-md md:block" style="background-image: url('../image/famille.webp');">
                <div class="flex flex-col justify-center w-full h-full p-6 text-center text-white bg-black bg-opacity-[2%]">
                    <h2 class="text-3xl font-bold">Vos archives, protégées avec soin</h2>
                    <p class="mt-2 text-gray-300">Rejoignez-nous et accédez à nos services exclusifs.</p>
                </div>
            </div>

            <!-- Login Card -->
            <div class="w-1/2 p-6 space-y-6 shadow-2xl login-card rounded-xl backdrop-blur-sm">
                <!-- Logo or Icon -->
                <div class="flex justify-center mb-4">
                    <img src="../image/logo.jpg" alt="error" class="w-10 h-10">
                </div>

                <h2 class="text-3xl font-bold text-center text-gray-800">Connexion à AGC</h2>

                <!-- Tabs -->
                <div class="flex justify-between p-1 text-sm bg-gray-200 rounded-full">
                    <button class="w-1/2 py-2 font-semibold text-gray-700 bg-white rounded-full w-full">Login</button>
                    
                </div>
<!-- Affichage du message d'erreur -->
                <?php if (!empty($phrase)): ?>
                    <div class="flex w-full justify-center">
                        <div class="w-[85%] bg-red-200 text-red-500 border border-red-500 rounded-sm p-2 text-center">
                            <?php echo htmlspecialchars($phrase); ?>
                        </div>
                    </div>
                <?php endif; ?>
               

                <!-- Login Form -->
                <form method="POST" action="index.php" class="space-y-4" id="login-form">
                    <div class="relative input-icon">
                        <ion-icon name="mail-outline" class="absolute right-0"></ion-icon>
                        <input type="email" name="email" id="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Email" >
                    </div>
                    <div class="relative input-icon" >
                        
                        <input type="password" name="password" id="password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg input-field" placeholder="Mot de passe">
                        <ion-icon name="eye-outline" id="toggle-password" class="absolute right-0 password-toggle"></ion-icon>
                    </div>

                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="mr-1 text-blue-600 border-gray-300 rounded focus:ring-blue-600" >
                            <span class="text-gray-600">Se souvenir de moi</span>
                        </label>
                        <a href="#" class="text-blue-600 hover:underline">Mot de passe oublié ?</a>
                    </div>

                    <button type="submit" class="w-full py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">Login</button>
                </form>

                <!-- Divider -->
                <div class="text-sm text-center text-gray-400">ou se connecter avec</div>

                <!-- Social Buttons -->
                <div class="flex justify-center space-x-4">
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Google" >
                        <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" alt="Google" class="w-4 h-4 mr-2">
                        Google
                    </button>
                    <button class="flex items-center px-4 py-2 text-sm bg-white border border-gray-300 rounded-lg shadow social-btn hover:shadow-md" data-provider="Facebook" >
                        <img src="https://cdn-icons-png.flaticon.com/512/733/733547.png" alt="Facebook" class="w-4 h-4 mr-2">
                        Facebook
                    </button>
                </div>

                
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>