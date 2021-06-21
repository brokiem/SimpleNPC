<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\NPCManager;
use pocketmine\entity\Human;

class CustomHuman extends Human {
    protected $gravity = 0.0;
    protected bool $canWalk = false;

    public function getIdentifier(): string {
        return $this->namedtag->getString("Identifier");
    }

    public function canWalk(): bool {
        return $this->canWalk;
    }

    protected function initEntity(): void {
        parent::initEntity();

        $this->setNameTagAlwaysVisible();
        $scale = NPCManager::getInstance()->getConfigNPC($this->getIdentifier())->get("scale", 1.0);
        if ($this->getScale() !== (float)$scale) {
            $this->setScale($scale);
        }
    }
}