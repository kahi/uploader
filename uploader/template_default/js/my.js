/**
 * Main functions and events handlings
 * @package Uploader
 * @all the honours Peter Kahoun aka Kahi, the man of many faces and master of various arts
 */


/*

QuickHelp
#########

Paths
*****
- Path = files/shapes/red
- Path stored in ID = upl_sth_files___shapes___red
- Path '' (empty string) often stands for the root (user-data) folder

AJAX/JSON
*********
- JSON response is usually an array containing two keys: status, data
  - if status = 'error' then data is string error message
  - if status = 'data' the data is often an array of... some data

Usual action structure
**********************
1) user triggers action that fires...
2) sthActionStart() - which makes all the visual changes in order to initialize action (if necessary). Might offer cancel/confirm options. (example: delete dialog)
3) user triggers confirms or cancels the action... (for any sub-action might exist more triggers, like a button on the screen and a keyboard shortcut)
	3a) sthActionCancel() - returns default state of things (example: hides the dialog window)
	3b) sthActionSend() - makes ajax call and handles both success and error ()
		4) after finishing often returns some kind of notification

*/


/**
 * Removes spaces from the beginning and the end of string
 */
String.prototype.trim = function(){
   return this.replace(/^\s+|\s+$/g, '');
}

/**
 * Holds information about currently running process
 * values: flRename | flDelete | etc.
 * @todo isn't fully implemented
 */
var running_action = false;


/**
 * Removes 'upl_[a-z-etc]_' prefix from the string => it's a path
 */
String.prototype.idToPath = function () {
	return this.replace(/^upl_[a-z]+_+/,'').replace('___', '/', "g");
}


/**
 * Returns string usable as value of an id attribute
 * @param string specifier necessary to keep uniquity of IDs in document. Example: 'whatever21'
 */
