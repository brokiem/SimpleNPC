<?php

declare(strict_types=1);

namespace brokiem\snpc\event;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEvent;
use pocketmine\player\Player;

class SNPCCreationEvent extends EntityEvent {
    private Player $creator;

    public function __construct(Entity $entity, Player $creator) {
        $this->entity = $entity;
        $this->creator = $creator;
    }

    public function getCreator(): Player {
        return $this->creator;
    }
}