<?php
declare(strict_types=1);

namespace brokiem\snpc\event;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\entity\EntityEvent;

class SNPCDamageEvent extends EntityEvent implements Cancellable
{
    /** @var Entity */
    private $damager;

    public function __construct(Entity $entity, Entity $damager)
    {
        $this->entity = $entity;
        $this->damager = $damager;
    }

    /** @return Entity */
    public function getDamager(): Entity
    {
        return $this->damager;
    }
}