<?php

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\commands\CommandManager;
use brokiem\snpc\entity\EntityManager;
use pocketmine\plugin\PluginBase;

class SimpleNPC extends PluginBase
{
    public const ENTITY_HUMAN = "human";

    /** @var */
    private static $i;

    /** @var array */
    public $npcType = [
        self::ENTITY_HUMAN
    ];

    public function onEnable(): void
    {
        self::$i = $this;
        EntityManager::init($this);
        CommandManager::init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    }

    public static function get(): self
    {
        return self::$i;
    }
}