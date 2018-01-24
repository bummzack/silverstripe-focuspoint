<?php

namespace JonoM\FocusPoint\Tests;


use JonoM\FocusPoint\FieldType\DBFocusPoint;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBField;

class FocusPointTest extends SapphireTest
{
    /** @var DBFocusPoint */
    protected $focusPoint = null;

    public function setUp()
    {
        parent::setUp();
        $this->focusPoint = DBField::create_field(DBFocusPoint::class, [0.5, 0.25]);
    }

    public function testCoordToOffset()
    {
        $this->assertEquals(0.75, DBFocusPoint::focusCoordToOffset('x', 0.5));
        $this->assertEquals(0.625, DBFocusPoint::focusCoordToOffset('x', 0.25));
        $this->assertEquals(0.375, DBFocusPoint::focusCoordToOffset('x', -0.25));
        $this->assertEquals(0, DBFocusPoint::focusCoordToOffset('x', -1));
        $this->assertEquals(0.5, DBFocusPoint::focusCoordToOffset('x', 0));
        $this->assertEquals(1.0, DBFocusPoint::focusCoordToOffset('x', 1));

        $this->assertEquals(0.75, DBFocusPoint::focusCoordToOffset('y', -0.5));
        $this->assertEquals(0.625, DBFocusPoint::focusCoordToOffset('y', -0.25));
        $this->assertEquals(0.375, DBFocusPoint::focusCoordToOffset('y', 0.25));
        $this->assertEquals(0, DBFocusPoint::focusCoordToOffset('y', 1));
        $this->assertEquals(0.5, DBFocusPoint::focusCoordToOffset('y', 0));
        $this->assertEquals(1.0, DBFocusPoint::focusCoordToOffset('y', -1));
    }

    public function testOffsetToCoord()
    {
        $this->assertEquals(0.5, DBFocusPoint::focusOffsetToCoord('x', 0.75));
        $this->assertEquals(0.25, DBFocusPoint::focusOffsetToCoord('x', 0.625));
        $this->assertEquals(-0.25, DBFocusPoint::focusOffsetToCoord('x', 0.375));
        $this->assertEquals(-1, DBFocusPoint::focusOffsetToCoord('x',0));
        $this->assertEquals(0, DBFocusPoint::focusOffsetToCoord('x', 0.5));
        $this->assertEquals(1.0, DBFocusPoint::focusOffsetToCoord('x', 1));

        $this->assertEquals(-0.5, DBFocusPoint::focusOffsetToCoord('y', 0.75));
        $this->assertEquals(-0.25, DBFocusPoint::focusOffsetToCoord('y', 0.625));
        $this->assertEquals(0.25, DBFocusPoint::focusOffsetToCoord('y', 0.375));
        $this->assertEquals(1, DBFocusPoint::focusOffsetToCoord('y', 0));
        $this->assertEquals(0, DBFocusPoint::focusOffsetToCoord('y', 0.5));
        $this->assertEquals(-1.0, DBFocusPoint::focusOffsetToCoord('y', 1));
    }

    public function testCalculateCrop()
    {
        $this->markTestIncomplete('Implement crop calculation tests');
    }
}
