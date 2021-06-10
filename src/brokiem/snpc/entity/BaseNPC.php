<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use brokiem\snpc\manager\command\CommandManager;
use brokiem\snpc\SimpleNPC;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\entity\EntitySizeInfo;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute as NetworkAttribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\player\Player;
use pocketmine\utils\Config;

abstract class BaseNPC extends Entity {
    public const SNPC_ENTITY_ID = 0;

    protected $gravity = 0.0;

    private string $identifier;
    protected bool $lookToPlayers;

    protected CommandManager $commandManager;

    public function getIdentifier(): string {
        return $this->identifier;
    }

    protected function initEntity(CompoundTag $nbt): void {
        parent::initEntity($nbt);

        $identifier = $nbt->getString("Identifier");

        $this->identifier = $identifier;
        $this->commandManager = new CommandManager($this);
        $this->lookToPlayers = $this->getConfig()->get("enableRotate", true);

        $this->getNetworkProperties()->setGenericFlag(EntityMetadataFlags::SILENT, true);
        $this->setNameTagAlwaysVisible();

        $scale = (float)$this->getConfig()->get("scale", 1.0);
        if ($this->getScale() !== $scale) {
            $this->setScale($scale);
        }
    }

    public function saveNBT(): CompoundTag {
        $nbt = parent::saveNBT();
        $nbt->setString("Identifier", $this->identifier);
        return $nbt;
    }

    protected function sendSpawnPacket(Player $player): void {
        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->type = static::getNetworkTypeId();
        $pk->position = $this->location->asVector3();
        $pk->motion = $this->getMotion();
        $pk->yaw = $this->location->yaw;
        $pk->headYaw = $this->location->yaw;
        $pk->pitch = $this->location->pitch;
        $pk->attributes = array_map(static function(Attribute $attr): NetworkAttribute {
            return new NetworkAttribute($attr->getId(), $attr->getMinValue(), $attr->getMaxValue(), $attr->getValue(), $attr->getDefaultValue());
        }, $this->attributeMap->getAll());
        $pk->metadata = $this->getAllNetworkData($player->getNetworkSession()->getProtocolId());

        $player->getNetworkSession()->sendDataPacket($pk);
    }

    public function getConfig(): Config {
        return new Config(SimpleNPC::getInstance()->getDataFolder() . "npcs/$this->identifier.json", Config::JSON);
    }

    public function setCanLookToPlayers(bool $value): void {
        $this->getConfig()->set("enableRotate", $value);
        $this->getConfig()->save();

        $this->lookToPlayers = $value;
    }

    public function canLookToPlayers(): bool {
        return $this->lookToPlayers;
    }

    public function getCommandManager(): CommandManager {
        return $this->commandManager;
    }

    abstract protected function getInitialSizeInfo(): EntitySizeInfo;

    abstract public static function getNetworkTypeId(): string;
}