<?php
/**
 * Plugin Name: WordPress Support by WPSupport.EU
 * Plugin URI: http://wpsupport.eu
 * Description: Get WordPress support into your Dashboard. Easy, straight forward and FREE!
 * Version: 1.0.1
 * Author: wpsupport.eu
 * Author URI: http://wpsupport.eu
 * Requires at least: 3.8
 * Tested up to: 3.9.1
 *
 * Text Domain: wpsupporteu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add the dashboard widget.
 *
 * Add the wpsupport.eu widget to Dashboard.
 *
 * @since 1.0
 *
 */
function wpseu_add_dashboard_widget() {
 	wp_add_dashboard_widget( 'wpseu_dashboard_widget', 'WPSupport.EU', 'wpseu_dashboard_widget' );
 
 	global $wp_meta_boxes;
 
 	$normal_dashboard = $wp_meta_boxes['dashboard']['normal']['core'];
 
 	$wpseu_widget_backup = array( 'wpseu_dashboard_widget' => $normal_dashboard['wpseu_dashboard_widget'] );
 	unset( $normal_dashboard['wpseu_dashboard_widget'] );
 
 	$sorted_dashboard = array_merge( $wpseu_widget_backup, $normal_dashboard );
 
 	$wp_meta_boxes['dashboard']['normal']['core'] = $sorted_dashboard;
}
add_action( 'wp_dashboard_setup', 'wpseu_add_dashboard_widget' );


/**
 * Create the dashboard widget.
 *
 * Create the wpsupport.eu Dashboard widget.
 *
 * @since 1.0
 *
 */
function wpseu_dashboard_widget() {
	if(isset ($_POST["wpseu_email"]) && isset($_POST["wpseu_key"])){
		$wpseu_login_details = array("email" => $_POST["wpseu_email"], "key" => $_POST["wpseu_key"]);
	} else {
		$wpseu_login_details = get_option('wpseu_settings');
	}
	if($wpseu_login_details && isset($wpseu_login_details["email"]) && isset($wpseu_login_details["key"])){
		$wpseu_login = wpseu_server_connect($wpseu_login_details["email"], $wpseu_login_details["key"]);
	}
	$wpseu_login = wpseu_server_connect();
	
	if($wpseu_login["status"] == "error"){?>
	<div class="wpseu_info">
		<?php if( $wpseu_login["error"] == "invalid_url"){?>
			<span><?php _e("You are not logged in because your website was not added to your WPSupport.EU account.", "wpsupporteu");?> <a href="http://wpsupport.eu/wp-admin/" target="_blank"><?php _e("Click here to add it","wpsupporteu");?></a></span>
		<?php } else {?>
			<span><?php _e("You are not logged in.", "wpsupporteu");?> <a class="wpseu_login_link" href="#"><?php _e("Click here to login","wpsupporteu");?></a></span>
		<?php }?>
		<hr />
		
	</div>
	
	<?php }?>
	<div class="wpseu_menu">
		<a href="#" class="wpseu_nav_link" id="wpseu_ask_tab"><?php _e("Ask!", "wpsupporteu");?></a> | 
		<a href="#" class="wpseu_nav_link" id="wpseu_questions_tab"><?php _e("Your Questions", "wpsupporteu");?> <?php if(isset($wpseu_login["answers"]) && $wpseu_login["answers"]){?><span class="wpseu_answers_no"><?php echo $wpseu_login["answers"];?></span><?php }?></a> | 
		<a href="#" class="wpseu_nav_link" id="wpseu_tips_tab"><?php _e("Articles", "wpsupporteu");?></a> |
		<a href="#" class="wpseu_nav_link" id="wpseu_promotions_tab"><?php _e("Promotions", "wpsupporteu");?></a>
	</div>
	<hr />
	<div class="wpseu_container">
		<div id="wpseu_ask_tab_content" class="wpseu_container_item" <?php if($wpseu_login["status"] == "error"){ echo 'style="display:none;"';}?>>
			<?php wpseu_ask_tab_content($wpseu_login);?>
		</div>
		<div id="wpseu_questions_tab_content" class="wpseu_container_item" style="display:none;">
			<?php wpseu_questions_tab_content($wpseu_login);?>
		</div>
		<div id="wpseu_tips_tab_content" class="wpseu_container_item" <?php if($wpseu_login["status"] == "ok"){ echo 'style="display:none;"';}?>>
			<?php wpseu_articles_tab_content($wpseu_login);?>
		</div>
		<div id="wpseu_promotions_tab_content" class="wpseu_container_item" style="display:none;">
			<?php wpseu_promotions_tab_content($wpseu_login);?>
		</div>
	</div>
	<div class="wpseu_footer">
		<hr />
		<?php echo $wpseu_login["footer"];?>
	</div>
<?php }

