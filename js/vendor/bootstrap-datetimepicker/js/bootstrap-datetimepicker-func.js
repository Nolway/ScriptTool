	// Set des options du datetime picker
	$('.form_datetime').datetimepicker({
      language:  'fr',
		format: 'dd/mm/yyyy hh:'+ (new Date().getMinutes() < 10 ? "0" + new Date().getMinutes() : new Date().getMinutes()),
      weekStart: 1,
		daysOfWeekDisabled: ['0','6'],
		hoursDisabled: '0,1,2,3,4,5,6,7,19,20,21,22,23',
		autoclose: 1,
		todayHighlight: 1,
		pickerPosition: "top-left",
		startView: 2,
		minView:1,
		maxView: 3,
		startDate: new Date(),
		forceParse: 0,
      showMeridian: 0
    });
	
	
	// EVENEMENT : Si le datetime picker est caché
	$('.form_datetime').datetimepicker().on('hide', function(ev){
		setDateHeureRappelManuel();
	});
	
	
	// Set des options du datetime picker
	$('.rapmanuel_datetime').datetimepicker({
      language:  'fr',
		format: 'dd/mm/yyyy hh:'+ (new Date().getMinutes() < 10 ? "0" + new Date().getMinutes() : new Date().getMinutes()),
      weekStart: 1,
		daysOfWeekDisabled: ['0','6'],
		hoursDisabled: '0,1,2,3,4,5,6,7,19,20,21,22,23',
		autoclose: 1,
		todayHighlight: 1,
		pickerPosition: "top-left",
		startView: 2,
		minView:1,
		maxView: 3,
		startDate: new Date(),
		forceParse: 0,
      showMeridian: 0
    });
	
	
	// EVENEMENT : Si le datetime picker est caché
	$('.rapmanuel_datetime').datetimepicker().on('hide', function(ev){
		setDateHeureRappelManuel();
	});
	
	// Set des options du datetime picker
	$('.dtnaiss_datetime').datetimepicker({
      language:  'fr',
		format: 'dd/mm/yyyy hh:'+ (new Date().getMinutes() < 10 ? "0" + new Date().getMinutes() : new Date().getMinutes()),
      weekStart: 1,
		daysOfWeekDisabled: ['0','6'],
		hoursDisabled: '0,1,2,3,4,5,6,7,19,20,21,22,23',
		autoclose: 1,
		todayHighlight: 1,
		pickerPosition: "top-left",
		startView: 2,
		minView:1,
		maxView: 3,
		startDate: new Date(),
		forceParse: 0,
      showMeridian: 0
    });
	
	
	// EVENEMENT : Si le datetime picker est caché
	$('.dtnaiss_datetime').datetimepicker().on('hide', function(ev){
		setDateHeureRappelManuel();
	});
	
	
	// Set la date et l'heure de l'appel manuel
	function setDateHeureRappelManuel(){
		let datetime = document.getElementById('rap_dateheurerappelmanuel').value;
		let datetimeSplit = datetime.split(" ");
		document.getElementById('rap_daterappelmanuel').value = datetimeSplit['0'];
		document.getElementById('rap_heurerappelmanuel').value = datetimeSplit['1'];
	}