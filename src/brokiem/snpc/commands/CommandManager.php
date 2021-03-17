<?php
declare(strict_types=1);

namespace brokiem\snpc\commands;

use brokiem\snpc\SimpleNPC;

class CommandManager
{
    public static function init(SimpleNPC $plugin): void
    {
        $plugin->getServer()->getCommandMap()->register("SimpleNPC", new Commands("snpc", $plugin));
    }
}