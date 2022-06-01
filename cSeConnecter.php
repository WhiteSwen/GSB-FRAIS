<?php  
    /** 
     * Script de contrôle et d'affichage du cas d'utilisation "Se connecter"
     * @package default
     * @todo  RAS
     */
    $repInclude = './include/';
    require($repInclude . "_init.inc.php");
    
    // est-on au 1er appel du programme ou non ?
    $etape=(count($_POST)!=0)?'validerConnexion' : 'demanderConnexion';
    
    if ($etape=='validerConnexion') { // un client demande à s'authentifier
        // acquisition des données envoyées, ici login et mot de passe
        $login = lireDonneePost("txtLogin");
        $mdp = lireDonneePost("txtMdp");   
        $lgUser = verifierInfosConnexion($idConnexion, $login, $mdp) ;
        // si l'id utilisateur a été trouvé, donc informations fournies sous forme de tableau
        if ( is_array($lgUser) ) { 
            affecterInfosConnecte($lgUser["id"], $lgUser["login"], $lgUser["type"]);
            // Si l'utilisateur est un comptable
            if ( $lgUser["type"] == "comptable" ){
                // Récupère le numero du jour du mois actuel et le converti en entier
                $jourActuel = intval(date("d"));
                // Si le jour actuel est compris en 10 et 20 inclus
                if ( $jourActuel >= 10 && $jourActuel <= 20 ) {
                    // On récupère le mois précédent
                    $moisPrecedent = new DateTime(date("Y-m-01"));
                    $moisPrecedent->modify("-1 month");
                    // On cloture toutes les fiches de frais non cloturées du mois précédent
                    cloturerFichesFrais($idConnexion, $moisPrecedent->format("Ym"));
                }
            }
        }
        else {
            ajouterErreur($tabErreurs, "Pseudo et/ou mot de passe incorrects");
        }
    }
    if ( $etape == "validerConnexion" && nbErreurs($tabErreurs) == 0 ) {
        header("Location:cAccueil.php");
    }

    require($repInclude . "_entete.inc.html");
    require($repInclude . "_sommaire.inc.php");
    
?>
    <!-- Division pour le contenu principal -->
    <div id="contenu">
        <h2>Identification utilisateur</h2>
<?php
        if ( $etape == "validerConnexion" ) 
        {
            if ( nbErreurs($tabErreurs) > 0 ) 
            {
                echo toStringErreurs($tabErreurs);
            }
        }
?>               
        <form id="frmConnexion" action="" method="post">
            <div class="corpsForm">
                <input type="hidden" name="etape" id="etape" value="validerConnexion" />
                <p>
                    <label for="txtLogin" accesskey="n">* Login : </label>
                    <input type="text" id="txtLogin" name="txtLogin" maxlength="20" size="15" value="" title="Entrez votre login" />
                </p>
                <p>
                    <label for="txtMdp" accesskey="m">* Mot de passe : </label>
                    <input type="password" id="txtMdp" name="txtMdp" maxlength="8" size="15" value=""  title="Entrez votre mot de passe"/>
                </p>
            </div>
            <div class="piedForm">
                <p>
                    <input type="submit" id="ok" value="Valider" />
                    <input type="reset" id="annuler" value="Effacer" />
                </p> 
            </div>
        </form>
    </div>
<?php
    require($repInclude . "_pied.inc.html");
    require($repInclude . "_fin.inc.php");
?>