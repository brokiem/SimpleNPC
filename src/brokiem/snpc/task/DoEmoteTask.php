<?php

namespace brokiem\snpc\task;

use brokiem\snpc\entity\CustomHuman;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class DoEmoteTask extends Task
{

    public function onRun(): void
    {
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world)
            if ($world->isLoaded())
                foreach ($world->getEntities() as $NPC)
                    if ($NPC instanceof CustomHuman && $NPC->getEmoteId() !== null)
                        $NPC->broadcastEmote($NPC->getEmoteId());

        /*foreach (NPCManager::getInstance()->getAllNPCs() as $NPC)
            if ($NPC instanceof CustomHuman && $NPC->getEmoteId() !== null)
                $NPC->broadcastEmote($NPC->getEmoteId());*/
    }
}