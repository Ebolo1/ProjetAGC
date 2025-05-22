<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Accès interdit");
}

$users = [];
try {
    $stmt = $pdo->query("SELECT user_id, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
?>
<div class="animate-slide-in">
    <h2 class="text-3xl font-semibold text-black mb-6">Gestion des utilisateurs</h2>
    <div class="bg-white p-6 rounded-lg shadow-md">
        <h3 class="text-lg font-medium text-black mb-4 flex items-center"><ion-icon name="people-outline" class="mr-2"></ion-icon> Liste des utilisateurs</h3>
        <table id="usersTable" class="w-full text-left">
            <thead>
                <tr class="text-black">
                    <th class="p-2">Email</th>
                    <th class="p-2">Rôle</th>
                    <th class="p-2">Date de création</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr class="border-t hover:bg-gray-50 transition-colors">
                        <td class="p-2 text-black"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="p-2 text-black"><?php echo htmlspecialchars($user['role']); ?></td>
                        <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>