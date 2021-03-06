<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\User\Test\Unit\Model\Plugin;

/**
 * Test class for \Magento\User\Model\Plugin\AuthorizationRole testing
 */
class AuthorizationRoleTest extends \PHPUnit_Framework_TestCase
{
    /** @var \Magento\User\Model\Plugin\AuthorizationRole */
    protected $model;

    /** @var \Magento\User\Model\ResourceModel\User|\PHPUnit_Framework_MockObject_MockObject */
    protected $userResourceModelMock;

    /** @var \Magento\Authorization\Model\Role|\PHPUnit_Framework_MockObject_MockObject */
    protected $roleMock;

    /**
     * Set required values
     */
    protected function setUp()
    {
        $this->userResourceModelMock = $this->getMockBuilder('Magento\User\Model\ResourceModel\User')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $this->roleMock = $this->getMockBuilder('Magento\Authorization\Model\Role')
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            'Magento\User\Model\Plugin\AuthorizationRole',
            [
                'userResourceModel' => $this->userResourceModelMock
            ]
        );
    }

    public function testAfterSave()
    {
        $this->userResourceModelMock->expects($this->once())->method('updateRoleUsersAcl')->with($this->roleMock);
        $this->assertInstanceOf(
            '\Magento\Authorization\Model\Role',
            $this->model->afterSave($this->roleMock, $this->roleMock)
        );
    }
}