/**
 * Set allowed tags.
 *
 * Set allowed tags in the displayed text. Used to protect against malicious code.
 *
 * @since 1.0
 *
 * @return array List of allowed tags.
 */
function wpseu_allowed_tags(){
	$wpseu_allowed_tags = array(
		'a' => array(
			'href' => array(),
			'title' => array(),
			'target' => array()
		),
		'br' => array(),
		'em' => array(),
		'b' => array(),
		'strong' => array(),
		'p' => array(),
		'span' => array(),
		'div' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
	);
	return $wpseu_allowed_tags;	
}

/**
 * Add CSS styles.
 *
 * Add CSS styles to admin pages.
 *
 * @since 1.0
 */
function wpseu_load_css() {
        wp_register_style( 'wpseu_admin_css', plugins_url('/includes/wpsupporteu.css', __FILE__), false, "1.0.0" );
        wp_enqueue_style( 'wpseu_admin_css' );
}
add_action( 'admin_enqueue_scripts', 'wpseu_load_css' );

/**
 * Add required JS.
 *
 * Add JS to process forms and UI.
 *
 * @since 1.0
 */
function wpseu_script_load() {
?>
	<script type="text/javascript">
	jQuery( "#wpseu_question_title_input, #wpseuquestioncontentinput" ).on("focusin", function() {
		jQuery(this).removeClass("invalid");
	});
	
	jQuery(".wpseu_nav_link").on("click", function(){
		event.preventDefault();
		var container_id = "#" + jQuery(this).attr("id") + "_content";
		jQuery(".wpseu_container_item").hide("slow");
		jQuery(container_id).show("slow");
	});

	jQuery(".wpseu_login_link").on("click", function(){
		event.preventDefault();
		jQuery(".wpseu_container_item").hide("slow");
		jQuery("#wpseu_ask_tab_content").show("slow");
	});
	
	jQuery('#wpseu_question_submit').on("click", function(){
		event.preventDefault();
		jQuery(".wpseu_loading").show();
		wpseu_ask_question();
	});
	
	function wpseu_ask_question() {
		var wpseu_request = 'ask_question';
		var wpseu_token = jQuery('#wpseu_question_token').val();
		var wpseu_question_title = jQuery('#wpseu_question_title_input').val();
		var wpseu_question_content = jQuery('#wpseuquestioncontentinput').val();
		
		var wpseu_control = 0;
		
		if(wpseu_question_title == ""){
			jQuery('#wpseu_question_title_input').addClass("invalid");
			var wpseu_control = 1;
		}
		
		if(wpseu_question_content == ""){
			jQuery('#wpseuquestioncontentinput').addClass("invalid");
			var wpseu_control = 1;
		}
		
		if(wpseu_control == 1){
			jQuery(".wpseu_loading").hide();
			return;
		}
		
		var data = {
			'action': 'wpseu_action',
			'wpseu_request': wpseu_request,
			'wpseu_token': wpseu_token,
			'wpseu_question_title': wpseu_question_title,
			'wpseu_question_content': wpseu_question_content
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery(".wpseu_loading").hide();
			var response_obj = JSON.parse(response);
			if(response_obj.status == "ok"){
				jQuery("#wpseu_ask_tab_content").html(response_obj.content);
			} else {
				jQuery(".wpseu_error").remove();
				jQuery("#wpseu_ask_tab_content").prepend("<p class='wpseu_error' style='display:none;'>" + response_obj.content + "</p>");
				jQuery(".wpseu_error").show("slow");
			}
		});
	};
	
	function wpseu_login() {
		var wpseu_email = jQuery('#wpseu_email').val();
		var wpseu_key = jQuery('#wpseu_key').val();
		var wpseu_request = 'wpseu_' + jQuery('#wpseu_request').val();
		
		var data = {
			'action': 'wpseu_action',
			'wpseu_request': wpseu_request,
			'wpseu_email': wpseu_email,
			'wpseu_key': wpseu_key
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery(".wpseu_loading").hide();
			var response_obj = JSON.parse(response);
			jQuery("#wpseu_ask_tab_content").html(response_obj.ask_content);
			jQuery("#wpseu_questions_tab_content").html(response_obj.questions_content);
			if( response_obj.wpseu_login.status =='ok'){
				jQuery(".wpseu_info").hide("slow");
			}
		});
	};
	</script>
