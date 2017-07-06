<?php


namespace Zitec\ApiZitecExtension\Data;

use Nelmio\Alice\Fixtures\Loader;
use Nelmio\Alice\Fixtures\Fixture;

/**
 * Class ZitecLoader
 *
 * @author Bianca VADEAN bianca.vadean@zitec.com
 * @copyright Copyright (c) Zitec COM
 */
class ZitecLoader  extends Loader
{
    /**
     * @param array $fixtures
     */
    protected function instantiateFixtures(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $name  = $fixture->getName();
            $instance = $this->instantiator->instantiate($fixture);
            $this->setProperties($instance, $fixture);
            $this->objects->set(
                $name,
                $instance
            );
        }
    }

    /**
     * Adds a property to the instance for each fixture property.
     * If the property is __collection, the metadata is saved in the __collection_info property
     *  and values are saved in __collection.
     *
     * @param mixed $instance
     * @param Fixture $fixture
     */
    public function setProperties($instance, Fixture $fixture)
    {
        $specs = $fixture->getProperties();
        foreach ($specs as $key => $value) {
            if (substr($key, 0, strpos($key, '(')) == "__collection") {
                $instance->__collection = null;
                preg_match_all("/\((.*?)\)/u", $key, $collectionArgs);
                $collectionArgs = explode(',', $collectionArgs[1][0]);
                $instance->__collection_info = [
                    'name' => $collectionArgs[0],
                    'min' => isset($collectionArgs[1]) ? $collectionArgs[1] : null,
                    'max' => isset($collectionArgs[2]) ? $collectionArgs[2] : null,
                ];
            } else {
                $instance->$key = null;
            }
        }
    }
}
