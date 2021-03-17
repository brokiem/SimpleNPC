<?php
declare(strict_types=1);

namespace brokiem\snpc\entity;

use pocketmine\entity\Entity;

class EntityManager
{
    /** @var array */
    private static $entities = [
        CustomHuman::class => ["human", "snpc:human"]
    ];

    public static function init(): void
    {
        foreach (self::$entities as $class => $saveName) {
            Entity::registerEntity($class, true, $saveName);
        }
    }
}