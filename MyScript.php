<?php
/* 
Plugin Name: My Script
Plugin URI: http://weibo.com/lijohnson
Description: a plugin help create and edit code(or anything text) online 
Author: Johnson
Version: 1.0 
Author URI: http://weibo.com/lijohnson 
*/
define("MYSCRIPT_OPTION" , "MyScript");
if( !class_exists("MyScript") ){

  class MyScript{
		function addOptionPage(){
			if(function_exists('add_options_page')){
				add_options_page(__("MyScript", "MyScript"), __("MyScript", "MyScript"), 9,__file__ , array(&$this,"optionPage"));
			}
		}
		
		function optionPage(){

			//add or update script
			if( isset( $_POST['script']) ){
				$data['name'] = $_POST['name'] ;
				$data['script'] = $_POST['script'] ;
				if(strlen(trim($_POST['contentType'])) > 0) $data['contentType'] = $_POST['contentType'] ;
				$data['random'] = "adf";
				
				$files = get_option(MYSCRIPT_OPTION);
				if(!is_array($files))$files = array();
				
				$key = "MyScript_".$data['name'] ;
				$files[] = $key ;
				$files = array_unique($files);
				update_option("TOD_".$key , $data);
				update_option(MYSCRIPT_OPTION , $files);
				$message = $data['name'].' save success';

			}//print script for edit
			else if( isset($_POST['echo_script']) ){
				$data = get_option("TOD_".$_POST['echo_script']);
			}//delete script
			else if( isset($_POST['delect_script']) ){
				$files = get_option(MYSCRIPT_OPTION);
				foreach ($files as $key => $file) {
					if( $file == $_POST['delect_script'] ){
						unset($files[$key]); 
						delete_option("TOD_".$file);
						update_option(MYSCRIPT_OPTION , $files);
						$message = $file.' delete success';
						break;
					}
				}
				
			}

			wp_enqueue_script('jQuery');			
			wp_enqueue_script('editArea' , get_site_url()."/wp-content/plugins/MyScript/js/ace/ace.js" );
			
			if( $message ){
					echo "<div class=updated ><p>$message</p></div>";
				}
			?>

			<script>
			(function($){
				if(!$)return;
				var aceOption = <?php echo json_encode($this->getAceOption(dirname(__file__)."/js/ace")) ; ?>;
				window.localStorage = window.localStorage || {};

				$(function(){
					var $text = $("textarea[name='script']");
					var $theme = $("select[name=theme]");
					var $mode  = $("select[name=mode]");
					var editor = window.editor= ace.edit("editor");
					var updateTheme = function(theme){ theme && $theme.val(theme); theme = $theme.val(); editor.setTheme("ace/theme/"+theme); localStorage.myScriptTheme = theme;};
					var updataMode  = function(mode ){ mode && $mode.val(mode); mode = $mode.val();editor.getSession().setMode("ace/mode/" + mode);localStorage.myScriptMode = mode;};
					$.each(aceOption,function(k,data){
						$sel = $("select[name="+k+"]");
						$.each(data , function(i,v){
							$sel.append("<option value="+v+">"+v+"</option>");
						});
					});
					editor.setValue($text.val());
			    	$theme.change(function(){updateTheme()});
			    	$mode.change(function(){updataMode();});
			    	editor.getSession().on('change', function(e) {
					   $text.val(editor.getValue());
					});
					updateTheme(localStorage.myScriptTheme);
			    	updataMode(localStorage.myScriptMode);
				});
			})(window.jQuery);
			</script>
			<style>
				#myScript{margin: 5px 15px 2px;}
				#myScript i.split{border-right:1px solid rgb(177, 177, 177);margin: 5px;color: white;font-style: normal;display: inline-block;}
				#myScript .fileList,#myScript .form{ float: left;}
				#myScript .fileList{ width: 100px ;}
				#myScript .fileList table{width: 100%;}
				#myScript .fileList td a{display: inline-block;    width: 100px;    overflow: hidden;    word-wrap: normal;}
				#myScript .form{ width: 80%;margin-left: 10px; }
			</style>
			<div class=wrap id='myScript' >
				<?php 
					$files = get_option(MYSCRIPT_OPTION);
					if(!is_array($files))$files = array();
					echo '<table class="wp-list-table widefat plugins fileList">';
					echo '<tr><th width=100 >name</th><th colspan=2 >action</th></tr>';
					foreach($files as $f){
						$fileData = get_option("TOD_".$f);
						echo "<tr>";
						echo "<td><a title='$fileData[contentType]-$fileData[name]' href=".get_site_url()."/wp-content/plugins/MyScript/load-script.php?script=$f target='_blank' >$fileData[name]</a></td>";
						echo "<td><form method='post' action > <input type='hidden' name='echo_script' value='$f' /><input type='submit' value='edt' class=button /></form></td>";
						echo "<td><form method='post' action onsubmit=\"return confirm('sure delete ?')\" > <input type='hidden' name='delect_script' value='$f' /><input type='submit' value='del' class=button /></form></td>";
						echo "</tr>";
					}
					echo "</table>";
				?>
				<div class=form >
					<form method='post' action>
						<a class='button button-primary' value='new' href='javascript:location.reload()' >new</a><i class=split >.</i>
						<input type=submit class='button button-primary' value='save' /><i class=split >.</i>
						<label>fileName<input type=text name='name' value='<?php echo $data['name']; ?>' required /></label><i class=split >.</i>
						<label>contentType
							<?php 
								echo "<select name=contentType required>";
								foreach ($this->getContentType() as $contentType) {
									$selected = $data['contentType'] == $contentType ? 'selected' :'';
									echo "<option value=$contentType $selected >$contentType</option>";
								}
								echo "</select>";
							?>
						</label><i class=split >.</i>
						<label>mode<select name=mode ></select> </label><i class=split >.</i>
						<label>theme<select name=theme ></select></label>
						<?php echo  "<textarea name='script' id=script style='margin: 1px; height: 90%; width: 100%; display:none' >".stripslashes($data['script']) ."</textarea>";?>
					</form>
					<div id='editor' style=" margin: 1px; height: 500px; width: 98%; "></div>
				</div>
			</div>
						
			<?php
			
		}

		private function getAceOption($dir) {
			$handle=opendir($dir);
			$fileList = array();
			while($file=readdir($handle)) {
				if ( $file =="." ||  $file =="..") continue;
				
				$type = preg_split("/\-/", $file);
				if( $type[0] != 'mode' && $type[0] != 'theme' )continue;

				$type[1] = preg_split("/\./", $type[1]);
				$fileList[$type[0]][] = $type[1][0];
			}
			closedir($handle);
			//$fileList['mode'] = array('css','javascript');
			if( is_array($fileList['mode']) ){
				sort($fileList['mode']);
			}
			if( is_array($fileList['theme']) ){
				sort($fileList['theme']);
			}
			return $fileList;
		}

		private function getContentType(){
			$contentType = array('application/javascript' ,'text/javascript','text/css' ,'text/plain' ,'text/xml' ,'text/html' );
			return $contentType;
		}
	}
}

$ms = new MyScript();
add_action('admin_menu', array(&$ms,"addOptionPage"));

?>
