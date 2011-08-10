/**
 * DeleteFile functions
 * @package Uploader
 */


/**
 * Prepare and show the delete dialog.
 * @param element relevat row of file-list table
 */
function flDeleteStart($tr){
	dialogConstruct(
		'<p>Do you really wish to delete file <em>'+$tr.attr('id').idToPath().filename()+'</em>?</p>',
		'<button class="cancel" id="do0">No, keep it</button><button class="alert" id="do1">Delete it</button>',
		'deletefile');

	dialogShow();
}

/**
 * Makes AJAX request to delete the file and processes following error/success
 * @param string (relative) path to file 
 */
function flDeleteSend (path){

	$.ajax({
		url: '?do=ajax/deleteFile',
		type: 'post',
		data: ({path:path}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('File deletion failed. Please, try again.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();

			if (data['status'] == 'error') {
				renderError(data['data']);
			} else {
				renderMessage('File <em>'+path.filename()+'</em> was deleted.');
				//flDeleteFinally(true);
				$('#filelist tr.active2').fadeOut('500', function (){$(this).remove();});
			}

		} // /success
	});

}

/**
 * @note Not used. Delete? @maybe
 */
function flDeleteFinally (success) {

	if (success) {
		
	}

}