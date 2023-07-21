<?php
include_once 'dbh.inc.php';

$errorreached = 0;

function printFinalResults($conn, $option){
    $sql_preliminary_most_often = "SELECT
  nameofshow,
  COUNT(nameofshow) AS `value_occurrence` 

FROM
  jetixxfcz6986.megamaxtable 

GROUP BY 
  nameofshow

ORDER BY 
  `value_occurrence` ";

    if(strcmp($option, "most") == 0){
        $sql_preliminary_most_often = $sql_preliminary_most_often."DESC LIMIT 10;";
        echo "<br><br><br><table><th colspan='2'>Most often at schedule:</th>";
    }else{
        $sql_preliminary_most_often = $sql_preliminary_most_often."ASC  LIMIT 16;";
        echo "<br><br><br><table><th colspan='2'>Least often at schedule:</th>";
    }
    $result_nabidk = mysqli_query($conn, $sql_preliminary_most_often);
    // For checking if program can use result, it will count number of them.
    // Zero means: "Noo, dont go to that array!"
    $resultCheck_nabidka = mysqli_num_rows($result_nabidk);
                        
    if($resultCheck_nabidka > 0){
         $i = 1;
         while($row = mysqli_fetch_assoc($result_nabidk)){
                $portal = $row['nameofshow'];
                echo '<tr><td>'.$i.'.</td><td>'.$portal.'</td><td><i>('.$row['value_occurrence'].')</i></td></tr>';
                $i++;
         }
         echo "</table>";
    }
}

function printShows($conn){
    $sql = "SELECT nameofshow FROM jetixxfcz6986.megamaxtable GROUP BY `nameofshow`;";
    $result_nabidk = mysqli_query($conn, $sql);

    // For checking if program can use result, it will count number of them.
    // Zero means: "Noo, dont go to that array!"
    $resultCheck = mysqli_num_rows($result_nabidk);                        
    if($resultCheck > 0){
         $i = 1;
         echo "<br><b>Every show in the database: </b>";
         echo "<p>";
         while($row = mysqli_fetch_assoc($result_nabidk)){
                echo "<i>".$row['nameofshow']."</i>";
                $i++;
                if($resultCheck !== $i){
                     echo ", ";
                }
         }
         echo "</p>";
    }
}

// Choose station. It's make for TV Megamax, but it can be change to different one.
if(isset($_GET["tv"])){ 
    $nameOfStation = chooseStation($_GET["tv"]);
}else{
    $nameOfStation = "Megamax";
}

$nameOfPage = "TV Schedule ".$nameOfStation." Analytics";
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
printFinalResults($conn, "most");
printFinalResults($conn, "least");
printShows($conn);
?>

  <br><a href="https://www.toplist.cz"><script language="JavaScript" type="text/javascript" charset="utf-8">
  <!--
document.write('<img src="https://toplist.cz/count.asp?id=1701110&http='+
encodeURIComponent(document.referrer)+'&t='+encodeURIComponent(document.title)+'&l='+encodeURIComponent(document.URL)+
'&wi='+encodeURIComponent(window.screen.width)+'&he='+encodeURIComponent(window.screen.height)+'&cd='+
encodeURIComponent(window.screen.colorDepth)+'" width="88" height="31" border=0 alt="TOPlist" />');
//--></script><noscript><img src="https://toplist.cz/count.asp?id=1701110&njs=1" border="0" alt="TOPlist" width="88" height="31" /></noscript></a>
 </body>
</html>
