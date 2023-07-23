<?php
// Paramètres de connexion à la base de données
$serveur = "localhost";
$utilisateur = "root";
$motDePasse = "";
$nomBaseDeDonnees = "webreathe";

// Connexion à la base de données
$connexion = mysqli_connect($serveur, $utilisateur, $motDePasse, $nomBaseDeDonnees);

// Vérification de la connexion
if (!$connexion) {
    die("La connexion à la base de données a échoué : " . mysqli_connect_error());
}

// Récupération des modules enregistrés
$requeteModules = "SELECT * FROM modules";
$resultatModules = mysqli_query($connexion, $requeteModules);
$modules = mysqli_fetch_all($resultatModules, MYSQLI_ASSOC);

// Parcourir chaque module et générer des données aléatoires
foreach ($modules as $module) {
    // Génération de données aléatoires pour chaque module
    $mesureActuelle = rand(0, 100); // Générer une valeur aléatoire pour la mesure

    // Insérer la nouvelle mesure dans la table historique
    $moduleID = $module['id'];
    $dateHeure = date('Y-m-d H:i:s'); // Date et heure actuelles
    $requeteInsertion = "INSERT INTO historique (module_id, date_heure, valeur_mesure) VALUES ('$moduleID', '$dateHeure', '$mesureActuelle')";
    mysqli_query($connexion, $requeteInsertion);

    // Mise à jour des informations du module dans la table modules
    $requeteMiseAJour = "UPDATE modules SET mesure_actuelle = '$mesureActuelle' WHERE id = '$moduleID'";
    mysqli_query($connexion, $requeteMiseAJour);
}

// Récupération de l'historique
$requeteHistorique = "SELECT * FROM historique";
$resultatHistorique = mysqli_query($connexion, $requeteHistorique);
$historique = mysqli_fetch_all($resultatHistorique, MYSQLI_ASSOC);

// Fermeture de la connexion à la base de données
mysqli_close($connexion);
?>

<!-- Affichage du tableau d'historique avec style -->
<style>
    table {
        border-collapse: collapse;
        width: 100%;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
    }

    th {
        background-color: #f2f2f2;
        font-weight: bold;
    }

    tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    tr:hover {
        background-color: #f5f5f5;
    }
</style>

<table>
    <tr>
        <th>Module ID</th>
        <th>Date et heure</th>
        <th>Valeur de mesure</th>
    </tr>
    <?php foreach ($historique as $entree) : ?>
        <tr>
            <td><?php echo $entree['module_id']; ?></td>
            <td><?php echo $entree['date_heure']; ?></td>
            <td><?php echo $entree['valeur_mesure']; ?></td>
        </tr>
    <?php endforeach; ?>
</table>
