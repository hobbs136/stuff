<?php
// get a rabbitmq worker
declare( ticks = 1 );
include 'rabbitmq.php';
include_once "Daemon.php";
function worker(){
	$exchangeName = "exchange1";
	$routingKey = "routing.key";
	$queueName = "queue1";
	amqp_receive($exchangeName, $routingKey, $queueName);
}
function worker1($envelope, $queue){
	var_dump($envelope, $queue);
}
worker();
exit;
$o = new Daemon();
$o->setPidFile( "/tmp/daemon" );
$oWorker = new Workers();

$o->setCallback("worker");
$o->setProcTitle('worker');
$o->setMaxProcNum(256);
$o->start();
