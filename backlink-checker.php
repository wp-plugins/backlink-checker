<?php
/*
Plugin Name: Backlink Checker
Plugin URI:  https://wordpress.org/plugins/backlink-checker/
Description: A WordPress plugin that let you check backlinks using free MOZ API
Version:     1.0
Author:      Sunny Verma
Author URI:  http://99webtools.com
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Domain Path: /lang
Text Domain: backlink
*/
add_action( 'plugins_loaded', 'backlink_load_textdomain' );
function backlink_load_textdomain() {
  load_plugin_textdomain( 'backlink', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' ); 
}

add_action('admin_menu', 'backlink_menu');
function backlink_menu() {
	add_options_page(__('Backlink Checker Settings','backlink'), __('Backlink Checker','backlink'), 'manage_options', 'backlink-checker.php', 'backlink_page_function');
	add_menu_page( __('Backlink Report','backlink'), __('Backlink Report','backlink'), 'manage_options', 'backlink-report.php', 'backlink_report', 'dashicons-admin-links', 76 );
	add_action( 'admin_init', 'register_backlink_setting' );
}
function register_backlink_setting() {
	register_setting( 'backlink_options', 'mozmember' ); 
	register_setting( 'backlink_options', 'mozsecret' ); 
} 
function backlink_page_function(){
?>
<div class="wrap">
		<h2><?php _e('Backlink Checker Settings','backlink'); ?></h2>
        <form method="post" action="options.php">
<?php
 settings_fields('backlink_options');
 do_settings_sections( 'backlink_options' );
?>
<div class='wrap'>
 <table class="form-table">
        <tr valign="top">
        <th scope="row"><?php _e('Moz Access ID','backlink'); ?></th>
        <td><input type="text" name="mozmember" value="<?php echo esc_attr( get_option('mozmember') ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row"><?php _e('Moz Secret Key','backlink'); ?></th>
        <td><input type="text" name="mozsecret" value="<?php echo esc_attr( get_option('mozsecret') ); ?>" /></td>
        </tr>
    </table>
    <?php submit_button();?>
</form>
<?php printf(__('To get MOZ API credentials visit %s','backlink'),'<a href="https://moz.com/products/api" target="_blank">https://moz.com/products/api</a>'); ?></div>
</div>
<?php
}

function get_moz_backlinks()
{
	$accessID = get_option('mozmember');
	$secretKey = get_option('mozsecret');
	// Set your expires for five minutes into the future.
	$expires = time() + 300;
	// A new linefeed is necessary between your AccessID and Expires.
	$stringToSign = $accessID."\n".$expires;
	// Get the "raw" or binary output of the hmac hash.
	$binarySignature = hash_hmac('sha1', $stringToSign, $secretKey, true);
	// We need to base64-encode it and then url-encode that.
	$urlSafeSignature = urlencode(base64_encode($binarySignature));
	// This is the URL that we want link metrics for.
	$objectURL = get_site_url();
	// Now put your entire request together.
	// This example uses the Mozscape URL Metrics API.
	$requestUrl = "http://lsapi.seomoz.com/linkscape/links/".urlencode($objectURL)."?Scope=page_to_subdomain&SourceCols=4&LinkCols=10&TargetCols=0&Limit=500&Filter=external&AccessID=".$accessID."&Expires=".$expires."&Signature=".$urlSafeSignature;
	$content = wp_remote_get($requestUrl,array('timeout'=>60));
	return json_decode($content['body'],true);
}
function backlink_report()
{
	$err=0;
	if(false===($data=get_transient('backlink_report')))
	{
		if(get_option('mozsecret') && get_option('mozmember'))
		{
		$data=get_moz_backlinks();
		set_transient('backlink_report',$data,WEEK_IN_SECONDS);
		}
		else 
		$err=1;
	}
	if($err)
	{
		echo '<h2>'.__('Backlink Report','backlink').'</h2>';
		echo '<div class="error">'.sprintf(__('Please goto <a href="%s">Settings Page</a>. ','backlink'),admin_url( 'options-general.php?page=backlink-checker.php' ));
		_e('And add MOZ API Credentials.','backlink');
		echo '<br>';
		printf(__('To get MOZ API credentials visit %s','backlink'),'<a href="https://moz.com/products/api" target="_blank">https://moz.com/products/api</a>');
		 echo '</div>';
	}
	else
	{
	?>
	<h2><?php _e('Backlink Report','backlink'); ?></h2>
	<table class="widefat fixed striped" cellspacing="0">
<thead><tr><th class="column-comments">#</th><th class="column-title"><?php _e('Backlink','backlink'); ?></th><th class="column-title"><?php _e('Anchor Text','backlink'); ?></th><th class="column-categories"><?php _e('Type','backlink'); ?></th></tr></thead>
<tbody id="the-list">
	<?php
	$i=0;
	if(count($data))
	{
	foreach($data as $r)
	{
		$i++;
	?>
	<tr><td class="column-comments"><?php echo $i; ?></td><td class="column-title"><a href="http://<?php echo $r['uu']; ?>" target="_blank"><?php echo $r['uu']; ?></a></td><td class="column-title"><?php echo $r['lnt']; ?></td><td class="column-categories"><?php echo ($r['lf']&1)?"Nofollow":"Dofollow"; ?></td></tr>
	<?php
}
}
else
echo "<tr><td colspan=\"4\">".__('No Backlink Found','backlink')."</td></tr>"
?>
</tbody>
</table>
<?php
}
}
?>
