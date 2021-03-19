<?php

declare(strict_types=1);

namespace brokiem\snpc\entity;

use pocketmine\block\Liquid;
use pocketmine\math\Vector3;

class WalkingHuman extends CustomHuman
{
    protected $gravity = 0.08;
    /** @var Vector3 */
    private $randomPosition;
    /** @var int */
    private $findNewPosition = 0;
    /** @var float */
    private $speed = 0.35;
    /** @var int */
    private $jumpTick = 25;

    public function onUpdate(int $currentTick): bool
    {
        if ($this->findNewPosition === 0 or $this->distance($this->randomPosition) <= 2) {
            $this->findNewPosition = mt_rand(150, 500);
            $this->generateRandomPosition();
        }

        --$this->findNewPosition;
        --$this->jumpTick;

        if (!$this->isUnderwater() and $this->shouldJump()) {
            $this->jump();
        }

        if ($this->isUnderwater()) {
            $this->motion->y = $this->gravity * 3;
        }

        $position = $this->randomPosition;
        $x = $position->x - $this->getX();
        $z = $position->z - $this->getZ();

        if ($x * $x + $z * $z < 4 + $this->getScale()) {
            $this->motion->x = 0;
            $this->motion->z = 0;
        } else {
            $this->motion->x = $this->getSpeed() * 0.15 * ($x / (abs($x) + abs($z)));
            $this->motion->z = $this->getSpeed() * 0.15 * ($z / (abs($x) + abs($z)));
        }

        $this->yaw = rad2deg(atan2(-$x, $z));
        $this->pitch = 0.0;
        $this->move($this->motion->x, $this->motion->y, $this->motion->z);
        $this->updateMovement();

        return parent::onUpdate($currentTick);
    }

    private function shouldJump(): bool
    {
        if ($this->jumpTick === 0) {
            $this->jumpTick = 25;
            $pos = $this->asVector3()->add($this->getDirectionVector()->x * $this->getScale(), 1, $this->getDirectionVector()->z * $this->getScale())->round();
            return $this->isCollidedHorizontally || $this->getLevel()->getBlock($pos)->getId() !== 0;
        }

        return false;
    }

    private function generateRandomPosition(): void
    {
        $minX = $this->getFloorX() - 8;
        $maxX = $minX + 16;
        $minY = $this->getFloorY() - 8;
        $maxY = $minY + 16;
        $minZ = $this->getFloorZ() - 8;
        $maxZ = $minZ + 16;
        $world = $this->getLevel();

        $x = mt_rand($minX, $maxX);
        $y = mt_rand($minY, $maxY);
        $z = mt_rand($minZ, $maxZ);

        for ($attempts = 0; $attempts < 16; ++$attempts) {
            while ($y >= 0 and !$world->getBlockAt($x, $y, $z)->isSolid()) {
                $y--;
            }

            if ($y < 0) {
                continue;
            }

            $blockAboveEntity = $world->getBlockAt($x, $y + 1, $z);
            $blockBelowEntity = $world->getBlockAt($x + 1, $y - 1, $z);
            if ($blockAboveEntity->isSolid() || $blockAboveEntity->getId() !== 0 || $blockBelowEntity instanceof Liquid) {
                continue;
            }

            break;
        }

        $this->randomPosition = new Vector3($x, $y + 1, $z);
    }

    public function getSpeed()
    {
        return ($this->isUnderwater() ? $this->speed / 2 : $this->speed);
    }
}