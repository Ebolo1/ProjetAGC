<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGC Archiv' Secure - Tableau de bord</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
       body {
    font-family: 'Inter', sans-serif;
    margin: 0; /* Remove default margin */
}

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

header {
    position: fixed; /* Fix navbar at the top */
    top: 0;
    left: 0;
    right: 0;
    z-index: 20; /* Ensure navbar is above other content */
    background: #000; /* Keep background consistent */
}

aside {
    position: fixed; /* Fix sidebar on the left */
    top: 0; /* Align with top of viewport */
    bottom: 0; /* Extend to bottom */
    width: 16rem; /* Match w-64 (64 * 0.25rem = 16rem) */
    z-index: 10; /* Ensure sidebar is above main content but below navbar */
    background: #000; /* Keep background consistent */
    overflow-y: auto; /* Allow scrolling within sidebar if content overflows */
}

main {
    margin-left: 16rem; /* Match sidebar width (w-64) */
    margin-top: 4rem; /* Match navbar height (adjust based on your navbar height) */
    padding: 2rem; /* Match p-8 */
    overflow-y: auto; /* Ensure main content is scrollable */
    height: calc(100vh - 4rem); /* Full height minus navbar height */
}

/* Existing styles remain unchanged */
.animate-slide-in {
    animation: slideIn 0.5s ease-in-out;
}

