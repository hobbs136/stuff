<?php
// get a rabbitmq worker
declare( ticks = 1 );
include 'rabbitmq.php';
include_once "Daemon.php";
function worker(Daemon $o){
	global $exchangeName, $routingKey, $queueName;
	amqp_receive($exchangeName, $routingKey, $queueName);
}
function worker1($envelope, $queue){
		global $o;
		$o->blockSigsets();
		$queue->ack($envelope->getDeliveryTag());
		file_put_contents(dirname(__FILE__).'/test',$envelope->getBody()."\n",FILE_APPEND);
		usleep(100000);
		$o->unblockSigsets();
		return false;
}
$o = new Daemon();
$o->setPidFile( "/tmp/daemon" );

$o->setCallback("worker");
$o->setProcTitle('worker');
$o->setMaxProcNum(64);
$o->start();
