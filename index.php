<?php
   /****
    ** Translates a Facebook Graph JSON to RSS
    ** Use this script by invoking the its url plus the Facebook Page name in the URL ala /index.php?page=PageNameHere
    **/
    //Max title length in characters, will trim on word boundary
    $title_length = 70;
    //OPTIONAL You can specify your timezone here, if different from your server config
    // date_default_timezone_set('Europe/Berlin');
    //Download at https://github.com/facebook/facebook-php-sdk for authentication
    //require_once("facebook-php-sdk-master/src/facebook.php");
    //Setup your app at https://developers.facebook.com/apps
    $config = array();
    $config['appId'] = 'ADD_HERE';
    $config['secret'] = 'ADD_HERE';
    $config['fileUpload'] = false; // optional
    // if using SDK
    //$facebook = new Facebook($config);
    //$access_token = $facebook->getAccessToken();
    // if not using SDK and getting access token from developer account
    $access_token='insert access token here';
    $screen_name  = $_GET['page'];
    $statuses_url = 'https://graph.facebook.com/' . $screen_name . '/posts?fields=full_picture,description,message&access_token=' . $access_token;
    //Error reporting for missing facebook user
    $fetch_json   = @file_get_contents($statuses_url);
	   if($fetch_json === FALSE) { echo "<h2>Fehler beim Holen der Daten</h2> Konnte den User <b>".$screen_name."</b> nicht finden";}
		   else{
    $return       = json_decode($fetch_json);
    $now          = date("D, d M Y H:i:s O");
    $output = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>
        <rss version=\"2.0\" xmlns:media=\"http://search.yahoo.com/mrss/\" xmlns:atom=\"http://www.w3.org/2005/Atom\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\">
            <channel>
                <title>".$screen_name."</title>
                <link>http://www.facebook.com/".$screen_name."</link>
                <description> </description>
                <pubDate>$now</pubDate>
                <lastBuildDate>$now</lastBuildDate>
                <docs>https://github.com/khawkins98/facebook-json-to-rss</docs>
                <generator>facebook-json-to-rss</generator>
                ";
    //for validation
    $output .='<atom:link href="http://ulrichlang.de/TTRSS/Facebook-RSS/index.php?page='.$screen_name.'" rel="self" type="application/rss+xml" />';
    header("Content-Type: application/rss+xml; charset=UTF-8");
    foreach ($return->data as $line){
        //Catch facebook posts with no links and set it to the page url
        if (!isset($line->link)) {
            $line->link = "https://www.facebook.com/".$line->id;
        }
        // Only render if there's a message
        if (isset($line->message)) {
            // Trim the title to be the first line
            $linetitle = explode(PHP_EOL,$line->message)[0];

            //Check for titles longer than the specified length, trims on word end and adds ellipses
            if (strlen($linetitle) > $title_length) { 
                $linetitle = preg_replace('/\s+?(\S+)?$/', '', substr($linetitle, 0, $title_length)) . ' ...';
            } 
            $description="    <description><![CDATA[".htmlspecialchars(strip_tags($line->message),ENT_HTML401,'utf-8')."]]></description>";
	    if (isset($line->description)) {
		$description="    <description><![CDATA[".htmlspecialchars(strip_tags($line->description),ENT_HTML401,'utf-8')."]]></description>";
	    }
	    // Any attachment?
            $enclosure = '';
            if (isset($line->picture)) {
                $enclosure = '<media:content type="image/jpeg"  url="' . htmlspecialchars($line->picture,ENT_HTML401,'utf-8') . '" />';
            }
            $output .= "  <item>".PHP_EOL.
                "    <title><![CDATA[".htmlspecialchars($linetitle,ENT_HTML401,'utf-8')."]]></title>".PHP_EOL.
                "    <link><![CDATA[".utf8_encode(htmlentities(utf8_encode(strip_tags($line->link)),ENT_HTML401,'utf-8'))."]]></link>".PHP_EOL.
		$description.PHP_EOL.
                "    ".$enclosure.PHP_EOL.
                // Ommitted as this should be an e-mail
                //"    <author><![CDATA[".htmlentities(utf8_encode($line->from->name))."]]></author>".PHP_EOL.
                "    <pubDate>".date("D, d M Y H:i:s O",strtotime($line->created_time))."</pubDate>".PHP_EOL.
                "    <guid><![CDATA[".utf8_encode(htmlentities(utf8_encode(strip_tags($line->link)),ENT_HTML401,'utf-8'))."]]></guid>".PHP_EOL.
                "  </item>".PHP_EOL;
        }

    }
    $output .= "</channel>".PHP_EOL."</rss>";
    echo $output;}
?>
