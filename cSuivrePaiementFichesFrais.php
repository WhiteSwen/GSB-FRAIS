<?php
    /**
     * Script de contrôle et d'affichage du cas d'utilisation "Consulter une fiche de frais"
     * @package default
     * @todo  RAS
     */
    $repInclude = './include/';
    require($repInclude . "_init.inc.php");

    // page inaccessible si visiteur non connecté
    if ( ! estVisiteurConnecte() ) {
        header("Location: cSeConnecter.php");  
    }
    require($repInclude . "_entete.inc.html");
    require($repInclude . "_sommaire.inc.php");

    // Définition des variables pour plus tard
    $tabFicheFrais = null;
    $detailsUtilisateur = null;
    $moisSaisiFormatBDD = null;

    // acquisition des données entrées, ici l'utilisateur, le mois et l'étape du traitement
    $visiteurSaisi=lireDonneePost("lstVisiteur", "");
    $moisSaisi=lireDonneePost("chMois", "");
    $etape=lireDonneePost("etape", ""); 

    // Vérification si le mois est valide
    $moisValide = estMoisValide($moisSaisi);

    // Vérification si le visiteur est valide
    $detailsUtilisateur = obtenirDetailVisiteur($idConnexion, $visiteurSaisi);
    $visiteurValide = is_array($detailsUtilisateur) && $detailsUtilisateur['type'] == 'visiteur';

    // l'utilisateur valide le visiteur qu'il veux voir
    if ($etape == "validerConsult" || $etape == "validerRemboursement") {
        // Si le mois saisi n'est pas valide
        if (!$moisValide) {
            ajouterErreur($tabErreurs, "Le mois saisi est invalide");
        } elseif (!$visiteurValide) { // Si mois valide mais visiteur non valide
            ajouterErreur($tabErreurs, "Le visiteur saisi est invalide");
        } else { // Si mois et visiteur valide
            // Changement du format de la date pour la BDD
            $moisSaisiFormatBDD = substr_replace($moisSaisi, '', 4, 1);

            // Vérification de l'existence de la fiche de frais pour le mois et le visiteur demandé
            $existeFicheFrais = existeFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
            $etatFicheFrais = obtenirEtatFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
            // si elle n'existe pas, on met une erreur
            if ( !$existeFicheFrais ) {
                ajouterErreur($tabErreurs, "Pas de fiche de frais pour ce visiteur ce mois");
            } elseif ( $etatFicheFrais == 'RB' ) {
                ajouterErreur($tabErreurs, "La fiche de frais déjà est marquée comme remboursée");
            } elseif ( $etatFicheFrais == 'CR' || $etatFicheFrais == 'CL' ) {
                ajouterErreur($tabErreurs, "La fiche de frais n'a pas encore été validé");
            } else {
                if($etape == "validerRemboursement") {
                    modifierEtatFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi, 'RB');
                }

                // récupération des données sur la fiche de frais demandée
                $tabFicheFrais = obtenirDetailFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
            }
        }
    }

