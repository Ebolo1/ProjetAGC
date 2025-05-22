<?php
session_start();
require_once "config.php";

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? 'user';
$search_results = [
    'documents' => [],
    'users' => [],
    'logs' => []
];
$categories = [];
$error_message = '';

if (!isset($_SESSION['user_id'])) {
    $error_message = "Vous devez être connecté pour effectuer une recherche.";
} else {
    try {
        // Récupérer les catégories pour les suggestions
        $stmt = $pdo->query("SELECT DISTINCT category FROM documents ORDER BY category");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search'])) {
            $query = filter_input(INPUT_POST, 'query', FILTER_SANITIZE_STRING);
            $category_filter = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
            $date_from = filter_input(INPUT_POST, 'date_from', FILTER_SANITIZE_STRING);
            $date_to = filter_input(INPUT_POST, 'date_to', FILTER_SANITIZE_STRING);
            $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING) ?: 'all';

            if (empty($query) && empty($category_filter) && empty($date_from) && empty($date_to)) {
                $error_message = "Veuillez entrer au moins un critère de recherche.";
            } else {
                $total_results = 0;

                // Recherche dans les documents
                if ($type === 'all' || $type === 'documents') {
                    $sql = "SELECT d.id, d.title, d.category, d.file_path, d.created_at, u.email 
                            FROM documents d 
                            JOIN users u ON d.user_id = u.user_id 
                            WHERE (d.title LIKE ? OR d.category LIKE ?)";
                    $params = ["%$query%", "%$query%"];

                    if ($role !== 'admin') {
                        $sql .= " AND d.user_id = ?";
                        $params[] = $user_id;
                    }
                    if (!empty($category_filter)) {
                        $sql .= " AND d.category = ?";
                        $params[] = $category_filter;
                    }
                    if (!empty($date_from)) {
                        $sql .= " AND d.created_at >= ?";
                        $params[] = $date_from;
                    }
                    if (!empty($date_to)) {
                        $sql .= " AND d.created_at <= ?";
                        $params[] = $date_to . ' 23:59:59';
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $search_results['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $total_results += count($search_results['documents']);
                }

                // Recherche dans les utilisateurs (admin uniquement)
                if (($type === 'all' || $type === 'users') && $role === 'admin') {
                    $sql = "SELECT user_id, email, role FROM users 
                            WHERE email LIKE ? OR role LIKE ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(["%$query%", "%$query%"]);
                    $search_results['users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $total_results += count($search_results['users']);
                }

                // Recherche dans les logs
                if ($type === 'all' || $type === 'logs') {
                    $sql = "SELECT l.id, l.user_id, l.action, l.details, l.created_at, u.email 
                            FROM logs l 
                            LEFT JOIN users u ON l.user_id = u.user_id 
                            WHERE l.action LIKE ? OR l.details LIKE ?";
                    $params = ["%$query%", "%$query%"];

                    if ($role !== 'admin') {
                        $sql .= " AND l.user_id = ?";
                        $params[] = $user_id;
                    }
                    if (!empty($date_from)) {
                        $sql .= " AND l.created_at >= ?";
                        $params[] = $date_from;
                    }
                    if (!empty($date_to)) {
                        $sql .= " AND l.created_at <= ?";
                        $params[] = $date_to . ' 23:59:59';
                    }

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $search_results['logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $total_results += count($search_results['logs']);
                }

                // Enregistrer la recherche dans la table search
                $stmt = $pdo->prepare("INSERT INTO search (user_id, query, results_count, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$user_id, $query ?: 'Filtres avancés', $total_results]);
            }
        }
    } catch (PDOException $e) {
        $error_message = "Erreur de base de données. Veuillez réessayer plus tard.";
        // Log l'erreur dans un fichier
        $log_dir = __DIR__ . '/../logs/';
        if (is_writable($log_dir)) {
            file_put_contents($log_dir . 'errors.log', date('Y-m-d H:i:s') . " [DB ERROR] " . $e->getMessage() . "\n", FILE_APPEND);
        }
    }
}
?>
<div class="animate-slide-in">
    <h2 class="mb-6 text-3xl font-semibold text-white">Recherche Avancée</h2>
    <div class="p-6 mb-8 bg-white rounded-lg shadow-md">
        <h3 class="flex items-center mb-4 text-lg font-medium text-gray-800"><ion-icon name="search-outline" class="mr-2"></ion-icon> Rechercher dans le site</h3>
        <form id="searchForm" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Recherche générale</label>
                    <input type="text" name="query" class="w-full p-2 mt-1 border rounded focus:ring-blue-500 focus:border-blue-500" placeholder="Titre, catégorie, email, action..." list="categories">
                    <datalist id="categories">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Catégorie (optionnel)</label>
                    <select name="category" class="w-full p-2 mt-1 border rounded focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Toutes</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date de début</label>
                    <input type="date" name="date_from" class="w-full p-2 mt-1 border rounded focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Date de fin</label>
                    <input type="date" name="date_to" class="w-full p-2 mt-1 border rounded focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type de contenu</label>
                    <select name="type" class="w-full p-2 mt-1 border rounded focus:ring-blue-500 focus:border-blue-500">
                        <option value="all">Tout</option>
                        <option value="documents">Documents</option>
                        <?php if ($role === 'admin'): ?>
                            <option value="users">Utilisateurs</option>
                        <?php endif; ?>
                        <option value="logs">Journaux</option>
                    </select>
                </div>
            </div>
            <button type="submit" name="search" class="px-6 py-2 text-white transition bg-blue-600 rounded-lg hover:bg-blue-700">Rechercher</button>
        </form>
    </div>

    <?php if ($error_message): ?>
        <div class="p-4 mb-6 text-red-700 bg-red-100 border-l-4 border-red-500 rounded">
            <p><?php echo htmlspecialchars($error_message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($search_results['documents']) || !empty($search_results['users']) || !empty($search_results['logs'])): ?>
        <!-- Résultats des documents -->
        <?php if (!empty($search_results['documents'])): ?>
            <div class="p-6 mb-6 bg-white rounded-lg shadow-md">
                <h3 class="flex items-center mb-4 text-lg font-medium text-gray-800"><ion-icon name="documents-outline" class="mr-2"></ion-icon> Documents (<?php echo count($search_results['documents']); ?>)</h3>
                <table id="documentsTable" class="w-full text-left">
                    <thead>
                        <tr class="text-gray-800">
                            <th class="p-2">Titre</th>
                            <th class="p-2">Catégorie</th>
                            <th class="p-2">Utilisateur</th>
                            <th class="p-2">Date</th>
                            <th class="p-2">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results['documents'] as $doc): ?>
                            <tr class="transition-colors border-t hover:bg-gray-50">
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($doc['email']); ?></td>
                                <td class="p-2 text-gray-800"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                                <td class="p-2">
                                    <a href="../includes/download.php?file=<?php echo urlencode($doc['file_path']); ?>" class="text-blue-600 hover:underline">
                                        <ion-icon name="download-outline"></ion-icon>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Résultats des utilisateurs (admin uniquement) -->
        <?php if (!empty($search_results['users']) && $role === 'admin'): ?>
            <div class="p-6 mb-6 bg-white rounded-lg shadow-md">
                <h3 class="flex items-center mb-4 text-lg font-medium text-gray-800"><ion-icon name="people-outline" class="mr-2"></ion-icon> Utilisateurs (<?php echo count($search_results['users']); ?>)</h3>
                <table id="usersTable" class="w-full text-left">
                    <thead>
                        <tr class="text-gray-800">
                            <th class="p-2">Email</th>
                            <th class="p-2">Rôle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results['users'] as $user): ?>
                            <tr class="transition-colors border-t hover:bg-gray-50">
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($user['role']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Résultats des journaux -->
        <?php if (!empty($search_results['logs'])): ?>
            <div class="p-6 bg-white rounded-lg shadow-md">
                <h3 class="flex items-center mb-4 text-lg font-medium text-gray-800"><ion-icon name="journal-outline" class="mr-2"></ion-icon> Journaux (<?php echo count($search_results['logs']); ?>)</h3>
                <table id="logsTable" class="w-full text-left">
                    <thead>
                        <tr class="text-gray-800">
                            <th class="p-2">Action</th>
                            <th class="p-2">Détails</th>
                            <th class="p-2">Utilisateur</th>
                            <th class="p-2">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results['logs'] as $log): ?>
                            <tr class="transition-colors border-t hover:bg-gray-50">
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($log['action']); ?></td>
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                                <td class="p-2 text-gray-800"><?php echo htmlspecialchars($log['email'] ?? 'N/A'); ?></td>
                                <td class="p-2 text-gray-800"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion du formulaire de recherche
    const searchForm = document.getElementById('searchForm');
    searchForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('search', '1');

        Swal.fire({
            title: 'Recherche en cours...',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('search_content.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            Swal.close();
            document.getElementById('mainContent').innerHTML = data;
            initializeDataTables();
        })
        .catch(error => {
            Swal.fire({
                icon: 'error',
                title: 'Erreur',
                text: 'Une erreur s\'est produite lors de la recherche. Veuillez réessayer.'
            });
        });
    });

    // Initialiser DataTables pour chaque table
    function initializeDataTables() {
        const tables = [
            { id: 'documentsTable', columns: [0, 1, 2, 3] },
            { id: 'usersTable', columns: [0, 1] },
            { id: 'logsTable', columns: [0, 1, 2, 3] }
        ];

        tables.forEach(table => {
            if (document.getElementById(table.id)) {
                if ($.fn.DataTable.isDataTable(`#${table.id}`)) {
                    $(`#${table.id}`).DataTable().destroy();
                }
                $(`#${table.id}`).DataTable({
                    paging: true,
                    searching: true,
                    ordering: true,
                    pageLength: 10,
                    order: [[0, 'asc']],
                    columns: table.columns.map(col => ({ searchable: true, orderable: true })),
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json'
                    }
                });
            }
        });
    }

    // Initialiser DataTables si des résultats sont déjà présents
    initializeDataTables();
});
</script>