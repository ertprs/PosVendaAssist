/**
 * @license Copyright (c) 2003-2015, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	config.extraPlugins = 'wordcount,notification';
	config.wordcount = {
		showParagraphs: false,
		showWordCount: false,
		showCharCount: true,
		countSpacesAsChars: false,
		countHTML: false,
		maxWordCount: -1,
		maxCharCount: -1,
		filter: new CKEDITOR.htmlParser.filter({
			elements: {
				div: function( element ) {
					if(element.attributes.class == 'mediaembed') {
						return false;
					}
				}
			}
		})
	};
	config.language = 'pt-br';
	config.uiColor = '#A0BFE0';
	config.width = 800;
	config.toolbarGroups = [
							    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
							    { name: 'paragraph',   groups: [ 'list', 'align'] },
							    '/',
							    { name: 'colors' },
							    { name: 'styles' },

							];
};


/*
CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	config.language = 'pt-br';
	config.uiColor = '#A0BFE0';
	config.width = 800;
	config.toolbarGroups = [
							    { name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
							    { name: 'paragraph',   groups: [ 'list', 'align'] },
							    '/',
							    { name: 'colors' },
							    { name: 'styles' },

							];
};
*/