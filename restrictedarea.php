<?php
/*
Plugin Name: RestrictedArea
Plugin URI: http://vaso.sma.hu/blog/restricted-area-plugin-for-wordpress/
Description: Restrict a section in your post or page to the logged in users only.
Version: 1.0
Author: Ferenc Vasóczki
Author URI: http://www.vaso.hu/blog/
Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
*/

/* 
Usage:
Please read the readme.txt file.
If you not found it, download this plugin from this site:
http://vaso.sma.hu/blog/restricted-area-plugin-for-wordpress/
*/

/*  
License:

		Copyright 2010 Ferenc Vasóczki  (email : vasoczki.ferenc [at] gmail [dot] com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define ('BGDS',DIRECTORY_SEPARATOR);
	if (class_exists(RestrictedArea)) {
		$ra = new RestrictedArea();	
		
	}

	if (is_admin()) {
		add_action('admin_menu', array($ra, 'AdminMain'));
		if ($_GET["page"] == $ra->page) {
			$ra->RegisterJavascripts();
			add_action('init', array($ra, 'Init'));
			load_plugin_textdomain( $ra->name, false, dirname( plugin_basename( __FILE__ )).'/localization' );
		}
	} else {
		//This is the frontend page handler
		add_shortcode('restrictedarea',array($ra,'Process'));	
	}

	class RestrictedArea {
		var $text;
		var $show_logintext; 
		var $name;
		var $ver;
		var $title;
		var $msg;
		var $page;
		var $url;
		
		function RestrictedArea() {
			$this->name = 'restrictedarea';
			$this->title = 'Restricted Area';
			$this->ver = '1.0';
			$this->msg = '';
			$this->url = WP_PLUGIN_URL."/".$this->name;
			$this->page = 'restrictedarea_page';
			register_activation_hook(__FILE__,array($this, 'Install'));
			register_deactivation_hook(__FILE__,array($this, 'UnInstall'));
		}
		
		function RegisterJavascripts() {
			wp_register_style('ColorPickerCss', $this->url."/js/colorpicker/css/colorpicker.css");
			wp_enqueue_style( 'ColorPickerCss');
			wp_enqueue_script('jquery');
			wp_register_script('ra_colorpicker',$this->url."/js/colorpicker/js/colorpicker.js");
			wp_enqueue_script('ra_colorpicker');	
		}
		
		function Init() {
			$action = $_GET["ra_action"];
			
			switch ($action) {
				case "update_options":
					if (isset($_POST["op"]) && $_POST["op"] == 'update') {
						$show_logintext = 'no';
						$text = $_POST["text"];	
						$border = str_replace("#",'',$_POST["ra_border"]);
						if ($_POST["show_logintext"] == 1) {
							$show_logintext = 'yes';
						}
						
						$options = array();
						$options["text"] = $text;
						$options["show_logintext"] = $show_logintext;
						$options['border_color'] = $border;
						update_option($this->name,$options);
						wp_redirect('options-general.php?page='.$this->page.'&u=1');
					}
					break;	
			}
		}
		
		function Install() {
			$options = array();
			$options = get_option($this->name);
			if (empty($options['serverid'])) {
				$options['serverid'] = md5(get_bloginfo('siteurl'));
				$options['text'] = __('Sorry, this content is available only for logged in users.' ,$this->name);
				$options['show_logintext'] = 'yes';
				$options['border_color'] = 'ff0000';
				add_option($this->name, $options, '', 'yes');
			}
		}
	
		function UnInstall() {
			delete_option($this->name);
		}
		
		function AdminMain() {
			add_options_page($this->title." version: ".$this->ver, $this->title, '10', $this->page, array($this, 'RaOptionPage'));
		}
		
		function RaOptionPage() {
			$this->ShowMainForm();
		}
		
		function ShowMainForm() {
			if (!isset($_POST["op"])) {
				$checked = '';
				$options = get_option($this->name);
				$_POST["text"] = $options["text"];
				$_POST["ra_border"] = $options["border_color"];
				if ($options["show_logintext"] ==  'yes') {
					$checked = 'checked="checked"';
				}
			}
?>
			<script type="text/javascript">
				jQuery(function() {
					jQuery('#ra_border').ColorPicker({
						onSubmit: function(hsb, hex, rgb, el) {
							jQuery(el).val(hex);
							jQuery(el).ColorPickerHide();
						},
						onBeforeShow: function () {
							jQuery(this).ColorPickerSetColor(this.value);
						}
					})
					.bind('keyup', function(){
						jQuery(this).ColorPickerSetColor(this.value);
					});
				});
					
			</script>
			
			<div class="wrap">
				<h2><?php echo $this->title; ?></h2>
				<form method="post" action="options-general.php?page=<?php echo $this->page."&ra_action=update_options"?>">
					<div style="padding-bottom: 5px;"><?php _e('Text if the user not logged in:', $this->name);?></div>
					<div style="padding-bottom: 10px;"><input style="width: 400px;" type="text" name="text" value="<?php echo $_POST["text"]?>" /></div>
					<div style="padding-bottom: 10px;"><input type="checkbox" name="show_logintext" value="1"<?php echo $checked; ?> /> <?php _e('Show the login link.',$this->name);?></div>
					<div style="padding-bottom: 5px;"><?php _e('Border of the text:', $this->name);?></div>
					<div style="padding-bottom: 20px;"><input id="ra_border" name="ra_border" type="text" value="<?php echo $_POST["ra_border"]; ?>" /></div>
					<div id="colorSelector">
						<div style="background-color: #<?php echo $_POST["ra_border"]; ?>"></div>
						
					</div> 
					<div><input type="submit" value="<?php _e('Update', $this->name);?>" /> </div>
					<input type="hidden" name="op" value="update" />
				</form>
			</div>
<?			
		}
		
		/********************************************/
		/************ Frontend Functions ************/
		/********************************************/
		
		function Process($args, $content=null) {
			$html = $content;
			if ( is_user_logged_in() ) {
				//Checking the level, if needs.
				if (is_array($args) && array_key_exists('level',$args)) {
					$udata = wp_get_current_user();
					$ulevel = $udata->wp_user_level;
					if ($ulevel < $args["level"]) {
						$html = $this->GetHtml();
					}
				}
			} else { 
				$html = $this->GetHtml();
			}
			return $html;
		}
		
		
		function GetHtml() {
			$options = get_option($this->name);
			//pre ($options);
			$html = '<p style="padding: 5px; border: 1px solid #'.$options["border_color"].'">'."\n";
			$html .= $options["text"]."\n";
			if ($options["show_logintext"] == 'yes') {
				$html .= '<br /><a href="'.get_bloginfo('wpurl').'/wp-login.php?action=login">'.__('Login',$this->name).'</a> or <a href="'.get_bloginfo('wpurl').'/wp-login.php?action=register">'.__('register.',$this->name).'</a>'."\n";
			}
			$html .= '</p>'."\n";	
			return $html;
		}
	} //Class end.
?>