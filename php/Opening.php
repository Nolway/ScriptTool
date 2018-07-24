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
    
}
?>