String.prototype.pathToId = function (specifier) {
	var semiresult = this.replace(/^\//g, ''); // ltrim \
	return 'upl_' + specifier + '_' + semiresult.replace('/', '___', "g");
}


/**
 * Returns string. Input is supposed to be path (dirs separated by /), output the parental dir. Equivalent of same-named function in PHP.
 */
String.prototype.dirname = function () {
	if (this.lastIndexOf('/') === false)
		return '';
	else
		return this.substr(0, this.lastIndexOf('/')+1);
}


/**
 * Returns last fragment of input, which is supposed to be a path (dirs separated by /).
 */
String.prototype.filename = function () {
	_this = this.replace(/\/+$/, '');

	if (_this.lastIndexOf('/') === false)
		return _this;
	else
		return _this.substr(_this.lastIndexOf('/')+1);
}

/**
 * Highlights element. For debugging purposes.
 * Returns void
 */
$.extend($.fn, {
	hl: function() {
		$(this).css('border', '2px dotted red');
	}
});




/**
 * Specific cookie wrapper
 */
function rememberActiveDir (path){
	$.cookie('upl_active_dir', path, {path:'/', expires:365});
}

// --------------------------
// --------------------------
$(document).ready(function(){


/**
 * Buttons: appends HTML into each .button. Simplifies template code.
 */
$('.button').append('<span class="icon"></span>');
$('.button').not('[tabindex]').attr('tabindex', '0');


/**
 * Buttons: .button.switch functionality
 */
$('.button.switch').click(function() {

	if ($(this).is('active')) {
		return false;
	} else {
		$(this).siblings('.active').removeClass('active');
		$(this).addClass('active');
		// blur?
	}

});


/**
 * Buttons: .button.keeper functionality
 */
$('.button.keeper').click(function() {
	$(this).toggleClass('active');
});


}); // document.ready




// --------------------------
// --------------------------
// Above all

$(document).ready(function(){

	// onload --> activate recently used directory or root
	var active_dir = ($.cookie('upl_active_dir')) ? $.cookie('upl_active_dir') : '';

	// @note btw triggers dirActivate() // @maybe rewrite
	dlRebuild(active_dir);

});

/**
 * Activates a directory and reloads/regenerates list of files, breadcrumbs or other.
 * @param string path
 */
function dirActivate (path) {

	active_dir = path; // for sure
	
	// affect breadcrumbs
	bcRebuild(path);

	// affect dirlist
	dlDirActivate(path);

	// affect filelist
	flRebuild(path);

	// remember active dir in cookie
	rememberActiveDir(path);
	
	// details clean-up //@maybe move
	$('#dirlist li > span.marked-modified').removeClass('marked-modified');

}


/**
 * Global keydown controller (not really used)
 */
$('body').live('keydown', function(e){

	// Esc
	if (e.which == 27) {

		// cancel currently running action (well, not any)
		// @todo won't propagate sometimes
		if (running_action == 'flRename') {
			flRenameCancel();
		}

	// Enter
	} else if (e.which == 13) {

	}

});




// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------
// Filelist

/**
 * Gets ajax/json list of files and displays it as HTML table.
 * @param string path (directory)
 */
function flRebuild (path) {
	
	$('#filelist table, div.no-files').remove();
	
	$.ajax({
		url: '?do=ajax/getDirContent',
		type: 'post',
		data: ({conditions:'files_only', dir:path}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			flWorkingStatusOn();
		},
		error: function(){
			flWorkingStatusOff();
			renderError('Files list reload failed. Please, try again, maybe later.');
		},
		success: function(data) {
			flWorkingStatusOff();

			if (data['status'] == 'error')
				renderError(data['data']);
			else {
				$('#filelist > .no-files').remove();
				
				if (!data['data'].length) {
					$('#filelist').append('<div class="no-files" style="display:none">no files here</div>').find('> .no-files').fadeIn();
				} else {
					var html = '<table><thead>\
<tr>\
<th class="name">Name</th>\
<th class="datetime" colspan="2">Created</th>\
<th class="size">Size</th>\
<th class="type">Type</th>\
</tr></thead><tbody>';

					$.each(data['data'], function(i,file){
						file.url = data_url + file.dir + file.name;

						// shorten too long names
						file.name_to_show = file.name;
						if (file.name_to_show.length > 40) file.name_to_show = file.name_to_show.substr(0, 30) + '...' + file.name_to_show.substr(-10, 10);

						// both rename and delete are considered "destructive"
						var destructive_actions = (user_can_delete) ? '<li><span class="pseudolink rename">rename</span></li><li><span class="pseudolink delete">delete</span></li>' : '';

						// preview
						// @todo
						var preview = (file.type == 'image') ? '' : '<div class="preview"><span><img src="files/preview-example.png" />@todo</span></div>';

						html += '\
<tr id="'+ (file.dir + file.name).pathToId('fl') +'" class="' + file.fileordir + ' ' + file.type + '">\
	<td class="name"><span><i class="options-trigger">&nbsp;</i><a href="' + file.url + '" tabindex="900">'+ file.name_to_show +'</a>\
	<div class="options"><ul>\
	<li><span class="pseudolink copy-url">copy address</span></li>\
	'+ destructive_actions +'\
	</ul>'+ preview +'</div></span></td>\
	<td class="date '+ file.date_c_class +'">' + file.date_c_nice + '</td>\
	<td class="time '+ file.time_c_class +'">' + file.time_c_nice + '</td>\
	<td class="size">' + file.size_value + ' <span>' + file.size_unit +'</span></td>\
	<td class="type"><span>' + file.ext + '</span></td>\
</tr>';

					});
					
					html += '</tbody></table>';
					
					$('#filelist').append(html);
				
					rebindDragAndDropStuff();
					$("a[href$='.jpg'],a[href$='.JPG'],a[href$='.png'],a[href$='.PNG'],a[href$='.gif'],a[href$='.GIF']")
						.attr('rel', 'same')
						.colorbox({opacity:0.58, maxHeight:'90%'});
					// @maybe trigger own event here (for ev. extensions)
				}
			}

// @maybe implement footer:
// <!-- <tfoot>
// <tr>
// <td class="count" colspan="3">5 files total</td>
// <td class="size">1.3 MB</td>
// </tr>
// </tfoot>
// -->

		} // /success
	});

/*
example goal (list-mode)
**********************
<tr class="file image" id="upl_fl__preview-example.png"><td class="name">
<span><i class="options-trigger"></i><a href="http://localhost:8888/Work-for-myself/uploader%202/program/files/preview-example.png">review-example.png</a>
<div class="options">
	<ul>
		<li><span class="pseudolink preview">preview</span></li>
		<li><span class="pseudolink copy-url">copy address</span></li>
		<li><span class="pseudolink rename">rename</span></li>
		<li><span class="pseudolink delete">delete</span></li>
	</ul>

	<div class="preview">
		<span><img src="files/preview-example.png" /></span><!-- max 100x100 -->
	</div>
</div></span>
</td>
<td class="date ">4.7.2009</td>
<td class="time old">8:40</td>
<td class="size">224 <span>KiB</span></td>
<td class="type"><span>png</span></td>
</tr>
*/
}


/**
 * Event: FL > td.type > hover
 */
$('#filelist td.type')
	.live('mouseover', function(){
		$('#filelist td.type').addClass('on');
	})
	.live('mouseout', function(){
		$('#filelist td.type').removeClass('on');
	});




/**
 * Event: FL > file > a > keyDown
 * @todo should not work when lightbox on
 */
/*
$('#filelist a').live('keydown', function(e){
	
	// Space: preview (lightbox, 400 px max w)
	// Del: delete dialog
	// ? Mac-only: enter: rename
	// ? Win-only: F2 rename
	// [?]: show options
	
	var $tr = $(this).closest('tr');
	
	if ((e.which == 39 && $tr.not('.options-on')) || (e.which == 37 && $tr.is('.options-on'))) {
		flFileOptionsToggle($(this).closest('tr'));
	} else if (e.which == 38) { // arr up
		
	} else if (e.which == 40) { // arr down
		
	}
});
*/


// --------------------------
// FL > file > size extras

/**
 * Gets ajax/json size-related details of specified file and displays it in soft pop-up.
 * @param string path (file)
 * @uses dialog_()
 */
function flShowSizeExtras (path) {
	$.ajax({
		url: '?do=ajax/getFileSizeExtras',
		type: 'post',
		data: ({path:path}),
		dataType: 'json',
		timeout: 50000, // @todo decide. 700MB file needs hopefully <30s.
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Getting file\'s size details failed (timeout). Maybe the server is too busy or the file too large.');
			contentWorkingStatusOff();
		},
		success: function(data) {
			contentWorkingStatusOff();

			if (data['status'] == 'error')
				renderError(data['data']);
			else {
				var file = data['data'];

				dialogConstruct('<p><strong>'+path.filename()+'</strong> file size details:</p><table>\
<tr><th>Filesize (approximate)</th><td>'+file.nicesize[0] + ' ' + file.nicesize[1] +'</td></tr>\
<tr><th>Filesize (exact)</th><td>'+file.size+'</td></tr>\
<tr><th>MD5 hash</th><td>'+file.hash_md5+'</td></tr>\
<tr><th>SHA1 hash</th><td>'+file.hash_sha1+'</td></tr></table>',
					'<button class="cancel">Close</button>',
					'info');

				dialogShow();
			}
		}
	});
}


