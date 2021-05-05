<?php

	namespace ThreadX;

	use LibX\Util\Hashtable;

  /**
   * Class ThreadManager
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadManager
  {
    protected $threads;

    protected static $instance;

    /**
     * Get an instance of this ThreadManager
     *
     * @param void
     * @return ThreadStack
     */
    public static function getInstance()
    {
      if (!isset(self::$instance))
        self::$instance = new self();

      return self::$instance;
    }

    /**
     * Construct a ThreadManager
     *
     * @param void
     * @return ThreadManager
     */
    public function __construct()
    {
      $this->setThreads(new Hashtable());
    }

    /**
     * Get threads of this ThreadManager
     *
     * @param void
     * @return LibXHashtable
     */
    public function getThreads()
    {
      return $this->threads;
    }

    /**
     * Set threads of this ThreadManager
     *
     * @param Hashtable $threads
     * @return void
     */
    public function setThreads(Hashtable $threads)
    {
      $this->threads = $threads;
    }

    /**
     * Add a Thread to this ThreadManager
     *
     * @param Thread $thread
     * @return void
     */
    public function addThread(Thread $thread)
    {
      $this->getThreads()->set(spl_object_hash($thread), $thread);
    }

    /**
     * Remove a Thread to this ThreadManager
     *
     * @param Thread $thread
     * @return void
     */
    public function removeThread(Thread $thread)
    {
      $this->getThreads()->delete(spl_object_hash($thread));
    }

    public function create()
    {
      // Create a new Thread
      $thread = new Thread();

      if($thread->getParentPid() == 0)
      {
        /**
         * Parent process
         */

        // Return thread
        return $thread;
      }
      else
      {
        /**
         * Child process
         */

        // Idle mode (await thread messages)
        $thread->idle();
      }
    }

    public function destroy()
    {

    }
  }

?>