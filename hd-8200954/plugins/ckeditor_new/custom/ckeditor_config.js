/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	config.language = 'pt-br';
	config.uiColor = '#A0BFE0';
	config.toolbarGroups = [
							    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
							    { name: 'paragraph',   groups: [ 'list', 'align'] },
							    '/',
							    { name: 'colors' },
							    { name: 'styles' },
							    
							];
};