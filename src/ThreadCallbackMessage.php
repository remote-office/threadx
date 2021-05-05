<?php

  namespace ThreadX;

  use Closure;

  /**
   * Class ThreadCallbackMessage
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  class ThreadCallbackMessage extends ThreadMessage
  {
    protected $callable;
    protected $parameters;

    /**
     * Constuct a ThreadCallbackMessage
     *
     * @param integer $pid
     * @param Closure $closure
     * @param array $parameters
     * @return ThreadRunnableMessage
     */
    public function __construct($pid, callable $callable, $parameters = [])
    {
      parent::__construct($pid);

      $this->setCallable($callable);
      $this->setParameters($parameters);
    }

    public function getCallable()
    {
      return $this->callable;
    }

    public function setCallable(callable $callable)
    {
      $this->callable = $callable;
    }

    public function getParameters()
    {
      return $this->parameters;
    }

    public function setParameters($parameters)
    {
      $this->parameters = $parameters;
    }
  }

?>