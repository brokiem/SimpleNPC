<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use pocketmine\entity\Entity;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;

class BaseNPC extends Entity {
    public const SNPC_ENTITY_ID = 0;

    protected $gravity = 0.0;

    public function __construct(Level $level, CompoundTag $nbt){
        $this->setCanSaveWithChunk(false);
        parent::__construct($level, $nbt);
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

    public function getIdentifier(): string{
        return $this->namedtag->getString("Identifier");
    }
}