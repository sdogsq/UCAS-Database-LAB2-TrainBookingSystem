<!DOCTYPE html>
<html>
<meta charset="utf-8"/>
<head>
<style>
table{
    table-layout:fixed;
    border-collapse:collapse;
}
td{
    word-wrap: break-word;
}
</style>
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
<title>订单确认</title>
</head>
<body>


<?php
header("content-type:text/html;charset=utf-8");

session_start();
if(!isset($_SESSION['U_ID'])){
    exit('Please Login first');
}


$jsontxt = $_POST['jsontxt'];

// $data = file_get_contents('php://input');
$data = json_decode($jsontxt,true);
if ($data == NULL){
    die('Illegal Request');
}

if ($data['check'] == 1) $databak = $data; 

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());



$seatpr = array( 'hardseat'=> 'r_priceyz',
                'softseat'=> 'r_pricerz',
                'hardcoach_h'=> 'r_priceyws',
                'hardcoach_m'=> 'r_priceywz',
                'hardcoach_l'=> 'r_priceywx',
                'softcoach_h'=> 'r_pricerws',
                'softcoach_l'=> 'r_pricerwx'
              );
$seatch = array( 'hardseat'=> '硬座',
                'softseat'=> '软座',
                'hardcoach_h'=> '硬卧上',
                'hardcoach_m'=> '硬卧中',
                'hardcoach_l'=> '硬卧下',
                'softcoach_h'=> '软卧上',
                'softcoach_l'=> '软卧下'
              );
$TotalPrice = $data['num'] * 5; //订票费

for ($i=0;$i<$data['num'];$i++){
    $TID = $data[$i]['TID'];
    $T_RNumDep = $data[$i]['L'];
    $T_RNumArr = $data[$i]['R'];
    $T_Date = $data[$i]['Date'];

    $qtr = <<<EOF
        set intervalstyle to iso_8601;
        SELECT
            R_Num, R_Station, r_priceyz, r_pricerz,r_priceyws,r_priceywz,r_priceywx,r_pricerws,r_pricerwx,S_Name,R_TimeDep,R_TimeArr
        FROM
            route, station
        WHERE
            S_ID = R_Station and
            R_TrainID = '$TID' and
            (R_Num = '$T_RNumDep' or R_Num = '$T_RNumArr')
        ORDER BY
            R_Num;
EOF;

    $qret = pg_query($qtr);
    if (pg_num_rows($qret) != 2) die('Error');
    
    $dbac = pg_fetch_all($qret);#var_dump($dbac);
    
    $data[$i]['SIDL'] = $dbac[0]['r_station'];
    $data[$i]['SIDR'] = $dbac[1]['r_station'];
    $data[$i]['Price'] = $dbac[1][$seatpr[$data[$i]['SeatType']]]-($dbac[0][$seatpr[$data[$i]['SeatType']]]==-1?0:$dbac[0][$seatpr[$data[$i]['SeatType']]]);
    $data[$i]['SNameL'] = $dbac[0]['s_name'];
    $data[$i]['SNameR'] = $dbac[1]['s_name'];
    $data[$i]['STimeL'] = $dbac[0]['r_timedep'];
    $data[$i]['STimeR'] = $dbac[1]['r_timearr'];
    $TotalPrice += $data[$i]['Price'];
    echo $data[$i]['Price'];

}

if ($data['check'] == 1){
    $UName =$_SESSION['U_UserName'];
    echo "<p><b>$UName</b> 您好，请核对您的订单信息：<br></p>";
    echo "<p>订单总票价（含手续费）：$TotalPrice 元</p><br>";

    //echo '<table border="0"><tr><td><div>';
    echo '<table border="2" align="left">';
    echo '<tr><td>车次</td><td align="center">出发时间</td><td>出发站</td><td align="center">到达时间</td><td>到达站</td><td>座位类型</td><td>票价</td></tr>';
    for ($i = 0;$i < $data['num'];$i++){
        echo '<tr>';
        echo '<td>'.$data[$i]['TID'].'</td>';
        $date = new DateTime($data[$i]['Date']);
        #echo $data[$i]['STimeL'];
        $interval = new DateInterval($data[$i]['STimeL']);
        #echo $date->format('Y-m-d H:i:s');
        $date->add($interval);
        echo '<td>'.$date->format('Y-m-d H:i').'</td>';
        echo '<td align="center">'.$data[$i]['SNameL'].'</td>';
        
        $date = new DateTime($data[$i]['Date']);
        #echo $data[$i]['STimeR'];
        $interval = new DateInterval($data[$i]['STimeR']);
        $date->add($interval);
        echo '<td>'.$date->format('Y-m-d H:i').'</td>';
        echo '<td align="center">'.$data[$i]['SNameR'].'</td>';
        
        echo '<td align="center">'.$seatch[$data[$i]['SeatType']].'</td>';
        echo '<td>'.$data[$i]['Price'].'</td>';
        
        echo '</tr>';
    }

    $databak['check'] = 0;
    $databak = str_replace('"',"'",json_encode($databak));
    //echo $databak;
    echo '<table border="0" style="border-collapse: collapse;"><tr>';
    echo "<td><button  onclick=\"rpost('/bookticket.php',$databak)\">确定</button></td></tr>";
    echo "<tr><td><button  onclick=\"{location.href='Welcome.php'}\">取消</button></td></tr>";
    echo "</table>";
//     用户点击确认，就生成订单
//  记录到用户的历史订单中，修改车次对应的座位信息
//  订单包含：订单号、上述车次、出发、到达、座位类型、票价、日期
// 和时间
//  包含一个链接返回首页
// 用户点击取消，返回登录首页

// 每个车次显示
// – 车次
// – 出发日期、出发时间、出发车站
// – 到达日期、到达时间、到达车站
// – 座位类型、本次车票价
//   订票费：5元*车次数
// 
 
    
    die();
}

$UID = $_SESSION['U_ID'];

#$Date =  $data[0]['Date'];
$fdate = new DateTime($data[0]['Date']);
$interval = new DateInterval($data[0]['STimeL']);
$fdate->add($interval);
$Date = $fdate->format('Y-m-d'); //真实发车日期

$B_StartSID = $data[0]['SIDL'];
$B_EndSID = $data[$data['num'] - 1]['SIDR'];

#echo $data['UID'].$Date.' '.$B_StartID.' '.$B_EndID.'<br>';
$qorder = <<<EOF
    BEGIN;
    insert into Booking(B_UserID,B_Date,B_StartSID,B_EndSID,B_Status,B_Price)
    values('$UID','$Date','$B_StartSID','$B_EndSID','normal',$TotalPrice);
EOF;
#currval('booking_b_id_seq')



#$ret = pg_query($qorder);
#echo $qorder;

for ($i=0;$i<$data['num'];$i++){
    $TID = $data[$i]['TID'];
    $T_RNumDep = $data[$i]['L'];
    $T_RNumArr = $data[$i]['R'];
    $T_Date = $data[$i]['Date'];
    $T_TypeTicket = $data[$i]['SeatType'];
    $qorder = $qorder. <<<EOF
        insert into Ticket
        values('$TID',currval('booking_b_id_seq'),'$T_RNumDep','$T_RNumArr','$T_Date','$T_TypeTicket');
EOF;
}

$qorder = $qorder.<<<EOF
    COMMIT;
EOF;

$ret = pg_query($qorder);
pg_close($dbconn);

echo "<script type='text/javascript'> alert('预订成功!'); </script>";
echo("<script>window.location = '/orderlist.php';</script>");



?>


</p>
</body>

</html>
