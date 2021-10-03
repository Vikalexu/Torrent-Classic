<?php
// Start session
session_start();
// Load config
require '../config.php';
// Load libs
require '../core/captcha/index.php';
require '../core/torrent/vendor/autoload.php';
require '../core/torrent/torrent.class.php';

// Captcha class
$captcha = new SimpleCaptcha();

function cleanInput($input) {
   
    $search = array(
      '@<script[^>]*?>.*?</script>@si',   // Strip out javascript
      '@<[\/\!]*?[^<>]*?>@si',            // Strip out HTML tags
      '@<style[^>]*?>.*?</style>@siU',    // Strip style tags properly
      '@<![\s\S]*?--[ \t\n\r]*>@'         // Strip multi-line comments
    );
   
    $output = preg_replace($search, '', $input);
    return $output;

}

if(!empty($_FILES['file'])) {
	if ($_POST['captcha'] === $_SESSION['captcha']) {
		$extension = '.'. pathinfo(cleanInput($_FILES['file']['name']))['extension'];
		$file = md5(file_get_contents($_FILES['file']['tmp_name']));
		$dirfile = '/tmp/'. $file. $extension;
		rename($_FILES['file']['tmp_name'], $dirfile);
		//$torrent = BitTorrent\Torrent::createFromPath($dirfile, array('http//:'. $_SERVER["SERVER_NAME"].':80/announce.php', 'udp://tracker.opentrackr.org:1337/announce'))
		$torrent = BitTorrent\Torrent::createFromPath($dirfile, 'udp://tracker.opentrackr.org:1337/announce')
		->withComment(cleanInput($_FILES['file']['name']). ', created '. date("F j. Y. g:i a"));
		$torrent->save('./file/'. $file. '.torrent');

		$i = 'File was uploaded as "<a href="./file/'. $file. $extension. '">'. $file. '.torrent</a>" !';
	} else {
		$i = 'Captcha wrong!';
	}
} else {
	$i = '';
}

function formatSizeUnits($bytes)
{
	if ($bytes >= 1073741824)
	{
		$bytes = number_format($bytes / 1073741824, 2) . ' GB';
	}
	elseif ($bytes >= 1048576)
	{
		$bytes = number_format($bytes / 1048576, 2) . ' MB';
	}
	elseif ($bytes >= 1024)
	{
		$bytes = number_format($bytes / 1024, 2) . ' KB';
	}
	elseif ($bytes > 1)
	{
		$bytes = $bytes . ' bytes';
	}
	elseif ($bytes == 1)
	{
		$bytes = $bytes . ' byte';
	}
	else
	{
		$bytes = '0 bytes';
	}

	return $bytes;
}

$db = new PDO('mysql:host='.__DB_SERVER.';dbname='.__DB_NAME.';charset=utf8', __DB_USERNAME, __DB_PASSWORD);
?>
<!DOCTYPE html>
<html>
  <head>
	<title>Torrent Classic</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="stylesheet" href="style.css">
  </head>
  <body>
    <h1 class="logo">Torrent Classic</h1>
	<div id="wrapper">
	  <div class="panel">
		<h3>Current Peers</h3>
		<?php
		$stmt = $db->query("SELECT * FROM peers");
		$count = 0;
		while ($peer = $stmt->fetch()) {
		$count++;
		?>
		<div class="c">
			Useragent: <?= $peer['userAgent'] ?><br>
			Seed: <?= $peer['isSeed']? "Yes": "No"?><br>
			IP: <?= $peer['ipAddress' ]?><br><br>
			PeerID: <?= $peer['peerId']?><br>
			Hash: <?= $peer['infoHash']?><br>
			Key: <?= $peer['key']?>
		</div>
		<?php
		}
		if ($count <= 0) {
			echo 'No peers.<br><br>';
		}
		$stmt = $db->query("SELECT * FROM stats");
		$stats = $stmt->fetch();
		?>
		<br>
		<hr>
		Last update: <?= date("F j. Y. g:i a", $stats['lastupdate'])?>
	</div>
	<div class="panel">
	  <h3>Upload</h3>
	  <form class="c" enctype="multipart/form-data" method="POST">
		<p><?php echo $i; ?></p>
		<?php echo '<img class="mb-3" src="'. $captcha->CreateImage(). '">'; ?><br>
		<input type="text" name="captcha" placeholder="What do you see on the captcha"></input><br style="margin-bottom:5px;">
	    <input type="file" name="file"></input><br style="margin-bottom:5px;">
		<input type="submit" value="Upload File"></input>
		<br><br><br>
	  </form>
	</div>
	<div class="panel">	
	  <h3>Torrent Files</h3>
	  <?php
	  $dir_open = opendir('./file');
	  $count = 0;
	  while(false !== ($filename = readdir($dir_open))){
		  if($filename != "." && $filename != "..") {
			$count++;
			echo '<div class="c">';
			$torrent = new Torrent('./file/'. $filename);
			echo 'Name: <a href="/file/'. $filename. '">'. $filename. '</a>';
			echo '<br>Magnet: <input readonly value="', $torrent->magnet(false). '">';
			echo '<br>Meta: ', $torrent->comment();
			echo '<br>Hash: ', $torrent->hash_info();
			echo '<br>Piece length: ', formatSizeUnits($torrent->piece_length());
			echo '<br>Size: ', formatSizeUnits($torrent->size());
			//$torrent->send();
			echo '</div>';
			echo '<br>';
		}
	  }
	  if ($count <= 0) {
		echo 'No files.<br><br>';
	  }
	  closedir($dir_open); 
	  ?>
	  </div>
	</div>
    <div class="footer">
	    Server Time: <?= date("F j. Y. g:i a"); ?><br>
    </div>
  </body>
</html>
