<?php

	namespace ThreadX;

  /**
   * Class Task
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class Task
  {
    protected $id;
    protected $runnable;

    public function __construct($id, RunnableInterface $runnable)
    {
      $this->id = $id;
      $this->runnable = $runnable;
    }

    public function getId()
    {
      return $this->id;
    }

    public function getRunnable()
    {
      return $this->runnable;
    }
  }

?>