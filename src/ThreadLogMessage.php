<?php

  namespace ThreadX;

  /**
   * Class ThreadLogMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadLogMessage extends ThreadMessage
  {
   	protected $log;

    /**
     * Constuct a ThreadLogMessage
     *
     * @param integer $pid
     * @return ThreadLogMessage
     */
    public function __construct($pid, $log)
    {
      parent::__construct($pid);

      $this->setLog($log);
    }

    public function getLog()
    {
      return $this->log;
    }

    public function setLog($log)
    {
      $this->log = $log;
    }
  }

?>