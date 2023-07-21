<?php
include_once 'dbh.inc.php';

$errorreached = 0;

function loadPage($pagecode){
    $url = "https://www.fdb.cz/tv/21-discovery-channel-zdf-dsf-cs-mini-megamax.html?datum=".$pagecode."&cas_od=0";
    $page = file_get_contents($url);

    if(empty($page)){
         echo "<b>Error:</b> Page <a href='".$url."'>".$url."</a> was not loaded. Error was reached. Try it again later. <br>";
         global $errorreached;
         $errorreached = $errorreached + 1;
         return "";
    }
    
    return $page;
}

function takeTVSchedulePart($page){
    $begin = '<table border="0" class="tbl_program_block" cellspacing="0" cellpadding="0">';
    $listWithPageWithoutUP = explode($begin, $page);
    $listWithPageWithoutDOWN = explode('</table>', $listWithPageWithoutUP[1]);
    return $listWithPageWithoutDOWN[0];
}

function takeBoxes($unformatedTVSchedule){
    $listAndResult = [];
    $rest = $unformatedTVSchedule;
    $end_uTVs = "<!-- stanice konec -->";
    $begin_uTVs = '<div class="blok_stanice">';
    $i = 1;
    while(strstr($rest, $end_uTVs) != false){
         $listWithoutUP = explode($begin_uTVs, $rest, 2);
         $listWithoutDOWN = explode($end_uTVs, $listWithoutUP[1], 2);
         array_push($listAndResult, $listWithoutDOWN[0]);
         if(empty($listWithoutDOWN[1])){
            $rest = "";
         }else{
            $rest = $listWithoutDOWN[1];
         }
         $i = $i + 1;
    }
    return $listAndResult;
}

// Find order of television station at schedule
function findOrder($TVStationName, $takenBoxes){
    $result = -1;
    // First boxes have name of station.
    // Boxes with names contain text "title_stanice"

    foreach($takenBoxes as $key => $box){
         // if box has name of some station
         if(strstr($box, "title_stanice") != false){
               // that try to find $TVStationName
               if(strstr($box, $TVStationName) != false){
                      return $key + 1;
               }
         }else{
               // else give error
               echo "Error was reached: TV Station ".$TVStationName." was not find at schedule. Try visit <a href='https://www.fdb.cz/tv/'>FDb.cz</a> for correct name of station.";
               global $errorreached;
               $errorreached = $errorreached + 1;
               return $result;
         }
    }
    return $result;
}

function countTVStations($takenBoxes){
    $result = -1;
    // We count boxes with names contain text "title_stanice".
    // Every station has one box with its name.

    foreach($takenBoxes as $key => $box){
         // if box has not name of some station
         if(strstr($box, "title_stanice") == false){
               return $key;
         }
    }
    return $result;
}

function findBoxesOfThisStation($boxes, $thisStation){
    $numberOfStations = countTVStations($boxes);
    $orderOfStation   = findOrder($thisStation, $boxes);
    $selectedBoxes    = [];

    // We take bunch of boxes. First $numberOfStations is just name of station. 
    // We will skip this. Program is trying to select boxes 

    $findMe_key = $orderOfStation + $numberOfStations - 1; // It's indexed from zero.

    foreach($boxes as $key => $onebox){
         if($key == $findMe_key){
                array_push($selectedBoxes, $onebox);
                $findMe_key = $findMe_key + $numberOfStations;
         }
    }
    return $selectedBoxes;
}

function cutBoxesToShows($boxes_of_station){
    $listAndResult = [];
    $end_uTVs      = '<div class="reset"></div>';
    $begin_uTVs    = "<a name";
    foreach($boxes_of_station as $box){
         $rest     = $box;
         while(strstr($rest, $end_uTVs) != false){
              $listWithoutUP = explode($begin_uTVs, $rest, 2);
              $listWithoutDOWN = explode($end_uTVs, $listWithoutUP[1], 2);
              array_push($listAndResult, $listWithoutDOWN[0]);
              if(empty($listWithoutDOWN[1])){
                 $rest = "";
              }else{
                 $rest = $listWithoutDOWN[1];
              }
         }
    }
    return $listAndResult;
}

function isTime2Smaller($time1, $time2){
    // Function expect fdb format of time /d/d:/d/d (06:10 atc.)
    if(($time1[0]*10 +  $time1[1]) > ($time2[0]*10 +  $time2[1])){
          return true;
    };
    
    if(($time1[0]*10 +  $time1[1]) == ($time2[0]*10 +  $time2[1])){
        if(($time1[3]*10 +  $time1[4]) > ($time2[3]*10 +  $time2[4])){
             return true;
        };
        return false;
    };
    return false;
}

function gapTime($time1, $time2){
     $datetime_1 = '2000-01-01 '.$time1.':00'; 
     $datetime_2 = '2000-01-01 '.$time2.':00';

     // If first is 23:45 and second is 00:10, 
     // we expect result 25 not more than 23 hours.
     if(isTime2Smaller($time1, $time2)){
           $datetime_2 = '2000-01-02 '.$time2.':00';
     }
 
     $start_datetime = new DateTime($datetime_1); 
     $diff = $start_datetime->diff(new DateTime($datetime_2)); 
     return $diff->i + ($diff->h*60);
}

