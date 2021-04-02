<?php

declare(strict_types=1);

namespace brokiem\snpc\event;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEvent;

class SNPCDeletionEvent extends EntityEvent
{
    public function __construct(Entity $entity)
    {
        $this->entity = $entity;
    }
}