<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\event;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityEvent;
use pocketmine\Player;

class SNPCDeletionEvent extends EntityEvent {
    private ?Player $deletetor;

    public function __construct(Entity $entity, Player $deletor = null) {
        $this->entity = $entity;
        $this->deletetor = $deletor;
    }

    public function getDeletor(): ?Player {
        return $this->deletetor;
    }
}