function correctName($name){
    $name = str_replace("'","’", $name); // Char ' can do problems at SQL

    // // array("Bad name of show", "Correct name of show")
    $arrayOfNames = array(
          array("Mr Young", "Mr. Young"),
          array("Max Oţel", "Max Steel"),
          array("Megamax Kód Lyoko", "Kód Lyoko"),
          array("Jak je kanci!", "Jak je, kanci?"),
          array("Ready For This", "Jste připraveni"),
          array("Radio F. Roscoe", "Pirátské vysílání"),
          array("Zorro: Generation Z.", "Zorro: Generace Z"),
          array("Kód Lyoko – Evoluce", "Kód Lyoko - Evoluce"),
          array("Zdravíme gympl West Hill!", "Jak je, kanci?"),
          array("Matt Hatter Chronicles", "Kroniky Matta Hattera"),
          array("Transformers Cybertron", "Transformers: Cybertron"),
          array("Slugterra Specials",             "Filmy Slugterra"),
          array("Slugterra: Démon z neznáma",     "Filmy Slugterra"),
          array("Slugterra - Slug Fu: Zúčtování", "Filmy Slugterra"),
          array("Slugterra: Návrat elementů",     "Filmy Slugterra"),
          array("Střední škola Degrassi XI",  "Střední škola Degrassi"),
          array("Střední škola Degrassi XII", "Střední škola Degrassi")
    );

    foreach($arrayOfNames as $a){
         if(strcmp($a[0], $name) == 0){
                return $a[1];
         }
    }

    if(strpos($name, ",") and strcmp($name, "Jak je, kanci?")){
        echo '<FONT COLOR="#ff0000">Warning: Suspicion on faking multiple shows for one in FDb data. For name "'.$name.'".</FONT><br>';
    }

    return $name;
}

// Some data from FDb seems be one show but truly it is more show as one data
// This function is for spliting its.
// For example: "Angry Birds Toons, Simon's Cat, Invaze P..."
// is correctly "Angry Birds Toons", "Simon’s Cat", and "Invaze Planktonu"

function fixFakingMultipleShowsAsOne($name, $time, $code, $duration, $conn){
    $result = false;

    $arrayOfMultiples = array(
          array("Angry Birds Toons, Simon's Cat, Invaze P...",
                 array("Angry Birds Toons", "Simon’s Cat", "Invaze Planktonu")
               ),
          array("Angry Birds Toons, Simon's Cat, Erky Per...",
                 array("Angry Birds Toons", "Simon’s Cat", "Erky Perky")
               ),
          array("Angry Birds Toons, Kasper",
                 array("Angry Birds Toons", "Kasper")
               ),
          array("Bernard, Invaze Planktonu",
                 array("Bernard", "Invaze Planktonu")
               ),
          array("Bernard / Simon’s Cat",
                 array("Bernard", "Simon’s Cat")
               )
    );

    foreach($arrayOfMultiples as $multiple){
        $nameMulti = $multiple[0];
        if(strcmp($nameMulti, $name) == 0){
              $duration = intval($duration / count($multiple[1]));
              foreach($multiple[1] as $nameOfShort){
                   putToDatabase($conn, $time, $nameOfShort, $code, $duration);
              }
              $result = true;
        }
    }
    return $result;
}

function putToDatabase($conn, $time, $name, $code, $duration){
    // If you dont need fix multiple show faking as one, programm just continue as normal
    if(fixFakingMultipleShowsAsOne($name, $time, $code, $duration, $conn) == false){
          $name = correctName($name); // Change wrong titles of name to correct ones.

          $sql = "INSERT INTO jetixxfcz6986.megamaxtable (time, nameofshow, pagecode, duration) VALUES ('$time', '$name', '$code', $duration);";

          $errorstatus = mysqli_query($conn, $sql);

          if($errorstatus != 1){
               echo "<b>Error (".$errorstatus.")</b>: Page couldn't upload data to database. Unupload data was time ".$time.", name ".$name.", code ".$code.", duration ".$duration.".<br>";
               global $errorreached;
               $errorreached = $errorreached + 1;
          }
     }
}

function takeDataFromShows($shows, $conn, $pagecode){
    // Take from HTML text of shows just data.
    $result;
    $previous_name = "x";
    $previous_time = "x";

    foreach($shows as $show){
         // I will delete "<a" for easier regex.
         $show = str_replace("<a", "a", $show); 

         // Regex (maded with help of https://regex101.com/) will find name.
         preg_match('/<div  class="text_info">(  a (.*)>|)(?P<name>.*)( )*<((\/a><p class=)|(br\/>|span|))/iJmU', $show, $result_name); 
         preg_match('/time_info">(?P<time>(\d)\d:\d\d)</iJmU', $show, $result_time); 

         $previous_duration = 0;
         if((strcmp($previous_time, "x") !== 0)){
              $previous_duration = gapTime($previous_time, $result_time['time']); 
              putToDatabase($conn, $previous_time, $previous_name, $pagecode, $previous_duration);             
              // store data to database
         }
         $previous_name     = $result_name['name'];
         $previous_time     = $result_time['time'];

    }
    if((strcmp($previous_time, "x") !== 0)){
         putToDatabase($conn, $previous_time, $previous_name, $pagecode, 0);
    }
}

