<?php

     /*
     ###############################################
     ####                                       ####
     ####    Author : Harish Chauhan            ####
     ####    Updated: 05 Oct,2012               ####
     ####    Updated By: Fernando R.            ####
     ####                                       ####
     ###############################################

     */


	class ExcelWriter {

		var $error;
		var $newRow  = false;
		var $state   = "CLOSED";
		var $bgcolor = false;

		private $fp  = null;

		private $default_title = array("column"  => array("bgcolor: #596D9B" => "parameter",
													  	  "align: center"    => "parameter"),
									   "content" => array("color: white"  => "parameter",
									  					  "face: calibri" => "parameter"));

		private $default_content = array("content" => array("face: calibri" => "parameter"));


		// Constructor
		function __construct ($file="") {
			return $this->open($file);
		}

		// Create the file
		private function open($file) {

			if($this->state!="CLOSED") {
				$this->error="Error : Another file is opend .Close it to save the file";
				return false;
			}

			if(!empty($file)) {
				$this->fp=@fopen($file,"w+");
			} else {
				$this->error="Usage : New ExcelWriter('fileName')";
				return false;
			}

			if($this->fp==false){
				$this->error="Error: Unable to open/create File.You may not have permmsion to write the file.";
				return false;
			}

			$this->state = "OPENED";
			fwrite($this->fp,$this->GetHeader());
			return $this->fp;
		}

		// Close table and the file
		function close() {
			if($this->state != "OPENED") {
				$this->error = "Error : Please open the file.";
				return false;
			}

			if($this->newRow) {
				fwrite($this->fp,"</tr>");
				$this->newRow = false;
			}

			fwrite($this->fp,$this->GetFooter());
			fclose($this->fp);
			$this->state = "CLOSED";
			return true;
		}

		private function GetHeader() {
			$header = "<table x:str style='border-collapse: collapse;' border='1' width='100%'>";
			return $header;
		}

		private function GetFooter() {
			return "</table>";
		}

		function writeLine($line_arr, $css = NULL) {

			// Default background color
			$this->bgcolor = !$this->bgcolor;

			// If the file isn't opened
			if($this->state!="OPENED") {
				$this->error="Error : Please open the file.";
				return false;
			}

			// If the content isn't an array
			if(!is_array($line_arr)) {
				$this->error="Error : Argument is not valid. Supply an valid Array.";
				return false;
			}

			// Verify if css is defined
			if($css != NULL) {

				// Set default title css
				if($css == "default_title") {

					$cssColumn  = isset($this->default_title["column"])  ? $this->getCssString($this->default_title["column"]) : "";
					$cssContent = isset($this->default_title["content"]) ? $this->getCssString($this->default_title["content"]) : "";

				// Set default content css
				} elseif($css == "default_content") {

					$cssColumn  = isset($this->default_content["column"])  ? $this->getCssString($this->default_content["column"]) : "";
					$cssContent = isset($this->default_content["content"]) ? $this->getCssString($this->default_content["content"]) : "";

					// Default background-color
					if($css == "default_content") {
						$cssColumn .= ($this->bgcolor ? " bgcolor='#91C8FF'" : " bgcolor='#F1F4FA'");
					}

				// Set css by parameter
				} else {

					$cssColumn  = isset($css["column"]) ? $this->getCssString($css["column"]) : "";
					$cssContent = isset($css["content"])? $this->getCssString($css["content"]) : "";

				}
			} else {

				$cssColumn = $cssContent = "";
			}

			fwrite($this->fp,"<tr>");

			foreach($line_arr as $text => $par) {
				fwrite($this->fp, "<td align='left' $cssColumn nowrap><font " . (is_array($par) ? $this->getCssString($par) : $cssContent) . ">". (is_array($par) ? $text : $par) . "</font></td>");
			}

			fwrite($this->fp,"</tr>");
		}

		private function getCssString($css) {

			$style  = 'style="';
			$params = "";

			foreach ($css as $key => $value) {

				$key   = str_replace(" ", "", $key);
				$value = str_replace(" ", "", $value);

				if($value == "parameter") {
					$params .= implode('="', explode(":", $key)) . '" ';
				} else {
					$style .= "$value; ";
				}

			}

			$style = trim($style) . '" ';

			return ($style == 'style="" ' ? '' : $style) . $params;
		}

	}
?>