<?php }
add_action( 'admin_footer', 'wpseu_script_load' );

/**
 * Display login form.
 *
 * Displays the login form in the WPSupport.eu widget.
 *
 * @since 1.0
 *
 * @param  string $wpseu_request Optional, request type.
 * @param  boolean $wpseu_details Optional, use saved login details.
 */
function wpseu_login_form($wpseu_request="", $wpseu_details=true){
	$old_login = false;
	if($wpseu_details && get_option('wpseu_settings')){
		$old_login = get_option('wpseu_settings');
	}?>
	<form id="wpseu_login_form" method="post" action="<?php echo admin_url(); ?>">
		<p>
			<input name="wpseu_email" id="wpseu_email" type="email" placeholder="<?php _e('Email', 'wpsupporteu');?>" <?php if($wpseu_details && $old_login){echo 'value="' . $old_login["email"] . '"';}?>/><br />
			<input name="wpseu_key" id="wpseu_key" type="text" placeholder="WPSupport.eu Key" <?php if($wpseu_details && $old_login){echo 'value="' . $old_login["key"] . '"';}?>/>
			<input type="hidden" name="wpseu_request" value="<?php echo $wpseu_request;?>" id="wpseu_request" />
			<input type="hidden" name="wpseu_site" value="<?php echo site_url();?>" id="wpseu_site" />
		</p>
		<p>
			<input type="submit" name="wpseu_login_submit" id="wpseu_login_submit" class="button button-primary" value="Login">
		</p>
	</form>
	<hr />
	<p><?php _e("Request an invite for a WPSupport.eu account.", 'wpsupporteu');?> <?php _e("It's Free!", 'wpsupporteu');?></p>
	<p><a class="button button-primary" href="http://wpsupport.eu" target="_blank"><?php _e('Get an invite!', 'wpsupporteu');?></a></p>
<?php }

/**
 * Process AJAX request.
 */
function wpseu_process_ajax(){
	//Exit if there is no action set
	if(!isset($_POST['wpseu_request'])){
		die();
	}
	
	$wpseu_request = $_POST['wpseu_request'];
	
	//Check action
	switch ($wpseu_request){
		case "wpseu_login":
			if(!isset($_POST['wpseu_email']) || !isset($_POST['wpseu_key']) || !$_POST['wpseu_email'] || !$_POST['wpseu_key']){
				_e("Something went wrong. Please try again.", "wpsupporteu");
				wpseu_login_form($wpseu_request="login", $wpseu_details = false);
				die();
			}
			$wpseu_login = wpseu_server_connect($_POST['wpseu_email'], $_POST['wpseu_key']);
			
			$return_content = array();
			
			ob_start();
				wpseu_ask_tab_content($wpseu_login);
			$return_content["ask_content"] = ob_get_clean();
			
			ob_start();
				wpseu_questions_tab_content($wpseu_login);
			$return_content["questions_content"] = ob_get_clean();
			
			$return_content["wpseu_login"] = $wpseu_login;
			$return_content["answers"] = $wpseu_login["answers"];
			echo json_encode($return_content);
			die();
			break;
		case "ask_question":
			$return_content = array();
			if(!isset($_POST["wpseu_token"])){
				$return_content["status"] = "local_error";
				$return_content["content"] = __("Invalid Token. Please refresh the page and try again.", "wpsupporteu");
				echo json_encode($return_content);
				die();
			}
			if(!isset($_POST["wpseu_question_title"]) || !isset($_POST["wpseu_question_content"])){
				$return_content["status"] = "local_error";
				$return_content["content"] = __("Invalid question and/or question title. Please try again.", "wpsupporteu");
				echo json_encode($return_content);
				die();
			}
			$wpseu_question = array(
					'wpseu_question_title'   => $_POST["wpseu_question_title"],
					'wpseu_question_content' => $_POST["wpseu_question_content"]
				);
			$wpseu_login = wpseu_server_connect("","",$wpseu_question);
			if(isset($wpseu_login["question_response"]) && is_array($wpseu_login["question_response"]) && !empty($wpseu_login["question_response"])){
				if($wpseu_login["question_response"]["status"] == "ok"){
					$return_content["status"] = "ok";
					$return_content["content"] = __("Your question was submitted for approval. We will do our best to publish it and find an answer in the shortest time.", "wpsupporteu");
				} elseif ($wpseu_login["question_response"]["status"] == "error" && $wpseu_login["question_response"]["error"] == "questions_limit") {
					$return_content["status"] = "error";
					$return_content["content"] = __("You have reached the maximum number of questions for today. Please try again in 24h.", "wpsupporteu");
				} else {
					$return_content["status"] = "error";
					$return_content["content"] = __("There was a problem processing your request. Please try again.", "wpsupporteu");
				}
			}
			echo json_encode($return_content);
			die();
		default:
			die();
	}
}
add_action( 'wp_ajax_wpseu_action', 'wpseu_process_ajax' );


