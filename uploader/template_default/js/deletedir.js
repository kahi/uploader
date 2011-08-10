/**
 * DeleteDir functions
 * @package Uploader
 */


/**
 * Prepare and show the delete dialog.
 * @param element relevat row of file-list table
 */
function dlDeleteStart(){
	var $item = $('#dirlist li.active');
	dialogConstruct(
		'<p>Do you really wish to delete folder named <em><strong>'+$item.attr('id').idToPath().filename()+'</strong></em> and all its contents?</p>',
		'<button class="cancel" id="do0">No, keep it</button><button class="alert" id="do1">Delete it</button>',
		'deletedir');

	dialogShow();
}

/**
 * Makes AJAX request to delete the file and processes following error/success
 * @param string (relative) path to file 
 */
function dlDeleteSend (path){

	$.ajax({
		url: '?do=ajax/deleteDir',
		type: 'post',
		data: ({path:path}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Folder deletion failed. Please, try again.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();

			if (data['status'] == 'error') {
				renderError(data['data']);
			} else {
				renderMessage('Folder <em>'+path.filename()+'</em> was deleted.');
				
				var $active = $('#dirlist li.active');
				var $parent = $active.parents('li').eq(0);

				$active.fadeOut('500', function (){
					$(this).remove();
					
					dirActivate($parent.attr('id').idToPath());
					
					if (!$parent.find('li').length) {
						$parent.removeClass('open');
					}
				
				});
				
			}

		} // /success
	});

}


/**
 * Event: delete-dialog > submit [=> Send]
 */
$('#dialog.deletedir #do1').live('click', function(){
	dialogHide();
	dlDeleteSend($('#dirlist li.active').attr('id').idToPath());
});