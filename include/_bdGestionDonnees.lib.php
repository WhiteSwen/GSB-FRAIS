<?php
/** 
 * Regroupe les fonctions d'accès aux données.
 * @package default
 * @author Arthur Martin
 * @todo Fonctions retournant plusieurs lignes sont à réécrire.
 */

/** 
 * Se connecte au serveur de données MySql.                      
 * Se connecte au serveur de données MySql à partir de valeurs
 * prédéfinies de connexion (hôte, compte utilisateur et mot de passe). 
 * Retourne l'identifiant de connexion si succès obtenu, le booléen false 
 * si problème de connexion.
 * @return resource identifiant de connexion
 */
function connecterServeurBD() {
    $hote = "127.0.0.1";
    $login = "gsb";
    $mdp = "gsb";
    return mysqli_connect($hote, $login, $mdp);
}

/**
 * Sélectionne (rend active) la base de données.
 * Sélectionne (rend active) la BD prédéfinie gsb_frais sur la connexion
 * identifiée par $idCnx. Retourne true si succès, false sinon.
 * @param resource $idCnx identifiant de connexion
 * @return boolean succès ou échec de sélection BD 
 */
function activerBD($idCnx) {
    $bd = "gsb_frais";
    $query = "SET CHARACTER SET utf8";
    // Modification du jeu de caractères de la connexion
    $res = mysqli_query($idCnx, $query); 
    $ok = mysqli_select_db($idCnx, $bd);
    return $ok;
}

/** 
 * Ferme la connexion au serveur de données.
 * Ferme la connexion au serveur de données identifiée par l'identifiant de 
 * connexion $idCnx.
 * @param resource $idCnx identifiant de connexion
 * @return void  
 */
function deconnecterServeurBD($idCnx) {
    mysqli_close($idCnx);
}

/**
 * Echappe les caractères spéciaux d'une chaîne.
 * Envoie la chaîne $str échappée, càd avec les caractères considérés spéciaux
 * par MySql (tq la quote simple) précédés d'un \, ce qui annule leur effet spécial
 * @param string $str chaîne à échapper
 * @return string chaîne échappée 
 */    
function filtrerChainePourBD($idCnx, $str) {
    return mysqli_real_escape_string($idCnx, $str);
}

/** 
 * Fournit les informations sur un visiteur demandé. 
 * Retourne les informations du visiteur d'id $unId sous la forme d'un tableau
 * associatif dont les clés sont les noms des colonnes(id, nom, prenom).
 * @param resource $idCnx identifiant de connexion
 * @param string $unId id de l'utilisateur
 * @return array  tableau associatif du visiteur
 */
function obtenirDetailVisiteur($idCnx, $unId) {
    $id = filtrerChainePourBD($idCnx, $unId);
    $requete = "select id, nom, prenom, type from Utilisateur where id='" . $unId . "'";
    $idJeuRes = mysqli_query($idCnx, $requete);  
    $ligne = false;     
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }
    return $ligne;
}

/** 
 * Fournit les informations d'une fiche de frais. 
 * Retourne les informations de la fiche de frais du mois de $unMois (MMAAAA)
 * sous la forme d'un tableau associatif dont les clés sont les noms des colonnes
 * (nbJustitificatifs, idEtat, libelleEtat, dateModif, montantValide).
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return array tableau associatif de la fiche de frais
 */
function obtenirDetailFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    $ligne = false;
    $requete="select IFNULL(nbJustificatifs,0) as nbJustificatifs, Etat.id as idEtat, libelle as libelleEtat, dateModif, montantValide 
    from FicheFrais inner join Etat on idEtat = Etat.id 
    where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    $idJeuRes = mysqli_query($idCnx, $requete);  
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
    }        
    mysqli_free_result($idJeuRes);
    
    return $ligne ;
}
              
/** 
 * Vérifie si une fiche de frais existe ou non. 
 * Retourne true si la fiche de frais du mois de $unMois (MMAAAA) du visiteur 
 * $idVisiteur existe, false sinon. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return booléen existence ou non de la fiche de frais
 */
function existeFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    $requete = "select idVisiteur from FicheFrais where idVisiteur='" . $unIdVisiteur . 
              "' and mois='" . $unMois . "'";
    $idJeuRes = mysqli_query($idCnx, $requete);  
    $ligne = false ;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }        
    
    // si $ligne est un tableau, la fiche de frais existe, sinon elle n'exsite pas
    return is_array($ligne) ;
}

/** 
 * Fournit le mois de la dernière fiche de frais d'un visiteur.
 * Retourne le mois de la dernière fiche de frais du visiteur d'id $unIdVisiteur.
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur id visiteur  
 * @return string dernier mois sous la forme AAAAMM
 */
function obtenirDernierMoisSaisi($idCnx, $unIdVisiteur) {
	$requete = "select max(mois) as dernierMois from FicheFrais where idVisiteur='" .
            $unIdVisiteur . "'";
	$idJeuRes = mysqli_query($idCnx, $requete);
    $dernierMois = false ;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        $dernierMois = $ligne["dernierMois"];
        mysqli_free_result($idJeuRes);
    }        
	return $dernierMois;
}

/** 
 * Ajoute une nouvelle fiche de frais et les éléments forfaitisés associés, 
 * Ajoute la fiche de frais du mois de $unMois (MMAAAA) du visiteur 
 * $idVisiteur, avec les éléments forfaitisés associés dont la quantité initiale
 * est affectée à 0. Clôt éventuellement la fiche de frais précédente du visiteur. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return void
 */
function ajouterFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    // modification de la dernière fiche de frais du visiteur
    $dernierMois = obtenirDernierMoisSaisi($idCnx, $unIdVisiteur);
	$laDerniereFiche = obtenirDetailFicheFrais($idCnx, $dernierMois, $unIdVisiteur);
	if ( is_array($laDerniereFiche) && $laDerniereFiche['idEtat']=='CR'){
		modifierEtatFicheFrais($idCnx, $dernierMois, $unIdVisiteur, 'CL');
	}
    
    // ajout de la fiche de frais à l'état Créé
    $requete = "insert into FicheFrais (idVisiteur, mois, nbJustificatifs, montantValide, idEtat, dateModif) values ('" 
              . $unIdVisiteur 
              . "','" . $unMois . "',0,NULL, 'CR', '" . date("Y-m-d") . "')";
    mysqli_query($idCnx, $requete);
    
    // ajout des éléments forfaitisés
    $requete = "select id from FraisForfait";
    $idJeuRes = mysqli_query($idCnx, $requete);
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        while ( is_array($ligne) ) {
            $idFraisForfait = $ligne["id"];
            // insertion d'une ligne frais forfait dans la base
            $requete = "insert into LigneFraisForfait (idVisiteur, mois, idFraisForfait, quantite)
                        values ('" . $unIdVisiteur . "','" . $unMois . "','" . $idFraisForfait . "',0)";
            mysqli_query($idCnx, $requete);
            // passage au frais forfait suivant
            $ligne = mysqli_fetch_assoc ($idJeuRes);
        }
        mysqli_free_result($idJeuRes);       
    }        
}

/**
 * Retourne le texte de la requête select concernant les mois pour lesquels un 
 * visiteur a une fiche de frais. 
 * 
 * La requête de sélection fournie permettra d'obtenir les mois (AAAAMM) pour 
 * lesquels le visiteur $unIdVisiteur a une fiche de frais. 
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqMoisFicheFrais($unIdVisiteur) {
    $req = "select fichefrais.mois as mois from  fichefrais where fichefrais.idvisiteur ='"
            . $unIdVisiteur . "' order by fichefrais.mois desc ";
    return $req ;
}  
                  
/**
 * Retourne le texte de la requête select concernant les éléments forfaitisés 
 * d'un visiteur pour un mois donnés. 
 * 
 * La requête de sélection fournie permettra d'obtenir l'id, le libellé et la
 * quantité des éléments forfaitisés de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois    
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqEltsForfaitFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    $requete = "select idFraisForfait, libelle, quantite from LigneFraisForfait
              inner join FraisForfait on FraisForfait.id = LigneFraisForfait.idFraisForfait
              where idVisiteur='" . $unIdVisiteur . "' and mois='" . $unMois . "'";
    return $requete;
}

/**
 * Retourne le texte de la requête select concernant les éléments hors forfait 
 * d'un visiteur pour un mois donnés. 
 * 
 * La requête de sélection fournie permettra d'obtenir l'id, la date, le libellé 
 * et le montant des éléments hors forfait de la fiche de frais du visiteur
 * d'id $idVisiteur pour le mois $mois    
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return string texte de la requête select
 */                                                 
function obtenirReqEltsHorsForfaitFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    $requete = "select id, date, libelle, montant from LigneFraisHorsForfait
              where idVisiteur='" . $unIdVisiteur 
              . "' and mois='" . $unMois . "'";
    return $requete;
}

/**
 * Supprime une ligne hors forfait.
 * Supprime dans la BD la ligne hors forfait d'id $unIdLigneHF
 * @param resource $idCnx identifiant de connexion
 * @param string $idLigneHF id de la ligne hors forfait
 * @return void
 */
function supprimerLigneHF($idCnx, $unIdLigneHF) {
    $requete = "delete from LigneFraisHorsForfait where id = " . $unIdLigneHF;
    mysqli_query($idCnx, $requete);
}

/**
 * Ajoute une nouvelle ligne hors forfait.
 * Insère dans la BD la ligne hors forfait de libellé $unLibelleHF du montant 
 * $unMontantHF ayant eu lieu à la date $uneDateHF pour la fiche de frais du mois
 * $unMois du visiteur d'id $unIdVisiteur
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (AAMMMM)
 * @param string $unIdVisiteur id du visiteur
 * @param string $uneDateHF date du frais hors forfait
 * @param string $unLibelleHF libellé du frais hors forfait 
 * @param double $unMontantHF montant du frais hors forfait
 * @return void
 */
function ajouterLigneHF($idCnx, $unMois, $unIdVisiteur, $uneDateHF, $unLibelleHF, $unMontantHF) {
    $unLibelleHF = filtrerChainePourBD($idCnx, $unLibelleHF);
    $uneDateHF = filtrerChainePourBD($idCnx, convertirDateFrancaisVersAnglais($uneDateHF));
    $unMois = filtrerChainePourBD($idCnx, $unMois);
    $requete = "insert into LigneFraisHorsForfait(idVisiteur, mois, date, libelle, montant) 
                values ('" . $unIdVisiteur . "','" . $unMois . "','" . $uneDateHF . "','" . $unLibelleHF . "'," . $unMontantHF .")";
    mysqli_query($idCnx, $requete);
}

/**
 * Modifie les quantités des éléments forfaitisés d'une fiche de frais. 
 * Met à jour les éléments forfaitisés contenus  
 * dans $desEltsForfaits pour le visiteur $unIdVisiteur et
 * le mois $unMois dans la table LigneFraisForfait, après avoir filtré 
 * (annulé l'effet de certains caractères considérés comme spéciaux par 
 *  MySql) chaque donnée   
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA) 
 * @param string $unIdVisiteur  id visiteur
 * @param array $desEltsForfait tableau des quantités des éléments hors forfait
 * avec pour clés les identifiants des frais forfaitisés 
 * @return void  
 */
function modifierEltsForfait($idCnx, $unMois, $unIdVisiteur, $desEltsForfait) {
    $unMois=filtrerChainePourBD($idCnx, $unMois);
    $unIdVisiteur=filtrerChainePourBD($idCnx, $unIdVisiteur);
    foreach ($desEltsForfait as $idFraisForfait => $quantite) {
        $requete = "update LigneFraisForfait set quantite = " . $quantite 
                    . " where idVisiteur = '" . $unIdVisiteur . "' and mois = '"
                    . $unMois . "' and idFraisForfait='" . $idFraisForfait . "'";
      mysqli_query($idCnx, $requete);
    }
}

