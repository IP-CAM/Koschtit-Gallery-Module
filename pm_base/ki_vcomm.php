<div id="ki_viewercomment">
<?php
session_start();

/* --------------------------------------------------------- functions ------------------------------------------------------ */

if (!function_exists('file_get_contents')) {
	function file_get_contents($filename) {
		if ($handle = @fopen($filename, 'rb')) {
			$data = fread($handle, filesize($filename));
			fclose($fh);
			return $data;
		}
	}
}


if (!function_exists('file_put_contents')) {
    function file_put_contents($filename, $data) {
        $f = @fopen($filename, 'w');
        if (!$f) {
            return false;
        } else {
            $bytes = fwrite($f, $data);
            fclose($f);
            return $bytes;
        }
    }
}

function sendAmail($to, $betreff, $content){
	global $ki_admin_mail_from;
				
	$content = "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Strict//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd\">
<html xmlns=\"http://www.w3.org/1999/xhtml\"><head><title>".$betreff."</title></head><body><div style=\"font:13px Arial,Verdana,sans-serif;\">".nl2br($content)."</div></body></html>";
	
	$header  = "MIME-Version: 1.0\r\n";
	$header .= "Content-type: text/html; charset=UTF-8\r\n";
	$header .= "Content-Transfer-Encoding: 8bit\r\n";
	$header .= "From: ".$ki_admin_mail_from."\r\n";

	// verschicke die E-Mail
	@ini_set(sendmail_from, $ki_admin_mail_from);	
	@mail($to, $betreff, $content, $header);
	@ini_restore(sendmail_from);
}



/* ---------------------------------------------------------- end functions -------------------------------------------------- */

if (get_magic_quotes_gpc()) {
    function stripslashes_gpc(&$value)
    {
        $value = stripslashes($value);
    }
    array_walk_recursive($_GET, 'stripslashes_gpc');
    array_walk_recursive($_POST, 'stripslashes_gpc');
    array_walk_recursive($_COOKIE, 'stripslashes_gpc');
    array_walk_recursive($_REQUEST, 'stripslashes_gpc');
}

if(isset($_POST['file']))
	$file = rawurldecode($_POST['file']);
else
	exit;
	
if(isset($_POST['gallery']))
	$gallery = $_POST['gallery'];
else
	exit;

include_once("../ki_config/ki_setup.php");

// -------------- Sicherheitsabfragen!
if(preg_match("/[\.]*\//", $file))exit();
if(preg_match("/[\.]*\//", $gallery))exit();
if(!is_file("../".$ki_galleries.$gallery."/".$file))exit();
// ---------- Ende Sicherheitsabfragen!

if(is_file("../ki_config/".$gallery."_ki_setup.php"))include_once("../ki_config/".$gallery."_ki_setup.php");

$pwok = 0;
if(isset($_SESSION['pwquery'])){
	if($_SESSION['pwquery'] === $ki_pw)$pwok = 1;
}

if(!isset($_POST['get'])){

	if(isset($_POST['email']))
		$name = addslashes(htmlentities(rawurldecode($_POST['email']), ENT_QUOTES, "UTF-8"));
	else
		exit;
		
	if(isset($_POST['assystem']))
		$comment = addslashes(htmlentities(rawurldecode($_POST['assystem']), ENT_QUOTES, "UTF-8"));
	else
		exit;
	
	if(isset($_POST['address']))
		$address = rawurldecode($_POST['address']);
	else
		exit;
	
	if (!ini_get('date.timezone') && function_exists("date_default_timezone_set"))date_default_timezone_set('Europe/Berlin');
	
	if( stripos($name, "@") !== false ){
		echo "2";
		exit();
	}
	
	$ip = $_SERVER["REMOTE_ADDR"];
	$date = date("m/d/Y");
	$time = date('H').":".date('i');
	
	$moderate = "";
	if($ki_moderate_posts == 1)$moderate = "._.1";
	$post = $name."._.".$date."._.".$time."._.".$ip.$moderate."\r\n".$comment."\r\n:_:\r\n";
	
$ki_db->query("INSERT  " . $db_prefix . "koschtit_gallery_viewercomment SET 
                              name='" . $name ."',
                              address='".$ki_db->escape($address). "',
                              date=NOW(),
                              folder_name='" .$ki_db->escape($gallery)."',
                              image_name='" . $ki_db->escape($file)."',
                              comment='".$ki_db->escape($comment)."'");
	if($ki_admin_mail != 0){
		$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
		$address = "http".$s."://".$address;
		$message = stripslashes($comment);
		
		$betreff = "New comment on picture (".$_SERVER['HTTP_HOST'].")";
		if($ki_moderate_posts == 1){
			$content = "A new comment has been made for one of your images. Visit '<a href='".$address."'>".$address."</a>' and flip the picture to read all comments for this image.\nThis is the last comment:\n\n\"".$message."\"\n\nThis comment is moderated and is not readable in public yet. You have the option publish this comment when you login as admin.\n\n\n<small>E-Mail generated by KoschtIT Image Gallery.\n<a href='http://koschtit.tabere.net/en/'>http://koschtit.tabere.net/en/</a></small>";
		} else {
			$content = "A new comment has been made for one of your images. Visit '<a href='".$address."'>".$address."</a>' and flip the picture to read all comments for this image.\nThis is the last comment:\n\n\"".$message."\"\n\n\n<small>E-Mail generated by KoschtIT Image Gallery.\n<a href='http://koschtit.tabere.net/en/'>http://koschtit.tabere.net/en/</a></small>";
		}
		sendAmail($ki_admin_mail_to, $betreff, $content);
	}
}
?>


<?php
if(isset($_POST['counter']) || isset($_POST['publish'])){
	$newfile = "";	
}
	
$query = $ki_db->query("SELECT * FROM  " . $db_prefix . "koschtit_gallery_viewercomment WHERE
                              folder_name='" .$gallery."' AND
                              image_name='" . $file."' ORDER BY id DESC");
                              $results = $query->rows;
          if(isset($results[0]['date'])){
          foreach($results as $result){
         			
?>

<div class="ki_comment_date">&raquo; 
<span class="ki_vcomm_date">
<?php echo $result['date'] ?> </span><span class="ki_comment_name" style=""><?php echo $result['name'];?></span></div>
<div class="ki_viewercomment">

<?php echo nl2br(stripslashes($result['comment'])) ?>
</div>


<?php
 }
}
?>
</div>


