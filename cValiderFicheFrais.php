<?php
    /**
     * Script de contrôle et d'affichage du cas d'utilisation "Valider fiche de frais"
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
    $moisSaisi=lireDonneePost("txtMois", "");
    $etape=lireDonneePost("hdEtape", "");
    $refuFraisHF=lireDonneePost("btRefuHF", "");
    $reportFraisHF=lireDonneePost("btReportHF", "");
    $nbJustificatifs=lireDonneePost("txtNbJustificatifs", "");

    // Vérification si le mois est valide
    $moisValide = estMoisValide($moisSaisi);

    // Vérification si le visiteur est valide
    $detailsUtilisateur = obtenirDetailVisiteur($idConnexion, $visiteurSaisi);
    $visiteurValide = is_array($detailsUtilisateur) && $detailsUtilisateur['type'] == 'visiteur';
    
    // l'utilisateur valide le visiteur qu'il veux voir
    if ($etape == "validerConsult" || $etape == "validerModification") {
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
            } elseif ( $etatFicheFrais == 'CR' ) {
                ajouterErreur($tabErreurs, "La fiche de frais n'est pas encore cloturée");
            } elseif ( $etatFicheFrais == 'VA' || $etatFicheFrais == 'RB' ) {
                ajouterErreur($tabErreurs, "La fiche de frais à déjà été validé");
            } else {
                if ($etape == "validerModification") {
                    // Si une demande de suppression de frais hors forfait est demandée
                    if ($refuFraisHF != "") {
                        // Refuse le frais hors forfait
                        refuserLigneHF($idConnexion, $refuFraisHF, $visiteurSaisi);
                    } elseif ($reportFraisHF != "") { // Si une demande de report de frais hors forfait est demandée
                        // Récupération de la date de la fiche de frais selectionnée
                        $annee = substr($moisSaisiFormatBDD, 0, 4);
                        $mois = substr($moisSaisiFormatBDD, 4, 2);
                        $date = new DateTime($annee . "-" . $mois . "-01");

                        // Récupération de la date du mois suivant
                        $nouvelleDate = clone $date;
                        $nouvelleDate->modify("+1 month")->format("Ym");

                        // Vérification si la fiche de vrais du mois suivant existe
                        $existeFicheFrais = existeFicheFrais($idConnexion, $nouvelleDate, $visiteurSaisi);
                        // si elle n'existe pas, on la crée
                        if ( !$existeFicheFrais ) {
                            ajouterFicheFrais($idConnexion, $nouvelleDate, $visiteurSaisi);
                        }

                        // Report le frais hors forfait au mois suivant
                        reporterLigneHF($idConnexion, $date, $reportFraisHF, $visiteurSaisi);
                    } else { // Sinon edition frais forfaitisés
                        if (estEntierPositif($nbJustificatifs) != 1) {
                            ajouterErreur($tabErreurs, "Le nombre de justificatifs doit être un entier positif");
                        } elseif ( ! verifierEntiersPositifs($tabChQteForfait) ) {
                            ajouterErreur($tabErreurs, "Les quantités des frais forfaitisés doivent être des entiers positifs");
                        } else {
                            // Modification des valeurs
                            modifierEltsForfait($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi, $tabChQteForfait);
                            modifierNbJustificatifs($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi, $nbJustificatifs);
                            
                            // Calcul du montant total des frais
                            $montantTotal = 0;
                            
                            // Récupération des frais forfaitisés et ajout au motant total
                            $reqTypeFraisForfait = obtenirTypeFraisForfait($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
                            $lgEltTypeFraisForfait = mysqli_fetch_assoc($reqTypeFraisForfait);
                            while ( is_array($lgEltTypeFraisForfait) ) {
                                // Si le pointeur contient un id présent dans la liste des frais envoyer depuis le formulaire
                                if ( isset($tabChQteForfait[$lgEltTypeFraisForfait["id"]]) ) {
                                    $montantTotal += $lgEltTypeFraisForfait["montant"] * $tabChQteForfait[$lgEltTypeFraisForfait["id"]];
                                }
                                $lgEltTypeFraisForfait = mysqli_fetch_assoc($reqTypeFraisForfait);
                            }
                            mysqli_free_result($reqTypeFraisForfait);
                            
                            // Récupération des frais hors forfait et ajout au montant total
                            $reqFraisHF = obtenirReqEltsHorsForfaitFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi);
                            $idJeuEltsFraisHF = mysqli_query($idConnexion, $reqFraisHF);
                            $lgEltHF = mysqli_fetch_assoc($idJeuEltsFraisHF);
                            while ( is_array($lgEltHF) ) {
                                // Si le libelle du frais hors forfait ne commence pas par le mot "REFUSE"
                                if ( !str_starts_with($lgEltHF["libelle"], 'REFUSE') ) {
                                    $montantTotal += $lgEltHF["montant"];
                                }
                                $lgEltHF = mysqli_fetch_assoc($idJeuEltsFraisHF);
                            }
                            mysqli_free_result($idJeuEltsFraisHF);

                            modifierMontantTotalValideFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi, $montantTotal);
                            modifierEtatFicheFrais($idConnexion, $moisSaisiFormatBDD, $visiteurSaisi, 'VA');
                        }
                    }
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
                <input type="hidden" name="hdEtape" value="validerConsult" />
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
                    <label for="txtMois">Mois : </label>
                    <input type="month" id="txtMois" name="txtMois" 
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
                if ( $etape == "validerConsult" || $etape == "validerModification" ) {
                    if ( nbErreurs($tabErreurs) > 0 ) {
                        echo toStringErreurs($tabErreurs) ;
                    } elseif ($etape == "validerModification" && $refuFraisHF == "" && $reportFraisHF == "") {
            ?>
                        <p class="info">
                            &nbsp;Les modifications ont été enregistrées,<br/>
                            &nbsp;la fiche de frais à été marquée comme validée et mise en attente de remboursement.
                        </p>
            <?php
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
                            $tabEltsFraisForfait[$lgEltForfait["idFraisForfait"]] = array(
                                "libelle" => $lgEltForfait["libelle"],
                                "quantite" => $lgEltForfait["quantite"]
                            );
                            $lgEltForfait = mysqli_fetch_assoc($idJeuEltsFraisForfait);
                        }
                        mysqli_free_result($idJeuEltsFraisForfait);

            ?>
                <h3>Fiche de frais de <em><?=$nomComplet?></em> pour le mois de <em><?=$libelleMois?></em></h3>
                <div class="encadre">
                    <form action="" method="POST">
                        <input type="hidden" name="etape" value="validerModification" />
                        <input type="hidden" name="lstVisiteur" value="<?=$visiteurSaisi?>" />
                        <input type="hidden" name="txtMois" value="<?=$moisSaisi?>" />
                        <table class="listeLegere">
                            <caption>Quantités des éléments forfaitisés</caption>
                            <tr>
                                <?php
                                    // premier parcours du tableau des frais forfaitisés du visiteur selectionné
                                    // pour afficher la ligne des libellés des frais forfaitisés
                                    foreach ( $tabEltsFraisForfait as $forfait ) {
                                ?>
                                    <th><?=$forfait['libelle']?></th>
                                <?php
                                    }
                                ?>
                            </tr>
                            <tr>
                                <?php
                                    // second parcours du tableau des frais forfaitisés du visiteur connecté
                                    // pour afficher la ligne des quantités des frais forfaitisés
                                    foreach ( $tabEltsFraisForfait as $idForfait => $forfait ) {
                                ?>
                                        <td class="qteForfait">
                                            <input type="number" id="chQteForfait[<?=$idForfait?>]" name="chQteForfait[<?=$idForfait?>]" 
                                                min="0" value="<?=$forfait['quantite']?>" require>
                                        </td>
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
                                <th class="action">Actions</th>
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
                                        <td>
                                            <button id="btRefuHF-<?=$lgEltHorsForfait["id"]?>" name="btRefuHF" value="<?=$lgEltHorsForfait["id"]?>">
                                                Refuser
                                            </button>
                                            <br />
                                            <button id="btReportHF-<?=$lgEltHorsForfait["id"]?>" name="btReportHF" value="<?=$lgEltHorsForfait["id"]?>">
                                                Reporter
                                            </button>
                                        </td>
                                    </tr>
                                <?php
                                        $lgEltHorsForfait = mysqli_fetch_assoc($idJeuEltsHorsForfait);
                                    }
                                    mysqli_free_result($idJeuEltsHorsForfait);
                                ?>
                        </table>
                        <br /><br />
                        <label for="txtNbJustificatifs">Nombre de justificatifs reçus : </label>
                        <input id="txtNbJustificatifs" name="txtNbJustificatifs" type="number" 
                            min="0" max="999" value="<?=$tabFicheFrais['nbJustificatifs']?>" step="1"
                            require >

                        <br /><br />
                        <input id="cmdModifier" type="submit" value="Enregistrer les modifications et valider la fiche" />
                        <input id="brAnnuler" type="reset" value="Réinitialiser les valeurs" />
                    </form>
                </div>
            <?php
                }
            }
        ?>
    </div>
<?php     
    require($repInclude . "_pied.inc.html");
    require($repInclude . "_fin.inc.php");
?> 