/**
 * Communicate with the server.
 *
 * Communicate with the WPSupport.eu server to get / send information. Checks login details.
 *
 * @since 1.0
 *
 * @param  string $wpseu_request Optional, request type.
 * @param  boolean $wpseu_details Optional, use saved login details.
 *
 * @return array The response from wpsupport.eu server.
 */
function wpseu_server_connect($wpseu_user_email="", $wpseu_user_key="", $wpseu_question = array()){
	$wpseu_allowed_tags = wpseu_allowed_tags();
	$loggedin = array(
			"status" => "",
			"error" => "",
			"questions" => array(),
			"answers" => 0,
			"articles" => array(),
			"promotions" => array(),
			"footer" => ""
		);
	if(!$wpseu_user_email || !$wpseu_user_key){
		$default_details = get_option('wpseu_settings');
		if($default_details && isset($default_details["email"]) && isset($default_details["key"])){
			$wpseu_login = get_option('wpseu_settings');
		} else{
			$wpseu_login = array(
				"email" => "example@example.com",
				"key" => "default"
			);
		}
	} else{
		$wpseu_login = array(
			"email" => $wpseu_user_email,
			"key" => $wpseu_user_key
		);
	}
		
	if(is_array($wpseu_login) && $wpseu_login['email'] && $wpseu_login['key']){
		
		//Load WP_Http class if not loaded
		if( !class_exists( 'WP_Http' ) ){
		  include_once( ABSPATH . WPINC. '/class-http.php' );
		}
		
		$send_data = array(
			'method' => 'POST',
			'redirection' => 5,
			'body' => array( 
				'wpseu_email' => $wpseu_login['email'],
				'wpseu_key' => $wpseu_login['key'],
				'wpseu_url' => site_url(),
				'wpseu_plugin' => "plugin"
				)
			);
		if(isset($wpseu_question) && is_array($wpseu_question) && !empty($wpseu_question)){
			$send_data["body"]["wpseu_question_title"] = $wpseu_question["wpseu_question_title"];
			$send_data["body"]["wpseu_question_content"] = $wpseu_question["wpseu_question_content"];
		}
		//Get login status from wpsupport.eu
		$response = wp_remote_post( "http://wpsupport.eu", $send_data);

		//If the http request generates errors
		
		if ( is_wp_error( $response ) ) {
			$loggedin["status"] = "error";
		} else{
			//No errors
			$response_args = wp_parse_args($response);
			$response_args = wp_parse_args($response_args["body"]);
			
			//Check if logged in
			if(isset($response_args["status"]) && $response_args["status"] == "ok"){
				//Logged in
				$loggedin["status"] = "ok";
				$loggedin["token"] = $response_args["token"];
				$loggedin["questions"] = $response_args["questions"];
				$loggedin["answers"] = 0;
				foreach($loggedin["questions"] as $question){
					$loggedin["answers"] += $question["answers"];
				}
				$loggedin["articles"] = $response_args["articles"];
				$loggedin["promotions"] = $response_args["promotions"];
				$loggedin["footer"] = wp_kses($response_args["footer"], $wpseu_allowed_tags);
				
				if(isset($response_args["question_response"]) && is_array($response_args["question_response"]) && !empty($response_args["question_response"])){
					$loggedin["question_response"] = $response_args["question_response"];
				}
				
				update_option('wpseu_settings', $wpseu_login);
			} else {
				//Not logged in. Return reason
				if(isset($response_args["error"]) && $response_args["error"]){
					$loggedin["status"] = "error";
					$loggedin["error"] = $response_args["error"];
					if(isset($response_args["error_count"]) && $response_args["error_count"]){
						$loggedin["error_count"] = $response_args["error_count"];
					}
					$loggedin["questions"] = $response_args["questions"];
					$loggedin["answers"] = 0;
					foreach($loggedin["questions"] as $question){
						$loggedin["answers"] += $question["answers"];
					}
					$loggedin["articles"] = $response_args["articles"];
					$loggedin["promotions"] = $response_args["promotions"];
					$loggedin["footer"] = wp_kses($response_args["footer"], $wpseu_allowed_tags);
				} else{
					$loggedin["status"] = "error";
					$loggedin["questions"] = $response_args["questions"];
					$loggedin["answers"] = 0;
					foreach($loggedin["questions"] as $question){
						$loggedin["answers"] += $question["answers"];
					}
					$loggedin["articles"] = $response_args["articles"];
					$loggedin["promotions"] = $response_args["promotions"];
					$loggedin["footer"] = wp_kses($response_args["footer"], $wpseu_allowed_tags);
				}
			}
		}
	} else{
		//No valid login details
		$loggedin["status"] = "error";
		$loggedin["error"] = "invalid_login";
		$loggedin["questions"] = $response_args["questions"];
		$loggedin["answers"] = 0;
		foreach($loggedin["questions"] as $question){
			$loggedin["answers"] += $question["answers"];
		}
		$loggedin["articles"] = $response_args["articles"];
		$loggedin["promotions"] = $response_args["promotions"];
		$loggedin["footer"] = wp_kses($response_args["footer"], $wpseu_allowed_tags);
	}
	return $loggedin;
}

