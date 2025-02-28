<?php

namespace Jsor\Doctrine\PostGIS\Test\Functions;

use Jsor\Doctrine\PostGIS\Test\AbstractFunctionalTestCase;
use Jsor\Doctrine\PostGIS\Test\fixtures\PointsEntity;

class ST_TransformTest extends AbstractFunctionalTestCase
{
    protected function setUp():void
    {
        parent::setUp();

        $this->_setUpEntitySchema([
            'Jsor\Doctrine\PostGIS\Test\fixtures\PointsEntity'
        ]);

        $em = $this->_getEntityManager();

        $entity = new PointsEntity([
            'text' => 'foo',
            'geometry' => 'POINT(1 1)',
            'point' => 'POINT(1 1)',
            'point2D' => 'SRID=3785;POINT(1 1)',
            'point3DZ' => 'SRID=3785;POINT(1 1 1)',
            'point3DM' => 'SRID=3785;POINTM(1 1 1)',
            'point4D' => 'SRID=3785;POINT(1 1 1 1)',
            'point2DNullable' => null,
            'point2DNoSrid' => 'POINT(1 1)',
            'geography' => 'SRID=4326;POINT(1 1)',
            'pointGeography2d' => 'SRID=4326;POINT(1 1)',
            'pointGeography2dSrid' => 'POINT(1 1)',
        ]);

        $em->persist($entity);
        $em->flush();
        $em->clear();
    }

    /**
     * @group postgis-2.x
     */
    public function testQuery1Postgis2()
    {
        $query = $this->_getEntityManager()->createQuery('SELECT ST_AsText(ST_Transform(ST_GeomFromText(\'POLYGON((743238 2967416,743238 2967450,743265 2967450,743265.625 2967416,743238 2967416))\',2249),4326)) AS value FROM Jsor\\Doctrine\\PostGIS\\Test\\fixtures\\PointsEntity point');

        $result = $query->getSingleResult();

        array_walk_recursive($result, function (&$data) {
            if (is_resource($data)) {
                $data = stream_get_contents($data);

                if (false !== ($pos = strpos($data, 'x'))) {
                    $data = substr($data, $pos + 1);
                }
            }

            if (is_string($data)) {
                $data = trim($data);
            }
        });

        $expected = [
  'value' => 'POLYGON((-71.1776848522251 42.39028965129019,-71.17768437663263 42.390382947800894,-71.17758443054647 42.39038266779173,-71.17758259272306 42.390289364798704,-71.1776848522251 42.39028965129019))',
];

        $this->assertEquals($expected, $result);
    }

    /**
     * @group postgis-3.x
     */
    public function testQuery1Postgis3()
    {
        $query = $this->_getEntityManager()->createQuery('SELECT ST_AsText(ST_Transform(ST_GeomFromText(\'POLYGON((743238 2967416,743238 2967450,743265 2967450,743265.625 2967416,743238 2967416))\',2249),4326)) AS value FROM Jsor\\Doctrine\\PostGIS\\Test\\fixtures\\PointsEntity point');

        $result = $query->getSingleResult();

        array_walk_recursive($result, function (&$data) {
            if (is_resource($data)) {
                $data = stream_get_contents($data);

                if (false !== ($pos = strpos($data, 'x'))) {
                    $data = substr($data, $pos + 1);
                }
            }

            if (is_string($data)) {
                $data = trim($data);
            }
        });

        $expected = [
            'value' => 'POLYGON((-71.1776848522251 42.3902896512903,-71.17768437663261 42.390382947801015,-71.17758443054647 42.390382667791854,-71.17758259272304 42.390289364798825,-71.1776848522251 42.3902896512903))',
        ];

        $this->assertEquals($expected, $result);
    }
}