/**
 * Event: FL > td.size > doubleclick
 */
$('#filelist td.size').live('dblclick', function(){
	flShowSizeExtras ($(this).parents('tr').attr('id').idToPath());
});


// --------------------------
// FL > file > options

/**
 * Event Definition: optionsHide - trigger always when hiding options (@maybe rethink)
 */
jQuery.fn.extend({
	optionsHide: function (fn) {
		if (fn) {
			return jQuery.event.add(this[0], "optionsHide", fn, null);
		} else {
			var result = jQuery.event.trigger("optionsHide", null, this[0], false, null);
			if (result === undefined) result = true;
			return result;
		}
	}
});


/**
 * Toggles visibility of file's options.
 * @param element A row of file-list table
 */
function flFileOptionsToggle ($tr) {
	$td = $tr.find('td.name');
	was_on = $tr.is('.options-on');

	// hiding
	$('#filelist tr.active .options').hide();
	$('#filelist tr.active').removeClass('active').removeClass('options-on');
	$(document).optionsHide();

	// showing
	if (!was_on) {
		$tr.addClass('active').addClass('options-on');
		$td.find('.options').slideDown('fast');
	}
}


/**
 * Event: FL > file-options-trigger > click
 */
$('#filelist td.name .options-trigger').live('click', function(){
	flFileOptionsToggle($(this).parents('tr'));
});