/**
 * Display ASK tab content.
 *
 * Display ASK tab content based on the content received from the server.
 *
 * @since 1.0
 *
 * @param  array $wpseu_login Content.
 */
function wpseu_ask_tab_content($wpseu_login=array()){
	if(isset($wpseu_login["status"]) && $wpseu_login["status"] == "ok"){
		//Loggedin?>
		<div class="wpseu_success">
			<form id="wpseu_ask_form" name="wpseu_ask_form">
				<p><input type="text" class="wpseu_full_width" id="wpseu_question_title_input" name="wpseu_question_title_input" placeholder="<?php _e("Question title","wpsupporteu");?>" /></p>
				<input type="hidden" id="wpseu_question_token" value="<?php echo $wpseu_login["token"];?>" />
				<?php $settings = array( 'media_buttons' => false, 'textarea_rows'=>10, 'textarea_name' => 'wpseu_question_content_input', "tinymce" => false);
				wp_editor( '', "wpseuquestioncontentinput", $settings );?>
				<p><input type="submit" name="wpseu_question_submit" id="wpseu_question_submit" class="button button-primary" value="<?php _e('Submit Question','wpsupporteu');?>"><img src="<?php echo plugins_url('/includes/loadingbar.gif', __FILE__); ?>" alt="loading" class="wpseu_loading" style="display:none;"/></p>
			</form>
		</div>
	<?php } else{ 
		//Not loggedin?>
		<p><?php _e('You need to be logged into your WPSupport.eu account to ask a question.', 'wpsupporteu');?></p>
		<?php if(isset($wpseu_login["error"]) && $wpseu_login["error"] == "invalid_login"){
			//Wrong login details?>
			<?php if(isset($wpseu_login["error_count"])){?>
				<?php if($wpseu_login["error_count"] > 0){?>
					<p><?php _e("Login error. Please check your email address and app key and try again", "wpsupporteu");?></p>
					<p><?php _e("Number of tries left: ", "wpsupporteu"); echo $wpseu_login["error_count"];?></p>
					<?php wpseu_login_form("login");
				} else{?>
					<p><?php _e("Login failed multiple times. For your security we have restricted your account for 24 hours. Please try again tomorrow", "wpsupporteu");?></p>
				<?php }
			} else{?>
				<p><?php _e("Login error. Please check your email address and app key and try again", "wpsupporteu");?></p>
				<?php wpseu_login_form("login");
			}
		} elseif(isset($wpseu_login["error"]) && $wpseu_login["error"] == "invalid_url"){
			//Site not added to account?>
			<p><?php _e("Please add this site to your WPSupport.eu account", "wpsupporteu");?> <a href="http://wpsupport.eu" target="_blank"><?php _e("Click here to add it now", "wpsupporteu");?></a></p>
		<?php } else {?>
			<?php _e("Something went wrong. Please try again later", "wpsupporteu");
			wpseu_login_form("login");?>
		<?php }
	}
}

