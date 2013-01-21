<?php

class PHPTail {
	
	
	/**
	 * Location of the log file we're tailing
	 * @var string
	 */
	private	$log = "";
	/**
	 * The time between AJAX requests to the server. 
	 * 
	 * Setting this value too high with an extremly fast-filling log will cause your PHP application to hang.
	 * @var integer
	 */
	private $updateTime;
	/**
	 * This variable holds the maximum amount of bytes this application can load into memory (in bytes).
	 * @var string
	 */
	private $maxSizeToLoad;
	/**
	 * 
	 * PHPTail constructor
	 * @param string $log the location of the log file
	 * @param integer $defaultUpdateTime The time between AJAX requests to the server. 
	 * @param integer $maxSizeToLoad This variable holds the maximum amount of bytes this application can load into memory (in bytes). Default is 2 Megabyte = 2097152 byte
	 */
	public function __construct($log, $defaultUpdateTime = 2000, $maxSizeToLoad = 2097152) {
		$this->log = is_array($log) ? $log : array($log);
		$this->updateTime = $defaultUpdateTime;
		$this->maxSizeToLoad = $maxSizeToLoad;
	}
	/**
	 * This function is in charge of retrieving the latest lines from the log file
	 * @param string $lastFetchedSize The size of the file when we lasted tailed it.  
	 * @param string $grepKeyword The grep keyword. This will only return rows that contain this word
	 * @return Returns the JSON representation of the latest file size and appended lines.
	 */
	public function getNewLines($tab, $lastFetchedSize, $grepKeyword, $invert) {

		/**
		 * Clear the stat cache to get the latest results
		 */
		clearstatcache();
		/**
		 * Define how much we should load from the log file 
		 * @var 
		 */
		$fsize = filesize($this->log[$tab]);
		$maxLength = ($fsize - $lastFetchedSize);
		/**
		 * Verify that we don't load more data then allowed.
		 * Set maxLength to half maxSizeToLoad when it was too large.
		 */
		if($maxLength > $this->maxSizeToLoad) {
			$maxLength = ($this->maxSizeToLoad / 2);
		}
		/**
		 * Actually load the data
		 */
		$data = array();
		if($maxLength > 0) {
			
			$fp = fopen($this->log[$tab], 'r');
			fseek($fp, -$maxLength , SEEK_END); 
			$data = explode("\n", fread($fp, $maxLength));
			
		}
		/**
		 * Run the grep function to return only the lines we're interested in.
		 */
		if($invert == 0) {
			$data = preg_grep("/$grepKeyword/",$data);
		}
		else {
			$data = preg_grep("/$grepKeyword/",$data, PREG_GREP_INVERT);
		}
		/**
		 * If the last entry in the array is an empty string lets remove it.
		 */
		if(end($data) == "") {
			array_pop($data);
		}
		return json_encode(array("size" => $fsize, "data" => $data));	
	}
	/**
	 * This function will print out the required HTML/CSS/JS
	 */
	public function generateGUI() {
?>
		<!DOCTYPE html>
		<html>
			<head>
				<title>PHPTail</title> 
				<meta charset='utf-8'>

				<link type="text/css" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/flick/jquery-ui.css" rel="stylesheet"></link>
				<style type="text/css">
					#grepKeyword, #settings { 
						font-size: 80%; 
					}
					.float {
						width: 100%; 					
					}
					.header.float {
						background: white; 
						border-bottom: 1px solid black; 
						padding: 10px 0 10px 0; 
						margin: 0px;  
						height: 30px;
						text-align: left;
					}
					.results {
						font-family: monospace;
						font-size: small;
						padding-bottom: 10px;
						white-space: pre;
					}
				</style>

				<script src="//ajax.googleapis.com/ajax/libs/jquery/1.4.4/jquery.min.js"></script>
				<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/jquery-ui.min.js"></script>
				
