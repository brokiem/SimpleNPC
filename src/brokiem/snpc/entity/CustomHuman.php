<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\NPCManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Human;

class CustomHuman extends Human {
    protected $gravity = 0.0;

    public function getIdentifier(): string {
        return $this->namedtag->getString("Identifier");
    }

    protected function initEntity(): void {
        parent::initEntity();

        $this->setNameTagAlwaysVisible();
        $scale = NPCManager::getInstance()->getConfigNPC(SimpleNPC::getInstance()->getDataFolder() . "npcs/" . $this->namedtag->getString("Identifier") . ".json")->get("scale", 1.0);
        if ($this->getScale() != $scale) {
            $this->setScale($scale);
        }
    }
}