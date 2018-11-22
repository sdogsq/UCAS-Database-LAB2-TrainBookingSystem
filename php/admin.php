<!DOCTYPE html>
<html>

<body>
<meta charset="utf-8"/>
<head>
    <title>后台管理</title>
</head>


<h1 align="center">后台管理</h1>


<?php
session_start();
if(!isset($_SESSION['U_Type']) or $_SESSION['U_Type'] != 'admin'){
    header("Location: home.php");  
    exit();
}

echo '<p>欢迎您，管理员 <b>'.$_SESSION['U_UserName'].'</b>.  <a href="login.php?action=logout">Logout</a></p>';

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
    or die('Could not connect: ' . pg_last_error());

$qbook = <<<EOF
    SELECT
        count(*), sum(B_Price)
    FROM
        booking
    WHERE
        B_STATUS = 'normal' or
        B_STATUS = 'expired';
EOF;

$ret = pg_query($qbook);

if ($row = pg_fetch_array($ret)){
    echo '<p>当前总订单数： <b>'.$row['count'].'</b></p>';
    echo '<p>当前总票价：<b>'.$row['sum'].'</b></p><br>';
}else die('error');


$qticket = <<<EOF
    SELECT
        T_TrainID, count(*)
    FROM
        Ticket
    GROUP BY
        T_TrainID
    ORDER BY
        count(*) desc
    LIMIT 10;
EOF;

$rec = pg_query($qticket);

$hnum = pg_num_rows($rec);
$trows = pg_fetch_all($rec);

echo '<p>最热点车次：</p>';
echo '<table border="1">';
echo '<tr><td>车次</td>';
for ($i = 0; $i<$hnum; $i++){
    echo '<td>'.$trows[$i]['t_trainid'].'</td>';
}
echo '</tr>';
echo '<tr><td>总票数</td>';
for ($i = 0; $i<$hnum; $i++){
    echo '<td>'.$trows[$i]['count'].'</td>';
}
echo '</tr>';
echo '</table>';


$quser = <<<EOF
    SELECT
        *
    FROM
        userinfo
    Order By
        U_UserName, U_UserName;
EOF;

$reu = pg_query($quser);

echo '<br><p>用户列表：</p>';

echo '<table border="1">';
echo "<tr>";
echo "<td>身份证号</td>" ;
echo "<td>姓名</td>" ;
echo "<td>手机号</td>" ;
echo "<td>信用卡号</td>" ;
echo "<td>用户名</td>";
echo "<td>用户类型</td>" ;
echo "<td>用户订单</td>";
echo "</tr>";


while ($rowu = pg_fetch_row($reu)){
	$num = count($rowu);

	echo "<tr>";
	for ( $i = 0; $i < $num ; $i = $i + 1 ){
        echo '<td>'.$rowu[$i].'</td>';
	}
    echo "<td><button  onclick=\"{location.href='orderlist.php?name=$rowu[4]'}\">查看</button></td>";
	echo "</tr>";
}

echo "</table>";


// 总订单数
// 总票价
// 最热点车次排序，排名前10的车次
// 当前注册用户列表
?>


<p><a href="Query_Train.html">查询具体车次</a></p>

</body>
</html>
