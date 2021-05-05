<?php

	namespace ThreadX;

  /**
   * Runnable Interface
   *
   * @author David Betgen <d.betgen@remote-office.nl>
   * @version 1.0
   */
  interface RunnableInterface
  {
    public function run(Thread $thread);
  }

?>