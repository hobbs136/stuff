<?php
// get a gearman worker
declare( ticks = 1 );
include_once "Daemon.php";
class Workers {
	private $oWorker = null;
	private $servers = array("10.13.23.69:4730","10.13.23.27:4730");
	private $oDaemon = null;
	public function worker($oDaemon) {
		try{
			$this->oDaemon = $oDaemon;
			

			$worker= $this->getWorker();


			$worker->addServers( implode(",", $this->servers) );


			// add the "reverse" function
			$worker->addFunction( "reverse", array($this,"reverse_cb") );
			$worker->addFunction( "reload", array($this,"reloadServers") );

			//10.13.69.89
			// start the worker
			while ( $worker->work() );
		}catch( \GearmanException $e ) {
			usleep( 10000 );
			file_put_contents( "/tmp/worker_quit", $e);
			exit;
		}
	}
	public function reloadServers( $job ) {
		$worker = $this->getWorker();
		$s = $job->workload();
		$a = explode(",", $s);
		$servers = array();
		foreach ($a as $server){
			if (!in_array($server, $this->servers)){
				$servers[] = $server;
				$this->servers[] = $server;
			}
		}
		$worker->addServers(implode(",", $servers));
	}

	private function getWorker( ) {
		if ($this->oWorker == null){
			$this->oWorker = new GearmanWorker();
		}
		
		return $this->oWorker;
	}


	public function reverse_cb( $job) {
		$this->oDaemon->blockSigsets();

		setproctitle( $job->workload() );
		file_put_contents( '/tmp/gearman',  date("Y-m-d H:i:s")."\t" . strrev( $job->workload() )."\n", FILE_APPEND );
		
		$this->oDaemon->unblockSigsets();
		setproctitle( "worker" );
	}
}
$o = new Daemon();
$o->setPidFile( "/tmp/daemon" );
$oWorker = new Workers();

$o->setCallback( array($oWorker,"worker"));
$o->setProcTitle('worker');
$o->setMaxProcNum(256);
$o->start();
