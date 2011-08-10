/**
 * addDir functions
 * @package Uploader
 */

/**
 * var bool
 * Creation process status 
 * @todo check constistency with other process-statuses, maybe create handler-functions and array of actions inst. of...
 */  
var add_dir_running = false;


/**
 * Create text-field one level deeper than the currently active directory.
 */
function dlAddDirStart() {
	
	// if still in creation process, cancel it
	if (add_dir_running) {
		$('#controls .func-new-dir').removeClass('active');
		dlAddDirCancel();		
	}
	
	add_dir_running = true;
	
	$('#controls .func-new-dir').addClass('active'); // @todo 
	
	// get active directory
	var $active = $('#dirlist li.active');
	var path = $active.attr('id').idToPath();

	// create form/input field after its children
	if (!$active.children('ul').size()) {
		$active.append('<ul></ul>');
	}

	$active.children('ul').prepend('<li id="new-dir"><input type="text" name="name" /></li>');
	$('#new-dir input[type=text]').attr('value','New Folder').focus().select();

}


/**
 * Hide the text-field for new directory name input.
 */
function dlAddDirCancel() {
	$('#new-dir').slideUp('500', function() {
		$('#new-dir').remove();
		dlAddDirFinally();
	});
}


/**
 * Clean things up.
 */
function dlAddDirFinally() {

	$('#new-dir').removeAttr('id');
	$('#controls .func-new-dir').removeClass('active');
	add_dir_running = false;

}


/**
 * 
 */
function dlAddDirSend (path, name) {
	
	$.ajax({
		url: '?do=ajax/adddir',
		type: 'post',
		data: ({path:path, name:name}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Attempt to add directory failed. Please, try again, maybe later.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();
			
			if (data['status'] == 'error')
				renderError(data['data']);
			else {
				
				var new_dir_name = data['data'];
				var $new = $('#new-dir');
				
				$new
					.find('input[type=text]')
						.remove().end()
					.append('<span>' + new_dir_name + '</span>')
					.attr('id', ($new.parents('li').attr('id').idToPath() +'/'+ new_dir_name).pathToId('dl'))
					.parents('li:eq(0)').addClass('open');
							
			}
			
			dlAddDirFinally();
			rebindDragAndDropStuff();
			
		} // /success
	});

}


$(document).ready(function(){
	
/**
 * Event: DL > #new-dir > click
 * Event: DL > #new-dir > Key Enter/Esc
 */
$('#new-dir').live('click', function(e){
	e.stopPropagation();
	return false; // @test

}).live('keydown', function(e){

	// Esc
	if (e.which == 27) {
		dlAddDirCancel();

	// Enter
	} else if (e.which == 13) {
		var new_name = $(this).children('input[type=text]').val();

		if (new_name.trim())
			dlAddDirSend($(this).parents('li').attr('id').idToPath(), new_name);

		return false;
	}

});
		
}); // document.ready