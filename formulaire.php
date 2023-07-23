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

if (isset($_POST['submit'])) {
    // Récupération des données du formulaire
    $nom = $_POST['nom'];
    $description = $_POST['description'];
    $mesure_actuelle = $_POST['mesure_actuelle'];
    $duree_fonctionnement = $_POST['duree_fonctionnement'];
    $nombre_donnees_envoyees = $_POST['nombre_donnees_envoyees'];
    $etat_fonctionnement = $_POST['etat_fonctionnement'];

    // Recherche d'un module existant avec le même nom
    $requeteModuleExistant = "SELECT id, etat_fonctionnement FROM modules WHERE nom = '$nom'";
    $resultatModuleExistant = mysqli_query($connexion, $requeteModuleExistant);

    if (mysqli_num_rows($resultatModuleExistant) > 0) {
        $moduleExistant = mysqli_fetch_assoc($resultatModuleExistant);

        // Vérification de l'état de fonctionnement
        if ($moduleExistant['etat_fonctionnement'] != $etat_fonctionnement) {
            // Suppression du module existant
            $requeteSuppressionModule = "DELETE FROM modules WHERE id = {$moduleExistant['id']}";
            mysqli_query($connexion, $requeteSuppressionModule);

            // Suppression de l'historique associé au module existant
            $requeteSuppressionHistorique = "DELETE FROM historique WHERE module_id = {$moduleExistant['id']}";
            mysqli_query($connexion, $requeteSuppressionHistorique);
        } else {
            // Ne rien faire si le module existant a le même état de fonctionnement
            // ou si le formulaire soumis contient le même nom et le même état de fonctionnement
            // Cela évite de créer des doublons dans la base de données
            // ou de remplacer un module fonctionnel par un autre module fonctionnel,
            // ou de remplacer un module en dysfonctionnement par un autre module en dysfonctionnement.
            // Vous pouvez modifier cette condition selon vos besoins spécifiques.
            return;
        }
    }

    // Préparation de la requête d'insertion pour le module
    $requeteInsertionModule = "INSERT INTO modules (nom, description, mesure_actuelle, duree_fonctionnement, nombre_donnees_envoyees, etat_fonctionnement) 
        VALUES ('$nom', '$description', '$mesure_actuelle', '$duree_fonctionnement', '$nombre_donnees_envoyees', '$etat_fonctionnement')";

    // Exécution de la requête d'insertion pour le module
    mysqli_query($connexion, $requeteInsertionModule);

    // Récupération de l'identifiant du module inséré
    $moduleID = mysqli_insert_id($connexion);
    // Préparation de la requête d'insertion pour l'historique
    $dateHeure = date('Y-m-d H:i:s'); // Date et heure actuelles
    $requeteInsertionHistorique = "INSERT INTO historique (module_id, date_heure, valeur_mesure) VALUES ('$moduleID', '$dateHeure', '$mesure_actuelle')";

    // Exécution de la requête d'insertion pour l'historique
    mysqli_query($connexion, $requeteInsertionHistorique);
}

// Récupération des modules enregistrés
$requeteModules = "SELECT * FROM modules ORDER BY id DESC";
$resultatModules = mysqli_query($connexion, $requeteModules);
$resultat = mysqli_fetch_all($resultatModules, MYSQLI_ASSOC);

// Récupération des données pour le graphique
$requeteDonneesEnvoyees = "SELECT nom, nombre_donnees_envoyees, etat_fonctionnement FROM modules";
$resultatDonneesEnvoyees = mysqli_query($connexion, $requeteDonneesEnvoyees);
$donneesEnvoyees = mysqli_fetch_all($resultatDonneesEnvoyees, MYSQLI_ASSOC);

// Préparation des données pour le graphique
$labels = [];
$data = [];
$colors = [];

foreach ($donneesEnvoyees as $donnees) {
    $labels[] = $donnees['nom'];
    $data[] = $donnees['nombre_donnees_envoyees'];

    if ($donnees['etat_fonctionnement'] == 0) {
        $colors[] = '#FF5151'; // Rouge pour dysfonctionnement
    } else {
        $colors[] = '#86FF7A'; // Vert pour fonctionnement
    }
}
if (isset($_POST['supprimer'])) {
    $id = $_POST['id'];

    // Recherche du module avec l'ID spécifié
    $requeteModule = "SELECT id FROM modules WHERE id = '$id'";
    $resultatModule = mysqli_query($connexion, $requeteModule);

    if (mysqli_num_rows($resultatModule) > 0) {
        // Suppression du module
        $requeteSuppressionModule = "DELETE FROM modules WHERE id = '$id'";
        mysqli_query($connexion, $requeteSuppressionModule);
        echo "Le module avec l'ID $id a été supprimé.";
    } else {
        echo "Aucun module trouvé avec l'ID $id.";
    }
}

