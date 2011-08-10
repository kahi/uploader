/**
 * Dialog
 * main functions and general behaviour of dialogs; specific events expected to be located elsewhere
 * @package Uploader
 * version 1.2
 */

/**
 * Create the dialog and append it into DOM.
 * @param string text/html, main message content
 * @param string text/html, buttons
 * @param string class(es) of the #dialog. Useful when specifying behaviour of buttons like $('#dialog.delete-file button#cancel').live('click', ...)
 */
function dialogConstruct (content, buttons, dclass) {
	
	if (dclass)
		dclass = ' class="'+dclass+'"';
	
	var dialog = '<div id="dialog"'+dclass+' style="display:none"><div class="content">'+content+'</div><div class="buttons">'+buttons+'</div></div>';
	
	$('body').append(dialog);

/*
example goal
*************
<div id="dialog" class="info" style="display:none">
	<div class="content">
		<p>Do you really want to do that (lorem ipsum dolor)?</p>
	</div>
	
	<div class="buttons">
		<button class="cancel">Close</button>
		<!--<button class="alert">Yes, you moron!</button>-->
	</div>
</div>
*/
}


/**
 * Show the dialog (that already exists in DOM (probably created by dialogConstruct();))
 */
function dialogShow() {
	
	$('body').append('<div id="wall" style="display:none;"></div>');
	$('#wall').fadeIn(50, function(){
		
		$('#dialog').show();
		$('#dialog .buttons button:last-child').focus();
		
	});

}

/**
 * Hide and destroy the dialog
 */
function dialogHide() {

	$('#wall').fadeOut(100);
	$('#dialog').fadeOut(100, function(){
			$('#dialog').hide();
			$('#wall').hide().remove();
			//$('#dialog').dialogCancel(); // fire binded functions
			$('#dialog').remove();
		});
 
}





$(document).ready(function(){


/**
 * Default behaviour: pressing Esc key in scope of dialog or wall -> fires the desctruction
 */
$('#dialog, #wall').live('keydown', function(e){
	if (e.which == 27) {// esc
		dialogHide();
		e.stopPropagation();
	}
});

/**
 * Default behaviour: pressing button.cancel -> fires the desctruction
 */
$('#dialog button.cancel').live('click', function(){
	dialogHide();	
});


}); // /document.ready




/**
 * Dialog's cancel event. Disabled for now. (I didn't succeed creating proper usage design/pattern.)
 */
/*jQuery.fn.extend({
	dialogCancel: function (fn) {  
		if (fn) {  
			return jQuery.event.add(this[0], "dialogCancel", fn, null);  
		} else {  
			var ret = jQuery.event.trigger("dialogCancel", null, this[0], false, null);  
 
			if (ret === undefined)  
				ret = true;  
  
			return ret;  
		}  
	}  
});
$('#dialog').dialogCancel(function(){
	// add your code - fired when the dialog is hiding
});*/