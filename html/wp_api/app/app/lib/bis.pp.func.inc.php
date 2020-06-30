<?php

function bis_redirect(&$redirect_url)
{
	header("Location: ".$redirect_url);
  echo "<html><head><script language=\"javascript\" type=\"text/javascript\">	window.location=\"".$redirect_url."\"; </script><meta http-equiv=\"refresh\" content=\"1;URL=".$redirect_url."\"></head><body bgcolor=\"white\">&nbsp;</body></html>";
}
function bis_mailer(&$subject,&$message,&$to,&$from,&$replyto,&$Cc,&$Bcc)
{
	$subject = trim($subject);
	$newline = "\r\n";
	$headers .= 'From: '.$from.$newline;
	$headers .= 'Reply-To: '.$replyto.$newline;
	//$headers .= 'To: '.$to.$newline;
	if(strlen($Cc) > 2){ $headers .= 'Cc: '.$Cc.$newline; }
	if(strlen($Bcc) > 2){ $headers .= 'Bcc: '.$Bcc.$newline; }
	$headers .= 'MIME-Version: 1.0'.$newline;
	$headers .= 'Content-type: text/html; charset=iso-8859-1'.$newline;
	$headers .= 'X-Mailer: BISMAIL'.date("Ymdhis").$newline;//PHP/'.phpversion()
	$headers .= 'X-Priority: 1'.$newline;
	//$message = wordwrap($message, 70);
	$message = trim($message);
	if(mail($to,$subject,$message,$headers))
	{ return true; }
	else
	{	return false; }
}
function hash_call(&$METHOD,&$nvpStr)
{
	// /* Sandbox */
	// $V = "64"; $sindash = "="; $dubdash = $sindash.$sindash;
	// $U = "jasbar_1302936879_biz_api1.gmail.com";
	// $P = "1302936900";
	// $S= "AIXHPJfiTm5Nk00hMAhTgURYTH6lAlVFZtGPyAon4sY-t8t7e2fcr79f ";
	// $Ur = base64_decode("aHR0cHM6Ly9hcGktM3Quc2FuZGJveC5wYXlwYWwuY29tL252cA".$sindash);
	// $M = $METHOD;
	
	/* Production */
	$V = "64"; $sindash = "="; $dubdash = $sindash.$sindash;
	$U = "contact_api1.stockupfood.com";
	$P = "K7SJP4ZD93BEPBXX";
	$S= "AFcWxV21C7fd0v3bYYYRCpSSRl31Atm4KwtixV5leq2rtCp.xuvyLj5G ";
	$Ur = base64_decode("aHR0cHM6Ly9hcGktM3QucGF5cGFsLmNvbS9udnA=".$sindash);
	$M = $METHOD;
	
	$url = $Ur;//"https://api-3t.paypal.com/nvp"<-- if prod, https://api-3t.sandbox.paypal.com/nvp<-- if sandbox (the encoded string above);
	$user_agent = "PHP_cUrl_5";
	$proxy      = ""; 
	$data       = "";
	$returnArr  = array();
	
	$data  = "METHOD=".$M."&VERSION=".$V."&PWD=".$P."&USER=".$U."&SIGNATURE=".$S;
	$data .= $nvpStr;
/*  */
$thisUrl = (($_SERVER['SERVER_PORT'] == "80") ? "HTTP" : "HTTPS")."://".$_SERVER['HTTP_HOST'].(($_SERVER['QUERY_STRING']) ? ($_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']) : ($_SERVER['PHP_SELF'])); 

	$ch = curl_init();
	//curl_setopt ($ch, CURLOPT_PROXY, $proxy);
	curl_setopt ($ch, CURLOPT_URL, $url); 
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
  curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent); 
  curl_setopt ($ch, CURLOPT_POST, 1);
	curl_setopt ($ch, CURLOPT_POSTFIELDS, $data);
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1); 
  //curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt ($ch, CURLOPT_TIMEOUT, 120);
	$_SESSION['SRV_DATA_PRE'] = $data;
  $result = curl_exec ($ch);
	if(curl_errno($ch) >= 1) 
	{
	  
		$returnArr['curl_error_no']  = curl_errno($ch) ;
		$returnArr['curl_error_msg'] = curl_error($ch);
		curl_close($ch);
	} 
	else
	{
		$_SESSION['SRV_DATA_POST'] = $result;
		$arrRes = explode("&",$result);
		$arrFields = array();
		foreach($arrRes as $value)
		{
			list($k,$v) = explode("=",$value);
			$returnArr[$k] = $v;			 
		}
	}
  curl_close($ch);
	
	// Connect to the Database
	// include $_SERVER['DOCUMENT_ROOT'].'/includes/db.inc.php';
	
	/*
	if (mysqli_connect_errno()) {
			printf("Connect failed: %s\n", mysqli_connect_error()); exit();
	}else{ $move_on = true; }
  */

	
	// $stmt = mysqli_prepare($conn, "INSERT INTO tbl_pp_transactionlogs (ip_address,c_datetime,pre_data,post_data) VALUES ('".$_SERVER['REMOTE_ADDR']."','".date("Y-m-d H:i:s")."','".base64_encode($_SESSION['SRV_DATA_PRE'])."','".base64_encode($_SESSION['SRV_DATA_POST'])."') "); /* Query 1 */
//mysqli_stmt_bind_param($stmt, "si", $string, $integer);
  // mysqli_stmt_execute($stmt);
	
	
	
	return $returnArr;
}
?>