<?php /** @noinspection RandomApiMigrationInspection */

/**
 * Copyright (c) 2021 brokiem
 * SimpleNPC is licensed under the GNU Lesser General Public License v3.0
 */

declare(strict_types=1);

namespace brokiem\snpc\entity;

use pocketmine\block\Air;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;

final class WalkingHuman extends CustomHuman {

    public Vector3 $randomPosition;
    protected $gravity = 0.08;
    protected $jumpVelocity = 0.45;
    private int $findNewPosition = 0;
    private float $speed = 0.35;
    private int $jumpTick = 30;
    protected bool $canWalk = true;

    protected function initEntity(CompoundTag $nbt): void {
        $this->canWalk = true;
        parent::initEntity($nbt);
    }

    public function onUpdate(int $currentTick): bool {
        if ($this->getLocation()->y <= 1) {
            $this->teleport($this->getWorld()->getSpawnLocation());
        }

        if ($this->findNewPosition === 0 || $this->getLocation()->distance($this->randomPosition) <= 2) {
            $this->findNewPosition = mt_rand(150, 300);
            $this->generateRandomPosition();
        }

        --$this->findNewPosition;
        --$this->jumpTick;

        if ($this->isUnderwater()) {
            $this->motion->y = $this->gravity * 2;
            $this->jumpVelocity = 0.54;
        }

        if ($this->shouldJump()) {
            $this->jump();
        }

        $position = $this->randomPosition;
        $x = $position->x - $this->getLocation()->getX();
        $z = $position->z - $this->getLocation()->getZ();

        if ($x * $x + $z * $z < 4 + $this->getScale()) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }

        $this->getLocation()->yaw = rad2deg(atan2(-$x, $z));
        $this->getLocation()->pitch = 0.0;

        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();

        return parent::onUpdate($currentTick);
    }

    public function generateRandomPosition(): void {
        $minX = $this->getLocation()->getFloorX() - 8;
        $maxX = $minX + 16;
        $minY = $this->getLocation()->getFloorY() - 8;
        $maxY = $minY + 16;
        $minZ = $this->getLocation()->getFloorZ() - 8;
        $maxZ = $minZ + 16;
        $world = $this->getWorld();

        $x = mt_rand($minX, $maxX);
        $y = mt_rand($minY, $maxY);
        $z = mt_rand($minZ, $maxZ);

        for ($attempts = 0; $attempts < 16; ++$attempts) {
            while ($y >= 0 and !$world->getBlockAt($x, $y, $z)->isSolid()) {
                $y--;
            }

            $blockAboveEntity = $world->getBlockAt($x, $y + 1, $z);
            $blockBelowEntity = $world->getBlockAt($x + 1, $y - 1, $z);
            if (!$blockAboveEntity instanceof Air || $blockBelowEntity instanceof Liquid) {
                continue;
            }

            break;
        }

        $this->randomPosition = new Vector3($x, $y + 1, $z);
    }

    public function shouldJump(): bool {
        if ($this->jumpTick === 0) {
            $this->jumpTick = 20;
            $pos = $this->getLocation()->add($this->getDirectionVector()->x * $this->getScale(), 0, $this->getDirectionVector()->z * $this->getScale())->round();
            return $this->getWorld()->getBlock($pos)->getId() !== 0 and !$this->getWorld()->getBlock($pos) instanceof Flowable;
        }

        return false;
    }

    public function getSpeed(): float {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }
}