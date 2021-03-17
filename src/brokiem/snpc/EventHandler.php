<?php

declare(strict_types=1);

namespace brokiem\snpc;

use pocketmine\event\Listener;

class EventHandler implements Listener
{

    /** @var SimpleNPC */
    private $plugin;

    public function __construct(SimpleNPC $plugin)
    {
        $this->plugin = $plugin;
    }
}