<?php
function amqp_connection() {
	$amqpConnection = new AMQPConnection();
	//$amqpConnection->setLogin("username");
	//$amqpConnection->setPassword("123456");
	//$amqpConnection->setVhost("virthost");
	$amqpConnection->setHost('10.13.23.69');
	$amqpConnection->connect();

	if(!$amqpConnection->isConnected()) {
		die("Cannot connect to the broker, exiting !\n");
	}
	return $amqpConnection;
}

function amqp_receive($exchangeName, $routingKey, $queueName) {
	$amqpConnection = amqp_connection();

	$channel = new AMQPChannel($amqpConnection);
	$queue = new AMQPQueue($channel);
	$queue->setName($queueName);
	$queue->bind($exchangeName, $routingKey);

	while($message = $queue->get()) {
		echo("Message #".$message->getDeliveryTag()." '".$message->getBody()."'");

		if($message->isRedelivery()) {
			echo("\t(this message has already been delivered)");
		}

		if(rand(0,6) > 4) {
			$queue->ack($message->getDeliveryTag());
			echo("\t(this message has been removed from the queue)");
		}
		print_r($message->getMessageId());
		echo "\n";
	}

	if(!$amqpConnection->disconnect()) {
		throw new Exception("Could not disconnect !");
	}
}

function amqp_send($text, $routingKey, $exchangeName){
	$amqpConnection = amqp_connection();

	$channel = new AMQPChannel($amqpConnection);
	$exchange = new AMQPExchange($channel);
	$exchange->setName($exchangeName);
	$exchange->setType("direct");
	$message = $exchange->publish($text, $routingKey);
	if(!$message) {
		echo "Error: Message '".$message."' was not sent.\n";
	} else {
		//echo "Message '".$message."' sent.\n";
	}

	if (!$amqpConnection->disconnect()) {
		throw new Exception("Could not disconnect !");
	}
}
$exchangeName = "exchange1";
$routingKey = "routing.key";
$queueName = "queue1";

