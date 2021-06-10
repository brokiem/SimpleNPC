<?php

declare(strict_types=1);

namespace brokiem\snpc\entity\npc;

use brokiem\snpc\entity\BaseNPC;
use pocketmine\entity\EntitySizeInfo;

class AxolotlNPC extends BaseNPC {

    public $height = 0.4;
    public $width = 0.3;

    protected function getInitialSizeInfo(): EntitySizeInfo {
        return new EntitySizeInfo($this->height, $this->width);
    }

    public static function getNetworkTypeId(): string {
        return "minecraft:axolotl";
    }
}