<?php

namespace Jsor\Doctrine\PostGIS\Test\Event;

use Jsor\Doctrine\PostGIS\Test\AbstractFunctionalTestCase;
use Jsor\Doctrine\PostGIS\Test\fixtures\PointsEntity;
use Jsor\Doctrine\PostGIS\Test\fixtures\ReservedWordsEntity;

class ORMSchemaEventSubscriberTest extends AbstractFunctionalTestCase
{
    public function testEntity()
    {
        $this->_setUpEntitySchema([
            'Jsor\Doctrine\PostGIS\Test\fixtures\PointsEntity',
        ]);

        $em = $this->_getEntityManager();

        $sm = $em->getConnection()->getSchemaManager();
        $table = $sm->listTableDetails('points');
        $this->assertTrue($table->hasIndex('idx_point'));
        $this->assertTrue($table->getIndex('idx_point')->hasFlag('spatial'));

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

        $entity = $em->find('Jsor\Doctrine\PostGIS\Test\fixtures\PointsEntity', 1);

        $this->assertEquals('POINT(1 1)', $entity->getPoint());
        $this->assertEquals('SRID=3785;POINT(1 1)', $entity->getPoint2D());
        $this->assertEquals('SRID=3785;POINT(1 1 1)', $entity->getPoint3DZ());
        $this->assertEquals('SRID=3785;POINTM(1 1 1)', $entity->getPoint3DM());
        $this->assertEquals('SRID=3785;POINT(1 1 1 1)', $entity->getPoint4D());
        $this->assertNull($entity->getPoint2DNullable());
        $this->assertEquals('POINT(1 1)', $entity->getPoint2DNoSrid());
        $this->assertEquals('SRID=4326;POINT(1 1)', $entity->getPointGeography2d());
        $this->assertEquals('SRID=4326;POINT(1 1)', $entity->getPointGeography2dSrid());
    }

    public function testEntityWithReservedWords()
    {
        $this->_setUpEntitySchema([
            'Jsor\Doctrine\PostGIS\Test\fixtures\ReservedWordsEntity',
        ]);

        $em = $this->_getEntityManager();

        $entity = new ReservedWordsEntity([
            'user' => 'POINT(1 1)',
            'primary' => 'SRID=4326;POINT(1 1)',
        ]);

        $em->persist($entity);
        $em->flush();
        $em->clear();

        $entity = $em->find('Jsor\Doctrine\PostGIS\Test\fixtures\ReservedWordsEntity', 1);

        $this->assertEquals('POINT(1 1)', $entity->getUser());
        $this->assertEquals('SRID=4326;POINT(1 1)', $entity->getPrimary());
    }
}
