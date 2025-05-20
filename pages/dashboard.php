<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGC Archiv' Secure - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <?php
    session_start();
    // Check if user is logged in
    

    // Database connection (replace with your credentials)
    require_once "../includes/config.php";

    // Fetch total documents
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM documents");
    $totalDocuments = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Fetch recent documents (last 5)
    $stmt = $pdo->query("SELECT title, created_at, category FROM documents ORDER BY created_at DESC LIMIT 5");
    $recentDocuments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch activity data for the chart (documents added per day this month)
    $stmt = $pdo->query("SELECT DATE(created_at) as date, COUNT(*) as count 
                         FROM documents 
                         WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                         GROUP BY DATE(created_at)
                         ORDER BY date ASC");
    $activityData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare data for Chart.js
    $labels = [];
    $data = [];
    foreach ($activityData as $row) {
        $labels[] = date('d M', strtotime($row['date']));
        $data[] = $row['count'];
    }
    ?>
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-black text-white shadow-md">
            <div class="p-4">
                <h1 class="text-2xl font-bold text-blue-400">AGC Archiv' Secure</h1>
            </div>
            <nav class="mt-6">
                <ul>
                    <li><a href="dashboard.php" class="block px-4 py-2 text-white bg-blue-600">Tableau de bord</a></li>
                    <li><a href="archives.php" class="block px-4 py-2 text-white hover:bg-blue-700">Gestion des archives</a></li>
                    <li><a href="search.php" class="block px-4 py-2 text-white hover:bg-blue-700">Recherche</a></li>
                    <?php if ($_SESSION['is_admin']): ?>
                        <li><a href="users.php" class="block px-4 py-2 text-white hover:bg-blue-700">Gestion des utilisateurs</a></li>
                        <li><a href="logs.php" class="block px-4 py-2 text-white hover:bg-blue-700">Journal des actions</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php" class="block px-4 py-2 text-red-500 hover:bg-red-700 hover:text-white">Déconnexion</a></li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-auto">
            <h2 class="text-3xl font-semibold text-black mb-6">Tableau de bord</h2>

            <!-- Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-blue-600 text-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-medium">Total des documents</h3>
                    <p class="text-3xl font-bold"><?php echo $totalDocuments; ?></p>
                </div>
                <div class="bg-blue-600 text-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-medium">Documents récents</h3>
                    <p class="text-3xl font-bold"><?php echo count($recentDocuments); ?></p>
                </div>
                <div class="bg-blue-600 text-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-medium">Activité ce mois</h3>
                    <p class="text-3xl font-bold"><?php echo array_sum($data); ?></p>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h3 class="text-lg font-medium text-black mb-4">Activité des documents ce mois</h3>
                <canvas id="activityChart" height="100"></canvas>
            </div>

            <!-- Recent Documents -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-black">Derniers documents</h3>
                    <a href="archives.php" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Ajouter un document</a>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="text-black">
                            <th class="p-2">Titre</th>
                            <th class="p-2">Date</th>
                            <th class="p-2">Catégorie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentDocuments as $doc): ?>
                            <tr class="border-t">
                                <td class="p-2 text-black"><?php echo htmlspecialchars($doc['title']); ?></td>
                                <td class="p-2 text-black"><?php echo date('d/m/Y', strtotime($doc['created_at'])); ?></td>
                                <td class="p-2 text-red-600"><?php echo htmlspecialchars($doc['category']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Chart.js for activity graph
        const ctx = document.getElementById('activityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Documents ajoutés',
                    data: <?php echo json_encode($data); ?>,
                    borderColor: '#1e40af', // Blue
                    backgroundColor: 'rgba(30, 64, 175, 0.1)', // Light blue fill
                    pointBackgroundColor: '#dc2626', // Red points
                    pointBorderColor: '#dc2626', // Red point borders
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Nombre de documents', color: '#000000' }, // Black
                        ticks: { color: '#000000' } // Black
                    },
                    x: {
                        title: { display: true, text: 'Date', color: '#000000' }, // Black
                        ticks: { color: '#000000' } // Black
                    }
                },
                plugins: {
                    legend: {
                        labels: { color: '#000000' } // Black
                    }
                }
            }
        });
    </script>
</body>
</html>