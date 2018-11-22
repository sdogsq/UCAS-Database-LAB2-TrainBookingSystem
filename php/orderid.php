<!DOCTYPE html>
<html>
<head>
    <title>订单查询</title>
</head>
<body>
<meta charset="utf-8"/>



 <h2 align="left">订单查询</h2>

<p><a href="Welcome.php">首页</a></p>
    
<p><?php
header("content-type:text/html;charset=utf-8");  

session_start();
if(!isset($_SESSION['U_ID'])){ // check login
    header("Location: home.html");  
    exit();
}
$UID = $_SESSION['U_ID'];
$FID = $_GET['id'];

// if ($_SESSION['U_Type'] == 'admin' and isset($_GET['name']) ){
//     $FName = $_GET['name'];
// }

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());

$qorder = <<<EOF
    SELECT
        count(*)
    FROM
        booking,userinfo
    WHERE
        B_UserID = '$UID' and
        B_ID = '$FID';
EOF;

$ret = pg_query($qorder);

if ($row = pg_fetch_row($ret)){
    if ($row[0] < 1 and $_SESSION['U_Type'] != 'admin') die('error');
}else die('error');


echo '<p>编号 <b>'.$FID.'</b> 的订单信息如下： <br></p>';

$qticket = <<<EOF
    SELECT
        *
    from
        ticket
    where
        T_BookID = '$FID';
EOF;

$retkt = pg_query($qticket);
$num = 0;
if (($num = pg_num_rows($retkt)) <= 0) die('error');

$data = array(num=>$num);

//{check:'1',num:'1','0':{'TID':'$TID','Date':'$fdate','L':1,'R':$crow,'SeatType':'$pseat[$i]'}
for ($i = 0 ; $i < $num; $i++){
    $row = pg_fetch_array($retkt);
    $data[$i]['TID'] = $row['t_trainid'];
    $data[$i]['Date'] = $row['t_date'];
    $data[$i]['L'] = $row['t_rnumdep'];
    $data[$i]['R'] = $row['t_rnumarr'];
    $data[$i]['SeatType'] = $row['t_typeticket'];
}



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

}

    $UName =$_SESSION['U_UserName'];
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
 
    pg_close($dbconn);
    die();


?>

 

</body>
</html>
