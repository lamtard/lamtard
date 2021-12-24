<?php

use Workerman\Worker;
use \Workerman\Connection\ConnectionInterface;

require_once __DIR__ . '/../vendor/autoload.php';
$worker = new Worker('smtp://0.0.0.0:2525');
Worker::$logFile = 'MailServerWorker.log';
$worker->count = 1;
$worker->name = 'Smtp Server';

$worker->onConnect = function(ConnectionInterface $connection) {
	//var_dump('SmtpServer onConnect');
	$connection->send('220 Welcome to Smtp Server on PHPMailServer.');
};
$worker->onMessage = function(ConnectionInterface $connection,$msg) {
	if(isset($connection->startData) and $connection->startData) {
		if($msg != '.') {
			$connection->user['data'] .= $msg;
		} else {
			$connection->startData = FALSE;
			$tomail = str_replace([' ','<','>'],'',$connection->user['to']);
			list($user,$host) = explode('@',$tomail);
			//Not own domain name, forward
			//var_dump(gethostbyname($host),gethostbyaddr($_SERVER['SERVER_ADDR']));
			if($host != '0.0.1') {
				getmxrr($host,$mxhosts,$weight);
				$mx = $mxhosts[array_search(max($weight),$weight)];
				$smtpServerIp = gethostbyname($mx);
			}
			$data = $connection->user['data'];
			$mailUid = md5($connection->user['id'].time());
			$connection->user['data'] = $mailUid;
			file_put_contents('../data/'.$mailUid.'.txt',$data);
			$connection->user['size'] = filesize('../data/'.$mailUid.'.txt');
			if(!file_exists('../data/mailLists.json')) {
				$mailLists = [];
			} else {
				$mailLists = json_decode(file_get_contents('../data/mailLists.json'),TRUE);
			}
			$mailLists[] = $connection->user;
			file_put_contents('../data/mailLists.json',json_encode($mailLists,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
			unset($mailLists);
		}
		$connection->send('250 OK');
	}
	else
	{
		//var_dump('SmtpServer onMessage',$msg);
		//file_put_contents('SmtpServer_onMessage.log','SmtpServer_onMessage=>'.$msg.PHP_EOL.PHP_EOL,FILE_APPEND);
		set_error_handler(function(){});
		@list($cmd,$arg) = explode(' ',$msg,2);
		restore_error_handler();
		$cmd = strtolower($cmd);
		switch($cmd)
		{
			case 'ehlo':// mail sending session started
				$connection->user = [
					'id'=>$arg,
				];
				$connection->send('250 OK');
				break;
			case 'mail':// Identifies the start of the mail transmission by identifying the sender of the mail
				$args = explode(':',$arg);
				$connection->user['from'] = isset($args[1])?trim($args[1]):'';
				$connection->send('250 OK');
				break;
			case 'rcpt':// identify the recipient of the mail
				$args = explode(':',$arg);
				$connection->user['to'] = isset($args[1])?trim($args[1]):'';
				$connection->send('250 OK');
				break;
			case 'data':// start to transmit the content of the mail
				$connection->user['data'] = '';
				$connection->startData = TRUE;
				$connection->send('354 OK');
				break;
			case 'quit':// session end
				$connection->send('250 OK');
				$connection->close();
				break;
		}
	}
};
$worker->onClose = function(ConnectionInterface $connection) {
	//var_dump('SmtpServer onClose');
};
Worker::runAll();
