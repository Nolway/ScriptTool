class Script{
    
    /**
     * Initialise la classe Script
     *
     * @param boolean p2 Vrai si le script possède une phase 2
     * @param string curdte Date heure du serveur PHP
    */
    constructor(p2, curdte){
        this.p2 = p2;
        this.curdte = curdte * 1000;
    }
    
    /**
     * Désactive l'obligation de saisir un motif de refus
    */
    resetMotifRef(){
        this.unRequiredField(document.getElementById('res_motifrefus'));
        document.getElementById('res_motifrefus').options[0].selected = true;
    }
    
    /**
     * Oblige la saisie d'un champs et le met en rouge
    */
    requiredField(field){
        field.required = true;
        field.style.background = "pink";
    }
    
    /*
     * Supprime l'obligation la saisie d'un champs et le met en blanc
    */
    unRequiredField(field){
        field.required = false;
        field.style.background = "white";
    }
    
    /**
     * Vérifie si date de naissance est inférieure à son max
     * si non alors on affiche un message dans un span
     *
     * @param input field Champs age à vérifier
     * @param string date Date de naissance
     * @param int ageMax Age maximum
     * @param span error Balise qui affiche une erreur
    */
    checkAgeMax(field, ageMax, error){
        if(this.getAge(field.value) <= ageMax){
            field.style.background = "white";
            error.style.display = "none";
        }else{
            field.style.background = "pink";
            error.style.display = "block";
        }
    }
    
    /**
     * Récupère l'age depuis une date de naissance
     *
     * @param string dteNaiss Date de naissance jj/mm/aaaa
     *
     * @return int Age
    */
    getAge(dteNaiss){
        let dateArr = dteNaiss.split('/');
        let today = new Date(this.curdte);
        let birthDate = new Date(dateArr[2]);
        let age = today.getFullYear() - birthDate.getFullYear();
        return age;
    }
    
    /**
     * Vérifie qu'une conclusion a bien été renseignée
     */
	checkRadio() {
		// ON VERIFIE QU'AU MOINS UNE CONCLUSION A ETE COCHEE
		if(($('input[name=res_conclusion]:checked').val() || '') !== ''){
            
            switch($('input[name=res_conclusion]:checked').val()){
                case "ACCORD":
                    if(this.p2){
                        // Crée et écrit la date et l'heure de rappel en phase 2 sur le script
                        let dteP2 = new Date(this.curdte);
                        dteP2.setDate(dteP2.getDate()+7);
                        dteP2 = this.formatDate(dteP2);
                        document.getElementById('res_p2_date').value = dteP2;
                        
                        let hP2 = new Date(this.curdte);
                        hP2.getHours(hP2.setHours(9));
                        hP2.getMinutes(hP2.setMinutes(0));
                        hP2.getSeconds(hP2.setSeconds(0));
                        hP2 = this.formatHour(hP2);
                        document.getElementById('res_p2_heure').value = hP2;
                        
                        document.getElementById('res_p2_timestamp').value = this.setTimestamp(dteP2, hP2);
                    }
                    break;
                case "RAPPEL AUTO":
                case "REPONDEUR":
                    // Crée et écrit la date et l'heure du rappel
                    let dteRappelAuto = new Date(this.curdte);
                    dteRappelAuto.setDate(dteRappelAuto.getDate()+1);
                    dteRappelAuto = this.formatDate(dteRappelAuto);
                    document.getElementById('res_rappel_date').value = dteRappelAuto;

                    let hRappelAuto = new Date(this.curdte);
                    hRappelAuto.getHours(hRappelAuto.setHours(9));
                    hRappelAuto.getMinutes(hRappelAuto.setMinutes(0));
                    hRappelAuto.getSeconds(hRappelAuto.setSeconds(0));
                    hRappelAuto = this.formatHour(hRappelAuto);
                    document.getElementById('res_rappel_heure').value = hRappelAuto;
                    
                    document.getElementById('res_rappel_timestamp').value = this.setTimestamp(dteRappelAuto, hRappelAuto);
                    break;
                case "RAPPEL MANUEL":
					let date = document.getElementById('res_rappel_date').value;
					let hour = document.getElementById('res_rappel_heure').value;
                    // On vérifie pour l'appel manuel qu'une heure et une date on bien été renseignée
                    // SI CE N'EST PAS LE CAS, ON BLOQUE LA CONCLUSION
                    if(date === '' || hour === ''){
                        alert("Rappel manuel : veuillez renseigner une date et une heure de rappel");
                        return false;
                    }
					document.getElementById('res_rappel_timestamp').value = this.setTimestamp(date, hour);
                    break;
            }
			
		}else{
			alert("Veuillez renseigner une conclusion !");
			return false;
		}
	}
    
    /**
     * Sauvgarde la date et l'heure d'un rappel manuel
     *
     * @param input field Champs où doit apparaitre la date en "jj/mm/aaaa hh:mm:ss"
    */
    setRapTimeMan(field){
        let rapArr = field.value.split(' ');
        document.getElementById('res_rappel_date').value = rapArr[0];
        document.getElementById('res_rappel_heure').value = rapArr[1]+":00";
        document.getElementById('res_rappel_timestamp').value = this.setTimestamp(rapArr[0], rapArr[1]);
    }
    
    /**
     * Créé un timestamp avec une date et une heure
     *
     * @param string vDate Date
     * @param string vHeure Heure
     *
     * @returns int Timestamp
    */
	setTimestamp(vDate, vHeure) {
        let dateParts = vDate.split('/');
        let timeParts = vHeure.split(':');
		
		// ON CREE UNE NOUVELLE DATE AVEC LES INFORMATIONS RECUPEREES POUR LE PASSER EN TIMESTAMP
		let vTimestampRappel = new Date(dateParts[2], parseInt(dateParts[1], 10) - 1, dateParts[0], timeParts[0], timeParts[1]);
		vTimestampRappel = (vTimestampRappel.getTime())/1000;
		return vTimestampRappel;
	}
    
    /**
     * Fonction permettant d'afficher l'heure
    */
	afficheHeure() {
        this.script.curdte += 500;
		let today = new Date(this.script.curdte);
		let h = today.getHours();
		let m = today.getMinutes();
		let s = today.getSeconds();
		h = this.checkTime(h);
		m = this.checkTime(m);
		s = this.checkTime(s);
		document.getElementById('hnow').innerHTML = h + ":" + m + ":" + s;
	}
    
    /**
     * Rajoute un 0 au valeurs de temps à 1 digit
     *
     * @param int i Valeur temps
     *
     * @return Valeur temps avec 0 si < 10
    */ 
	checkTime(i) {
		if (i < 10) {
		  i = "0" + i;
		}
		return i;
	}
    
    /**
     * Affiche le temps de conversation
    */
	startTimer() {
		var timer = 1;
		setInterval(function () {
			var minutes = parseInt(timer / 60, 10);
			var seconds = parseInt(timer % 60, 10);
			minutes = this.checkTime(minutes);
			seconds = this.checkTime(seconds);
			document.getElementById('tpsConversation').innerHTML = minutes + ":" + seconds;
			if (++timer < 0) {
				timer = 0;
			}
		}, 1000);
	}
    
    /**
     * Permet de formater une date
     *
     * @param date pDate Date à formater
     *
     * @returns string Date formaté jj/mm/aaaa
    */
    formatDate(pDate){
        // Récupére le jour
        let dateJ = this.checkTime(pDate.getDate());
        
        // Récupère le mois
        let dateM = this.checkTime(pDate.getMonth()+1);
        
        // Récupère l'année
        let dateY = pDate.getFullYear();
        
        return dateJ+"/"+dateM+"/"+dateY;
    }
    
    /**
     * Permet de formater une heure
     *
     * @param date pDate Date à formater
     *
     * @returns string Heure formaté hh:mm:ss
    */
    formatHour(pDate){
        // Récupère les heures
        let dateH = this.checkTime(pDate.getHours());
        
        // Récupére les minutes
        let dateN = this.checkTime(pDate.getMinutes());
        
        // Récupère les secondes
        let dateS = this.checkTime(pDate.getSeconds());
        
        return dateH+":"+dateN+":"+dateS;
    }
    
    /**
     * Ecrit la date/heure/timestamp du jour dans le script
    */
    dateNowToScript(){
        let now = new Date(this.curdte);
        let dteAppel = this.formatDate(now);
        let hAppel = this.formatHour(now);
        let timestampAppel = Math.round(now.getTime()/1000);
        
        document.getElementById('res_appel_date').value = dteAppel;
		document.getElementById('res_appel_heure').value = hAppel;
        document.getElementById('res_appel_timestamp').value = timestampAppel;
    }
    
    /**
     * Actionne la validation du répondeur
    */
    getRepondeur(){
		document.getElementById('concl_repond').checked = true;
		this.resetMotifRef();
		document.getElementById('res_contact').value += " /REP";
		document.getElementById("save_data").click();
	}
    
}