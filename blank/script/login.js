// do login on enter
var defButton = true;
document.onkeypress = keyPress;
function keyPress(e) {
	if (defButton) {
		var myKeyCode = document.all ? event.keyCode : e.which ? e.which : e.keyCode ? e.keyCode : e.charcode;
		if (myKeyCode == 13)
			reactOnKeyPress();
	}
	else {
		defButton = true;
	}
}
function capEvent() {
	if (document.layers) {
		document.captureEvents(Event.KEYPRESS);
		document.onkeypress = keyPress;
	}
}

function reactOnKeyPress() {
	submitAction('dologin');
}
