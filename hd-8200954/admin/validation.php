<?
require_once("Validator.php");

$validator = new Validator($_REQUEST);
$validator->filledIn("fname");
$validator->length("lname", "<", 15);
$validator->email("email");
$validator->compare("pass1", "pass2");
$validator->lengthBetween("color", 3, 15);
$validator->punctuation($sentence);
$validator->value("age", ">", 18);
$validator->valueBetween("number", 11, 16, true);
$validator->alpha("car");
$validator->alphaNumeric("monitor");
$validator->date("date", "mm/dd/yyyy");

$errors = $validator->getErrors();
$id = $validator->getId();
echo "Validator Id: $id<br><br>";
foreach($errors as $key => $value) {
	if(strstr($key, "|")) {
		$key = str_replace("|", " and ", $key);
	}
	echo "<b>Error $value:</b> on field $key<br>";
}
?>