
// Fonction permettant d'afficher le temps de conversation

var myTimer;
var minutes;
var seconds;

function checkTime(i) {
    if (i < 10) {i = "0" + i};  // add zero in front of numbers < 10
    return i;
}

function startRecord(agent_id, prospect_nom, campagne_id, prospect_id, appel_id, appel_uniqueid){
    let arr = new Array();
    arr.prospect_nom = prospect_nom;
    arr.campagne_id = campagne_id;
    arr.callid = appel_id;
    arr.uniqueid = appel_uniqueid;
    arr.refprospect = prospect_id;
    arr.agent_id = agent_id;
    
    // Envoi au serveur de démarrer l'enregistrement
	socket.emit('script_recorder_start', arr);
    
    document.getElementById('timerecorder').innerHTML = "00:00" ;
    timer = 1;
    myTimer = setInterval(function () {
        minutes = parseInt(timer / 60, 10);
        seconds = parseInt(timer % 60, 10);
        minutes = checkTime(minutes);
        seconds = checkTime(seconds);
        document.getElementById('timerecorder').innerHTML = minutes + ":" + seconds;
        if (++timer < 0){
            timer = 0;
        }
    }, 1000);
    document.getElementById('recorder_on').id = 'recorder_off';
    document.getElementById('recorder_off').setAttribute('onclick','');
    document.getElementById('titrerecorder_off').id = 'titrerecorder_on';
    setTimeout(function(){
        document.getElementById('stoprecorder_off').id = 'stoprecorder_on';
        document.getElementById('stoprecorder_on').setAttribute('onclick','stopRecord(\''+ agent_id +'\', \''+ prospect_nom +'\', \''+ campagne_id +'\', \''+ prospect_id +'\', \''+ appel_id +'\', \''+ appel_uniqueid +'\');');
    }, 5000);
}

function stopRecord(agent_id, prospect_nom, campagne_id, prospect_id, appel_id, appel_uniqueid){
	clearInterval(myTimer);
    
    let arr = new Array();
    arr.prospect_nom = prospect_nom;
    arr.campagne_id = campagne_id;
    arr.callid = appel_id;
    arr.uniqueid = appel_uniqueid;
    arr.refprospect = prospect_id;
    arr.agent_id = agent_id;
    
    // Envoi au serveur d'arrêter l'enregistrement
	socket.emit('script_recorder_stop', arr);
	
    document.getElementById('timerecorder').innerHTML = minutes + ":" + seconds;
    document.getElementById('recorder_off').id = 'recorder_on';
    document.getElementById('recorder_on').setAttribute('onclick','setRecording(\''+ agent_id +'\', \''+ prospect_nom +'\', \''+ campagne_id +'\', \''+ prospect_id +'\', \''+ appel_id +'\', \''+ appel_uniqueid +'\');disableSaveData();');
    document.getElementById('stoprecorder_on').id = 'stoprecorder_off';
    document.getElementById('titrerecorder_on').id = 'titrerecorder_off';
    document.getElementById('stoprecorder_off').setAttribute('onclick','');
    
    // INDISPENSABLE : attributions pour "restaurer" le bouton de qualification en fin de script
    document.getElementById('_save_data').id = "save_data";
    document.getElementById('save_data').disabled = false;
    document.getElementById('save_data').value = "QUALIFICATION";
}

// Désactive le bouton d'enregistrement des données pendant que l'enregistrement est en cours
function disableSaveData(){
  document.getElementById('save_data').id = "_save_data";
  document.getElementById('_save_data').disabled = true;
  document.getElementById('_save_data').value = "/!\\ ARRETER L'ENREGISTREMENT AVANT DE QUALIFIER /!\\";
}