/**
 * Contrôle les informations de connexionn d'un utilisateur.
 * Vérifie si les informations de connexion $unLogin, $unMdp sont ou non valides.
 * Retourne les informations de l'utilisateur sous forme de tableau associatif 
 * dont les clés sont les noms des colonnes (id, nom, prenom, login, mdp)
 * si login et mot de passe existent, le booléen false sinon. 
 * @param resource $idCnx identifiant de connexion
 * @param string $unLogin login 
 * @param string $unMdp mot de passe 
 * @return array tableau associatif ou booléen false 
 */
function verifierInfosConnexion($idCnx, $unLogin, $unMdp) {
    $unLogin = filtrerChainePourBD($idCnx, $unLogin);
    $unMdp = filtrerChainePourBD($idCnx, $unMdp);
    // le mot de passe est crypté dans la base avec la fonction de hachage md5
    $req = "select id, nom, prenom, type, login, mdp from Utilisateur where login='".$unLogin."' and mdp='" . $unMdp . "'";
    $idJeuRes = mysqli_query($idCnx, $req);
    $ligne = false;
    if ( $idJeuRes ) {
        $ligne = mysqli_fetch_assoc($idJeuRes);
        mysqli_free_result($idJeuRes);
    }
    return $ligne;
}

/**
 * Modifie l'état et la date de modification d'une fiche de frais
 
 * Met à jour l'état de la fiche de frais du visiteur $unIdVisiteur pour
 * le mois $unMois à la nouvelle valeur $unEtat et passe la date de modif à 
 * la date d'aujourd'hui
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdVisiteur 
 * @param string $unMois mois sous la forme aaaamm
 * @return void 
 */
function modifierEtatFicheFrais($idCnx, $unMois, $unIdVisiteur, $unEtat) {
    $requete = "update FicheFrais set idEtat = '" . $unEtat . 
               "', dateModif = now() where idVisiteur ='" .
               $unIdVisiteur . "' and mois = '". $unMois . "'";
    mysqli_query($idCnx, $requete);
}  

/** 
 * Renvoie l'état d'une fiche de frais 
 * Retourne l'état de la fiche de frais ou false si elle n'est pas trouvée
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id visiteur  
 * @return string l'état de la fiche de frais
 */
function obtenirEtatFicheFrais($idCnx, $unMois, $unIdVisiteur) {
    // Traiter les entrées de la requête
    $mois = filtrerChainePourBD($idCnx, $unMois);
    $visiteur = filtrerChainePourBD($idCnx, $unIdVisiteur);
    
    // Génére et éxécute la requête
    $requete = "select idEtat from FicheFrais where idVisiteur='" . $visiteur . "' and mois='" . $mois . "'";
    $idFichRes = mysqli_query($idCnx, $requete);  

    // Si un resultat, le recupérer et renvoie l'etat
    if ( $idFichRes ) {
        $ligne = mysqli_fetch_assoc($idFichRes);
        mysqli_free_result($idFichRes);
        
        // Renvoie renvoie l'état de la fiche de frais
        return $ligne['idEtat'];
    }      

    // Renvoie false si aucun résultat
    return false;
}

/**
 * Script de cloture automatique des fiches de frais non-cloturées
 * pour un mois donné
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois à traité (AAAAMM)
 * @return void
 */
function cloturerFichesFrais($idCnx, $unMois) {
    // Traiter les entrées de la requête
    $mois = filtrerChainePourBD($idCnx, $unMois);
    // Génére et éxécute la requête
    $requete = "UPDATE fichefrais SET idEtat='CL' WHERE mois='" . $mois . "' AND idEtat='CR'";
    mysqli_query($idCnx, $requete);
}

/**
 * Retourne le texte de la requête select concernant la liste des visiteurs
 * 
 * La requête de sélection fournie permettra l'id et le nom pour chaque visiteurs 
 * @return string texte de la requête select
 */
function obtenirReqUtilisateurs() {
    $req = "SELECT id, nom, prenom FROM Utilisateur WHERE type='visiteur'";
    return $req ;
}  

/**
 * Refuse une ligne hors forfait.
 * Refuse dans la BDD la ligne hors forfait d'id $unIdLigneHF
 * @param resource $idCnx identifiant de connexion
 * @param string $unIdLigneHF id de la ligne hors forfait
 * @param string $unIdVisisteur id du visiteur
 * @return void
 */