// --------------------------
// FL > file > options > preview

/**
 * Event: FL > file > options > preview > hover
 */
$('#filelist td.name .pseudolink.preview')
	.live('mouseover', function(){
		$('div.preview', $(this).parents('.options')).show();
	})
	.live('mouseout', function(){
		$('div.preview', $(this).parents('.options')).hide();
	});


// --------------------------
// FL > file > options > copy URL

/**
 * Whole copy-url function
 * @maybe refactor
 *
 * Event: FL > file > options > copy-url > hover
 * Event: ClipboardFlash > click
 */
ZeroClipboard.setMoviePath(template_url + 'js/zeroclipboard/ZeroClipboard.swf');

$('#filelist td.name .pseudolink.copy-url').live('mouseover', function(){

	var this_button = $(this);
	var this_url = $(this).parents('td.name').find('a').attr('href');

	clip = new ZeroClipboard.Client();

	clip.addEventListener('mouseOver', over);
	clip.addEventListener('complete', complete);
	clip.glue($(this)[0]);

	function over(client) {
		clip.setText(this_url);
	}

	function complete(client, text) {
		$('#filelist td.name .pseudolink.copy-url.used').removeClass('used');
		this_button.addClass('used');
		this_button.parents('.options').fadeOut(function(){
			this_button.parents('tr').removeClass('active').removeClass('options-on');
			$('embed[id^="ZeroClipboardMovie"]').parents('div').remove();
		});
	}

});

$(document).optionsHide(function(){ $('embed[id^="ZeroClipboardMovie"]').parents('div').remove(); });


// -----------------------------------------------------------------------------
// FL > RenameFile

/**
 * Event: FL > file > options > rename > click
 */
$('#filelist td.name .pseudolink.rename').live('click', function(){

	// cancel all other renaming processes
	flRenameCancel();

	flRenameStart($(this).parents('tr'));

});

/**
 * Event: FL > file > input.rename_to > Enter [=> send]
 */
$('#filelist td.name input.rename_to_namecore').live('keydown', function(e){

	// [Enter]
	if (e.which == 13) {
		if ($(this).val().trim() != '') {
			flRenameSend($(this).parents('tr').attr('id').idToPath(), $(this).val().trim());
		}
	}
	// [Esc 27] tried to handle after propagation to body

});


// -----------------------------------------------------------------------------
// FL > DeleteFile

/**
 * Event: FL > file > options > delete > click 
 */
$('#filelist td.name .pseudolink.delete').live('click', function(){
	var $tr = $(this).parents('tr');
	$tr.find('.options').hide();
	$tr.removeClass('options-on').removeClass('active').addClass('active2');
	
	flDeleteStart($tr);
	
});


/**
 * Event: delete-dialog > submit [=> Send]
 */
$('#dialog.deletefile #do1').live('click', function(){
	dialogHide();
	var $tr = $('#filelist tr.active2');
	flDeleteSend($tr.attr('id').idToPath());

});




// -----------------------------------------------------------------------------
// -----------------------------------------------------------------------------
// DIRLIST


/**
 * Event: DL > item > name > click / key:Enter [=> activate]
 */
$('#dirlist li > span').live('click', function(){

	dirActivate($(this).parent().attr('id').idToPath());
	return false;

});
$('#dirlist li').live('keydown', function(e){
	// Enter
	if (e.which == 13) {
		var id = $(this).attr('id');
		if (id != 'new-dir') {
			dirActivate(id.idToPath());
			return false;
			
		}
	}
});


/**
 * Event: DL > item [arrow] > click [=> toggle subfolders]
 */
$('#dirlist li').live('click', function(event){
	dlDirToggle($(this), false);
	return false;
});


/**
 * Event: DL > item [arrow] > Key:arrows [=> toggle subfolders or focus prev/next]
 */
