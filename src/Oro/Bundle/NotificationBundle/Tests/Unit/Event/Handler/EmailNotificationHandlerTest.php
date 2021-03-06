<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Event\Handler;

use Oro\Bundle\NotificationBundle\Event\Handler\EmailNotificationHandler;
use Monolog\Logger;

class EmailNotificationHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TEST_ENTITY_CLASS = 'SomeEntity';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityManager;

    /** @var \Twig_Environment */
    protected $twig;

    /** @var \Swift_Mailer */
    protected $mailer;

    /** @var EmailNotificationHandler */
    protected $handler;

    /** @var Logger */
    protected $logger;

    protected function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $this->twig = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\EmailRenderer')
            ->disableOriginalConstructor()->getMock();

        $this->mailer = $this->getMockBuilder('\Swift_Mailer')
            ->disableOriginalConstructor()->getMock();

        $this->logger = $this->getMockBuilder('Monolog\Logger')
            ->disableOriginalConstructor()->getMock();

        $this->handler = new EmailNotificationHandler(
            $this->twig,
            $this->mailer,
            $this->entityManager,
            'a@a.com',
            $this->logger
        );
        $this->handler->setEnv('prod');
        $this->handler->setMessageLimit(10);
    }

    protected function tearDown()
    {
        unset($this->entityManager);
        unset($this->twig);
        unset($this->securityPolicy);
        unset($this->sandbox);
        unset($this->mailer);
        unset($this->logger);
        unset($this->securityContext);
        unset($this->configProvider);
        unset($this->cache);
        unset($this->handler);
    }

    /**
     * Test handler
     */
    public function testHandle()
    {
        $entity = $this->getMock('Oro\Bundle\TagBundle\Entity\ContainAuthorInterface');
        $event = $this->getMock('Oro\Bundle\NotificationBundle\Event\NotificationEvent', array(), array($entity));
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');
        $template->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('html'));

        $notification = $this->getMock('Oro\Bundle\NotificationBundle\Entity\EmailNotification');
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $recipientList = $this->getMock('Oro\Bundle\NotificationBundle\Entity\RecipientList');
        $notification->expects($this->once())
            ->method('getRecipientList')
            ->will($this->returnValue($recipientList));

        $notifications = array(
            $notification,
        );

        $entity = $this->getMock('Oro\Bundle\TagBundle\Entity\ContainAuthorInterface');

        $repo = $this->getMockBuilder('Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('getRecipientEmails')
            ->with($recipientList, $entity)
            ->will($this->returnValue(array('a@aa.com')));

        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->with('Oro\Bundle\NotificationBundle\Entity\RecipientList')
            ->will($this->returnValue($repo));

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf('\Swift_Message'));

        $this->addJob();

        $this->handler->handle($event, $notifications);
    }

    /**
     * Test handler with expection and empty recipients
     */
    public function testHandleErrors()
    {
        $entity = $this->getMock('Oro\Bundle\TagBundle\Entity\ContainAuthorInterface');
        $event = $this->getMock('Oro\Bundle\NotificationBundle\Event\NotificationEvent', array(), array($entity));
        $event->expects($this->once())
            ->method('getEntity')
            ->will($this->returnValue($entity));

        $template = $this->getMock('Oro\Bundle\EmailBundle\Entity\EmailTemplate');

        $notification = $this->getMock('Oro\Bundle\NotificationBundle\Entity\EmailNotification');
        $notification->expects($this->once())
            ->method('getTemplate')
            ->will($this->returnValue($template));

        $notifications = array(
            $notification,
        );

        $this->twig->expects($this->once())
            ->method('compileMessage')
            ->will($this->throwException(new \Twig_Error('bla bla bla')));

        $this->handler->handle($event, $notifications);
    }

    public function testNotify()
    {
        $params = $this->getMock('Symfony\Component\HttpFoundation\ParameterBag');
        $params->expects($this->once())
            ->method('get')
            ->with('to')
            ->will($this->returnValue(array()));

        $this->assertFalse($this->handler->notify($params));
    }

    /**
     * add job assertions
     */
    public function addJob()
    {
        $query = $this->getMock(
            'Doctrine\ORM\AbstractQuery',
            array('getSQL', 'setMaxResults', 'getOneOrNullResult', 'setParameter', '_doExecute'),
            array(),
            '',
            false
        );

        $query->expects($this->once())->method('getOneOrNullResult')
            ->will($this->returnValue(null));
        $query->expects($this->exactly(2))
            ->method('setParameter')
            ->will($this->returnSelf());
        $query->expects($this->once())
            ->method('setMaxResults')
            ->with($this->equalTo(1))
            ->will($this->returnSelf());

        $this->entityManager->expects($this->once())
            ->method('createQuery')
            ->will($this->returnValue($query));

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');
    }
}
