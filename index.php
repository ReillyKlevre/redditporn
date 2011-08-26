<?php

include 'curl.php';

//error_reporting(0);
$query = $_SERVER['QUERY_STRING'];
echo "query:   ".$_SERVER['QUERY_STRING'];

$page_title = '';

$param_q = str_replace(" ", "+", $_GET['q']); //http://www.reddit.com/r/*/
$param_m = $_GET['after']; //http://www.reddit.com/r/reddit.com/?count=25&after=t3_8e5ly&
$param_s = $_GET['sort']; //http://www.reddit.com/r/reddit.com/new/?sort=new - new|rising|top
$param_t = $_GET['t']; //http://www.reddit.com/r/reddit.com/top/?t=all - all|day|hour|week|month|year
$param_x = $_GET['x'];
$param_g = $_GET['gw'];

// Build request url
$subreddit = 'r/'.$subreddit;
$format = '.json?';
$reddit_url = 'http://www.reddit.com/r/';

$ref = getenv("HTTP_REFERER"); 
echo $ref; 

echo "\n".$param_g;

if($param_q == "") {
	$param_q = 'all';
}

if(isset($param_q)) {
	$reddit_url .= $param_q;
} 

$short_url = str_replace("reddit.com", "redditporn.com", $reddit_url);
if(substr($short_url, -1) != '/') $short_url .= '/';

if($param_s == 'top') {
	$reddit_url .= '/top';
}
if($param_s == 'new') {
	$reddit_url .= '/new';
}

$next_url = str_replace("reddit.com", "redditporn.com", str_replace("http:////www.", "", $reddit_url)) . '/?count=100&x=1';
$reddit_url .= '/.json?limit=100';

if(isset($param_s)) {
	$reddit_url .= '&sort='.$param_s;
}
if(isset($param_t)) {
	$reddit_url .= '&t='.$param_t;
}
if(isset($param_m)) {
	$reddit_url .= '&after='.$param_m;
	$next_url .= '&after='.$param_m;
}

echo "\nreddit_url: ".$reddit_url;
echo "\nnext_url:   ".$next_url;

if(!isset($param_t)) {
	$param_t = 'd';
}

$bSubreddit = false;
if($param_q == "all") $bSubreddit = true;

function get_url($url) {

	$content = '';
	$cur_time = time();

	$filename = "requests/".md5($url).".json";
	//echo $filename;

	$getNew = FALSE;

	if (file_exists($filename)) {
		//echo '<br>file exists';
		$file = basename($filename, ".json");
		$old_time = filemtime($filename);
		//echo '<br> Prev time: '.$old_time;
		$mins = ($cur_time - $old_time);
		//echo " -- ".$mins;
		if($mins >= 300) {
			$getNew = TRUE;
		} else {
			$content = file_get_contents($filename);
		}
	} else {
		//echo '<br>file does not exists';
		$getNew = TRUE;
	}

	if($getNew) {
		//echo '<br> getting new';

		$needLogin = TRUE;
		if($needLogin) {
			//echo '<br>need login';

			$login_url = 'http://www.reddit.com/api/login/username';
			$curl = new mycurl();
			$curl->setCookiFileLocation('requests/cookie.txt');
			$data = array(
			     		'api_type' => 'json',
			      		'user' => 'ReillyKlevre',
			      		'passwd' => 'password',
			);
			$curl->setPost($data);
			$curl->createCurl($login_url);
			if($curl->getHttpStatus() != 200) {
				echo '<br>LOGIN ERROR > HTTP STATUS: '.$curl->getHttpStatus();
				exit();
			}
		}

		
		//echo '<br>after: '.$url;
			
		$curl = new mycurl();
		$curl->setCookiFileLocation('requests/cookie.txt');
		$curl->createCurl($url);
		if($curl->getHttpStatus() != 200) {
			echo '<br>REDDIT_URL '.$url.' > HTTP STATUS: '.$curl->getHttpStatus();
			exit();
		}
		$content = $curl->__tostring();

		if($content) {
			$fp = fopen($filename, 'w');
			fwrite($fp, $content);
			fclose($fp);
		}
	}
	return $content;
}

