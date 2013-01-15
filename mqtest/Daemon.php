<?php
class Daemon {
	/**
	 * 
	 * @var array 子进程id列表
	 */
	private $chldPids = array();
	/**
	 * pid文件
	 * @var string
	 */
	private $pidFile;
	/**
	 * 
	 * @var array 子进程工作时要屏蔽的信号集
	 */
	private $sigSetsMask = array();
	/**
	 * 
	 * @var integer 最大子进程数量 默认32
	 */
	private $maxNum = 32;
	/**
	 * 父进程标题
	 * @var string
	 */
	private $procTitle = '';
	/**
	 * 
	 * @var integer 子进程空闲多长时间后退出 单位为秒
	 */
	private $idleTime = 30;
	/**
	 * 正在运行的子进程数量
	 * @var integer
	 */
	private $runningProcNum = 0;
	/**
	 * 子进程要执行的回调函数
	 * @var mixed string or array(object, method)
	 */
	private $callback = null;

	public function __construct() {
		$this->sigSetsMask = array( SIGTERM, SIGUSR1, SIGUSR2, SIGINT, SIGABRT, SIGALRM );
	}
	
	public function start() {
		$this->daemonize( );
		$this->fork();
		while ( true ) {
			$status = '';
			$pid = pcntl_waitpid( -1, $status, WNOHANG );
	
			if ( $pid ) {
				unset( $this->chldPids[$pid] );
			}
	
			if ( !$this->chldPids ) {
				$this->fork();
			}
			usleep( 10000 );
		}
	
	}
	
	public function stop() {
		$pid = file_get_contents( $this->pidFile );
		posix_kill( $pid, SIGTERM );
	}
	
	
	/**
	 * 获取子进程id集合
	 * @return array
	 */
	public function getChldPids() {
		return $this->chldPids;
	}
	
	/**
	 * 设置/获取子进程要屏蔽的信号集合
	 * @param array $sigs
	 */
	public function setSigsetsMask( array $sigs ) {
		$this->sigSetsMask = $sigs;
	}
	public function getSigsetsMask() {
		return $this->sigSetsMask;
	}
	
	/**
	 * 屏蔽/解除屏蔽信号集
	 */
	public function blockSigsets() {
		$this->tellParentIamruning( true );
		if ( $this->sigSetsMask ) {
			pcntl_sigprocmask( SIG_BLOCK, $this->sigSetsMask );
		}
	}
	public function unblockSigsets() {
		$this->tellParentIamruning( false );
		$this->setMaxIdleTime( 10 );
		$this->setIdleAlarm();
		if ( $this->sigSetsMask ) {
			return pcntl_sigprocmask( SIG_UNBLOCK, $this->sigSetsMask );
		}
		return true;
	}

	/**
	 * 设置pid文件
	 * @param string $file
	 */
	public function setPidFile( $file ) {
		$this->pidFile = $file;
	}
	/**
	 * 设定最大子进程数量
	 * @param integer $num
	 */
	public function setMaxProcNum( $num ) {
		$this->maxNum = $num;
	}
	/**
	 * 设定/获取子进程空闲多长时间后退出
	 * @param integer $secs 系统默认 30
	 */
	public function setMaxIdleTime( $secs ) {
		$this->idleTime = $secs;
	}
	public function getMaxIdleTime() {
		return $this->idleTime;
	}

	/**
	 * 设置/获取子进程要执行的回调函数 
	 * @param mixed $callback
	 */
	public function setCallback( $callback ) {
		$this->callback = $callback;
	}

	public function getCallback() {
		return $this->callback;
	}


	/**
	 * 设定子进程进程标题
	 * @param unknown_type $title
	 */
	public function setproctitle( $title ) {
		$this->procTitle = $title;
	}
	/**
	 * 默认设定的主进程信号处理程序
	 * @param integer $sig
	 */
	public function defaultSignal( $sig ) {
		if ( $sig == SIGTERM ) {
			posix_kill( 0, SIGUSR1 );
			@unlink( $this->pidFile );
			exit;
		}

		if ( $sig == SIGUSR1 ) {
			$this->runningProcNum+=1;
			if ( $this->runningProcNum >= count( $this->chldPids ) ) {
				if ( count( $this->chldPids ) < $this->maxNum ) {
					$this->fork();
				}
			}
		}

		if ( $sig == SIGUSR2 ) {
			$this->runningProcNum--;
		}

	}


	private function daemonize( ) {
		if ( file_exists( $this->pidFile ) ) {
			exit( "an instance is running\n" );
		}

		if ( !$this->callback ) {
			exit( 'not set  callback' );
		}

		if ( is_string( $this->callback ) ) {
			$callName = null;
			if ( !is_callable( $this->callback, false, $callName ) ) {
				exit( "$callName is not  callable \n" );
			}
		}elseif ( is_array( $this->callback ) ) {
			$callName = null;
			if ( !is_callable( $this->callback, true, $callName ) ) {
				exit( "$callName is not  callable \n" );
			}
		}else {
			exit( "uncorrect callback \n" );
		}

		$pid = pcntl_fork();
		if ( $pid === -1 ) {
			return false;
		} else if ( $pid ) {
				usleep( 500 );
				exit();                //exit parent
			}

		chdir( "/" );
		umask( 0 );
		$sid = posix_setsid();
		if ( !$sid ) {
			return false;
		}

		$pid = pcntl_fork();
		if ( $pid === -1 ) {
			return false;
		} else if ( $pid ) {
				usleep( 500 );
				exit( 0 );
			}

		if ( !file_put_contents( $this->pidFile, getmypid() ) ) {
			exit( "write pid file failed ({$this->pidFile})\n" );
		}

		pcntl_signal( SIGTERM, array( $this, "defaultSignal" ) );
		pcntl_signal( SIGUSR1, array( $this, "defaultSignal" ) );
		pcntl_signal( SIGUSR2, array( $this, "defaultSignal" ) );


		if ( defined( 'STDIN' ) ) {
			fclose( STDIN );
		}
		if ( defined( 'STDOUT' ) ) {
			fclose( STDOUT );
		}
		if ( defined( 'STDERR' ) ) {
			fclose( STDERR );
		}
	}


	private function setIdleAlarm() {
		pcntl_alarm( $this->idleTime );
	}
	
	private function tellParentIamruning( $running=true ) {
		$sig = $running?SIGUSR1:SIGUSR2;
		posix_kill( posix_getppid(), $sig );
	}

	private function fork( ) {
		$pid = pcntl_fork();
		if ( $pid == -1 ) {
			posix_kill( 0, SIGUSR1 );
			exit;
		}elseif ( $pid ) {
			$this->chldPids[$pid] = 1;
		} else {
		
			if ( $this->procTitle ) {
				setproctitle( $this->procTitle );
			}


			pcntl_signal( SIGTERM, SIG_IGN );
			pcntl_signal( SIGALRM, SIG_DFL );
			pcntl_signal( SIGUSR1, SIG_DFL );
			pcntl_signal( SIGUSR2, SIG_DFL );

			$callback = $this->callback;
			if ( is_string( $callback ) ) {

				$callback( $this );
			}elseif ( is_array( $callback ) ) {
				$o =  $callback[0];
				$method = $callback[1];
				$o->$method( $this );
			}
		}
	}


}
