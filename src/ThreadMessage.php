<?php

	namespace ThreadX;

  /**
   * Class ThreadMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  abstract class ThreadMessage
  {
    protected $pid;

    /**
     * Constuct a ThreadMessage
     *
     * @param integer $pid
     * @return ThreadMessage
     */
    protected function __construct($pid)
    {
      $this->setPid($pid);
    }

    public function getPid()
    {
      return $this->pid;
    }

    public function setPid($pid)
    {
      $this->pid = $pid;
    }
  }

?>