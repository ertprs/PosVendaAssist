<?php

$load = array(
	"autocomplete" => "posvenda_jquery_ui/development-bundle/ui/jquery.ui.autocomplete.js",
	"datepicker" => "posvenda_jquery_ui/development-bundle/ui/minified/jquery.ui.datepicker.min.js",
	"shadowbox" => array(
		"js" => "shadowbox_lupa/shadowbox.js",
		"css" => "shadowbox_lupa/shadowbox.css"
	),
	"mask" => "jquery.mask.js",
	"maskedinput" => "jquery.maskedinput_new.js",
	"dataTable" => array(
		"js" => "dataTable.js?v=".date('YmdHis'),
		"css" => "dataTable.css?v=".date('YmdHis')
	),
	"datatable_responsive" => array(
		"js" => array(
			"datatable_responsive/jquery.dataTables.min.js",
			"datatable_responsive/dataTables.responsive.min.js"
		),
		"css" => array(
			"datatable_responsive/jquery.dataTables.min.css",
			"datatable_responsive/responsive.dataTables.min.css"
		)
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
	"informacaoCompleta" => "informacaoCompleta.js",
	"timepicker" => array(
		"js" => array(
			"timepicker/jquery.timepicker.js"
		),
		"css" => array(
			"timepicker/jquery.timepicker.css"
		)
	),
	"ajaxform" => "jquery.form.js",
	"fancyzoom" => array(
		"js" => array(
			"FancyZoom/FancyZoom.js",
			"FancyZoom/FancyZoomHTML.js"
		)
	),
	"jquery_multiselect" => array(
		"js" => array(
			"jquery_multiselect/js/jquery.multi-select.js"
		),
		"css" => array(
			"jquery_multiselect/css/multi-select.css"
		)
	),
	"select2" => array(
		"js" => array(
			"select2/select2.js"
		),
		"css" => array(
			"select2/select2.css"
		)
	),
	"ckeditor" => array(
		"ckeditor_new/ckeditor.js"
	),
	"bootstrap3" => array(
	    	"js" => array(
	    		"bootstrap3/js/bootstrap.min.js"
    		),
	    	"css" => array(
	    		"bootstrap3/css/bootstrap.min.css",
	    		"bootstrap3/css/bootstrap-theme.min.css"
    		)
	),
	"datetimepicker" => array(
		"js" => array(
			"datetimepicker/js/moment.js",
			"datetimepicker/js/bootstrap-datetimepicker.min.js"
		),
		"css" => array(
			"datetimepicker/css/bootstrap-datetimepicker.min.css"
		)
	),
	"datetimepickerbs2" => array(
		"js" => array(
			"datetimepickerbs2/js/bootstrap-datetimepicker.min.js"
		),
		"css" => array(
			"datetimepickerbs2/css/bootstrap-datetimepicker.min.css"
		)
	),
	"highcharts" => array(
		"js" => array(
			"highcharts/highcharts_4.2.5.js",
			"highcharts/highcharts_4.2.5_more.js"
		)
	),
	"mapbox" => array(
		"js" => array(
			"mapbox/geocoder.js",
			"mapbox/map.js",
			"mapbox/mapbox.js",
			"mapbox/polyline.js"
		),
		"css" => array(
			"mapbox/map.css"
		)
	),
	"colorpicker" => array(
	    	"js" => array(
	    		"colorpickerbootstrap2/js/bootstrap-colorpicker.min.js"
    		),
	    	"css" => array(
	    		"colorpickerbootstrap2/css/bootstrap-colorpicker.min.css",
    		)
	),
	"leaflet" => array(
		"js" => array(
			"mapbox/geocoder.js",
			"leaflet/leaflet.js",
			"leaflet/map.js",
			"mapbox/polyline.js"
		),
		"css" => array(
			"leaflet/leaflet.css"
		)
	),
	"datatable_responsive" => array(
		"js" => array(
			"datatable_responsive/jquery.dataTables.min.js",
			"datatable_responsive/dataTables.responsive.min.js"
		),
		"css" => array(
			"datatable_responsive/jquery.dataTables.min.css",
			"datatable_responsive/responsive.dataTables.min.css"
		)
	),
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
