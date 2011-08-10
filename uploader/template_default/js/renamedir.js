/**
 * RenameDir functions
 * @package Uploader
 */

var rename_dir_running;

/**
 * Create text-field prefilled with current name
 */
function dlRenameStart(){
	
	running_action = 'dlRename'; // @todo unify
	rename_dir_running = true;
	
	$li = $('#dirlist li.active');
		
	var old_name = $li.attr('id').idToPath().filename();

	$li
		.addClass('in-action')
		.addClass('in-action-rename')
		.data('old_name', old_name)
		.find('> span').hide().end()
		.prepend('<input type="text" class="new_name" value="'+ old_name +'" />')
		.find('input.new_name').select();

}


/**
 * Remove the text-field, return the row back to normal state
 */
function dlRenameCancel(){
	
	running_action = ''; // @todo implement or kill
	
	var $li = $('#dirlist li.in-action-rename');
	
	$li
		.find('input[type="text"]').remove().end()
		.find('> span').show().end()
		.removeClass('in-action').removeClass('in-action-rename');

	dlRenameFinally();
}


/**
 * Sends AJAX request to rename the file and process following error/success
 *
 * @param string (relative) path to the original file
 * @param string new filename without extension
 */
function dlRenameSend(from, to_name){
	
	$.ajax({
		url: '?do=ajax/renameDir',
		type: 'post',
		data: ({from:from, to_name:to_name}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Attempt to rename directory failed. Please, try again, maybe later.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();
			
			if (data['status'] == 'error')
				renderError(data['data']);
			else {
				
				var new_name = data['data'];
				var $li = $('#dirlist li.in-action-rename');
				var new_id = ($li.attr('id').idToPath().dirname() + new_name).pathToId('dl');

				// hide input
				$li
					.attr('id', new_id)
					.find('> input')
						.remove().end()
					.find('> span')
						.text(new_name).show().end()
					.removeClass('in-action')
					.removeClass('in-action-rename')
					.removeData('old_name');					
				
				if ($li.find('li').length) 
					dlRebuild(new_id.idToPath()); // need to re-generate childrens' ids
				else
					dirActivate(new_id.idToPath());
				

				dlRenameFinally();
			}
			
		} // /success
	});
	
}

function dlRenameFinally(){
	$('#dl-controls .start-rename-dir').removeClass('active');
	rename_dir_running = false;
}

/**
 * Event: DL > li.in-action-rename > input > click [=> do not propagate]
 * Event: DL > li.in-action-RENAME > Key Enter/Esc 
 */
$('#dirlist li.in-action-rename input').live('click', function(e){
	return false;
	
}).live('keydown', function(e){

	// Esc
	if (e.which == 27) {
		dlRenameCancel();

	// Enter
	} else if (e.which == 13) {

		var new_name = $(this).val();
		
		if (new_name == $(this).closest('li').data('old_name'))
			dlRenameCancel();
		else if (new_name.trim())
			dlRenameSend($(this).closest('li').attr('id').idToPath(), new_name);
		
		return false;
	}

});
