<?php

require_once __DIR__.'/../../config.php';


class DAO{
	private $db_camp;
    private $db_campP2;
    private $db_admin;
	private $db_savprod;
	private $db_webphone;
	
	/**
	 * Initialisation de la classe
	 *
	 * @param int $id_camp Id de la campagne
	*/
	public function __construct($id_server, $id_camp){
		$this->loadAdmin();
		$this->loadWebphone();
		$this->loadSavProd();
        $this->db_camp = $this->loadCamp($id_server, $id_camp);
    }
	
	/**
	 * Charge la base Admin d'Elastix
	*/
	private function loadAdmin(){
        $mdbFilename = BASES_PATH."adminElastix.mdb";
		if (!file_exists($mdbFilename)) {
			die("La base de donnée admin elastix n'a pas été trouvée !");
		}
        $user = "";
        $password = "";
        $this->db_admin = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$mdbFilename; Uid=$user; Pwd=$password;");
		$this->db_admin->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
	
	/**
	 * Charge la base SavProd
	*/
	private function loadSavProd(){
        $mdbFilename = BASES_PATH."admin/savProd.mdb";
		if (!file_exists($mdbFilename)) {
			die("La base de donnée de sauvgarde de la production n'a pas été trouvée !");
		}
        $user = "";
        $password = "";
        $this->db_savprod = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$mdbFilename; Uid=$user; Pwd=$password;");
		$this->db_savprod->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
	
	/**
	 * Charge la base du Webphone
	*/
	private function loadWebphone(){
		$host = "1.30.0.1";
		$user = "root";
		$pass = "Denys2001";
		$db = "webphone";
        $this->db_webphone = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
		$this->db_webphone->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
	
	
	/**
	 * Charge la base de la campagne et de la campagne phase 2 si existe
	 *
	 * @param int $id Id de la campagne
	 *
	 * @return Un objet PDO
	*/
	private function loadCamp($server, $id){
		
		$db_camp_name = "";
		$db_phase2 = "";
		$db_camp;
		
        // Récupère le nom fichier de la base
        $req_camp = "SELECT DB_NAME, PHASE2, PHASE2_SERVEUR FROM CAMPAGNES WHERE id = :camp_id AND SERVEUR = :server";
        $result = $this->db_admin->prepare($req_camp);
        $result->execute(array(
            "camp_id" => $id,
			"server" => $server
        ));

        foreach($result AS $row){
            $db_camp_name = $row['DB_NAME'];
			$db_phase2 = $row['PHASE2'];
			$db_phase2_serveur = $row['PHASE2_SERVEUR'];
            break;
        }

        $mdbFilename = BASES_PATH.$db_camp_name.".mdb";
        $user = "";
        $password = "";
        $db_camp = new PDO("odbc:DRIVER={Microsoft Access Driver (*.mdb)}; DBQ=$mdbFilename; Uid=$user; Pwd=$password;");
		$db_camp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		// Set la campagne de phase 2
		if($db_phase2 != 0){
			$this->db_campP2 = $this->loadCamp($db_phase2_serveur, $db_phase2);
		}
		
		return $db_camp;
	}
	
	public function getCamp(){
		return $this->db_camp;
	}
	
	public function getCampP2(){
		return $this->db_campP2;
	}
	
	public function getAdmin(){
		return $this->db_admin;
	}
	
	public function getSavProd(){
		return $this->db_savprod;
	}
	
	public function getWebphone(){
		return $this->db_webphone;
	}
	
	/**
	 * Transforme les clés d'un tableau associatif en string pour les requêtes SQL UPDATE en PDO
	 *
	 * @param array $tab Tableau associatif
	 *
	 * @return string Clés du tableau en string
	*/
	public function keysToStrPDOUpdate(array $tab){
		$str = "";
		
		foreach($tab as $key => $val){
			$str = $str === "" ? $key." = :".$key : $str.", ".$key." = :".$key;
		}
		
		return $str;
	}
	
	/**
	 * Transforme les clés d'un tableau associatif en string pour les requêtes SQL INSERT en PDO
	 *
	 * @param array $tab Tableau associatif
	 *
	 * @return string Clés du tableau en string
	*/
	public function keysToStrPDOInsert(array $tab){
		$str = "";
		$strFields = "";
		$strVals = "";
		
		foreach($tab as $key => $val){
			$strFields = $strFields === "" ? $key : $strFields.", ".$key;
			$strVals = $strVals === "" ? ":".$key : $strVals.", :".$key;
			
		}
		
		$str = "(".$strFields.") VALUES (".$strVals.")";
		
		return $str;
	}
}