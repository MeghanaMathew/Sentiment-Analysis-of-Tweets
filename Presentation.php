 <?php session_start();?>
<?php if (isset($_SESSION['name'])){
try{
$conn = new PDO('mysql:host=localhost;dbname=sentiment','root','');
$conn->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);}
catch(PDOException $e){
die('Oops,Something Broke.');
}
$startdate = '2018-01-01';
$enddate = '2018-01-31';
if (isset($_POST['month']) && isset($_POST['year']))
{
	$startdate = $_POST['year']."-".$_POST['month']."-01";
	$enddate = $_POST['year']."-".$_POST['month']."-31";
}
$sql = $conn->prepare('SELECT * FROM data WHERE (date BETWEEN :startdate AND :enddate) and company=:company');
$res = $sql->execute(array('company' => $_SESSION['name'],'startdate' => $startdate ,'enddate' => $enddate));
$userRow=$sql->fetchAll(PDO::FETCH_ASSOC);
$label = array();
$neutweets = array();
$negtweets = array();
$postweets = array();
$nettweets = array();
$direction = array();
$w2 = array();
$w3 = array();
if($sql->rowCount() > 0)
{
	foreach ($userRow as $row)
	{
		$label[] = $row['date'];
		$neutweets[] = $row['neutweets']; 
		$negtweets[] = $row['negtweets']; 
		$postweets[] = $row['postweets'];
		$nettweets[] = $row['postweets']-$row['negtweets'];
		$direction[] = $row['stock_direction']; 
	}
}
$w2[0]=0;$w3[0]=0;$w3[1]=0;
for($i=1;$i<sizeof($direction);$i++)
{
	$direction[$i]= $direction[$i]+$direction[$i-1];
}
for($i=1;$i<sizeof($nettweets);$i++)
{
	$w2[$i]= $nettweets[$i]+$nettweets[$i-1];
}
for($i=2;$i<sizeof($nettweets);$i++)
{
	$w3[$i]= $nettweets[$i] + $nettweets[$i-1]+ $nettweets[$i-2];
}

$labels = implode("','", $label); 
$y1 = implode(",", $nettweets);
$y2 = implode(",", $direction); 
$y3 = implode(",", $w2);
$y4 = implode(",", $w3);
?>
<!doctype html>
<html lang="en">
<head>
	<title>Stock Prediction using Sentiment analysis</title>
		<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<script src="https://cdn.plot.ly/plotly-latest.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css"
integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
	<style type="text/css">
	html,body{height:100%;}
	.sidebar{width: 100%;
    color: #364149;
    background: #f8f9fa;
    border-right: 1px solid rgba(0,0,0,.07);
    padding: 20px;
    text-align: center;}
    .navbar{border-bottom: 1px solid rgba(0,0,0,.07);}
</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-light">
		<a class="navbar-brand" href="#">Stock Market Analysis Tool</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<div class="collapse navbar-collapse" id="navbarNavAltMarkup">
			<div class="navbar-nav">
				<a class="nav-item nav-link" href="index.php">Home</a>
				<a class="nav-item nav-link" href="dashboard.php"> Analysis </a>
				<a class="nav-item nav-link" href="stocks.php">Stocks</a>
				<a class="nav-item nav-link" href="logout.php">Logout</a>
			</div>
		</div>
	</nav>
<div class="sidebar">
	<br>
	<h4>Welcome <?php echo $_SESSION['name'];?></h4>
	<br>
	<form action="dashboard.php" method="post">
	<select name="month">
		<option>Select Month</option>
		<option value="1">January</option>
		<option value="2">February</option>
		<option value="3">March</option>
		<option value="4">April</option>
		<option value="5">May</option>
		<option value="6">June</option>
		<option value="7">July</option>
		<option value="8">August</option>
		<option value="9">September</option>
		<option value="10">October</option>
		<option value="11">November</option>
		<option value="12">December</option>
	</select>
	<select name="year">
		<option>Select Year</option>
		<option value="2018" selected>2018</option>
	</select>
	<input type="submit" name="submit" value="Analyse">
	</form>
</div>
<div class="container">
<br>
<span>Window Size:</span>
<select id="wsize" name="wsize" onchange="redraw();">
	<option value="1">1</option>
	<option value="2">2</option>
	<option value="3">3</option>
</select>
<br>
<div id="myDiv" style="width:100%;display: block;"></div>
<br><br>
<span>Predicted Movement: </span><span id="prediction"></span>
<script type="text/javascript">
var layout = {
        "autosize": true, 
        "title": "<?php echo $_SESSION['name'];?> Sentiment and Stock Analysis", 
        "dragmode": "pan", 
        "yaxis": { 
            "type": "linear", 
            "autorange": true, 
            "title": "Net Tweets"
        },
        "yaxis2": {
    	"title": 'Stock Movement',
    	"titlefont": {"color": 'rgb(148, 103, 189)'},
    	"tickfont": {"color": 'rgb(148, 103, 189)'},
    	"overlaying": 'y',
    	"dtick": 1,
    	"side": 'right'},
        "breakpoints": [], 
        "xaxis": {
            "type": "date", 
            "autorange": true, 
            "tickformat": "%Y-%m-%d",
            "range":[0-1],
            "title": "Date",
            "hovermode": "closest"
        }
    };
var x1 = [<?php echo "'".$labels."'";?>];
var y1 = [<?php echo $y1; ?>];
var x2 = [<?php echo "'".$labels."'";?>]
var y2 = [<?php echo $y2; ?>]
var y3 = [<?php echo $y3; ?>]
var y4 = [<?php echo $y4; ?>]
var data = [{
    x: x1,
    y: y1,
    type: 'scatter',
    name: 'Net Tweets'},
    {
    x: x2,
    y: y2,
    type: 'scatter',name: 'Stock Movement',yaxis:'y2'}
    ];
var prediction = 0;
	if(y1[y1.length-1]>0)
		prediction = 1;
	else
		prediction = -1;
document.getElementById('prediction').innerHTML = prediction;
Plotly.newPlot('myDiv', data,layout);
function redraw(){
	var wsize = document.getElementById("wsize").value;
	if(wsize==1){
		yvar = y1;
		if(y1[y1.length-1]>0)
			prediction = 1;
		else
			prediction = -1;
	}
	else if(wsize==2)
		{
		yvar = y3;
		if(y4[y3.length-1]>0)
			prediction = 1;
		else
			prediction = -1;
		}
	else if(wsize==3)
		{
		yvar = y4;
		if(y4[y4.length-1]>0)
			prediction = 1;
		else
			prediction = -1;
		}
	document.getElementById('prediction').innerHTML = prediction;
	var data = [{
    x: x1,
    y: yvar,
    type: 'scatter',
    name: 'Net Tweets'},
    { x: x2,
        y: y2,
         type: 'scatter',name: 'Stock Movement',yaxis:'y2'}
    ];
	Plotly.newPlot('myDiv',data,layout);
}
</script>
</div>
<?php include('footer.php');?>
<?php 
}else{
	header("location:index.php");			}?>

