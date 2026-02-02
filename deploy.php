<?php

/**
 * Script de déploiement pour o2switch (sans SSH)
 *
 * IMPORTANT: Supprimer ce fichier après utilisation !
 *
 * Accéder via: https://votre-domaine.com/deploy.php?key=VOTRE_CLE_SECRETE
 */

// Clé secrète pour protéger ce script - À CHANGER !
$secretKey = 'CHANGEZ_MOI_123456789';

// Vérification de la clé
if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    http_response_code(403);
    die('Accès refusé. Clé invalide.');
}

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(300);

echo "<pre style='font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px; margin: 0;'>";
echo "=== DEPLOIEMENT FAKTUR.LU ===\n\n";

// Charger l'autoloader
require __DIR__ . '/vendor/autoload.php';

// Charger l'application Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Action demandée
$action = $_GET['action'] ?? 'status';

switch ($action) {
    case 'migrate':
        echo ">>> Exécution des migrations...\n";
        $exitCode = $kernel->call('migrate', ['--force' => true]);
        echo $exitCode === 0 ? "✓ Migrations terminées avec succès\n" : "✗ Erreur lors des migrations\n";
        break;

    case 'migrate:fresh':
        echo ">>> ATTENTION: Réinitialisation complète de la base de données...\n";
        $exitCode = $kernel->call('migrate:fresh', ['--force' => true]);
        echo $exitCode === 0 ? "✓ Base de données réinitialisée\n" : "✗ Erreur\n";
        break;

    case 'cache:clear':
        echo ">>> Nettoyage des caches...\n";
        $kernel->call('cache:clear');
        echo "✓ Cache application vidé\n";
        $kernel->call('config:clear');
        echo "✓ Cache config vidé\n";
        $kernel->call('route:clear');
        echo "✓ Cache routes vidé\n";
        $kernel->call('view:clear');
        echo "✓ Cache vues vidé\n";
        break;

    case 'optimize':
        echo ">>> Optimisation pour la production...\n";
        $kernel->call('config:cache');
        echo "✓ Config mise en cache\n";
        $kernel->call('route:cache');
        echo "✓ Routes mises en cache\n";
        $kernel->call('view:cache');
        echo "✓ Vues compilées\n";
        break;

    case 'storage:link':
        echo ">>> Création du lien symbolique storage...\n";
        $exitCode = $kernel->call('storage:link');
        echo $exitCode === 0 ? "✓ Lien créé\n" : "✗ Erreur (peut-être déjà existant)\n";
        break;

    case 'key:generate':
        echo ">>> Génération de la clé d'application...\n";
        $exitCode = $kernel->call('key:generate', ['--force' => true]);
        echo $exitCode === 0 ? "✓ Clé générée\n" : "✗ Erreur\n";
        break;

    case 'status':
    default:
        echo ">>> Status de l'application\n\n";

        // Vérifier la connexion DB
        echo "Base de données: ";
        try {
            $pdo = new PDO(
                'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_DATABASE'),
                env('DB_USERNAME'),
                env('DB_PASSWORD')
            );
            echo "✓ Connectée (" . env('DB_DATABASE') . ")\n";
        } catch (Exception $e) {
            echo "✗ Erreur: " . $e->getMessage() . "\n";
        }

        // Vérifier le storage
        echo "Storage writable: ";
        echo is_writable(storage_path()) ? "✓ Oui\n" : "✗ Non\n";

        // Vérifier le lien storage
        echo "Storage link: ";
        echo file_exists(public_path('storage')) ? "✓ Existe\n" : "✗ Manquant\n";

        echo "\n=== ACTIONS DISPONIBLES ===\n";
        echo "?action=key:generate   - Générer APP_KEY\n";
        echo "?action=migrate        - Exécuter les migrations\n";
        echo "?action=migrate:fresh  - Reset complet DB (ATTENTION!)\n";
        echo "?action=cache:clear    - Vider tous les caches\n";
        echo "?action=optimize       - Optimiser pour production\n";
        echo "?action=storage:link   - Créer le lien storage\n";
        break;
}

echo "\n=== FIN ===\n";
echo "</pre>";
echo "<p style='color: red; font-weight: bold;'>⚠️ SUPPRIMEZ ce fichier après utilisation !</p>";
