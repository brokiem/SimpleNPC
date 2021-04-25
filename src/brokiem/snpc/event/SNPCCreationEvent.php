<?php

declare(strict_types=1);

namespace brokiem\snpc\event;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityEvent;

class SNPCCreationEvent extends EntityEvent implements Cancellable {
    public function __construct(Entity $entity) {
        $this->entity = $entity;
    }

    //TODO
}