<?php

namespace Symfony\Bundle\SecurityBundle\Tests\DataCollector;

use Symfony\Bundle\SecurityBundle\DataCollector\SecurityDataCollector;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

class SecurityDataCollectorTest extends \PHPUnit_Framework_TestCase
{
    public function testCollectWhenSecurityIsDisabled()
    {
        $collector = new SecurityDataCollector();
        $collector->collect($this->getRequest(), $this->getResponse());

        $this->assertSame('security', $collector->getName());
        $this->assertFalse($collector->isEnabled());
        $this->assertFalse($collector->isAuthenticated());
        $this->assertNull($collector->getTokenClass());
        $this->assertFalse($collector->supportsRoleHierarchy());
        $this->assertCount(0, $collector->getRoles());
        $this->assertCount(0, $collector->getInheritedRoles());
        $this->assertEmpty($collector->getUser());
    }

    /** @dataProvider provideTokenStorage */
    public function testCollectWhenAuthenticationTokenIsNull($tokenStorage)
    {
        $collector = new SecurityDataCollector($tokenStorage, $this->getRoleHierarchy());
        $collector->collect($this->getRequest(), $this->getResponse());

        $this->assertTrue($collector->isEnabled());
        $this->assertFalse($collector->isAuthenticated());
        $this->assertNull($collector->getTokenClass());
        $this->assertTrue($collector->supportsRoleHierarchy());
        $this->assertCount(0, $collector->getRoles());
        $this->assertCount(0, $collector->getInheritedRoles());
        $this->assertEmpty($collector->getUser());
    }

    public function provideTokenStorage()
    {
        return array(
            array(new TokenStorage()),
            array($this->getMock('Symfony\Component\Security\Core\SecurityContextInterface')),
        );
    }

    /** @dataProvider provideRoles */
    public function testCollectAuthenticationTokenAndRoles(array $roles, array $normalizedRoles, array $inheritedRoles)
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken('hhamon', 'P4$$w0rD', 'provider', $roles));

        $collector = new SecurityDataCollector($tokenStorage, $this->getRoleHierarchy());
        $collector->collect($this->getRequest(), $this->getResponse());

        $this->assertTrue($collector->isEnabled());
        $this->assertTrue($collector->isAuthenticated());
        $this->assertSame('Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken', $collector->getTokenClass());
        $this->assertTrue($collector->supportsRoleHierarchy());
        $this->assertSame($normalizedRoles, $collector->getRoles());
        $this->assertSame($inheritedRoles, $collector->getInheritedRoles());
        $this->assertSame('hhamon', $collector->getUser());
    }

    public function provideRoles()
    {
        return array(
            // Basic roles
            array(
                array('ROLE_USER'),
                array('ROLE_USER'),
                array(),
            ),
            array(
                array(new Role('ROLE_USER')),
                array('ROLE_USER'),
                array(),
            ),
            // Inherited roles
            array(
                array('ROLE_ADMIN'),
                array('ROLE_ADMIN'),
                array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
            ),
            array(
                array(new Role('ROLE_ADMIN')),
                array('ROLE_ADMIN'),
                array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
            ),
        );
    }

    private function getRoleHierarchy()
    {
        return new RoleHierarchy(array(
            'ROLE_ADMIN' => array('ROLE_USER', 'ROLE_ALLOWED_TO_SWITCH'),
        ));
    }

    private function getRequest()
    {
        return $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
    }
    private function getResponse()
    {
        return $this
            ->getMockBuilder('Symfony\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();
    }
}
