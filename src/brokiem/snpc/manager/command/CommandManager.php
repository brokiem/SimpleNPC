<?php

declare(strict_types=1);

namespace brokiem\snpc\manager\command;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;

final class CommandManager {

    private array $commands = [];

    public function __construct(CompoundTag $nbt) {
        if (($commandsTag = $nbt->getTag('Commands')) instanceof ListTag) {
            foreach ($commandsTag as $stringTag) {
                $this->add($stringTag->getValue());
            }
        }
    }

    public function add($command): bool {
        return in_array($command, $this->commands, true);
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