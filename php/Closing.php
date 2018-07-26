<?php

require_once __DIR__.'/DAO.php';

class Closing{
	
	// Si c'est une campagne de phase 2 true sinon false
	private $isP2;
	
	// Informations envoyées en POST et Autre informations du dictionnaire
	private $infos;
	
	// Accés au base de données P1, P2 et Admin Elastix
	private $dao;
	
	
	/**
	 * Initialisation de la classe Closing
	 *
	 * @param boolean $isP2 Permet de savoir si c'est une campagne de phase2 ou non
	 * @param array $infos Tableau des propriétés reçu du $_POST
	*/
	public function __construct(bool $isP2, array $infos){
		$this->isP2 = $isP2;
		$this->infos = $infos;
		$this->checkInfos();
		$this->dao = new DAO($this->serveur_id, $this->campagne_id);
		$this->getProspect();
		$this->getAgent();
	}
	
	/**
	 * Permet de créer des propriétés dans $infos
	 * 
	 * @param string $name Nom de la propriété
	 * @param string $value Valeur de la propriété
	*/
	public function __set(string $name, $value){
		$this->infos[$name] = htmlspecialchars($value);
	}
	
	/**
	 * Permet de récupérer des propriétés dans $infos
	 *
	 * @param string $name Nom de la propriété
	 *
	 * @return string Valeur de la propriété
	*/
	public function __get(string $name){
		if(array_key_exists($name, $this->infos)){
			return $this->infos[$name];
		}
		
		$trace = debug_backtrace();
		trigger_error("Propriété $name non-définie dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'], E_USER_NOTICE);
		exit();
		return null;
	}
	
	/**
	 * Vérifie si les propriétés obligatoires à l'initialisation sont bien présentes
	*/
	private function checkInfos(){
		$trace = debug_backtrace();
		$fields = ["agent_id",
				   "serveur_id",
				   "campagne_id",
				   "prospect_id",
				   "appel_id", "appel_uniqueid", "appel_tel", "appel_timestamp", "appel_date", "appel_heure",
				   "resultat_conclusion", "resultat_conclusion_motifref"];
		
		if($this->isP2){
			$fields[] = "resultat_conclusion_status";
		}
		
		foreach($fields as $field){
			if(!array_key_exists($field, $this->infos)){
				trigger_error("La propriété $field est obligatoire et n'a pas été renseignée !", E_USER_NOTICE);
				exit();
			}
		}
	}
	
	/**
	 * Récupère les informations du prospect
	*/
	private function getProspect(){
		// Count des itérations
		$i = 0;
		
		// Récupère les infos
		$req_getProspect = $this->dao->getCamp()->prepare("SELECT * FROM Prospect WHERE RefProspect = :ref;");
		$req_getProspect->execute(array(
			"ref" => $this->prospect_id
		));
		
		
		foreach($req_getProspect->fetch(PDO::FETCH_ASSOC) AS $key => $prospect){
			// Set la nouvelle propriété
			$this->{'db_prospect_'.strtolower($key)} = $prospect;
			$i++;
		}
		
		// Vérifie si le prospect à bien été récupéré
		if($i === 0){
			$trace = debug_backtrace();
			trigger_error("Aucun prospect trouvé dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'], E_USER_NOTICE);
			exit();
		}
	}
	
	/**
	 * Récupère les informations de l'agent
	*/
	private function getAgent(){
		// Count des itérations
		$i = 0;
		
		// Récupère les infos
		$req_getAgent = $this->dao->getAdmin()->prepare("SELECT TEAM, AGENT, LIBAGENT, SEXE, TYPE FROM AGENTS WHERE ID_AGENT = :id;");
		$req_getAgent->execute(array(
			"id" => $this->agent_id
		));
		
		foreach($req_getAgent AS $agent){
			// Set la nouvelle propriété
			$this->agent_team = $agent['TEAM'];
			$this->agent_nom = $agent['AGENT'];
			$this->agent_lib = $agent['LIBAGENT'];
			$this->agent_sexe = $agent['SEXE'];
			$this->agent_type = $agent['TYPE'];
			$i++;
		}
		
		// Vérifie si l'agent à bien été récupéré
		if($i === 0){
			$trace = debug_backtrace();
			trigger_error("Aucun agent trouvé dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'], E_USER_NOTICE);
			exit();
		}
	}
	
	/**
	 * Sauvgarde de toutes des tables
	 *
	 * @param array $prospect Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à mettre à jour pour la table Prospect
	 *
	 * @param array $resultat Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à insérer pour la table Resultats
	*/
	public function save(array $prospect, array $resultat){
		$this->saveProspect($prospect);
		$this->createResultat($resultat);
	}
	
	/**
	 * Sauvgarde les données dans la table Prospect
	 *
	 * @param array $save Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à mettre à jour
	 *
	 * @return boolean Renvoi un message de confirmation
	*/
	public function saveProspect(array $save){
		// Count des itérations
		$i = 0;
		
		// Vérifie le status si est une phase 2
		if($this->isP2){
			
			$save['NBBS'] = $this->db_prospect_nbbs;
			$save['NBAPP'] = $this->db_prospect_nbapp;
			$save['NBACC'] = $this->db_prospect_nbacc;
			
			// Mise à jour des stats du prospect en phase 2
			switch($this->resultat_conclusion_status){
				case 'MAINTIEN ACCORD RENVOI BS':
				case "MAINTIEN ACCORD ENVOI MAIL":
					$save['NBBS'] += 1;
					$save['NBAPP'] +=1;
					$save['NBACC'] += 1;
					break;
				case "MAINTIEN ACCORD REARGUMENTATION":
				case "MAINTIEN ACCORD RETOURNE":
					$save['NBAPP'] += 1;
					$save['NBACC'] += 1;
					break;
				case "BS DEJA RENVOYE":
				case "VIENT DE RECEVOIR":
				case "REFLECHIT":
				case "N'A PAS ENCORE RECU":
				case "ABSENT":
					$save['NBAPP'] += 1;
					break;
				default:
					if($this->resultat_conclusion != "REPONDEUR"){
						$save['NBAPP'] += 1;
					}
			}
			
			// Si nombre d'envoi BS supérieur à 2 alors alerte
			if($save['NBBS'] >= 2){
				$save['ALERTE'] = "Risque de fausse barbe";
			}
			
			// Set l'étacom phase 2
			$save['ETACOM2'] = $this->resultat_conclusion_status.", ".$this->appel_date." : ".$this->agent_lib;
			
			// On ajoute ou non à l''étacom déjà existant
			$save['ETACOM2'] =
				$this->db_prospect_etacom2 != "" || $this->db_prospect_etacom2 != null ?
					$this->db_prospect_etacom2." | ".$save['ETACOM2'] : $save['ETACOM2'];
		}
		
		// Liste des champs
		$strReq = $this->dao->keysToStrPDOUpdate($save);
		
		
		// Ajout de la référence prospect au tableau
		$save['refprospect'] = $this->prospect_id;
		
		
		// Sauvgarde les infos
		try{
			$req_saveProspect = $this->dao->getCamp()->prepare("UPDATE Prospect SET $strReq WHERE RefProspect = :refprospect;");
			$req_saveProspect->execute($save);
		}catch(PDOException $e){
			// Vérifie si le prospect à bien été sauvgardé
			$trace = debug_backtrace();
			trigger_error("Erreur dans la sauvegarde du prospect dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
			exit();
		}
		
		$this->getProspect();
	}
	
	/**
	 * Traitement du résultat
	 *
	 * @param array $result Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à insérer
	*/
	public function createResultat(array $result){
		// Vérifie le type de conclusion
		switch($this->resultat_conclusion){
			case 'ACCORD':
				$this->resultat_conclusion_id = 110;
				$this->resultat_conclusion_categ = 2;
				$this->resultat_etacom = "Accord du ".$this->appel_date." par ".$this->agent_lib;
				
				// Si la campagne possède une phase 2 alors on crée un les informations
				// pour un rappel en phase 2
				if($this->dao->getCampP2() != ""){
					$this->resultat_rappel_dateP2 = $this->rappel_p2_date;
					$this->resultat_rappel_heureP2 = $this->rappel_p2_heure;
					$this->resultat_rappel_timestampP2 = $this->rappel_p2_timestamp;
				}
				break;
			case 'REFUS':
				$this->resultat_conclusion_id = 111;
				$this->resultat_conclusion_categ = 3;
				break;
			case 'REFUS RAP':
				$this->resultat_conclusion_id = 211;
				$this->resultat_conclusion_categ = 3;
				break;
			case 'FAUX NUMERO':
				$this->resultat_conclusion_id = 1;
				$this->resultat_conclusion_categ = 0;
				break;
			case 'RAPPEL AUTO':
			case 'RAPPEL MANUEL':
				$this->resultat_conclusion_id = 6;
				$this->resultat_conclusion_categ = 0;
				$this->resultat_rappel_date = $this->rappel_date;
				$this->resultat_rappel_heure = $this->rappel_heure;
				$this->resultat_rappel_timestamp = $this->rappel_timestamp;
				break;
			case 'REPONDEUR':
				$this->resultat_conclusion_id = 2;
				$this->resultat_conclusion_categ = 0;
				$this->resultat_rappel_date = $this->rappel_date;
				$this->resultat_rappel_heure = $this->rappel_heure;
				$this->resultat_rappel_timestamp = $this->rappel_timestamp;
				break;
			default:
				$this->resultat_conclusion_id = 0;
				$this->resultat_conclusion_categ = 0;
				break;
		}
		
		$this->saveResultat($result);
		
		if($this->resultat_conclusion == 'RAPPEL AUTO' ||
		   $this->resultat_conclusion == 'RAPPEL MANUEL' ||
		   $this->resultat_conclusion == 'REPONDEUR'){
			$this->saveRappel();
		}
		
		$this->saveResAppel();
	}
	
	/**
	 * Sauvegarde les données dans la table Resultats
	 *
	 * @param array $save Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à insérer
	*/
	private function saveResultat(array $save){
		// Count des itérations
		$i = 0;
		
		// Ajout des propriétés obligatoires
		$save['RefAppel'] = $this->appel_id;
		$save['HOTESSE'] = $this->agent_lib;
		$save['DATEAPPEL'] = $this->appel_date;
		$save['HEUREAPPEL'] = $this->appel_heure;
		$save['RefQualif'] = $this->resultat_conclusion_id;
		$save['RefCateg'] = $this->resultat_conclusion_categ;
		if($this->isP2){
			$save['CONCLUSION'] = $this->resultat_conclusion_status == "" || $this->resultat_conclusion_status == null ? $this->resultat_conclusion : $this->resultat_conclusion_status;
		}else{
			$save['CONCLUSION'] = $this->resultat_conclusion;
		}
		$save['MOTIFREFUS'] = $this->resultat_conclusion_motifref;
		$save['RecordFileName'] = $this->getRFN();
		
		// Vérifie si l'appel a déjà été qualifié
		if($this->checkExistAppelResultats()){
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOUpdate($save);

			// Ajout des propriétés obligatoires
			// pour la where clause de la mise à jour
			$save['RefProspect'] = $this->prospect_id;
			$save['UNIQUE_ID'] = $this->appel_uniqueid;

			// Sauvgarde les infos
			try{
				$req_updateResultat = $this->dao->getCamp()->prepare("UPDATE Resultats SET $strReq WHERE RefProspect = :RefProspect AND UNIQUE_ID = :UNIQUE_ID;");
				$req_updateResultat->execute($save);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la mise à jour du résultat dans ".$trace[0]['file']."
						à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}else{
			// Ajout des propriétés obligatoires pour un ajout à la base
			$save['RefProspect'] = $this->prospect_id;
			$save['UNIQUE_ID'] = $this->appel_uniqueid;

			// Liste des champs
			$strReq = $this->dao->keysToStrPDOInsert($save);

			// Sauvgarde les infos
			try{
				$req_insertResultat = $this->dao->getCamp()->prepare("INSERT INTO Resultats $strReq;");
				$req_insertResultat->execute($save);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la sauvegarde du résultat dans ".$trace[0]['file']."
						à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}
		
		$this->saveProd();
	}
	
	/**
	 * Récupère les noms des fichiers d'enregistrement de l'appel
	 *
	 * @return string Noms des fichiers d'enregistrement
	*/
	public function getRFN(){
		$rfn = "";
		$nbRFN = 0;
		
		try{
			$get_rfn = $this->dao->getWebphone()->prepare("SELECT RFN
											 FROM HISTO_VERIF_RECORDING
											 WHERE UNIQUEID = :uniqueid
											 AND CALLID = :callid
											 AND REFPROSPECT = :refprospect");
			$get_rfn->execute(array(
				"uniqueid" => $this->appel_uniqueid,
				"callid" => $this->appel_id,
				"refprospect" => $this->prospect_id
			));
		}catch(PDOException $e){
			// Vérifie si le résultat à bien été sauvgardé
			$trace = debug_backtrace();
			trigger_error("Erreur dans la récupération des RFN dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
			exit();
		}
		
		foreach($get_rfn AS $row){
			
			if($nbRFN === 0){
				$rfn = $row['RFN'];
			}else{
				$rfn = $rfn.";".$row['RFN'];
			}
			
			$nbRFN++;
		}
		
		return $rfn;
	}
	
	/**
	 * Sauvegarde les données du résultat dans la table Prod
	*/
	private function saveProd(){
		// Count des itérations
		$i = 0;
		
		// Vérifie si la refappel est déjà présente
		if($this->checkExistProd()){
			// Tableau conteant les informations de la refappel
			$savprod = array(
				"BASECAMP" => $this->db_prospect_basecamp,
				"REFAPPEL" => $this->$this->appel_id,
				"HOTESSE" => $this->agent_lib,
				"IDAGENT" => $this->agent_id,
				"DATEAPPEL" => $this->appel_date,
				"HEUREAPPEL" => $this->appel_heure,
				"REFQUALIF" => $this->resultat_conclusion_id
			);
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOUpdate($savprod);
			
			// Ajout de la référence prospect et l'unique id au tableau
			$savprod['REFPROSPECT'] = $this->prospect_id;
			$savprod['UNIQUE_ID'] = $this->appel_uniqueid;
			
			// Sauvgarde les infos
			try{
				$req_updateProd = $this->dao->getSavProd()->prepare("UPDATE Prod SET $strReq WHERE REFPROSPECT = :REFPROSPECT AND UNIQUE_ID = :UNIQUE_ID;");
				$req_updateProd->execute($savprod);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la mise à jour de la prod dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
			
		}else{
			// Tableau conteant les informations de resappel
			$savprod = array(
				"BASECAMP" => $this->db_prospect_basecamp,
				"REFPROSPECT" => $this->prospect_id,
				"HOTESSE" => $this->agent_lib,
				"IDAGENT" => $this->agent_id,
				"DATEAPPEL" => $this->appel_date,
				"HEUREAPPEL" => $this->appel_heure,
				"REFQUALIF" => $this->resultat_conclusion_id,
				"REFAPPEL" => $this->appel_id,
				"UNIQUE_ID" => $this->appel_uniqueid
			);
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOInsert($savprod);
			
			// Sauvgarde les infos
			try{
				$req_insertProd = $this->dao->getSavProd()->prepare("INSERT INTO Prod $strReq;");
				$req_insertProd->execute($savprod);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la sauvegarde du resappel dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}
	}

	/**
	 * Vérifie si l'appel existe déjà dans la table Resultats
	 *
	 * @return boolean Est présent ou non dans le table
	*/
	public function checkExistAppelResultats(){
		// Count des itérations
		$i = 0;	
		
		// Récupére le rappel
		$req_getAppelResultats = $this->dao->getCamp()->prepare('SELECT REF_AUTO FROM Resultats WHERE RefProspect = :ref AND UNIQUE_ID = :uniqueId');
		$req_getAppelResultats->execute(array(
			"ref" => $this->prospect_id,
			"uniqueId" => $this->appel_uniqueid
		));
		
		foreach($req_getAppelResultats as $resultat){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Vérifie si la refappel existe déjà dans la table Prod
	 *
	 * @return boolean Est présent ou non dans le table
	*/
	public function checkExistProd(){
		// Count des itérations
		$i = 0;	
		
		// Récupére la refappel
		$req_getRefappel = $this->dao->getSavProd()->prepare("SELECT REFAPPEL FROM Prod WHERE REFAPPEL = :refappel;");
		$req_getRefappel->execute(array(
			"refappel" => $this->appel_id
		));
		
		foreach($req_getRefappel as $refappel){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Vérifie si existe déjà en rappel
	 *
	 * @return boolean Est présent ou non dans le table
	*/
	public function checkExistRappel(){
		// Count des itérations
		$i = 0;	
		
		// Récupére le rappel
		$req_getRappel = $this->dao->getCamp()->prepare("SELECT RefAgent FROM Rappels WHERE RefProspRapp = :ref;");
		$req_getRappel->execute(array(
			"ref" => $this->prospect_id
		));
		
		foreach($req_getRappel as $rappel){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Vérifie si existe déjà en rappel en phase 2
	 *
	 * @param string Id prospect
	 *
	 * @return boolean Est présent ou non dans le table
	*/
	public function checkExistRappelP2(){
		// Count des itérations
		$i = 0;	
		
		// Récupére le rappel
		$req_getRappel = $this->dao->getCampP2()->prepare("SELECT RefAgent FROM Rappels WHERE RefProspRapp = :ref;");
		$req_getRappel->execute(array(
			"ref" => $this->prospect_id
		));
		
		foreach($req_getRappel as $rappel){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Sauvegarde les données dans la table Rappels
	*/
	private function saveRappel(){
		// Création d'un tableau avec les informations du rappel
		$rappel = array(
			"RefAgent" => $this->agent_id,
			"HeureRappel" => $this->resultat_rappel_timestamp,
			"NumAppelRapp" => $this->appel_tel,
			"lockAgent" => '0',
			"DateHeur" => $this->resultat_rappel_date." ".$this->resultat_rappel_heure,
			"auteur" => $this->agent_lib,
			"Manual" => ($this->checkExistRappel() ? '-1' : '0'),
			"Affected" => ($this->checkExistRappel() ? '-1' : '0')
		);
		
		// Count des itérations
		$i = 0;		
		
		// Sauvgarde les infos
		try{
			if($this->checkExistRappel()){
				$strReq = $this->dao->keysToStrPDOUpdate($rappel);
				$rappel['RefProspRapp'] = $this->prospect_id;
				$req_insertRappel = $this->dao->getCamp()->prepare("UPDATE Rappels SET $strReq WHERE RefProspRapp = :RefProspRapp;");
			}else{
				$rappel['RefProspRapp'] = $this->prospect_id;
				$strReq = $this->dao->keysToStrPDOInsert($rappel);
				$req_insertRappel = $this->dao->getCamp()->prepare("INSERT INTO Rappels $strReq;");
			}
			$req_insertRappel->execute($rappel);
		}catch(PDOException $e){
			// Vérifie si le résultat à bien été sauvgardé
			$trace = debug_backtrace();
			trigger_error("Erreur dans la sauvegarde du rappel dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
			exit();
		}
	}
	
	/**
	 * Sauvegarde les données dans la table Rappels de la Phase 2
	*/
	private function saveRappelP2(){
		// Création d'un tableau avec les informations du rappel
		$rappel = array(
			"RefAgent" => $this->agent_id,
			"HeureRappel" => $this->resultat_rappel_timestampP2,
			"NumAppelRapp" => $this->appel_tel,
			"lockAgent" => '0',
			"DateHeur" => $this->resultat_rappel_dateP2." ".$this->resultat_rappel_heureP2,
			"auteur" => $this->agent_lib,
			"Manual" => ($this->checkExistRappelP2() ? '-1' : '0'),
			"Affected" => ($this->checkExistRappelP2() ? '-1' : '0')
		);
		
		// Count des itérations
		$i = 0;		
		
		// Sauvgarde les infos
		try{
			if($this->checkExistRappelP2()){
				$strReq = $this->dao->keysToStrPDOUpdate($rappel);
				$rappel['RefProspRapp'] = $this->prospect_id;
				$req_insertRappel = $this->dao->getCampP2()->prepare("UPDATE Rappels SET $strReq WHERE RefProspRapp = :RefProspRapp;");
			}else{
				$rappel['RefProspRapp'] = $this->prospect_id;
				$strReq = $this->dao->keysToStrPDOInsert($rappel);
				$req_insertRappel = $this->dao->getCampP2()->prepare("INSERT INTO Rappels $strReq;");
			}
			$req_insertRappel->execute($rappel);
		}catch(PDOException $e){
			// Vérifie si le résultat à bien été sauvgardé
			$trace = debug_backtrace();
			trigger_error("Erreur dans la sauvegarde du rappel en phase 2 dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
			exit();
		}
	}	
	
	/**
	 * Vérifie si le resappel existe
	 *
	 * @return boolean Si existe ou non
	*/
	public function checkExistResAppel(){
		// Count des itérations
		$i = 0;	
		
		// Récupére le resappel
		$req_getResAppel = $this->dao->getCamp()->prepare("SELECT NbAppels FROM ResAppel WHERE RefProspect = :ref;");
		$req_getResAppel->execute(array(
			"ref" => $this->prospect_id
		));
		
		foreach($req_getResAppel as $resappel){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Vérifie si le prospect existe en phase 2
	 *
	 * @return boolean Si existe ou non
	*/
	public function checkExistP2(){
		// Count des itérations
		$i = 0;	
		
		// Récupére le resappel
		$req_getResAppel = $this->dao->getCampP2()->prepare("SELECT RefProspect FROM Prospect WHERE RefProspect = :ref;");
		$req_getResAppel->execute(array(
			"ref" => $this->prospect_id
		));
		
		foreach($req_getResAppel as $resappel){
			$i++;
		}
		
		if($i > 0){
			return true;
		}
		
		return false;
	}
	
	/**
	 * Retourne le nombre d'appel dans ResAppel
	 *
	 * @return int Nombre d'appel
	*/
	public function getResAppelNum(){
		// Nombre d'appel
		$i = 0;
		
		// Récupére le resappel
		$req_getResAppel = $this->dao->getCamp()->prepare("SELECT NbAppels FROM ResAppel WHERE RefProspect = :ref;");
		$req_getResAppel->execute(array(
			"ref" => $this->prospect_id
		));
		
		foreach($req_getResAppel as $resappel){
			$i = $resappel['NbAppels'];
			break;
		}
		
		return $i;
	}
	
	/**
	 * Sauvgarde les données dans la table ResAppel
	*/
	private function saveResAppel(){
		// Count des itérations
		$i = 0;
		
		// Vérifie si resappel est déjà présent
		if($this->checkExistResAppel()){
			// Tableau conteant les informations de resappel
			$resappel = array(
					"NbAppels" => $this->getResAppelNum() + 1,
					"ResAppel" => ($this->resultat_conclusion === "FAUX NUMERO" ? '3' : '8'),
					"Qualification" => $this->resultat_conclusion_id,
					"DateHeureAppel" => $this->appel_timestamp,
					"NumAppel" => $this->appel_tel,
					"QualiteCompl" => '0',
					"DateHeur" => $this->appel_date." ".$this->appel_heure,
					"RefAgenda" => '0'
				);
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOUpdate($resappel);
			
			// Ajout de la référence prospect au tableau
			$resappel['refprospect'] = $this->prospect_id;
			
			// Sauvgarde les infos
			try{
				$req_insertResAppel = $this->dao->getCamp()->prepare("UPDATE ResAppel SET $strReq WHERE RefProspect = :refprospect;");
				$req_insertResAppel->execute($resappel);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la sauvegarde du resappel dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
			
		}else{
			// Tableau conteant les informations de resappel
			$resappel = array(
				"RefProspect" => $this->prospect_id,
				"NbAppels" => $this->getResAppelNum() + 1,
				"ResAppel" => ($this->resultat_conclusion === "FAUX NUMERO" ? '3' : '8'),
				"Qualification" => $this->resultat_conclusion_id,
				"DateHeureAppel" => $this->appel_timestamp,
				"NumAppel" => $this->appel_tel,
				"QualiteCompl" => '0',
				"DateHeur" => $this->appel_date." ".$this->appel_heure,
				"RefAgenda" => '0'
			);
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOInsert($resappel);
			
			// Sauvgarde les infos
			try{
				$req_insertResAppel = $this->dao->getCamp()->prepare("INSERT INTO ResAppel $strReq;");
				$req_insertResAppel->execute($resappel);
			}catch(PDOException $e){
				// Vérifie si le résultat à bien été sauvgardé
				$trace = debug_backtrace();
				trigger_error("Erreur dans la sauvegarde du resappel dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}
	}

	
	
	/**
	 * Envoye le prospect vers la phase 2
	 *
	*/
	public function exportToP2(){
		// Initialisation d'un tableau permetant le transport des données
		$prospect = [];
		
		// Récupère les informations de la phase 1
		try{
			$req_getProspectP1 = $this->dao->getCamp()->prepare("SELECT * FROM Prospect WHERE RefProspect = :ref;");
			$req_getProspectP1->execute(array(
				"ref" => $this->prospect_id
			));
		}catch(PDOException $e){
			// Vérifie si le prospect à bien été sauvgardé
			$trace = debug_backtrace();
			trigger_error("Erreur dans la récupération du prospect en phase 1 dans ".$trace[0]['file']."
					  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
			exit();
		}
		
		// Récupération de toutes les propriété pour l'insertion
		foreach($req_getProspectP1->fetch(PDO::FETCH_ASSOC) as $key => $prospectP1){
			$prospect[$key] = $prospectP1;
		}

		// Enlève les propriétés inutile pour la phase 2
		unset($prospect['ETACOM']);
		unset($prospect['BASECAMP']);
		
		if($this->checkExistP2()){
			
			// Enlève la propriété refprospect
			unset($prospect['RefProspect']);
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOUpdate($prospect);
			
			// Rajoute la propriété RefProspect
			$prospect['RefProspect'] = $this->prospect_id;
			
			// Insère les informations dans la phase 2
			try{
				$req_getProspectP2 = $this->dao->getCampP2()->prepare("UPDATE Prospect SET $strReq WHERE RefProspect = :RefProspect;");
				$req_getProspectP2->execute($prospect);
			}catch(PDOException $e){
				// Vérifie si le prospect à bien été inséré
				$trace = debug_backtrace();
				trigger_error("Erreur dans l'insertion du prospect en phase 2 dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}else{
			// Ajout des champs de Phase 2
			$prospect['DTEACC'] = $this->appel_date;
			$prospect['HOTACC'] = $this->agent_lib;
			$prospect['ETACOM'] = $this->resultat_etacom;
			
			// Liste des champs
			$strReq = $this->dao->keysToStrPDOInsert($prospect);
			
			// Insère les informations dans la phase 2
			try{
				$req_getProspectP2 = $this->dao->getCampP2()->prepare("INSERT INTO Prospect $strReq;");
				$req_getProspectP2->execute($prospect);
			}catch(PDOException $e){
				// Vérifie si le prospect à bien été inséré
				$trace = debug_backtrace();
				trigger_error("Erreur dans l'insertion du prospect en phase 2 dans ".$trace[0]['file']."
						  à la ligne ".$trace[0]['line'].". Erreur PDO : ".$e->getMessage().".", E_USER_NOTICE);
				exit();
			}
		}
		
		// Ajoute ou modifie un rappel en phase 2
		$this->saveRappelP2();
	}
	
}
?>