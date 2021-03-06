<?php
namespace Readerself\CoreBundle\Event;

use Readerself\CoreBundle\Entity\ActionFeed;
use Symfony\Component\EventDispatcher\Event;

class ActionFeedEvent extends Event
{
    protected $data;

    protected $mode;

    public function __construct(ActionFeed $data, $mode)
    {
        $this->data = $data;
        $this->mode = $mode;
    }

    public function getData()
    {
        return $this->data;
    }

    public function getMode()
    {
        return $this->mode;
    }
}
