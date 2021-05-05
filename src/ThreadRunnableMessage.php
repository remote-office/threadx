<?php

  namespace ThreadX;

  /**
   * Class ThreadRunnableMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadRunnableMessage extends ThreadMessage
  {
    protected $runnable;

    /**
     * Constuct a ThreadRunnableMessage
     *
     * @param integer $pid
     * @param Runnable $runnable
     * @return ThreadRunnableMessage
     */
    public function __construct($pid, RunnableInterface $runnable)
    {
      parent::__construct($pid);

      $this->setRunnable($runnable);
    }

    public function getRunnable()
    {
      return $this->runnable;
    }

    public function setRunnable(RunnableInterface $runnable)
    {
      $this->runnable = $runnable;
    }
  }

?>