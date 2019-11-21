<?php

namespace App\EventSubscriber;

use Doctrine\ORM\Events;
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;

class historySubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            Events::prePersist => ['prePersist'],
        ];
    }


    public function prePersist(LifecycleEventArgs $args)
    {
        $this->index($args);
    }

    public function index(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();

        if ($entity instanceof \App\Entity\History) {
            $entityManager = $args->getObjectManager();
            $now = new \DateTime(date('Y-m-d H:i:s', time()));
            $entity->setDate($now);
        }
    }

}