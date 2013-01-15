<?php

/* create our object */
$gmclient= new GearmanClient();
$gmclient->addServers("10.13.23.27:4730,10.13.23.69:4730");

/* add the default server */

/* run reverse client */
for ( $i=0; $i<100000; $i++ ) {
	$job_handle = $gmclient->doBackground( "reverse", "127.0.0.1:4730" );
	//var_dump($job_handle);
	file_put_contents('/tmp/test', $i);
}



?>