function refuserLigneHF($idCnx, $unIdLigneHF, $unIdVisisteur) {
    // Traiter les entrées de la requête
    $idLigne = filtrerChainePourBD($idCnx, $unIdLigneHF);
    $idVisisteur = filtrerChainePourBD($idCnx, $unIdVisisteur);

    // Génére et éxécute la requête
    $requete = "UPDATE LigneFraisHorsForfait SET libelle=LEFT(CONCAT('REFUSE ', libelle), 100) WHERE libelle NOT LIKE 'REFUSE%' AND id = " . $idLigne . " AND idVisiteur = '" . $idVisisteur . "'";
    mysqli_query($idCnx, $requete);
}

/**
 * Reporte le frais en retard sur la fiche du mois suivant
 * Reporte dans la BDD le frais au mois suivant
 * @param resource $idCnx identifiant de connexion
 * @param DateTime $dateActuel date actuel du frais
 * @param string $unIdLigneHF id de la ligne hors forfait
 * @param string $unIdVisiteur id du visiteur
 * @return void
 */
function reporterLigneHF($idCnx, $dateActuel, $unIdLigneHF, $unIdVisiteur) {
    // Traiter les entrées de la requête
    $idLigne = filtrerChainePourBD($idCnx, $unIdLigneHF);
    $idVisiteur = filtrerChainePourBD($idCnx, $unIdVisiteur);
    // Trouve la date du mois suivant
    $nouvelleDate = clone $dateActuel;
    $nouvelleDate->modify("+1 month");
    // Génére et éxécute la requête
    $requete = "UPDATE LigneFraisHorsForfait SET mois='" . $nouvelleDate->format("Ym") . "' WHERE id = " . $idLigne . " AND idVisiteur = '" . $idVisiteur . "'";
    mysqli_query($idCnx, $requete);
}

/**
 * Modifie le nombre de justificatifs fournis par un visiteur pour une fiche de frais
 * Modifie dans la BDD la valeur nbJustificatifs en fonction du visteur
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (MMAAAA)
 * @param string $unIdVisiteur id du visiteur
 * @param int $nbJustificatifs nombre de justificatifs
 * @return void
 */
function modifierNbJustificatifs($idCnx, $unMois, $unVisiteur, $nbJustificatifs) {
    // Traiter les entrées de la requête
    $mois = filtrerChainePourBD($idCnx, $unMois);
    $idVisiteur = filtrerChainePourBD($idCnx, $unVisiteur);
    // Génére et éxécute la requête
    $requete = "UPDATE fichefrais SET nbJustificatifs=" . $nbJustificatifs . " WHERE idVisiteur='" . $idVisiteur . "' AND mois='" . $mois . "'";
    mysqli_query($idCnx, $requete);
}

/**
 * Script de modification du montant total de la fiche de frais
 * d'un visiteur dans la BDD.
 * @param resource $idCnx identifiant de connexion
 * @param string $unMois mois demandé (AAAAMM)
 * @param string $unVisiteur id du visiteur
 * @param float $montant total des frais
 * @return void
 */
function modifierMontantTotalValideFicheFrais($idCnx, $unMois, $unVisisteur, $montant) {
    // Traiter les entrées de la requête
    $mois = filtrerChainePourBD($idCnx, $unMois);
    $idVisiteur = filtrerChainePourBD($idCnx, $unVisisteur);
    // Génére et éxécute la requête
    $requete = "UPDATE fichefrais SET montantValide=" . $montant . " WHERE idVisiteur='" . $idVisiteur . "' AND mois='" . $mois . "'";
    mysqli_query($idCnx, $requete);
}

/**
 * Récupération du type de Forfait dans la BDD
 * @param type $idCnx identifiant de connexion
 * @return void
 */
function obtenirTypeFraisForfait($idCnx) {
    $req = "SELECT id, libelle, montant FROM FraisForfait";
    $idJeuRes = mysqli_query($idCnx, $req);
    return $idJeuRes;
}

?>