				<script>
					/* <![CDATA[ */
					//Last known document height
					documentHeight = 0; 
					//Last known scroll position
					scrollPosition = 0; 
					//Should we scroll to the bottom?
					scroll = true;
					lastTab = window.location.hash != "" ? window.location.hash.substr(1) :  "<?php echo array_keys($this->log)[0];?>";
					console.log(lastTab);
					$(document).ready(function(){

						$( "#tabs" ).tabs();
						$( "#tabs" ).bind( "tabsselect", function(event, ui) {
							lastTab = $(ui.tab).attr('hash').substr(1);
							console.log(lastTab);
						});
						// Setup the settings dialog
						$( "#settings" ).dialog({
							modal: true,
							resizable: false,
							draggable: false,
							autoOpen: false,
							width: 590,
							height: 270,
							buttons: {
								Close: function() {
									$( this ).dialog( "close" );
								}
							},
							close: function(event, ui) { 
								var tab = $('#'+lastTab);
								tab.data('grep', $("#grep").val());
								tab.data('invert', $('#invert input:radio:checked').val());
								$(".grepspan", tab).html("Grep keyword: \"" + tab.data('grep') + "\"");
								$(".invertspan", tab).html("Inverted: " + (tab.data('invert') == 1 ? 'true' : 'false'));
							}
						});
						//Close the settings dialog after a user hits enter in the textarea
						$('#grep').keyup(function(e) {
							if(e.keyCode == 13) {
								$( "#settings" ).dialog('close');
							}
						});		
						//Focus on the textarea					
						$("#grep").focus();
						//Settings button into a nice looking button with a theme
						$(".grepKeyword").button();
						//Settings button opens the settings dialog
						$(".grepKeyword").click(function(){
							$( "#settings" ).dialog('open');
							$(this).removeClass('ui-state-focus');
						});
						//Set up an interval for updating the log. Change updateTime in the PHPTail contstructor to change this
						setInterval ( "updateLog()", <?php echo $this->updateTime; ?> );
						//Some window scroll event to keep the menu at the top
						$(window).scroll(function(e) {
						    if ($(window).scrollTop() > 0) { 
						        $('.header.float').css({
						            position: 'fixed',
						            top: '49px',
						            left: 'auto'
						        });
						        $('#tabs ul').css({
						            position: 'fixed',
						            top: '0px',
						            left: 'auto'
						        });
						    } else {
						        $('.float').css({
						            position: 'static'
						        });
						    }
						});
						//If window is resized should we scroll to the bottom?
						$(window).resize(function(){
							if(scroll) {
								scrollToBottom();
							}
						});
						//Handle if the window should be scrolled down or not
						$(window).scroll(function(){
							documentHeight = $(document).height(); 
							scrollPosition = $(window).height() + $(window).scrollTop(); 
							if(documentHeight <= scrollPosition) {
								scroll = true;
							}
							else {
								scroll = false; 
							}
						});
						updateLog();
						scrollToBottom();
					});
					//This function scrolls to the bottom
					function scrollToBottom() {
						$('.ui-widget-overlay').width($(document).width());
					    $('.ui-widget-overlay').height($(document).height());

						$("html, body").scrollTop($(document).height());
						if($( "#settings" ).dialog("isOpen")) {
							$('.ui-widget-overlay').width($(document).width());
						    $('.ui-widget-overlay').height($(document).height());
						    $( "#settings" ).dialog("option", "position", "center");
						}
					}
					//This function queries the server for updates.
					function updateLog() {
						var tab = $('#'+lastTab);
						$.getJSON('?ajax=1&tab=' + lastTab + '&lastsize=' + tab.data('lastSize') + '&grep='+tab.data('grep') + '&invert='+tab.data('invert'), function(data) {
							tab.data('lastSize',data.size);
							$.each(data.data, function(key, value) { 
								$(".results", tab).append('' + value + '<br/>');
							});
							if(scroll) {
								scrollToBottom();
							}
						});
					}
					/* ]]> */
				</script>
			</head> 
			<body>
				<div id="settings" title="PHPTail settings">
					<p>Grep keyword (return results that contain this keyword)</p>
					<input id="grep" type="text" value=""/>
					<p>Should the grep keyword be inverted? (Return results that do NOT contain the keyword)</p>
					<div id="invert">
						<input type="radio" value="1" id="invert1" name="invert" /><label for="invert1">Yes</label>
						<input type="radio" value="0" id="invert2" name="invert" checked="checked" /><label for="invert2">No</label>
					</div>
				</div>
				<div id="tabs">
					<ul class="float">
						<?php foreach ($this->log as $title => $file): ?>
							<li><a href="#<?php echo $title;?>"><?php echo $title;?></a></li>
						<?php endforeach;?>
					</ul>
					<?php foreach ($this->log as $title => $file): ?>
					<div id="<?php echo $title;?>" data-lastSize="0" data-grep="" data-invert="0">
						<div class="header float">
							<button class="grepKeyword">Settings</button>
							<span>Tailing file: <?php echo $file; ?></span> | <span class="grepspan">Grep keyword: ""</span> | <span class="invertspan">Inverted: false</span>
						</div>
						<div class="results"></div>
					</div>
					<?php endforeach;?>
				</div>
			</body> 
		</html> 
<?php
	}
}

