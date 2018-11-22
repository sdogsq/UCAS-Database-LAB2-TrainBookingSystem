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

$TID = $_GET["T_TID"];
$Date = new Datetime($_GET["date_search"]);


$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

 
$qtrain = <<<EOF
	SELECT 
        r_trainid,r_num,s_name,r_timearr,r_timedep,r_priceyz,r_pricerz,r_priceyws,r_priceywz,r_priceywx,r_pricerws,r_pricerwx
	FROM
        route,station
	WHERE
        r_trainid = '$TID' and
        r_station = s_id;
EOF;

$result = pg_query($qtrain);
$tnum = 0;
if (($tnum = pg_num_rows($result)) == 0){
    $Msg='车次'.$TID.'不存在！';
    echo "<script type='text/javascript'> alert('$Msg'); history.go(-1); </script>";
    exit();
}
 
$fdate = $Date->format('Y-n-d');

$qticket = <<<EOF
            SELECT
                T_Typeticket,T_RNumDep,Count(*) as Number
            FROM
                Ticket
            WHERE
                T_TrainID = '$TID' and
                T_Date = '$fdate'
            GROUP BY
                T_RNumDep, T_Typeticket
            ORDER BY
                T_Typeticket,T_RNumDep;
EOF;


$rticket = pg_query($qticket);

$seatp = array( 'hardseat'=> '5',
                'softseat'=> '6',
                'hardcoach_h'=> '7',
                'hardcoach_m'=> '8',
                'hardcoach_l'=> '9',
                'softcoach_h'=> '10',
                'softcoach_l'=> '11'
              );
$pseat = array(  '5'=>'hardseat',
                 '6'=>'softseat',
                 '7'=>'hardcoach_h',
                '8'=> 'hardcoach_m',
                '9'=>'hardcoach_l' ,
                '10'=>'softcoach_h' ,
                '11'=>'softcoach_l' 
              );
$nticket = array();


while ($row = pg_fetch_array($rticket)){
    $nticket[$row['t_rnumdep']][$seatp[$row['t_typeticket']]] = (int)$row['number'];
}
#var_dump($nticket);
for ($j = 5; $j < 12; $j++){
    for ($i = 1; $i <= $tnum; $i++){  
        $nticket[$i][$j] += $nticket[$i-1][$j];
    }
}

#var_dump($nticket);
// $json = file_get_contents('url_here');
// $obj = json_decode($json);
// echo $obj->access_token;



echo '<b>'.$Date->format('Y年n月d日 ').$TID.'</b>车次情况如下 (票价/余票)：<br>';

echo "<table border=\"4\">";
echo "<tr>";
echo "<td>车次</td>" ;
echo "<td>站次</td>" ;
echo "<td>站名</td>" ;
echo "<td>到达时间</td>" ;
echo "<td>出发时间</td>";
echo "<td>硬座</td>" ;
echo "<td>软座</td>" ; 
echo "<td>硬卧上铺</td>";
echo "<td>硬卧中铺</td>" ;
echo "<td>硬卧下铺</td>" ;
echo "<td>软卧上铺</td>" ;
echo "<td>软卧下铺</td>";
echo "</tr>";

$crow=0;
while ($row = pg_fetch_row($result)){
    $crow +=1;
	$num = count($row);
	echo "<tr>";
	for ( $i = 0; $i < $num ; $i = $i + 1 ){
        if ($i==3 or $i==4){
            $format = '!H:i:s';
            $date = DateTime::createFromFormat($format, $row[$i]);
           # echo "Format: $format; " . $date->format('Y-m-d H:i:s') . "\n";
            
           # $interval = new DateInterval($row[$i]);
            echo "<td>" .str_replace('第1','当',$date->format('第j日H:i')). "</td>";
        }else{
            if ($i<5){
                echo "<td>" . ("$row[$i]"==-1?'无':$row[$i]) . "</td>";
            }else{
                if ($row[$i]==-1){
                    echo "<td>无</td>";
                }else{
                    echo "<td>" . $row[$i].' / ';
                    
                    $avlt = 5-$nticket[(string)$crow][$i];
                    echo $avlt;
                    
                    if ($avlt > 0 ) {
                        echo " <button  onclick=\"rpost('/bookticket.php',{check:'1',num:'1','0':{'TID':'$TID','Date':'$fdate','L':1,'R':$crow,'SeatType':'$pseat[$i]'} })\">预订</button>";
                    }else{
                        echo " <button disabled='true'>预订</button>";
                    }
                    
                    echo "</td>";
               }
            }
        }
	}
	echo "</tr>";
}

echo "</table>";
pg_free_result($rticket);
pg_free_result($result);
pg_close($dbconn);
?>

    </div>

</body>
</html>
