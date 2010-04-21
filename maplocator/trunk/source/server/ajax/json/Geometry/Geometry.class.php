<?php
/*
 * This file is part of the sfMapFishPlugin package.
 * (c) Camptocamp <info@camptocamp.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Geometry : abstract class which represents a geometry.
 *
 * @package    sfMapFishPlugin
 * @subpackage php_geojson
 * @author     Camptocamp <info@camptocamp.com>
 * @version
 */
abstract class Geometry
{
  protected $geom_type;

  abstract public function getCoordinates();

  /**
   * Accessor for the geometry type
   *
   * @return string The Geometry type.
   */
  public function getGeomType()
  {
    return $this->geom_type;
  }

  /**
   * Returns an array suitable for serialization
   *
   * @return array
   */
  public function getGeoInterface()
  {
    return array(
      'type'=> $this->getGeomType(),
      'coordinates'=> $this->getCoordinates()
    );
  }

  /**
   * Shortcut to dump geometry as GeoJSON
   */
  public function __toString()
  {
    return json_encode($this->getGeoInterface());
  }
}



?>