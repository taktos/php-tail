<?php
/**
 * Require the library
 */
require 'PHPTail.php';
/**
 * Initilize a new instance of PHPTail
 * @var PHPTail
 */

$tail = new PHPTail( array(
	"Access_Log" => "C:/Program Files/Apache Software Foundation/Apache2.2/logs/access.log",
	"Error_Log" => "C:/Program Files/Apache Software Foundation/Apache2.2/logs/access.log",
));

/**
 * We're getting an AJAX call
 */
if(isset($_GET['ajax']))  {
	echo $tail->getNewLines($_GET['tab'], $_GET['lastsize'], $_GET['grep'], $_GET['invert']);
	die();
}
/**
 * Regular GET/POST call, print out the GUI
 */
$tail->generateGUI();