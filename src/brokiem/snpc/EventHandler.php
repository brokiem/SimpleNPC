<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use brokiem\snpc\manager\NPCManager;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class EventHandler implements Listener {

    private SimpleNPC $plugin;

    public function __construct(SimpleNPC $plugin) {
        $this->plugin = $plugin;
    }

    public function onCommand(CommandEvent $event): void {
        $command = strtolower($event->getCommand());

        // TODO: find another way to fix this
        if ($command === "reload") {
            $event->getSender()->sendMessage(TextFormat::RED . "[SimpleNPC] Don't use reload command or in some cases SimpleNPC NPC's will duplicates!");
            $event->setCancelled();
        }
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        if ($player->hasPermission("simplenpc.notify") and !empty($this->plugin->cachedUpdate)) {
            [$latestVersion, $updateDate, $updateUrl] = $this->plugin->cachedUpdate;

            if ($this->plugin->getDescription()->getVersion() !== $latestVersion) {
                $player->sendMessage(" \n§aSimpleNPC §bv$latestVersion §ahas been released on §b$updateDate. §aDownload the new update at §b$updateUrl\n ");
            }
        }
    }

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();

        if ($entity instanceof CustomHuman || $entity instanceof BaseNPC) {
            $event->setCancelled();
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            if ($entity instanceof CustomHuman || $entity instanceof BaseNPC) {
                $event->setCancelled();

                $damager = $event->getDamager();

                if ($damager instanceof Player) {
                    NPCManager::getInstance()->interactToNPC($entity, $damager);
                }
            }
        }
    }

    public function onDataPacketRecieve(DataPacketReceiveEvent $event): void {
        $player = $event->getPlayer();
        $packet = $event->getPacket();

        if ($packet instanceof InventoryTransactionPacket && $packet->trData instanceof UseItemOnEntityTransactionData && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_INTERACT) {
            $entity = $this->plugin->getServer()->findEntity($packet->trData->getEntityRuntimeId());

            if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
                NPCManager::getInstance()->interactToNPC($entity, $player);
            }
        }
    }

    public function onMotion(EntityMotionEvent $event): void {
        $entity = $event->getEntity();

        if (($entity instanceof CustomHuman) && $entity->canWalk()) {
            $event->setCancelled();
        }
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();

        if (isset($this->plugin->lastHit[$player->getName()])) {
            unset($this->plugin->lastHit[$player->getName()]);
        }

        if (isset($this->plugin->removeNPC[$player->getName()])) {
            unset($this->plugin->removeNPC[$player->getName()]);
        }

        if (isset($this->plugin->idPlayers[$player->getName()])) {
            unset($this->plugin->idPlayers[$player->getName()]);
        }
    }

    public function onMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        if ($this->plugin->settings["lookToPlayersEnabled"]) {
            if ($event->getFrom()->distance($event->getTo()) < 0.1) {
                return;
            }

            foreach ($player->getLevelNonNull()->getNearbyEntities($player->getBoundingBox()->expandedCopy($this->plugin->settings["maxLookDistance"], $this->plugin->settings["maxLookDistance"], $this->plugin->settings["maxLookDistance"]), $player) as $entity) {
                if (($entity instanceof CustomHuman) or $entity instanceof BaseNPC) {
                    $angle = atan2($player->z - $entity->z, $player->x - $entity->x);
                    $yaw = (($angle * 180) / M_PI) - 90;
                    $angle = atan2((new Vector2($entity->x, $entity->z))->distance($player->x, $player->z), $player->y - $entity->y);
                    $pitch = (($angle * 180) / M_PI) - 90;

                    if ($entity instanceof CustomHuman and !$entity->canWalk() and $entity->namedtag->getShort("Rotate", 1) === 1) {
                        $pk = new MovePlayerPacket();
                        $pk->entityRuntimeId = $entity->getId();
                        $pk->position = $entity->add(0, $entity->getEyeHeight());
                        $pk->yaw = $yaw;
                        $pk->pitch = $pitch;
                        $pk->headYaw = $yaw;
                        $pk->onGround = $entity->onGround;
                        $player->sendDataPacket($pk);
                    } elseif ($entity instanceof BaseNPC and $entity->namedtag->getShort("Rotate", 1) === 1) {
                        $pk = new MoveActorAbsolutePacket();
                        $pk->entityRuntimeId = $entity->getId();
                        $pk->position = $entity->asVector3();
                        $pk->xRot = $pitch;
                        $pk->yRot = $yaw;
                        $pk->zRot = $yaw;
                        $player->sendDataPacket($pk);
                    }
                }
            }
        }
    }
}