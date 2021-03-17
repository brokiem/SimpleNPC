<?php

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\entity\sHuman;
use brokiem\snpc\entity\sNPC;
use brokiem\snpc\event\SNPCDamageEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EventHandler implements Listener
{

    /** @var SimpleNPC */
    private $plugin;

    public function __construct(SimpleNPC $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onDamage(EntityDamageEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof sHuman || $entity instanceof sNPC) {
            $event->setCancelled();
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            if ($entity instanceof sHuman || $entity instanceof sNPC) {
                $damager = $event->getDamager();

                (new SNPCDamageEvent($entity, $damager));

                if ($damager instanceof Player) {
                    if (isset($this->plugin->removeNPC[$damager->getName()]) && !$entity->isFlaggedForDespawn()) {
                        $entity->flagForDespawn();
                        $damager->sendMessage(TextFormat::GREEN . "The NPC was successfully removed!");
                        unset($this->plugin->removeNPC[$damager->getName()]);
                        return;
                    }

                    if (($commands = $entity->namedtag->getCompoundTag("Commands")) !== null) {
                        foreach ($commands as $stringTag) {
                            $this->plugin->getServer()->getCommandMap()->dispatch(new ConsoleCommandSender(), str_replace("{player}", '"' . $damager->getName() . '"', $stringTag->getValue()));
                        }
                    }
                }

                $event->setCancelled();
            }
        }
    }

    public function onMotion(EntityMotionEvent $event): void
    {
        $entity = $event->getEntity();

        if ($entity instanceof sHuman || $entity instanceof sNPC) {
            $event->setCancelled();
        }
    }
}