$('#dirlist li').live('keydown', function(e){
	
	// @todo add Insert [=>new dir]

	if (e.target.tagName != 'INPUT')
		if (e.which == 43 || e.which == 39) { // [arr right] OR [+] // @todo fix +
			dlDirToggle($(this), true);
			return false;
		} else if (e.which == 45 || e.which == 37) { // [arr left] OR [-] // @todo fix -
			dlDirToggle($(this), false);
			return false;
		} else if (e.which == 38) { // arr up
			
			// console.log($(this).prevAll('li'));
			var $prev = $(this).prev('li');
			
			// @todo Now focus last children of last children of last children ... of prev.
			
			// if (!$prev.length) {
			// 	$prev = $(this).parent().parent('li');
			// }
			
			if ($prev.length)
				$('> span', $prev).focus();

			return false;
		} else if (e.which == 40) { // arr down
			
			var $next = $('li:visible:eq(0)', this);

			if (!$next.length)	
				$next = $(this).next('li');
			
			if (!$next.length) { // quite shit but nevermind
				var $parent = $(this).parent().parent('li');
				var i = 1;
				while (!$parent.next('li').length && i < 20) {
					$parent = $parent.parent().parent('li');
					i++;
				}
				$next = $parent.next('li');			
			}
			
			if ($next.length)
				$('> span', $next).focus();
				
			return false;
		}
});


/**
 * Toggle a directory in directory-tree (= show/hide subfolders)
 *
 * @param	element	li element supposed to be toggled 
 * @param	bool	true = do not close, open only
 */
function dlDirToggle (object, details) {

	if (object.children('ul').size()) {

		if (!details) {

			// set classes
			object.toggleClass('closed').toggleClass('open');

			// hide or show
			$('> ul', object).slideToggle('fast');

		} else {
			object.removeClass('closed').addClass('open');
			$('> ul', object).slideDown('fast');
		}

	}

	// remember in cookie
	if (object.is('.closed'))
		$.cookie(object.attr('id'), 'closed', {path:'/', expires:365});
	else
		$.cookie(object.attr('id'), null, {path:'/', expires:365});

}


/**
 * Activate directory
 */
function dlDirActivate (path) {

	$('#dirlist li.active').removeClass('active');

	$active = $('#dirlist li[id='+ path.pathToId('dl') +']');
	$active.addClass('active');
	dlDirToggle($active, true);

	// maybe open ancestoring directories
	if ($active.is(':hidden')) {
		$active.parents('li').each(function(){
			if ($('> ul', this).is(':hidden'))
				dlDirToggle($(this), false);
			else return false; // =break
		});
	}
}


/**
 * Make AJAX request to get list of directories and if possible, generate the HTML
 * @param  string  path (optional). If set, that directory is activated after creating HTML
 */
function dlRebuild (active_dir) {

	$.ajax({
		url: '?do=ajax/getDirContent',
		type: 'post',
		data: ({conditions:'dirs_only', dir:''}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Directories list reload failed. Please, try again, maybe later.');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();

			if (data['status'] == 'error')
				renderError(data['data']);
			else {

				var html = '';
				$.each(data['data'], function(i, dir){
					html += dlDrawDir(dir);
				});

				$('#dirlist > ul > li > ul').html(html);

				$('#dirlist .closed > ul').hide();

				// activate dir
				if (active_dir !== false) {
					dirActivate(active_dir);
				}

			}
			
			rebindDragAndDropStuff();

		}
	});
	
	
	/**
	 * Generates HTML of one directory level. Helping function. Used recursively.
	 */
	function dlDrawDir(dir) {

		var has_subdirs = (dir.content.length > 0);
		var html_open = '';
		if (has_subdirs)
			if ($.cookie(dir.path.pathToId('dl')) == 'closed')
				html_open = ' class="closed"';
			else
				html_open = ' class="open"';


		// open
		var html = '<li id="' + dir.path.pathToId('dl') + '"'+ html_open +'><span tabindex="700">' + dir.name + '</span>';

		// if has sub-dirs
		if (has_subdirs) {
			html += '<ul>';

			$.each(dir.content, function(i, dir_sub) {
				html += dlDrawDir(dir_sub); // recursion
			});

			html += '</ul>';
		}

		// close
		html += '</li>';

		return html;

	/* GOAL EXAMPLE
	 **************

	<li id="upl_dl__files___msice___cervene" class="closed"><span tabindex="700">cervene</span>
		<ul>
			<li id="upl_dl__files___msice___cervene___oranzove"><span tabindex="700">oranžské</span></li>
		</ul>
	</li>
	*/

	}

}


// ----------
// DL > drag and drop folders

