/**
 * RenameFile functions
 * @package Uploader
 */


/**
 * Create text-field prefilled with current filename
 * @param element row of file-list table
 */
function flRenameStart($tr){
	
	running_action = 'flRename';
	$tr.addClass('active');
	
	// hide options
	$tr.find('.options').slideUp('fast', function() {
		$tr.removeClass('options-on');
		$(document).optionsHide();
	});
	
	// transform name into input:text
	var old_name = $tr.attr('id').idToPath().filename();
	old_name = old_name.substr(0, old_name.lastIndexOf('.'));
	
	$tr.data('filename_no_ext', old_name);

	$tr
		.find('td.name a').hide().end()
		.find('td.name').append('<input type="text" class="rename_to_namecore" value="'+ old_name +'" />')
			.find('input.rename_to_namecore').select();
	
	// @maybe disable everything else (lightbox-like focus on this row)
	
}


/**
 * Remove the text-field, return the row back to normal state
 */
function flRenameCancel(){
	
	running_action = '';
	var $tr = $('#filelist tr.active');
	
	$tr
		.find('td.name input[type="text"]').remove().end()
		.find('td.name a').show().end()
		.removeClass('active');
	
}


/**
 * Sends AJAX request to rename the file and process following error/success
 *
 * @param string (relative) path to the original file
 * @param string new filename without extension
 */
function flRenameSend(from, to_namecore){
	
	$.ajax({
		url: '?do=ajax/renameFile',
		type: 'post',
		data: ({from:from, to_namecore:to_namecore}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('File rename failed. Please, try again, maybe later.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();
			
			if (data['status'] == 'error')
				renderError(data['data']);
			else {
				
				var new_name = data['data'];
				
				// hide input
				$('#filelist tr.active input.rename_to_namecore').hide();
				
				// update link text + href, then show it
				var $a = $('#filelist tr.active td.name a');
				
				$a.text(new_name);

				var dir_url = $a.attr('href');
				dir_url = dir_url.substr(0, dir_url.lastIndexOf('/')+1);
				
				$a.attr('href', dir_url + data['data']).show();
				
				// modify tr.id (path)
				var old_id = $a.parents('tr').attr('id');
				
				$a.parents('tr').attr('id', (old_id.idToPath().dirname() + new_name).pathToId('fl'));
				
				$('#filelist tr.active2').removeClass('active2');
				$a.parents('tr').removeClass('active').addClass('active2');
								
			}
			
		} // /success
	});
	
}