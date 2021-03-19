<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\Entity;

class SkeletonNPC extends BaseNPC {

    public const NETWORK_ID = Entity::SKELETON;

    public $height = 1.99;
}
