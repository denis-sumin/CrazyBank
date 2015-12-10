var focus_id='';

function setfocus (id) {
	focus_id = id;
}

function InvertCheckboxes (str) {
	temp = document.getElementById(str).elements.length ;

	for (i=0; i < temp; i++) {
		if ( document.getElementById(str).elements[i].checked == 1 ) { document.getElementById(str).elements[i].checked = 0; }
		else { document.getElementById(str).elements[i].checked = 1 }
	}
}

function account(id) {
	if ( document.getElementById('account_id') == undefined ) {
		document.getElementById(focus_id).value=id;
		document.getElementById(focus_id).focus();
		focus_id='';
	}
	else document.getElementById('account_id').value=id;
}

function highlight(id) {
	document.getElementById(id).style.background = "#eee";
}
function nohighlight(id) {
	document.getElementById(id).style.background = "white";
}
function viewInfo(id) {
	location.href = "?action=account_info&account_id="+id+"&fromlist";
}
