<?php

  namespace ThreadX;

  /**
   * Class ThreadKeepAliveMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadKeepAliveMessage extends ThreadMessage
  {
  	/**
     * Constuct a ThreadKeepAliveMessage
     *
     * @param integer $pid
     * @return ThreadKeepAliveMessage
     */
    public function __construct($pid)
    {
      parent::__construct($pid);
    }
  }

?>