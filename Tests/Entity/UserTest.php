<?php

namespace Hypebeast\WordpressBundle\Tests\Entity;

use Hypebeast\WordpressBundle\Entity\User;
use Hypebeast\WordpressBundle\Entity\UserMeta;

/**
 * Test class for User.
 * Generated by PHPUnit on 2011-09-27 at 15:14:46.
 * 
 * @covers Hypebeast\WordpressBundle\Entity\User
 */
class UserTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var User
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new User;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        
    }

    public function testSetRoles() {
        $this->assertEmpty($this->object->getRoles());
        
        $this->object->setRoles(array('ROLE_WORKER'));
        $this->assertEquals(array('ROLE_WORKER'), $this->object->getRoles());
        
        $this->object->setRoles(array('ROLE_MANAGER', 'ROLE_WORKER'));
        $this->assertEquals(array('ROLE_MANAGER', 'ROLE_WORKER'), $this->object->getRoles());
    }

    public function testGetRoles() {
        $this->assertEmpty($this->object->getRoles());
        
        $capabilities = new UserMeta;
        $capabilities->setKey('wp_capabilities');
        $capabilities->setValue(serialize(
                array('administrator' => 1, 'editor' => 1, 'subscriber' => 1)
        ));
        $this->object->addMetas($capabilities);
        
        $this->assertEquals(
                array('ROLE_WP_ADMINISTRATOR', 'ROLE_WP_EDITOR', 'ROLE_WP_SUBSCRIBER'),
                $this->object->getRoles()
        );
    }

    public function testAddMetas() {
        $this->assertEquals(0, count($this->object->getMetas()));
        
        $meta = new UserMeta;
        $meta->setKey('expected key');
        $meta->setValue(serialize('expected value'));
        $this->object->addMetas($meta);
        
        $this->assertContains($meta, $this->object->getMetas());
    }

    /**
     * @todo Implement testEraseCredentials().
     */
    public function testEraseCredentials() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );
    }

    public function testEqualsReturnsTrueIfUsersAreIdentical() {
        $comparison = new User;
        $comparison->setUsername('mickey mouse');
        $this->object->setUsername('mickey mouse');
        
        $this->assertTrue($this->object->equals($comparison));
    }

    public function testEqualsReturnsFalseIfUsernamesAreDifferent() {
        $comparison = new User;
        $comparison->setUsername('minnie mouse');
        $this->object->setUsername('mickey mouse');
        
        $this->assertFalse($this->object->equals($comparison));
    }

    public function testEqualsReturnsFalseIfIdsAreDifferent() {
        $comparison = new UserMock;
        $comparison->id = 3;
        $comparison->setUsername('mickey mouse');
        $this->object->setUsername('mickey mouse');
        
        $this->assertFalse($this->object->equals($comparison));
    }

}


class UserMock extends User {
    public $id;
}