@keyframes slideIn {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.modal {
    transition: opacity 0.3s ease, transform 0.3s ease;
}

.modal-hidden {
    opacity: 0;
    transform: translateY(-100px);
    pointer-events: none;
}

.modal-visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

.nav-link.active {
    background-color: #2563eb;
}
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../dist/index.php");
        exit();
    }

    require_once "../includes/config.php";

    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT email, role FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    $email = $user['email'] ?? 'email@example.com';
    $role = $user['role'] ?? 'user';
    ?>
    <div class="wave"></div>
    <div class="absolute inset-0 h-screen bg-center bg-cover" style="background-image: url('../image/image.webp'); opacity: 0.1;"></div>
    <div class="relative z-10 ">
        <!-- Navbar -->
        <header class="flex items-center justify-between p-4 text-white bg-black shadow-md">
            <h1 class="text-2xl font-bold text-blue-400">AGC Archiv' Secure</h1>
            <div class="relative">
                <button id="profileBtn" class="flex items-center p-2 space-x-2 rounded hover:bg-blue-700">
                    <ion-icon name="person-circle-outline" class="text-2xl"></ion-icon>
                    <span><?php echo htmlspecialchars($email); ?></span>
                    <ion-icon name="chevron-down-outline" class="text-xl"></ion-icon>
                </button>
                <div id="profileDropdown" class="absolute right-0 hidden w-48 mt-2 text-black bg-white rounded-lg shadow-lg animate-slide-in">
                    <a href="#" id="editProfileBtn" class="flex items-center block px-4 py-2 hover:bg-gray-100"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</a>
                    <a href="../dist/logout.php" class="flex items-center block px-4 py-2 text-red-600 hover:bg-gray-100"><ion-icon name="log-out-outline" class="mr-2"></ion-icon> Déconnexion</a>
                </div>
            </div>
        </header>

        <div class="flex ">
            <!-- Sidebar -->
            <aside class="w-64 text-white bg-black shadow-md">
                <nav class="mt-24">
                    <ul>
                        <li><a href="#" data-section="dashboard" class="flex items-center px-4 py-2 text-white bg-blue-600 nav-link hover:bg-blue-700 hover:border-l-4 hover:border-red-700"><ion-icon name="home-outline" class="mr-2"></ion-icon> Tableau de bord</a></li>
                        <li><a href="#" data-section="archive" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700 hover:border-l-4 hover:border-red-700"><ion-icon name="folder-outline" class="mr-2"></ion-icon> Gestion des archives</a></li>
                        <li><a href="#" data-section="search" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700 hover:border-l-4 hover:border-red-700"><ion-icon name="search-outline" class="mr-2"></ion-icon> Recherche</a></li>
                        <?php if ($role === 'admin'): ?>
                            <li><a href="#" data-section="users" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700 hover:border-l-4 hover:border-red-700"><ion-icon name="people-outline" class="mr-2"></ion-icon> Gestion des utilisateurs</a></li>
                            <li><a href="#" data-section="logs" class="flex items-center px-4 py-2 text-white nav-link hover:bg-blue-700 hover:border-l-4 hover:border-red-700"><ion-icon name="document-text-outline" class="mr-2"></ion-icon> Journal des actions</a></li>
                        <?php endif; ?>
                        <li><a href="../dist/logout.php" class="flex items-center px-4 py-2 text-red-500 hover:bg-red-700 hover:text-white hover:border-l-4 hover:border-blue-700 "><ion-icon name="log-out-outline" class="mr-2"></ion-icon> Déconnexion</a></li>
                    </ul>
                </nav>
            </aside>

            <!-- Main Content -->
            <main id="mainContent" class="flex-1 p-8 overflow-auto">
                <!-- Content will be loaded dynamically here -->
            </main>
        </div>

        <!-- Profile Edit Modal -->
        <div id="profileModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 modal modal-hidden">
            <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
                <h3 class="flex items-center mb-4 text-lg font-medium"><ion-icon name="create-outline" class="mr-2"></ion-icon> Modifier le profil</h3>
                <form id="profileForm">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 mt-1 border rounded" required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="closeModalBtn" class="px-4 py-2 text-black bg-gray-300 rounded hover:bg-gray-400">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Add Category Modal (Admin Only) -->
        <?php if ($role === 'admin'): ?>
        <div id="categoryModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 modal modal-hidden">
            <div class="w-full max-w-md p-6 bg-white rounded-lg shadow-lg">
                <h3 class="flex items-center mb-4 text-lg font-medium"><ion-icon name="add-circle-outline" class="mr-2"></ion-icon> Ajouter une catégorie</h3>
                <form id="categoryForm">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Nom de la catégorie</label>
                        <input type="text" name="new_category" class="w-full p-2 mt-1 border rounded" required>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" id="closeCategoryModalBtn" class="px-4 py-2 text-black bg-gray-300 rounded hover:bg-gray-400">Annuler</button>
                        <button type="submit" class="px-4 py-2 text-white bg-blue-600 rounded hover:bg-blue-700">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Load content dynamically
        function loadSection(section) {
            fetch(`../includes/${section}_content.php`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('mainContent').innerHTML = data;
                    if (section === 'dashboard') {
                        initializeDashboard();
                    } else if (section === 'archive' || section === 'users' || section === 'logs') {
                        initializeDataTable();
                    }
                })
                .catch(error => console.error('Erreur chargement section:', error));

            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
                if (link.dataset.section === section) {
                    link.classList.add('active');
                }
            });
        }

        // Initialize DataTable
        function initializeDataTable() {
            if ($.fn.DataTable.isDataTable('#documentsTable') || $.fn.DataTable.isDataTable('#usersTable') || $.fn.DataTable.isDataTable('#logsTable')) {
                $('#documentsTable, #usersTable, #logsTable').DataTable().destroy();
            }
            $('#documentsTable, #usersTable, #logsTable').DataTable({
                paging: true,
                searching: true,
                ordering: true,
                pageLength: 5,
                // language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json' }
            });
        }

        // Initialize Dashboard
        function initializeDashboard() {
            // Initialize activity chart
            const activityChartCanvas = document.getElementById('activityChart')?.getContext('2d');
            if (activityChartCanvas) {
                new Chart(activityChartCanvas, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(document.getElementById('activityChart').dataset.labels || '[]'),
                        datasets: [{
                            label: 'Documents ajoutés',
                            data: JSON.parse(document.getElementById('activityChart').dataset.data || '[]'),
                            borderColor: '#1e40af',
                            backgroundColor: 'rgba(30, 64, 175, 0.1)',
                            pointBackgroundColor: '#dc2626',
                            pointBorderColor: '#dc2626',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Nombre de documents' } },
                            x: { title: { display: true, text: 'Date' } }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: { mode: 'index', intersect: false }
                        }
                    }
                });
            }

            // Initialize category chart
            const categoryChartCanvas = document.getElementById('categoryChart')?.getContext('2d');
            if (categoryChartCanvas) {
                new Chart(categoryChartCanvas, {
                    type: 'bar',
                    data: {
                        labels: JSON.parse(document.getElementById('categoryChart').dataset.categories || '[]'),
                        datasets: [{
                            label: 'Nombre de documents',
                            data: JSON.parse(document.getElementById('categoryChart').dataset.counts || '[]'),
                            backgroundColor: 'rgba(30, 64, 175, 0.6)',
                            borderColor: '#1e40af',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: { beginAtZero: true, title: { display: true, text: 'Nombre de documents' } },
                            x: { title: { display: true, text: 'Catégorie' } }
                        },
                        plugins: {
                            legend: { display: true },
                            tooltip: { mode: 'index', intersect: false }
                        }
                    }
                });
            }

            // Add event listener for category button (admin only)
            <?php if ($role === 'admin'): ?>
            document.getElementById('addCategoryBtn')?.addEventListener('click', () => {
                document.getElementById('categoryModal').classList.remove('modal-hidden');
                document.getElementById('categoryModal').classList.add('modal-visible');
            });
            <?php endif; ?>

            initializeDataTable();
        }

        // Profile dropdown toggle
        document.getElementById('profileBtn').addEventListener('click', () => {
            document.getElementById('profileDropdown').classList.toggle('hidden');
        });

        // Profile modal toggle
        const profileModal = document.getElementById('profileModal');
        document.getElementById('editProfileBtn').addEventListener('click', () => {
            profileModal.classList.remove('modal-hidden');
            profileModal.classList.add('modal-visible');
        });
        document.getElementById('closeModalBtn').addEventListener('click', () => {
            profileModal.classList.remove('modal-visible');
            profileModal.classList.add('modal-hidden');
        });

        // Profile form submission
        document.getElementById('profileForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('../includes/update_profile.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        Swal.fire('Erreur', 'Erreur lors de la mise à jour du profil.', 'error');
                    }
                });
        });

        // Category modal toggle (admin only)
        <?php if ($role === 'admin'): ?>
        const categoryModal = document.getElementById('categoryModal');
        document.getElementById('closeCategoryModalBtn')?.addEventListener('click', () => {
            categoryModal.classList.remove('modal-visible');
            categoryModal.classList.add('modal-hidden');
        });

        // Category form submission
        document.getElementById('categoryForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            fetch('../includes/dashboard_content.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Succès', data.message, 'success').then(() => {
                            categoryModal.classList.remove('modal-visible');
                            categoryModal.classList.add('modal-hidden');
                            loadSection('dashboard'); // Reload dashboard to update cards and chart
                        });
                    } else {
                        Swal.fire('Erreur', data.message || 'Erreur lors de l\'ajout de la catégorie.', 'error');
                    }
                });
        });
        <?php endif; ?>

        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                loadSection(link.dataset.section);
            });
        });

        // Load dashboard by default
        loadSection('dashboard');
    </script>
</body>
</html>