// Vérification de l'état de fonctionnement des modules pour récupérer les noms des modules dysfonctionnants
$modulesDysfonctionnants = array();
foreach ($resultat as $module) {
    if ($module['etat_fonctionnement'] == 0) {
        $modulesDysfonctionnants[] = $module['nom'];
    }
}

// Compter le nombre de modules en état de dysfonctionnement
$nombreModulesDysfonctionnants = count($modulesDysfonctionnants);

// Fermeture de la connexion à la base de données
mysqli_close($connexion);
?>



<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Inscription et monitoring des modules IoT</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
<!-- Menu de notification -->
<div class="notification-menu">
        <div class="notification-badge"><?php echo $nombreModulesDysfonctionnants; ?></div>
        <div class="notification-list">
            <?php foreach ($modulesDysfonctionnants as $module) { ?>
                <div class="notification-item">Le module "<?php echo $module; ?>" est en dysfonctionnement.</div>
            <?php } ?>
        </div>
    </div>
<section class="container0">
        <h1>Ajouter un module</h1>
        <!-- Formulaire d'ajout de module -->
        <form method="POST" action="">
            <label for="nom">Nom du module :</label>
            <input type="text" name="nom" required><br><br>

            <label for="description">Description :</label>
            <textarea name="description" required style="resize: none;"></textarea><br><br>

            <label for="mesure_actuelle">Mesure actuelle :</label>
            <input type="number" name="mesure_actuelle" required><br><br>

            <label for="duree_fonctionnement">Durée de fonctionnement :</label>
            <input type="number" name="duree_fonctionnement" required><br><br>

            <label for="nombre_donnees_envoyees">Nombre de données envoyées :</label>
            <input type="number" name="nombre_donnees_envoyees" required><br><br>

            <label for="etat_fonctionnement">État de fonctionnement :</label>
            <select name="etat_fonctionnement" required>
                <option value="1">Fonctionnel</option>
                <option value="0">En dysfonctionnement</option>
            </select><br><br>
            <input type="submit" name="submit" value="Ajouter">
        </form>
        <a href="get_modules_data.php">Consulter Tous l'htorique  </a>

        <h2>Supprimer un module</h2>
<form class="supprimer" method="POST" action="">
    <label for="idModule">ID du module :</label>
    <input type="text" name="id" id="id" required><br><br>
    <button type="submit" name="supprimer" value="supprimer">Supprimer</button>
</form>
<p id="message"><?php echo isset($message) ? $message : ''; ?></p>

    </section>

    <section class="container1">
   
 <?php foreach ($resultat as $module) { ?>
        <div class="module-container <?php echo $module['etat_fonctionnement'] == 0 ? 'dysfonctionnement' : 'module-fonctionnel'; ?>">
            <h3><?php echo $module['nom']; ?></h3>
            <p>ID:<?php echo $module['id'];?></p>
            <p>Description : <?php echo $module['description']; ?></p>
            <p>Mesure actuelle : <?php echo $module['mesure_actuelle']; ?></p>
            <p>Durée de fonctionnement : <?php echo $module['duree_fonctionnement']; ?></p>
            <p>Nombre de données envoyées : <?php echo $module['nombre_donnees_envoyees']; ?></p>
            <p>État de fonctionnement : <?php echo $module['etat_fonctionnement'] == 0 ? 'En dysfonctionnement' : 'Fonctionnel'; ?></p>
        </div>
    <?php } ?>
</section>
 

<section class="container">
        <canvas id="myChart" class="chart-canvas" style="width: 300px; height: 300px;"></canvas>
    </section>

    <script>
        $(document).ready(function () {
            // Récupération des données pour le graphique
            const labels = <?php echo json_encode(array_column($donneesEnvoyees, 'nom')); ?>;
            const data = <?php echo json_encode(array_column($donneesEnvoyees, 'nombre_donnees_envoyees')); ?>;
            const colors = <?php echo json_encode(array_map(function ($etat_fonctionnement) {
                return $etat_fonctionnement == 0 ? '#FF5151' : '#86FF7A';
            }, array_column($donneesEnvoyees, 'etat_fonctionnement'))); ?>;
            
            // Création du graphique à barres avec Chart.js
            const ctx = document.getElementById('myChart').getContext('2d');
            const myChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Nombre de données envoyées',
                        data: data,
                        backgroundColor: colors,
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'black',
                                lineWidth: 2
                            },
                            ticks: {
                                color: 'black',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        },
                        x: {
                            grid: {
                                color: 'black',
                                lineWidth: 2
                            },
                            ticks: {
                                color: 'black',
                                font: {
                                    weight: 'bold'
                                }
                            }
                        }
                    }
                }
            });
        });
        $(document).ready(function () {
            // Afficher/cacher la liste de notification au clic sur l'icône de notification
            $('.notification-menu').click(function () {
                $('.notification-list').toggle();
            });
        });
    </script>
</body>
</html>
