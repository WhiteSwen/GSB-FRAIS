<?php
    /** 
     * Contient la division pour le sommaire, sujet à des variations suivant la 
     * connexion ou non d'un utilisateur, et dans l'avenir, suivant le type de cet utilisateur 
     * @todo  RAS
     */

?>
<!-- Division pour le sommaire -->
<div id="menuGauche">
    <div id="infosUtil">
    <?php      
        if (estVisiteurConnecte() ) {
            $idUser = obtenirIdUserConnecte() ;
            $lgUser = obtenirDetailVisiteur($idConnexion, $idUser);
            $nom = $lgUser['nom'];
            $prenom = $lgUser['prenom'];            
    ?>
            <h2>
                <?php  
                    echo $nom . " " . $prenom ;
                ?>
            </h2>
            <h3>
                <?php
                    if ($lgUser['type'] == "visiteur") {
                        echo "Visiteur médical";
                    } elseif ($lgUser['type'] == "comptable") {
                        echo "Comptable";
                    } else {
                        echo "Autre";
                    }
                ?>
            </h3>        
    <?php
        }
    ?>  
    </div>  
    <?php      
        if (estVisiteurConnecte() ) {
    ?>
            <ul id="menuList">
                <li class="smenu">
                    <a href="cAccueil.php" title="Page d'accueil">Accueil</a>
                </li>
                <li class="smenu">
                    <a href="cSeDeconnecter.php" title="Se déconnecter">Se déconnecter</a>
                </li>
                <?php
                    // affichage des liens pour les visiteurs connectés 
                    if ($lgUser['type'] == "visiteur") {
                ?>
                        <li class="smenu">
                            <a href="cSaisieFicheFrais.php" title="Saisie fiche de frais du mois courant">Saisie fiche de frais</a>
                        </li>
                        <li class="smenu">
                            <a href="cConsultFichesFrais.php" title="Consultation de mes fiches de frais">Mes fiches de frais</a>
                        </li>
                <?php
                    // affichage des liens pour les comptables connectés 
                    } else if ($lgUser['type'] == "comptable") {
                ?>
                        <li class="smenu">
                            <a href="cValiderFicheFrais.php" title="Validation de fiches de frais">Validation fiche de frais</a>
                        </li>
                        <li class="smenu">
                            <a href="cSuivrePaiementFichesFrais.php" title="Suivre le paiement de fiche de frais">Suivi paiement fiche de frais</a>
                        </li> 
                <?php
                    }
                ?>
            </ul>
    <?php
            // affichage des éventuelles erreurs déjà détectées
            if ( nbErreurs($tabErreurs) > 0 ) {
                echo toStringErreurs($tabErreurs) ;
            }
        }
    ?>
</div>