/**
 * Display QUESTIONS tab content.
 *
 * Display QUESTIONS tab content based on the content received from the server.
 *
 * @since 1.0
 *
 * @param  array $wpseu_login Content.
 */
function wpseu_questions_tab_content($wpseu_login=array()){
	$wpseu_allowed_tags = wpseu_allowed_tags();
	$questions = $wpseu_login["questions"];
	foreach($questions as $question){?>
		<h4><a href="<?php echo wp_kses($question["link"], $wpseu_allowed_tags);?>" target="_blank"><?php echo wp_kses($question["title"], $wpseu_allowed_tags);?></a></h4>
		<p><?php echo wp_kses($question["content"], $wpseu_allowed_tags);?><br />
		<a href="<?php echo wp_kses($question["link"], $wpseu_allowed_tags);?>" target="_blank"><?php echo $question["answers"] . " "; _e("New Answers","wpsupporteu");?></a>
		</p>
		<hr />
	<?php }
}

/**
 * Display ARTICLES tab content.
 *
 * Display ARTICLES tab content based on the content received from the server.
 *
 * @since 1.0
 *
 * @param  array $wpseu_login Content.
 */
function wpseu_articles_tab_content($wpseu_login=array()){
	$wpseu_allowed_tags = wpseu_allowed_tags();
	$articles = $wpseu_login["articles"];
	foreach($articles as $articles){?>
		<h4><a href="<?php echo wp_kses($articles["link"], $wpseu_allowed_tags);?>" target="_blank"><?php echo wp_kses($articles["title"], $wpseu_allowed_tags);?></a></h4>
		<p><?php echo wp_kses($articles["content"], $wpseu_allowed_tags);?></p>
		<hr />
	<?php }
}

/**
 * Display PROMOTIONS tab content.
 *
 * Display PROMOTIONS tab content based on the content received from the server.
 *
 * @since 1.0
 *
 * @param  array $wpseu_login Content.
 */
function wpseu_promotions_tab_content($wpseu_login=array()){
	$wpseu_allowed_tags = wpseu_allowed_tags();
	$promotions = $wpseu_login["promotions"];
	foreach($promotions as $promotions){?>
		<h4><a href="<?php echo wp_kses($promotions["link"], $wpseu_allowed_tags);?>" target="_blank"><?php echo wp_kses($promotions["title"], $wpseu_allowed_tags);?></a></h4>
		<p><?php echo wp_kses($promotions["content"], $wpseu_allowed_tags);?></p>
		<hr />
	<?php }
}

/**
 * Replace "code" quicktag.
 */
function wpseu_remove_quicktags( $qtInit, $editor_id )
{
	if($editor_id =="wpseuquestioncontentinput"){
		$remove_these = array('code');
		$buttons = explode(',', $qtInit['buttons']);
		
		for( $i=0; $i < count($remove_these); $i++ )
		{
			if( ($key = array_search($remove_these[$i], $buttons)) !== false)
				unset($buttons[$key]);
		}
		$qtInit['buttons'] = implode(',', $buttons);
	}
    return $qtInit;
}
add_filter('quicktags_settings', 'wpseu_remove_quicktags', 10, 2);

function wpseu_add_quicktags() {
    if (wp_script_is('quicktags')){
?>
    <script type="text/javascript">
    QTags.addButton( 'eg_pre', 'code', '<code>Insert your code here</code>', '', 'q', 'Insert Code', 111 );
    </script>
<?php
    }
}
add_action( 'admin_print_footer_scripts', 'wpseu_add_quicktags' );