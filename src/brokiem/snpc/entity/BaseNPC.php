<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\NPCManager;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;

abstract class BaseNPC extends Entity {
    public const SNPC_ENTITY_ID = null;

    protected $gravity = 0.0;

    public function getIdentifier(): string {
        return $this->namedtag->getString("Identifier");
    }

    protected function initEntity(): void {
        parent::initEntity();
        $this->setGenericFlag(Entity::DATA_FLAG_SILENT, true);
        $this->setNameTagAlwaysVisible();

        $scale = NPCManager::getInstance()->getConfigNPC($this->getIdentifier())->get("scale", 1.0);
        if ($this->getScale() !== (float)$scale) {
            $this->setScale($scale);
        }
    }

    protected function sendSpawnPacket(Player $player): void {
        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->type = is_string(static::SNPC_ENTITY_ID) ? static::SNPC_ENTITY_ID : AddActorPacket::LEGACY_ID_MAP_BC[static::SNPC_ENTITY_ID];
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $pk->headYaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->propertyManager->getAll();

        $player->dataPacket($pk);
    }
}