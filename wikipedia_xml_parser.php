//    Copyright 2014 Francesco Bailo
    
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.

//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.

//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.

<?php

// Set time zone
date_default_timezone_set('Australia/Sydney');

$db_store = "talks.sqlite";

$z = new XMLReader;
$z->open('/Users/francesco/Downloads/wiki_dump/itwiki-latest-pages-meta-current.xml');

$doc = new DOMDocument;

// move to the first <product /> node
while ($z->read() && $z->name !== 'page');

try
{
  $dbhandle = new PDO("sqlite:$db_store");
  $dbhandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  // now that we're at the right depth, hop to the next <product/> until the end of the tree
  while ($z->name === 'page')
    {
      // either one should work
      //$node = new SimpleXMLElement($z->readOuterXML());
      $node = simplexml_import_dom($doc->importNode($z->expand(), true));

      // Check if page is a talk
      if (substr($node->title, 0, 12) == "Discussione:") {

	// Parse page
	$array_page = array("pageId" => $node->id,
			    "title" => $node->title,
			    "text" => $node->revision->text);

	// Send page to database
	$stmt = $dbhandle->prepare("INSERT OR REPLACE INTO page (pageId, title, text) VALUES (:pageId, :title, :text)");
	$stmt->bindValue(':pageId', $array_page['pageId'], PDO::PARAM_INT);
	$stmt->bindValue(':title', $array_page['title'], PDO::PARAM_STR);
	$stmt->bindValue(':text', $array_page['text'], PDO::PARAM_STR);
	$stmt->execute();
      

	// Parse talks
	$raw_text = $node->revision->text;
	$results = parseTalks($node->id, $raw_text);
	
	// Send results to database
	foreach ($results as $talk) {

	  $stmt = $dbhandle->prepare("INSERT OR REPLACE INTO talk (pageId, text, user, date) VALUES (:pageId, :text, :user, :date)");
      
	  $stmt->bindValue(':pageId', $talk['pageId'], PDO::PARAM_INT);
	  $stmt->bindValue(':text', $talk['text'], PDO::PARAM_STR);
	  $stmt->bindValue(':user', $talk['user'], PDO::PARAM_STR);
	  $stmt->bindValue(':date', $talk['date'], PDO::PARAM_STR);

	  $stmt->execute();

	} 

      }
      
      // go to next <product />
      $z->next('page');
    }

  $dbhandle = NULL;

} 
  
catch(PDOException $e)
{
  print 'Exception : '.$e->getMessage();
}



// Functions

function convertMonth($date) {
  $months = array(
		  'gen' => 'jan',
		  'feb' => 'feb',
		  'mar' => 'mar',
		  'apr' => 'apr',
		  'mag' => 'may',
		  'giu' => 'jun',
		  'lug' => 'jul',
		  'ago' => 'aug',
		  'set' => 'sep',
		  'ott' => 'oct',
		  'nov' => 'nov',
		  'dic' => 'dec'
		  );

  return str_replace(array_keys($months), array_values($months), strtolower($date));
}

function parseTalks($pageId, $text) {

  // Define regex patterns
  $user_pattern = "/\[\[Utente:(.*?)\]\]/";
  $date_pattern1 = "/(\d{1,2}):(\d{2})[,]? (\d{1,2})[,]? [a-zA-Z]{3}[,]? (\d{2,4})[,]? \([a-zA-Z]{3,}\)/";
  $date_pattern2 = "/(\d{1,2}):(\d{2})[,]? [a-zA-Z]{3}[,]? (\d{1,2})[,]? (\d{2,4})[,]? \([a-zA-Z]{3,}\)/";
  $title_pattern = "/==(.*?)==/";

  // Create array for results
  $results = array();

  // Split text in paragraphs
  $array = preg_split("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", $text);

  // remove titles
  $num_paragraph = 0;
  foreach ($array as $paragraph) {
    if (preg_match($title_pattern, $paragraph)) {
      unset($array[$num_paragraph]);
    } 
    $num_paragraph++; 
  }
  $array = array_merge($array);

  // check if string contains only username, if it does append username to previous item and delete. Check if string contains username in first row, if it does append username to previous item and delete first row.
  $num_paragraph = 0;
  foreach ($array as $paragraph) {
    $paragraph = trim($paragraph);
    $substr = substr($paragraph, 0, 9);
    if ($substr == "--[[Utent" or $substr == "[[Utente:") {
      $num_lines = substr_count($paragraph, "\n");
      if ($num_lines < 1) {
	$array[$num_paragraph-1] = $array[$num_paragraph-1]." ".$array[$num_paragraph];
	unset($array[$num_paragraph]);
      } else {
	$pos = strpos($paragraph, "\n");
	$usrstr = substr($paragraph, 0, $pos);
	$txtstr = substr($paragraph, $pos+1);
	$array[$num_paragraph-1] = $array[$num_paragraph-1]." ".$usrstr;
	$array[$num_paragraph] = $txtstr;
      }
    }
    $num_paragraph++; 
  }
  $array = array_merge($array);

  // Create array with text, username and date
  foreach ($array as $paragraph) {
    //Subtring part containing username and date, store text in variable
    if (preg_match("^\[\[Utente:^", $paragraph)) {
      $pos = strpos($paragraph, "[[Utente:");
      $text = substr($paragraph, 0, $pos);
      $tmpstr = substr($paragraph, $pos);
      preg_match($user_pattern, $tmpstr, $match);
      // Substring username
      $pos =  strpos($match[1], "|");
      if ($pos!=FALSE) {
	$user = substr($match[1], 0, $pos);
      } else {
	if (ctype_alnum($match[1])==TRUE) {
	  $user = $match[1];
		   } else {
	    $pos =  strpos($match[1], "/");
	    $user = substr($match[1], 0, $pos);
	  }
	}
      // Substring date
      preg_match($date_pattern1, $tmpstr, $match);
      if (count($match)!=0) {
	$date = $match[0];
      } else {
	preg_match($date_pattern2, $tmpstr, $match);
	if (count($match)!=0) {
	  $date = $match[0];
	} else {
	  $date = "NA";
	}
      }
    } else {
      $text = $paragraph;
      $user = "NA";
      $date = "NA";
    }

    // Convert date string to DATETIME
    if ($date!="NA") {
      $date = convertMonth($date);
      $date = strtotime($date);
      $date = date("Y-m-d H:i:s", $date);
    }

    // echo "TEXT: ".$text."\n";
    // echo "USER: ".$user."\n";
    // echo "DATE: ".$date."\n";
    // echo "\n";

    $array_talk = array("pageId" => $pageId, 
			"text" => $text,
			"user" => $user,
			"date" => $date);
    
    array_push($results, $array_talk);

  }

  return($results);
}

function parseArray($string, $beg_tag, $close_tag) {
    preg_match_all("($beg_tag(.*)$close_tag)siU", $string, $matching_data);
    return $matching_data[0];
}


?>