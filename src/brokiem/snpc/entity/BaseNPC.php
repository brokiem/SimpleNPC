<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;

class BaseNPC extends Entity {
    public const SNPC_ENTITY_ID = 0;

    protected $gravity = 0.0;

    public function getIdentifier(): string{
        return $this->namedtag->getString("Identifier");
    }

    protected function initEntity(): void{
        parent::initEntity();
        $this->setGenericFlag(Entity::DATA_FLAG_SILENT, true);
        $this->setNameTagAlwaysVisible();
    }

    protected function sendSpawnPacket(Player $player): void{
        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->type = AddActorPacket::LEGACY_ID_MAP_BC[static::SNPC_ENTITY_ID];
        $pk->position = $this->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $pk->headYaw = $this->yaw;
        $pk->pitch = $this->pitch;
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->propertyManager->getAll();

        $player->dataPacket($pk);
    }
}