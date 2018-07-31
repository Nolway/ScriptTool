<?php

require_once __DIR__.'/DAO.php';

class Opening{
    
    // Informations envoyées en GET et autre informations du dictionnaire
	private $infos;
    
    // Accés au base de données P1, P2 et Admin Elastix
	private $dao;
    
	/**
	 * Initialisation de la classe Opening
	 * 
	 * @param array $infos Tableau des propriétés reçu du $_GET
	*/
	public function __construct(array $infos){
		$this->infos = $infos;
		$this->checkInfos();
		$this->dao = new DAO($this->serveur_id, $this->campagne_id);
		$this->getProspect();
		$this->getAgent();
		$this->getPseudo();
		$this->defaultResultat();
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
				   "appel_id", "appel_uniqueid", "appel_tel"];
		
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
		$req_getProspect = $this->dao->getCamp()->prepare('SELECT * FROM Prospect WHERE RefProspect = :ref;');
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
		$req_getAgent = $this->dao->getCamp()->prepare('SELECT TEAM, AGENT, LIBAGENT, SEXE, TYPE FROM AGENTS WHERE ID_AGENT = :id;');
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
     * Récupère le pseudo
    */
    private function getPseudo(){
        // Count des itérations
		$i = 0;
		
		// Récupère les infos
		$req_getPseudo = $this->dao->getAdmin()->prepare('SELECT CONSEILLER FROM PSEUDOS WHERE ID_CAMP = :camp_id AND SERVEUR = :serveur_id AND SEXE = :sexe;');
		$req_getPseudo->execute(array(
			"serveur_id" => $this->serveur_id,
			"camp_id" => $this->campagne_id,
            "sexe" => $this->agent_sexe
		));
        
        foreach($req_getPseudo AS $pseudo){
			// Set la nouvelle propriété
			$this->agent_pseudo = $pseudo['CONSEILLER'];
			$i++;
		}
		
		// Vérifie si l'agent à bien été récupéré
		if($i === 0){
			$trace = debug_backtrace();
			trigger_error("Aucun pseudo trouvé pour le campagne ".$this->campagne_id." et pour le sexe ".$this->agent_sexe, E_USER_NOTICE);
			exit();
		}
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
		$req_getResAppel = $this->dao->getCampP2()->prepare('SELECT RefProspect FROM Prospect WHERE RefProspect = :ref');
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
     * Donne l'âge d'une date de naissance
     *
     * @param string $dte_naiss Date de naissance
     *
     * @return int L'age si erreur 0
    */
    public function getAge(string $dte_naiss){
		if(strtotime(str_replace('/', '-', $dte_naiss))){
			$arr1 = explode('/', $dte_naiss);
			$arr2 = explode('/', date('d/m/Y'));
				
			if(($arr1[1] < $arr2[1]) || (($arr1[1] == $arr2[1]) && ($arr1[0] <= $arr2[0])))
			return $arr2[2] - $arr1[2];
		
			return $arr2[2] - $arr1[2] - 1;
		}else{
			return 0;
		}
	}
	
	/**
	 * Crée une information de base 
	 *
	 * @param array $save Tableau contenant en clé le nom du
	 * champ dans la base et en valeur la valeur à insérer
	*/
	private function defaultResultat(){
		// Count des itérations
		$i = 0;
		
		// Ajout des propriétés obligatoires
		$save = array(
			'RefAppel' => $this->appel_id,
			'HOTESSE' => $this->agent_lib,
			'DATEAPPEL' => date("d/m/Y"),
			'HEUREAPPEL' => date("H:i:s"),
			'RefQualif' => 0,
			'RefCateg' => 0
		);
		
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
				"REFAPPEL" => $this->appel_id,
				"HOTESSE" => $this->agent_lib,
				"IDAGENT" => $this->agent_id,
				"DATEAPPEL" => date("d/m/Y"),
				"HEUREAPPEL" => date("H:i:s"),
				"REFQUALIF" => 0
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
				"DATEAPPEL" => date("d/m/Y"),
				"HEUREAPPEL" => date("H:i:s"),
				"REFQUALIF" => 0,
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
    
}
?>