<?php

declare(strict_types=1);

namespace brokiem\snpc\manager\command;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;

final class CommandManager {

    private array $commands;

    public function __construct(CustomHuman|BaseNPC $npc) {
        $this->commands = $npc->getConfig()->get("commands", []);
    }

    public function add($command): bool {
        if (!$this->exists($command)) {
            $this->commands[] = $command;

            return true;
        }

        return false;
    }

    public function exists(string $command): bool {
        if (in_array($command, $this->commands, true)) {
            return true;
        }

        return false;
    }

    public function getAll(): array {
        return $this->commands;
    }

    public function remove($command): bool {
        if ($this->exists($command)) {
            unset($this->commands[$command]);

            return true;
        }

        return false;
    }
}