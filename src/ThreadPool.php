<?php

	namespace ThreadX;



	/**
	 * Class ThreadPool
	 *
	 * @author David Betgen <d.betgen@remote-office.nl>
	 * @versio 1.0
	 */
  class ThreadPool
  {
    protected $size;

    /**
     * Construct a ThreadPool
     *
     * @param integer $size
     * @return ThreadPool
     */
    public function __construct($size)
    {
      // Set size
      $this->size = $size;

      // Install signalhandler on important POSIX signals
      pcntl_signal(SIGUSR1, array($this, 'signal'));
      pcntl_signal(SIGCHLD, array($this, 'signal'));
      pcntl_signal(SIGTERM, array($this, 'signal'));
      pcntl_signal(SIGINT,  array($this, 'signal'));
      pcntl_signal(SIGHUP,  array($this, 'signal'));
    }

    /**
     * Start this ThreadPool
     *
     * @param void
     * @return void
     */
    public function start()
    {
      $threadManager = ThreadManager::getInstance();

      while($threadManager->getThreads()->sizeOf() < $this->size)
      {
        // Create and start a new Thread
        $thread = $threadManager->create();

        // Add thread to manager
        $threadManager->addThread($thread);
      }
    }

    /**
     * Stop this ThreadPool
     *
     * @param void
     * @return void
     */
    public function stop()
    {
      $threadManager = ThreadManager::getInstance();

      foreach($threadManager->getThreads() as $thread)
      {
        // Remove thread from manager
        $threadManager->removeThread($thread);

        // Stop a Thread
        $thread->stop();
      }
    }

    public function thread()
    {
      // Get thread manager
      $threadManager = ThreadManager::getInstance();

      // Get threads
      $threads = $threadManager->getThreads();
      $threads = clone($threads);
      // Reset pointer
      $threads->rewind();

      // Init thread
      $thread = null;

      while($threads->valid() && is_null($thread))
      {
        // Current element
        if($threads->current()->isIdle())
          $thread = $threads->current();

        // Next element
        $threads->next();
      }

      return $thread;
    }

    public function available()
    {
      // Get thread manager
      $threadManager = ThreadManager::getInstance();

      // Get threads
      $threads = $threadManager->getThreads();
      $threads = clone($threads);
      // Reset pointer
      $threads->rewind();

      // Init available
      $available = false;

      while($threads->valid() && !$available)
      {
        // Current element
        if($threads->current()->isIdle())
          $available = true;

        // Next element
        $threads->next();
      }

      return $available;
    }

    /**
     * Wait until all childs have exited
     *
     * @param voud
     * @return void
     */
    protected function wait()
    {
      while(pcntl_waitpid(-1, $status) != -1)
      {
        // Child has exited, no action needed
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
      //echo __METHOD__  . ' ' . $signal . "\n";

      // Handle signal
      switch($signal)
      {
        // Signal when a child has send a message
        case SIGUSR1:
          {
            $threadManager = ThreadManager::getInstance();

            $threads = array();
            $sockets = array();

            foreach($threadManager->getThreads() as $thread)
            {
              if(is_resource($thread->getSocket()))
              {
                // Add thread to lookup table
                $threads[spl_object_hash($thread)] = $thread;
                // Add socket to lookup table
                $sockets[spl_object_hash($thread)] = $thread->getSocket();
              }
            }

            // Zend engine limitation fix
            $null = null;

            if(count($sockets) > 0)
            {
              // Select
              @socket_select($sockets, $null, $null, 0);

              foreach(array_keys($sockets) as $hash)
              {
                $thread = $threads[$hash];
                $data = $thread->read();

                if(!empty($data))
                {
                  // Unserialize ThreadMessage
                  $class = unserialize($data);

                  if(is_subclass_of($class, 'ThreadX\ThreadMessage'))
                  {
                    // Get pid of message
                    $pid = $class->getPid();

                    if($class instanceof ThreadStatusMessage)
                    {
                      //echo '[' . date('Y/m/d H:i:s', time()) . '] -- Thread with pid ' . $thread->getPid() . ' (' . $pid . ') Status update... (' . $class->getStatus() . ')' . "\n";

                      // Get status (Thread::STOPPED or Thread::IDLE)
                      $status = $class->getStatus();

                      // Update status
                      $thread->setStatus($status);
                    }
                    elseif($class instanceof ThreadCallbackMessage)
                    {
                      //echo '[' . date('Y/m/d H:i:s', time()) . '] -- Thread with pid ' . $thread->getPid() . ' (' . $pid . ') Calling callback...' . "\n";

                      // Get callable
                      $callable = $class->getCallable();
                      $parameters = $class->getParameters();

                      // Call it
                      call_user_func_array($callable, $parameters);
                    }
                    elseif($class instanceof ThreadKeepAliveMessage)
                    {
                      //echo '[' . date('Y/m/d H:i:s', time()) . '] -- Thread with pid ' . $thread->getPid() . ' (' . $pid . ') reporting in...' . "\n";
                    }
                    elseif($class instanceof ThreadLogMessage)
                    {
                      $log = $class->getLog();

                      echo '[' . date('Y/m/d H:i:s', time()) . '] -- Thread with pid ' . $thread->getPid() . ' (' . $log . ')' . "\n";
                    }
                    else
                    {
                      //echo '[' . date('Y/m/d H:i:s', time()) . '] -- Thread with pid ' . $thread->getPid() . ' read (' . $data . ')' . "\n";
                    }
                  }
                }
              }
            }

            //echo __METHOD__ . '; Return to parent process... ' . "\n";
          }
          break;

          // Handle signal child
        case SIGCHLD:
          {
            while(($pid = pcntl_waitpid(0, $status, WNOHANG)) > 0)
            {
              if(pcntl_wifexited($status))
              {
                echo 'Child ' .  $pid . ' exited normally' . "\n";
              }
              else
              {
                // Translate status to exit code
                $code = pcntl_wexitstatus($status);

                echo 'Child ' .  $pid . ' exited with code ' .  $code . "\n";
              }
            }
          }
          break;

        case SIGTERM:
        case SIGINT:
          {
            $threadManager = ThreadManager::getInstance();

            foreach($threadManager->getThreads() as $thread)
              posix_kill($thread->getPid(), $signal);

            // Handle sigterm and sigint
            exit();
          }
          break;

        case SIGHUP:
          {
            echo 'Running threads...' . "\n";

            // Handle sighup
            $threadManager = ThreadManager::getInstance();
            foreach($threadManager->getThreads() as $thread)
            {
              if($thread->hasName())
                echo '--' . $thread->getName() . ' - ' . $thread->getPid() . "\n";
            }
          }
          break;
      }
    }
  }

?>
