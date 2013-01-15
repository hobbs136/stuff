<?php
include 'rabbitmq.php';

for ( $i=0; $i<100000; $i++ ) {
	amqp_send('the '.$i.'th message', $routingKey, $exchangeName);
}

?>
