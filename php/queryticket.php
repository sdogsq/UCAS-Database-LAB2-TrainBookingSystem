<?php //查询某日车次 站次L->站次R 余票信息

$TID = $_GET['TID'];
$Date = $_GET['Date'];
$L = $_GET['L'];
$R = $_GET['R'];

$dbconn = pg_connect("dbname=trainbookingdb user=dbms password=dbms")
or die('Could not connect: ' . pg_last_error());

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

#$seatleft = array(num=>1, 0=>array(TID=>'k818',Date=>'2018-11-22','L'=>3,'R'=>5,Seat=>'soft') );
echo json_encode($seatleft);

pg_free_result($rticket);
pg_close($dbconn);

?>