/**
 * Contains Drag-and-drop Bondages
 * needed to call after almost any modification of dirlist or filelist
 */
function rebindDragAndDropStuff () {
	
	/**
	 * Draggable (files and dirs)
	 */
	$('#dirlist li').draggable({
		// cancel: 'a.ui-icon',
		revert: 'invalid',
		helper: 'clone', 
		cursor: 'move'
	});

	$('#filelist tr, #filelist tr *').draggable({
		// cancel: 'a',
		revert: 'invalid',
		helper: 'clone', 
		cursor: 'move'
	});

	/**
	 * Droppable (dirs)
	 */
	$('#dirlist li > span').droppable({
		greedy: true,
		accept: '#dirlist li, #filelist tr, #filelist tr *',
		hoverClass: 'marked',
		drop: function(ev, ui) {

			var $item = ui.draggable;
			var $target = $(this).parent();
			
			if ($item.is('tr *'))
				$item = $item.parents('tr:eq(0)');
			
			// finishing process (visual move) performed in moveFileDirSend() (got better idea? @maybe rethink)
			if (ev.metaKey || ev.ctrlKey) {
				copyFileDirSend($item, $target);
			} else {
				moveFileDirSend($item, $target);
			}	
			
		}
	});

}

/**
 * 
 */
function moveFileDirSend($item, $target) {

	var item_path = $item.attr('id').idToPath();
	var target_path = $target.attr('id').idToPath();

	var item_type = ($item.is('#dirlist li')) ? 'dir' : 'file';

	if (!$target.find('> ul').size())
		$target.append('<ul></ul>');

	$target_html = $target.find('> ul');
	
	$.ajax({
		url: '?do=ajax/moveFileDir',
		type: 'post',
		data: ({item:item_path, target:target_path}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Moving failed. Please, try again. (Server didn\'t respond correctly.)');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();

			if (data['status'] == 'error') {
				renderError(data['data']);
			} else {
				var type = (item_type == 'dir') ? 'Folder' : 'File';

				renderMessage(type + ' <em>' + item_path.filename() + '</em> was moved.');
				
				if (item_type == 'file') {
					$item.fadeOut(function(){
						$(this).remove();
					});
				} else {
					// if active directory was moved, regenerate filelist too
					if ($item.is('.active') || $('li.active', $item).size()) {
						
						// this is awesome:
						active_dir = target_path +'/'+ active_dir.replace(item_path.dirname(), '');
						dlRebuild(active_dir);
						// dirActivate(active_dir); // reload everything
					} else {
						dlRebuild(active_dir);
					}

					// $item.hide(function(){
					// 	$item.prependTo($target).fadeIn();
					// });
					
				}
				
				$('#dirlist li > span.marked-modified').removeClass('marked-modified');
				$('#' + target_path.pathToId('dl') + ' > span').addClass('marked-modified');

			}

		} // /success
	});

}

/**
 * 
 */
function copyFileDirSend($item, $target) {

	var item_path = $item.attr('id').idToPath();
	var target_path = $target.attr('id').idToPath();

	var item_type = ($item.is('#dirlist li')) ? 'dir' : 'file';

	if (!$target.find('> ul').size())
		$target.append('<ul></ul>');

	$target_html = $target.find('> ul');
	
	$.ajax({
		url: '?do=ajax/copyFileDir',
		type: 'post',
		data: ({item:item_path, target:target_path}),
		dataType: 'json',
		timeout: 5000,
		beforeSend: function() {
			contentWorkingStatusOn();
		},
		error: function(){
			renderError('Copying failed. Please, try again. (Server didn\'t respond correctly.)');
			contentWorkingStatusOff();
		},
		success: function(data) {

			contentWorkingStatusOff();

			if (data['status'] == 'error') {
				renderError(data['data']);
			} else {
				var type = (item_type == 'dir') ? 'Folder' : 'File';

				renderMessage(type + ' <em>' + item_path.filename() + '</em> was copied.');
				
				if (item_type == 'file') {
				} else {
					// if active directory was moved, regenerate filelist too
					if ($item.is('.active') || $('li.active', $item).size()) {
						
						// this is awesome:
						active_dir = target_path +'/'+ active_dir.replace(item_path.dirname(), '');
						dlRebuild(active_dir);
						dirActivate(active_dir); // reload everything
					} else {
						dlRebuild(active_dir);
					}

					// $item.hide(function(){
					// 	$item.prependTo($target).fadeIn();
					// });
					
				}

			}

		} // /success
	});

}

