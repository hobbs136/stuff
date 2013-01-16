<?php
function amqp_connection() {
	$amqpConnection = new AMQPConnection();
	//$amqpConnection->setLogin("username");
	//$amqpConnection->setPassword("123456");
	//$amqpConnection->setVhost("virthost");
	$amqpConnection->setHost('10.13.23.27');
	$amqpConnection->connect();
	if(!$amqpConnection->isConnected()) {
		die("Cannot connect to the broker, exiting !\n");
	}
	return $amqpConnection;
}

function amqp_receive($exchangeName, $routingKey, $queueName) {
	$amqpConnection = amqp_connection();

	$channel = new AMQPChannel($amqpConnection);

	/*
	 * 下面声明一个交换器(exchange)
	 * 这段代码可以要也可以不要
	 * 因为consumer仅仅与队列(queue)打交道
	 */
	$exchange = new AMQPExchange($channel);
	$exchange->setType('direct');
	$exchange->setName($exchangeName);
	$exchange->declare();

	$queue = new AMQPQueue($channel);
	$queue->setFlags(AMQP_DURABLE);
	$queue->setName($queueName);
	$queue->declare();

	$queue->bind($exchangeName, $routingKey);
	while(true){
		$message = $queue->consume('worker1');
	}
}


function amqp_send($text, $routingKey, $exchangeName){
	$amqpConnection = amqp_connection();

	$channel = new AMQPChannel($amqpConnection);
	
	/*
	 * 下面声明一个队列(queue)
	 * 这段代码可以要也可以不要
	 * 因为producer只是将消息投递给exchange
	 * exchange和queue的绑定是在consumer一端处理的
	 * declare queue start
	 * */
	$queue = new AMQPQueue($channel);
	$queue->setFlags(AMQP_DURABLE);
	$queue->setName('queue1');
	$queue->declare();

	/**
	 * declare queue end
	 */

	$exchange = new AMQPExchange($channel);
	$exchange->setName($exchangeName);
	$exchange->setFlags(AMQP_DURABLE);
	$exchange->setType("direct");
	$message = $exchange->publish($text, $routingKey);
	if(!$message) {
		echo "Error: Message '".$message."' was not sent.\n";
	} else {
		echo "Message '".$message."' sent.\n";
	}
	/*
	if (!$amqpConnection->disconnect()) {
		throw new Exception("Could not disconnect !");
	}
	 */
}
$exchangeName = "exchange1";
$routingKey = "routing.key";
$queueName = "queue1";

