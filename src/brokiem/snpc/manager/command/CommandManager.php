<?php

declare(strict_types=1);

namespace brokiem\snpc\manager\command;

use brokiem\snpc\SimpleNPC;
use pocketmine\utils\Config;

final class CommandManager {

    private string $identifier;
    private array $commands;

    public function __construct(string $identifier) {
        $this->identifier = $identifier;
        $this->commands = $this->getConfig()->get("commands", []);
    }

    public function getConfig(): Config {
        return new Config(SimpleNPC::getInstance()->getDataFolder() . "npcs/$this->identifier.json", Config::JSON);
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