<?php

  namespace ThreadX;

  /**
   * Class ThreadStatusMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadStatusMessage extends ThreadMessage
  {
    protected $status;

    /**
     * Constuct a ThreadStatusMessage
     *
     * @param integer $pid
     * @return ThreadStatusMessage
     */
    public function __construct($pid, $status)
    {
      parent::__construct($pid);

      $this->setStatus($status);
    }

    public function getStatus()
    {
      return $this->status;
    }

    public function setStatus($status)
    {
      $this->status = $status;
    }
  }

?>