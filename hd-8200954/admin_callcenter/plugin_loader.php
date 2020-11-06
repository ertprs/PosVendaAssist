<?php

$load = array(
	"autocomplete" => "posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js",
	"datepicker" => "posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js",
	"shadowbox" => array(
		"js" => "shadowbox_lupa/shadowbox.js",
		"css" => "shadowbox_lupa/shadowbox.css"
	),
	"mask" => "jquery.mask.js",
	"dataTable" => array(
		"js" => "dataTable.js",
		"css" => "dataTable.css"
	),
	"price_format" => array(
		"price_format/jquery.price_format.1.7.min.js",
		"price_format/config.js",
		"price_format/accounting.js"
	),
	"multiselect" => array(
		"js" => "multiselect/multiselect.js",
		"css" => "multiselect/multiselect.css"
	),	
	"tooltip" => "tooltip.js",
	"alphanumeric" => "jquery.alphanumeric.js",
	"informacaoCompleta" => "informacaoCompleta.js"
);

if($bi == "sim"){

	if (isset($plugins) && count($plugins) > 0) {
		foreach ($plugins as $plugin) {
			if (isset($load[$plugin])) {
				if (is_array($load[$plugin])) {
					foreach ($load[$plugin] as $type => $plugin_value) {
						switch ($type) {
							case "js":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										echo "<script src='../plugins/{$value}'></script>";
									}
								} else {
									echo "<script src='../plugins/{$plugin_value}'></script>";
								}
								break;
							
							case "css":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										echo "<link rel='stylesheet' type='text/css' href='../plugins/{$value}' />";
									}
								} else {
									echo "<link rel='stylesheet' type='text/css' href='../plugins/{$plugin_value}' />";
								}
								break;

							case "php":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										include("../plugins/{$value}");
									}
								} else {
									include("../plugins/{$plugin_value}");
								}
								break;

							default:
								echo "<script src='../plugins/{$plugin_value}'></script>";
								break;
						}
					}
				} else {
					echo "<script src='../plugins/{$load[$plugin]}'></script>";
				}
			}
		}
	}

}else{

	if (isset($plugins) && count($plugins) > 0) {
		foreach ($plugins as $plugin) {
			if (isset($load[$plugin])) {
				if (is_array($load[$plugin])) {
					foreach ($load[$plugin] as $type => $plugin_value) {
						switch ($type) {
							case "js":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										echo "<script src='plugins/{$value}'></script>";
									}
								} else {
									echo "<script src='plugins/{$plugin_value}'></script>";
								}
								break;
							
							case "css":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										echo "<link rel='stylesheet' type='text/css' href='plugins/{$value}' />";
									}
								} else {
									echo "<link rel='stylesheet' type='text/css' href='plugins/{$plugin_value}' />";
								}
								break;

							case "php":
								if (is_array($plugin_value)) {
									foreach ($plugin_value as $value) {
										include("plugins/{$value}");
									}
								} else {
									include("plugins/{$plugin_value}");
								}
								break;

							default:
								echo "<script src='plugins/{$plugin_value}'></script>";
								break;
						}
					}
				} else {
					echo "<script src='plugins/{$load[$plugin]}'></script>";
				}
			}
		}
	}

}

?>