#!/usr/bin/env php
<?php

require_once('./websockets.php');

$db = sqlite_open('db.sqlite');###########
$q = sqlite_query($db, 'CREATE TABLE messages (id int, msg TEXT)');###########

class BroadcastWebSocketServer extends WebSocketServer
{
	protected $users = array();
	protected function process($user, $message)
	{
		$this->broadcast($message);
	}
	protected function connected($user)
	{
		global $db;
		//echo "Users: " . count($this->users) . "\n";
		array_push($this->users, $user);
		$q = sqlite_query($db, 'SELECT * FROM messages');###########
		while($entry = sqlite_fetch_array($q))###########
		$this->send($user, $entry['msg']);###########
	}
	
	protected function closed($user)
	{
		$num = array_search($user, $this->users);
		if($num)
			unset($this->users[$num]);
	}
	protected function broadcast($message)
	{
		global $db;
		$q = sqlite_query($db, "INSERT INTO messages (msg) VALUES ('$message')");###########
		$almostsent = array();
		foreach($this->users as $user)
		{
			if(array_search($user, $almostsent) === FALSE)
			{
				$this->send($user,$message);
				array_push($almostsent, $user);
			}
		}
	}
}

$webserver = new BroadcastWebSocketServer("0.0.0.0","9000");

try
{
	$webserver->run();
}
catch (Exception $e)
{
	$webserver->stdout($e->getMessage());
}