?>
    <!-- Division principale -->
    <div id="contenu">
        <h2>Suivie paiement fiche de frais</h2>
        <h3>Visiteur à sélectionner : </h3>
        <form action="" method="post">
            <div class="corpsForm">
                <input type="hidden" name="etape" value="validerConsult" />
                <p>
                    <label for="lstVisiteur">Visiteur : </label>
                    <select id="lstVisiteur" name="lstVisiteur" title="Sélectionnez le visiteur souhaité pour la fiche de frais" require>
                        <?php
                            // on propose tous les Utilisateurs
                            $req = obtenirReqUtilisateurs();
                            $idJeuvisiteur = mysqli_query($idConnexion, $req);
                            $lgVisiteur = mysqli_fetch_assoc($idJeuvisiteur);
                            while ( is_array($lgVisiteur) ) {
                                $nom = $lgVisiteur["nom"] . " " . $lgVisiteur["prenom"];
                                $id = $lgVisiteur["id"];
                        ?>    
                            <option value="<?php echo $id; ?>"<?php if ($visiteurSaisi == $id) { ?> selected="selected"<?php } ?>><?=$nom?></option>
                        <?php
                                $lgVisiteur = mysqli_fetch_assoc($idJeuvisiteur);        
                            }
                            mysqli_free_result($idJeuvisiteur);
                        ?>
                    </select>
                    <br />
                    <br />
                    <label for="chMois">Mois : </label>
                    <input type="month" id="chMois" name="chMois" 
                        placeholder="YYYY-MM" pattern="[0-9]{4}-[0-9]{2}"
                        max="<?=sprintf("%04d-%02d", date("Y"), date("m"))?>"
                        value="<?=(($moisSaisi == "") ? sprintf("%04d-%02d", date("Y"), date("m")) : $moisSaisi)?>"
                        title="Sélectionnez le visiteur souhaité pour la fiche de frais"
                        required />
                </p>
            </div>
            <div class="piedForm">
                <p>
                    <input id="ok" type="submit" value="Valider" size="20"
                        title="Demandez la fiche de frais du visiteur" />
                    <input id="annuler" type="reset" value="Effacer" size="20" />
                </p> 
            </div> 
        </form>
            <?php      

                // demande et affichage des différents éléments (forfaitisés et non forfaitisés)
                // de la fiche de frais demandée, uniquement si pas d'erreur détecté au contrôle
                if ( $etape == "validerConsult" ) {
                    if ( nbErreurs($tabErreurs) > 0 ) {
                        echo toStringErreurs($tabErreurs) ;
                    } else { 
                        // Définition du nom complet du visiteur selectionnez
                        $nomComplet = $detailsUtilisateur['nom'] . " " . $detailsUtilisateur['prenom'];
                                    
                        // Définition de la date au format littéraire
                        $tabDate = explode('-', $moisSaisi);
                        $libelleMois = obtenirLibelleMois(intval($tabDate[1]));
                        if($libelleMois == ""){
                            // Si pas de libellé, on affiche le mois en chiffre au format MM/YYYY
                            $libelleMois = $tabDate[1] . "/" . $tabDate[0];
                        } else {
                            // Si le libellé existe, on affiche l'année en chiffre derrière
                            $libelleMois .= " " . $tabDate[0];
                        }


                        // Récuperation des données forfétisé de la fiche de frais
                        $req = obtenirReqEltsForfaitFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
                        $idJeuEltsFraisForfait = mysqli_query($idConnexion, $req);
                        echo mysqli_error($idConnexion);
                        $lgEltForfait = mysqli_fetch_assoc($idJeuEltsFraisForfait);
                        // parcours des frais forfaitisés du visiteur selectionné
                        // le stockage intermédiaire dans un tableau est nécessaire
                        // car chacune des lignes du jeu d'enregistrements doit être doit être
                        // affichée au sein d'une colonne du tableau HTML
                        $tabEltsFraisForfait = array();
                        while ( is_array($lgEltForfait) ) {
                            $tabEltsFraisForfait[$lgEltForfait["libelle"]] = $lgEltForfait["quantite"];
                            $lgEltForfait = mysqli_fetch_assoc($idJeuEltsFraisForfait);
                        }
                        mysqli_free_result($idJeuEltsFraisForfait);

            ?>
                <h3>Fiche de frais de <em><?=$nomComplet?></em> pour le mois de <em><?=$libelleMois?></em></h3>
                <div class="encadre">
                    <p>Montant validé : <?=$tabFicheFrais["montantValide"]?></p>
                    <form action="" method="POST">
                        <input type="hidden" name="etape" value="validerRemboursement" />
                        <input type="hidden" name="lstVisiteur" value="<?=$visiteurSaisi?>" />
                        <input type="hidden" name="chMois" value="<?=$moisSaisi?>" />
                        <table class="listeLegere">
                            <caption>Quantités des éléments forfaitisés</caption>
                            <tr>
                                <?php
                                    // premier parcours du tableau des frais forfaitisés du visiteur selectionné
                                    // pour afficher la ligne des libellés des frais forfaitisés
                                    foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) {
                                ?>
                                    <th><?=$unLibelle?></th>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <?php
                                    // second parcours du tableau des frais forfaitisés du visiteur connecté
                                    // pour afficher la ligne des quantités des frais forfaitisés
                                    foreach ( $tabEltsFraisForfait as $unLibelle => $uneQuantite ) {
                                ?>
                                        <td class="qteForfait"><?=$uneQuantite?></td>
                                <?php
                                    }
                                ?>
                            </tr>
                        </table>
                        <br /><br />
                        <table class="listeLegere">
                            <caption>Descriptif des éléments hors forfait</caption>
                            <tr>
                                <th class="date">Date</th>
                                <th class="libelle">Libellé</th>
                                <th class="montant">Montant</th>
                            </tr>
                                <?php
                                    // demande de la requête pour obtenir la liste des éléments hors
                                    // forfait du visiteur selectionnée pour le mois demandé
                                    $req = obtenirReqEltsHorsForfaitFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
                                    $idJeuEltsHorsForfait = mysqli_query($idConnexion, $req);
                                    $lgEltHorsForfait = mysqli_fetch_assoc($idJeuEltsHorsForfait);

                                    // parcours des éléments hors forfait 
                                    while ( is_array($lgEltHorsForfait) ) {
                                ?>
                                    <tr>
                                        <td><?=$lgEltHorsForfait["date"]?></td>
                                        <td><?=filtrerChainePourNavig($lgEltHorsForfait["libelle"])?></td>
                                        <td><?=$lgEltHorsForfait["montant"]?></td>
                                    </tr>
                                <?php
                                        $lgEltHorsForfait = mysqli_fetch_assoc($idJeuEltsHorsForfait);
                                    }
                                    mysqli_free_result($idJeuEltsHorsForfait);
                                ?>
                        </table>
                        <br /><br />
                        <label for="chNbJustificatifs">Nombre de justificatifs reçus : <?=$tabFicheFrais['nbJustificatifs']?></label>
                        <br />
                        <label for="txtDateModif">Date de la dernière modification : <?=$tabFicheFrais['dateModif']?></label>
                        <br /><br />
                        <input type="submit" value="Marquer comme remboursé" />
                    </form>
                </div>
        <?php
                }
            } elseif ($etape == "validerRemboursement") {
        ?>
                <p class="info">
                    &nbsp;La fiche à été marqué comme remboursée avec succès.
                </p>
        <?php
            }
        ?>
    </div>
<?php     
    require($repInclude . "_pied.inc.html");
    require($repInclude . "_fin.inc.php");
?> 