<?php

namespace Jsor\Doctrine\PostGIS\Test\Types;

use Doctrine\DBAL\Types\Type;
use Jsor\Doctrine\PostGIS\Test\AbstractTestCase;
use Jsor\Doctrine\PostGIS\Types\GeographyType;

abstract class AbstractTypeTestCase extends AbstractTestCase
{
    protected $type;

    protected function setUp():void
    {
        $this->_registerTypes();

        $this->type = Type::getType($this->getTypeName());
    }

    abstract protected function getTypeName();

    /**
     * @dataProvider getSQLDeclarationDataProvider
     */
    public function testGetSQLDeclaration($type)
    {
        $defaultSrid = $this->type instanceof GeographyType ? 4326 : 0;

        $this->assertEquals(sprintf('%s(%s, %d)', $this->getTypeName(), $type, $defaultSrid), $this->type->getSqlDeclaration(['name' => 'test', 'geometry_type' => $type], $this->getPlatformMock()));
        $this->assertEquals(sprintf('%s(%s, %d)', $this->getTypeName(), $type, 1234), $this->type->getSqlDeclaration(['name' => 'test', 'geometry_type' => $type, 'srid' => 1234], $this->getPlatformMock()));
    }

    public function getSQLDeclarationDataProvider()
    {
        $dimensions = [
            '',
            'Z',
            'M',
            'ZM',
        ];

        $types = [
            'POINT',
            'LINESTRING',
            'POLYGON',
            'MULTIPOINT',
            'MULTILINESTRING',
            'MULTIPOLYGON',
            'GEOMETRYCOLLECTION',
        ];

        $data = [];

        foreach ($types as $type) {
            foreach ($dimensions as $dimension) {
                $data[] = [$type . $dimension];
            }
        }

        return $data;
    }

    public function testConvertToPHPValue()
    {
        $this->assertIsString($this->type->convertToPHPValue('foo', $this->getPlatformMock()));
        $this->assertIsString($this->type->convertToPHPValue('', $this->getPlatformMock()));
    }

    public function testConvertToDatabaseValue()
    {
        $this->assertIsString($this->type->convertToDatabaseValue('foo', $this->getPlatformMock()));
        $this->assertIsString($this->type->convertToDatabaseValue('', $this->getPlatformMock()));
    }

    public function testNullConversion()
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->getPlatformMock()));
    }

    public function testConvertToPHPValueSQL()
    {
        $this->assertTrue($this->type->canRequireSQLConversion());

        $this->assertEquals('ST_AsEWKT(foo::geometry)', $this->type->convertToPHPValueSQL('foo', $this->getPlatformMock()));
    }

    public function testConvertToDatabaseValueSQL()
    {
        $this->assertTrue($this->type->canRequireSQLConversion());

        $this->assertEquals('ST_GeomFromText(foo)', $this->type->convertToDatabaseValueSQL('foo', $this->getPlatformMock()));
    }
}
