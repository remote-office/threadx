<?php

	namespace ThreadX;

	use Closure;
	use LibX\Util\Stack;

  /**
   * Class Thread
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class Thread
  {
    const IDLE      = 0;
    const STARTING  = 1;
    const RUNNING   = 2;
    const STOPPING  = 3;
    const STOPPED   = 4;

    protected $id;
    protected $name;
    protected $runnable;

    // Status flags
    protected $status;

    // Internal socket
    protected $socket;

    // Internal values
    protected $ppid;
    protected $pid;
    
    // Shared memory
    protected $memory;

    protected $runnables = [];

    /**
     * Construct a Thread
     *
     * @param string $name
     * @return Thread
     */
    public function __construct($name = 'Thread')
    {
      $this->setId(uniqid());
      $this->setName($name);

      // Set initial status
      $this->setStatus(Thread::IDLE);

      // Create shared memory (64KB)
      $this->memory = new Memory(1024 * 64);
      
      // Init sockets array
      $sockets = array();

      // Create socket pair for interprocess communication
      if(socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets) === false)
        throw new \Exception(__METHOD__ . '; '. socket_strerror(socket_last_error()));

      /**
       * Set communication socket before fork to ensure we can receive messages
       * from childs sending data before fork returns to parent
       */

      // Set communication socket
      $this->setSocket($sockets[1]);

      // Fork the currect process
      $pid = pcntl_fork();

      if($pid == -1)
        throw new \Exception(__METHOD__ . '; Could not fork');

      /**
       * Forking
       *
       * When PID is not equal to zero => Parent process
       * When PID is equal to zero => Child process
       */

      if($pid != 0)
      {
        // Set parent process id
        $this->setParentPid(0);

        // Set PID of this thread
        $this->setPid($pid);
      }
      else
      {
        declare(ticks = 1);

        // Set communication socket
        $this->setSocket($sockets[0]);

        // Set parent process id
        $this->setParentPid(posix_getppid());

        // Set PID of this thread
        $this->setPid(posix_getpid());

        // ReInstall signalhandler on important POSIX signals
        pcntl_signal(SIGUSR2, array($this, 'signal'));
        pcntl_signal(SIGTERM, array($this, 'signal'));
        pcntl_signal(SIGINT,  array($this, 'signal'));
        pcntl_signal(SIGHUP,  array($this, 'signal'));
      }
    }

    /**
     * Handle posix signals
     *
     * @param int $signal
     * @return void
     */
    public function signal($signal)
    {
      echo __METHOD__  . ' ' . $signal . "\n";

      // Handle signal
      switch($signal)
      {
        // Signal when a parent has send a message
        case SIGUSR2:
          {
            // Gather sockets
            $sockets = array($this->getSocket());

            // Select
            @socket_select($sockets, $null, $null, 5);

            // Do we have something to read
            if(count($sockets) > 0)
            {
              $data = $this->read();

              if(!empty($data))
              {
                // Unserialize ThreadMessage
                $class = unserialize($data);

                if(in_array('ThreadX\ThreadMessage', class_parents($class)))
                {
                  if($class instanceof ThreadRunnableMessage)
                  {
                    // Set it
                    $this->setRunnable($class->getRunnable());
                  }
                }
              }
            }
          }
          break;

        case SIGTERM:
        case SIGINT:
          {
            // Handle sigterm and sigint
            exit();
          }
          break;

        case SIGHUP:
          {
            echo 'Hi there... stop poking me!' . "\n";
          }
          break;
      }
    }

    public function __destruct()
    {

    }

    public function getId()
    {
      return $this->id;
    }

    public function setId($id)
    {
      $this->id = $id;
    }

    /**
     * Get name of this Thread
     *
     * @param void
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set PID of this Thread
     *
     * @param int $pid
     * @return void
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Check if this Thread as a name
     *
     * @param void
     * @return string
     */
    public function hasName()
    {
      return !is_null($this->name);
    }

    /**
     * Get socket of this Thread
     *
     * @param void
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Set Socket of this Thread
     *
     * @param resource $socket
     * @return void
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    /**
     * Get PID of this Thread
     *
     * @param void
     * @return integer
     */
    public function getPid()
    {
        return $this->pid;
    }

    /**
     * Set PID of this Thread
     *
     * @param int $pid
     * @return void
     */
    public function setPid($pid)
    {
        $this->pid = $pid;
    }

    public function hasPid()
    {
      return !is_null($this->pid);
    }

    /**
     * Get PPID of this Thread
     *
     * @param void
     * @return integer
     */
    public function getParentPid()
    {
        return $this->ppid;
    }

    /**
     * Set PPID of this Thread
     *
     * @param int $ppid
     * @return void
     */
    public function setParentPid($ppid)
    {
        $this->ppid = $ppid;
    }

    public function hasParentPid()
    {
      return !is_null($this->ppid);
    }

    protected function getRunnable()
    {
      return $this->runnable;
    }

    protected function setRunnable(RunnableInterface $runnable = null)
    {
      $this->runnable = $runnable;
    }

    protected function hasRunnable()
    {
      return !is_null($this->runnable);
    }

    public function getStatus()
    {
      return $this->status;
    }

    public function setStatus($status)
    {
    	// When in child inform parent of status change
    	if($this->getParentPid() != 0)
    	{
    		// Create a ThreadStatusMessage
        $threadStatusMessage = new ThreadStatusMessage($this->getPid(), $status);

        // Serialize
        $data = serialize($threadStatusMessage);

        // Write
        $this->write($data);

        // Notify parent
        posix_kill($this->getParentPid(), SIGUSR1);
    	}

      $this->status = $status;
    }

    /**
     * Check status for idle
     *
     * @param void
     * @return boolean
     */
    public function isIdle()
    {
      return ($this->getStatus() == Thread::IDLE);
    }

    /**
     * Check status for running
     *
     * @param void
     * @return boolean
     */
    public function isRunning()
    {
      return ($this->getStatus() == Thread::RUNNING);
    }

    /**
     * Check status for stopped
     *
     * @param void
     * @return boolean
     */
    public function isStopped()
    {
      return ($this->getStatus() == Thread::STOPPED);
    }

    /**
     * Stop the thread
     *
     * @param void
     * @return void
     */
    public function stop()
    {
      // Close socket
      socket_close($this->getSocket());
    }

    /**
     * Execute a Runnable
     *
     * @param Runnable
     * @return void
     */
    public function execute(RunnableInterface $runnable)
    {
      // Update status
      $this->setStatus(Thread::RUNNING);

      // Create a ThreadRunnableMessage
      $threadRunnableMessage = new ThreadRunnableMessage($this->getPid(), $runnable);

      // Send runnable class to other process
      $data = serialize($threadRunnableMessage);

      // Write to other process
      $this->write($data);

      // Notify child
      posix_kill($this->getPid(), SIGUSR2);
    }

    /**
     * Execute a Runnable
     *
     * @param Runnable
     * @return void
     */
    public function callback(callable $callable, $parameters = [])
    {
      // Create a ThreadCallbackMessage
      $threadCallbackMessage = new ThreadCallbackMessage($this->getPid(), $callable, $parameters);

      // Send runnable class to other process
      $data = serialize($threadCallbackMessage);

      // Write to other process
      $this->write($data);

      // Notify parent
      posix_kill($this->getParentPid(), SIGUSR1);
    }

    /**
     * Send a keep alive message to parent
     *
     * @param void
     * @return void
     */
    public function keepalive()
    {
    	// When in child
    	if($this->getParentPid() != 0)
    	{
	    	// Create a ThreadKeepAliveMessage
	      $threadKeepAliveMessage = new ThreadKeepAliveMessage($this->getPid());

	      // Serialize
	      $data = serialize($threadKeepAliveMessage);

	      // Write
	      $this->write($data);

	      // Notify parent
	      posix_kill($this->getParentPid(), SIGUSR1);
    	}
    }

    /**
     * Send a log message to parent
     *
     * @param string $log
     * @return void
     */
    public function log($log)
    {
    	// When in child
    	if($this->getParentPid() != 0)
    	{
	    	// Create a ThreadLogMessage
	      $threadLogMessage = new ThreadLogMessage($this->getPid(), $log);

	      // Serialize
	      $data = serialize($threadLogMessage);

	      // Write
	      $this->write($data);

	      // Notify parent
	      posix_kill($this->getParentPid(), SIGUSR1);
    	}
    }

    /**
     * Read from thread socket
     *
     * @param void
     * @return string
     */
    public function read()
    {
      // Init data
      $data = null;

      try
      {
        // Read length of data from socket
        if(strlen($length = @socket_read($this->getSocket(), 4)) !== 4)
          throw new \Exception(__METHOD__ . '; socket_read() failed: ' . socket_strerror(socket_last_error()));

        // Unpack length
        $length = unpack('N', $length);
        $length = $length[1];

        // Read data
        if(strlen($data = @socket_read($this->getSocket(), $length)) !== $length)
          throw new \Exception(__METHOD__ . '; socket_read() failed: ' . socket_strerror(socket_last_error()));

        // Uncompress data
        $data = gzinflate($data);
      }
      catch(\Exception $exception)
      {
        echo $exception->getMessage() . "\n";

        // Should only happend when connection fails
        exit;
      }

      return $data;
    }

    /**
     * Write to thread socket
     *
     * @param string $data
     * @return void
     */
    public function write($data)
    {
      // Compress data
      $data = gzdeflate($data);

      // Pack length of data into 32 bit binary string
      $length = pack('N', strlen($data));

      try
      {
        // Write to socket (length of data)
        if(@socket_write($this->getSocket(), $length) !== strlen($length))
          throw new \Exception(__METHOD__ . '; socket_write() failed: ' . socket_strerror(socket_last_error()));

        // Write to socket (data)
        if(@socket_write($this->getSocket(), $data) !== strlen($data))
          throw new \Exception(__METHOD__ . '; socket_write() failed: ' . socket_strerror(socket_last_error()));
      }
      catch(\Exception $exception)
      {
        echo $exception->getMessage() . "\n";

        // Should only happend when connection fails
        exit;
      }
    }

    /**
     * Idle mode (await incomining thread messages)
     *
     * @param void
     * @return void
     */
    public function idle()
    {
      while(true)
      {
        if($this->hasRunnable())
        {
          //echo 'Okay! Okay! I\'m up...' . "\n";

          $runnable = $this->getRunnable();
          $runnable->run($this);

          //echo 'Can I go back to sleep now?' . "\n";

          // Clear runnable
          $this->setRunnable();

          // Update status to idle
          $this->setStatus(Thread::IDLE);
        }
        else
        {
          sleep(1);
          //pcntl_signal_dispatch();
        }
      }
    }

    public function output($output)
    {
      echo $output . "\n";
    }

    /**
     * Wait
     *
     * @param void
     * @return void
     */
    protected function wait($time)
    {
      sleep($time);
    }
  }

  /**
   * Class ThreadStack
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadStack extends Stack
  {
    /**
     * Push a Thread onto the stack
     *
     * @param Thread $thread
     * @return void
     */
    public function push(Thread $thread)
    {
      array_push($this->array, $thread);
    }

    /**
     * Pop a Thread from the stack
     *
     * @param void
     * @return Thread
     */
    public function pop()
    {
      return array_pop($this->array);
    }
  }

?>
