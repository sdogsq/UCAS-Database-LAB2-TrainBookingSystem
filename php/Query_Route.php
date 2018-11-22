<!DOCTYPE html>
    
<html>

<title> 车次查询 </title>
    
<head>
<script>
function rpost(path, params, method) {
    var tjson = {jsontxt:JSON.stringify(params)};
    params = tjson;
    
    method = method || "post"; // Set method to post by default if not specified.

    // The rest of this code assumes you are not using a library.
    // It can be made less wordy if you use one.
    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);

    for(var key in params) {
        if(params.hasOwnProperty(key)) {
            var hiddenField = document.createElement("input");
            hiddenField.setAttribute("type", "hidden");
            hiddenField.setAttribute("name", key);
            hiddenField.setAttribute("value", params[key]);

            form.appendChild(hiddenField);
        }
    }
    document.body.appendChild(form);
    form.submit();
}

</script>
    
</head>
<body>
<meta charset="utf-8"/>
<div class="main">

<?php

$CityL = $_GET["CityL"];
$CityR = $_GET["CityR"];

$fdatetime = new Datetime($_GET["datetime"]);

$Date = $fdatetime->format('Y-m-d');
$Time = $fdatetime->format('H:i:s'); 

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

$qroute = <<<EOF
set intervalstyle to iso_8601;
SELECT
    RD.R_TrainID,
    SD.S_Name as NameL,RD.R_TimeDep,
    SA.S_Name as NameR, RA.R_TimeArr,
    RD.r_priceyz as yzl,RD.r_pricerz as rzl ,RD.r_priceyws as ywsl,RD.r_priceywz as ywzl ,RD.r_priceywx as ywxl,RD.r_pricerws as rwxl ,RD.r_pricerwx as rwxl,
    RA.r_priceyz as yzr,RA.r_pricerz as rzr ,RA.r_priceyws as ywsr ,RA.r_priceywz as ywzr ,RA.r_priceywx as ywxr,RA.r_pricerws as rwsr ,RA.r_pricerwx as rwxr,
    to_char( ( date '$Date' - extract(day from justify_hours(RD.R_TimeDep) )*(interval '1 day') ),'YYYY-MM-DD') as fdate,
    RD.R_Num as numl ,RA.R_Num as numr
FROM
    route as RD, route as RA, station as SD, station as SA
WHERE
    SD.S_City = '$CityL' and SA.S_City = '$CityR' and
    SD.S_ID = RD.R_Station and SA.S_ID = RA.R_Station and
    RD.R_TrainID = RA.R_TrainID and
    RD.R_Num < RA.R_Num and
    RD.R_TimeDep - extract(day from justify_hours(RD.R_TimeDep) )*(interval '1 day') > interval '$Time'


ORDER BY
    (RD.R_TimeDep - extract(day from  justify_hours(RD.R_TimeDep) )*(interval '1 day')) asc
LIMIT 10;
EOF;

$rer = pg_query($qroute);

#var_dump(pg_fetch_row($rer));
echo '<b>'.$Date.' '.$Time.' </b>开始<b> '.$CityL.' </b>到<b> '.$CityR.' </b>车次情况如下 (票价/余票)：<br>';

echo "<table border=\"4\">";
echo "<tr>";
echo "<td>车次</td>" ;
echo "<td>始发站</td>" ;
echo "<td>出发时间</td>" ;
echo "<td>到达站</td>" ;
echo "<td>到达时间</td>";
echo "<td>硬座</td>" ;
echo "<td>软座</td>" ; 
echo "<td>硬卧上铺</td>";
echo "<td>硬卧中铺</td>" ;
echo "<td>硬卧下铺</td>" ;
echo "<td>软卧上铺</td>" ;
echo "<td>软卧下铺</td>";
echo "</tr>";


$pseat = array(  '5'=>'hardseat',
                 '6'=>'softseat',
                 '7'=>'hardcoach_h',
                '8'=> 'hardcoach_m',
                '9'=>'hardcoach_l' ,
                '10'=>'softcoach_h' ,
                '11'=>'softcoach_l' 
              );

function queryticket($TID, $Date, $L, $R){
    
 //查询某日车次 站次L->站次R 余票信息
// $TID = $_GET['TID'];
// $Date = $_GET['Date'];
// $L = $_GET['L'];
// $R = $_GET['R'];

    global $dbconn;

    $qticket = <<<EOF
            SELECT
            T_TypeTicket, count(*)
        FROM
            Ticket
        WHERE
            T_TrainID = '$TID' and
            T_Date = '$Date' and
            T_RNumArr > '$L' and
            T_RNumDep < '$R' 
        GROUP BY
            T_TypeTicket;
EOF;

    $rticket = pg_query($qticket);

    $seatleft = array('hardseat'=>5,
                  'softseat'=>5,
                  'hardcoach_h'=>5,
                  'hardcoach_m'=>5,
                  'hardcoach_l'=>5,
                  'softcoach_h'=>5,
                  'softcoach_l'=>5);

    while ($row = pg_fetch_row($rticket)){
        $seatleft[$row[0]] -= $row[1];
    }
    
    pg_free_result($rticket);
    return $seatleft;//json_encode($seatleft);

}

while ($row = pg_fetch_row($rer)){

    #$json = file_get_contents("http://192.168.113.128/queryticket.php?TID=$row[0]&Date=$row[19]&L=$row[20]&R=$row[21]"); //TEMP !!!!!!!!
#    echo $json;
    $ticket = queryticket($row[0],$row[19],$row[20],$row[21]);//json_decode($json,true);
    //var_dump($ticket); 
    echo '<tr>';
    for ($i = 0;$i< 12; $i++){
        if ($i ==0 or $i == 1 or $i == 3){
            echo '<td>'.$row[$i].'</td>';
        }
        elseif ($i == 2 or $i == 4){
            $date = new DateTime($row[19]);    
            $interval = new DateInterval($row[$i]);
            $date->add($interval);
            echo '<td>'.$date->format('Y-m-d H:i').'</td>';
        }
        else{
            if ($row[$i+7] == -1 or ($row[$i]== -1 and $row[20] != 1) ){
                echo '<td>无</td>';
            }else{
                $Price = $row[$i+7]- ($row[$i]==-1?0:$row[$i] );
                echo '<td>'.$Price.'/'.$ticket[$pseat[$i]];
                
                if ($ticket[$pseat[$i]] > 0 ) {
                    echo " <button  onclick=\"rpost('/bookticket.php',{check:'1',num:'1','0':{'TID':'$row[0]','Date':'$row[19]','L':$row[20],'R':$row[21],'SeatType':'$pseat[$i]'} })\">预订</button>";
                }else{
                    echo " <button disabled='true'>预订</button>";
                    
                }
                echo"</td>";
            }
        }
    }
    echo '</tr>';
}
echo '</table>';

        
pg_close($dbconn);
?>

</div>
</body>
</html>
