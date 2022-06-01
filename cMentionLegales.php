<?php
    /** 
     * Page d'accueil de l'application web AppliFrais
     * @package default
     * @todo  RAS
     */
    $repInclude = './include/';
    require($repInclude . "_init.inc.php");

    // page inaccessible si visiteur non connecté
    if ( ! estVisiteurConnecte() ) 
    {
        header("Location: cSeConnecter.php");  
    }
    require($repInclude . "_entete.inc.html");
    require($repInclude . "_sommaire.inc.php");
?>
    <!-- Division principale -->
    <div id="contenu">
        <h2>Mentions Légales</h2>
    </div>
<?php        
    require($repInclude . "_pied.inc.html");
    require($repInclude . "_fin.inc.php");
?>
