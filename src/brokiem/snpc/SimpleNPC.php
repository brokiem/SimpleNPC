<?php

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\commands\CommandManager;
use brokiem\snpc\entity\EntityManager;
use pocketmine\plugin\PluginBase;

class SimpleNPC extends PluginBase
{

    public function onEnable(): void
    {
        EntityManager::init($this);
        CommandManager::init($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    }

}