function loadingDataFromOnePage($pagecode, $nameOfStation, $conn){
    $page = loadPage($pagecode);
    $unformatedTVSchedule = takeTVSchedulePart($page);
    $all_boxes = takeBoxes($unformatedTVSchedule);
    $boxes_of_station = findBoxesOfThisStation($all_boxes, $nameOfStation);
    $shows = cutBoxesToShows($boxes_of_station);
    takeDataFromShows($shows, $conn, $pagecode);
}

function choosePagecode($userpagecode, $defaultpagecode){
    if(empty($userpagecode) or ($userpagecode < 1000000000)){
         return $defaultpagecode;
    }
    return $userpagecode;
}

function chooseStation($userstation){
    if(empty($userpagecode)){
         return "Megamax"; // return default tv station
    }
    return $userstation;
}

function printStatus($pagecode, $lastPagecode, $defaultpagecode){
    echo ($pagecode - $defaultpagecode)."/".($lastPagecode - $defaultpagecode);
}

function printPreliminaryResults($conn){
    $sql_preliminary_most_often = "SELECT
  nameofshow,
  COUNT(nameofshow) AS `value_occurrence` 

FROM
  jetixxfcz6986.megamaxtable 

GROUP BY 
  nameofshow

ORDER BY 
  `value_occurrence` DESC

LIMIT 10;";
    $result_nabidk = mysqli_query($conn, $sql_preliminary_most_often);
    // For checking if program can use result, it will count number of them.
    // Zero means: "Noo, dont go to that array!"
    $resultCheck_nabidka = mysqli_num_rows($result_nabidk);
                        
    if($resultCheck_nabidka > 0){
         $i = 1;
         echo "<br><br><br><table><th colspan='2'>Most often at schedule so far</th>";
         while($row = mysqli_fetch_assoc($result_nabidk)){
                $portal = $row['nameofshow'];
                echo '<tr><td>'.$i.'.</td><td>'.$portal.'</td></tr>';
                $i++;
         }
         echo "</table>";
    }
}

$defaultpagecode = 1363561200;  // Code for first page with Megamax schedule
$lastPagecode    = 1577833200;  // Code for last  page with Megamax schedule
$DayDiffForPage  = 86400; // Difference of code between two pages, where time difference is day.

$usersNext = 0;                 // Used for avoiding of Notice/s
if(isset($_GET["next"])){       // for empty user things
    $usersNext = $_GET["next"];
}
$pagecode = choosePagecode($usersNext, $defaultpagecode);

// Choose station. It's make for TV Megamax, but it can be change to different one.
if(isset($_GET["tv"])){ 
    $nameOfStation = chooseStation($_GET["tv"]);
}else{
    $nameOfStation = "Megamax";
}


// HTML Part

$nameOfPage = "Uploading TV Schedule ".$nameOfStation 
?>
<html>
 <head>
  <style> * {font-family: Arial, Helvetica, sans-serif}</style>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <title><?= $nameOfPage; ?></title>
 </head>
 <body>
  <h2><?= $nameOfPage; ?></h2>
<?php

$debugPrintPagecode = $pagecode." - "; // Archive for debugging data

for($i = 0; $i <= 20 and $pagecode < $lastPagecode; $i++){
    loadingDataFromOnePage($pagecode, $nameOfStation, $conn);
    $pagecode = $pagecode + $DayDiffForPage;
}  

$debugPrintPagecode = $debugPrintPagecode.$pagecode;
    echo "Uploaded <b>".(($pagecode - $defaultpagecode)/($lastPagecode - $defaultpagecode)*100)."%</b>. ";

if(($pagecode < $lastPagecode) and ($errorreached == 0)){ // Final condition

    echo "About ".(((($lastPagecode - $defaultpagecode)-($pagecode - $defaultpagecode))/$DayDiffForPage)*(0.10/20))." minutes left.<br>During the last step, the program checked pages ".$debugPrintPagecode.". Last page has a number ".$lastPagecode. ".";

    printPreliminaryResults($conn);

    $url = "http://jetix.xf.cz/MegamaxLoading.php?tv=".$nameOfStation."&next=".$pagecode;
    echo '<script> window.location.href="'.$url.'"; </script>';
}else{
    if($errorreached != 0){
         echo " Dear User, unfortunately, ".$errorreached." error/s was reached. Uploading of data was cancelled. You can contact programmer on vithrbacek(at)email.cz.";

    }else{
         echo " Uploading is finished. The End.";
         printPreliminaryResults($conn); // Basically not prelimianary for now :)
    }
}
?>
 </body>
</html>