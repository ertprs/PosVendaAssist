/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	disableNativeSpellChecker = true;
	config.scayt_autoStartup = false;

	config.format_p = { element: "p", style: { 'margin-top': '0px', 'margin-bottom': '0px', 'padding-top': '0px', 'padding-bottom': '0px' } };
};
