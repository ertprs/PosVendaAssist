<?php
 
 namespace html\element;

 class Br implements HtmlElement{

 	public function setAttribute($name,$value){
 		return;
 	}

	public function setContent($content){
		return;
	}

	public function mergeAttributes(Array $attributes){
		return;
	}

	public function render(){
		echo '<br />';
	}

 }