// ----------
// DL > buttons


/**
 * Event: DL > buttons > Add
 */
$('#dl-controls .start-add-dir').live('click', function(e){
	dlAddDirStart();
});

/**
 * Event: DL > buttons > Rename
 */
$('#dl-controls .start-rename-dir').live('click', function(){
	if (!rename_dir_running)
		dlRenameStart();
	else 
		dlRenameCancel();
});

/**
 * Event: DL > buttons > Delete
 */
$('#dl-controls .start-delete-dir').live('click', function(){
	dlDeleteStart();
});




// --------------------------
// --------------------------
// BreadCrumbs

/**
 * Event: BC > item > click
 */
$('#breadcrumbs span.pseudo-link').live('click', function(){
	dirActivate($(this).attr('id').idToPath());
});

/**
 * Regenerated (HTML) breadcrumbs
 * @param string path (last directory in path expected to be the active one)
 */
function bcRebuild (path) {

	// clear
	$('#breadcrumbs').text('');

	// root
	var current_path = '';
	var class_active = (!path) ? ' active' : '';
	$('#breadcrumbs').append('<span class="functional pseudo-link dir'+ class_active +'" id="'+ current_path.pathToId('bc') +'" tabindex="500">'+ data_url.filename() +'</span>');

	// rebuild
	var dirs = (path) ? path.split('/') : false;
	if (dirs) {
		$(dirs).each(function (i) {
			var is_first = !i;
			var is_last = (i == dirs.length-1);
			class_active = (is_last) ? ' active' : '';

			current_path = (is_first) ? this : current_path + '/' + this;

			// separator
			//if (!is_first)
				$('#breadcrumbs').append(' &rsaquo; ');

			// bc item
			$('#breadcrumbs').append('<span class="functional pseudo-link dir'+ class_active +'" id="'+ current_path.pathToId('bc') +'" tabindex="500">'+ this +'</span>');
		});
	}

/* Example goal:
****************
<span class="functional pseudo-link dir" id="upl_bc__" tabindex="500">files</span> &rsaquo; <span...
*/

}




// --------------------------
// --------------------------
// Tabs

$('#tabs h2').live('click', function(){
	tabActivate($(this).attr('id').replace('h-', ''));
});

function tabActivate (page_key) {

	// hide all
	$('#content > div[id]').removeClass('active').hide();
	$('#tabs > h2').removeClass('active');

	// show wanted
	$('#'+page_key).addClass('active').show();
	$('#h-'+page_key).addClass('active');

}

/**
 * Init onLoad
 * 1. Move all headings into tab-bar  2. activate browsing
 */
$(document).ready(function(){
	
	$('#content h2').appendTo('#tabs');
	tabActivate('browse');
	
}); // document.ready




// --------------------------
// --------------------------
/**
 * Loading animation
 * Indicates a function in progress, that may take some time, like ajax calls.
 * @maybe rework to more local indicator(s)
 */


function contentWorkingStatusOn() {
	$('body').addClass('loading');
}

function contentWorkingStatusOff() {
	$('body').removeClass('loading');
}

function flWorkingStatusOn() {
	$('#filelist').addClass('loading');
}

function flWorkingStatusOff() {
	$('#filelist').removeClass('loading');
}


// --------------------------
// --------------------------
/**
 * Messages
 * Rendering of messages. Only the last message is visible at the time. 
 */

function renderError(text) {
	renderMessageGeneral(text, 'error');
}

function renderMessage(text) {
	renderMessageGeneral(text, 'ok');
}

function renderMessageGeneral (text, mclass) {
	if ($('#messages p').length)
		$('#messages p').slideUp('150', function(){$(this).remove(); add(text,mclass);});
	else
		add(text,mclass);

	function add(text,mclass){
		$('#messages').append('<p class="'+ mclass +'" style="display:none;" title="click to hide"><span>'+ text +'</span></p>');
		$('#messages p').slideDown('200');
	}
}

$('#messages p').live('click', function (){
	$(this).fadeOut('1000');
});