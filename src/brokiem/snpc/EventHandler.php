<?php

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc;

use brokiem\snpc\entity\BaseNPC;
use brokiem\snpc\entity\CustomHuman;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\player\Player;

class EventHandler implements Listener {

    public function __construct(private SimpleNPC $plugin) { }

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
            $event->cancel();
        }

        if ($event instanceof EntityDamageByEntityEvent) {
            if ($entity instanceof CustomHuman || $entity instanceof BaseNPC) {
                $event->cancel();

                $damager = $event->getDamager();

                if ($damager instanceof Player) {
                    $entity->interact($damager);
                }
            }
        }
    }

    public function onDataPacketRecieve(DataPacketReceiveEvent $event): void {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();

        if ($player !== null and $packet instanceof InventoryTransactionPacket && $packet->trData instanceof UseItemOnEntityTransactionData && $packet->trData->getActionType() === UseItemOnEntityTransactionData::ACTION_INTERACT) {
            $entity = $this->plugin->getServer()->getWorldManager()->findEntity($packet->trData->getEntityRuntimeId());

            if ($entity instanceof BaseNPC || $entity instanceof CustomHuman) {
                $entity->interact($player);
            }
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

        if ($this->plugin->getConfig()->get("enable-look-to-players", true)) {
            if ($event->getFrom()->distance($event->getTo()) < 0.1) {
                return;
            }

            foreach ($player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($this->plugin->getConfig()->get("max-look-distance", 8), $this->plugin->getConfig()->get("max-look-distance", 8), $this->plugin->getConfig()->get("max-look-distance", 8)), $player) as $entity) {
                if (($entity instanceof CustomHuman) or $entity instanceof BaseNPC) {
                    $angle = atan2($player->getLocation()->z - $entity->getLocation()->z, $player->getLocation()->x - $entity->getLocation()->x);
                    $yaw = (($angle * 180) / M_PI) - 90;
                    $angle = atan2((new Vector2($entity->getLocation()->x, $entity->getLocation()->z))->distance($player->getLocation()->x, $player->getLocation()->z), $player->getLocation()->y - $entity->getLocation()->y);
                    $pitch = (($angle * 180) / M_PI) - 90;

                    if ($entity instanceof CustomHuman and !$entity->canWalk() and $entity->canLookToPlayers()) {
                        $pk = new MovePlayerPacket();
                        $pk->entityRuntimeId = $entity->getId();
                        $pk->position = $entity->getLocation()->add(0, $entity->getEyeHeight(), 0);
                        $pk->yaw = $yaw;
                        $pk->pitch = $pitch;
                        $pk->headYaw = $yaw;
                        $pk->onGround = $entity->onGround;
                        $player->getNetworkSession()->sendDataPacket($pk);
                    } elseif ($entity instanceof BaseNPC and $entity->canLookToPlayers()) {
                        $pk = new MoveActorAbsolutePacket();
                        $pk->entityRuntimeId = $entity->getId();
                        $pk->position = $entity->getLocation()->asVector3();
                        $pk->xRot = $pitch;
                        $pk->yRot = $yaw;
                        $pk->zRot = $yaw;
                        $player->getNetworkSession()->sendDataPacket($pk);
                    }
                }
            }
        }
    }
}