function url_to_md5($url) {
  // Get extension
  $ext = pathinfo($url, PATHINFO_EXTENSION);
  if($ext == '') $ext = 'asdf';
  
  // Convert url to md5 and split up to folders
  $fname = md5($url);
  $folder =  "images/".substr($fname, 1, 1);
  @mkdir($folder, 0775);
  $sub_folder = substr($fname, 2, 2);
  @mkdir($folder.'/'.$sub_folder.'/', 0775);
  $fname = $folder.'/'.$sub_folder.'/'.substr($fname, 3).'.'.$ext;	
  return $fname;
}

// Get specific subreddit info
if(isset($param_q) && ($param_q != 'all') && (strpos($param_q, '+') === false)) {
	$about_url = 'http://www.reddit.com/r/'.$param_q.'/about.json';
	//echo $about_url;
	$content = get_url($about_url);
	if($content) {
		$json = json_decode($content,true);
		$page_title = $json['data']['title'];
	}
}

if(!isset($param_x)) {
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
      "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php
echo '<title>'; 
	if($page_title) echo $page_title;
	else echo 'reddit: the front end for imgur';
echo '</title>';
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<link type="text/css" rel="stylesheet" href="/static/style.css"/>
</head>
<body class="mosaics">
<div id="main">
	<a href="/" id="logo"><img src="/static/images/logo.png" alt="MOSAIC Logo" width="227" height="45"/></a>
	<div id="nav">
		<a id="view_mosaic" href="/mosaics/all/" class="active"><img src='/static/images/blank.png' width="35" height="35" alt="mo"/></a>
		<a id="view_list" href="/list/all/"><img src='/static/images/blank.png' width="35" height="35" alt="li"/></a>
		<a id="sort_time" href="/mosaics/all/all/" class="active"><img src='/static/images/blank.png' width="35" height="35" alt="ti"/></a>
		<span class="sort_time_data data">
			<a href='<?php echo $short_url.'top/?sort=top&t=all'; ?>'>All 
				<img src='/static/images/checkbox<?php if($param_t == 'a') echo '_active'; ?>.png' class='check right' width="15" height="15" alt="x"/></a>

			<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
			<a href='<?php echo $short_url.'top/?sort=top&t=day'; ?>'>Today 
				<img src='/static/images/checkbox<?php if($param_t == 'd') echo '_active'; ?>.png' class='check right' width="15" height="15" alt="x"/>
			</a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/><a href='<?php echo $short_url.'top/?sort=top&t=week'; ?>'>Week <img src='/static/images/checkbox<?php if($param_t == 'w') echo '_active'; ?>.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/><a href='<?php echo $short_url.'top/?sort=top&t=month'; ?>'>Month <img src='/static/images/checkbox<?php if($param_t == 'm') echo '_active'; ?>.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/><a href='<?php echo $short_url.'top/?sort=top&t=year'; ?>'>Year <img src='/static/images/checkbox<?php if($param_t == 'y') echo '_active'; ?>.png' class='check right' width="15" height="15" alt="x"/></a>
		</span>
		<a id="sort_cat" href="/mosaics/all/?limit=none&amp;sort=&amp;love_limit=none"><img src='/static/images/blank.png' width="35" height="35" alt="ca"/></a>
		<span class="sort_cat_data data">
		<a href='/mosaics/all/?limit=none&amp;sort=&amp;love_limit=none' style="color: ">all<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a><img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/none/?limit=none&amp;sort=&amp;love_limit=none' style="color: ">none<img src='/static/images/checkbox.png' class='check right' width="15" height="15" alt="x"/></a><img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/general/?limit=none&amp;sort=&amp;love_limit=none' style="color: #FFFC17">GENERAL<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/fashion/?limit=none&amp;sort=&amp;love_limit=none' style="color: #FF0075">FASHION<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/filmtv/?limit=none&amp;sort=&amp;love_limit=none' style="color: #4EE2EC">FILM/TV<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/artdesign/?limit=none&amp;sort=&amp;love_limit=none' style="color: #9172EC">ART/DESIGN<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/fooddrinks/?limit=none&amp;sort=&amp;love_limit=none' style="color: #6AFB92">FOOD/DRINKS<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/music/?limit=none&amp;sort=&amp;love_limit=none' style="color: #FFF8C6">MUSIC<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/photography/?limit=none&amp;sort=&amp;love_limit=none' style="color: #3BB9FF">PHOTOGRAPHY<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/architecture/?limit=none&amp;sort=&amp;love_limit=none' style="color: #F88158">ARCHITECTURE<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/techgear/?limit=none&amp;sort=&amp;love_limit=none' style="color: #52D017">TECH/GEAR<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		<img src="/static/images/hr.png" alt="---" height="2" width="100%" class="hr"/>
		<a href='/mosaics/special/?limit=none&amp;sort=&amp;love_limit=none' style="color: #E41B17">SPECIAL<img src='/static/images/checkbox_active.png' class='check right' width="15" height="15" alt="x"/></a>
		</span>
		<a id="shuffle" href="?q=random"><img src='/static/images/blank.png' width="35" height="35" alt="s"/></a>
		<a id="search" href="#"><img src='/static/images/blank.png' alt='f' width="35" height="35"/></a>
		<a id="profile" href="."><img src='/static/images/blank.png' width="35" height="35" alt="p"/></a>
		<span class="profile_data data">
		<a href='/accounts/login/facebook/' class='fconnect'></a>
		</span>
	</div>
	<div id='content'>
		<div id="mosaics">
			<ul>
<?php
}
/*
function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 }
 rrmdir("images/");
 exit();
*/

  //echo "url: ".$reddit_url."<br>";
  
  $content = get_url($reddit_url);
  if($content) {

	$json = json_decode($content, true);
	$after = $json['data']['after'];
	$image_list = array();
	$counter = 0;
	
	if(isset($after)) {
		if(isset($param_m)) 
			$next_url = str_replace($param_m, $after, $next_url);
		else	
			$next_url .= '&after='.$after;

		if(isset($param_g)) $next_url .= '&gw='.$param_g;
	} else {
		$next_url = '';
	}
	
  	//echo "<br>url: ".$next_url."<br>";
	
	// Loop through all returned json data and put links into array
	foreach($json['data']['children'] as $child) { 
	  $url = $child['data']['url'];
	  $title = $child['data']['title'];
	  $permalink = $child['data']['permalink'];
	
	  //echo "\n".$title;
	  
	  $ext = pathinfo($url, PATHINFO_EXTENSION);
	  if($ext == '' && (strstr($url, 'imgur') == TRUE)) {
		$url = str_replace("imgur.com", "i.imgur.com", $url).'.jpg';
	  }
	  $ext = pathinfo($url, PATHINFO_EXTENSION);
	  if($ext == '') $ext = 'asdf';
	  
	  $supported_extensions = "jpg,jpeg,gif,png";
	  
	  if(stristr($supported_extensions, $ext) == TRUE) {
		//echo '<br>'.$url;
		$addImage = false;
		if($param_g == '') {
			$addImage = true;
		} else {

			if($param_g == 'f') {
				if((stristr($title, "[f]")) || (stristr($title, "[w]")) || (stristr($title, "(f)")) || (stristr($title, "(w)")) || (stristr($title, "{f}")) || (stristr($title, "{w}")) || (stristr($title, "(g)")) || (stristr($title, "{g}")) || (stristr($title, "[g]"))) {
					$addImage = true;
					//echo " -- FEMALE";
				}
			} else {
				if((stristr($title, "[m]")) || (stristr($title, "(m)")) || (stristr($title, "{m}"))) {
					$addImage = true;
					//echo " -- MALE";
				}
			}
		}

		if($addImage) {
			$image_list[$counter]['url'] = $url;
			$image_list[$counter]['title'] = $title;
			$image_list[$counter]['subreddit'] = $child['data']['subreddit'];
			$image_list[$counter]['permalink'] = $permalink;
			$counter++;
		}
	  }

	}
	
	$image_url_list = array();

	// Loop through saved image_list and find unsaved thumbs
	foreach($image_list as $image) { 
		//echo '<br>'.$image['url'];
		$url = $image['url'];
		
		// Convert url to md5 and split up to folders
		$fname = url_to_md5($url);
		//echo '<br>'.$fname;
		
		if (!file_exists($fname)) {
			array_push($image_url_list, $image['url']);
		}
		else {
			$counter = 0;
			foreach($image_list as $img) { 
				if($img['url'] == $url) {
					$image_list[$counter]['thumb'] = $fname;				
				}
				$counter++;
			}
		}
	}


	// Loop through retrieved images and save them
	//echo '<pre>';
	//print_r($image_url_list);
	
	// Download new images
	set_time_limit(300);
	multiRequest($image_url_list);
	
	// Create thumbnails for new images
	foreach($image_url_list as $image) { 
		$url = $image;
		$fname = url_to_md5($url);
		$tmpfile = 'tmp/'.md5($url).'.'.pathinfo($url, PATHINFO_EXTENSION);
		//$handle = fopen('http://example.com/foo.jpg', 'rb');
		set_time_limit(300);
		if (file_exists($tmpfile)) {
			//echo '<br> processing: '.$url.' file: '.$fname;
			$handle = fopen($tmpfile, 'rb');
			try {
				$img = new Imagick();
				$img->readImageFile($handle);
		
				if($img->getformat() != 'gif') {
					$img->scaleImage(300, 0);
					$height = $img->getImageHeight();
					if($height > 550) $img->cropImage(300, 550, 0, 0);
					$img->writeImage($fname);
					$img->clear();
					$img->destroy();
		
				} else {
					$fp = fopen($fname, 'w');
					$tmpFile = file_get_contents($tmpfile);
					fwrite($fp, $tmpFile);
					fclose($fp);
				}
				
				// Add thumbs to list
				$counter = 0;
				foreach($image_list as $img) { 
					if($img['url'] == $url) {
						$image_list[$counter]['thumb'] = $fname;				
					}
					$counter++;
				}
			} catch (ImagickException $e) {
			  // something went wrong, handle the problem
			}
			@unlink($tmpfile);
		}
	}
	
	// Print the output
	foreach($image_list as $img) { 
		if($img['thumb'] != '') {
			$url = $img['url'];
			$title = $img['title'];
			$thumb = $img['thumb'];
			$subreddit = $img['subreddit'];
			$permalink = "http://reddit.com/".$img['permalink'];
			
			$img = new imagick($thumb); 
			$height = $img->getImageHeight();
			$img->clear();
			$img->destroy();
			
			$thumb = '/'.$thumb;

			$subreddit_text = '';
			if($bSubreddit) $subreddit_text = '<a class="cat" href="http://redditporn.com/r/'.$subreddit.'" style="color: #3BB9FF">'.$subreddit.'</a><br/>';
			
			echo '<li id="mosaic1170" class="item_image" style="height: ' .($height + 4). 'px;">
				<div class="img">
					<a href="' .$url. '" target="_blank"><img src="' .$thumb. '" alt="' . $title. '" width="300" height="' .$height. '"/></a>
				</div>
				<div class="info">
					' .$subreddit_text. '
					<a href="' .$permalink. '" target="_blank">' . $title. '</a>
				</div>
				</li>';
		}
		$counter++;
	}
  }

  echo '<nav id="page-nav">';
  echo '<a href="'.$next_url.'"></a>';

if(!isset($param_x)) {
?>
			</ul>
		</div>
	</div>
</div>
<div id="newBatch" style="display: none;">
</div>
</body>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript" src="http://redditporn.com/static/js/jquery.masonry.min.js"></script>
<script type="text/javascript" src="http://redditporn.com/static/js/mosaic.js"></script>
<script type="text/javascript" src="http://redditporn.com/static/js/base.js"></script>
</html>
<?php } ?>
