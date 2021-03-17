<?php

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\commands\Commands;
use brokiem\snpc\entity\EntityManager;
use brokiem\snpc\task\async\CheckUpdateTask;
use pocketmine\plugin\PluginBase;

class SimpleNPC extends PluginBase
{
    public const ENTITY_HUMAN = "human";

    /** @var array */
    public $npcType = [
        self::ENTITY_HUMAN
    ];

    /** @var array */
    public $removeNPC = [];
    /** @var int */
    public $maxLookDistance = 10;
    /** @var bool */
    public $lookToPlayersEnabled = true;

    public function onEnable(): void
    {
        EntityManager::init();

        $this->initConfiguration();
        $this->getServer()->getCommandMap()->register("SimpleNPC", new Commands("snpc", $this));
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
        $this->getServer()->getAsyncPool()->submitTask(new CheckUpdateTask());
    }

    private function initConfiguration(): void
    {
        $this->saveDefaultConfig();

        $this->lookToPlayersEnabled = $this->getConfig()->get("enable-look-to-players", true);
        $this->maxLookDistance = $this->getConfig()->get("max-look-distance", 8);
    }
}